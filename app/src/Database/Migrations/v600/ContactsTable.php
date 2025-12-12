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
 * Contacts table migration
 */
class ContactsTable extends Migration
{
    public static $dependencies = [];

    public function up(): void
    {
        if ($this->schema->hasTable('contacts')) {
            return;
        }

        $this->schema->create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 254)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->text('notes')->nullable();
            $table->text('bio')->nullable();
            $table->string('company', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('newsletter_opt_in')->default(false);
            $table->timestamps();

            $table->index('last_name');
            $table->index('first_name');
            $table->index('email');
            $table->index('company');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('contacts');
    }
}
