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
 * Abstract base class for field type handlers.
 * 
 * Provides sensible defaults for common field type implementations.
 * Extend this class to create custom field types with minimal boilerplate.
 * 
 * @example
 * ```php
 * class PhoneFieldType extends AbstractFieldType
 * {
 *     public function getType(): string
 *     {
 *         return 'phone';
 *     }
 *     
 *     public function getPhpType(): string
 *     {
 *         return 'string';
 *     }
 *     
 *     public function getValidationRules(): array
 *     {
 *         return [
 *             'validators' => [
 *                 'regex' => [
 *                     'pattern' => '/^\d{3}-\d{3}-\d{4}$/'
 *                 ]
 *             ]
 *         ];
 *     }
 * }
 * ```
 */
abstract class AbstractFieldType implements FieldTypeInterface
{
    /**
     * {@inheritdoc}
     */
    abstract public function getType(): string;

    /**
     * {@inheritdoc}
     * 
     * Default implementation returns value as-is.
     * Override to implement custom transformation.
     */
    public function transform(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     * 
     * Default implementation returns value as-is.
     * Override to implement custom casting.
     */
    public function cast(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     * 
     * Default implementation returns 'string'.
     * Override to specify different PHP type.
     */
    public function getPhpType(): string
    {
        return 'string';
    }

    /**
     * {@inheritdoc}
     * 
     * Default implementation returns empty array (no validation).
     * Override to add default validation rules.
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     * 
     * Default implementation returns false (not virtual).
     * Override to return true for computed/virtual fields.
     */
    public function isVirtual(): bool
    {
        return false;
    }
}
