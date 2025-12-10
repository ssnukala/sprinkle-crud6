<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller\Traits;

use Carbon\Carbon;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;

/**
 * Trait for processing relationship actions on model operations.
 *
 * Provides automatic management of pivot table entries when creating,
 * updating, or deleting records with many-to-many relationships.
 *
 * Actions are defined in the schema's relationship configuration:
 * - on_create: Triggered after creating a record
 * - on_update: Triggered after updating a record
 * - on_delete: Triggered before deleting a record
 *
 * This trait uses Eloquent's BelongsToMany relationship methods (attach/sync/detach)
 * which are dynamically created on CRUD6Model based on schema configuration.
 * When configureFromSchema() is called, relationships defined in the schema become
 * available as methods (e.g., $model->roles() for a 'roles' relationship).
 */
trait ProcessesRelationshipActions
{
    /**
     * Process relationship actions for a given event.
     *
     * Executes the configured actions (attach, sync, detach) on the model's
     * relationships based on the event type (on_create, on_update, on_delete).
     *
     * @param CRUD6ModelInterface $model  The model instance to process
     * @param array               $schema The schema configuration
     * @param array               $data   The request data (for sync operations)
     * @param string              $event  The event type ('on_create', 'on_update', 'on_delete')
     *
     * @return void
     */
    protected function processRelationshipActions(
        CRUD6ModelInterface $model,
        array $schema,
        array $data,
        string $event
    ): void {
        // Skip if no relationships defined in schema
        if (!isset($schema['relationships']) || !is_array($schema['relationships'])) {
            return;
        }

        // Process each relationship that has actions defined
        foreach ($schema['relationships'] as $relationship) {
            // Skip if no actions defined for this relationship
            if (!isset($relationship['actions'][$event]) || !is_array($relationship['actions'][$event])) {
                continue;
            }

            $relationName = $relationship['name'] ?? null;
            if (!$relationName) {
                $this->logger->warning("Line:61 CRUD6 [RelationshipActions] Skipping relationship without name", [
                    'event' => $event,
                    'model' => $schema['model'],
                ]);
                continue;
            }

            $action = $relationship['actions'][$event];

            try {
                // Process attach action (on_create, on_update)
                if (isset($action['attach']) && is_array($action['attach'])) {
                    $this->processAttachAction($model, $schema, $relationName, $action['attach'], $event);
                }

                // Process sync action (on_update)
                if ($event === 'on_update' && isset($action['sync'])) {
                    $this->processSyncAction($model, $schema, $relationName, $action['sync'], $data);
                }

                // Process detach action (on_update, on_delete)
                if (isset($action['detach'])) {
                    $this->processDetachAction($model, $schema, $relationName, $action['detach'], $event);
                }
            } catch (\Exception $e) {
                $this->logger->error("Line:86 CRUD6 [RelationshipActions] Failed to process action", [
                    'event' => $event,
                    'model' => $schema['model'],
                    'relationship' => $relationName,
                    'error' => $e->getMessage(),
                ]);

                // Re-throw exception to rollback transaction
                throw $e;
            }
        }
    }

    /**
     * Find relationship configuration by name in the schema.
     *
     * Searches through the schema's relationships array to find the configuration
     * for a specific relationship by name.
     *
     * @param array  $schema       The schema configuration
     * @param string $relationName The name of the relationship to find
     *
     * @return array|null The relationship configuration, or null if not found
     */
    protected function findRelationshipConfig(array $schema, string $relationName): ?array
    {
        if (!isset($schema['relationships']) || !is_array($schema['relationships'])) {
            return null;
        }

        foreach ($schema['relationships'] as $relationship) {
            if (($relationship['name'] ?? null) === $relationName) {
                return $relationship;
            }
        }

        return null;
    }

    /**
     * Process attach action to add related records.
     *
     * Attaches one or more related records to the model's relationship
     * using Eloquent's BelongsToMany::attach() method. The relationship
     * method (e.g., roles()) is dynamically available on the model after
     * configureFromSchema() has been called.
     *
     * @param CRUD6ModelInterface $model        The model instance
     * @param array               $schema       The schema configuration
     * @param string              $relationName The name of the relationship
     * @param array               $attachConfig Array of records to attach
     * @param string              $event        The event type
     *
     * @return void
     */
    protected function processAttachAction(
        CRUD6ModelInterface $model,
        array $schema,
        string $relationName,
        array $attachConfig,
        string $event
    ): void {
        foreach ($attachConfig as $attachItem) {
            if (!is_array($attachItem) || !isset($attachItem['related_id'])) {
                $this->logger->warning("Line:150 CRUD6 [RelationshipActions] Invalid attach configuration", [
                    'model' => $schema['model'],
                    'relationship' => $relationName,
                    'event' => $event,
                ]);
                continue;
            }

            $relatedId = $attachItem['related_id'];
            $pivotData = $attachItem['pivot_data'] ?? [];

            // Process special values in pivot data
            $pivotData = $this->processPivotData($pivotData);

            // Use Eloquent's attach method via dynamic relationship
            // e.g., $model->roles()->attach($relatedId, $pivotData)
            $model->{$relationName}()->attach($relatedId, $pivotData);

            $this->debugLog("CRUD6 [RelationshipActions] Attached relationship", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'related_id' => $relatedId,
                'pivot_data' => $pivotData,
            ]);
        }
    }

    /**
     * Process sync action to synchronize related records.
     *
     * Synchronizes the model's relationship with the IDs provided in the
     * request data using Eloquent's BelongsToMany::sync() method. This will
     * attach new records, keep existing ones, and detach any that are not
     * in the provided list.
     *
     * @param CRUD6ModelInterface $model        The model instance
     * @param array               $schema       The schema configuration
     * @param string              $relationName The name of the relationship
     * @param mixed               $syncConfig   Sync configuration (boolean or field name)
     * @param array               $data         The request data
     *
     * @return void
     */
    protected function processSyncAction(
        CRUD6ModelInterface $model,
        array $schema,
        string $relationName,
        mixed $syncConfig,
        array $data
    ): void {
        // Determine field name to read IDs from
        $fieldName = is_string($syncConfig) ? $syncConfig : $relationName . '_ids';

        // Skip if field not present in request data
        if (!isset($data[$fieldName])) {
            $this->debugLog("CRUD6 [RelationshipActions] Sync field not present in data, skipping", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'field' => $fieldName,
            ]);
            return;
        }

        // Get related IDs from data (ensure array format)
        $relatedIds = is_array($data[$fieldName]) ? $data[$fieldName] : [$data[$fieldName]];

        // Filter out empty values and validate that IDs are numeric
        $relatedIds = array_values(array_filter($relatedIds, function ($id) {
            return $id !== null && $id !== '' && (is_numeric($id) || is_string($id));
        }));

        // Use Eloquent's sync method via dynamic relationship
        // e.g., $model->roles()->sync($relatedIds)
        $model->{$relationName}()->sync($relatedIds);

        $this->debugLog("CRUD6 [RelationshipActions] Synced relationship", [
            'model' => $schema['model'],
            'relationship' => $relationName,
            'field' => $fieldName,
            'related_ids' => $relatedIds,
        ]);
    }

    /**
     * Process detach action to remove related records.
     *
     * Detaches related records from the model's relationship using Eloquent's
     * BelongsToMany::detach() method. Can detach all records or specific ones by ID.
     *
     * @param CRUD6ModelInterface $model        The model instance
     * @param array               $schema       The schema configuration
     * @param string              $relationName The name of the relationship
     * @param mixed               $detachConfig Detach configuration ('all' or array of IDs)
     * @param string              $event        The event type
     *
     * @return void
     */
    protected function processDetachAction(
        CRUD6ModelInterface $model,
        array $schema,
        string $relationName,
        mixed $detachConfig,
        string $event
    ): void {
        if ($detachConfig === 'all') {
            // Detach all related records using Eloquent's detach() with no arguments
            // e.g., $model->roles()->detach()
            $model->{$relationName}()->detach();

            $this->debugLog("CRUD6 [RelationshipActions] Detached all relationships", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
            ]);
        } elseif (is_array($detachConfig)) {
            // Detach specific IDs using Eloquent's detach() with IDs array
            // e.g., $model->roles()->detach([1, 2, 3])
            $model->{$relationName}()->detach($detachConfig);

            $this->debugLog("CRUD6 [RelationshipActions] Detached specific relationships", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'related_ids' => $detachConfig,
            ]);
        } else {
            $this->logger->warning("Line:277 CRUD6 [RelationshipActions] Invalid detach configuration", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'config' => $detachConfig,
            ]);
        }
    }

    /**
     * Process special values in pivot data.
     *
     * Replaces special placeholder values with actual values:
     * - "now" -> current timestamp
     * - "current_user" -> authenticated user's ID
     * - "current_date" -> current date (Y-m-d format)
     *
     * @param array $pivotData The pivot data to process
     *
     * @return array The processed pivot data
     */
    protected function processPivotData(array $pivotData): array
    {
        $processed = [];

        foreach ($pivotData as $key => $value) {
            if ($value === 'now') {
                $processed[$key] = Carbon::now()->toDateTimeString();
            } elseif ($value === 'current_user') {
                $currentUser = $this->authenticator->user();
                $processed[$key] = $currentUser ? $currentUser->id : null;
            } elseif ($value === 'current_date') {
                $processed[$key] = Carbon::now()->toDateString();
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Delete child records based on schema's "details" configuration.
     *
     * This method implements cascade deletion by:
     * 1. Reading the "details" section from the schema
     * 2. Identifying child tables that have foreign key references to this model
     * 3. Deleting all child records before the parent is deleted
     *
     * This prevents foreign key constraint violations when deleting records
     * that have dependent child records.
     *
     * For soft deletes:
     * - If the child model supports soft deletes, it will be soft deleted
     * - If the child model doesn't support soft deletes, it will be hard deleted
     * - This can be overridden in the schema using "cascade_delete_mode" in details
     *
     * @param CRUD6ModelInterface $model         The parent model instance to cascade delete from
     * @param array               $schema        The schema configuration
     * @param mixed               $schemaService The schema service for loading child schemas
     * @param bool                $softDelete    Whether this is a soft delete operation
     *
     * @return void
     */
    protected function cascadeDeleteChildRecords(
        CRUD6ModelInterface $model,
        array $schema,
        $schemaService,
        bool $softDelete = false
    ): void {
        // Skip if no details defined in schema
        if (!isset($schema['details']) || !is_array($schema['details'])) {
            return;
        }

        $primaryKey = $schema['primary_key'] ?? 'id';
        $parentId = $model->getAttribute($primaryKey);

        $this->debugLog("CRUD6 [CascadeDelete] Starting cascade delete for child records", [
            'model' => $schema['model'],
            'parent_id' => $parentId,
            'details_count' => count($schema['details']),
            'soft_delete' => $softDelete,
        ]);

        // Process each detail configuration
        foreach ($schema['details'] as $detail) {
            // Skip if no foreign_key defined (not a parent-child relationship)
            if (!isset($detail['foreign_key']) || !isset($detail['model'])) {
                continue;
            }

            $childModel = $detail['model'];
            $foreignKey = $detail['foreign_key'];

            // Check if cascade delete is explicitly disabled for this child
            if (isset($detail['cascade_delete']) && $detail['cascade_delete'] === false) {
                $this->debugLog("CRUD6 [CascadeDelete] Cascade delete disabled for child", [
                    'model' => $schema['model'],
                    'child_model' => $childModel,
                ]);
                continue;
            }

            try {
                // Create a new instance of the child model
                // In CRUD6, all models are CRUD6Model instances configured from schema
                // This is the standard pattern used throughout the system
                $childModelInstance = new \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model();

                // Load schema for the child model to get table name and configuration
                $childSchema = $schemaService->getSchema($childModel);
                if (!$childSchema) {
                    $this->logger->warning("CRUD6 [CascadeDelete] Child model schema not found", [
                        'model' => $schema['model'],
                        'child_model' => $childModel,
                    ]);
                    continue;
                }

                // Configure the child model with its schema
                // This sets table name, columns, relationships, etc.
                $childModelInstance->configureFromSchema($childSchema);

                // Determine delete strategy
                $childSupportsSoftDelete = $childModelInstance->hasSoftDeletes();
                $deleteMode = $detail['cascade_delete_mode'] ?? 'auto';

                // Get all matching child records
                $childRecords = $childModelInstance
                    ->where($foreignKey, '=', $parentId)
                    ->get();

                $deletedCount = 0;

                // Delete child records based on strategy
                foreach ($childRecords as $childRecord) {
                    if ($softDelete && $childSupportsSoftDelete && $deleteMode !== 'hard') {
                        // Soft delete child record
                        // CRUD6Model has custom softDelete() method (doesn't use Laravel's SoftDeletes trait)
                        // This manually sets the deleted_at timestamp
                        $childRecord->softDelete();
                        $deletedCount++;

                        $this->debugLog("CRUD6 [CascadeDelete] Soft deleted child record", [
                            'model' => $schema['model'],
                            'child_model' => $childModel,
                            'child_id' => $childRecord->getAttribute($childSchema['primary_key'] ?? 'id'),
                        ]);
                    } else {
                        // Hard delete child record
                        // Either parent is hard delete, child doesn't support soft delete, or forced hard
                        // Note: CRUD6Model's delete() performs hard delete (doesn't use SoftDeletes trait)
                        $childRecord->delete();
                        $deletedCount++;

                        $this->debugLog("CRUD6 [CascadeDelete] Hard deleted child record", [
                            'model' => $schema['model'],
                            'child_model' => $childModel,
                            'child_id' => $childRecord->getAttribute($childSchema['primary_key'] ?? 'id'),
                            'reason' => !$softDelete ? 'parent_hard_delete' : (!$childSupportsSoftDelete ? 'child_no_soft_delete' : 'forced_hard'),
                        ]);
                    }
                }

                $this->debugLog("CRUD6 [CascadeDelete] Completed cascade delete for child model", [
                    'model' => $schema['model'],
                    'parent_id' => $parentId,
                    'child_model' => $childModel,
                    'foreign_key' => $foreignKey,
                    'deleted_count' => $deletedCount,
                    'delete_type' => $softDelete && $childSupportsSoftDelete ? 'soft' : 'hard',
                ]);
            } catch (\Exception $e) {
                $this->logger->error("CRUD6 [CascadeDelete] Failed to delete child records", [
                    'model' => $schema['model'],
                    'parent_id' => $parentId,
                    'child_model' => $childModel,
                    'foreign_key' => $foreignKey,
                    'error' => $e->getMessage(),
                ]);

                // Re-throw exception to rollback transaction
                throw $e;
            }
        }

        $this->debugLog("CRUD6 [CascadeDelete] Completed cascade delete for all child records", [
            'model' => $schema['model'],
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Log debug message (abstract method that must be implemented by using class).
     *
     * @param string $message The log message
     * @param array  $context Additional context data
     *
     * @return void
     */
    abstract protected function debugLog(string $message, array $context = []): void;
}
