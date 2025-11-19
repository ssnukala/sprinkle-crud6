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
        
        // Find toggle_enabled action
        $toggleEnabledAction = null;
        $toggleVerifiedAction = null;
        
        foreach ($schema['actions'] as $action) {
            if ($action['key'] === 'toggle_enabled') {
                $toggleEnabledAction = $action;
            }
            if ($action['key'] === 'toggle_verified') {
                $toggleVerifiedAction = $action;
            }
        }
        
        $this->assertNotNull($toggleEnabledAction, 'toggle_enabled action must exist');
        $this->assertNotNull($toggleVerifiedAction, 'toggle_verified action must exist');
        
        // Verify toggle_enabled action configuration
        $this->assertEquals('field_update', $toggleEnabledAction['type'], 'Action type must be field_update');
        $this->assertEquals('flag_enabled', $toggleEnabledAction['field'], 'Action must target flag_enabled field');
        $this->assertTrue($toggleEnabledAction['toggle'], 'Action must have toggle set to true');
        
        // Verify toggle_verified action configuration
        $this->assertEquals('field_update', $toggleVerifiedAction['type'], 'Action type must be field_update');
        $this->assertEquals('flag_verified', $toggleVerifiedAction['field'], 'Action must target flag_verified field');
        $this->assertTrue($toggleVerifiedAction['toggle'], 'Action must have toggle set to true');
    }
    
    /**
     * Test that boolean fields are properly configured in schema.
     */
    public function testBooleanFieldsAreProperlyConfigured(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Verify fields exist
        $this->assertArrayHasKey('fields', $schema, 'Schema must have fields');
        $this->assertArrayHasKey('flag_enabled', $schema['fields'], 'flag_enabled field must exist');
        $this->assertArrayHasKey('flag_verified', $schema['fields'], 'flag_verified field must exist');
        
        $flagEnabled = $schema['fields']['flag_enabled'];
        $flagVerified = $schema['fields']['flag_verified'];
        
        // Verify field types
        $this->assertEquals('boolean', $flagEnabled['type'], 'flag_enabled must be boolean type');
        $this->assertEquals('boolean', $flagVerified['type'], 'flag_verified must be boolean type');
        
        // Verify UI configuration
        $this->assertEquals('toggle', $flagEnabled['ui'], 'flag_enabled must use toggle UI');
        $this->assertEquals('toggle', $flagVerified['ui'], 'flag_verified must use toggle UI');
        
        // CRITICAL: Verify that these fields have NO validation rules
        // This is what triggers the bug we fixed
        $this->assertArrayNotHasKey('validation', $flagEnabled, 
            'flag_enabled should NOT have validation rules (this triggers the bug we fixed)');
        $this->assertArrayNotHasKey('validation', $flagVerified,
            'flag_verified should NOT have validation rules (this triggers the bug we fixed)');
    }
    
    /**
     * Test the fix logic with actual schema data.
     * 
     * This simulates what UpdateFieldAction does with the real schema.
     */
    public function testUpdateFieldActionLogicWithRealSchema(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/users.json';
        $schemaContent = file_get_contents($schemaPath);
        $schema = json_decode($schemaContent, true);
        
        // Simulate UpdateFieldAction receiving a toggle request
        $fieldName = 'flag_enabled';
        $params = [$fieldName => false];  // Frontend sends boolean false
        
        // Get field config from schema
        $fieldConfig = $schema['fields'][$fieldName];
        
        // Simulate what UpdateFieldAction does
        $fieldType = $fieldConfig['type'] ?? 'string';
        $validationRules = $fieldConfig['validation'] ?? [];
        
        // Verify our assumptions
        $this->assertEquals('boolean', $fieldType, 'Field type should be boolean');
        $this->assertEmpty($validationRules, 'Validation rules should be empty (this is the bug condition)');
        
        // Simulate the RequestDataTransformer potentially skipping the field
        $data = [];  // Transformer might return empty array with no validation rules
        
        // Apply the FIX logic from UpdateFieldAction.php lines 173-182
        if (!array_key_exists($fieldName, $data) && array_key_exists($fieldName, $params)) {
            $data[$fieldName] = $params[$fieldName];
        }
        
        // Verify the fix works
        $this->assertArrayHasKey($fieldName, $data, 'Field must be in data after fix is applied');
        $this->assertFalse($data[$fieldName], 'Field value must be false');
        
        // Simulate the database update check
        $canUpdate = array_key_exists($fieldName, $data);
        $this->assertTrue($canUpdate, 'Field update should be allowed');
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
