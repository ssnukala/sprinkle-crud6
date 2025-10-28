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
 * Sales Order table migration
 * Version 6.0.0
 *
 * See https://laravel.com/docs/migrations
 * @extends Migration
 * @author Srinivas Nukala
 */
class SalesOrderTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('or_sales_order')) {
            $this->schema->create('or_sales_order', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('year')->nullable();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->string('order_number', 100)->nullable();
                $table->string('contract_number', 100)->nullable();
                $table->string('order_status', 50)->nullable();
                $table->string('type', 50)->nullable();
                $table->integer('parent_id')->unsigned()->nullable();
                $table->integer('user_id')->unsigned();
                $table->integer('approver_id')->unsigned()->nullable();
                $table->dateTime('order_date')->nullable();
                $table->dateTime('expiry_date')->nullable();
                $table->decimal('net_amount', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('epay_commission', 10, 2)->default(0.00);
                $table->decimal('gross_amount', 10, 2)->default(0.00);
                $table->string('payment_type', 50)->nullable();
                $table->string('payment_ref', 100)->nullable();
                $table->string('payment_link', 255)->nullable();
                $table->dateTime('payment_date')->nullable();
                $table->text('payment_note')->nullable();
                $table->text('notes')->nullable();
                $table->json('meta')->nullable();
                $table->string('status', 10)->default('A');
                $table->integer('created_by')->unsigned()->nullable();
                $table->integer('updated_by')->unsigned()->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->index('user_id');
                $table->index('order_status');
                $table->index('status');
            });
        }
        // Permissions are now managed via CRUD6 schemas
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->drop('or_sales_order');
    }
}
