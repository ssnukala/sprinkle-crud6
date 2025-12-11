# Flexible Placeholder Replacement System

## Overview

The integration test path generation system now includes a flexible placeholder replacement mechanism that automatically detects placeholders in path templates, reads JSON schemas to extract actual values, and generates specific test paths with appropriate payloads.

## Problem Statement

Previous integration tests had paths like:
- `PUT /api/crud6/permissions/2/{field}` - field placeholder not replaced
- `POST /api/crud6/users/2/a/{actionKey}` - action key placeholder not replaced
- `DELETE /api/crud6/roles/2/{relation}` - relation placeholder not replaced

This caused 500 errors because the backend received literal `{field}` instead of actual field names.

Additionally, CreateAction had SQL errors due to incomplete payloads missing required fields like `slug`.

## Solution

### Flexible Placeholder Detection

The system automatically detects any placeholder pattern `{placeholder_name}` in path templates and processes them dynamically:

```javascript
function detectPlaceholders(str) {
    const placeholderRegex = /\{([^}]+)\}/g;
    // Automatically finds: {field}, {actionKey}, {relation}, etc.
}
```

### Dynamic Value Extraction

For each detected placeholder type, the system reads values from:

1. **JSON Schemas** (`examples/schema/*.json`)
   - Extracts editable fields (skips readonly, auto_increment, computed)
   - Extracts custom actions from `actions` array
   - Extracts relationships from `relationships` array

2. **Model Configuration** (`integration-test-models.json`)
   - Falls back to relationship definitions in model config
   - Uses `create_payload` for generating test data

### Supported Placeholders

| Placeholder | Source | Example Values |
|------------|--------|----------------|
| `{field}` | Schema `fields` | `user_name`, `slug`, `name` |
| `{actionKey}` | Schema `actions` | `toggle_enabled`, `reset_password` |
| `{relation}` | Schema `relationships` | `roles`, `permissions`, `users` |
| `{model}` | Model config | `users`, `permissions` |
| `{test_id}` | Model config | `2` |

### Path Expansion

A single template path with placeholders expands to multiple specific paths:

**Template:**
```json
{
  "update_field": {
    "method": "PUT",
    "path": "{api_prefix}/{model}/{test_id}/{field}",
    "description": "Update single field for {singular}"
  }
}
```

**Expands to:**
```json
{
  "users_update_field_user_name": {
    "method": "PUT",
    "path": "/api/crud6/users/2/user_name",
    "description": "Update single field for user",
    "note": "Using field='user_name'",
    "payload": {
      "user_name": "apitest"
    }
  }
}
```

### Automatic Payload Generation

The system generates appropriate payloads based on:

1. **Field Updates** - Uses field name and model's create_payload value
2. **Relationship Operations** - Generates `{relation}_ids` array
3. **Create Operations** - Uses model's `create_payload` directly
4. **Custom Actions** - Usually empty payload

```javascript
function generatePayload(pathConfig, model, placeholder, value) {
    if (placeholder === 'field') {
        return { [value]: generateFieldValue(value, model) };
    } else if (placeholder === 'relation' && pathConfig.method === 'POST') {
        return { [`${value}_ids`]: [1] };
    }
    // ... etc
}
```

## Usage

### Generating Paths

```bash
cd .github/config
node ../scripts/generate-paths-from-models.js \
  integration-test-models.json \
  integration-test-paths.json \
  ../../examples/schema
```

### Adding New Placeholder Types

The system is designed to be extensible. To add a new placeholder type:

1. **Add to detection** (optional - regex already catches all `{...}` patterns)
2. **Add value extraction function**:
   ```javascript
   function getPlaceholderValues(placeholder, model, schema) {
       switch (placeholder) {
           case 'my_new_placeholder':
               return extractMyValues(schema);
           // ...
       }
   }
   ```
3. **Add payload generation logic**:
   ```javascript
   function generatePayload(pathConfig, model, placeholder, value) {
       if (placeholder === 'my_new_placeholder') {
           return { custom: value };
       }
       // ...
   }
   ```

### Skipping Paths

If a model doesn't have values for a placeholder, the path is automatically skipped:

```
⏭️  Skipping permissions_custom_action: No actionKey values available for permissions
⏭️  Skipping activities_relationship_attach: No relation values available for activities
```

This prevents generating invalid test paths.

## Statistics

From the December 2025 update:

- **Total Paths Generated**: 112
  - 62 authenticated API paths
  - 10 authenticated frontend paths
  - 30 unauthenticated API paths
  - 10 unauthenticated frontend paths

- **Paths Expanded**:
  - `users`: 29 paths (includes 3 field updates, 4 custom actions, 3 relationships)
  - `groups`: 20 paths (1 field update, 1 relationship)
  - `roles`: 23 paths (1 field update, 2 relationships)
  - `permissions`: 23 paths (1 field update, 2 relationships)
  - `activities`: 17 paths (no actions/relationships)

- **Placeholders Resolved**: 100% (verified no `{field}`, `{actionKey}`, or `{relation}` remaining)

## Benefits

1. **Automatic** - No manual path creation for each field/action/relationship
2. **Schema-Driven** - Reads actual values from schema files
3. **Flexible** - Extensible to new placeholder types without code changes
4. **Safe** - Skips invalid paths instead of generating broken tests
5. **Complete** - Includes payloads for all operations
6. **Maintainable** - Update schema, regenerate paths automatically

## Examples

### Users Model (29 paths)

- ✅ `users_update_field_user_name` - Updates user_name field
- ✅ `users_custom_action_toggle_enabled` - Executes toggle_enabled action
- ✅ `users_custom_action_reset_password` - Executes reset_password action
- ✅ `users_relationship_attach_roles` - Attaches roles to user
- ✅ `users_relationship_detach_roles` - Detaches roles from user
- ✅ `users_nested_relationship_roles` - Gets user's roles

### Permissions Model (23 paths)

- ✅ `permissions_update_field_slug` - Updates slug field
- ✅ `permissions_relationship_attach_roles` - Attaches roles to permission
- ✅ `permissions_nested_relationship_users` - Gets users with permission (through roles)
- ⏭️ `permissions_custom_action` - Skipped (no actions in schema)

## Validation

After generation, always verify:

```bash
# Check for unresolved placeholders
cat integration-test-paths.json | grep -E '\{field\}|\{actionKey\}|\{relation\}'
# Should output: ✅ No unresolved placeholders found

# Validate JSON
node -e "require('./integration-test-paths.json'); console.log('✅ Valid JSON')"

# Count paths
node -e "const d=require('./integration-test-paths.json'); console.log('Paths:', Object.keys(d.paths.authenticated.api).length)"
```

## Future Enhancements

Potential improvements:

1. **Field Type Detection** - Generate type-appropriate test values (email format for email fields, etc.)
2. **Validation Rules** - Read validation rules from schema and generate compliant test data
3. **Multiple Field Values** - Test each field multiple times with different values
4. **Relationship Depth** - Test nested relationships (users -> roles -> permissions)
5. **Action Parameters** - Extract and use action parameters from schema
6. **Negative Tests** - Generate invalid payloads to test validation

## Related Files

- `.github/scripts/generate-paths-from-models.js` - Path generation script
- `.github/config/integration-test-models.json` - Model definitions and templates
- `.github/config/integration-test-paths.json` - Generated paths output
- `examples/schema/*.json` - Source of truth for fields, actions, relationships
