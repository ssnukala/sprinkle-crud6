<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Seeds;

use Illuminate\Database\Connection;
use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;

/**
 * Seeder for product catalogs
 */
class CatalogSeeder implements SeedInterface
{
    /**
     * Constructor
     */
    public function __construct(
        protected Connection $db,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogs = [
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Main Catalog', 'description' => 'Primary product catalog for all items', 'slug' => 'main-catalog', 'type' => 'MA', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Holiday Sale 2025', 'description' => 'Special catalog for holiday promotions', 'slug' => 'holiday-sale-2025', 'type' => 'PR', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Back to School', 'description' => 'Educational products and supplies', 'slug' => 'back-to-school', 'type' => 'PR', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Summer Collection', 'description' => 'Summer seasonal products', 'slug' => 'summer-collection', 'type' => 'SE', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Black Friday Deals', 'description' => 'Black Friday special offers', 'slug' => 'black-friday-deals', 'type' => 'PR', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Clearance Items', 'description' => 'Discounted clearance products', 'slug' => 'clearance-items', 'type' => 'CL', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'New Arrivals', 'description' => 'Latest product additions', 'slug' => 'new-arrivals', 'type' => 'NW', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Best Sellers', 'description' => 'Top selling products', 'slug' => 'best-sellers', 'type' => 'BS', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Premium Products', 'description' => 'High-end premium items', 'slug' => 'premium-products', 'type' => 'PR', 'status' => 'A'],
            ['user_id' => 1, 'parent_id' => 0, 'name' => 'Budget Friendly', 'description' => 'Affordable product options', 'slug' => 'budget-friendly', 'type' => 'BU', 'status' => 'A'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($catalogs as $catalog) {
            $this->db->table('pr_catalog')->insert(array_merge($catalog, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
