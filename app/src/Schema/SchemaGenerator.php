<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Schema;

/**
 * Schema and Translation Generator for CRUD6 Tests.
 * 
 * Generates schema JSON files and corresponding locale translations
 * for CRUD6 testing. This replaces the need to copy schemas from
 * the examples folder during CI.
 * 
 * Usage:
 * ```php
 * GenerateSchemas::generate();
 * ```
 * 
 * This creates:
 * - app/schema/crud6/*.json - Schema files
 * - app/locale/en_US/messages.php - Translation keys
 */
class SchemaGenerator
{
    /**
     * Default base directory for schema files.
     */
    private const DEFAULT_SCHEMA_DIR = __DIR__ . '/../../schema/crud6';

    /**
     * Default base directory for locale files.
     */
    private const DEFAULT_LOCALE_DIR = __DIR__ . '/../../locale/en_US';

    /**
     * Generate all schemas and translations using default paths.
     * 
     * @return void
     */
    public static function generate(): void
    {
        self::generateToPath(self::DEFAULT_SCHEMA_DIR, self::DEFAULT_LOCALE_DIR);
    }

    /**
     * Generate schemas and translations to custom paths.
     * 
     * @param string $schemaDir Path to schema directory
     * @param string $localeDir Path to locale directory
     * 
     * @return void
     */
    public static function generateToPath(string $schemaDir, string $localeDir): void
    {
        echo "Generating CRUD6 Schemas and Translations...\n";

        // Create directories if they don't exist
        self::createDirectories($schemaDir, $localeDir);

        // Generate schemas
        $schemas = self::getSchemaDefinitions();
        self::generateSchemaFiles($schemas, $schemaDir);

        // Generate translations
        self::generateTranslations($schemas, $localeDir);

        echo "Schema and translation generation complete\n";
    }

    /**
     * Create required directories.
     * 
     * @param string $schemaDir Schema directory path
     * @param string $localeDir Locale directory path
     * 
     * @return void
     */
    private static function createDirectories(string $schemaDir, string $localeDir): void
    {
        if (!is_dir($schemaDir)) {
            mkdir($schemaDir, 0755, true);
        }

        if (!is_dir($localeDir)) {
            mkdir($localeDir, 0755, true);
        }
    }

    /**
     * Get all schema definitions to generate.
     * 
     * @return array Array of [name => callable] pairs
     */
    private static function getSchemaDefinitions(): array
    {
        return [
            'users' => fn() => SchemaBuilder::userSchema(),
            'groups' => fn() => SchemaBuilder::groupSchema(),
            'products' => fn() => SchemaBuilder::productSchema(),
            'roles' => fn() => self::rolesSchema(),
            'permissions' => fn() => self::permissionsSchema(),
            'activities' => fn() => self::activitiesSchema(),
        ];
    }

    /**
     * Generate schema JSON files.
     * 
     * @param array  $schemas   Schema definitions
     * @param string $schemaDir Schema directory path
     * 
     * @return void
     */
    private static function generateSchemaFiles(array $schemas, string $schemaDir): void
    {
        foreach ($schemas as $name => $schemaFn) {
            $schema = $schemaFn();
            $filename = $schemaDir . "/{$name}.json";
            
            $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($filename, $json);
        }
        
        echo "Generated " . count($schemas) . " schema files\n";
    }

    /**
     * Generate locale translations.
     * 
     * @param array  $schemas   Schema definitions
     * @param string $localeDir Locale directory path
     * 
     * @return void
     */
    private static function generateTranslations(array $schemas, string $localeDir): void
    {
        $filename = $localeDir . '/messages.php';
        
        // Load existing base translations if the file exists
        $baseTranslations = [];
        if (file_exists($filename)) {
            $baseTranslations = require $filename;
        }
        
        // Build schema-specific translations
        $schemaTranslations = self::buildTranslations($schemas);
        
        // Merge: base translations + schema translations
        $mergedTranslations = self::mergeTranslations($baseTranslations, $schemaTranslations);
        
        $content = self::generateTranslationFile($mergedTranslations);
        file_put_contents($filename, $content);
        
        echo "Generated translations file\n";
    }

    /**
     * Merge base translations with schema translations.
     * 
     * @param array $base   Base translations (existing messages.php)
     * @param array $schema Schema-specific translations
     * 
     * @return array Merged translations
     */
    private static function mergeTranslations(array $base, array $schema): array
    {
        if (empty($base)) {
            return $schema;
        }
        
        // Deep merge: base CRUD6 array + schema CRUD6 array
        if (isset($base['CRUD6']) && isset($schema['CRUD6'])) {
            $base['CRUD6'] = array_merge($base['CRUD6'], $schema['CRUD6']);
        } elseif (isset($schema['CRUD6'])) {
            $base['CRUD6'] = $schema['CRUD6'];
        }
        
        return $base;
    }

    /**
     * Build translation array from schemas.
     * 
     * @param array $schemas Schema definitions
     * 
     * @return array Translation array
     */
    private static function buildTranslations(array $schemas): array
    {
        $translations = ['CRUD6' => []];

        foreach ($schemas as $name => $schemaFn) {
            $schema = $schemaFn();
            $modelKey = strtoupper($schema['model']);
            
            $translations['CRUD6'][$modelKey] = self::getModelTranslations($schema);
        }

        return $translations;
    }

    /**
     * Get translations for a specific model.
     * 
     * @param array $schema Schema definition
     * 
     * @return array Model translations
     */
    private static function getModelTranslations(array $schema): array
    {
        $model = $schema['model'];
        $modelName = ucfirst($model);
        $modelNamePlural = $modelName . 's'; // Simple pluralization

        $trans = [
            1 => $modelName,
            2 => $modelNamePlural,
            'PAGE' => $modelNamePlural,
            'PAGE_DESCRIPTION' => "A listing of {$model} for your site",
        ];

        // Add field labels
        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            $key = strtoupper($fieldName);
            $label = ucwords(str_replace('_', ' ', $fieldName));
            $trans[$key] = $label;
        }

        return $trans;
    }

    /**
     * Generate PHP file content for translations.
     * 
     * @param array $translations Translation array
     * 
     * @return string PHP file content
     */
    private static function generateTranslationFile(array $translations): string
    {
        $export = var_export($translations, true);
        
        return <<<PHP
<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * US English message token translations for the 'crud6' sprinkle.
 * 
 * This file contains both:
 * 1. Base CRUD6 translations (manually maintained)
 * 2. Schema-specific model translations (auto-generated from schemas)
 * 
 * To regenerate schema translations: php scripts/generate-test-schemas.php
 * Note: Base translations are preserved during regeneration.
 *
 * @author Alexander Weissman
 */
return {$export};

PHP;
    }

    /**
     * Generate roles schema.
     * 
     * @return array Schema definition
     */
    private static function rolesSchema(): array
    {
        return SchemaBuilder::create('roles', 'roles')
            ->setTitleField('name')
            ->addPermissions([
                'read' => 'uri_roles',
                'create' => 'create_role',
                'update' => 'update_role_field',
                'delete' => 'delete_role',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true)
            ->addStringField('slug', required: true, unique: true, sortable: true, listable: true, maxLength: 255)
            ->addStringField('name', required: true, sortable: true, filterable: true, listable: true, maxLength: 255)
            ->addTextField('description', filterable: true, listable: true)
            ->addDateTimeField('created_at', readonly: true)
            ->addDateTimeField('updated_at', readonly: true)
            ->addDetail('permissions', 'role_id', ['slug', 'name', 'description'], 'CRUD6.ROLE.PERMISSIONS')
            ->build();
    }

    /**
     * Generate permissions schema.
     * 
     * @return array Schema definition
     */
    private static function permissionsSchema(): array
    {
        return SchemaBuilder::create('permissions', 'permissions')
            ->setTitleField('name')
            ->addPermissions([
                'read' => 'uri_permissions',
                'create' => 'create_permission',
                'update' => 'update_permission_field',
                'delete' => 'delete_permission',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true)
            ->addStringField('slug', required: true, unique: true, sortable: true, listable: true, maxLength: 255)
            ->addStringField('name', required: true, sortable: true, filterable: true, listable: true, maxLength: 255)
            ->addTextField('description', filterable: true, listable: true)
            ->addTextField('conditions', listable: false)
            ->addDateTimeField('created_at', readonly: true)
            ->addDateTimeField('updated_at', readonly: true)
            ->build();
    }

    /**
     * Generate activities schema.
     * 
     * @return array Schema definition
     */
    private static function activitiesSchema(): array
    {
        return SchemaBuilder::create('activities', 'activities')
            ->setTitleField('type')
            ->addPermissions([
                'read' => 'uri_activities',
                'create' => 'create_activity',
                'update' => 'update_activity_field',
                'delete' => 'delete_activity',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true)
            ->addIntegerField('user_id', required: true, sortable: true, listable: true)
            ->addStringField('type', required: true, sortable: true, filterable: true, listable: true, maxLength: 255)
            ->addDateTimeField('occurred_at', required: true, sortable: true, listable: true)
            ->addStringField('ip_address', sortable: true, listable: true, maxLength: 45)
            ->addTextField('description', filterable: true, listable: false)
            ->build();
    }
}
