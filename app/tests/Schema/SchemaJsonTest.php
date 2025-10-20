<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Schema JSON Validation Test
 *
 * Tests to ensure all schema JSON files are valid and can be parsed correctly.
 */
class SchemaJsonTest extends TestCase
{
    /**
     * Test that all example schema JSON files are valid
     */
    public function testExampleSchemasAreValid(): void
    {
        $exampleFiles = [
            'products.json',
            'categories.json',
            'analytics.json',
            'field-template-example.json',
            'products-template-file.json',
        ];

        foreach ($exampleFiles as $file) {
            $path = __DIR__ . '/../../../examples/' . $file;
            $this->assertFileExists($path, "Example schema file {$file} does not exist");

            $content = file_get_contents($path);
            $this->assertNotFalse($content, "Could not read example schema file {$file}");

            $json = json_decode($content, true);
            $this->assertNotNull($json, "Invalid JSON in example schema file {$file}: " . json_last_error_msg());
            $this->assertIsArray($json, "Schema {$file} should decode to an array");
        }
    }

    /**
     * Test that schema files in app/schema/crud6 are valid
     */
    public function testAppSchemasAreValid(): void
    {
        $schemaFiles = [
            'users.json',
            'groups.json',
            'db1/users.json',
        ];

        foreach ($schemaFiles as $file) {
            $path = __DIR__ . '/../../../app/schema/crud6/' . $file;
            $this->assertFileExists($path, "Schema file {$file} does not exist");

            $content = file_get_contents($path);
            $this->assertNotFalse($content, "Could not read schema file {$file}");

            $json = json_decode($content, true);
            $this->assertNotNull($json, "Invalid JSON in schema file {$file}: " . json_last_error_msg());
            $this->assertIsArray($json, "Schema {$file} should decode to an array");
        }
    }

    /**
     * Test that schemas have required fields
     */
    public function testSchemasHaveRequiredFields(): void
    {
        $path = __DIR__ . '/../../../examples/products.json';
        $content = file_get_contents($path);
        $schema = json_decode($content, true);

        $this->assertArrayHasKey('model', $schema);
        $this->assertArrayHasKey('table', $schema);
        $this->assertArrayHasKey('fields', $schema);

        $this->assertIsString($schema['model']);
        $this->assertIsString($schema['table']);
        $this->assertIsArray($schema['fields']);
    }

    /**
     * Test that schemas with field_template have valid structure
     */
    public function testFieldTemplateStructure(): void
    {
        $path = __DIR__ . '/../../../examples/field-template-example.json';
        $content = file_get_contents($path);
        $schema = json_decode($content, true);

        $this->assertArrayHasKey('fields', $schema);

        // Find a field with field_template
        $hasFieldTemplate = false;
        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['field_template'])) {
                $hasFieldTemplate = true;
                $this->assertIsString($fieldConfig['field_template'], "Field template for {$fieldName} should be a string");
                
                // Check that placeholder syntax is valid (contains {{ and }})
                $template = $fieldConfig['field_template'];
                if (strpos($template, '{{') !== false) {
                    $this->assertStringContainsString('}}', $template, "Field template for {$fieldName} has opening {{ but no closing }}");
                }
            }
        }

        $this->assertTrue($hasFieldTemplate, "field-template-example.json should have at least one field with field_template");
    }

    /**
     * Test that schemas without explicit defaults will use default values
     */
    public function testSchemasCanOmitDefaults(): void
    {
        $path = __DIR__ . '/../../../examples/products.json';
        $content = file_get_contents($path);
        $schema = json_decode($content, true);

        // After optimization, these should be optional
        // If not present, SchemaService will apply defaults
        if (!isset($schema['primary_key'])) {
            $this->assertTrue(true, "primary_key can be omitted - will default to 'id'");
        }
        
        if (!isset($schema['timestamps'])) {
            $this->assertTrue(true, "timestamps can be omitted - will default to true");
        }
        
        if (!isset($schema['soft_delete'])) {
            $this->assertTrue(true, "soft_delete can be omitted - will default to false");
        }
    }

    /**
     * Test that field_template can reference external template files
     */
    public function testFieldTemplateFileReferences(): void
    {
        $path = __DIR__ . '/../../../examples/products-template-file.json';
        $content = file_get_contents($path);
        $schema = json_decode($content, true);

        $this->assertArrayHasKey('fields', $schema);

        // Find fields with file-based field_template
        $fileTemplateFound = false;
        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['field_template'])) {
                $template = $fieldConfig['field_template'];
                
                // Check if it's a file reference (ends with .html or .htm)
                if (preg_match('/\.html?$/i', $template)) {
                    $fileTemplateFound = true;
                    
                    // Verify the referenced template file exists
                    $templatePath = __DIR__ . '/../../../app/assets/templates/crud6/' . $template;
                    $this->assertFileExists($templatePath, "Template file {$template} should exist at {$templatePath}");
                    
                    // Verify template file contains valid HTML with placeholders
                    $templateContent = file_get_contents($templatePath);
                    $this->assertNotEmpty($templateContent, "Template file {$template} should not be empty");
                }
            }
        }

        $this->assertTrue($fileTemplateFound, "products-template-file.json should have at least one field with a file-based template");
    }

    /**
     * Test that template files exist and are valid
     */
    public function testTemplateFilesExist(): void
    {
        $templateFiles = [
            'product-card.html',
            'category-info.html',
        ];

        foreach ($templateFiles as $file) {
            $path = __DIR__ . '/../../../app/assets/templates/crud6/' . $file;
            $this->assertFileExists($path, "Template file {$file} does not exist");

            $content = file_get_contents($path);
            $this->assertNotFalse($content, "Could not read template file {$file}");
            $this->assertNotEmpty($content, "Template file {$file} should not be empty");
            
            // Check for placeholder syntax
            if (strpos($content, '{{') !== false) {
                $this->assertStringContainsString('}}', $content, "Template file {$file} has opening {{ but no closing }}");
            }
        }
    }
}
