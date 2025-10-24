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
use Illuminate\Support\Facades\Schema;

/**
 * Database Scanner Service.
 * 
 * Analyzes database structures to detect foreign key relationships based on
 * naming conventions and data sampling. Useful for databases that implement
 * relationships at the application layer without explicit foreign key constraints.
 * 
 * Features:
 * - Detect potential foreign keys by field naming patterns (e.g., *_id fields)
 * - Sample data to verify relationships exist
 * - Support configurable naming patterns
 * - Multi-database connection support
 * - Generate relationship metadata for schema definitions
 */
class DatabaseScanner
{
    /**
     * Default naming patterns for foreign key detection
     * 
     * @var array<string>
     */
    protected array $foreignKeyPatterns = [
        '/_id$/',           // Matches: user_id, group_id, category_id
        '/_uuid$/',         // Matches: user_uuid, group_uuid
        '/^fk_/',          // Matches: fk_user, fk_category
    ];

    /**
     * Number of rows to sample for relationship validation
     * 
     * @var int
     */
    protected int $sampleSize = 100;

    /**
     * Minimum percentage of matching records to consider a valid relationship
     * 
     * @var float
     */
    protected float $validationThreshold = 0.8;

    /**
     * Constructor for DatabaseScanner.
     * 
     * @param DatabaseManager $db Database manager for accessing connections
     */
    public function __construct(
        protected DatabaseManager $db
    ) {
    }

    /**
     * Set custom foreign key naming patterns.
     * 
     * @param array<string> $patterns Array of regex patterns
     * @return static
     */
    public function setForeignKeyPatterns(array $patterns): static
    {
        $this->foreignKeyPatterns = $patterns;
        return $this;
    }

    /**
     * Set sample size for data validation.
     * 
     * @param int $size Number of rows to sample
     * @return static
     */
    public function setSampleSize(int $size): static
    {
        $this->sampleSize = max(1, $size);
        return $this;
    }

    /**
     * Set validation threshold for relationship detection.
     * 
     * @param float $threshold Percentage (0.0 to 1.0) of matching records required
     * @return static
     */
    public function setValidationThreshold(float $threshold): static
    {
        $this->validationThreshold = max(0.0, min(1.0, $threshold));
        return $this;
    }

    /**
     * Scan a table and detect potential relationships.
     * 
     * @param string      $tableName  Name of the table to scan
     * @param string|null $connection Optional database connection name
     * 
     * @return array<string, mixed> Array of detected relationships
     */
    public function scanTable(string $tableName, ?string $connection = null): array
    {
        $conn = $this->getConnection($connection);
        
        // Get all columns for the table
        $columns = $this->getTableColumns($tableName, $conn);
        
        // Detect potential foreign keys
        $potentialForeignKeys = $this->detectForeignKeyFields($columns);
        
        // Validate relationships through data sampling
        $relationships = [];
        foreach ($potentialForeignKeys as $field => $targetInfo) {
            $validationResult = $this->validateRelationship(
                $tableName,
                $field,
                $targetInfo['table'],
                $targetInfo['key'],
                $conn
            );
            
            if ($validationResult['is_valid']) {
                $relationships[$field] = array_merge($targetInfo, $validationResult);
            }
        }
        
        return $relationships;
    }

    /**
     * Scan all tables in a database and detect relationships.
     * 
     * @param string|null $connection Optional database connection name
     * @param array<string>|null $excludeTables Tables to exclude from scanning
     * 
     * @return array<string, array<string, mixed>> Map of table names to their relationships
     */
    public function scanDatabase(?string $connection = null, ?array $excludeTables = null): array
    {
        $conn = $this->getConnection($connection);
        $excludeTables = $excludeTables ?? [];
        
        // Get all tables
        $tables = $this->getDatabaseTables($conn);
        
        // Scan each table
        $databaseRelationships = [];
        foreach ($tables as $table) {
            if (!in_array($table, $excludeTables, true)) {
                $relationships = $this->scanTable($table, $connection);
                if (!empty($relationships)) {
                    $databaseRelationships[$table] = $relationships;
                }
            }
        }
        
        return $databaseRelationships;
    }

    /**
     * Get database connection.
     * 
     * @param string|null $connection Connection name or null for default
     * @return Connection
     */
    protected function getConnection(?string $connection = null): Connection
    {
        return $this->db->connection($connection);
    }

    /**
     * Get all tables in the database.
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
     * 
     * @param Connection $conn Database connection
     * @return array<string>
     */
    protected function getMySQLTables(Connection $conn): array
    {
        $database = $conn->getDatabaseName();
        $tables = $conn->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?", [$database]);
        
        return array_map(fn($table) => $table->TABLE_NAME, $tables);
    }

    /**
     * Get PostgreSQL tables.
     * 
     * @param Connection $conn Database connection
     * @return array<string>
     */
    protected function getPostgresTables(Connection $conn): array
    {
        $tables = $conn->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        
        return array_map(fn($table) => $table->tablename, $tables);
    }

    /**
     * Get SQLite tables.
     * 
     * @param Connection $conn Database connection
     * @return array<string>
     */
    protected function getSQLiteTables(Connection $conn): array
    {
        $tables = $conn->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        return array_map(fn($table) => $table->name, $tables);
    }

    /**
     * Get column information for a table.
     * 
     * @param string     $tableName Table name
     * @param Connection $conn      Database connection
     * 
     * @return array<string, array<string, mixed>> Column information indexed by column name
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
     * 
     * @param string     $tableName Table name
     * @param Connection $conn      Database connection
     * 
     * @return array<string, array<string, mixed>>
     */
    protected function getMySQLColumns(string $tableName, Connection $conn): array
    {
        $database = $conn->getDatabaseName();
        $columns = $conn->select(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY 
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
            ];
        }
        
        return $result;
    }

    /**
     * Get PostgreSQL column information.
     * 
     * @param string     $tableName Table name
     * @param Connection $conn      Database connection
     * 
     * @return array<string, array<string, mixed>>
     */
    protected function getPostgresColumns(string $tableName, Connection $conn): array
    {
        $columns = $conn->select(
            "SELECT column_name, data_type, is_nullable
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
            ];
        }
        
        return $result;
    }

    /**
     * Get SQLite column information.
     * 
     * @param string     $tableName Table name
     * @param Connection $conn      Database connection
     * 
     * @return array<string, array<string, mixed>>
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
            ];
        }
        
        return $result;
    }

    /**
     * Detect foreign key fields based on naming patterns.
     * 
     * @param array<string, array<string, mixed>> $columns Column information
     * 
     * @return array<string, array<string, string>> Detected foreign keys with target info
     */
    protected function detectForeignKeyFields(array $columns): array
    {
        $foreignKeys = [];
        
        foreach ($columns as $columnName => $columnInfo) {
            // Skip primary keys
            if ($columnInfo['key'] === 'PRI') {
                continue;
            }
            
            // Check if column name matches any foreign key pattern
            foreach ($this->foreignKeyPatterns as $pattern) {
                if (preg_match($pattern, $columnName)) {
                    $targetInfo = $this->inferTargetTable($columnName);
                    if ($targetInfo !== null) {
                        $foreignKeys[$columnName] = $targetInfo;
                    }
                    break;
                }
            }
        }
        
        return $foreignKeys;
    }

    /**
     * Infer target table and key from field name.
     * 
     * @param string $fieldName Field name (e.g., 'user_id', 'category_uuid')
     * 
     * @return array<string, string>|null Target table and key, or null if unable to infer
     */
    protected function inferTargetTable(string $fieldName): ?array
    {
        // Remove common suffixes to get base name
        $baseName = preg_replace('/_id$|_uuid$|^fk_/', '', $fieldName);
        
        if (empty($baseName)) {
            return null;
        }
        
        // Convert to plural for table name (simple pluralization)
        $tableName = $this->pluralize($baseName);
        
        // Determine key type
        $keyType = 'id'; // default
        if (str_ends_with($fieldName, '_uuid')) {
            $keyType = 'uuid';
        }
        
        return [
            'table' => $tableName,
            'key' => $keyType,
            'field' => $fieldName,
        ];
    }

    /**
     * Simple pluralization of table names.
     * 
     * @param string $singular Singular form
     * @return string Plural form
     */
    protected function pluralize(string $singular): string
    {
        // Handle common irregular plurals
        $irregulars = [
            'person' => 'people',
            'child' => 'children',
            'foot' => 'feet',
            'tooth' => 'teeth',
            'goose' => 'geese',
            'man' => 'men',
            'woman' => 'women',
        ];
        
        if (isset($irregulars[$singular])) {
            return $irregulars[$singular];
        }
        
        // Simple pluralization rules
        if (str_ends_with($singular, 'y')) {
            return substr($singular, 0, -1) . 'ies';
        }
        
        if (str_ends_with($singular, 's') || 
            str_ends_with($singular, 'sh') || 
            str_ends_with($singular, 'ch') || 
            str_ends_with($singular, 'x')) {
            return $singular . 'es';
        }
        
        return $singular . 's';
    }

    /**
     * Validate a potential relationship by sampling data.
     * 
     * @param string     $sourceTable Source table name
     * @param string     $sourceField Foreign key field name
     * @param string     $targetTable Target table name
     * @param string     $targetKey   Target primary key field
     * @param Connection $conn        Database connection
     * 
     * @return array<string, mixed> Validation result with statistics
     */
    protected function validateRelationship(
        string $sourceTable,
        string $sourceField,
        string $targetTable,
        string $targetKey,
        Connection $conn
    ): array {
        try {
            // Check if target table exists
            if (!$this->tableExists($targetTable, $conn)) {
                return [
                    'is_valid' => false,
                    'reason' => 'Target table does not exist',
                ];
            }
            
            // Check if target key exists
            $targetColumns = $this->getTableColumns($targetTable, $conn);
            if (!isset($targetColumns[$targetKey])) {
                return [
                    'is_valid' => false,
                    'reason' => 'Target key does not exist',
                ];
            }
            
            // Sample data from source table
            $samples = $conn->table($sourceTable)
                ->whereNotNull($sourceField)
                ->limit($this->sampleSize)
                ->pluck($sourceField)
                ->toArray();
            
            if (empty($samples)) {
                return [
                    'is_valid' => false,
                    'reason' => 'No non-null values in source field',
                ];
            }
            
            // Check how many values exist in target table
            $matchingCount = $conn->table($targetTable)
                ->whereIn($targetKey, $samples)
                ->distinct()
                ->count($targetKey);
            
            $matchRate = $matchingCount / count($samples);
            
            return [
                'is_valid' => $matchRate >= $this->validationThreshold,
                'match_rate' => $matchRate,
                'sampled_count' => count($samples),
                'matching_count' => $matchingCount,
                'target_table' => $targetTable,
                'target_key' => $targetKey,
            ];
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'reason' => 'Error during validation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a table exists in the database.
     * 
     * @param string     $tableName Table name
     * @param Connection $conn      Database connection
     * 
     * @return bool
     */
    protected function tableExists(string $tableName, Connection $conn): bool
    {
        $tables = $this->getDatabaseTables($conn);
        return in_array($tableName, $tables, true);
    }

    /**
     * Generate schema relationships section from scan results.
     * 
     * @param array<string, mixed> $relationships Detected relationships
     * 
     * @return array<string, array<string, mixed>> Schema-compatible relationships definition
     */
    public function generateSchemaRelationships(array $relationships): array
    {
        $schemaRelationships = [];
        
        foreach ($relationships as $field => $info) {
            $schemaRelationships[$field] = [
                'type' => 'belongsTo',
                'related' => $info['target_table'],
                'foreign_key' => $field,
                'owner_key' => $info['target_key'],
                'confidence' => $info['match_rate'] ?? 0.0,
            ];
        }
        
        return $schemaRelationships;
    }
}
