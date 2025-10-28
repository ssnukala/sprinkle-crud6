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
 * Product-Catalog relationship table migration
 * Version 6.0.0
 *
 * See https://laravel.com/docs/migrations
 * @extends Migration
 * @author Srinivas Nukala
 */
class ProductCatalogTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('pr_product_catalog')) {
            $this->schema->create('pr_product_catalog', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->unsigned()->default(0);
                $table->integer('catalog_id')->unsigned()->default(0);
                $table->string('name', 200)->nullable();
                $table->text('description')->nullable();
                $table->string('slug', 100);
                $table->string('photo', 500)->nullable();
                $table->dateTime('active_date')->nullable();
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->string('notes', 500)->nullable();
                $table->json('meta')->nullable();
                $table->char('status', 1)->default('A');
                $table->string('created_by', 20)->nullable();
                $table->string('updated_by', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->index('product_id');
                $table->index('catalog_id');
            });
        }
        // Permissions are now managed via CRUD6 schemas
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->drop('pr_product_catalog');
    }
}