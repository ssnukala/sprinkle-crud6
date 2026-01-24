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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Database Scanner Service
 *
 * Scans a database and catalogs all tables, columns, and relationships.
 *
 * @author Srinivas Nukala
 */
class DatabaseScanner
{
    /**
     * @var Connection
     */
    protected Connection $connection;

    /**
     * @var AbstractSchemaManager
     */
    protected AbstractSchemaManager $schemaManager;

    /**
     * @var Capsule
     */
    protected Capsule $capsule;

    /**
     * @var string|null Current connection name
     */
    protected ?string $connectionName = null;

    /**
     * @var array Naming patterns for implicit foreign key detection
     */
    protected array $namingPatterns = [
        '/^(.+)_id$/i',      // user_id, category_id
        '/^(.+)Id$/i',       // userId, categoryId
    ];

    /**
     * @var array Table name prefixes to remove when matching
     */
    protected array $tablePrefixes = [
        'tbl_',
        'test_',
    ];

    /**
     * @var float Minimum confidence threshold for implicit relationships
     */
    protected float $confidenceThreshold = 0.8;

    /**
     * Constructor.
     *
     * @param Capsule $capsule Database capsule manager (provided by UserFrosting Core)
     */
    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;

        // Get default connection
        $defaultConnectionName = $this->capsule->getDatabaseManager()->getDefaultConnection();
        $illuminateConnection = $this->capsule->getConnection($defaultConnectionName);
        $this->connection = $illuminateConnection->getDoctrineConnection();

        // Register DB type mappings to avoid "Unknown database type enum requested" errors
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');
            $platform->registerDoctrineTypeMapping('set', 'string');
        } catch (\Throwable $e) {
            // Best-effort mapping; if it fails, we'll continue and let schema introspection fail later with the original error.
        }

        $this->schemaManager = $this->connection->createSchemaManager();
    }

    /**
     * Set the database connection to use.
     *
     * @param string|null $connectionName Connection name, or null for default
     * @return self
     */
    public function setConnection(?string $connectionName = null): self
    {
        $this->connectionName = $connectionName;

        if ($connectionName !== null) {
            // Get the named connection from the capsule
            $illuminateConnection = $this->capsule->getConnection($connectionName);
            $this->connection = $illuminateConnection->getDoctrineConnection();
        } else {
            // Use default connection
            $defaultConnectionName = $this->capsule->getDatabaseManager()->getDefaultConnection();
            $illuminateConnection = $this->capsule->getConnection($defaultConnectionName);
            $this->connection = $illuminateConnection->getDoctrineConnection();
        }

        // Register DB type mappings for the new connection before creating schema manager
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');
            $platform->registerDoctrineTypeMapping('set', 'string');
        } catch (\Throwable $e) {
            // ignore if mapping not available
        }

        $this->schemaManager = $this->connection->createSchemaManager();

        return $this;
    }

    /**
     * Get the current connection name.
     *
     * @return string|null
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    /**
     * Set naming patterns for implicit foreign key detection.
     *
     * @param array $patterns Array of regex patterns where first capture group is table name
     * @return self
     */
    public function setNamingPatterns(array $patterns): self
    {
        $this->namingPatterns = $patterns;
        return $this;
    }

    /**
     * Set table name prefixes to remove when matching.
     *
     * @param array $prefixes Array of prefixes
     * @return self
     */
    public function setTablePrefixes(array $prefixes): self
    {
        $this->tablePrefixes = $prefixes;
        return $this;
    }

    /**
     * Set confidence threshold for implicit relationships.
     *
     * @param float $threshold Threshold between 0.0 and 1.0
     * @return self
     */
    public function setConfidenceThreshold(float $threshold): self
    {
        $this->confidenceThreshold = max(0.0, min(1.0, $threshold));
        return $this;
    }

    /**
     * Get all table names from the database.
     *
     * @return string[]
     */
    public function getTables(): array
    {
        return $this->schemaManager->listTableNames();
    }

    /**
     * Get detailed information about a specific table.
     *
     * @param string $tableName Table name
     * @return array Table metadata including columns, indexes, and foreign keys
     */
    public function getTableInfo(string $tableName): array
    {
        $table = $this->schemaManager->introspectTable($tableName);

        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = [
                'name' => $column->getName(),
                'type' => $column->getType()->getName(),
                'length' => $column->getLength(),
                'nullable' => !$column->getNotnull(),
                'default' => $column->getDefault(),
                'autoincrement' => $column->getAutoincrement(),
                'unsigned' => $column->getUnsigned(),
                'comment' => $column->getComment(),
            ];
        }

        $indexes = [];
        foreach ($table->getIndexes() as $index) {
            $indexes[$index->getName()] = [
                'name' => $index->getName(),
                'columns' => $index->getColumns(),
                'unique' => $index->isUnique(),
                'primary' => $index->isPrimary(),
            ];
        }

        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $foreignKey) {
            $foreignKeys[$foreignKey->getName()] = [
                'name' => $foreignKey->getName(),
                'localColumns' => $foreignKey->getLocalColumns(),
                'foreignTable' => $foreignKey->getForeignTableName(),
                'foreignColumns' => $foreignKey->getForeignColumns(),
                'onUpdate' => $foreignKey->onUpdate(),
                'onDelete' => $foreignKey->onDelete(),
            ];
        }

        return [
            'name' => $tableName,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreignKeys' => $foreignKeys,
            'primaryKey' => $table->getPrimaryKey()?->getColumns() ?? [],
        ];
    }

    /**
     * Scan all tables and return their metadata.
     *
     * @param array $tableFilter Optional list of table names to include (empty = all tables)
     * @return array Array of table metadata
     */
    public function scanDatabase(array $tableFilter = []): array
    {
        $tables = $this->getTables();

        if (!empty($tableFilter)) {
            $tables = array_intersect($tables, $tableFilter);
        }

        $result = [];
        foreach ($tables as $tableName) {
            $result[$tableName] = $this->getTableInfo($tableName);
        }

        return $result;
    }

    /**
     * Detect relationships between tables based on foreign keys.
     *
     * This method detects related tables by looking at foreign key constraints.
     * It only tracks 'references' (tables that this table references via its own foreign keys),
     * which matches how foreign keys are defined in standard DDL.
     *
     * To find tables that reference the current table, iterate through all tables'
     * references arrays and check if they reference the current table.
     *
     * @param array $tablesMetadata Table metadata from scanDatabase()
     * @param bool $includeImplicit Whether to include implicit relationships detected by naming conventions (default: false)
     * @param int $sampleSize Number of rows to sample for validation (default: 100, 0 to disable sampling)
     * @return array Relationships organized by table
     */
    public function detectRelationships(array $tablesMetadata, bool $includeImplicit = false, int $sampleSize = 100): array
    {
        $relationships = [];

        // Initialize relationships for all tables
        foreach ($tablesMetadata as $tableName => $metadata) {
            $relationships[$tableName] = [
                'references' => [],   // Tables this table references via FK
            ];
        }

        // Process foreign keys to detect relationships
        foreach ($tablesMetadata as $tableName => $metadata) {
            foreach ($metadata['foreignKeys'] as $foreignKey) {
                $foreignTable = $foreignKey['foreignTable'];
                $localColumn = $foreignKey['localColumns'][0] ?? null;
                $foreignColumn = $foreignKey['foreignColumns'][0] ?? null;

                // This table references the foreign table
                $relationships[$tableName]['references'][] = [
                    'table' => $foreignTable,
                    'localKey' => $localColumn,
                    'foreignKey' => $foreignColumn,
                    'type' => 'explicit', // Explicitly defined foreign key
                ];
            }
        }

        // If includeImplicit is enabled, detect implicit relationships
        if ($includeImplicit) {
            $implicitRelationships = $this->detectImplicitRelationships($tablesMetadata, $sampleSize);

            // Merge implicit relationships with explicit ones
            foreach ($implicitRelationships as $tableName => $tableRelationships) {
                if (isset($relationships[$tableName])) {
                    $relationships[$tableName]['references'] = array_merge(
                        $relationships[$tableName]['references'],
                        $tableRelationships['references']
                    );
                }
            }
        }

        return $relationships;
    }

    /**
     * Detect implicit foreign key relationships based on naming conventions and data sampling.
     *
     * This method analyzes column names to identify potential foreign key relationships
     * in databases that don't implement foreign key constraints but follow naming conventions.
     *
     * Common patterns detected:
     * - {table_name}_id (e.g., user_id references users.id)
     * - {table_name}Id (e.g., userId references users.id)
     * - {singular_table_name}_id (e.g., category_id references categories.id)
     *
     * @param array $tablesMetadata Table metadata from scanDatabase()
     * @param int $sampleSize Number of rows to sample for validation (0 to skip validation)
     * @return array Implicit relationships organized by table
     */
    protected function detectImplicitRelationships(array $tablesMetadata, int $sampleSize = 100): array
    {
        $relationships = [];

        // Initialize relationships for all tables
        foreach ($tablesMetadata as $tableName => $metadata) {
            $relationships[$tableName] = [
                'references' => [],
            ];
        }

        // Build a lookup of potential target tables and their primary keys
        $tableLookup = $this->buildTableLookup($tablesMetadata);

        // Scan each table's columns for potential foreign key patterns
        foreach ($tablesMetadata as $tableName => $metadata) {
            foreach ($metadata['columns'] as $columnName => $column) {
                // Skip if column is the primary key
                if (in_array($columnName, $metadata['primaryKey'] ?? [])) {
                    continue;
                }

                // Skip if already has an explicit foreign key
                if ($this->hasExplicitForeignKey($metadata, $columnName)) {
                    continue;
                }

                // Try to identify potential foreign key relationship
                $potentialRelationship = $this->identifyPotentialForeignKey(
                    $columnName,
                    $column,
                    $tableLookup
                );

                if ($potentialRelationship !== null) {
                    // Validate relationship with data sampling if enabled
                    $isValid = true;
                    $confidence = 1.0;

                    if ($sampleSize > 0) {
                        $validationResult = $this->validateRelationshipWithSampling(
                            $tableName,
                            $columnName,
                            $potentialRelationship['table'],
                            $potentialRelationship['foreignKey'],
                            $sampleSize
                        );
                        $isValid = $validationResult['valid'];
                        $confidence = $validationResult['confidence'];
                    }

                    if ($isValid) {
                        $relationships[$tableName]['references'][] = [
                            'table' => $potentialRelationship['table'],
                            'localKey' => $columnName,
                            'foreignKey' => $potentialRelationship['foreignKey'],
                            'type' => 'implicit',
                            'confidence' => $confidence,
                        ];
                    }
                }
            }
        }

        return $relationships;
    }

    /**
     * Build a lookup table of all tables with their primary keys and naming variations.
     *
     * @param array $tablesMetadata Table metadata from scanDatabase()
     * @return array Lookup table
     */
    protected function buildTableLookup(array $tablesMetadata): array
    {
        $lookup = [];
        
        // Auto-detect prefixes from the actual table names
        $detectedPrefixes = $this->detectTablePrefixes(array_keys($tablesMetadata));

        foreach ($tablesMetadata as $tableName => $metadata) {
            $primaryKey = $metadata['primaryKey'][0] ?? 'id';

            // Store variations of the table name
            $lookup[$tableName] = [
                'table' => $tableName,
                'primaryKey' => $primaryKey,
                'variations' => $this->generateTableNameVariations($tableName, $detectedPrefixes),
            ];
        }

        return $lookup;
    }

    /**
     * Detect common table prefixes from the actual table names.
     *
     * This method analyzes all table names to find common prefixes that appear
     * in multiple tables. It looks for patterns like:
     * - _adm_*, _cmreg_*, cr_*, rs_*, or_*
     * - tbl_*, test_*
     *
     * @param array $tableNames Array of table names
     * @return array Array of detected prefixes sorted by length (longest first)
     */
    protected function detectTablePrefixes(array $tableNames): array
    {
        $prefixCounts = [];
        
        foreach ($tableNames as $tableName) {
            // Look for prefixes up to the first 3 underscore-delimited segments
            // e.g., "_cmreg_adm_" would be split into ["_cmreg_", "_cmreg_adm_"]
            $parts = explode('_', $tableName);
            $currentPrefix = '';
            
            // Try up to 3 segments as prefix
            for ($i = 0; $i < min(3, count($parts) - 1); $i++) {
                $currentPrefix .= $parts[$i] . '_';
                
                // Only consider prefixes that are at least 2 characters (excluding underscore)
                if (strlen($currentPrefix) >= 3) {
                    if (!isset($prefixCounts[$currentPrefix])) {
                        $prefixCounts[$currentPrefix] = 0;
                    }
                    $prefixCounts[$currentPrefix]++;
                }
            }
        }
        
        // Filter to prefixes that appear in at least 2 tables
        $detectedPrefixes = array_keys(array_filter($prefixCounts, function ($count) {
            return $count >= 2;
        }));
        
        // Merge with configured prefixes
        $allPrefixes = array_unique(array_merge($detectedPrefixes, $this->tablePrefixes));
        
        // Sort by length (longest first) to match most specific prefixes first
        usort($allPrefixes, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        return $allPrefixes;
    }

    /**
     * Generate common naming variations for a table name.
     *
     * @param string $tableName Table name
     * @param array $prefixes Array of detected prefixes (optional)
     * @return array Array of name variations
     */
    protected function generateTableNameVariations(string $tableName, array $prefixes = []): array
    {
        $variations = [];
        
        // Use provided prefixes or fall back to configured prefixes
        if (empty($prefixes)) {
            $prefixes = $this->tablePrefixes;
        }

        // Remove configured prefixes
        $cleanName = $tableName;
        foreach ($prefixes as $prefix) {
            if (strpos($tableName, $prefix) === 0) {
                $cleanName = substr($tableName, strlen($prefix));
                break;
            }
        }

        // Add the original name
        $variations[] = $tableName;
        
        // Add clean name if different from original
        if ($cleanName !== $tableName) {
            $variations[] = $cleanName;
        }

        // Add singular forms with improved pluralization rules
        $singularForms = $this->getSingularForms($cleanName);
        foreach ($singularForms as $singular) {
            $variations[] = $singular;
            
            // Also add the singular with the prefix stripped
            foreach ($prefixes as $prefix) {
                if (strpos($tableName, $prefix) === 0) {
                    $variations[] = $prefix . $singular;
                }
            }
        }

        // Add camelCase variations
        $baseVariations = array_unique($variations);
        foreach ($baseVariations as $variation) {
            // snake_case to camelCase
            $camelCase = lcfirst(str_replace('_', '', ucwords($variation, '_')));
            $variations[] = $camelCase;
        }

        return array_unique($variations);
    }
    
    /**
     * Get singular forms of a table name with improved pluralization rules.
     *
     * Handles common English pluralization patterns:
     * - classes → class
     * - categories → category
     * - activities → activity
     * - boxes → box
     * - data → data (no change)
     *
     * @param string $name Table name
     * @return array Array of possible singular forms
     */
    protected function getSingularForms(string $name): array
    {
        $singulars = [];
        
        // Special cases - words that don't follow standard rules
        $specialCases = [
            'data' => 'data',
            'info' => 'info',
            'series' => 'series',
            'species' => 'species',
            'status' => 'status',
            'syllabus' => 'syllabus',  // Latin word, plural is syllabi
            'campus' => 'campus',      // Latin word, plural is campi
            'genus' => 'genus',        // Latin word, plural is genera
        ];
        
        if (isset($specialCases[$name])) {
            $singulars[] = $specialCases[$name];
            return $singulars;
        }
        
        // -ies → -y (e.g., categories → category, activities → activity)
        if (substr($name, -3) === 'ies' && !in_array($name, ['series', 'species'])) {
            $singulars[] = substr($name, 0, -3) . 'y';
        }
        // -sses, -shes, -ches, -xes → remove -es (e.g., classes → class, boxes → box)
        elseif (preg_match('/(ss|sh|ch|x)es$/', $name)) {
            $singulars[] = substr($name, 0, -2);
        }
        // -ves → -f or -fe (e.g., knives → knife, wolves → wolf)
        elseif (substr($name, -3) === 'ves') {
            $singulars[] = substr($name, 0, -3) . 'f';
            $singulars[] = substr($name, 0, -3) . 'fe';
        }
        // -oes → -o (e.g., heroes → hero, potatoes → potato)
        elseif (substr($name, -3) === 'oes') {
            $singulars[] = substr($name, 0, -2);
        }
        // -ses → -s (e.g., buses → bus, but also try removing just 'es')
        elseif (substr($name, -3) === 'ses' && !in_array($name, ['analyses', 'bases', 'cases'])) {
            $singulars[] = substr($name, 0, -2); // buses → bus
            $singulars[] = substr($name, 0, -1); // ses → se (rare)
        }
        // -us ending (Latin words) - typically don't change in English usage
        elseif (substr($name, -2) === 'us') {
            $singulars[] = $name; // status, campus, syllabus stay as-is
        }
        // -s → remove s (e.g., users → user, products → product)
        elseif (substr($name, -1) === 's') {
            $singulars[] = substr($name, 0, -1);
        }
        
        // If no plural pattern matched, return the original name
        if (empty($singulars)) {
            $singulars[] = $name;
        }
        
        return $singulars;
    }

    /**
     * Check if a column already has an explicit foreign key constraint.
     *
     * @param array $tableMetadata Table metadata
     * @param string $columnName Column name
     * @return bool True if column has explicit foreign key
     */
    protected function hasExplicitForeignKey(array $tableMetadata, string $columnName): bool
    {
        foreach ($tableMetadata['foreignKeys'] as $foreignKey) {
            if (in_array($columnName, $foreignKey['localColumns'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identify potential foreign key relationship based on column name pattern.
     *
     * @param string $columnName Column name
     * @param array $column Column metadata
     * @param array $tableLookup Table lookup data
     * @return array|null Potential relationship or null
     */
    protected function identifyPotentialForeignKey(string $columnName, array $column, array $tableLookup): ?array
    {
        // Only consider integer-type columns as potential foreign keys
        if (!in_array($column['type'], ['integer', 'smallint', 'bigint'])) {
            return null;
        }

        // Use configured naming patterns
        foreach ($this->namingPatterns as $pattern) {
            if (preg_match($pattern, $columnName, $matches)) {
                $potentialTableName = $matches[1];

                // Search for matching table
                foreach ($tableLookup as $tableData) {
                    if (in_array($potentialTableName, $tableData['variations'])) {
                        return [
                            'table' => $tableData['table'],
                            'foreignKey' => $tableData['primaryKey'],
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Validate a potential foreign key relationship by sampling data.
     *
     * This method samples data from both tables to verify that:
     * 1. Values in the potential foreign key column exist in the referenced table
     * 2. The relationship has high integrity (few orphaned records)
     *
     * @param string $tableName Source table name
     * @param string $columnName Foreign key column name
     * @param string $foreignTable Referenced table name
     * @param string $foreignKey Referenced column name
     * @param int $sampleSize Number of rows to sample
     * @return array Validation result with 'valid' and 'confidence' keys
     */
    protected function validateRelationshipWithSampling(
        string $tableName,
        string $columnName,
        string $foreignTable,
        string $foreignKey,
        int $sampleSize
    ): array {
        try {
            // Get sample of non-null values from the potential foreign key column
            $stmt = $this->connection->prepare(
                "SELECT DISTINCT {$columnName} FROM {$tableName} WHERE {$columnName} IS NOT NULL LIMIT {$sampleSize}"
            );
            $result = $stmt->executeQuery();
            $sampleValues = $result->fetchFirstColumn();

            if (empty($sampleValues)) {
                // No data to validate, assume valid with low confidence
                return ['valid' => true, 'confidence' => 0.5];
            }

            // Check how many of these values exist in the referenced table
            $placeholders = implode(',', array_fill(0, count($sampleValues), '?'));
            $stmt = $this->connection->prepare(
                "SELECT COUNT(DISTINCT {$foreignKey}) as count FROM {$foreignTable} WHERE {$foreignKey} IN ({$placeholders})"
            );
            $result = $stmt->executeQuery($sampleValues);
            $matchCount = $result->fetchOne();

            // Calculate confidence based on match rate
            $confidence = count($sampleValues) > 0 ? $matchCount / count($sampleValues) : 0;

            // Use configured confidence threshold
            $isValid = $confidence >= $this->confidenceThreshold;

            return [
                'valid' => $isValid,
                'confidence' => $confidence,
            ];
        } catch (\Exception $e) {
            // If sampling fails, assume invalid
            return ['valid' => false, 'confidence' => 0.0];
        }
    }
}