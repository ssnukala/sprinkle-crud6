<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller\Traits;

use Carbon\Carbon;
use Illuminate\Database\Connection;
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
 * This trait uses direct pivot table operations (via DB::table()) instead of
 * Eloquent relationship methods, since CRUD6Model is dynamic and does not have
 * pre-defined relationship methods like roles(), permissions(), etc.
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
                $this->logger->warning("CRUD6 [RelationshipActions] Skipping relationship without name", [
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
                $this->logger->error("CRUD6 [RelationshipActions] Failed to process action", [
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
     * by inserting directly into the pivot table. Uses direct DB operations
     * since CRUD6Model doesn't have pre-defined relationship methods.
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
        // Get relationship configuration from schema
        $relationConfig = $this->findRelationshipConfig($schema, $relationName);
        if (!$relationConfig) {
            $this->logger->warning("CRUD6 [RelationshipActions] Relationship config not found", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'event' => $event,
            ]);
            return;
        }

        // Get pivot table configuration
        $pivotTable = $relationConfig['pivot_table'] ?? null;
        $foreignKey = $relationConfig['foreign_key'] ?? null;
        $relatedKey = $relationConfig['related_key'] ?? null;

        if (!$pivotTable || !$foreignKey || !$relatedKey) {
            $this->logger->warning("CRUD6 [RelationshipActions] Incomplete pivot configuration", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'pivot_table' => $pivotTable,
                'foreign_key' => $foreignKey,
                'related_key' => $relatedKey,
            ]);
            return;
        }

        // Get the model's primary key value
        $primaryKey = $schema['primary_key'] ?? 'id';
        $modelId = $model->{$primaryKey};

        foreach ($attachConfig as $attachItem) {
            if (!is_array($attachItem) || !isset($attachItem['related_id'])) {
                $this->logger->warning("CRUD6 [RelationshipActions] Invalid attach configuration", [
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

            // Build insert data: foreign key, related key, plus any extra pivot data
            $insertData = array_merge([
                $foreignKey => $modelId,
                $relatedKey => $relatedId,
            ], $pivotData);

            // Insert directly into pivot table using the DB connection
            $this->db->table($pivotTable)->insert($insertData);

            $this->debugLog("CRUD6 [RelationshipActions] Attached relationship", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'pivot_table' => $pivotTable,
                'model_id' => $modelId,
                'related_id' => $relatedId,
                'pivot_data' => $pivotData,
            ]);
        }
    }

    /**
     * Process sync action to synchronize related records.
     * 
     * Synchronizes the model's relationship with the IDs provided in the
     * request data. This will attach new records, keep existing ones, and
     * detach any that are not in the provided list. Uses direct DB operations
     * since CRUD6Model doesn't have pre-defined relationship methods.
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

        // Get relationship configuration from schema
        $relationConfig = $this->findRelationshipConfig($schema, $relationName);
        if (!$relationConfig) {
            $this->logger->warning("CRUD6 [RelationshipActions] Relationship config not found for sync", [
                'model' => $schema['model'],
                'relationship' => $relationName,
            ]);
            return;
        }

        // Get pivot table configuration
        $pivotTable = $relationConfig['pivot_table'] ?? null;
        $foreignKey = $relationConfig['foreign_key'] ?? null;
        $relatedKey = $relationConfig['related_key'] ?? null;

        if (!$pivotTable || !$foreignKey || !$relatedKey) {
            $this->logger->warning("CRUD6 [RelationshipActions] Incomplete pivot configuration for sync", [
                'model' => $schema['model'],
                'relationship' => $relationName,
            ]);
            return;
        }

        // Get the model's primary key value
        $primaryKey = $schema['primary_key'] ?? 'id';
        $modelId = $model->{$primaryKey};

        // Get related IDs from data (ensure array format)
        $relatedIds = is_array($data[$fieldName]) ? $data[$fieldName] : [$data[$fieldName]];

        // Filter out empty values
        $relatedIds = array_values(array_filter($relatedIds, function ($id) {
            return $id !== null && $id !== '';
        }));

        // Get current related IDs from pivot table
        $currentIds = $this->db->table($pivotTable)
            ->where($foreignKey, $modelId)
            ->pluck($relatedKey)
            ->toArray();

        // Calculate IDs to attach (new ones not currently in pivot)
        $idsToAttach = array_diff($relatedIds, $currentIds);

        // Calculate IDs to detach (current ones not in new list)
        $idsToDetach = array_diff($currentIds, $relatedIds);

        // Detach records that are no longer needed
        if (!empty($idsToDetach)) {
            $this->db->table($pivotTable)
                ->where($foreignKey, $modelId)
                ->whereIn($relatedKey, $idsToDetach)
                ->delete();
        }

        // Attach new records
        foreach ($idsToAttach as $relatedId) {
            $this->db->table($pivotTable)->insert([
                $foreignKey => $modelId,
                $relatedKey => $relatedId,
            ]);
        }

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
     * Detaches related records from the model's relationship. Can detach
     * all records or specific ones by ID. Uses direct DB operations
     * since CRUD6Model doesn't have pre-defined relationship methods.
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
        // Get relationship configuration from schema
        $relationConfig = $this->findRelationshipConfig($schema, $relationName);
        if (!$relationConfig) {
            $this->logger->warning("CRUD6 [RelationshipActions] Relationship config not found for detach", [
                'model' => $schema['model'],
                'relationship' => $relationName,
                'event' => $event,
            ]);
            return;
        }

        // Get pivot table configuration
        $pivotTable = $relationConfig['pivot_table'] ?? null;
        $foreignKey = $relationConfig['foreign_key'] ?? null;
        $relatedKey = $relationConfig['related_key'] ?? null;

        // Get the model's primary key value
        $primaryKey = $schema['primary_key'] ?? 'id';
        $modelId = $model->{$primaryKey};

        if ($detachConfig === 'all') {
            // For detach all, we only need pivot_table and foreign_key
            if (!$pivotTable || !$foreignKey) {
                $this->logger->warning("CRUD6 [RelationshipActions] Incomplete pivot configuration for detach all", [
                    'model' => $schema['model'],
                    'relationship' => $relationName,
                ]);
                return;
            }

            // Detach all related records
            $this->db->table($pivotTable)
                ->where($foreignKey, $modelId)
                ->delete();

            $this->debugLog("CRUD6 [RelationshipActions] Detached all relationships", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'pivot_table' => $pivotTable,
                'model_id' => $modelId,
            ]);
        } elseif (is_array($detachConfig)) {
            // For detach specific IDs, we also need related_key
            if (!$pivotTable || !$foreignKey || !$relatedKey) {
                $this->logger->warning("CRUD6 [RelationshipActions] Incomplete pivot configuration for detach specific IDs", [
                    'model' => $schema['model'],
                    'relationship' => $relationName,
                    'pivot_table' => $pivotTable,
                    'foreign_key' => $foreignKey,
                    'related_key' => $relatedKey,
                ]);
                return;
            }

            // Detach specific IDs
            $this->db->table($pivotTable)
                ->where($foreignKey, $modelId)
                ->whereIn($relatedKey, $detachConfig)
                ->delete();

            $this->debugLog("CRUD6 [RelationshipActions] Detached specific relationships", [
                'event' => $event,
                'model' => $schema['model'],
                'relationship' => $relationName,
                'pivot_table' => $pivotTable,
                'model_id' => $modelId,
                'related_ids' => $detachConfig,
            ]);
        } else {
            $this->logger->warning("CRUD6 [RelationshipActions] Invalid detach configuration", [
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
     * Log debug message (abstract method that must be implemented by using class).
     * 
     * @param string $message The log message
     * @param array  $context Additional context data
     * 
     * @return void
     */
    abstract protected function debugLog(string $message, array $context = []): void;
}
