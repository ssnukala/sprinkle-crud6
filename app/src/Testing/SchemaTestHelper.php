<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

/**
 * SchemaTestHelper
 * 
 * Helper class for tests that need to handle both static and auto-generated schemas.
 * Provides methods to detect schema type and adjust test expectations accordingly.
 * 
 * **Schema Types:**
 * 
 * 1. **Static Schemas** (Default):
 *    - Hand-crafted from `examples/schema/`
 *    - Use semantic field types (email, password)
 *    - Have precise permission counts
 *    - Use both old (`listable`, `detail`) and new (`show_in`, `details`) structures
 * 
 * 2. **Auto-Generated Schemas**:
 *    - Generated via `php bakery crud6:generate` from database
 *    - Use database types (string for VARCHAR, not email)
 *    - Modern structure only (`show_in` arrays, `details` plural)
 *    - May have different permission counts based on tables scanned
 * 
 * **Usage in Tests:**
 * 
 * ```php
 * use UserFrosting\Sprinkle\CRUD6\Testing\SchemaTestHelper;
 * 
 * class MyTest extends TestCase
 * {
 *     public function testPermissions(): void
 *     {
 *         $role = Role::where('slug', 'crud6-admin')->first();
 *         
 *         if (SchemaTestHelper::isUsingGeneratedSchemas()) {
 *             // Auto-generated: May have more permissions from additional tables
 *             $this->assertGreaterThanOrEqual(24, $role->permissions->count());
 *         } else {
 *             // Static: Exact count from 4 example schemas
 *             $this->assertCount(24, $role->permissions->count());
 *         }
 *     }
 *     
 *     public function testSchemaStructure(): void
 *     {
 *         $schema = $this->getSchema('users');
 *         $field = $schema['fields']['email'];
 *         
 *         if (SchemaTestHelper::isUsingGeneratedSchemas()) {
 *             // Auto-generated uses database type
 *             $this->assertEquals('string', $field['type']);
 *             $this->assertTrue($field['validation']['email'] ?? false);
 *         } else {
 *             // Static uses semantic type
 *             $this->assertContains($field['type'], ['string', 'email']);
 *         }
 *     }
 * }
 * ```
 */
class SchemaTestHelper
{
    /**
     * Check if tests are using auto-generated schemas.
     * 
     * Tests use auto-generated schemas when GENERATE_TEST_SCHEMAS=1
     * environment variable is set. Otherwise, they use static schemas
     * from examples/schema/ directory.
     * 
     * @return bool True if using auto-generated schemas, false for static schemas
     */
    public static function isUsingGeneratedSchemas(): bool
    {
        return getenv('GENERATE_TEST_SCHEMAS') === '1';
    }
    
    /**
     * Get expected permission count for crud6-admin role.
     * 
     * Static schemas:
     * - 6 legacy CRUD6 permissions (crud6_*, delete_crud6_field, etc.)
     * - 20 schema-defined permissions from 5 example schemas (users, roles, groups, permissions, activities)
     * - Total: 26
     * 
     * Auto-generated schemas:
     * - May include additional tables or fields
     * - Permission count depends on tables scanned and their structure
     * - Minimum: 26 (could be more)
     * 
     * @return array{min: int, exact: int|null} Expected count range
     */
    public static function getExpectedPermissionCount(): array
    {
        if (self::isUsingGeneratedSchemas()) {
            return ['min' => 26, 'exact' => null]; // At least 26, exact count varies
        }
        
        return ['min' => 26, 'exact' => 26]; // Exactly 26 from static schemas
    }
    
    /**
     * Check if schema field should use modern structure.
     * 
     * Auto-generated schemas always use modern structure:
     * - show_in arrays instead of listable/editable booleans
     * - details (plural) instead of detail (singular)
     * 
     * Static schemas may use mixed structures for backward compatibility testing.
     * 
     * @param array $schema Schema array
     * @return bool True if using modern structure
     */
    public static function usesModernSchemaStructure(array $schema): bool
    {
        // Auto-generated schemas always use modern structure
        if (self::isUsingGeneratedSchemas()) {
            return true;
        }
        
        // Static schemas: Check if they have show_in fields (modern)
        // or listable fields (old structure)
        if (!empty($schema['fields'])) {
            $firstField = reset($schema['fields']);
            return isset($firstField['show_in']) && !isset($firstField['listable']);
        }
        
        return false;
    }
    
    /**
     * Get expected field type for email columns.
     * 
     * Static schemas: Use semantic 'email' type
     * Auto-generated schemas: Use database type 'string' with email validation
     * 
     * @return array{type: string|array, hasValidation: bool}
     */
    public static function getExpectedEmailFieldType(): array
    {
        if (self::isUsingGeneratedSchemas()) {
            return [
                'type' => 'string',           // From database VARCHAR type
                'hasValidation' => true       // Should have email validation rule
            ];
        }
        
        return [
            'type' => ['string', 'email'],   // Static schemas may use either
            'hasValidation' => true
        ];
    }
    
    /**
     * Assert permission count based on schema type.
     * 
     * Helper method that automatically uses the correct assertion
     * based on whether static or auto-generated schemas are in use.
     * 
     * @param \PHPUnit\Framework\TestCase $test Test case instance
     * @param \Illuminate\Support\Collection $permissions Permissions collection
     * @param string $message Assertion message
     */
    public static function assertPermissionCount($test, $permissions, string $message = ''): void
    {
        $expected = self::getExpectedPermissionCount();
        
        if ($expected['exact'] !== null) {
            // Static schemas: exact count
            $test->assertCount(
                $expected['exact'],
                $permissions,
                $message ?: "Static schemas: Expected exactly {$expected['exact']} permissions"
            );
        } else {
            // Auto-generated schemas: minimum count
            $test->assertGreaterThanOrEqual(
                $expected['min'],
                $permissions->count(),
                $message ?: "Auto-generated schemas: Expected at least {$expected['min']} permissions"
            );
        }
    }
    
    /**
     * Assert schema has expected structure (modern or legacy).
     * 
     * @param \PHPUnit\Framework\TestCase $test Test case instance
     * @param array $schema Schema array
     * @param string $fieldName Field name to check
     */
    public static function assertSchemaStructure($test, array $schema, string $fieldName): void
    {
        $test->assertArrayHasKey('fields', $schema);
        $test->assertArrayHasKey($fieldName, $schema['fields']);
        
        $field = $schema['fields'][$fieldName];
        
        if (self::usesModernSchemaStructure($schema)) {
            // Modern structure: show_in arrays
            $test->assertArrayHasKey('show_in', $field,
                'Modern schemas should have show_in array');
            $test->assertIsArray($field['show_in']);
        } else {
            // May have legacy listable/editable OR modern show_in
            $hasModern = isset($field['show_in']);
            $hasLegacy = isset($field['listable']) || isset($field['editable']);
            
            $test->assertTrue($hasModern || $hasLegacy,
                'Schema field should have either show_in (modern) or listable/editable (legacy)');
        }
    }
}
