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
use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

/**
 * Tests CRUD6Sprunje search functionality across multiple fields.
 */
class CRUD6SprunjeSearchTest extends AdminTestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Set database up.
        $this->refreshDatabase();
        $this->createData();
    }

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
     * Test search across multiple searchable fields.
     */
    public function testSearchAcrossMultipleFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with groups table and searchable fields
        $sprunje->setupSprunje(
            'groups',
            ['name', 'slug'],  // sortable
            ['name'],           // filterable
            ['name', 'slug', 'description'],  // listable
            ['name', 'description']  // searchable - search in name and description only
        );
        
        // Search for "Alpha" - should match both Alpha Group (by name) and Gamma Group (by description)
        $sprunje->setOptions(['search' => 'Alpha']);
        $data = $sprunje->getArray();

        $this->assertEquals(2, $data['count_filtered'], 'Should find 2 groups matching "Alpha"');
        $this->assertCount(2, $data['rows']); // @phpstan-ignore-line
        
        // Verify the correct groups are returned
        $names = array_column($data['rows'], 'name');
        $this->assertContains('Alpha Group', $names);
        $this->assertContains('Gamma Group', $names);
        $this->assertNotContains('Beta Group', $names);
    }

    /**
     * Test search with partial match.
     */
    public function testSearchPartialMatch(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with groups table
        $sprunje->setupSprunje(
            'groups',
            ['name'],
            [],
            ['name', 'slug', 'description'],
            ['name', 'description']
        );
        
        // Search for "test" - should match all groups (all have "test" in description)
        $sprunje->setOptions(['search' => 'test']);
        $data = $sprunje->getArray();

        $this->assertEquals(3, $data['count_filtered'], 'Should find 3 groups with "test" in description');
        $this->assertCount(3, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test search with no matches.
     */
    public function testSearchNoMatches(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with groups table
        $sprunje->setupSprunje(
            'groups',
            ['name'],
            [],
            ['name', 'slug', 'description'],
            ['name', 'description']
        );
        
        // Search for something that doesn't exist
        $sprunje->setOptions(['search' => 'NonExistentTerm']);
        $data = $sprunje->getArray();

        $this->assertEquals(0, $data['count_filtered'], 'Should find 0 groups');
        $this->assertCount(0, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test search works case-insensitively.
     */
    public function testSearchCaseInsensitive(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with groups table
        $sprunje->setupSprunje(
            'groups',
            ['name'],
            [],
            ['name', 'slug', 'description'],
            ['name', 'description']
        );
        
        // Search for "alpha" in lowercase - should match "Alpha Group"
        $sprunje->setOptions(['search' => 'alpha']);
        $data = $sprunje->getArray();

        $this->assertEquals(2, $data['count_filtered'], 'Should find groups regardless of case');
        $this->assertCount(2, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search does not search non-searchable fields.
     */
    public function testSearchOnlySearchableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with only 'name' as searchable (not description or slug)
        $sprunje->setupSprunje(
            'groups',
            ['name'],
            [],
            ['name', 'slug', 'description'],
            ['name']  // Only name is searchable
        );
        
        // Search for "beta-group" which is in slug but slug is not searchable
        $sprunje->setOptions(['search' => 'beta-group']);
        $data = $sprunje->getArray();

        $this->assertEquals(0, $data['count_filtered'], 'Should not find groups by slug when slug is not searchable');
        $this->assertCount(0, $data['rows']); // @phpstan-ignore-line
        
        // Now search for "Beta" which is in name
        $sprunje->setOptions(['search' => 'Beta']);
        $data = $sprunje->getArray();

        $this->assertEquals(1, $data['count_filtered'], 'Should find group by name');
        $this->assertCount(1, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search works with empty searchable fields (no search performed).
     */
    public function testSearchWithNoSearchableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with no searchable fields
        $sprunje->setupSprunje(
            'groups',
            ['name'],
            [],
            ['name', 'slug', 'description'],
            []  // No searchable fields
        );
        
        // Search should have no effect
        $sprunje->setOptions(['search' => 'Alpha']);
        $data = $sprunje->getArray();

        // Should return all groups since search has no fields to search
        $this->assertEquals(3, $data['count_filtered'], 'Should return all groups when no fields are searchable');
        $this->assertCount(3, $data['rows']); // @phpstan-ignore-line
    }
}
