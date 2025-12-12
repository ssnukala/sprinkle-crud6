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
 * Tasks table migration
 */
class TasksTable extends Migration
{
    public static $dependencies = [];

    public function up(): void
    {
        if ($this->schema->hasTable('tasks')) {
            return;
        }

        $this->schema->create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('priority', 50)->default('medium');
            $table->unsignedInteger('assigned_to')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('tasks');
    }
}
