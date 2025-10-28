<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600;

use UserFrosting\Sprinkle\Core\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Sales Order Lines table migration
 * Version 6.0.0
 *
 * See https://laravel.com/docs/migrations
 * @extends Migration
 * @author Srinivas Nukala
 */
class SalesOrderLinesTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('or_sales_order_lines')) {
            $this->schema->create('or_sales_order_lines', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('order_id')->unsigned();
                $table->integer('product_catalog_id')->unsigned()->nullable();
                $table->integer('ref_id1')->unsigned()->nullable();
                $table->integer('ref_id2')->unsigned()->nullable();
                $table->integer('line_no');
                $table->string('type', 50)->nullable();
                $table->text('description');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('quantity', 10, 2);
                $table->decimal('net_amount', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('gross_amount', 10, 2)->default(0.00);
                $table->decimal('balance_amount', 10, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->string('status', 10)->default('A');
                $table->integer('created_by')->unsigned()->nullable();
                $table->integer('updated_by')->unsigned()->nullable();
                $table->timestamps();
                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->index('order_id');
                $table->index('product_catalog_id');
            });
        }
        // Permissions are now managed via CRUD6 schemas
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->drop('or_sales_order_lines');
    }
}
