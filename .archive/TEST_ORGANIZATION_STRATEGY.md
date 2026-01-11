# Test Organization Strategy

## Current Issue
Integration tests are hardcoded to specific models (User, Role, Group) instead of being schema-driven:
- `FrontendUserWorkflowTest.php` - Hardcoded User workflows
- `CRUD6UsersIntegrationTest.php` - Hardcoded User CRUD tests
- `CRUD6GroupsIntegrationTest.php` - Hardcoded Group CRUD tests
- `RoleUsersRelationshipTest.php` - Hardcoded Role-User relationship tests

This violates CRUD6's core principle of being schema-driven.

## Better Approach: Schema-Driven Tests

### Example: SchemaBasedApiTest
`SchemaBasedApiTest.php` demonstrates the correct pattern:
- Reads schema files from `examples/schema/`
- Dynamically tests endpoints based on schema configuration
- No hardcoded model-specific logic
- Schema defines the test data and expected behavior

### Benefits of Schema-Driven Tests
1. **DRY Principle**: One test class tests all models
2. **Maintainable**: Add new models by adding schemas, not code
3. **Consistent**: Same test logic for all models
4. **Schema-Driven**: Aligns with CRUD6's architecture
5. **Scalable**: Easy to test custom schemas

## Recommended Changes

### 1. Consolidate Model-Specific Tests
Replace:
- `FrontendUserWorkflowTest.php`
- `CRUD6UsersIntegrationTest.php`
- `CRUD6GroupsIntegrationTest.php`

With:
- `SchemaBasedWorkflowTest.php` - Generic workflow tests driven by schema

### 2. Make Relationship Tests Generic
Replace:
- `RoleUsersRelationshipTest.php`

With:
- Add relationship tests to `SchemaBasedApiTest.php`
- Use schema's `relationships` array to drive tests

### 3. Test Structure

```php
class SchemaBasedApiTest extends CRUD6TestCase
{
    /**
     * @dataProvider schemaProvider
     */
    public function testCrudOperations(string $schemaFile): void
    {
        $schema = $this->loadSchema($schemaFile);
        $modelName = $schema['model'];
        
        // Test based on schema configuration
        $this->testListEndpoint($modelName, $schema);
        $this->testCreateEndpoint($modelName, $schema);
        $this->testReadEndpoint($modelName, $schema);
        $this->testUpdateEndpoint($modelName, $schema);
        $this->testDeleteEndpoint($modelName, $schema);
    }
    
    public function schemaProvider(): array
    {
        $schemas = glob('examples/schema/*.json');
        return array_map(fn($s) => [basename($s)], $schemas);
    }
}
```

### 4. Specific Test Cases
Keep specific tests only for:
- Bug reproductions (with comments explaining the bug)
- Edge cases not covered by schema
- Complex scenarios needing detailed setup

### 5. Migration Path
1. Fix immediate error in RoleUsersRelationshipTest (Collection → array)
2. Enhance SchemaBasedApiTest to cover more scenarios
3. Gradually migrate model-specific tests to schema-driven approach
4. Remove redundant tests once covered by schema-driven tests

## Implementation Priority
1. **Immediate**: Fix TypeError in RoleUsersRelationshipTest
2. **Short-term**: Enhance SchemaBasedApiTest coverage
3. **Long-term**: Refactor model-specific tests to be schema-driven

## Example Schema-Driven Test

```php
public function testRelationships(string $schemaFile): void
{
    $schema = $this->loadSchema($schemaFile);
    
    if (!isset($schema['relationships'])) {
        $this->markTestSkipped('No relationships defined');
    }
    
    foreach ($schema['relationships'] as $relationship) {
        $endpoint = "/api/crud6/{$schema['model']}/{id}/{$relationship['name']}";
        
        // Test based on relationship type
        match ($relationship['type']) {
            'has_many' => $this->testHasManyRelationship($endpoint, $relationship),
            'belongs_to_many' => $this->testBelongsToManyRelationship($endpoint, $relationship),
            'belongs_to' => $this->testBelongsToRelationship($endpoint, $relationship),
            default => null,
        };
    }
}
```

This approach ensures tests remain generic and schema-driven, aligning with CRUD6's architecture.
