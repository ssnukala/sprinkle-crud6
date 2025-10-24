<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

/**
 * Schema Generator Service.
 * 
 * Works with DatabaseScanner to generate complete schema definitions
 * from existing database tables, including field definitions and relationships.
 * 
 * This service provides higher-level functionality for automatically creating
 * JSON schema files from database introspection.
 */
class SchemaGenerator
{
    /**
     * Type mapping from database types to schema types
     * 
     * @var array<string, string>
     */
    protected array $typeMapping = [
        // Integer types
        'int' => 'integer',
        'integer' => 'integer',
        'bigint' => 'integer',
        'smallint' => 'integer',
        'tinyint' => 'integer',
        'mediumint' => 'integer',
        
        // String types
        'varchar' => 'string',
        'char' => 'string',
        'text' => 'text',
        'tinytext' => 'text',
        'mediumtext' => 'text',
        'longtext' => 'text',
        
        // Decimal/Float types
        'decimal' => 'decimal',
        'numeric' => 'decimal',
        'float' => 'float',
        'double' => 'float',
        'real' => 'float',
        
        // Boolean types
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'bit' => 'boolean',
        
        // Date/Time types
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'time' => 'string',
        
        // JSON types
        'json' => 'json',
        'jsonb' => 'json',
        
        // Binary types (stored as string in schema)
        'blob' => 'string',
        'binary' => 'string',
        'varbinary' => 'string',
    ];

    /**
     * Constructor for SchemaGenerator.
     * 
     * @param DatabaseManager  $db      Database manager
     * @param DatabaseScanner  $scanner Database scanner for relationship detection
     */
    public function __construct(
        protected DatabaseManager $db,
        protected DatabaseScanner $scanner
    ) {
    }

    /**
     * Generate complete schema for a table.
     * 
     * @param string      $tableName  Table name
     * @param string|null $connection Database connection
     * @param array       $options    Generation options
     * 
     * @return array<string, mixed> Complete schema definition
     */
    public function generateSchema(string $tableName, ?string $connection = null, array $options = []): array
    {
        $conn = $this->db->connection($connection);
        
        // Get table columns
        $columns = $this->getTableColumns($tableName, $conn);
        
        // Generate field definitions
        $fields = $this->generateFields($columns);
        
        // Detect relationships if enabled (default: true)
        $relationships = [];
        if ($options['detect_relationships'] ?? true) {
            $detectedRelationships = $this->scanner->scanTable($tableName, $connection);
            $relationships = $this->scanner->generateSchemaRelationships($detectedRelationships);
        }
        
        // Detect primary key
        $primaryKey = $this->detectPrimaryKey($columns);
        
        // Detect timestamps
        $timestamps = $this->detectTimestamps($columns);
        
        // Build schema
        $schema = [
            'model' => $tableName,
            'title' => $this->generateTitle($tableName),
            'singular_title' => $this->generateSingularTitle($tableName),
            'description' => $options['description'] ?? "Manage {$tableName}",
            'table' => $tableName,
            'primary_key' => $primaryKey,
            'timestamps' => $timestamps,
            'soft_delete' => isset($columns['deleted_at']),
            'fields' => $fields,
        ];
        
        // Add relationships if any were detected
        if (!empty($relationships)) {
            $schema['relationships'] = $relationships;
        }
        
        // Add permissions template if requested
        if ($options['include_permissions'] ?? true) {
            $schema['permissions'] = $this->generatePermissionsTemplate($tableName);
        }
        
        // Add default sort if requested
        if ($options['include_default_sort'] ?? true) {
            $schema['default_sort'] = $this->generateDefaultSort($fields);
        }
        
        return $schema;
    }

    /**
     * Generate schemas for all tables in a database.
     * 
     * @param string|null $connection   Database connection
     * @param array       $excludeTables Tables to exclude
     * @param array       $options      Generation options
     * 
     * @return array<string, array<string, mixed>> Map of table names to schemas
     */
    public function generateAllSchemas(?string $connection = null, array $excludeTables = [], array $options = []): array
    {
        $conn = $this->db->connection($connection);
        $tables = $this->getDatabaseTables($conn);
        
        // Add common system tables to exclusions
        $defaultExclusions = ['migrations', 'cache', 'sessions', 'jobs', 'failed_jobs'];
        $excludeTables = array_merge($excludeTables, $defaultExclusions);
        
        $schemas = [];
        foreach ($tables as $table) {
            if (!in_array($table, $excludeTables, true)) {
                $schemas[$table] = $this->generateSchema($table, $connection, $options);
            }
        }
        
        return $schemas;
    }

    /**
     * Get all tables in database.
     * 
     * @param Connection $conn Database connection
     * @return array<string>
     */
    protected function getDatabaseTables(Connection $conn): array
    {
        $driver = $conn->getDriverName();
        
        return match ($driver) {
            'mysql' => $this->getMySQLTables($conn),
            'pgsql' => $this->getPostgresTables($conn),
            'sqlite' => $this->getSQLiteTables($conn),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };
    }

    /**
     * Get MySQL tables.
     */
    protected function getMySQLTables(Connection $conn): array
    {
        $database = $conn->getDatabaseName();
        $tables = $conn->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$database]);
        return array_map(fn($table) => $table->TABLE_NAME, $tables);
    }

    /**
     * Get PostgreSQL tables.
     */
    protected function getPostgresTables(Connection $conn): array
    {
        $tables = $conn->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        return array_map(fn($table) => $table->tablename, $tables);
    }

    /**
     * Get SQLite tables.
     */
    protected function getSQLiteTables(Connection $conn): array
    {
        $tables = $conn->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return array_map(fn($table) => $table->name, $tables);
    }

    /**
     * Get table columns using DatabaseScanner methods.
     */
    protected function getTableColumns(string $tableName, Connection $conn): array
    {
        $driver = $conn->getDriverName();
        
        return match ($driver) {
            'mysql' => $this->getMySQLColumns($tableName, $conn),
            'pgsql' => $this->getPostgresColumns($tableName, $conn),
            'sqlite' => $this->getSQLiteColumns($tableName, $conn),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };
    }

    /**
     * Get MySQL column information.
     */
    protected function getMySQLColumns(string $tableName, Connection $conn): array
    {
        $database = $conn->getDatabaseName();
        $columns = $conn->select(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA, CHARACTER_MAXIMUM_LENGTH
             FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$database, $tableName]
        );
        
        $result = [];
        foreach ($columns as $column) {
            $result[$column->COLUMN_NAME] = [
                'name' => $column->COLUMN_NAME,
                'type' => $column->DATA_TYPE,
                'nullable' => $column->IS_NULLABLE === 'YES',
                'key' => $column->COLUMN_KEY,
                'default' => $column->COLUMN_DEFAULT,
                'extra' => $column->EXTRA,
                'max_length' => $column->CHARACTER_MAXIMUM_LENGTH,
            ];
        }
        
        return $result;
    }

    /**
     * Get PostgreSQL column information.
     */
    protected function getPostgresColumns(string $tableName, Connection $conn): array
    {
        $columns = $conn->select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ?",
            [$tableName]
        );
        
        $result = [];
        foreach ($columns as $column) {
            $result[$column->column_name] = [
                'name' => $column->column_name,
                'type' => $column->data_type,
                'nullable' => $column->is_nullable === 'YES',
                'key' => '',
                'default' => $column->column_default,
                'extra' => '',
                'max_length' => $column->character_maximum_length,
            ];
        }
        
        return $result;
    }

    /**
     * Get SQLite column information.
     */
    protected function getSQLiteColumns(string $tableName, Connection $conn): array
    {
        $columns = $conn->select("PRAGMA table_info({$tableName})");
        
        $result = [];
        foreach ($columns as $column) {
            $result[$column->name] = [
                'name' => $column->name,
                'type' => $column->type,
                'nullable' => $column->notnull == 0,
                'key' => $column->pk == 1 ? 'PRI' : '',
                'default' => $column->dflt_value,
                'extra' => '',
                'max_length' => null,
            ];
        }
        
        return $result;
    }

    /**
     * Generate field definitions from columns.
     */
    protected function generateFields(array $columns): array
    {
        $fields = [];
        
        foreach ($columns as $columnName => $columnInfo) {
            $field = $this->generateFieldDefinition($columnName, $columnInfo);
            $fields[$columnName] = $field;
        }
        
        return $fields;
    }

    /**
     * Generate single field definition.
     */
    protected function generateFieldDefinition(string $columnName, array $columnInfo): array
    {
        $type = $this->mapDatabaseType($columnInfo['type']);
        
        $field = [
            'type' => $type,
            'label' => $this->generateLabel($columnName),
            'readonly' => $columnInfo['key'] === 'PRI' || str_contains($columnInfo['extra'] ?? '', 'auto_increment'),
            'required' => !$columnInfo['nullable'] && $columnInfo['default'] === null && $columnInfo['key'] !== 'PRI',
            'sortable' => true,
            'filterable' => in_array($type, ['string', 'integer', 'boolean', 'date', 'datetime']),
            'searchable' => in_array($type, ['string', 'text']),
            'listable' => true,
        ];
        
        // Add auto_increment flag for primary keys
        if ($columnInfo['key'] === 'PRI' && str_contains($columnInfo['extra'] ?? '', 'auto_increment')) {
            $field['auto_increment'] = true;
        }
        
        // Add default value if present
        if ($columnInfo['default'] !== null) {
            $field['default'] = $this->parseDefaultValue($columnInfo['default'], $type);
        }
        
        // Add max length for strings
        if ($type === 'string' && $columnInfo['max_length'] !== null) {
            $field['validation'] = [
                'length' => [
                    'max' => (int) $columnInfo['max_length'],
                ],
            ];
        }
        
        // Special handling for common fields
        if ($columnName === 'email') {
            $field['validation']['email'] = true;
        }
        
        return $field;
    }

    /**
     * Map database type to schema type.
     */
    protected function mapDatabaseType(string $dbType): string
    {
        $dbType = strtolower($dbType);
        
        // Remove size specifications (e.g., varchar(255) -> varchar)
        $baseType = preg_replace('/\(.*?\)/', '', $dbType);
        
        return $this->typeMapping[$baseType] ?? 'string';
    }

    /**
     * Parse default value based on type.
     */
    protected function parseDefaultValue(mixed $default, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $default,
            'float', 'decimal' => (float) $default,
            'boolean' => in_array(strtolower((string) $default), ['1', 'true', 'yes']),
            'json' => json_decode($default, true),
            default => (string) $default,
        };
    }

    /**
     * Detect primary key from columns.
     */
    protected function detectPrimaryKey(array $columns): string
    {
        foreach ($columns as $columnName => $columnInfo) {
            if ($columnInfo['key'] === 'PRI') {
                return $columnName;
            }
        }
        
        return 'id'; // Default fallback
    }

    /**
     * Detect if table has timestamps.
     */
    protected function detectTimestamps(array $columns): bool
    {
        return isset($columns['created_at']) && isset($columns['updated_at']);
    }

    /**
     * Generate human-readable title from table name.
     */
    protected function generateTitle(string $tableName): string
    {
        // Remove underscores and capitalize words
        $words = str_replace('_', ' ', $tableName);
        return ucwords($words) . ' Management';
    }

    /**
     * Generate singular title from table name.
     */
    protected function generateSingularTitle(string $tableName): string
    {
        // Simple singularization (remove trailing 's')
        $singular = preg_replace('/ies$/', 'y', $tableName);
        $singular = preg_replace('/s$/', '', $singular);
        
        return ucwords(str_replace('_', ' ', $singular));
    }

    /**
     * Generate label from column name.
     */
    protected function generateLabel(string $columnName): string
    {
        // Special handling for common field names
        $labels = [
            'id' => 'ID',
            'uuid' => 'UUID',
            'email' => 'Email Address',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
        ];
        
        if (isset($labels[$columnName])) {
            return $labels[$columnName];
        }
        
        // Remove _id suffix for foreign keys
        $label = preg_replace('/_id$/', '', $columnName);
        
        // Convert underscores to spaces and capitalize
        return ucwords(str_replace('_', ' ', $label));
    }

    /**
     * Generate permissions template.
     */
    protected function generatePermissionsTemplate(string $tableName): array
    {
        $singular = preg_replace('/s$/', '', $tableName);
        
        return [
            'read' => "uri_{$tableName}",
            'create' => "create_{$singular}",
            'update' => "update_{$singular}",
            'delete' => "delete_{$singular}",
        ];
    }

    /**
     * Generate default sort based on fields.
     */
    protected function generateDefaultSort(array $fields): array
    {
        // Prefer common sortable fields
        $preferredFields = ['name', 'title', 'created_at', 'id'];
        
        foreach ($preferredFields as $field) {
            if (isset($fields[$field])) {
                return [$field => 'asc'];
            }
        }
        
        // Default to first sortable field
        foreach ($fields as $fieldName => $fieldInfo) {
            if ($fieldInfo['sortable'] ?? false) {
                return [$fieldName => 'asc'];
            }
        }
        
        return ['id' => 'asc'];
    }

    /**
     * Save schema to JSON file.
     * 
     * @param array  $schema   Schema definition
     * @param string $filePath Path to save the file
     * @param bool   $pretty   Whether to pretty-print JSON
     * 
     * @return bool Success status
     */
    public function saveSchemaToFile(array $schema, string $filePath, bool $pretty = true): bool
    {
        $json = $pretty 
            ? json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : json_encode($schema);
        
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        return file_put_contents($filePath, $json) !== false;
    }
}
