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
     * IMPORTANT: If the translation template contains placeholders {{variable}},
     * the original translation KEY is returned instead of the translated value.
     * This allows the frontend to translate with proper record context data.
     * 
     * UserFrosting 6 Translator Pattern:
     * - Passing empty array [] to translate() preserves {{placeholder}} syntax
     * - This is the official UserFrosting 6 pattern for getting raw templates
     * - See: https://github.com/userfrosting/framework/blob/6.0/src/I18n/Translator.php
     * 
     * @param string $value The value to potentially translate
     * 
     * @return string The translation KEY if template has placeholders, translated value otherwise
     */
    protected function translateValue(string $value): string
    {
        // Check if value looks like a translation key
        // Translation keys: contain uppercase letters, dots, underscores, numbers
        // Must contain at least one dot to distinguish from plain text
        if (preg_match('/^[A-Z][A-Z0-9_.]+\.[A-Z0-9_.]+$/', $value)) {
            // CRITICAL: Get the raw translation template WITHOUT interpolating placeholders
            // Try without any parameters first to see if that preserves placeholders better
            $template = $this->translator->translate($value);
            
            // Log what we got back to diagnose the issue
            $this->debugLog("[CRUD6 SchemaTranslator] Translate called", [
                'key' => $value,
                'template_result' => $template,
                'template_equals_key' => $template === $value,
                'has_double_curly_braces' => preg_match('/\{\{[^}]+\}\}/', $template) === 1,
                'has_double_spaces' => preg_match('/\s{2,}/', $template) === 1,
                'has_empty_parens' => preg_match('/\(\s*\)/', $template) === 1,
            ]);
            
            // If translation returns the same key, the key doesn't exist
            if ($template === $value) {
                $this->debugLog("[CRUD6 SchemaTranslator] Translation key not found - returning key as-is", [
                    'key' => $value,
                ]);
                return $template;
            }
            
            // Check if the template contains {{placeholder}} syntax
            // If it does, return the KEY for frontend to translate with record context
            if (preg_match('/\{\{[^}]+\}\}/', $template)) {
                $this->debugLog("[CRUD6 SchemaTranslator] Template has {{placeholders}} - returning KEY for frontend", [
                    'key' => $value,
                    'template' => $template,
                ]);
                
                // Return the translation KEY for frontend to translate with proper context
                return $value;
            }
            
            // Check if template has signs of empty placeholder interpolation
            // Double spaces or empty parentheses indicate placeholders were replaced with nothing
            if (preg_match('/\s{2,}|\(\s*\)/', $template)) {
                $this->debugLog("[CRUD6 SchemaTranslator] Template has empty interpolation patterns - returning KEY for frontend", [
                    'key' => $value,
                    'template' => $template,
                    'pattern' => 'double_spaces_or_empty_parens',
                ]);
                
                // Return the translation KEY for frontend to translate with proper context
                return $value;
            }
            
            // Template looks clean - safe to use backend translation
            $this->debugLog("[CRUD6 SchemaTranslator] Translation successful - using backend translation", [
                'key' => $value,
                'translated' => $template,
            ]);
            
            return $template;
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
