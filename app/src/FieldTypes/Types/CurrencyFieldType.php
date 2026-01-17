<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
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
     * Transform from dollars (float/string) to cents (int) for storage.
     * 
     * Uses string manipulation to avoid floating-point precision issues.
     * The value is converted to a string, split on the decimal point,
     * and then reconstructed as an integer in cents.
     * 
     * @param mixed $value Dollar amount as float, int, or string
     * 
     * @return int Amount in cents
     */
    public function transform(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        
        // Convert to string for precise manipulation
        $stringValue = (string) $value;
        
        // Remove any non-numeric characters except decimal point and minus
        $stringValue = preg_replace('/[^0-9.\-]/', '', $stringValue);
        
        // Handle negative values
        $isNegative = str_starts_with($stringValue, '-');
        $stringValue = ltrim($stringValue, '-');
        
        // Split on decimal point
        $parts = explode('.', $stringValue);
        $dollars = (int) ($parts[0] ?? 0);
        
        // Handle cents with proper padding/truncation
        $centsStr = $parts[1] ?? '00';
        $centsStr = str_pad(substr($centsStr, 0, 2), 2, '0');
        $cents = (int) $centsStr;
        
        // Calculate total in cents
        $totalCents = ($dollars * 100) + $cents;
        
        return $isNegative ? -$totalCents : $totalCents;
    }

    /**
     * Cast from cents (int) to dollars (float) for display.
     * 
     * For display purposes, floating-point is acceptable since we're
     * outputting for the UI, not performing calculations.
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
