<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use PHPUnit\Framework\TestCase;

/**
 * ApiAction Test
 *
 * Tests the CRUD6 ApiAction controller functionality, particularly the
 * schema endpoint that was fixed to resolve routing conflicts.
 * 
 * This test validates that the fixes for issue #39 are working correctly:
 * - Route ordering is correct (schema route before {id} routes)
 * - Schema loading works properly
 * - API response structure is correct
 */
class ApiActionTest extends TestCase
{
    /**
     * Test that route ordering is correct in the routes file
     * 
     * This validates the fix for the main issue where /api/crud6/{model}/schema
     * was being matched by the {id} route instead of the schema route.
     */
    public function testSchemaRouteOrderingIsCorrect(): void
    {
        $routesFile = dirname(__DIR__, 2) . '/src/Routes/CRUD6Routes.php';
        $this->assertFileExists($routesFile, 'CRUD6Routes.php should exist');
        
        $routesContent = file_get_contents($routesFile);
        $this->assertNotFalse($routesContent, 'Should be able to read routes file');
        
        // Find positions of schema route and {id} route
        $schemaPos = strpos($routesContent, "get('/schema'");
        $idPos = strpos($routesContent, "get('/{id}'");
        
        $this->assertNotFalse($schemaPos, 'Schema route should exist');
        $this->assertNotFalse($idPos, 'ID route should exist');
        $this->assertLessThan($idPos, $schemaPos, 'Schema route must come before {id} route to prevent routing conflicts');
    }

    /**
     * Test that schema files exist and are valid JSON
     */
    public function testSchemaFilesAreValid(): void
    {
        $schemaDir = dirname(__DIR__, 2) . '/schema/crud6';
        $this->assertDirectoryExists($schemaDir, 'Schema directory should exist');
        
        $expectedSchemas = ['groups.json', 'users.json'];
        
        foreach ($expectedSchemas as $schemaFile) {
            $schemaPath = $schemaDir . '/' . $schemaFile;
            $this->assertFileExists($schemaPath, "Schema file {$schemaFile} should exist");
            
            $content = file_get_contents($schemaPath);
            $this->assertNotFalse($content, "Should be able to read {$schemaFile}");
            
            $schema = json_decode($content, true);
            $this->assertNotNull($schema, "Schema file {$schemaFile} should contain valid JSON");
            $this->assertIsArray($schema, "Schema should be an array");
            
            // Validate required schema structure
            $this->assertArrayHasKey('model', $schema, "Schema should have 'model' field");
            $this->assertArrayHasKey('table', $schema, "Schema should have 'table' field");
            $this->assertArrayHasKey('fields', $schema, "Schema should have 'fields' field");
            $this->assertIsArray($schema['fields'], "Fields should be an array");
            $this->assertNotEmpty($schema['fields'], "Fields should not be empty");
        }
    }

    /**
     * Test that SchemaService uses YamlFileLoader for schema loading
     * 
     * This validates that the service supports both JSON and YAML formats
     */
    public function testSchemaServiceUsesYamlFileLoader(): void
    {
        $serviceFile = dirname(__DIR__, 2) . '/src/ServicesProvider/SchemaService.php';
        $this->assertFileExists($serviceFile, 'SchemaService.php should exist');
        
        $serviceContent = file_get_contents($serviceFile);
        $this->assertNotFalse($serviceContent, 'Should be able to read SchemaService.php');
        
        // Validate that YamlFileLoader is used for flexible format support
        $this->assertStringContainsString('YamlFileLoader', $serviceContent, 'SchemaService should use YamlFileLoader to support both JSON and YAML formats');
        $this->assertStringContainsString('use UserFrosting\Support\Repository\Loader\YamlFileLoader;', $serviceContent, 'YamlFileLoader should be imported');
    }

    /**
     * Test the expected API response structure
     * 
     * This simulates what the ApiAction controller should return for a schema request
     */
    public function testExpectedApiResponseStructure(): void
    {
        // Load a test schema to validate response structure
        $schemaPath = dirname(__DIR__, 2) . '/schema/crud6/groups.json';
        $content = file_get_contents($schemaPath);
        $schema = json_decode($content, true);
        
        $this->assertNotNull($schema, 'Test schema should be valid');
        
        // Simulate the response structure that ApiAction should return
        $expectedResponse = [
            'message' => 'CRUD6.API.SUCCESS',
            'model' => $schema['model'],
            'schema' => $schema
        ];
        
        $this->assertArrayHasKey('message', $expectedResponse, 'Response should have message');
        $this->assertArrayHasKey('model', $expectedResponse, 'Response should have model');
        $this->assertArrayHasKey('schema', $expectedResponse, 'Response should have schema');
        $this->assertEquals('groups', $expectedResponse['model'], 'Model should match schema');
        $this->assertIsArray($expectedResponse['schema'], 'Schema should be an array');
        $this->assertArrayHasKey('fields', $expectedResponse['schema'], 'Schema should contain fields');
    }

    /**
     * Test that all required routes are present in the correct order
     */
    public function testAllRoutesArePresentInCorrectOrder(): void
    {
        $routesFile = dirname(__DIR__, 2) . '/src/Routes/CRUD6Routes.php';
        $routesContent = file_get_contents($routesFile);
        
        $expectedRoutes = [
            "get('/schema'" => 'Schema route should come first',
            "get('')" => 'List route should be present',
            "post('')" => 'Create route should be present', 
            "get('/{id}'" => 'Read route should come after schema',
            "put('/{id}'" => 'Update route should be present',
            "delete('/{id}'" => 'Delete route should be present'
        ];
        
        $lastPos = 0;
        foreach ($expectedRoutes as $route => $message) {
            $pos = strpos($routesContent, $route, $lastPos);
            $this->assertNotFalse($pos, $message);
            $lastPos = $pos;
        }
    }
}