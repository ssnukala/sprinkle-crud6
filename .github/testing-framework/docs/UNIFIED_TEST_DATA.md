# Unified Test Data - Single Source of Truth

## Overview

All tests (frontend Vitest and backend PHPUnit) now use **the same centralized test data** from the integration testing framework. No more duplicate fixtures!

## Before vs After

### Before (Duplicated)
```
examples/test/vitest/
├── fixtures/products.json        ← Duplicate
├── fixtures/users.json            ← Duplicate
├── schemas/products.json          ← Duplicate
└── schemas/users.json             ← Duplicate

examples/schema/
├── products.json                  ← Original
└── users.json                     ← Original

.github/config/
└── integration-test-models.json   ← Original test data
```

**Problem**: Maintaining two copies, risk of inconsistency

### After (Unified)
```
examples/schema/                   ← Single source for schemas
├── products.json
├── users.json
├── groups.json
├── roles.json
└── ... (20+ models)

.github/config/
└── integration-test-models.json   ← Single source for test data
    Contains:
    - Model definitions
    - Test IDs
    - API paths
    - Create payloads (test data)
    - Relationships
```

**Solution**: Single source of truth, always consistent

## Data Sources

### 1. Schemas (`examples/schema/`)

These are the **same schema files** that:
- ✅ Define CRUD6 models
- ✅ Drive the integration tests
- ✅ Generate database tables
- ✅ Configure API endpoints
- ✅ Now used by frontend tests too!

**Example**: `examples/schema/users.json`
```json
{
  "model": "users",
  "title": "User Management",
  "fields": {
    "id": { "type": "integer", "editable": false },
    "user_name": { "type": "string", "required": true },
    "email": { "type": "email", "required": true }
  }
}
```

### 2. Test Data (`.github/config/integration-test-models.json`)

Comprehensive model configuration including test data:

```json
{
  "models": {
    "users": {
      "name": "users",
      "api_prefix": "/api/crud6",
      "test_id": 2,
      "create_payload": {
        "user_name": "apitest",
        "first_name": "API",
        "last_name": "Test",
        "email": "apitest@example.com"
      },
      "relationships": [
        { "name": "roles", "type": "many_to_many" }
      ]
    }
  }
}
```

This file provides:
- Test record data (`create_payload`)
- API endpoint paths
- Safe test IDs
- Relationship definitions
- Validation keys

## Usage in Tests

### Frontend (Vitest)

Updated `app/assets/tests/fixtures.ts` to use centralized data:

```typescript
import { loadSchemaFixture, loadDataFixture, getModelConfig } from './fixtures'

// Load schema from examples/schema/
const schema = loadSchemaFixture('users')

// Load test data from integration-test-models.json
const data = loadDataFixture('users')

// Get model configuration
const config = getModelConfig('users')

// Use in tests
expect(data[0].user_name).toBe('apitest')
expect(schema.fields.user_name).toBeDefined()
expect(config.api_prefix).toBe('/api/crud6')
```

### Backend (PHPUnit)

Already uses the same sources:

```php
// Integration tests use:
// - Schemas from examples/schema/
// - Test data from integration-test-models.json
// - Paths from integration-test-paths.json

$response = $this->post('/api/crud6/users', [
    'user_name' => 'apitest',  // Same data as frontend tests
    'email' => 'apitest@example.com'
]);
```

## Benefits

### 1. Consistency
- ✅ Frontend and backend tests use **exact same data**
- ✅ No risk of schema/data mismatch
- ✅ Changes propagate automatically

### 2. Maintainability
- ✅ Update **one file**, affects all tests
- ✅ Add new model? One schema file, one config entry
- ✅ No duplicate JSON to keep in sync

### 3. Comprehensiveness
- ✅ 20+ models available from `examples/schema/`
- ✅ 5+ models with full test data in `integration-test-models.json`
- ✅ Relationships, validations, API paths all defined

### 4. Documentation
- ✅ Single source documents expected structure
- ✅ Test data serves as API examples
- ✅ Schemas document field definitions

## Available Models

From `integration-test-models.json`:

| Model | Test Data | Relationships | API Tested |
|-------|-----------|---------------|------------|
| **users** | ✅ | roles, activities, permissions | ✅ |
| **groups** | ✅ | users | ✅ |
| **roles** | ✅ | users, permissions | ✅ |
| **permissions** | ✅ | roles, users | ✅ |
| **activities** | ✅ | user | ✅ |

From `examples/schema/` (20+ additional schemas available):
- products
- orders
- categories
- contacts
- ... and more

## New Fixture Loader Features

Enhanced `app/assets/tests/fixtures.ts` with:

```typescript
// Get available models
const models = getAvailableModels()
// ['users', 'groups', 'roles', 'permissions', 'activities']

// Get API paths for a model
const paths = getModelApiPaths('users')
// {
//   schema: '/api/crud6/users/schema',
//   list: '/api/crud6/users',
//   single: (id) => `/api/crud6/users/${id}`,
//   create: '/api/crud6/users',
//   ...
// }

// Get full model config
const config = getModelConfig('users')
// {
//   name: 'users',
//   api_prefix: '/api/crud6',
//   test_id: 2,
//   create_payload: { ... },
//   relationships: [ ... ]
// }
```

## Migration from Old Fixtures

### What Was Removed
- ❌ `examples/test/vitest/fixtures/` - No longer needed
- ❌ `examples/test/vitest/schemas/` - No longer needed
- ❌ Duplicate JSON files

### What Changed
- ✅ `fixtures.ts` - Now loads from centralized locations
- ✅ `fixtures.test.ts` - Updated for new data sources
- ✅ Configuration - Points to centralized data
- ✅ Workflow - Verifies centralized data instead

### Your Existing Tests
- ✅ **No changes needed!** API is the same:
  ```typescript
  loadSchemaFixture('users')  // Still works
  loadDataFixture('users')     // Still works
  ```
- ✅ Just getting data from better source now

## Adding New Test Data

### For Existing Models

Edit `.github/config/integration-test-models.json`:

```json
{
  "models": {
    "my_model": {
      "name": "my_model",
      "api_prefix": "/api/crud6",
      "test_id": 2,
      "create_payload": {
        "field1": "value1",
        "field2": "value2"
      }
    }
  }
}
```

Frontend tests can now use:
```typescript
const data = loadDataFixture('my_model')
// Uses create_payload from config
```

### For New Models

1. **Create schema** in `examples/schema/my_model.json`
2. **Add to config** in `integration-test-models.json`
3. **Use in tests** with `loadSchemaFixture()` and `loadDataFixture()`

## CI/CD Verification

The workflow now verifies centralized data:

```yaml
- name: Verify test data sources
  run: |
    # Frontend tests use same data as integration tests
    test -d "examples/schema"
    test -f ".github/config/integration-test-models.json"
    echo "✅ Test data sources verified"
```

No separate fixture copying needed!

## Best Practices

### 1. Use Schema for Structure
```typescript
const schema = loadSchemaFixture('users')
const editableFields = getEditableFields(schema)
// Always in sync with actual model definition
```

### 2. Use Config for Test Data
```typescript
const data = loadDataFixture('users')
// Same data as integration tests use
```

### 3. Use Config for API Paths
```typescript
const paths = getModelApiPaths('users')
// Consistent with integration test paths
```

### 4. Extend as Needed
```typescript
// Can still create test-specific variations
const testUser = {
  ...loadSingleRecordFixture('users'),
  custom_field: 'test_value'
}
```

## Documentation

- **Schema Files**: `examples/schema/*.json` - Field definitions
- **Model Config**: `.github/config/integration-test-models.json` - Test data and paths
- **Fixture Loader**: `app/assets/tests/fixtures.ts` - Utility functions
- **Fixture Tests**: `app/assets/tests/fixtures.test.ts` - Usage examples

## Summary

**One source of truth for all test data:**

- ✅ Schemas in `examples/schema/`
- ✅ Test data in `.github/config/integration-test-models.json`
- ✅ Used by both frontend (Vitest) and backend (PHPUnit) tests
- ✅ No duplication, always consistent
- ✅ Easy to maintain, extend, and document

**Your tests are now backed by the same robust test data infrastructure that powers the entire integration testing framework!**
