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
 * Migration for Product Categories Junction Table
 * 
 * This migration creates the product_categories table for the c6test pages.
 * The table structure matches the product_categories.json schema.
 */
class CreateProductCategoriesTable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public static array $dependencies = [
        CreateProductsTable::class,
        CreateCategoriesTable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('product_categories')) {
            $this->schema->create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('category_id');
                $table->timestamp('created_at')->nullable();
                
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                
                $table->unique(['product_id', 'category_id']);
                $table->index(['product_id']);
                $table->index(['category_id']);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('product_categories');
    }
}
