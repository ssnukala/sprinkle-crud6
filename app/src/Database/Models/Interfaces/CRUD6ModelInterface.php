<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces;

/**
 * CRUD6ModelInterface
 *
 * Interface for generic CRUD6 models that can be dynamically configured
 * to work with any database table based on JSON schema configuration.
 */
interface CRUD6ModelInterface
{
    /**
     * Configure the model with schema information
     *
     * @param array $schema The JSON schema configuration
     * @return static
     */
    public function configureFromSchema(array $schema): static;

    /**
     * Set the table name for this model instance
     *
     * @param string $table
     * @return static
     */
    public function setTable(string $table): static;

    /**
     * Set the fillable attributes
     *
     * @param array $fillable
     * @return static
     */
    public function setFillable(array $fillable): static;

    /**
     * Get the fillable attributes
     *
     * @return array
     */
    public function getFillable(): array;

    /**
     * Get the schema configuration
     *
     * @return array
     */
    public function getSchema(): array;

    /**
     * Soft delete this model instance
     *
     * @return bool
     */
    public function softDelete(): bool;

    /**
     * Restore a soft deleted model instance
     *
     * @return bool
     */
    public function restore(): bool;

    /**
     * Check if this model instance is soft deleted
     *
     * @return bool
     */
    public function isSoftDeleted(): bool;
}