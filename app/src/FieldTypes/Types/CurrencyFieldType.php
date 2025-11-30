<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\FieldTypes\Types;

use UserFrosting\Sprinkle\CRUD6\FieldTypes\AbstractFieldType;

/**
 * Currency field type handler.
 * 
 * Stores currency values as integers (cents) in the database for precision,
 * but displays/accepts values as floats (dollars) in the UI.
 * 
 * Example schema usage:
 * ```json
 * {
 *     "price": {
 *         "type": "currency",
 *         "label": "Price",
 *         "validation": {
 *             "required": true
 *         }
 *     }
 * }
 * ```
 */
class CurrencyFieldType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'currency';
    }

    /**
     * Transform from dollars (float) to cents (int) for storage.
     * 
     * @param mixed $value Dollar amount as float or string
     * 
     * @return int Amount in cents
     */
    public function transform(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        // Convert to float, multiply by 100, round to avoid floating point issues
        return (int) round((float) $value * 100);
    }

    /**
     * Cast from cents (int) to dollars (float) for display.
     * 
     * @param mixed $value Amount in cents
     * 
     * @return float Dollar amount
     */
    public function cast(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        
        return (float) $value / 100;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpType(): string
    {
        return 'int';
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules(): array
    {
        return [
            'validators' => [
                'numeric' => []
            ]
        ];
    }
}
