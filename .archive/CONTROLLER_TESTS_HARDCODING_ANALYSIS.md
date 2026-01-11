# Controller Tests Hardcoding Analysis

## Problem Statement

All Controller tests in `app/tests/Controller/` are hardcoded to test specific models (primarily 'users'). This violates CRUD6's core principle of being schema-driven.

## Affected Files

1. **CreateActionTest.php** - Hardcoded to '/api/crud6/users'
2. **CustomActionTest.php** - Hardcoded to '/api/crud6/users/{id}/a/{action}'
3. **DeleteActionTest.php** - Hardcoded to '/api/crud6/users/{id}'
4. **EditActionTest.php** - Hardcoded to '/api/crud6/users/{id}'
5. **UpdateFieldActionTest.php** - Hardcoded to '/api/crud6/users/{id}/{field}'
6. **RelationshipActionTest.php** - Hardcoded to specific models
7. **SchemaActionTest.php** - Has some hardcoded 'users' assertions
8. **ListableFieldsTest.php** - Hardcoded 'users' model in schema mocks
9. **ConfigActionTest.php** - May have hardcoded references
10. **DebugModeTest.php** - May have hardcoded references

## Hardcoding Patterns

### Path Hardcoding
```php
$request = $this->createJsonRequest('PUT', "/api/crud6/users/{$testUser->id}/first_name", [...]);
$request = $this->createJsonRequest('POST', '/api/crud6/users', [...]);
$request = $this->createJsonRequest('DELETE', '/api/crud6/users/' . $testUser->id);
```

### Model Hardcoding
```php
/** @var User */
$testUser = User::factory()->create();
```

### Schema Hardcoding
```php
'model' => 'users',
'table' => 'users',
```

## Proposed Solution

### Option 1: Convert to Schema-Driven with Data Providers (RECOMMENDED)

Similar to what we did with Integration tests, convert Controller tests to use data providers:

```php
/**
 * @dataProvider schemaProvider
 */
public function testCreateRequiresAuthentication(string $modelName): void
{
    $schema = $this->schemaService->getSchema($modelName);
    $factory = $this->getModelFactory($schema);
    $testRecord = $factory->create();
    
    $request = $this->createJsonRequest('POST', "/api/crud6/{$modelName}", [
        // Generate test data from schema
    ]);
    $response = $this->handleRequestWithTracking($request);
    
    $this->assertResponseStatus(401, $response);
}

public static function schemaProvider(): array
{
    return [
        'users' => ['users'],
        'roles' => ['roles'],
        'groups' => ['groups'],
        // etc.
    ];
}
```

### Option 2: Create Schema-Based Controller Test Suite

Create a new `SchemaBasedControllerTest.php` that tests all controller actions for all schemas using data providers, and remove the individual controller test files.

### Option 3: Keep Existing Tests but Parameterize

Keep the existing test files but make them abstract/generic and use a schema parameter throughout.

## Recommendation

**Option 2** is recommended to align with the Integration tests approach. This would:

1. Create `app/tests/Controller/SchemaBasedControllerTest.php`
2. Implement test methods with `@dataProvider schemaProvider` 
3. Test all controller actions (Create, Delete, Edit, UpdateField, CustomAction, etc.) for ALL schemas
4. Remove the individual controller test files
5. Move any unique/specialized tests to the Integration test suite

## Benefits

✅ **100% Schema-Driven**: No hardcoded models or paths
✅ **Consistent Pattern**: Matches Integration test approach
✅ **Comprehensive Coverage**: Every schema tests all controller actions
✅ **Maintainable**: Add schema → all controller tests run automatically
✅ **Less Code**: Single test file instead of 10+ files
✅ **Aligned with CRUD6 Principles**: Everything driven from schemas

## Implementation Steps

1. Create `SchemaBasedControllerTest.php` with schema provider
2. Migrate test logic from individual files to new schema-driven tests
3. Remove hardcoded controller test files
4. Update documentation
5. Run tests to ensure coverage is maintained

## Files to Remove After Migration

- CreateActionTest.php
- CustomActionTest.php
- DeleteActionTest.php
- EditActionTest.php
- UpdateFieldActionTest.php
- RelationshipActionTest.php
- (Keep utility tests like BaseControllerTest, DebugModeTest if they don't test specific models)

