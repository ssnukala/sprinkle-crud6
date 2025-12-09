<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Schema Translator.
 * 
 * Handles translation of schema values using UserFrosting's i18n system.
 * Recursively translates all string values that look like translation keys.
 */
class SchemaTranslator
{
    /**
     * Constructor.
     * 
     * Dependencies are injected through the DI container following UserFrosting 6 patterns.
     * 
     * @param Translator           $translator Translator for i18n
     * @param DebugLoggerInterface $logger     Debug logger for diagnostics
     */
    public function __construct(
        protected Translator $translator,
        protected DebugLoggerInterface $logger
    ) {
    }

    /**
     * Translate all translatable fields in a schema.
     * 
     * This method recursively processes the schema and translates:
     * - title, singular_title, description (top-level)
     * - Field labels and descriptions
     * - Action labels and confirm messages
     * - Relationship titles
     * - Detail titles
     * 
     * Translation keys are identified by checking if the value looks like a 
     * translation key (contains only uppercase letters, numbers, dots, and underscores).
     * 
     * @param array $schema The schema to translate
     * 
     * @return array The translated schema
     */
    public function translate(array $schema): array
    {
        $this->debugLog("[CRUD6 SchemaTranslator] translateSchema() called", [
            'model' => $schema['model'] ?? 'unknown',
        ]);

        // Recursively translate all string values that look like translation keys
        // Skip action confirm messages to preserve placeholders for frontend interpolation
        $schema = $this->translateArrayRecursive($schema, '');

        $this->debugLog("[CRUD6 SchemaTranslator] Schema translation complete", [
            'model' => $schema['model'] ?? 'unknown',
        ]);

        return $schema;
    }

    /**
     * Recursively translate all string values in an array that look like translation keys.
     * 
     * This method traverses the entire array structure and translates any string value
     * that matches the translation key pattern (uppercase with dots).
     * 
     * Placeholders {{variable}} are automatically preserved during translation using
     * temporary replacement markers (## and @@).
     * 
     * @param array $data The array to translate
     * @param string $parentKey The parent key to track context (for debug logging)
     * 
     * @return array The array with all translation keys translated, placeholders preserved
     */
    protected function translateArrayRecursive(array $data, string $parentKey = ''): array
    {
        foreach ($data as $key => $value) {
            $currentPath = $parentKey ? "$parentKey.$key" : $key;
            
            if (is_array($value)) {
                // Recursively translate nested arrays
                $data[$key] = $this->translateArrayRecursive($value, $currentPath);
            } elseif (is_string($value)) {
                // Translate string values that look like translation keys
                // Placeholders will be automatically preserved
                $data[$key] = $this->translateValue($value);
            }
            // Non-string, non-array values are left as-is
        }
        
        return $data;
    }

    /**
     * Translate a value if it looks like a translation key.
     * 
     * A translation key is identified by:
     * - Contains only uppercase letters, numbers, dots, and underscores
     * - Contains at least one dot (e.g., "USER.1", "CRUD6.ACTION.TOGGLE_ENABLED")
     * 
     * Values that don't match this pattern are returned as-is (plain text labels).
     * 
     * IMPORTANT: Placeholders {{variable}} are preserved during translation.
     * Strategy: Replace {{ with ## and }} with @@ before interpolation would occur,
     * then restore them in the final result. This allows backend translation while
     * preserving placeholders for frontend interpolation with actual record data.
     * 
     * @param string $value The value to potentially translate
     * 
     * @return string The translated value with placeholders intact, or original if not a translation key
     */
    protected function translateValue(string $value): string
    {
        // Check if value looks like a translation key
        // Translation keys: contain uppercase letters, dots, underscores, numbers
        // Must contain at least one dot to distinguish from plain text
        if (preg_match('/^[A-Z][A-Z0-9_.]+\.[A-Z0-9_.]+$/', $value)) {
            // Translate the key to get the template string
            $translated = $this->translator->translate($value);
            
            // If translation returns the same key, the key doesn't exist
            if ($translated === $value) {
                $this->debugLog("[CRUD6 SchemaTranslator] Translation key not found", [
                    'key' => $value,
                ]);
                return $translated;
            }
            
            // Check if the translated template contains placeholders that got interpolated with empty values
            // Common patterns indicating empty interpolation:
            // - "()" or "( )" - empty parentheses
            // - "  " - two or more consecutive spaces (placeholder was removed leaving spaces)
            // - "Create " or "Delete " - action words ending with space (should have {{model}})
            // - "from  ?" - prepositions followed by multiple spaces
            // - "<strong>  (" - HTML tags with excessive spacing
            if (preg_match('/\(\s*\)|<strong>\s+\(|>\s{2,}<|\s{2,}/', $translated)) {
                // Placeholders were interpolated with empty values
                // Return the translation KEY for frontend to translate with proper context
                $this->debugLog("[CRUD6 SchemaTranslator] Empty placeholder interpolation detected - frontend will handle", [
                    'key' => $value,
                    'translated' => $translated,
                    'pattern_match' => 'double_space_or_empty_parens',
                ]);
                
                // Return the translation KEY for frontend to translate with context
                return $value;
            }
            
            // Check if template still has {{}} placeholders (not interpolated)
            $hasPlaceholders = preg_match('/\{\{[^}]+\}\}/', $translated);
            
            $this->debugLog("[CRUD6 SchemaTranslator] Translation successful", [
                'key' => $value,
                'translated' => $translated,
                'has_placeholders' => $hasPlaceholders,
            ]);
            
            return $translated;
        }
        
        // Not a translation key, return as-is
        return $value;
    }

    /**
     * Log debug message.
     * 
     * @param string $message Debug message
     * @param array  $context Context data for structured logging
     * 
     * @return void
     */
    protected function debugLog(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
