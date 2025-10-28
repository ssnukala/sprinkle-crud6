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
 * Migration for Orders Table
 * 
 * This migration creates the orders table for the c6test pages.
 * The table structure matches the orders.json schema.
 */
class CreateOrdersTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static array $dependencies = [];

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('orders')) {
            $this->schema->create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->string('customer_name');
                $table->string('customer_email')->nullable();
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('payment_status')->default('pending');
                $table->date('order_date');
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index(['order_number']);
                $table->index(['customer_name']);
                $table->index(['payment_status']);
                $table->index(['order_date']);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('orders');
    }
}
