<?php

declare(strict_types=1);

/*
 * CRUD6 Sprinkle
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Migrations\v600;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Core\Database\Migration;

/**
 * Orders table migration
 */
class OrdersTable extends Migration
{
    public static $dependencies = [];

    public function up(): void
    {
        if ($this->schema->hasTable('orders')) {
            return;
        }

        $this->schema->create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_number', 50)->unique();
            $table->unsignedInteger('customer_id')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status', 50)->default('pending');
            $table->date('order_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_date');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('orders');
    }
}
