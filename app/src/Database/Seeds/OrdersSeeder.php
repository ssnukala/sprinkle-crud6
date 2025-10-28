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
 * Seed Orders Table with Test Data
 */
class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = [
            ['order_number' => 'ORD-2024-001', 'customer_name' => 'John Smith', 'customer_email' => 'john.smith@email.com', 'total_amount' => 2549.97, 'payment_status' => 'paid', 'order_date' => '2024-01-15', 'notes' => 'Express delivery requested'],
            ['order_number' => 'ORD-2024-002', 'customer_name' => 'Jane Doe', 'customer_email' => 'jane.doe@email.com', 'total_amount' => 1199.99, 'payment_status' => 'paid', 'order_date' => '2024-01-16', 'notes' => null],
            ['order_number' => 'ORD-2024-003', 'customer_name' => 'Bob Johnson', 'customer_email' => 'bob.johnson@email.com', 'total_amount' => 849.99, 'payment_status' => 'pending', 'order_date' => '2024-01-17', 'notes' => 'Gift wrapping requested'],
            ['order_number' => 'ORD-2024-004', 'customer_name' => 'Alice Williams', 'customer_email' => 'alice.w@email.com', 'total_amount' => 399.99, 'payment_status' => 'paid', 'order_date' => '2024-01-18', 'notes' => null],
            ['order_number' => 'ORD-2024-005', 'customer_name' => 'Charlie Brown', 'customer_email' => 'charlie.b@email.com', 'total_amount' => 2499.99, 'payment_status' => 'paid', 'order_date' => '2024-01-19', 'notes' => 'Professional photographer purchase'],
            ['order_number' => 'ORD-2024-006', 'customer_name' => 'Diana Prince', 'customer_email' => 'diana.prince@email.com', 'total_amount' => 1799.99, 'payment_status' => 'shipped', 'order_date' => '2024-01-20', 'notes' => 'Installation service included'],
            ['order_number' => 'ORD-2024-007', 'customer_name' => 'Edward Norton', 'customer_email' => 'edward.n@email.com', 'total_amount' => 649.99, 'payment_status' => 'paid', 'order_date' => '2024-01-21', 'notes' => null],
            ['order_number' => 'ORD-2024-008', 'customer_name' => 'Fiona Green', 'customer_email' => 'fiona.green@email.com', 'total_amount' => 329.99, 'payment_status' => 'paid', 'order_date' => '2024-01-22', 'notes' => 'Office equipment purchase'],
            ['order_number' => 'ORD-2024-009', 'customer_name' => 'George Wilson', 'customer_email' => 'george.w@email.com', 'total_amount' => 44.97, 'payment_status' => 'paid', 'order_date' => '2024-01-23', 'notes' => null],
            ['order_number' => 'ORD-2024-010', 'customer_name' => 'Hannah Montana', 'customer_email' => 'hannah.m@email.com', 'total_amount' => 479.97, 'payment_status' => 'pending', 'order_date' => '2024-01-24', 'notes' => 'Waiting for payment confirmation'],
            ['order_number' => 'ORD-2024-011', 'customer_name' => 'Ian McKellen', 'customer_email' => 'ian.mck@email.com', 'total_amount' => 849.99, 'payment_status' => 'paid', 'order_date' => '2024-01-25', 'notes' => 'Collector\'s edition'],
            ['order_number' => 'ORD-2024-012', 'customer_name' => 'Julia Roberts', 'customer_email' => 'julia.r@email.com', 'total_amount' => 179.99, 'payment_status' => 'shipped', 'order_date' => '2024-01-26', 'notes' => 'Fitness enthusiast'],
            ['order_number' => 'ORD-2024-013', 'customer_name' => 'Kevin Hart', 'customer_email' => 'kevin.h@email.com', 'total_amount' => 759.96, 'payment_status' => 'paid', 'order_date' => '2024-01-27', 'notes' => 'Automotive upgrade'],
            ['order_number' => 'ORD-2024-014', 'customer_name' => 'Laura Palmer', 'customer_email' => 'laura.p@email.com', 'total_amount' => 179.99, 'payment_status' => 'paid', 'order_date' => '2024-01-28', 'notes' => 'Home improvement project'],
            ['order_number' => 'ORD-2024-015', 'customer_name' => 'Michael Scott', 'customer_email' => 'michael.s@email.com', 'total_amount' => 54.99, 'payment_status' => 'paid', 'order_date' => '2024-01-29', 'notes' => 'Pet supplies'],
            ['order_number' => 'ORD-2024-016', 'customer_name' => 'Nancy Drew', 'customer_email' => 'nancy.d@email.com', 'total_amount' => 9850.00, 'payment_status' => 'paid', 'order_date' => '2024-01-30', 'notes' => 'Luxury purchase - VIP customer'],
            ['order_number' => 'ORD-2024-017', 'customer_name' => 'Oscar Wilde', 'customer_email' => 'oscar.w@email.com', 'total_amount' => 1899.99, 'payment_status' => 'pending', 'order_date' => '2024-01-31', 'notes' => 'Musician - professional use'],
            ['order_number' => 'ORD-2024-018', 'customer_name' => 'Patricia Hill', 'customer_email' => 'patricia.h@email.com', 'total_amount' => 599.88, 'payment_status' => 'paid', 'order_date' => '2024-02-01', 'notes' => 'Annual subscription'],
            ['order_number' => 'ORD-2024-019', 'customer_name' => 'Quincy Jones', 'customer_email' => 'quincy.j@email.com', 'total_amount' => 74.97, 'payment_status' => 'shipped', 'order_date' => '2024-02-02', 'notes' => 'Gourmet coffee order'],
            ['order_number' => 'ORD-2024-020', 'customer_name' => 'Rachel Green', 'customer_email' => 'rachel.g@email.com', 'total_amount' => 89.99, 'payment_status' => 'paid', 'order_date' => '2024-02-03', 'notes' => 'Art supplies for hobby'],
        ];

        foreach ($orders as $order) {
            DB::table('orders')->insert(array_merge($order, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
