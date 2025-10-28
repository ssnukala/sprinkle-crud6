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
 * Seeder for sales order line items
 */
class SalesOrderLinesSeeder implements SeedInterface
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
        $now = date('Y-m-d H:i:s');
        
        $lines = [
            // Order 1 lines
            ['order_id' => 1, 'line_no' => 1, 'product_catalog_id' => 4, 'description' => 'Gaming Laptop', 'unit_price' => 1299.99, 'quantity' => 1, 'net_amount' => 1299.99, 'tax' => 104.00, 'discount' => 0.00, 'gross_amount' => 1403.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 2, 'product_catalog_id' => 1, 'description' => 'Wireless Mouse', 'unit_price' => 29.99, 'quantity' => 1, 'net_amount' => 29.99, 'tax' => 2.40, 'discount' => 0.00, 'gross_amount' => 32.39, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 3, 'product_catalog_id' => 2, 'description' => 'USB-C Hub', 'unit_price' => 49.99, 'quantity' => 1, 'net_amount' => 49.99, 'tax' => 4.00, 'discount' => 0.00, 'gross_amount' => 53.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            
            // Order 2 lines
            ['order_id' => 2, 'line_no' => 1, 'product_catalog_id' => 23, 'description' => 'Ergonomic Chair', 'unit_price' => 299.99, 'quantity' => 2, 'net_amount' => 599.98, 'tax' => 48.00, 'discount' => 50.00, 'gross_amount' => 597.98, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 2, 'line_no' => 2, 'product_catalog_id' => 24, 'description' => 'Standing Desk', 'unit_price' => 499.99, 'quantity' => 1, 'net_amount' => 499.99, 'tax' => 40.00, 'discount' => 0.00, 'gross_amount' => 539.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 2, 'line_no' => 3, 'product_catalog_id' => null, 'description' => 'Delivery Fee', 'unit_price' => 50.00, 'quantity' => 1, 'net_amount' => 50.00, 'tax' => 0.00, 'discount' => 0.00, 'gross_amount' => 50.00, 'balance_amount' => 0.00, 'type' => 'service', 'status' => 'A'],
            
            // Order 3 lines
            ['order_id' => 3, 'line_no' => 1, 'product_catalog_id' => 19, 'description' => 'Gaming Console', 'unit_price' => 499.99, 'quantity' => 1, 'net_amount' => 499.99, 'tax' => 40.00, 'discount' => 0.00, 'gross_amount' => 539.99, 'balance_amount' => 539.99, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 2, 'product_catalog_id' => 20, 'description' => 'Gaming Controller', 'unit_price' => 69.99, 'quantity' => 2, 'net_amount' => 139.98, 'tax' => 11.20, 'discount' => 0.00, 'gross_amount' => 151.18, 'balance_amount' => 151.18, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 3, 'product_catalog_id' => 14, 'description' => 'Gaming Headset', 'unit_price' => 149.99, 'quantity' => 1, 'net_amount' => 149.99, 'tax' => 12.00, 'discount' => 0.00, 'gross_amount' => 161.99, 'balance_amount' => 161.99, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 4, 'product_catalog_id' => null, 'description' => 'Express Shipping', 'unit_price' => 30.00, 'quantity' => 1, 'net_amount' => 30.00, 'tax' => 2.40, 'discount' => 0.00, 'gross_amount' => 32.40, 'balance_amount' => 32.40, 'type' => 'service', 'status' => 'P'],
            
            // Order 4 lines
            ['order_id' => 4, 'line_no' => 1, 'product_catalog_id' => 7, 'description' => 'Smartphone X1', 'unit_price' => 799.99, 'quantity' => 1, 'net_amount' => 799.99, 'tax' => 64.00, 'discount' => 100.00, 'gross_amount' => 763.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            
            // Order 5 lines
            ['order_id' => 5, 'line_no' => 1, 'product_catalog_id' => 15, 'description' => 'DSLR Camera Kit', 'unit_price' => 899.99, 'quantity' => 1, 'net_amount' => 899.99, 'tax' => 72.00, 'discount' => 0.00, 'gross_amount' => 971.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 5, 'line_no' => 2, 'product_catalog_id' => 16, 'description' => 'Action Camera', 'unit_price' => 299.99, 'quantity' => 1, 'net_amount' => 299.99, 'tax' => 24.00, 'discount' => 0.00, 'gross_amount' => 323.99, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
        ];

        foreach ($lines as $line) {
            $this->db->table('or_sales_order_lines')->insert(array_merge($line, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
