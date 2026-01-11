# Comprehensive Schema-Driven Test Plan

## Overview

This document outlines the comprehensive testing approach for the CRUD6 sprinkle, where all functionality is tested through a defined set of schema files that iterate through standard tests.

## Test Schema Set

### Core Schemas for CRUD6 Functionality Testing

The following schema files are used as the standard test set to validate all CRUD6 sprinkle components:

1. **users.json** - Tests user management with:
   - All CRUD operations
   - Custom actions (reset_password, enable_user, disable_user)
   - Relationships (roles, groups, permissions, activities)
   - Soft deletes
   - Field visibility controls

2. **roles.json** - Tests role management with:
   - Many-to-many relationships
   - Pivot table handling
   - Nested relationships (users, permissions)

3. **groups.json** - Tests group management with:
   - Simple CRUD operations
   - Basic relationships (users)

4. **permissions.json** - Tests permission management with:
   - Complex nested relationships
   - Many-to-many through relationships

5. **activities.json** - Tests activity logging with:
   - Polymorphic relationships
   - Timestamp handling

6. **products.json** - Tests e-commerce scenarios with:
   - Decimal/float field types
   - Category relationships
   - Stock management

## CRUD6 Components Tested

### 1. Schema Loading & Validation
**Tested by:** All schemas
- JSON schema file parsing
- Schema validation (required fields: model, table, fields)
- Field type validation
- Permission structure validation
- Relationship definition validation

### 2. API Endpoints
**Tested per schema:**
- `GET /api/crud6/{model}` - List with pagination, sorting, filtering
- `POST /api/crud6/{model}` - Create with validation
- `GET /api/crud6/{model}/{id}` - Read single record
- `PUT /api/crud6/{model}/{id}` - Update full record
- `PUT /api/crud6/{model}/{id}/{field}` - Update single field
- `DELETE /api/crud6/{model}/{id}` - Delete record
- `POST /api/crud6/{model}/{id}/a/{action}` - Custom actions
- `GET /api/crud6/{model}/{id}/{relation}` - Relationship endpoints

### 3. Permission System
**Tested per schema:**
- Schema-driven permission mapping (`permissions` section)
- Action-specific permissions (read, create, update, delete)
- Custom action permissions
- Permission inheritance and fallbacks
- Authorization middleware (AuthGuard)

### 4. Field Types & Validation
**Tested across schemas:**
- String fields (varchar, text)
- Integer fields
- Boolean fields (flags)
- Date/DateTime fields
- Decimal/Float fields
- JSON fields
- Email validation
- Password hashing
- Required field validation
- Unique constraints

### 5. Relationships
**Tested per schema:**
- has_many relationships
- belongs_to relationships
- belongs_to_many relationships (many-to-many)
- belongs_to_many_through (nested many-to-many)
- Pivot table data handling
- Nested relationship queries

### 6. Custom Actions
**Tested per schema with actions:**
- Action definition in schema
- Action permission checking
- Action execution
- Action payload validation
- Action-specific business logic

### 7. Sprunje (Data Tables)
**Tested per schema:**
- Pagination
- Sorting (single and multiple columns)
- Filtering
- Searching
- Field visibility (listable fields)
- Metadata inclusion

### 8. Soft Deletes
**Tested on schemas with soft deletes:**
- Soft delete functionality
- deleted_at column handling
- Restore functionality
- Querying with/without soft deleted records

### 9. Field Visibility
**Tested per schema:**
- show_in controls (list, detail, form)
- Sensitive field filtering (passwords)
- Conditional field display

### 10. Context-Based Schemas
**Tested per schema:**
- list context (minimal fields for tables)
- form context (editable fields with validation)
- detail context (full record display)
- meta context (model metadata only)

## Test Execution Flow

### For Each Schema in Test Set:

```
1. Schema Validation
   ✓ JSON structure is valid
   ✓ Required fields present (model, table, fields)
   ✓ Field types are valid
   ✓ Permissions structure is valid
   ✓ Relationships are properly defined

2. CRUD Operations
   ✓ List: GET /api/crud6/{model}
   ✓ Create: POST /api/crud6/{model}
   ✓ Read: GET /api/crud6/{model}/{id}
   ✓ Update: PUT /api/crud6/{model}/{id}
   ✓ Delete: DELETE /api/crud6/{model}/{id}

3. Permissions
   ✓ Unauthenticated requests return 401
   ✓ Unauthorized requests return 403
   ✓ Authorized requests succeed (200/201)
   ✓ Schema permissions are respected

4. Relationships (if defined)
   ✓ Relationship endpoints accessible
   ✓ Related data is correctly returned
   ✓ Pivot data is included (many-to-many)

5. Custom Actions (if defined)
   ✓ Action endpoints accessible
   ✓ Action permissions checked
   ✓ Actions execute successfully

6. Field Updates
   ✓ Single field updates work
   ✓ Validation is enforced
   ✓ Read-only fields are protected

7. Sprunje Features
   ✓ Pagination works
   ✓ Sorting works
   ✓ Filtering works (if filterable fields defined)
   ✓ Searching works (if searchable fields defined)
```

## Test Output Format

Each test should clearly show:

```
[TESTING SCHEMA: users.json]
  Components Tested:
    ✓ Schema validation
    ✓ CRUD operations (5/5 passed)
    ✓ Permissions (4/4 passed)
    ✓ Relationships (4/4 passed)
    ✓ Custom actions (3/3 passed)
    ✓ Field updates (2/2 passed)
    ✓ Sprunje features (4/4 passed)
  
  Result: ✅ ALL TESTS PASSED (25/25)
  
[TESTING SCHEMA: roles.json]
  Components Tested:
    ✓ Schema validation
    ✓ CRUD operations (5/5 passed)
    ✓ Permissions (4/4 passed)
    ✓ Relationships (2/2 passed)
    ✗ Custom actions (0 defined - skipped)
    ✓ Field updates (2/2 passed)
    ✓ Sprunje features (4/4 passed)
  
  Result: ✅ ALL TESTS PASSED (19/19)
```

## Implementation

### Data Provider

```php
public static function schemaProvider(): array
{
    // Define the standard test schemas
    $testSchemas = [
        'users',
        'roles', 
        'groups',
        'permissions',
        'activities',
        'products',
    ];
    
    return array_map(fn($schema) => [$schema], $testSchemas);
}
```

### Comprehensive Test Method

```php
/**
 * @dataProvider schemaProvider
 */
public function testSchemaComprehensive(string $modelName): void
{
    echo "\n[TESTING SCHEMA: {$modelName}.json]\n";
    echo "  Components Tested:\n";
    
    $results = [];
    
    // 1. Schema validation
    $results['schema'] = $this->testSchemaValidation($modelName);
    
    // 2. CRUD operations
    $results['crud'] = $this->testCrudOperations($modelName);
    
    // 3. Permissions
    $results['permissions'] = $this->testPermissions($modelName);
    
    // 4. Relationships (if applicable)
    $results['relationships'] = $this->testRelationships($modelName);
    
    // 5. Custom actions (if applicable)
    $results['actions'] = $this->testCustomActions($modelName);
    
    // 6. Field updates
    $results['fields'] = $this->testFieldUpdates($modelName);
    
    // 7. Sprunje features
    $results['sprunje'] = $this->testSprunjeFeatures($modelName);
    
    // Display results
    $this->displayTestResults($modelName, $results);
}
```

## Benefits

1. **Visibility**: Clear view of what's being tested for each schema
2. **Comprehensive**: All CRUD6 components tested systematically
3. **Maintainable**: Add new schemas to test set easily
4. **Traceable**: Easy to see which components pass/fail per schema
5. **Documentation**: Tests serve as documentation of features
6. **Consistent**: Same tests applied to all schemas
7. **Schema-Driven**: Fully aligned with CRUD6 principles

## Adding New Schemas

To test a new model:
1. Add schema file to `examples/schema/{model}.json`
2. Add model name to `schemaProvider()` test set
3. Tests automatically run for new model
4. No test code changes needed

This ensures every schema tests all applicable CRUD6 functionality.
