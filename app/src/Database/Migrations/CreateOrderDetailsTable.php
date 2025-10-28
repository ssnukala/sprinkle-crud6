<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Migrations;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Core\Database\Migration;

/**
 * Migration for Order Details Table
 * 
 * This migration creates the order_details table for the c6test pages.
 * The table structure matches the order_details.json schema.
 */
class CreateOrderDetailsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static array $dependencies = [
        CreateOrdersTable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('order_details')) {
            $this->schema->create('order_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->integer('line_number');
                $table->string('sku');
                $table->string('product_name');
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('line_total', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->index(['order_id']);
                $table->index(['sku']);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('order_details');
    }
}
