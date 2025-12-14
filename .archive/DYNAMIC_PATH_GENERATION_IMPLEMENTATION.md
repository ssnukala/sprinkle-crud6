# Dynamic Integration Test Path Generation Implementation

**Date:** 2025-12-14  
**Issue:** Follow-up to test failure fixes  
**Branch:** copilot/fix-unique-inputs-for-tests  
**Commit:** 7798c3b

## Summary

Implemented complete dynamic generation of `integration-test-paths.json` from schema files, eliminating the need for manual path maintenance and hardcoded model definitions.

## Problem

The original implementation only updated payloads in an existing `integration-test-paths.json` file. The file claimed to be "auto-generated from models" but:
- No script actually generated the complete file
- Model definitions were manually maintained in `integration-test-models.json` (lines 12-157)
- Path structure (URLs, methods, relationships) was manually maintained
- Create payloads were hardcoded and could violate validation rules

## Solution

Created `generate-integration-test-paths.js` that:
1. Reads all schema JSON files
2. Extracts model information (name, relationships, actions, fields)
3. Applies path templates to generate all CRUD operations
4. Generates payloads respecting field validation rules
5. Integrates into existing seed generation workflow

## Architecture

### Input Sources

1. **Schema Files** (`examples/schema/*.json`)
   - Model definitions
   - Field types and validation rules
   - Relationships (many_to_many, has_many, belongs_to_many_through)
   - Custom actions
   - Boolean fields (for toggles)

2. **Path Templates** (`integration-test-models.json`)
   - Generic path patterns with placeholders
   - Validation rules
   - Expected status codes
   - Permission requirements

### Generated Outputs

1. **Complete Paths Structure**
   ```json
   {
     "description": "Integration test paths configuration for CRUD6 sprinkle (auto-generated from schemas)",
     "generated_from": "Schema JSON files",
     "generated_at": "2025-12-14T19:20:24.339Z",
     "paths": {
       "authenticated": {
         "api": {
           "users_schema": {...},
           "users_list": {...},
           "users_create": {...},
           // ... 138 total paths from 17 models
         }
       }
     }
   }
   ```

2. **Path Types Generated**
   - **Basic CRUD**: schema, list, create, single, update, delete
   - **Field Updates**: `PUT /api/crud6/{model}/{id}/{field}` for each unique field
   - **Custom Actions**: `POST /api/crud6/{model}/{id}/a/{action}` for each action
   - **Relationships**: 
     - Attach: `POST /api/crud6/{model}/{id}/{relation}` (many_to_many only)
     - Detach: `DELETE /api/crud6/{model}/{id}/{relation}` (many_to_many only)
     - Get: `GET /api/crud6/{model}/{id}/{relation}` (all types)

### Placeholder Replacement

The script replaces these placeholders in templates:
- `{api_prefix}` → `/api/crud6` (hardcoded for CRUD6 sprinkle)
- `{frontend_prefix}` → `/crud6` (hardcoded for CRUD6 sprinkle)
- `{model}` → Model name (e.g., "users", "groups")
- `{singular}` → Singular form (e.g., "user", "group")
- `{test_id}` → 100 (test data ID range)
- `{field}` → Field name (e.g., "user_name", "email")
- `{actionKey}` → Action key (e.g., "toggle_enabled")
- `{relation}` → Relationship name (e.g., "roles", "permissions")

## Integration Workflow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Developer runs: node generate-seed-sql.js               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Read all schema files from examples/schema/              │
│    - Extract field definitions                              │
│    - Extract validation rules                               │
│    - Extract relationships                                  │
│    - Extract custom actions                                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Generate SQL seed data (existing functionality)          │
│    - INSERT statements for test records                     │
│    - Uses IDs 100+ for test data                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Generate test payload JSON (existing functionality)      │
│    - Create payloads for each model                         │
│    - Update payloads (subset of fields)                     │
│    - Relationship payloads (attach/detach)                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. Call generate-integration-test-paths.js (NEW)           │
│    - Generate complete integration-test-paths.json          │
│    - Apply path templates                                   │
│    - Insert generated payloads                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Output files created:                                    │
│    ✓ app/sql/seeds/crud6-test-data.sql                     │
│    ✓ app/sql/seeds/crud6-test-data-payloads.json          │
│    ✓ .github/config/integration-test-paths.json (complete) │
└─────────────────────────────────────────────────────────────┘
```

## Code Organization

### New Script: `generate-integration-test-paths.js`

**Key Functions:**

1. **`loadTemplates()`**
   - Loads path templates from `integration-test-models.json`
   - Falls back to default templates if file not found

2. **`extractModelInfo(schema)`**
   - Extracts model name, singular form, table name
   - Identifies unique fields for validation
   - Identifies boolean fields for toggle actions
   - Extracts custom action keys
   - Returns structured model information

3. **`generateModelPaths(modelInfo, templates, testPayloads)`**
   - Applies templates for each path type
   - Replaces placeholders with actual values
   - Generates paths for each unique field
   - Generates paths for each custom action
   - Generates paths for each relationship
   - Inserts appropriate payloads

4. **`generateTestPayloads(schema)`**
   - Reuses field value generation logic
   - Creates payloads respecting validation rules
   - Returns create, update, and relationship payloads

5. **`generateFieldValue(fieldName, field, recordId)`**
   - Generates values based on field type
   - Respects max/min length constraints
   - Handles unique fields with ID suffix
   - Handles email, password, boolean types

### Modified: `generate-seed-sql.js`

**Changes:**
- Added `import { execSync } from 'child_process'`
- Replaced payload update logic with call to path generator
- Executes: `node generate-integration-test-paths.js {schema_dir} {paths_file}`

## Example Generated Paths

### Basic CRUD
```json
{
  "users_create": {
    "method": "POST",
    "path": "/api/crud6/users",
    "description": "Create new user via CRUD6 API",
    "expected_status": 200,
    "acceptable_statuses": [200, 201],
    "payload": {
      "user_name": "test_user_name_100",
      "email": "test100@example.com",
      "first_name": "Name100",
      "last_name": "Name100",
      "group_id": 1,
      "flag_enabled": true,
      "flag_verified": true,
      "password": "$2y$10$..."
    }
  }
}
```

### Field-Specific Update
```json
{
  "users_update_field_email": {
    "method": "PUT",
    "path": "/api/crud6/users/100/email",
    "description": "Update single field for user",
    "payload": {
      "email": "test100@example.com"
    }
  }
}
```

### Custom Action
```json
{
  "users_custom_action_toggle_enabled": {
    "method": "POST",
    "path": "/api/crud6/users/100/a/toggle_enabled",
    "description": "Execute custom action on user",
    "payload": {}
  }
}
```

### Relationship Operations
```json
{
  "users_relationship_attach_roles": {
    "method": "POST",
    "path": "/api/crud6/users/100/roles",
    "description": "Attach related items to user (many-to-many)",
    "payload": {
      "ids": [100, 101]
    }
  },
  "users_nested_relationship_roles": {
    "method": "GET",
    "path": "/api/crud6/users/100/roles",
    "description": "Get related items for user (nested endpoint)"
  }
}
```

## Statistics

From 17 models in `examples/schema/`:

| Path Type | Count |
|-----------|-------|
| Schema | 17 |
| List | 17 |
| Create | 17 |
| Single | 17 |
| Update | 17 |
| Delete | 17 |
| Field Updates | ~20 (unique fields) |
| Custom Actions | ~15 (from schema actions) |
| Relationship Operations | ~25 (attach, detach, get) |
| **Total** | **138** |

## Benefits

### For Developers
1. **No Manual Maintenance**: Add/modify schema → paths regenerate automatically
2. **Consistency**: Same validation logic for seeds, payloads, and paths
3. **Type Safety**: Generated payloads match field types (no string where int expected)
4. **Validation Compliance**: All values respect max/min length, uniqueness, etc.

### For Testing
1. **Complete Coverage**: All CRUD operations generated for every model
2. **Relationship Testing**: Both attach/detach and read operations
3. **Custom Actions**: All schema-defined actions tested
4. **Field Updates**: Individual field updates for unique fields

### For CI/CD
1. **Single Command**: One script generates everything
2. **Guaranteed Consistency**: Same source (schemas) for all test artifacts
3. **No Drift**: Paths always match current schema definitions
4. **Easy Updates**: Change schema → regenerate → done

## Migration Guide

### Before (Manual)
1. Define models in `integration-test-models.json`
2. Write create_payload for each model
3. Manually maintain path structure
4. Update payloads when validation changes
5. Add new paths when adding relationships
6. Add new paths when adding custom actions

### After (Automated)
1. Define schema in `examples/schema/{model}.json`
2. Run: `node generate-seed-sql.js examples/schema app/sql/seeds/crud6-test-data.sql`
3. Done!

### What to Remove

The models section (lines 12-157) in `integration-test-models.json` is no longer needed:
```json
{
  "models": {
    "users": { ... },  // Can be removed
    "groups": { ... }, // Can be removed
    "roles": { ... }   // Can be removed
  }
}
```

Keep only:
- `path_templates` section (used by generator)
- `config` section (base_url, auth, etc.)
- `security_notes` section (documentation)

## Future Enhancements

Potential improvements:
1. **Unauthenticated Paths**: Generate 401/403 test paths
2. **Frontend Paths**: Generate frontend page paths
3. **Custom Validators**: Support custom validation rules
4. **Localization**: Generate paths for different locales
5. **Performance Tests**: Generate load testing scenarios
6. **API Documentation**: Generate OpenAPI/Swagger specs

## References

- **Script**: `.github/testing-framework/scripts/generate-integration-test-paths.js`
- **Integration**: `.github/testing-framework/scripts/generate-seed-sql.js`
- **Templates**: `.github/config/integration-test-models.json`
- **Output**: `.github/config/integration-test-paths.json`
- **Schemas**: `examples/schema/*.json`
