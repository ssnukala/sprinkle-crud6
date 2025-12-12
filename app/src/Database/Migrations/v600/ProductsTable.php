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
 * Products table migration
 * Creates the products table for product management
 */
class ProductsTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static $dependencies = [
        CategoriesTable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        if ($this->schema->hasTable('products')) {
            return;
        }

        $this->schema->create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('sku', 100)->unique();
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->unsignedInteger('category_id');
            $table->string('tags', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('launch_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('sku');
            $table->index('category_id');
            $table->index('is_active');
            
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('products');
    }
}
