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
 * Seeder for purchase orders
 */
class PurchaseOrderSeeder implements SeedInterface
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
        $date45DaysAgo = date('Y-m-d H:i:s', strtotime('-45 days'));
        $date30DaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $date20DaysAgo = date('Y-m-d H:i:s', strtotime('-20 days'));
        $date10DaysAgo = date('Y-m-d H:i:s', strtotime('-10 days'));
        $date5DaysAgo = date('Y-m-d H:i:s', strtotime('-5 days'));
        $date10DaysAhead = date('Y-m-d H:i:s', strtotime('+10 days'));
        
        $orders = [
            [
                'year' => 2025,
                'name' => 'Purchase Order #PO-001',
                'description' => 'Inventory restock - Electronics',
                'order_number' => 'PO-2025-001',
                'contract_number' => 'CON-2025-ELC',
                'type' => 'inventory',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date45DaysAgo,
                'expiry_date' => null,
                'net_amount' => 15000.00,
                'tax' => 1200.00,
                'discount' => 500.00,
                'epay_commission' => 0.00,
                'gross_amount' => 15700.00,
                'payment_ref' => 'WIRE-12345',
                'notes' => 'Quarterly inventory restock',
                'contact_name' => 'John Smith',
                'contact_email' => 'john.smith@supplier.com',
                'contact_phone' => '+1-555-0001',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Purchase Order #PO-002',
                'description' => 'Office furniture and supplies',
                'order_number' => 'PO-2025-002',
                'contract_number' => null,
                'type' => 'supplies',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date30DaysAgo,
                'expiry_date' => null,
                'net_amount' => 5000.00,
                'tax' => 400.00,
                'discount' => 0.00,
                'epay_commission' => 0.00,
                'gross_amount' => 5400.00,
                'payment_ref' => 'CHECK-5678',
                'notes' => 'New office setup',
                'contact_name' => 'Sarah Johnson',
                'contact_email' => 'sarah.j@officepro.com',
                'contact_phone' => '+1-555-0002',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Purchase Order #PO-003',
                'description' => 'Bulk smartphone order',
                'order_number' => 'PO-2025-003',
                'contract_number' => 'CON-2025-MOB',
                'type' => 'inventory',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date20DaysAgo,
                'expiry_date' => $date10DaysAhead,
                'net_amount' => 24000.00,
                'tax' => 1920.00,
                'discount' => 1000.00,
                'epay_commission' => 0.00,
                'gross_amount' => 24920.00,
                'payment_ref' => 'WIRE-23456',
                'notes' => 'Pending delivery',
                'contact_name' => 'Mike Chen',
                'contact_email' => 'mike.chen@mobilecorp.com',
                'contact_phone' => '+1-555-0003',
                'status' => 'P',
            ],
            [
                'year' => 2025,
                'name' => 'Purchase Order #PO-004',
                'description' => 'Gaming equipment stock',
                'order_number' => 'PO-2025-004',
                'contract_number' => null,
                'type' => 'inventory',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date10DaysAgo,
                'expiry_date' => null,
                'net_amount' => 8000.00,
                'tax' => 640.00,
                'discount' => 200.00,
                'epay_commission' => 0.00,
                'gross_amount' => 8440.00,
                'payment_ref' => 'ACH-34567',
                'notes' => 'Holiday season preparation',
                'contact_name' => 'Emily Davis',
                'contact_email' => 'emily@gamingworld.com',
                'contact_phone' => '+1-555-0004',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Purchase Order #PO-005',
                'description' => 'Camera and photography equipment',
                'order_number' => 'PO-2025-005',
                'contract_number' => null,
                'type' => 'inventory',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date5DaysAgo,
                'expiry_date' => null,
                'net_amount' => 12000.00,
                'tax' => 960.00,
                'discount' => 300.00,
                'epay_commission' => 0.00,
                'gross_amount' => 12660.00,
                'payment_ref' => 'WIRE-45678',
                'notes' => 'Professional photography line expansion',
                'contact_name' => 'Robert Brown',
                'contact_email' => 'robert@photosupply.com',
                'contact_phone' => '+1-555-0005',
                'status' => 'A',
            ],
        ];

        foreach ($orders as $order) {
            $this->db->table('or_purchase_order')->insert(array_merge($order, [
                'created_at' => $order['order_date'],
                'updated_at' => $now,
            ]));
        }
    }
}
