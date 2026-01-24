<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Bakery\Helper;

/**
 * Schema Generator Service
 *
 * Generates CRUD6 schema files based on database table metadata.
 *
 * @author Srinivas Nukala
 */
class SchemaGenerator
{
    /**
     * @var string Base directory for generated schemas
     */
    protected string $schemaDirectory;

    /**
     * @var array CRUD options configuration
     */
    protected array $crudOptions;

    /**
     * Constructor.
     *
     * @param string $schemaDirectory Directory to save generated schema files
     * @param array $crudOptions CRUD options (create, read, update, delete)
     */
    public function __construct(string $schemaDirectory, array $crudOptions = [])
    {
        $this->schemaDirectory = $schemaDirectory;
        $this->crudOptions = array_merge([
            'create' => true,
            'read' => true,
            'update' => true,
            'delete' => true,
            'list' => true,
        ], $crudOptions);
    }

    /**
     * Generate schema file for a table.
     *
     * @param array $tableMetadata Table metadata from DatabaseScanner
     * @param array $relationships Table relationships
     * @return string Generated schema content
     */
    public function generateSchema(array $tableMetadata, array $relationships = []): string
    {
        $tableName = $tableMetadata['name'];
        $columns = $tableMetadata['columns'];
        $primaryKey = $tableMetadata['primaryKey'][0] ?? 'id';

        // Schema structure aligned with sprinkle-crud6 format
        $schema = [
            'model' => $tableName,
            'title' => $this->generateTitle($tableName),
            'singular_title' => $this->generateSingularTitle($tableName),
            'description' => 'Manage ' . $tableName,
            'table' => $tableName,
            'permissions' => $this->generatePermissions($tableName),
            'default_sort' => $this->generateDefaultSort($columns, $primaryKey),
        ];

        // Add detail section if there's a table that references this table via foreign key
        $detailRelation = $this->getDetailRelationship($tableName, $relationships);
        if ($detailRelation !== null) {
            $schema['detail'] = $detailRelation;
        }

        // Generate field definitions
        $schema['fields'] = [];
        foreach ($columns as $column) {
            $field = $this->generateFieldDefinition($column, $primaryKey);
            $schema['fields'][$column['name']] = $field;
        }

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate schema files for multiple tables.
     *
     * Three-phase approach:
     * 1. Generate all schemas with basic structure and store in memory
     * 2. Update detail sections with actual list_fields from related schemas
     * 3. Write all JSON files to disk
     *
     * @param array $tablesMetadata Tables metadata from DatabaseScanner
     * @param array $relationships Relationships from DatabaseScanner
     * @return array Generated file paths
     */
    public function generateSchemas(array $tablesMetadata, array $relationships = []): array
    {
        $generatedFiles = [];
        $generatedSchemas = [];

        // Phase 1: Generate all schemas and store them in memory
        foreach ($tablesMetadata as $tableName => $metadata) {
            $schemaContent = $this->generateSchema($metadata, $relationships);
            $generatedSchemas[$tableName] = json_decode($schemaContent, true);
        }

        // Phase 2: Update detail sections with actual list_fields from related schemas
        foreach ($generatedSchemas as $tableName => $schema) {
            if (isset($schema['detail']) && isset($schema['detail']['model'])) {
                $detailModel = $schema['detail']['model'];

                // Look up the schema for the detail model
                if (isset($generatedSchemas[$detailModel])) {
                    $detailSchema = $generatedSchemas[$detailModel];
                    $schema['detail']['list_fields'] = $this->extractListableFields($detailSchema);
                }
            }

            // Update the schema in the array with the modified detail section
            $generatedSchemas[$tableName] = $schema;
        }

        // Phase 3: Write all JSON files to disk
        foreach ($generatedSchemas as $tableName => $schema) {
            $schemaContent = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $filePath = $this->saveSchema($tableName, $schemaContent);
            $generatedFiles[] = $filePath;
        }

        return $generatedFiles;
    }

    /**
     * Save schema content to file.
     *
     * @param string $tableName Table name
     * @param string $content Schema content
     * @return string File path
     */
    public function saveSchema(string $tableName, string $content): string
    {
        if (!is_dir($this->schemaDirectory)) {
            mkdir($this->schemaDirectory, 0755, true);
        }

        $filePath = $this->schemaDirectory . '/' . $tableName . '.json';
        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Generate human-readable label from column name.
     *
     * @param string $columnName Column name
     * @return string Human-readable label
     */
    protected function generateLabel(string $columnName): string
    {
        // Convert snake_case to Title Case
        $label = str_replace('_', ' ', $columnName);
        return ucwords($label);
    }

    /**
     * Generate title from table name.
     *
     * @param string $tableName Table name
     * @return string Human-readable title
     */
    protected function generateTitle(string $tableName): string
    {
        // Remove common prefixes
        $name = preg_replace('/^(tbl_|test_)/', '', $tableName);

        // Convert snake_case to Title Case
        $title = str_replace('_', ' ', $name);
        $title = ucwords($title);

        return $title . ' Management';
    }

    /**
     * Generate singular title from table name.
     *
     * @param string $tableName Table name
     * @return string Human-readable singular title
     */
    protected function generateSingularTitle(string $tableName): string
    {
        // Remove common prefixes
        $name = preg_replace('/^(tbl_|test_)/', '', $tableName);

        // Convert snake_case to Title Case
        $title = str_replace('_', ' ', $name);
        $title = ucwords($title);

        // Simple singularization (remove trailing 's', 'es', 'ies')
        if (substr($title, -3) === 'ies') {
            $title = substr($title, 0, -3) . 'y';
        } elseif (substr($title, -2) === 'es') {
            $title = substr($title, 0, -2);
        } elseif (substr($title, -1) === 's') {
            $title = substr($title, 0, -1);
        }

        return $title;
    }

    /**
     * Generate permissions object for the schema.
     *
     * @param string $tableName Table name
     * @return array Permissions configuration
     */
    protected function generatePermissions(string $tableName): array
    {
        // Remove common prefixes
        $name = preg_replace('/^(tbl_|test_)/', '', $tableName);
        $singular = rtrim($name, 's');

        return [
            'read' => 'uri_' . $name,
            'create' => 'create_' . $singular,
            'update' => 'update_' . $singular,
            'delete' => 'delete_' . $singular,
        ];
    }

    /**
     * Generate default sort configuration.
     *
     * @param array $columns Table columns
     * @param string $primaryKey Primary key column name
     * @return array Default sort configuration
     */
    protected function generateDefaultSort(array $columns, string $primaryKey): array
    {
        // Try to find a name-like column
        $nameColumns = ['name', 'title', 'slug', 'user_name', 'username'];

        foreach ($nameColumns as $nameCol) {
            if (isset($columns[$nameCol])) {
                return [$nameCol => 'asc'];
            }
        }

        // Fall back to primary key
        return [$primaryKey => 'asc'];
    }

    /**
     * Get detail relationship for tables that reference the current table.
     *
     * This method finds tables that have a foreign key pointing to the current table,
     * without trying to infer the specific relationship type (hasMany, hasOne, etc.).
     * The presence of a foreign key is sufficient to establish a related table.
     *
     * Since relationships now only track 'references', we need to search through
     * all tables to find ones that reference the current table.
     *
     * @param string $tableName Current table name
     * @param array $relationships All relationships
     * @return array|null Detail relationship or null
     */
    protected function getDetailRelationship(string $tableName, array $relationships): ?array
    {
        // Search through all tables to find one that references the current table
        foreach ($relationships as $otherTableName => $tableRelationships) {
            if (!isset($tableRelationships['references']) || empty($tableRelationships['references'])) {
                continue;
            }

            // Check if this table references the current table
            foreach ($tableRelationships['references'] as $reference) {
                if ($reference['table'] === $tableName) {
                    // Found a table that references the current table
                    return [
                        'model' => $otherTableName,
                        'foreign_key' => $reference['localKey'], // FK column in the referencing table
                        'list_fields' => $this->getDefaultListFields($otherTableName),
                        'title' => strtoupper($tableName) . '.' . strtoupper($otherTableName),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get default list fields for a detail relationship.
     *
     * @param string $tableName Table name
     * @return array Default list fields
     */
    protected function getDefaultListFields(string $tableName): array
    {
        // Default common fields that are likely to exist
        $commonFields = ['id', 'name', 'title', 'email', 'status', 'created_at'];

        // Return a reasonable default set
        return array_slice($commonFields, 0, 5);
    }

    /**
     * Extract listable fields from a generated schema.
     *
     * Returns field names that have 'listable' set to true in the schema.
     * Limits the number of fields to a reasonable amount for display.
     *
     * @param array $schema Generated schema array
     * @param int $maxFields Maximum number of fields to return (default: 5)
     * @return array Array of field names
     */
    protected function extractListableFields(array $schema, int $maxFields = 5): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return [];
        }

        $listableFields = [];

        foreach ($schema['fields'] as $fieldName => $fieldDefinition) {
            // Check if field is marked as listable
            if (isset($fieldDefinition['listable']) && $fieldDefinition['listable'] === true) {
                $listableFields[] = $fieldName;
            }
        }

        // Limit to maxFields, ensuring 'id' is first if present
        if (count($listableFields) > $maxFields) {
            $hasId = in_array('id', $listableFields);

            if ($hasId) {
                // Keep 'id' first and take the next (maxFields - 1) fields
                $otherFields = array_diff($listableFields, ['id']);
                $listableFields = array_merge(['id'], array_slice($otherFields, 0, $maxFields - 1));
            } else {
                // Just take the first maxFields
                $listableFields = array_slice($listableFields, 0, $maxFields);
            }
        }

        return $listableFields;
    }

    /**
     * Generate field definition in the new format.
     *
     * @param array $column Column metadata
     * @param string $primaryKey Primary key column name
     * @return array Field definition
     */
    protected function generateFieldDefinition(array $column, string $primaryKey): array
    {
        $fieldType = $this->mapDatabaseTypeToSchemaType($column['type']);
        $columnName = $column['name'];

        // Check if this is a timestamp field
        $isTimestamp = in_array($columnName, ['created_at', 'updated_at', 'deleted_at']);

        $field = [
            'type' => $fieldType,
            'label' => $this->generateLabel($columnName),
        ];

        // Add auto_increment flag
        if ($column['autoincrement']) {
            $field['auto_increment'] = true;
        }

        // Add readonly flag for primary keys, auto-increment fields, and timestamps
        if ($column['autoincrement'] || $columnName === $primaryKey || $isTimestamp) {
            $field['readonly'] = true;
        }

        // Add required flag (opposite of nullable, but not for timestamps or auto-increment fields)
        if (!$column['nullable'] && !$isTimestamp && !$column['autoincrement']) {
            $field['required'] = true;
        }

        // Add display flags
        $field['sortable'] = $this->isSortable($column, $fieldType);
        $field['filterable'] = $this->isFilterable($column, $fieldType);
        $field['searchable'] = $this->isSearchable($column, $fieldType);
        // use the isListable method to determine listable flag and add the sensitive fields check to that method
        $field['listable'] = $this->isListable($column, $fieldType);

        // Add validation rules as object (not array)
        $validationRules = $this->generateValidationObject($column);
        if (!empty($validationRules)) {
            $field['validation'] = $validationRules;
        }

        return $field;
    }

    /**
     * Map database column type to schema type.
     *
     * @param string $dbType Database column type
     * @return string Schema field type
     */
    protected function mapDatabaseTypeToSchemaType(string $dbType): string
    {
        $typeMap = [
            'integer' => 'integer',
            'smallint' => 'integer',
            'bigint' => 'integer',
            'decimal' => 'decimal',
            'float' => 'float',
            'string' => 'string',
            'text' => 'text',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'json' => 'json',
            'blob' => 'blob',
        ];

        return $typeMap[$dbType] ?? 'string';
    }

    /**
     * Determine if a field should be sortable.
     *
     * @param array $column Column metadata
     * @param string $fieldType Field type
     * @return bool
     */
    protected function isSortable(array $column, string $fieldType): bool
    {
        // Text fields and commonly sorted types are sortable
        if (in_array($fieldType, ['string', 'integer', 'date', 'datetime', 'decimal', 'float'])) {
            return true;
        }

        // Large text fields are not sortable
        if ($fieldType === 'text' || $fieldType === 'blob') {
            return false;
        }

        return true;
    }

    /**
     * Determine if a field should be filterable.
     *
     * @param array $column Column metadata
     * @param string $fieldType Field type
     * @return bool
     */
    protected function isFilterable(array $column, string $fieldType): bool
    {
        // ID fields and timestamps are not typically filtered
        if ($column['autoincrement'] || in_array($column['name'], ['created_at', 'updated_at'])) {
            return false;
        }

        // String, boolean, and enum-like fields are filterable
        if (in_array($fieldType, ['string', 'boolean', 'integer'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a field should be searchable.
     *
     * @param array $column Column metadata
     * @param string $fieldType Field type
     * @return bool
     */
    protected function isSearchable(array $column, string $fieldType): bool
    {
        // ID and timestamps are not searchable
        if ($column['autoincrement'] || in_array($column['name'], ['created_at', 'updated_at'])) {
            return false;
        }

        // String and text fields are searchable
        if (in_array($fieldType, ['string', 'text'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a field should be filterable.
     *
     * @param array $column Column metadata
     * @param string $fieldType Field type
     * @return bool
     */
    protected function isListable(array $column, string $fieldType): bool
    {
        // ID fields and timestamps are not typically listed
        if (in_array($column['name'], ['created_at', 'updated_at', 'password', 'token', 'secret'])) {
            return false;
        }

        // String, boolean, and enum-like fields are listable
        if (in_array($fieldType, ['string', 'boolean', 'integer'])) {
            return true;
        }

        return false;
    }


    /**
     * Generate validation rules as an object (not array).
     *
     * @param array $column Column metadata
     * @return array Validation rules object
     */
    protected function generateValidationObject(array $column): array
    {
        $rules = [];

        // Required rule (but not for timestamps or auto-increment fields)
        if (!$column['nullable'] && !in_array($column['name'], ['created_at', 'updated_at', 'deleted_at']) && !$column['autoincrement']) {
            $rules['required'] = true;
        }

        // Length constraints for string fields
        if ($column['length'] && in_array($column['type'], ['string', 'text'])) {
            $rules['length'] = [
                'min' => 1,
                'max' => $column['length'],
            ];
        }

        // Email validation
        if (strpos($column['name'], 'email') !== false) {
            $rules['email'] = true;
        }

        // URL validation
        if (strpos($column['name'], 'url') !== false || strpos($column['name'], 'link') !== false) {
            $rules['url'] = true;
        }

        // Slug validation
        if (strpos($column['name'], 'slug') !== false) {
            $rules['slug'] = true;
        }

        return $rules;
    }

    /**
     * Get schema directory path.
     *
     * @return string
     */
    public function getSchemaDirectory(): string
    {
        return $this->schemaDirectory;
    }
}
