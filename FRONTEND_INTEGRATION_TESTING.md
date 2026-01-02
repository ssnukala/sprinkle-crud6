# Frontend Integration Testing with PHPUnit

This document describes the integration testing approach for frontend components using PHPUnit, which addresses complex mocking requirements in Vitest tests.

## Overview

Instead of complex mocking in Vitest unit tests, we use PHPUnit integration tests to:

1. **Test Real API Endpoints** - Verify actual API responses that frontend components consume
2. **Test Data Flow** - Validate complete workflows from frontend payloads → API → database
3. **Test Component Behavior** - Verify frontend components work with real backend data
4. **Avoid Complex Mocks** - Use actual database and API instead of mocking composables

## Architecture

```
Frontend Components (Vue)
        ↓
    Composables (useCRUD6Api, useCRUD6Schema)
        ↓
    API Endpoints (/api/crud6/*)
        ↓
    Controllers & Services
        ↓
    Database
```

**Vitest Tests** - Test component rendering and user interactions with simple mocks
**PHPUnit Tests** - Test the API layer that components depend on with real data

## Existing Integration Tests

The sprinkle already has comprehensive integration tests that simulate frontend workflows:

### FrontendUserWorkflowTest.php
Simulates complete user workflows by creating API payloads that match frontend requests:

```php
// Example: Create user workflow
public function testCreateUserWorkflow(): void
{
    // 1. User navigates to page
    $response = $this->get('/api/crud6/users');
    
    // 2. User opens modal - frontend loads schema
    $response = $this->get('/api/crud6/users/schema');
    
    // 3. User fills form and submits
    $payload = [
        'user_name' => 'frontend_user',
        'first_name' => 'Frontend',
        'email' => 'frontend.user@example.com',
        'password' => 'SecurePassword123!'
    ];
    $response = $this->post('/api/crud6/users', $payload);
    
    // 4. Verify user created
    $this->assertDatabaseHas('users', ['user_name' => 'frontend_user']);
}
```

### Other Integration Tests
- **SchemaBasedApiTest.php** - Tests schema loading and validation for all models
- **NestedEndpointsTest.php** - Tests nested relationship endpoints (users/1/roles)
- **RoleUsersRelationshipTest.php** - Tests complex relationship operations
- **CRUD6UsersIntegrationTest.php** - Full CRUD operations for users
- **CRUD6GroupsIntegrationTest.php** - Full CRUD operations for groups

## Adding Frontend Component Integration Tests

To test frontend components with real backend data:

### 1. Test Component Data Requirements

```php
/**
 * Test that PageList component gets required data from API
 */
public function testPageListDataEndpoint(): void
{
    $this->actAsUser($admin, permissions: ['uri_crud6']);
    
    // PageList component calls this endpoint on mount
    $response = $this->get('/api/crud6/users');
    
    // Verify response structure matches what PageList expects
    $data = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('rows', $data);
    $this->assertArrayHasKey('count', $data);
    $this->assertIsArray($data['rows']);
    
    // Verify fields are present
    foreach ($data['rows'] as $row) {
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('user_name', $row);
        $this->assertArrayHasKey('email', $row);
    }
}
```

### 2. Test Form Submissions

```php
/**
 * Test that Form component can submit data successfully
 */
public function testFormComponentSubmission(): void
{
    $this->actAsUser($admin, permissions: ['uri_crud6', 'create_user']);
    
    // Simulate Form component submission
    $formData = [
        'user_name' => 'test_user',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!'
    ];
    
    $response = $this->post('/api/crud6/users', $formData);
    
    // Verify success response
    $this->assertResponseStatus(201, $response);
    $body = json_decode($response->getBody(), true);
    $this->assertTrue($body['success']);
    $this->assertArrayHasKey('data', $body);
    
    // Verify Form component would receive the created record ID
    $this->assertArrayHasKey('id', $body['data']);
}
```

### 3. Test Info Component Data

```php
/**
 * Test that Info component gets required record data
 */
public function testInfoComponentDataEndpoint(): void
{
    $user = User::factory()->create(['user_name' => 'info_test']);
    $this->actAsUser($admin, permissions: ['uri_crud6']);
    
    // Info component calls this endpoint
    $response = $this->get("/api/crud6/users/{$user->id}");
    
    // Verify response structure
    $data = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('id', $data);
    $this->assertArrayHasKey('user_name', $data);
    $this->assertEquals('info_test', $data['user_name']);
    
    // Verify all viewable fields are present
    $schemaResponse = $this->get('/api/crud6/users/schema?context=detail');
    $schema = json_decode($schemaResponse->getBody(), true);
    
    foreach ($schema['fields'] as $fieldName => $field) {
        if ($field['viewable'] ?? true) {
            $this->assertArrayHasKey($fieldName, $data);
        }
    }
}
```

### 4. Test UnifiedModal Actions

```php
/**
 * Test custom actions that UnifiedModal executes
 */
public function testUnifiedModalCustomAction(): void
{
    $user = User::factory()->create(['flag' => false]);
    $this->actAsUser($admin, permissions: ['uri_crud6']);
    
    // UnifiedModal sends action payload
    $actionPayload = [
        'action' => 'toggle_flag',
        'field' => 'flag',
        'value' => true
    ];
    
    $response = $this->put("/api/crud6/users/{$user->id}/field", $actionPayload);
    
    // Verify action executed
    $this->assertResponseStatus(200, $response);
    $user->refresh();
    $this->assertTrue($user->flag);
}
```

### 5. Test Schema Loading

```php
/**
 * Test schema endpoints for all components
 */
public function testSchemaEndpointsForComponents(): void
{
    $this->actAsUser($admin, permissions: ['uri_crud6']);
    
    // Form component needs 'form' context
    $response = $this->get('/api/crud6/users/schema?context=form');
    $schema = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('fields', $schema);
    
    // Verify editable fields present
    foreach ($schema['fields'] as $fieldName => $field) {
        if ($field['editable'] ?? true) {
            $this->assertIsArray($field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('label', $field);
        }
    }
    
    // Info component needs 'detail' context
    $response = $this->get('/api/crud6/users/schema?context=detail');
    $schema = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('fields', $schema);
    
    // PageList needs 'list' context
    $response = $this->get('/api/crud6/users/schema?context=list');
    $schema = json_decode($response->getBody(), true);
    $this->assertArrayHasKey('fields', $schema);
}
```

## Running Integration Tests

```bash
# Run all integration tests
vendor/bin/phpunit --testsuite Integration

# Run specific test
vendor/bin/phpunit app/tests/Integration/FrontendUserWorkflowTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

## Benefits of This Approach

### 1. Real Data Testing
- Tests use actual database and API responses
- No complex mocking of composables or stores
- Validates actual data flow end-to-end

### 2. Catches Integration Issues
- Finds problems in API layer that unit tests miss
- Verifies schema and data structure compatibility
- Tests permission and authentication flows

### 3. Complements Vitest Tests
- **Vitest**: Fast unit tests for UI logic and rendering
- **PHPUnit**: Integration tests for API and data layer
- Together provide comprehensive coverage

### 4. Documents Component Requirements
- Tests serve as documentation for component data needs
- Shows expected API responses and payloads
- Helps frontend developers understand backend contracts

## Test Organization

```
app/tests/Integration/
├── FrontendUserWorkflowTest.php      # User CRUD workflows
├── FrontendComponentDataTest.php      # NEW: Component data requirements
├── SchemaBasedApiTest.php             # Schema loading tests
├── NestedEndpointsTest.php            # Relationship endpoints
└── ...
```

## Best Practices

### 1. Test Component Dependencies
Focus on testing the API endpoints and data that components depend on:
- Schema endpoints for Form, Info, PageList
- CRUD endpoints for all components
- Relationship endpoints for Details component
- Custom action endpoints for UnifiedModal

### 2. Use Factory Data
Create realistic test data using factories:
```php
$user = User::factory()->create([
    'user_name' => 'test_user',
    'email' => 'test@example.com'
]);
```

### 3. Verify Response Structure
Test that API responses match component expectations:
```php
$this->assertArrayHasKey('id', $data);
$this->assertArrayHasKey('user_name', $data);
$this->assertIsString($data['user_name']);
```

### 4. Test Error Cases
Verify components receive proper error responses:
```php
// Test validation errors
$response = $this->post('/api/crud6/users', ['invalid' => 'data']);
$this->assertResponseStatus(400, $response);
$body = json_decode($response->getBody(), true);
$this->assertArrayHasKey('message', $body);
```

## Conclusion

Integration testing with PHPUnit provides:
- ✅ Real backend testing without complex mocks
- ✅ Validates complete data flow
- ✅ Documents component API requirements
- ✅ Catches integration issues early
- ✅ Complements Vitest unit tests

This approach addresses the complex mocking requirements in Vitest by testing the actual backend layer that components depend on.
