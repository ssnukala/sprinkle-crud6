<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed Product Categories Junction Table with Test Data
 */
class ProductCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assign multiple categories to some products to demonstrate many-to-many relationships
        $productCategories = [
            ['product_id' => 1, 'category_id' => 2],  // Dell XPS in Computers
            ['product_id' => 1, 'category_id' => 1],  // Dell XPS also in Electronics
            ['product_id' => 2, 'category_id' => 3],  // iPhone in Mobile Devices
            ['product_id' => 2, 'category_id' => 1],  // iPhone also in Electronics
            ['product_id' => 3, 'category_id' => 4],  // Sony Headphones in Audio Equipment
            ['product_id' => 3, 'category_id' => 1],  // Also in Electronics
            ['product_id' => 4, 'category_id' => 5],  // Canon Camera in Cameras
            ['product_id' => 4, 'category_id' => 1],  // Also in Electronics
            ['product_id' => 5, 'category_id' => 1],  // Samsung TV in Electronics
            ['product_id' => 6, 'category_id' => 6],  // Dyson Vacuum in Home Appliances
            ['product_id' => 7, 'category_id' => 7],  // HP Printer in Office Supplies
            ['product_id' => 7, 'category_id' => 1],  // Also in Electronics
            ['product_id' => 8, 'category_id' => 8],  // Gatsby in Books
            ['product_id' => 9, 'category_id' => 9],  // Nike Sneakers in Clothing
            ['product_id' => 9, 'category_id' => 10], // Also in Sports & Outdoors
            ['product_id' => 10, 'category_id' => 10], // Tennis Racket in Sports & Outdoors
            ['product_id' => 11, 'category_id' => 11], // LEGO in Toys & Games
            ['product_id' => 12, 'category_id' => 12], // Fitbit in Health & Beauty
            ['product_id' => 12, 'category_id' => 1],  // Also in Electronics
            ['product_id' => 13, 'category_id' => 13], // Michelin Tires in Automotive
            ['product_id' => 14, 'category_id' => 14], // DeWalt Drill in Garden & Tools
            ['product_id' => 15, 'category_id' => 15], // Dog Food in Pet Supplies
            ['product_id' => 16, 'category_id' => 16], // Rolex in Jewelry
            ['product_id' => 17, 'category_id' => 17], // Fender Guitar in Music Instruments
            ['product_id' => 18, 'category_id' => 18], // Adobe in Software
            ['product_id' => 19, 'category_id' => 19], // Lavazza in Food & Beverages
            ['product_id' => 20, 'category_id' => 20], // Prismacolor in Art & Crafts
        ];

        foreach ($productCategories as $productCategory) {
            DB::table('product_categories')->insert(array_merge($productCategory, [
                'created_at' => now(),
            ]));
        }
    }
}
