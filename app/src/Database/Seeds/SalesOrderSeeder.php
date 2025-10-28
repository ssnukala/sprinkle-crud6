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
 * Seeder for sales orders
 */
class SalesOrderSeeder implements SeedInterface
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
        $date30DaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $date25DaysAgo = date('Y-m-d H:i:s', strtotime('-25 days'));
        $date20DaysAgo = date('Y-m-d H:i:s', strtotime('-20 days'));
        $date15DaysAgo = date('Y-m-d H:i:s', strtotime('-15 days'));
        $date7DaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $date5DaysAgo = date('Y-m-d H:i:s', strtotime('-5 days'));
        $date25DaysAhead = date('Y-m-d H:i:s', strtotime('+25 days'));
        
        $orders = [
            [
                'year' => 2025,
                'name' => 'Order #SO-001',
                'description' => 'Electronics purchase for home office',
                'order_number' => 'SO-2025-001',
                'contract_number' => null,
                'order_status' => 'completed',
                'type' => 'retail',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date30DaysAgo,
                'expiry_date' => null,
                'net_amount' => 1379.96,
                'tax' => 110.40,
                'discount' => 0.00,
                'epay_commission' => 0.00,
                'gross_amount' => 1490.36,
                'payment_type' => 'credit_card',
                'payment_ref' => 'CC-12345',
                'payment_link' => null,
                'payment_date' => $date30DaysAgo,
                'payment_note' => 'Paid in full',
                'notes' => 'Customer requested expedited shipping',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Order #SO-002',
                'description' => 'Bulk office supplies order',
                'order_number' => 'SO-2025-002',
                'contract_number' => null,
                'order_status' => 'completed',
                'type' => 'wholesale',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date25DaysAgo,
                'expiry_date' => null,
                'net_amount' => 799.98,
                'tax' => 64.00,
                'discount' => 50.00,
                'epay_commission' => 0.00,
                'gross_amount' => 813.98,
                'payment_type' => 'invoice',
                'payment_ref' => 'INV-2025-002',
                'payment_link' => null,
                'payment_date' => $date20DaysAgo,
                'payment_note' => 'Net 30 payment terms',
                'notes' => 'Business customer - approved credit',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Order #SO-003',
                'description' => 'Gaming setup complete',
                'order_number' => 'SO-2025-003',
                'contract_number' => null,
                'order_status' => 'pending',
                'type' => 'retail',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date5DaysAgo,
                'expiry_date' => $date25DaysAhead,
                'net_amount' => 819.97,
                'tax' => 65.60,
                'discount' => 0.00,
                'epay_commission' => 0.00,
                'gross_amount' => 885.57,
                'payment_type' => 'paypal',
                'payment_ref' => null,
                'payment_link' => 'https://paypal.com/pay/12345',
                'payment_date' => null,
                'payment_note' => 'Awaiting payment',
                'notes' => 'Hold for payment confirmation',
                'status' => 'P',
            ],
            [
                'year' => 2025,
                'name' => 'Order #SO-004',
                'description' => 'Mobile phone upgrade',
                'order_number' => 'SO-2025-004',
                'contract_number' => null,
                'order_status' => 'completed',
                'type' => 'retail',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date15DaysAgo,
                'expiry_date' => null,
                'net_amount' => 799.99,
                'tax' => 64.00,
                'discount' => 100.00,
                'epay_commission' => 0.00,
                'gross_amount' => 763.99,
                'payment_type' => 'credit_card',
                'payment_ref' => 'CC-23456',
                'payment_link' => null,
                'payment_date' => $date15DaysAgo,
                'payment_note' => 'Trade-in discount applied',
                'notes' => 'Customer traded in old device',
                'status' => 'A',
            ],
            [
                'year' => 2025,
                'name' => 'Order #SO-005',
                'description' => 'Photography equipment bundle',
                'order_number' => 'SO-2025-005',
                'contract_number' => null,
                'order_status' => 'shipped',
                'type' => 'retail',
                'parent_id' => null,
                'user_id' => 1,
                'approver_id' => null,
                'order_date' => $date7DaysAgo,
                'expiry_date' => null,
                'net_amount' => 1199.98,
                'tax' => 96.00,
                'discount' => 0.00,
                'epay_commission' => 0.00,
                'gross_amount' => 1295.98,
                'payment_type' => 'credit_card',
                'payment_ref' => 'CC-34567',
                'payment_link' => null,
                'payment_date' => $date7DaysAgo,
                'payment_note' => 'Paid in full',
                'notes' => 'Shipped via express courier',
                'status' => 'A',
            ],
        ];

        foreach ($orders as $order) {
            $this->db->table('or_sales_order')->insert(array_merge($order, [
                'created_at' => $order['order_date'],
                'updated_at' => $now,
            ]));
        }
    }
}
