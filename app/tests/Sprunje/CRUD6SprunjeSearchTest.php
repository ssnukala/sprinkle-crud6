<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Sprunje;

use UserFrosting\Sprinkle\Account\Database\Models\Group;
use UserFrosting\Sprinkle\CRUD6\Sprunje\CRUD6Sprunje;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Tests CRUD6Sprunje search functionality using schema-driven configuration.
 * 
 * This test is completely driven by the groups.json schema file.
 * NO field names, table names, or configurations are hardcoded.
 */
class CRUD6SprunjeSearchTest extends CRUD6TestCase
{
    use RefreshDatabase;

    /** @var array The groups schema loaded from JSON */
    protected array $schema;

    /** @var array Sortable fields extracted from schema */
    protected array $sortableFields;

    /** @var array Filterable fields extracted from schema */
    protected array $filterableFields;

    /** @var array Listable fields extracted from schema */
    protected array $listableFields;

    /** @var string The table name from schema */
    protected string $tableName;

    public function setUp(): void
    {
        parent::setUp();

        // Set database up.
        $this->refreshDatabase();
        $this->seedDatabase();
        
        // Load schema from SchemaService - this is schema-driven, not hardcoded
        /** @var SchemaService */
        $schemaService = $this->ci->get(SchemaService::class);
        $this->schema = $schemaService->getSchema('groups');
        
        // Extract configuration from schema - completely dynamic
        $this->tableName = $this->schema['table'];
        $this->sortableFields = $this->extractSortableFields($this->schema);
        $this->filterableFields = $this->extractFilterableFields($this->schema);
        $this->listableFields = $this->extractListableFields($this->schema);
        
        // Create test data
        $this->createData();
    }

    /**
     * Create test data for groups.
     * Uses the actual Group model from Account sprinkle.
     */
    protected function createData(): void
    {
        // Create test groups with different names and descriptions
        Group::factory()->create([
            'name' => 'Alpha Group',
            'slug' => 'alpha-group',
            'description' => 'This is the first test group',
        ]);

        Group::factory()->create([
            'name' => 'Beta Group',
            'slug' => 'beta-group',
            'description' => 'This is the second test group',
        ]);

        Group::factory()->create([
            'name' => 'Gamma Group',
            'slug' => 'gamma-group',
            'description' => 'Contains special word Alpha in description',
        ]);
    }

    /**
     * Extract sortable fields from schema.
     * 
     * @param array $schema The schema configuration
     * @return array List of sortable field names
     */
    protected function extractSortableFields(array $schema): array
    {
        $sortable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['sortable']) && $fieldConfig['sortable'] === true) {
                    $sortable[] = $fieldName;
                }
            }
        }

        return $sortable;
    }

    /**
     * Extract filterable fields from schema.
     * 
     * @param array $schema The schema configuration
     * @return array List of filterable field names
     */
    protected function extractFilterableFields(array $schema): array
    {
        $filterable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                if (isset($fieldConfig['filterable']) && $fieldConfig['filterable'] === true) {
                    $filterable[] = $fieldName;
                }
            }
        }

        return $filterable;
    }

    /**
     * Extract listable fields from schema.
     * 
     * @param array $schema The schema configuration
     * @return array List of listable field names
     */
    protected function extractListableFields(array $schema): array
    {
        $listable = [];

        if (isset($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                $isListable = false;
                
                if (isset($fieldConfig['show_in'])) {
                    $isListable = in_array('list', $fieldConfig['show_in']);
                } elseif (isset($fieldConfig['listable'])) {
                    $isListable = $fieldConfig['listable'] === true;
                }
                
                if ($isListable) {
                    $listable[] = $fieldName;
                }
            }
        }

        return $listable;
    }

    /**
     * Test search across multiple filterable fields (schema-driven).
     * 
     * Schema determines which fields are filterable.
     * For groups schema: 'name' is marked as filterable: true
     */
    public function testSearchAcrossMultipleFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje using schema-extracted configuration
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            $this->filterableFields,
            $this->listableFields
        );
        
        // Search for "Alpha" - should match Alpha Group by name (name is filterable in schema)
        // Note: In the schema, only 'name' is filterable, not 'description'
        $sprunje->setOptions(['search' => 'Alpha']);
        $data = $sprunje->getArray();

        // With only 'name' as filterable (per schema), only Alpha Group should match
        $this->assertEquals(1, $data['count_filtered'], 'Should find 1 group matching "Alpha" in name field');
        $this->assertCount(1, $data['rows']); // @phpstan-ignore-line
        
        // Verify the correct group is returned
        $names = array_column($data['rows'], 'name');
        $this->assertContains('Alpha Group', $names);
        $this->assertNotContains('Beta Group', $names);
        $this->assertNotContains('Gamma Group', $names, 'Gamma should not match because description is not filterable');
    }

    /**
     * Test search with partial match (schema-driven).
     */
    public function testSearchPartialMatch(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje using schema-extracted configuration
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            $this->filterableFields,
            $this->listableFields
        );
        
        // Search for "Group" - all groups have this in their name
        $sprunje->setOptions(['search' => 'Group']);
        $data = $sprunje->getArray();

        $this->assertEquals(3, $data['count_filtered'], 'Should find 3 groups with "Group" in name');
        $this->assertCount(3, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test search with no matches (schema-driven).
     */
    public function testSearchNoMatches(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje using schema-extracted configuration
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            $this->filterableFields,
            $this->listableFields
        );
        
        // Search for something that doesn't exist
        $sprunje->setOptions(['search' => 'NonExistentTerm']);
        $data = $sprunje->getArray();

        $this->assertEquals(0, $data['count_filtered'], 'Should find 0 groups');
        $this->assertCount(0, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test search works case-insensitively (schema-driven).
     */
    public function testSearchCaseInsensitive(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje using schema-extracted configuration
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            $this->filterableFields,
            $this->listableFields
        );
        
        // Search for "alpha" in lowercase - should match "Alpha Group"
        $sprunje->setOptions(['search' => 'alpha']);
        $data = $sprunje->getArray();

        $this->assertEquals(1, $data['count_filtered'], 'Should find groups regardless of case');
        $this->assertCount(1, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search respects schema's filterable configuration.
     * 
     * Per groups.json schema:
     * - 'name' is filterable: true
     * - 'slug' and 'description' are filterable: false (or not set)
     */
    public function testSearchOnlyFilterableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje using schema-extracted configuration
        // This test verifies that only fields marked as filterable in the schema are searched
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            $this->filterableFields,
            $this->listableFields
        );
        
        // Search for "beta-group" which is in slug but slug is NOT filterable in schema
        $sprunje->setOptions(['search' => 'beta-group']);
        $data = $sprunje->getArray();

        $this->assertEquals(0, $data['count_filtered'], 'Should not find groups by slug when slug is not filterable in schema');
        $this->assertCount(0, $data['rows']); // @phpstan-ignore-line
        
        // Now search for "Beta" which is in name, and name IS filterable in schema
        $sprunje->setOptions(['search' => 'Beta']);
        $data = $sprunje->getArray();

        $this->assertEquals(1, $data['count_filtered'], 'Should find group by name which is filterable in schema');
        $this->assertCount(1, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search works when schema has no filterable fields.
     * 
     * This test artificially creates a scenario with no filterable fields
     * to verify the sprunje handles it gracefully.
     */
    public function testSearchWithNoFilterableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with empty filterable array (simulating schema with no filterable fields)
        $sprunje->setupSprunje(
            $this->tableName,
            $this->sortableFields,
            [], // No filterable fields
            $this->listableFields
        );
        
        // Search should have no effect when no fields are filterable
        $sprunje->setOptions(['search' => 'Alpha']);
        $data = $sprunje->getArray();

        // Should return all groups since search has no fields to search
        $this->assertEquals(3, $data['count_filtered'], 'Should return all groups when no fields are filterable');
        $this->assertCount(3, $data['rows']); // @phpstan-ignore-line
    }
}
