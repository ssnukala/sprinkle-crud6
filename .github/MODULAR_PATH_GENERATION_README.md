# Modular Path Generation for Integration Tests

## Overview

The CRUD6 sprinkle now uses a **modular, model-driven approach** to generate integration test paths. Instead of manually defining each path, you define models once and automatically generate all test paths.

## How It Works

### 1. Define Models (`.github/config/integration-test-models.json`)

Define your models with their properties:

```json
{
  "models": {
    "users": {
      "name": "users",
      "singular": "user",
      "api_prefix": "/api/crud6",
      "frontend_prefix": "/crud6",
      "test_id": 1,
      "api_validation_keys": ["id", "user_name", "email"],
      "list_validation_keys": ["rows"],
      "enabled": true
    }
  },
  "path_templates": {
    "authenticated": {
      "api": {
        "list": { /* template */ },
        "single": { /* template */ }
      },
      "frontend": {
        "list": { /* template */ },
        "detail": { /* template */ }
      }
    }
  }
}
```

### 2. Generate Paths (`.github/scripts/generate-paths-from-models.js`)

Run the generator script to create the paths configuration:

```bash
node .github/scripts/generate-paths-from-models.js \
  .github/config/integration-test-models.json \
  .github/config/integration-test-paths.json
```

### 3. Use Generated Paths

The generated `integration-test-paths.json` is used by:
- `test-paths.php` - Test API and frontend paths
- `take-screenshots-with-tracking.js` - Capture screenshots

## Model Configuration

### Model Properties

| Property | Description | Example |
|----------|-------------|---------|
| `name` | Model name (plural) | `"users"` |
| `singular` | Singular form | `"user"` |
| `api_prefix` | API path prefix | `"/api/crud6"` |
| `frontend_prefix` | Frontend path prefix | `"/crud6"` |
| `test_id` | ID to use for single-record tests | `1` |
| `api_validation_keys` | Keys to validate in API response | `["id", "user_name"]` |
| `list_validation_keys` | Keys to validate in list response | `["rows"]` |
| `enabled` | Whether to generate paths for this model | `true` |

### Path Templates

Templates use placeholders that are replaced with model properties:

| Placeholder | Replaced With | Example |
|-------------|---------------|---------|
| `{model}` | Model name (plural) | `users` |
| `{singular}` | Singular form | `user` |
| `{api_prefix}` | API path prefix | `/api/crud6` |
| `{frontend_prefix}` | Frontend path prefix | `/crud6` |
| `{test_id}` | Test ID | `1` |
| `{api_validation_keys}` | Validation keys (as JSON) | `["id", "name"]` |
| `{list_validation_keys}` | List validation keys | `["rows"]` |

## Generated Structure

For each enabled model, the generator creates:

### Authenticated Paths
- **API List**: `GET {api_prefix}/{model}`
- **API Single**: `GET {api_prefix}/{model}/{test_id}`
- **Frontend List**: `{frontend_prefix}/{model}`
- **Frontend Detail**: `{frontend_prefix}/{model}/{test_id}`

### Unauthenticated Paths
Same paths as above, but with:
- Expected status: 401 for API
- Expected status: 200 with redirect for frontend

### Screenshot Configurations
- List page: `{model}_list.png`
- Detail page: `{singular}_detail.png`

## Example: Adding a New Model

To add a new model for testing:

### Step 1: Add Model Definition

Edit `.github/config/integration-test-models.json`:

```json
{
  "models": {
    "products": {
      "name": "products",
      "singular": "product",
      "api_prefix": "/api/crud6",
      "frontend_prefix": "/crud6",
      "test_id": 1,
      "api_validation_keys": ["id", "name", "price"],
      "list_validation_keys": ["rows"],
      "enabled": true
    }
  }
}
```

### Step 2: Regenerate Paths

```bash
node .github/scripts/generate-paths-from-models.js \
  .github/config/integration-test-models.json \
  .github/config/integration-test-paths.json
```

### Step 3: Done!

The generator creates 8 paths automatically:
- ✅ `products_list` (authenticated API)
- ✅ `products_single` (authenticated API)
- ✅ `products_list` (authenticated frontend with screenshot)
- ✅ `products_detail` (authenticated frontend with screenshot)
- ✅ `products_list` (unauthenticated API)
- ✅ `products_single` (unauthenticated API)
- ✅ `products_list` (unauthenticated frontend)
- ✅ `products_detail` (unauthenticated frontend)

## Benefits

### 1. **No Repetition**
Define each model once, not 8 times (4 authenticated + 4 unauthenticated).

### 2. **Consistency**
All paths follow the same pattern - no copy-paste errors.

### 3. **Easy Updates**
Change the template once, regenerate all paths.

### 4. **Clear Structure**
Model definitions are self-documenting.

### 5. **Scalability**
Add 10 new models? Just add 10 model definitions and regenerate.

### 6. **Type Safety**
Validation keys are defined per model, ensuring correct testing.

## Usage in CI/CD

### Manual Generation (Development)

```bash
# Generate paths from models
node .github/scripts/generate-paths-from-models.js \
  .github/config/integration-test-models.json \
  .github/config/integration-test-paths.json

# Use the generated paths
php test-paths.php integration-test-paths.json
```

### Auto-Generation (CI Workflow)

Add to `.github/workflows/integration-test.yml`:

```yaml
- name: Generate paths from models
  run: |
    cd userfrosting
    cp ../sprinkle-crud6/.github/config/integration-test-models.json .
    cp ../sprinkle-crud6/.github/scripts/generate-paths-from-models.js .
    node generate-paths-from-models.js integration-test-models.json integration-test-paths.json
    
- name: Test paths
  run: |
    cd userfrosting
    php test-paths.php integration-test-paths.json unauth
```

## Comparison: Manual vs Modular

### Manual Approach (Old)

```json
{
  "paths": {
    "authenticated": {
      "api": {
        "users_list": {
          "method": "GET",
          "path": "/api/crud6/users",
          "description": "Get list of users via CRUD6 API",
          "expected_status": 200,
          "validation": { "type": "json", "contains": ["rows"] }
        },
        "users_single": { /* ... */ },
        "groups_list": { /* ... */ },
        "groups_single": { /* ... */ },
        /* ... 36 more path definitions ... */
      }
    }
  }
}
```

**Problem**: 40+ path definitions with lots of repetition.

### Modular Approach (New)

```json
{
  "models": {
    "users": {
      "name": "users",
      "singular": "user",
      "api_prefix": "/api/crud6",
      "frontend_prefix": "/crud6",
      "test_id": 1,
      "api_validation_keys": ["id", "user_name", "email"],
      "list_validation_keys": ["rows"],
      "enabled": true
    },
    "groups": { /* similar */ },
    "roles": { /* similar */ },
    "permissions": { /* similar */ },
    "activities": { /* similar */ }
  },
  "path_templates": { /* 4 templates */ }
}
```

**Solution**: 5 model definitions + 4 templates = 40 paths generated automatically.

## Current c6admin Models

The CRUD6 sprinkle currently tests these models:

| Model | API Prefix | Enabled |
|-------|-----------|---------|
| users | `/api/crud6` | ✅ |
| groups | `/api/crud6` | ✅ |
| roles | `/api/crud6` | ✅ |
| permissions | `/api/crud6` | ✅ |
| activities | `/api/crud6` | ✅ |

**Total**: 5 models × 8 paths each = **40 paths generated**

## Validation

The generator validates:
- ✅ Model definitions are complete
- ✅ Required properties are present
- ✅ Template placeholders are valid
- ✅ Generated JSON is valid
- ✅ Path counts are correct

## Files

| File | Purpose |
|------|---------|
| `.github/config/integration-test-models.json` | Model definitions and templates |
| `.github/scripts/generate-paths-from-models.js` | Path generator script |
| `.github/config/integration-test-paths.json` | Generated paths (can be committed) |

## Best Practices

### 1. Version Control

Commit both:
- `integration-test-models.json` (source)
- `integration-test-paths.json` (generated)

This allows reviewers to see both the model definitions and the generated output.

### 2. Regeneration

Regenerate paths after:
- Adding a new model
- Changing model properties
- Updating path templates
- Changing validation rules

### 3. Testing

After regeneration:
1. Validate JSON syntax: `node -c integration-test-paths.json`
2. Check path counts
3. Spot-check a few generated paths
4. Run integration tests

### 4. Documentation

When adding a model:
- Document why it's being tested
- Explain any special validation keys
- Note any dependencies

## Migration Guide

To migrate existing paths to the modular approach:

### Step 1: Extract Model Definitions

For each set of paths, create a model definition:

```json
{
  "name": "your_model",
  "singular": "your_item",
  "api_prefix": "/api/yoursprinkle",
  "frontend_prefix": "/yoursprinkle",
  "test_id": 1,
  "api_validation_keys": ["id", "name"],
  "list_validation_keys": ["rows"],
  "enabled": true
}
```

### Step 2: Create Models Config

Combine all model definitions into `integration-test-models.json`.

### Step 3: Generate Paths

Run the generator and verify output matches your manual paths.

### Step 4: Update Workflow

Replace manual path definitions with generator call.

### Step 5: Test

Run integration tests to ensure everything still works.

## Future Enhancements

Possible future additions:
- [ ] Support for custom path types (create, update, delete)
- [ ] Model-specific test data
- [ ] Relationship testing
- [ ] Performance benchmarking per model
- [ ] Custom validation rules per model
- [ ] Multi-tenant testing

## Summary

The modular path generation approach:
- ✅ Reduces repetition from 40+ definitions to 5 models
- ✅ Ensures consistency across all paths
- ✅ Makes adding new models trivial
- ✅ Improves maintainability
- ✅ Provides clear documentation
- ✅ Scales easily

**Next Steps**: Use this approach for all CRUD6 model testing!
