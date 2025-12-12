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
 * Product Categories junction table migration
 * Many-to-many relationship between products and categories
 */
class ProductCategoriesTable extends Migration
{
    public static $dependencies = [
        ProductsTable::class,
        CategoriesTable::class,
    ];

    public function up(): void
    {
        if ($this->schema->hasTable('product_categories')) {
            return;
        }

        $this->schema->create('product_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('category_id');
            $table->timestamps();

            $table->unique(['product_id', 'category_id']);
            $table->index('product_id');
            $table->index('category_id');
            
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('product_categories');
    }
}
