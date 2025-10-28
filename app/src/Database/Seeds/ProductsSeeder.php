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
 * Seed Products Table with Test Data
 */
class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Dell XPS 15 Laptop', 'sku' => 'DELL-XPS15-2024', 'price' => 1899.99, 'description' => 'High-performance 15-inch laptop with Intel i7 processor', 'category_id' => 2, 'tags' => 'laptop,dell,intel', 'is_active' => true, 'launch_date' => '2024-01-15'],
            ['name' => 'iPhone 15 Pro', 'sku' => 'APPLE-IP15PRO', 'price' => 1199.99, 'description' => 'Latest iPhone with A17 Pro chip', 'category_id' => 3, 'tags' => 'smartphone,apple,ios', 'is_active' => true, 'launch_date' => '2024-09-22'],
            ['name' => 'Sony WH-1000XM5 Headphones', 'sku' => 'SONY-WH1000XM5', 'price' => 399.99, 'description' => 'Premium noise-cancelling wireless headphones', 'category_id' => 4, 'tags' => 'headphones,sony,wireless', 'is_active' => true, 'launch_date' => '2023-05-12'],
            ['name' => 'Canon EOS R6 Camera', 'sku' => 'CANON-EOSR6', 'price' => 2499.99, 'description' => 'Full-frame mirrorless camera with 20MP sensor', 'category_id' => 5, 'tags' => 'camera,canon,mirrorless', 'is_active' => true, 'launch_date' => '2023-07-20'],
            ['name' => 'Samsung 65" 4K TV', 'sku' => 'SAMSUNG-65Q80C', 'price' => 1799.99, 'description' => 'QLED 4K Smart TV with Quantum HDR', 'category_id' => 1, 'tags' => 'tv,samsung,4k', 'is_active' => true, 'launch_date' => '2024-03-10'],
            ['name' => 'Dyson V15 Vacuum', 'sku' => 'DYSON-V15DET', 'price' => 649.99, 'description' => 'Cordless vacuum with laser dust detection', 'category_id' => 6, 'tags' => 'vacuum,dyson,cordless', 'is_active' => true, 'launch_date' => '2023-11-05'],
            ['name' => 'HP LaserJet Pro Printer', 'sku' => 'HP-LJPROM404N', 'price' => 329.99, 'description' => 'Monochrome laser printer for office use', 'category_id' => 7, 'tags' => 'printer,hp,laser', 'is_active' => true, 'launch_date' => '2024-02-14'],
            ['name' => 'The Great Gatsby', 'sku' => 'BOOK-GATSBY', 'price' => 14.99, 'description' => 'Classic American novel by F. Scott Fitzgerald', 'category_id' => 8, 'tags' => 'book,classic,fiction', 'is_active' => true, 'launch_date' => '2023-01-01'],
            ['name' => 'Nike Air Max Sneakers', 'sku' => 'NIKE-AIRMAX270', 'price' => 159.99, 'description' => 'Comfortable running shoes with Air cushioning', 'category_id' => 9, 'tags' => 'shoes,nike,sports', 'is_active' => true, 'launch_date' => '2024-04-01'],
            ['name' => 'Wilson Tennis Racket', 'sku' => 'WILSON-BLADE98', 'price' => 219.99, 'description' => 'Professional tennis racket for advanced players', 'category_id' => 10, 'tags' => 'tennis,wilson,sports', 'is_active' => true, 'launch_date' => '2023-06-15'],
            ['name' => 'LEGO Star Wars Set', 'sku' => 'LEGO-SW75192', 'price' => 849.99, 'description' => 'Millennium Falcon Ultimate Collector Series', 'category_id' => 11, 'tags' => 'lego,starwars,collectible', 'is_active' => true, 'launch_date' => '2023-09-01'],
            ['name' => 'Fitbit Charge 6', 'sku' => 'FITBIT-CHARGE6', 'price' => 179.99, 'description' => 'Advanced fitness tracker with heart rate monitoring', 'category_id' => 12, 'tags' => 'fitness,fitbit,wearable', 'is_active' => true, 'launch_date' => '2024-10-01'],
            ['name' => 'Michelin All-Season Tires', 'sku' => 'MICHELIN-PREM4', 'price' => 189.99, 'description' => 'Set of 4 premium all-season tires', 'category_id' => 13, 'tags' => 'tires,michelin,automotive', 'is_active' => true, 'launch_date' => '2024-05-20'],
            ['name' => 'DeWalt Cordless Drill', 'sku' => 'DEWALT-DCD791', 'price' => 179.99, 'description' => '20V MAX brushless drill/driver kit', 'category_id' => 14, 'tags' => 'drill,dewalt,powertools', 'is_active' => true, 'launch_date' => '2023-08-10'],
            ['name' => 'Purina Pro Plan Dog Food', 'sku' => 'PURINA-PROPLAN', 'price' => 54.99, 'description' => '35lb bag of premium dog food', 'category_id' => 15, 'tags' => 'petfood,purina,dogs', 'is_active' => true, 'launch_date' => '2024-01-05'],
            ['name' => 'Rolex Submariner Watch', 'sku' => 'ROLEX-SUB116610', 'price' => 9850.00, 'description' => 'Luxury dive watch with automatic movement', 'category_id' => 16, 'tags' => 'watch,rolex,luxury', 'is_active' => true, 'launch_date' => '2023-12-01'],
            ['name' => 'Fender Stratocaster Guitar', 'sku' => 'FENDER-STRAT-US', 'price' => 1899.99, 'description' => 'American Professional II electric guitar', 'category_id' => 17, 'tags' => 'guitar,fender,music', 'is_active' => true, 'launch_date' => '2024-03-15'],
            ['name' => 'Adobe Creative Cloud', 'sku' => 'ADOBE-CC-ANNUAL', 'price' => 599.88, 'description' => 'Annual subscription to all Adobe apps', 'category_id' => 18, 'tags' => 'software,adobe,creative', 'is_active' => true, 'launch_date' => '2024-01-01'],
            ['name' => 'Lavazza Coffee Beans', 'sku' => 'LAVAZZA-QUAL1KG', 'price' => 24.99, 'description' => '1kg bag of premium Italian coffee beans', 'category_id' => 19, 'tags' => 'coffee,lavazza,gourmet', 'is_active' => true, 'launch_date' => '2023-10-01'],
            ['name' => 'Prismacolor Premier Pencils', 'sku' => 'PRISMA-PREM150', 'price' => 89.99, 'description' => 'Set of 150 colored pencils for artists', 'category_id' => 20, 'tags' => 'art,prismacolor,pencils', 'is_active' => true, 'launch_date' => '2024-02-01'],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert(array_merge($product, [
                'metadata' => json_encode(['featured' => rand(0, 1) == 1]),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
