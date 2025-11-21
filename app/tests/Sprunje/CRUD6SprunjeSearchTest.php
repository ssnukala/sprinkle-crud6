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
     * Test search across multiple filterable fields.
     */
    public function testSearchAcrossMultipleFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with groups table and filterable fields
        $sprunje->setupSprunje(
            'groups',
            ['name', 'slug'],  // sortable
            ['name', 'description'],  // filterable - search in name and description only
            ['name', 'slug', 'description']  // listable
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
            ['name'],  // sortable
            ['name', 'description'],  // filterable
            ['name', 'slug', 'description']  // listable
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
            ['name'],  // sortable
            ['name', 'description'],  // filterable
            ['name', 'slug', 'description']  // listable
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
            ['name'],  // sortable
            ['name', 'description'],  // filterable
            ['name', 'slug', 'description']  // listable
        );
        
        // Search for "alpha" in lowercase - should match "Alpha Group"
        $sprunje->setOptions(['search' => 'alpha']);
        $data = $sprunje->getArray();

        $this->assertEquals(2, $data['count_filtered'], 'Should find groups regardless of case');
        $this->assertCount(2, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search does not search non-filterable fields.
     */
    public function testSearchOnlyFilterableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with only 'name' as filterable (not description or slug)
        $sprunje->setupSprunje(
            'groups',
            ['name'],  // sortable
            ['name'],  // filterable - Only name is filterable
            ['name', 'slug', 'description']  // listable
        );
        
        // Search for "beta-group" which is in slug but slug is not filterable
        $sprunje->setOptions(['search' => 'beta-group']);
        $data = $sprunje->getArray();

        $this->assertEquals(0, $data['count_filtered'], 'Should not find groups by slug when slug is not filterable');
        $this->assertCount(0, $data['rows']); // @phpstan-ignore-line
        
        // Now search for "Beta" which is in name
        $sprunje->setOptions(['search' => 'Beta']);
        $data = $sprunje->getArray();

        $this->assertEquals(1, $data['count_filtered'], 'Should find group by name');
        $this->assertCount(1, $data['rows']); // @phpstan-ignore-line
    }

    /**
     * Test that search works with empty filterable fields (no search performed).
     */
    public function testSearchWithNoFilterableFields(): void
    {
        /** @var CRUD6Sprunje */
        $sprunje = $this->ci->get(CRUD6Sprunje::class);
        
        // Setup sprunje with no filterable fields
        $sprunje->setupSprunje(
            'groups',
            ['name'],  // sortable
            [],  // filterable - No filterable fields
            ['name', 'slug', 'description']  // listable
        );
        
        // Search should have no effect
        $sprunje->setOptions(['search' => 'Alpha']);
        $data = $sprunje->getArray();

        // Should return all groups since search has no fields to search
        $this->assertEquals(3, $data['count_filtered'], 'Should return all groups when no fields are filterable');
        $this->assertCount(3, $data['rows']); // @phpstan-ignore-line
    }
}
