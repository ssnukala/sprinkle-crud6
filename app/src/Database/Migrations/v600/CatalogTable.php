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
 * Catalog table migration
 * Version 6.0.0
 *
 * See https://laravel.com/docs/migrations
 * @extends Migration
 * @author Srinivas Nukala
 */
class CatalogTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('pr_catalog')) {
            $this->schema->create('pr_catalog', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('parent_id')->unsigned()->default(0);
                $table->string('name', 100);
                $table->string('description', 500);
                $table->string('slug', 100);
                $table->string('photo', 500)->nullable();
                $table->char('type', 2);
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
            });
        }
        // Permissions are now managed via CRUD6 schemas
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->drop('pr_catalog');
    }
}