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

use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;

/**
 * Master seeder for Commerce sprinkle
 * 
 * Seeds all commerce tables with sample data for testing
 */
class CommerceSeeder implements SeedInterface
{
    /**
     * Constructor
     */
    public function __construct(
        protected CategorySeeder $categorySeeder,
        protected CatalogSeeder $catalogSeeder,
        protected ProductSeeder $productSeeder,
        protected ProductCatalogSeeder $productCatalogSeeder,
        protected SalesOrderSeeder $salesOrderSeeder,
        protected SalesOrderLinesSeeder $salesOrderLinesSeeder,
        protected PurchaseOrderSeeder $purchaseOrderSeeder,
        protected PurchaseOrderLinesSeeder $purchaseOrderLinesSeeder,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed in proper order to respect foreign key constraints
        $this->categorySeeder->run();
        $this->catalogSeeder->run();
        $this->productSeeder->run();
        $this->productCatalogSeeder->run();
        $this->salesOrderSeeder->run();
        $this->salesOrderLinesSeeder->run();
        $this->purchaseOrderSeeder->run();
        $this->purchaseOrderLinesSeeder->run();
    }
}
