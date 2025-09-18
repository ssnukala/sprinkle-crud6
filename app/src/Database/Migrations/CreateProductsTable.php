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
 * Example Migration for Products Table
 * 
 * This migration demonstrates creating a table that can be used
 * with the CRUD6 generic model system. The table structure matches
 * the products.json schema in the examples directory.
 */
class CreateProductsTable extends Migration
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
        if (!$this->schema->hasTable('products')) {
            $this->schema->create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sku')->unique();
                $table->decimal('price', 10, 2);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('category_id');
                $table->string('tags')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('launch_date')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->index(['is_active']);
                $table->index(['category_id']);
                $table->index(['launch_date']);
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('products');
    }
}