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
 * Seeder for products
 */
class ProductSeeder implements SeedInterface
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
        $products = [
            // Electronics (category_id: 1)
            ['category_id' => 1, 'name' => 'Wireless Mouse', 'description' => 'Ergonomic wireless mouse with USB receiver', 'slug' => 'wireless-mouse', 'type' => 'PH', 'unit_price' => 29.99, 'tax' => 2.40, 'status' => 'A'],
            ['category_id' => 1, 'name' => 'USB-C Hub', 'description' => '7-in-1 USB-C Hub with HDMI and card reader', 'slug' => 'usb-c-hub', 'type' => 'PH', 'unit_price' => 49.99, 'tax' => 4.00, 'status' => 'A'],
            ['category_id' => 1, 'name' => 'Webcam HD', 'description' => '1080p HD webcam with built-in microphone', 'slug' => 'webcam-hd', 'type' => 'PH', 'unit_price' => 79.99, 'tax' => 6.40, 'status' => 'A'],
            
            // Computers (category_id: 2)
            ['category_id' => 2, 'name' => 'Gaming Laptop', 'description' => '15.6" gaming laptop with RTX graphics', 'slug' => 'gaming-laptop', 'type' => 'PH', 'unit_price' => 1299.99, 'tax' => 104.00, 'status' => 'A'],
            ['category_id' => 2, 'name' => 'Business Desktop', 'description' => 'Compact desktop computer for office use', 'slug' => 'business-desktop', 'type' => 'PH', 'unit_price' => 899.99, 'tax' => 72.00, 'status' => 'A'],
            ['category_id' => 2, 'name' => 'Ultrabook Pro', 'description' => '13.3" lightweight ultrabook', 'slug' => 'ultrabook-pro', 'type' => 'PH', 'unit_price' => 1499.99, 'tax' => 120.00, 'status' => 'A'],
            
            // Smartphones (category_id: 3)
            ['category_id' => 3, 'name' => 'Smartphone X1', 'description' => '6.5" OLED display smartphone with 5G', 'slug' => 'smartphone-x1', 'type' => 'PH', 'unit_price' => 799.99, 'tax' => 64.00, 'status' => 'A'],
            ['category_id' => 3, 'name' => 'Budget Phone', 'description' => 'Affordable smartphone with good battery life', 'slug' => 'budget-phone', 'type' => 'PH', 'unit_price' => 199.99, 'tax' => 16.00, 'status' => 'A'],
            ['category_id' => 3, 'name' => 'Premium Flagship', 'description' => 'Top-tier flagship smartphone', 'slug' => 'premium-flagship', 'type' => 'PH', 'unit_price' => 1199.99, 'tax' => 96.00, 'status' => 'A'],
            
            // Tablets (category_id: 4)
            ['category_id' => 4, 'name' => 'Tablet Pro 11', 'description' => '11" professional tablet with stylus', 'slug' => 'tablet-pro-11', 'type' => 'PH', 'unit_price' => 699.99, 'tax' => 56.00, 'status' => 'A'],
            ['category_id' => 4, 'name' => 'Kids Tablet', 'description' => '7" educational tablet for children', 'slug' => 'kids-tablet', 'type' => 'PH', 'unit_price' => 129.99, 'tax' => 10.40, 'status' => 'A'],
            
            // Audio (category_id: 5)
            ['category_id' => 5, 'name' => 'Wireless Headphones', 'description' => 'Noise-canceling Bluetooth headphones', 'slug' => 'wireless-headphones', 'type' => 'PH', 'unit_price' => 249.99, 'tax' => 20.00, 'status' => 'A'],
            ['category_id' => 5, 'name' => 'Portable Speaker', 'description' => 'Waterproof Bluetooth speaker', 'slug' => 'portable-speaker', 'type' => 'PH', 'unit_price' => 89.99, 'tax' => 7.20, 'status' => 'A'],
            ['category_id' => 5, 'name' => 'Gaming Headset', 'description' => '7.1 surround sound gaming headset', 'slug' => 'gaming-headset', 'type' => 'PH', 'unit_price' => 149.99, 'tax' => 12.00, 'status' => 'A'],
            
            // Cameras (category_id: 6)
            ['category_id' => 6, 'name' => 'DSLR Camera Kit', 'description' => 'Professional DSLR camera with 18-55mm lens', 'slug' => 'dslr-camera-kit', 'type' => 'PH', 'unit_price' => 899.99, 'tax' => 72.00, 'status' => 'A'],
            ['category_id' => 6, 'name' => 'Action Camera', 'description' => '4K action camera with accessories', 'slug' => 'action-camera', 'type' => 'PH', 'unit_price' => 299.99, 'tax' => 24.00, 'status' => 'A'],
            
            // Wearables (category_id: 7)
            ['category_id' => 7, 'name' => 'Fitness Tracker', 'description' => 'Water-resistant fitness tracker with heart rate monitor', 'slug' => 'fitness-tracker', 'type' => 'PH', 'unit_price' => 79.99, 'tax' => 6.40, 'status' => 'A'],
            ['category_id' => 7, 'name' => 'Smartwatch Pro', 'description' => 'Premium smartwatch with GPS and cellular', 'slug' => 'smartwatch-pro', 'type' => 'PH', 'unit_price' => 399.99, 'tax' => 32.00, 'status' => 'A'],
            
            // Gaming (category_id: 8)
            ['category_id' => 8, 'name' => 'Gaming Console', 'description' => 'Next-gen gaming console with 1TB storage', 'slug' => 'gaming-console', 'type' => 'PH', 'unit_price' => 499.99, 'tax' => 40.00, 'status' => 'A'],
            ['category_id' => 8, 'name' => 'Gaming Controller', 'description' => 'Wireless gaming controller with haptic feedback', 'slug' => 'gaming-controller', 'type' => 'PH', 'unit_price' => 69.99, 'tax' => 5.60, 'status' => 'A'],
            
            // Home Appliances (category_id: 9)
            ['category_id' => 9, 'name' => 'Smart Coffee Maker', 'description' => 'Programmable coffee maker with app control', 'slug' => 'smart-coffee-maker', 'type' => 'PH', 'unit_price' => 149.99, 'tax' => 12.00, 'status' => 'A'],
            ['category_id' => 9, 'name' => 'Air Purifier', 'description' => 'HEPA air purifier for large rooms', 'slug' => 'air-purifier', 'type' => 'PH', 'unit_price' => 199.99, 'tax' => 16.00, 'status' => 'A'],
            
            // Office Supplies (category_id: 10)
            ['category_id' => 10, 'name' => 'Ergonomic Chair', 'description' => 'Adjustable ergonomic office chair', 'slug' => 'ergonomic-chair', 'type' => 'PH', 'unit_price' => 299.99, 'tax' => 24.00, 'status' => 'A'],
            ['category_id' => 10, 'name' => 'Standing Desk', 'description' => 'Electric height-adjustable standing desk', 'slug' => 'standing-desk', 'type' => 'PH', 'unit_price' => 499.99, 'tax' => 40.00, 'status' => 'A'],
            
            // Books (category_id: 11)
            ['category_id' => 11, 'name' => 'Programming Guide', 'description' => 'Complete guide to modern programming', 'slug' => 'programming-guide', 'type' => 'PH', 'unit_price' => 49.99, 'tax' => 0.00, 'status' => 'A'],
            ['category_id' => 11, 'name' => 'Business Strategy Book', 'description' => 'Best practices for business growth', 'slug' => 'business-strategy-book', 'type' => 'PH', 'unit_price' => 29.99, 'tax' => 0.00, 'status' => 'A'],
            
            // Clothing (category_id: 12)
            ['category_id' => 12, 'name' => 'Running Shoes', 'description' => 'Lightweight running shoes with cushioning', 'slug' => 'running-shoes', 'type' => 'PH', 'unit_price' => 89.99, 'tax' => 7.20, 'status' => 'A'],
            ['category_id' => 12, 'name' => 'Winter Jacket', 'description' => 'Warm winter jacket with hood', 'slug' => 'winter-jacket', 'type' => 'PH', 'unit_price' => 129.99, 'tax' => 10.40, 'status' => 'A'],
            
            // Sports & Outdoors (category_id: 13)
            ['category_id' => 13, 'name' => 'Yoga Mat', 'description' => 'Non-slip yoga mat with carrying strap', 'slug' => 'yoga-mat', 'type' => 'PH', 'unit_price' => 39.99, 'tax' => 3.20, 'status' => 'A'],
            ['category_id' => 13, 'name' => 'Camping Tent', 'description' => '4-person waterproof camping tent', 'slug' => 'camping-tent', 'type' => 'PH', 'unit_price' => 199.99, 'tax' => 16.00, 'status' => 'A'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($products as $product) {
            $this->db->table('pr_product')->insert(array_merge($product, [
                'active_date' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
