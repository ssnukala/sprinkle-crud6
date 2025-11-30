<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\FieldTypes;

/**
 * Registry for field type handlers.
 * 
 * Provides a centralized registry for field type handlers, enabling pluggable
 * field type support. Register custom field types to extend CRUD6's capabilities.
 * 
 * Built-in field types are registered automatically on instantiation.
 * 
 * @example
 * ```php
 * // Get the registry from DI container
 * $registry = $container->get(FieldTypeRegistry::class);
 * 
 * // Register a custom field type
 * $registry->register(new CurrencyFieldType());
 * 
 * // Transform a value using a field type
 * $storedValue = $registry->transform('currency', 99.99);  // Returns 9999 (cents)
 * 
 * // Cast a value using a field type
 * $displayValue = $registry->cast('currency', 9999);  // Returns 99.99 (dollars)
 * ```
 */
class FieldTypeRegistry
{
    /**
     * @var string Regex pattern for textarea type variants (e.g., "textarea-r5c60")
     */
    private const TEXTAREA_PATTERN = '/^(?:text|textarea)(?:-r\d+)?(?:c\d+)?$/';

    /**
     * @var array<string, FieldTypeInterface> Registered field type handlers
     */
    private array $types = [];

    /**
     * @var array<string, string> Default type to PHP type mapping for types without handlers
     */
    private static array $defaultPhpTypes = [
        'string' => 'string',
        'integer' => 'int',
        'int' => 'int',
        'float' => 'float',
        'decimal' => 'float',
        'boolean' => 'bool',
        'bool' => 'bool',
        'boolean-yn' => 'bool',
        'boolean-toggle' => 'bool',
        'boolean-tgl' => 'bool',
        'array' => 'array',
        'json' => 'array',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'email' => 'string',
        'url' => 'string',
        'phone' => 'string',
        'zip' => 'string',
        'text' => 'string',
        'textarea' => 'string',
        'password' => 'string',
        'smartlookup' => 'int',
        'multiselect' => 'array',
        'address' => 'string',
    ];

    /**
     * @var array<string> Virtual field types that don't map to database columns
     */
    private static array $virtualTypes = [
        'multiselect',
        'computed',
    ];

    /**
     * Constructor - registers built-in field types.
     */
    public function __construct()
    {
        // Built-in types can be registered here if needed
        // For now, we use the default mappings for simple types
    }

    /**
     * Register a custom field type handler.
     * 
     * @param FieldTypeInterface $handler The field type handler
     * 
     * @return void
     */
    public function register(FieldTypeInterface $handler): void
    {
        $this->types[$handler->getType()] = $handler;
    }

    /**
     * Check if a field type has a registered handler.
     * 
     * @param string $type The field type name
     * 
     * @return bool True if type has a handler
     */
    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * Get a field type handler.
     * 
     * @param string $type The field type name
     * 
     * @return FieldTypeInterface|null The handler or null if not found
     */
    public function get(string $type): ?FieldTypeInterface
    {
        return $this->types[$type] ?? null;
    }

    /**
     * Transform a value for database storage.
     * 
     * Uses the registered handler if available, otherwise applies default transformation.
     * 
     * @param string $type  The field type
     * @param mixed  $value The value to transform
     * 
     * @return mixed The transformed value
     */
    public function transform(string $type, mixed $value): mixed
    {
        // Use handler if available
        if (isset($this->types[$type])) {
            return $this->types[$type]->transform($value);
        }

        // Handle textarea with row/column specification (e.g., "textarea-r5c60")
        if (preg_match(self::TEXTAREA_PATTERN, $type)) {
            return (string) $value;
        }

        // Default transformations
        return $this->applyDefaultTransform($type, $value);
    }

    /**
     * Cast a value from database for output.
     * 
     * Uses the registered handler if available, otherwise applies default casting.
     * 
     * @param string $type  The field type
     * @param mixed  $value The value to cast
     * 
     * @return mixed The cast value
     */
    public function cast(string $type, mixed $value): mixed
    {
        // Use handler if available
        if (isset($this->types[$type])) {
            return $this->types[$type]->cast($value);
        }

        // Default: return as-is (most types don't need special casting)
        return $value;
    }

    /**
     * Get the PHP type for a field type.
     * 
     * @param string $type The field type
     * 
     * @return string PHP type string
     */
    public function getPhpType(string $type): string
    {
        // Use handler if available
        if (isset($this->types[$type])) {
            return $this->types[$type]->getPhpType();
        }

        // Normalize type for textarea variants
        if (preg_match(self::TEXTAREA_PATTERN, $type)) {
            return 'string';
        }

        return self::$defaultPhpTypes[$type] ?? 'string';
    }

    /**
     * Get validation rules for a field type.
     * 
     * @param string $type The field type
     * 
     * @return array<string, mixed> Validation rules
     */
    public function getValidationRules(string $type): array
    {
        // Use handler if available
        if (isset($this->types[$type])) {
            return $this->types[$type]->getValidationRules();
        }

        // Default validation rules for specific types
        return $this->getDefaultValidationRules($type);
    }

    /**
     * Check if a field type is virtual (not a database column).
     * 
     * @param string $type The field type
     * 
     * @return bool True if virtual
     */
    public function isVirtual(string $type): bool
    {
        // Use handler if available
        if (isset($this->types[$type])) {
            return $this->types[$type]->isVirtual();
        }

        return in_array($type, self::$virtualTypes, true);
    }

    /**
     * Get all registered field type names.
     * 
     * @return string[] List of registered type names
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * Get all known field types (registered + defaults).
     * 
     * @return string[] List of all known type names
     */
    public function getAllTypes(): array
    {
        return array_unique(array_merge(
            array_keys($this->types),
            array_keys(self::$defaultPhpTypes)
        ));
    }

    /**
     * Apply default value transformation.
     * 
     * @param string $type  The field type
     * @param mixed  $value The value to transform
     * 
     * @return mixed The transformed value
     */
    private function applyDefaultTransform(string $type, mixed $value): mixed
    {
        switch ($type) {
            case 'integer':
            case 'int':
                return (int) $value;
                
            case 'float':
            case 'decimal':
                return (float) $value;
                
            case 'boolean':
            case 'bool':
            case 'boolean-yn':
            case 'boolean-toggle':
            case 'boolean-tgl':
                return (bool) $value;
                
            case 'json':
            case 'array':
                // If already a string, check if it's valid JSON before returning
                if (is_string($value)) {
                    // Attempt to decode - if valid JSON, return as-is to avoid double-encoding
                    json_decode($value);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $value;
                    }
                    // If not valid JSON, encode the raw string
                    return json_encode($value);
                }
                // Encode non-string values (arrays, objects)
                return json_encode($value);
                
            case 'date':
            case 'datetime':
            case 'timestamp':
                return $value;
                
            case 'smartlookup':
                return $value !== null && $value !== '' ? (int) $value : null;
                
            default:
                return (string) $value;
        }
    }

    /**
     * Get default validation rules for a field type.
     * 
     * @param string $type The field type
     * 
     * @return array<string, mixed> Validation rules
     */
    private function getDefaultValidationRules(string $type): array
    {
        switch ($type) {
            case 'email':
                return [
                    'validators' => [
                        'email' => []
                    ]
                ];
                
            case 'url':
                return [
                    'validators' => [
                        'url' => []
                    ]
                ];
                
            case 'integer':
            case 'int':
                return [
                    'validators' => [
                        'integer' => []
                    ]
                ];
                
            case 'float':
            case 'decimal':
                return [
                    'validators' => [
                        'numeric' => []
                    ]
                ];
                
            default:
                return [];
        }
    }
}
