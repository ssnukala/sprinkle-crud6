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
 * Seed Categories Table with Test Data
 */
class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and accessories', 'icon' => 'laptop', 'is_active' => true],
            ['name' => 'Computers', 'slug' => 'computers', 'description' => 'Desktop and laptop computers', 'icon' => 'desktop', 'is_active' => true],
            ['name' => 'Mobile Devices', 'slug' => 'mobile-devices', 'description' => 'Smartphones and tablets', 'icon' => 'mobile', 'is_active' => true],
            ['name' => 'Audio Equipment', 'slug' => 'audio-equipment', 'description' => 'Headphones, speakers, and audio accessories', 'icon' => 'headphones', 'is_active' => true],
            ['name' => 'Cameras', 'slug' => 'cameras', 'description' => 'Digital cameras and photography equipment', 'icon' => 'camera', 'is_active' => true],
            ['name' => 'Home Appliances', 'slug' => 'home-appliances', 'description' => 'Kitchen and household appliances', 'icon' => 'home', 'is_active' => true],
            ['name' => 'Office Supplies', 'slug' => 'office-supplies', 'description' => 'Stationery and office equipment', 'icon' => 'briefcase', 'is_active' => true],
            ['name' => 'Books', 'slug' => 'books', 'description' => 'Physical and digital books', 'icon' => 'book', 'is_active' => true],
            ['name' => 'Clothing', 'slug' => 'clothing', 'description' => 'Apparel and fashion items', 'icon' => 'shopping-bag', 'is_active' => true],
            ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'description' => 'Sports equipment and outdoor gear', 'icon' => 'activity', 'is_active' => true],
            ['name' => 'Toys & Games', 'slug' => 'toys-games', 'description' => 'Children\'s toys and board games', 'icon' => 'gift', 'is_active' => true],
            ['name' => 'Health & Beauty', 'slug' => 'health-beauty', 'description' => 'Health products and cosmetics', 'icon' => 'heart', 'is_active' => true],
            ['name' => 'Automotive', 'slug' => 'automotive', 'description' => 'Car parts and accessories', 'icon' => 'truck', 'is_active' => true],
            ['name' => 'Garden & Tools', 'slug' => 'garden-tools', 'description' => 'Gardening supplies and power tools', 'icon' => 'tool', 'is_active' => true],
            ['name' => 'Pet Supplies', 'slug' => 'pet-supplies', 'description' => 'Food and accessories for pets', 'icon' => 'github', 'is_active' => true],
            ['name' => 'Jewelry', 'slug' => 'jewelry', 'description' => 'Watches, rings, and fine jewelry', 'icon' => 'award', 'is_active' => true],
            ['name' => 'Music Instruments', 'slug' => 'music-instruments', 'description' => 'Musical instruments and accessories', 'icon' => 'music', 'is_active' => true],
            ['name' => 'Software', 'slug' => 'software', 'description' => 'Computer software and licenses', 'icon' => 'code', 'is_active' => true],
            ['name' => 'Food & Beverages', 'slug' => 'food-beverages', 'description' => 'Gourmet food and specialty drinks', 'icon' => 'coffee', 'is_active' => true],
            ['name' => 'Art & Crafts', 'slug' => 'art-crafts', 'description' => 'Art supplies and craft materials', 'icon' => 'edit', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
