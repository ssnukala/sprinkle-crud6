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
 * 
 * This interface defines the contract for models that support dynamic
 * configuration from JSON schemas, enabling the CRUD6 system to work
 * with any database table without requiring pre-defined model classes.
 * 
 * ## Key Capabilities
 * 
 * - **Dynamic Configuration**: Configure model at runtime from schema
 * - **Flexible Table Assignment**: Work with any database table
 * - **Mass Assignment Control**: Manage fillable attributes programmatically
 * - **Type Casting**: Configure field type casting from schema
 * - **Soft Delete Support**: Built-in soft delete operations
 * - **Multi-Database**: Support for multiple database connections
 * 
 * ## Implementation Notes
 * 
 * The primary implementation of this interface is {@see \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model},
 * which extends Eloquent's Model class and adds dynamic schema-based configuration.
 * 
 * @see \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
 * @see \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService
 */
interface CRUD6ModelInterface
{
    /**
     * Configure the model with schema information.
     * 
     * This is the primary method for setting up a CRUD6 model from a JSON schema.
     * It configures:
     * - Table name
     * - Database connection
     * - Fillable attributes (mass assignment protection)
     * - Field type casting
     * - Soft delete settings
     * - Timestamp management
     * - Dynamic relationships
     *
     * @param array $schema The JSON schema configuration array
     * 
     * @return static The configured model instance (fluent interface)
     */
    public function configureFromSchema(array $schema): static;

    /**
     * Set the table name for this model instance.
     * 
     * Allows the model to work with any database table by setting
     * the table name at runtime.
     *
     * @param string $table The database table name
     * 
     * @return static The model instance (fluent interface)
     */
    public function setTable(string $table): static;

    /**
     * Set the fillable attributes for mass assignment protection.
     * 
     * Defines which attributes can be mass-assigned using create()
     * or fill() methods, following Eloquent's mass assignment protection.
     *
     * @param array $fillable Array of fillable attribute names
     * 
     * @return static The model instance (fluent interface)
     */
    public function setFillable(array $fillable): static;

    /**
     * Get the fillable attributes.
     * 
     * Returns the list of attributes that are allowed for mass assignment.
     * This is used by Eloquent's mass assignment protection system.
     *
     * @return array Array of fillable attribute names
     */
    public function getFillable(): array;

    /**
     * Soft delete this model instance.
     * 
     * Sets the deleted_at timestamp without actually removing the record
     * from the database. Only works if soft deletes are enabled in the schema.
     *
     * @return bool True if successful, false otherwise
     */
    public function softDelete(): bool;

    /**
     * Restore a soft deleted model instance.
     * 
     * Clears the deleted_at timestamp to restore a soft-deleted record.
     * Only works if soft deletes are enabled in the schema.
     *
     * @return bool True if successful, false otherwise
     */
    public function restore(): bool;

    /**
     * Check if this model instance is soft deleted.
     * 
     * Returns true if the model has a non-null deleted_at timestamp.
     * Returns false if soft deletes are not enabled or record is not deleted.
     *
     * @return bool True if soft deleted, false otherwise
     */
    public function isSoftDeleted(): bool;

    /**
     * Set the cast attributes for type conversion.
     * 
     * Configures how Eloquent should cast attribute values when reading
     * from and writing to the database (e.g., 'price' => 'decimal').
     *
     * @param array $casts Array mapping attribute names to cast types
     * 
     * @return static The model instance (fluent interface)
     */
    public function setCasts(array $casts): static;

    /**
     * Set the database connection for this model instance.
     * 
     * Allows the model to use a specific database connection other
     * than the default. Supports multi-database applications.
     *
     * @param string|null $connection The connection name, or null for default
     * 
     * @return static The model instance (fluent interface)
     */
    public function setConnection(?string $connection): static;
}
