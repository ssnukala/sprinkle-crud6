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
 * Seeder for purchase order line items
 */
class PurchaseOrderLinesSeeder implements SeedInterface
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
            // PO-001 lines (Electronics restock)
            ['order_id' => 1, 'line_no' => 1, 'product_catalog_id' => 1, 'description' => 'Wireless Mouse - Bulk 50 units', 'unit_price' => 15.00, 'quantity' => 50, 'net_amount' => 750.00, 'tax' => 60.00, 'discount' => 25.00, 'gross_amount' => 785.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 2, 'product_catalog_id' => 2, 'description' => 'USB-C Hub - Bulk 30 units', 'unit_price' => 25.00, 'quantity' => 30, 'net_amount' => 750.00, 'tax' => 60.00, 'discount' => 25.00, 'gross_amount' => 785.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 3, 'product_catalog_id' => 3, 'description' => 'Webcam HD - Bulk 20 units', 'unit_price' => 40.00, 'quantity' => 20, 'net_amount' => 800.00, 'tax' => 64.00, 'discount' => 30.00, 'gross_amount' => 834.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 4, 'product_catalog_id' => 12, 'description' => 'Wireless Headphones - Bulk 20 units', 'unit_price' => 120.00, 'quantity' => 20, 'net_amount' => 2400.00, 'tax' => 192.00, 'discount' => 100.00, 'gross_amount' => 2492.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 1, 'line_no' => 5, 'product_catalog_id' => 13, 'description' => 'Portable Speaker - Bulk 40 units', 'unit_price' => 45.00, 'quantity' => 40, 'net_amount' => 1800.00, 'tax' => 144.00, 'discount' => 80.00, 'gross_amount' => 1864.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            
            // PO-002 lines (Office furniture)
            ['order_id' => 2, 'line_no' => 1, 'product_catalog_id' => 23, 'description' => 'Ergonomic Chair - 10 units', 'unit_price' => 150.00, 'quantity' => 10, 'net_amount' => 1500.00, 'tax' => 120.00, 'discount' => 0.00, 'gross_amount' => 1620.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 2, 'line_no' => 2, 'product_catalog_id' => 24, 'description' => 'Standing Desk - 5 units', 'unit_price' => 250.00, 'quantity' => 5, 'net_amount' => 1250.00, 'tax' => 100.00, 'discount' => 0.00, 'gross_amount' => 1350.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 2, 'line_no' => 3, 'product_catalog_id' => null, 'description' => 'Assembly Service', 'unit_price' => 500.00, 'quantity' => 1, 'net_amount' => 500.00, 'tax' => 40.00, 'discount' => 0.00, 'gross_amount' => 540.00, 'balance_amount' => 0.00, 'type' => 'service', 'status' => 'A'],
            
            // PO-003 lines (Smartphones)
            ['order_id' => 3, 'line_no' => 1, 'product_catalog_id' => 7, 'description' => 'Smartphone X1 - Bulk 20 units', 'unit_price' => 400.00, 'quantity' => 20, 'net_amount' => 8000.00, 'tax' => 640.00, 'discount' => 400.00, 'gross_amount' => 8240.00, 'balance_amount' => 8240.00, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 2, 'product_catalog_id' => 8, 'description' => 'Budget Phone - Bulk 50 units', 'unit_price' => 100.00, 'quantity' => 50, 'net_amount' => 5000.00, 'tax' => 400.00, 'discount' => 200.00, 'gross_amount' => 5200.00, 'balance_amount' => 5200.00, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 3, 'product_catalog_id' => 9, 'description' => 'Premium Flagship - Bulk 10 units', 'unit_price' => 600.00, 'quantity' => 10, 'net_amount' => 6000.00, 'tax' => 480.00, 'discount' => 200.00, 'gross_amount' => 6280.00, 'balance_amount' => 6280.00, 'type' => 'product', 'status' => 'P'],
            ['order_id' => 3, 'line_no' => 4, 'product_catalog_id' => null, 'description' => 'Import Duties', 'unit_price' => 1200.00, 'quantity' => 1, 'net_amount' => 1200.00, 'tax' => 0.00, 'discount' => 0.00, 'gross_amount' => 1200.00, 'balance_amount' => 1200.00, 'type' => 'fee', 'status' => 'P'],
            
            // PO-004 lines (Gaming equipment)
            ['order_id' => 4, 'line_no' => 1, 'product_catalog_id' => 19, 'description' => 'Gaming Console - Bulk 10 units', 'unit_price' => 250.00, 'quantity' => 10, 'net_amount' => 2500.00, 'tax' => 200.00, 'discount' => 50.00, 'gross_amount' => 2650.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 4, 'line_no' => 2, 'product_catalog_id' => 20, 'description' => 'Gaming Controller - Bulk 30 units', 'unit_price' => 35.00, 'quantity' => 30, 'net_amount' => 1050.00, 'tax' => 84.00, 'discount' => 30.00, 'gross_amount' => 1104.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 4, 'line_no' => 3, 'product_catalog_id' => 14, 'description' => 'Gaming Headset - Bulk 20 units', 'unit_price' => 75.00, 'quantity' => 20, 'net_amount' => 1500.00, 'tax' => 120.00, 'discount' => 50.00, 'gross_amount' => 1570.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            
            // PO-005 lines (Camera equipment)
            ['order_id' => 5, 'line_no' => 1, 'product_catalog_id' => 15, 'description' => 'DSLR Camera Kit - Bulk 10 units', 'unit_price' => 450.00, 'quantity' => 10, 'net_amount' => 4500.00, 'tax' => 360.00, 'discount' => 150.00, 'gross_amount' => 4710.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
            ['order_id' => 5, 'line_no' => 2, 'product_catalog_id' => 16, 'description' => 'Action Camera - Bulk 15 units', 'unit_price' => 150.00, 'quantity' => 15, 'net_amount' => 2250.00, 'tax' => 180.00, 'discount' => 75.00, 'gross_amount' => 2355.00, 'balance_amount' => 0.00, 'type' => 'product', 'status' => 'A'],
        ];

        foreach ($lines as $line) {
            $this->db->table('or_purchase_order_lines')->insert(array_merge($line, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
