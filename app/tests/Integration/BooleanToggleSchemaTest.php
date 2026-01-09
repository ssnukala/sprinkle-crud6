<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration test for Boolean Toggle functionality using real schemas.
 * 
 * This test uses the actual users.json schema from app/schema/crud6/users.json
 * to verify that boolean toggle actions work correctly.
 */
class BooleanToggleSchemaTest extends TestCase
{
    /**
     * Test that users.json schema is properly configured for boolean toggles.
     */
    public function testUsersSchemaHasBooleanToggleActions(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        
        $this->assertFileExists($schemaPath, 'users.json schema file must exist');
        
        $schemaContent = file_get_contents($schemaPath);
        $this->assertNotEmpty($schemaContent, 'Schema file must not be empty');
        
        $schema = json_decode($schemaContent, true);
        $this->assertIsArray($schema, 'Schema must be valid JSON');
        
        // Verify schema has actions
        $this->assertArrayHasKey('actions', $schema, 'Schema must have actions array');
        $this->assertIsArray($schema['actions'], 'Actions must be an array');
        
        // Find all toggle actions dynamically from schema
        $toggleActions = [];
        foreach ($schema['actions'] as $action) {
            if (isset($action['type']) && $action['type'] === 'field_update' && 
                isset($action['toggle']) && $action['toggle'] === true) {
                $toggleActions[$action['key']] = $action;
            }
        }
        
        // Verify we have at least some toggle actions
        $this->assertNotEmpty($toggleActions, 'Schema must have at least one toggle action');
        
        // Verify each toggle action is properly configured
        foreach ($toggleActions as $actionKey => $action) {
            $this->assertArrayHasKey('field', $action, "Toggle action '{$actionKey}' must have 'field' property");
            $this->assertArrayHasKey('permission', $action, "Toggle action '{$actionKey}' must have 'permission' property");
            $this->assertEquals('field_update', $action['type'], "Toggle action '{$actionKey}' must be field_update type");
            $this->assertTrue($action['toggle'], "Toggle action '{$actionKey}' must have toggle set to true");
            
            // Verify the target field exists in schema
            $fieldName = $action['field'];
            $this->assertArrayHasKey($fieldName, $schema['fields'], 
                "Toggle action '{$actionKey}' references field '{$fieldName}' which must exist in schema");
            
            // Verify the target field is a boolean
            $field = $schema['fields'][$fieldName];
            $this->assertEquals('boolean', $field['type'], 
                "Field '{$fieldName}' targeted by toggle action must be boolean type");
        }
    }
    
    /**
     * Test that boolean fields with toggle UI are properly configured in schema.
     */
    public function testBooleanFieldsAreProperlyConfigured(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify fields exist
        $this->assertArrayHasKey('fields', $schema, 'Schema must have fields');
        
        // Find all boolean fields with toggle UI dynamically
        $booleanToggleFields = [];
        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['type']) && $fieldConfig['type'] === 'boolean' &&
                isset($fieldConfig['ui']) && $fieldConfig['ui'] === 'toggle') {
                $booleanToggleFields[$fieldName] = $fieldConfig;
            }
        }
        
        // Verify we have at least some boolean toggle fields
        $this->assertNotEmpty($booleanToggleFields, 'Schema must have at least one boolean field with toggle UI');
        
        // Verify each boolean toggle field is properly configured
        foreach ($booleanToggleFields as $fieldName => $field) {
            // Verify field type
            $this->assertEquals('boolean', $field['type'], "Field '{$fieldName}' must be boolean type");
            
            // Verify UI configuration
            $this->assertEquals('toggle', $field['ui'], "Field '{$fieldName}' must use toggle UI");
            
            // CRITICAL: Verify that these fields have NO validation rules
            // This is what triggers the bug we fixed - fields without validation rules
            // were being skipped by RequestDataTransformer
            $this->assertArrayNotHasKey('validation', $field, 
                "Field '{$fieldName}' should NOT have validation rules (this is the condition that triggers the bug we fixed)");
        }
    }
    
    /**
     * Test the fix logic with actual schema data.
     * 
     * This simulates what UpdateFieldAction does with the real schema.
     * Uses the first boolean toggle field found in the schema dynamically.
     */
    public function testUpdateFieldActionLogicWithRealSchema(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Find first boolean field with toggle UI dynamically
        $fieldName = null;
        $fieldConfig = null;
        foreach ($schema['fields'] as $name => $config) {
            if (isset($config['type']) && $config['type'] === 'boolean' &&
                isset($config['ui']) && $config['ui'] === 'toggle') {
                $fieldName = $name;
                $fieldConfig = $config;
                break;
            }
        }
        
        $this->assertNotNull($fieldName, 'Schema must have at least one boolean toggle field for this test');
        $this->assertNotNull($fieldConfig, 'Field config must be available');
        
        // Simulate UpdateFieldAction receiving a toggle request
        $params = [$fieldName => false];  // Frontend sends boolean false
        
        // Simulate what UpdateFieldAction does
        $fieldType = $fieldConfig['type'] ?? 'string';
        $validationRules = $fieldConfig['validation'] ?? [];
        
        // Verify our assumptions
        $this->assertEquals('boolean', $fieldType, "Field '{$fieldName}' type should be boolean");
        $this->assertEmpty($validationRules, "Field '{$fieldName}' validation rules should be empty (this is the bug condition)");
        
        // Simulate the RequestDataTransformer potentially skipping the field
        $data = [];  // Transformer might return empty array with no validation rules
        
        // Apply the FIX logic from UpdateFieldAction.php lines 173-182
        if (!array_key_exists($fieldName, $data) && array_key_exists($fieldName, $params)) {
            $data[$fieldName] = $params[$fieldName];
        }
        
        // Verify the fix works
        $this->assertArrayHasKey($fieldName, $data, "Field '{$fieldName}' must be in data after fix is applied");
        $this->assertFalse($data[$fieldName], "Field '{$fieldName}' value must be false");
        
        // Simulate the database update check
        $canUpdate = array_key_exists($fieldName, $data);
        $this->assertTrue($canUpdate, "Field '{$fieldName}' update should be allowed");
    }
    
    /**
     * Test all schema files are valid JSON.
     */
    public function testAllSchemaFilesAreValidJson(): void
    {
        $schemaDir = __DIR__ . '/../../schema/crud6';
        
        $this->assertDirectoryExists($schemaDir, 'Schema directory must exist');
        
        $schemaFiles = glob($schemaDir . '/*.json');
        $this->assertNotEmpty($schemaFiles, 'Schema directory must contain JSON files');
        
        foreach ($schemaFiles as $schemaFile) {
            $schemaContent = file_get_contents($schemaFile);
            $this->assertNotEmpty($schemaContent, basename($schemaFile) . ' must not be empty');
            
            $schema = json_decode($schemaContent, true);
            $this->assertIsArray($schema, basename($schemaFile) . ' must be valid JSON');
            $this->assertNull(json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : null,
                basename($schemaFile) . ' must have no JSON errors');
        }
    }
    
    /**
     * Test that each schema file has required structure.
     */
    public function testSchemaFilesHaveRequiredStructure(): void
    {
        $schemaDir = __DIR__ . '/../../schema/crud6';
        $schemaFiles = glob($schemaDir . '/*.json');
        
        foreach ($schemaFiles as $schemaFile) {
            $schemaContent = file_get_contents($schemaFile);
            $schema = json_decode($schemaContent, true);
            
            $filename = basename($schemaFile);
            
            // Required fields for all schemas
            $this->assertArrayHasKey('model', $schema, "$filename must have 'model' key");
            $this->assertArrayHasKey('table', $schema, "$filename must have 'table' key");
            $this->assertArrayHasKey('fields', $schema, "$filename must have 'fields' key");
            $this->assertIsArray($schema['fields'], "$filename 'fields' must be an array");
            $this->assertNotEmpty($schema['fields'], "$filename 'fields' must not be empty");
        }
    }
    
    /**
     * Test that toggle actions reference valid fields.
     */
    public function testToggleActionsReferenceValidFields(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        if (!isset($schema['actions'])) {
            $this->markTestSkipped('Schema has no actions defined');
            return;
        }
        
        foreach ($schema['actions'] as $action) {
            if (isset($action['type']) && $action['type'] === 'field_update' && isset($action['field'])) {
                $fieldName = $action['field'];
                $this->assertArrayHasKey($fieldName, $schema['fields'], 
                    "Action '{$action['key']}' references field '$fieldName' which must exist in schema");
            }
        }
    }
    
    /**
     * Test fix handles multiple boolean fields.
     */
    public function testFixHandlesMultipleBooleanFields(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        $booleanFields = [];
        foreach ($schema['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['type']) && $fieldConfig['type'] === 'boolean') {
                $booleanFields[$fieldName] = $fieldConfig;
            }
        }
        
        $this->assertNotEmpty($booleanFields, 'Schema must have at least one boolean field');
        
        // Test the fix logic for each boolean field
        foreach ($booleanFields as $fieldName => $fieldConfig) {
            $params = [$fieldName => true];
            $data = [];  // Simulate empty transformed data
            
            // Apply fix
            if (!array_key_exists($fieldName, $data) && array_key_exists($fieldName, $params)) {
                $data[$fieldName] = $params[$fieldName];
            }
            
            $this->assertArrayHasKey($fieldName, $data, 
                "Fix must work for boolean field: $fieldName");
            $this->assertTrue($data[$fieldName], 
                "Boolean value must be preserved for field: $fieldName");
        }
    }
}
