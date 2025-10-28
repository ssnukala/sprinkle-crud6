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
 * Seed Order Details Table with Test Data
 */
class OrderDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderDetails = [
            // Order 1 - ORD-2024-001
            ['order_id' => 1, 'line_number' => 1, 'sku' => 'DELL-XPS15-2024', 'product_name' => 'Dell XPS 15 Laptop', 'quantity' => 1, 'unit_price' => 1899.99, 'line_total' => 1899.99, 'notes' => null],
            ['order_id' => 1, 'line_number' => 2, 'sku' => 'DYSON-V15DET', 'product_name' => 'Dyson V15 Vacuum', 'quantity' => 1, 'unit_price' => 649.99, 'line_total' => 649.99, 'notes' => 'Include extra filter'],
            
            // Order 2 - ORD-2024-002
            ['order_id' => 2, 'line_number' => 1, 'sku' => 'APPLE-IP15PRO', 'product_name' => 'iPhone 15 Pro', 'quantity' => 1, 'unit_price' => 1199.99, 'line_total' => 1199.99, 'notes' => 'Space Black color'],
            
            // Order 3 - ORD-2024-003
            ['order_id' => 3, 'line_number' => 1, 'sku' => 'LEGO-SW75192', 'product_name' => 'LEGO Star Wars Set', 'quantity' => 1, 'unit_price' => 849.99, 'line_total' => 849.99, 'notes' => 'Birthday gift'],
            
            // Order 4 - ORD-2024-004
            ['order_id' => 4, 'line_number' => 1, 'sku' => 'SONY-WH1000XM5', 'product_name' => 'Sony WH-1000XM5 Headphones', 'quantity' => 1, 'unit_price' => 399.99, 'line_total' => 399.99, 'notes' => null],
            
            // Order 5 - ORD-2024-005
            ['order_id' => 5, 'line_number' => 1, 'sku' => 'CANON-EOSR6', 'product_name' => 'Canon EOS R6 Camera', 'quantity' => 1, 'unit_price' => 2499.99, 'line_total' => 2499.99, 'notes' => 'Professional photography'],
            
            // Order 6 - ORD-2024-006
            ['order_id' => 6, 'line_number' => 1, 'sku' => 'SAMSUNG-65Q80C', 'product_name' => 'Samsung 65" 4K TV', 'quantity' => 1, 'unit_price' => 1799.99, 'line_total' => 1799.99, 'notes' => 'Wall mount included'],
            
            // Order 7 - ORD-2024-007
            ['order_id' => 7, 'line_number' => 1, 'sku' => 'DYSON-V15DET', 'product_name' => 'Dyson V15 Vacuum', 'quantity' => 1, 'unit_price' => 649.99, 'line_total' => 649.99, 'notes' => null],
            
            // Order 8 - ORD-2024-008
            ['order_id' => 8, 'line_number' => 1, 'sku' => 'HP-LJPROM404N', 'product_name' => 'HP LaserJet Pro Printer', 'quantity' => 1, 'unit_price' => 329.99, 'line_total' => 329.99, 'notes' => 'Toner cartridge included'],
            
            // Order 9 - ORD-2024-009
            ['order_id' => 9, 'line_number' => 1, 'sku' => 'BOOK-GATSBY', 'product_name' => 'The Great Gatsby', 'quantity' => 3, 'unit_price' => 14.99, 'line_total' => 44.97, 'notes' => 'Book club order'],
            
            // Order 10 - ORD-2024-010
            ['order_id' => 10, 'line_number' => 1, 'sku' => 'NIKE-AIRMAX270', 'product_name' => 'Nike Air Max Sneakers', 'quantity' => 2, 'unit_price' => 159.99, 'line_total' => 319.98, 'notes' => 'Size 10 and Size 8'],
            ['order_id' => 10, 'line_number' => 2, 'sku' => 'NIKE-AIRMAX270', 'product_name' => 'Nike Air Max Sneakers', 'quantity' => 1, 'unit_price' => 159.99, 'line_total' => 159.99, 'notes' => 'Size 7'],
            
            // Order 11 - ORD-2024-011
            ['order_id' => 11, 'line_number' => 1, 'sku' => 'LEGO-SW75192', 'product_name' => 'LEGO Star Wars Set', 'quantity' => 1, 'unit_price' => 849.99, 'line_total' => 849.99, 'notes' => 'Collector item'],
            
            // Order 12 - ORD-2024-012
            ['order_id' => 12, 'line_number' => 1, 'sku' => 'FITBIT-CHARGE6', 'product_name' => 'Fitbit Charge 6', 'quantity' => 1, 'unit_price' => 179.99, 'line_total' => 179.99, 'notes' => null],
            
            // Order 13 - ORD-2024-013
            ['order_id' => 13, 'line_number' => 1, 'sku' => 'MICHELIN-PREM4', 'product_name' => 'Michelin All-Season Tires', 'quantity' => 4, 'unit_price' => 189.99, 'line_total' => 759.96, 'notes' => 'Include installation'],
            
            // Order 14 - ORD-2024-014
            ['order_id' => 14, 'line_number' => 1, 'sku' => 'DEWALT-DCD791', 'product_name' => 'DeWalt Cordless Drill', 'quantity' => 1, 'unit_price' => 179.99, 'line_total' => 179.99, 'notes' => null],
            
            // Order 15 - ORD-2024-015
            ['order_id' => 15, 'line_number' => 1, 'sku' => 'PURINA-PROPLAN', 'product_name' => 'Purina Pro Plan Dog Food', 'quantity' => 1, 'unit_price' => 54.99, 'line_total' => 54.99, 'notes' => 'Monthly subscription'],
            
            // Order 16 - ORD-2024-016
            ['order_id' => 16, 'line_number' => 1, 'sku' => 'ROLEX-SUB116610', 'product_name' => 'Rolex Submariner Watch', 'quantity' => 1, 'unit_price' => 9850.00, 'line_total' => 9850.00, 'notes' => 'Authentication certificate included'],
            
            // Order 17 - ORD-2024-017
            ['order_id' => 17, 'line_number' => 1, 'sku' => 'FENDER-STRAT-US', 'product_name' => 'Fender Stratocaster Guitar', 'quantity' => 1, 'unit_price' => 1899.99, 'line_total' => 1899.99, 'notes' => 'Sunburst finish'],
            
            // Order 18 - ORD-2024-018
            ['order_id' => 18, 'line_number' => 1, 'sku' => 'ADOBE-CC-ANNUAL', 'product_name' => 'Adobe Creative Cloud', 'quantity' => 1, 'unit_price' => 599.88, 'line_total' => 599.88, 'notes' => 'Business license'],
            
            // Order 19 - ORD-2024-019
            ['order_id' => 19, 'line_number' => 1, 'sku' => 'LAVAZZA-QUAL1KG', 'product_name' => 'Lavazza Coffee Beans', 'quantity' => 3, 'unit_price' => 24.99, 'line_total' => 74.97, 'notes' => 'Monthly delivery'],
            
            // Order 20 - ORD-2024-020
            ['order_id' => 20, 'line_number' => 1, 'sku' => 'PRISMA-PREM150', 'product_name' => 'Prismacolor Premier Pencils', 'quantity' => 1, 'unit_price' => 89.99, 'line_total' => 89.99, 'notes' => null],
        ];

        foreach ($orderDetails as $detail) {
            DB::table('order_details')->insert(array_merge($detail, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
