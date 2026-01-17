<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\FieldTypes;

/**
 * Interface for custom field type handlers.
 * 
 * Implement this interface to create custom field type handlers that can be
 * registered with the FieldTypeRegistry. Field type handlers are responsible
 * for transforming values between PHP/database formats and the frontend,
 * as well as providing validation and casting rules.
 * 
 * @example
 * ```php
 * class CurrencyFieldType implements FieldTypeInterface
 * {
 *     public function getType(): string
 *     {
 *         return 'currency';
 *     }
 *     
 *     public function transform(mixed $value): mixed
 *     {
 *         // Store as integer cents
 *         return (int) ($value * 100);
 *     }
 *     
 *     public function cast(mixed $value): mixed
 *     {
 *         // Cast to float dollars
 *         return (float) ($value / 100);
 *     }
 * }
 * ```
 */
interface FieldTypeInterface
{
    /**
     * Get the field type name.
     * 
     * This should match the 'type' value in schema field definitions.
     * 
     * @return string The field type name (e.g., 'currency', 'phone', 'custom_date')
     */
    public function getType(): string;

    /**
     * Transform a value for database storage.
     * 
     * Called before saving to the database. Converts the value from the
     * format received from the frontend to the format stored in the database.
     * 
     * @param mixed $value The input value from request
     * 
     * @return mixed The transformed value for database storage
     */
    public function transform(mixed $value): mixed;

    /**
     * Cast a value from the database for output.
     * 
     * Called when reading from the database. Converts the stored value
     * to the format expected by the frontend.
     * 
     * @param mixed $value The value from database
     * 
     * @return mixed The cast value for output
     */
    public function cast(mixed $value): mixed;

    /**
     * Get the PHP type for type casting.
     * 
     * Used by Eloquent model for automatic casting.
     * 
     * @return string PHP type ('string', 'int', 'float', 'bool', 'array', 'datetime', etc.)
     */
    public function getPhpType(): string;

    /**
     * Get validation rules for this field type.
     * 
     * Returns an array of default validation rules that should be applied
     * to fields of this type. These can be overridden in the schema.
     * 
     * @return array<string, mixed> Validation rules in UserFrosting Fortress format
     */
    public function getValidationRules(): array;

    /**
     * Check if this field type should be excluded from database operations.
     * 
     * Virtual fields like 'multiselect' are used for UI but don't map
     * to actual database columns.
     * 
     * @return bool True if field is virtual/computed
     */
    public function isVirtual(): bool;
}
