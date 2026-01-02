# Frontend Testing Integration for UserFrosting 6 Sprinkles

This document explains how to integrate frontend testing (Vitest/Vue Test Utils) into the UserFrosting 6 Integration Testing Framework for any sprinkle.

## Overview

The framework now supports **two-layer testing**:

1. **Vitest Frontend Tests** - Fast unit tests for Vue components, views, and composables
2. **PHPUnit Backend Tests** - Integration tests for API endpoints and database operations

Both test suites run automatically in CI/CD and are configured through JSON.

## Quick Start

### Step 1: Enable Frontend Testing in Configuration

Edit your `integration-test-config.json`:

```json
{
  "sprinkle": {
    "name": "my-sprinkle",
    "npm_package": "@vendor/my-sprinkle"
  },
  "frontend": {
    "enabled": true,
    "test_command": "npm test",
    "test_data": {
      "source": "integration_framework",
      "schemas_path": "examples/schema",
      "models_config": ".github/config/integration-test-models.json"
    },
    "coverage": {
      "enabled": true,
      "upload_artifacts": true
    }
  }
}
```

**Note:** Frontend tests automatically use the **same schemas and test data** as your integration tests! No separate fixtures needed.

### Step 2: Generate Workflow

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

The generated workflow will automatically include frontend testing steps!

## Configuration Options

### Frontend Section

```json
{
  "frontend": {
    "enabled": true,
    "test_command": "npm test",
    "test_data": {
      "source": "integration_framework",
      "schemas_path": "examples/schema",
      "models_config": ".github/config/integration-test-models.json"
    },
    "coverage": {
      "enabled": true,
      "command": "npm run test:coverage",
      "upload_artifacts": true
    },
    "additional_test_commands": []
  }
}
```

**Options:**

- `enabled` (boolean) - Enable/disable frontend testing
- `test_command` (string) - Command to run tests (default: `npm test`)
- `test_data.source` (string) - Data source (`integration_framework` for unified data)
- `test_data.schemas_path` (string) - Path to schema files (same as integration tests)
- `test_data.models_config` (string) - Path to models config with test data
- `coverage.enabled` (boolean) - Generate coverage reports
- `coverage.command` (string) - Coverage command
- `coverage.upload_artifacts` (boolean) - Upload coverage as CI artifacts
- `additional_test_commands` (array) - Additional commands to run (linting, type checking, etc.)

**Note:** Frontend tests now use the **same test data** as integration tests for consistency. No separate fixtures needed!

### PHPUnit Section

```json
{
  "phpunit": {
    "enabled": true,
    "test_command": "vendor/bin/phpunit",
    "coverage": {
      "enabled": true,
      "command": "vendor/bin/phpunit --coverage-text"
    },
    "testsuites": {
      "all": "vendor/bin/phpunit",
      "integration": "vendor/bin/phpunit --testsuite Integration",
      "controller": "vendor/bin/phpunit --testsuite Controller"
    }
  }
}
```

## Test Fixtures

### Why Use Fixtures?

Instead of inline mock data in tests, use JSON fixtures for:
- ✅ Realistic test data based on actual schemas
- ✅ Maintainability - update once, affects all tests
- ✅ Consistency across all tests
- ✅ Self-documenting data structure

### Fixture Structure

```
examples/test/vitest/
├── fixtures/          # Test data records
│   ├── products.json  # Sample product records
│   └── users.json     # Sample user records
└── schemas/           # Schema definitions
    ├── products.json  # Product model schema
    └── users.json     # User model schema
```

### Example Fixture - Schema

`examples/test/vitest/schemas/products.json`:

```json
{
  "model": "products",
  "title": "Product Management",
  "table": "products",
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "editable": false,
      "viewable": true,
      "listable": true
    },
    "name": {
      "type": "string",
      "label": "Product Name",
      "required": true,
      "editable": true,
      "viewable": true,
      "listable": true
    },
    "price": {
      "type": "decimal",
      "label": "Price",
      "required": true,
      "editable": true
    }
  }
}
```

### Example Fixture - Data

`examples/test/vitest/fixtures/products.json`:

```json
[
  {
    "id": 1,
    "name": "Test Product 1",
    "price": 99.99
  },
  {
    "id": 2,
    "name": "Test Product 2",
    "price": 149.99
  }
]
```

### Using Fixtures in Tests

Create `app/assets/tests/fixtures.ts`:

```typescript
import { readFileSync } from 'fs'
import { join } from 'path'

const FIXTURES_BASE_PATH = join(__dirname, '../../..', 'examples/test/vitest')

export function loadSchemaFixture(model: string): any {
  const filePath = join(FIXTURES_BASE_PATH, 'schemas', `${model}.json`)
  return JSON.parse(readFileSync(filePath, 'utf-8'))
}

export function loadDataFixture(model: string): any[] {
  const filePath = join(FIXTURES_BASE_PATH, 'fixtures', `${model}.json`)
  return JSON.parse(readFileSync(filePath, 'utf-8'))
}
```

Use in your tests:

```typescript
import { loadSchemaFixture, loadDataFixture } from './fixtures'

describe('My Component', () => {
  it('uses realistic data', () => {
    const schema = loadSchemaFixture('products')
    const products = loadDataFixture('products')
    
    // Test with real data structure
    expect(products[0]).toHaveProperty('name')
  })
})
```

## Generated Workflow Steps

When frontend testing is enabled, the workflow generator adds these steps:

```yaml
# Verify test fixtures exist
- name: Setup test fixtures
  run: |
    test -d "examples/test/vitest/fixtures"
    test -d "examples/test/vitest/schemas"

# Run frontend tests
- name: Run frontend tests
  run: npm test

# Generate coverage
- name: Generate coverage report
  run: npm run test:coverage
  continue-on-error: true

# Upload artifacts
- name: Upload coverage report
  uses: actions/upload-artifact@v4
  with:
    name: coverage-report
    path: _meta/_coverage
```

## Package.json Requirements

Your `package.json` must include these scripts:

```json
{
  "scripts": {
    "test": "vitest run",
    "test:watch": "vitest",
    "test:ui": "vitest --ui",
    "test:coverage": "vitest run --coverage"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.4",
    "@vue/test-utils": "^2.4.6",
    "happy-dom": "^15.11.7",
    "vitest": "^2.1.8"
  }
}
```

## Integration with PHPUnit Tests

Both test suites complement each other:

**Vitest Tests** → Component UI logic
- Component rendering and props
- User interactions and events
- Simple component behavior
- Uses JSON fixtures for data

**PHPUnit Tests** → API and backend logic
- API response structure
- Complete CRUD workflows
- Schema loading and validation
- Database operations

## CI/CD Workflow

The framework generates a workflow that:

1. **Sets up environment** - PHP, Node.js, MySQL
2. **Installs UserFrosting** - Creates project, installs sprinkle
3. **Runs backend tests** - PHPUnit integration tests
4. **Verifies fixtures** - Checks test data exists
5. **Runs frontend tests** - Vitest component tests
6. **Generates coverage** - Both frontend and backend
7. **Uploads artifacts** - Coverage reports, logs, screenshots

## Example: Complete Configuration

```json
{
  "sprinkle": {
    "name": "my-awesome-sprinkle",
    "composer_package": "myvendor/my-awesome-sprinkle",
    "npm_package": "@myvendor/my-awesome-sprinkle"
  },
  "testing": {
    "php_version": "8.1",
    "node_version": "20"
  },
  "frontend": {
    "enabled": true,
    "test_command": "npm test",
    "test_fixtures": {
      "enabled": true,
      "fixtures_path": "examples/test/vitest/fixtures",
      "schemas_path": "examples/test/vitest/schemas"
    },
    "coverage": {
      "enabled": true,
      "command": "npm run test:coverage",
      "upload_artifacts": true
    },
    "additional_test_commands": [
      "npm run lint",
      "npm run type-check"
    ]
  },
  "phpunit": {
    "enabled": true,
    "test_command": "vendor/bin/phpunit",
    "coverage": {
      "enabled": true
    },
    "testsuites": {
      "integration": "vendor/bin/phpunit --testsuite Integration"
    }
  }
}
```

## Best Practices

### 1. Keep Fixtures Simple
- Include only necessary fields
- Use realistic but simple data
- Provide 2-3 records per model

### 2. Test Structure
```
app/assets/tests/
├── fixtures.ts              # Fixture loader utility
├── fixtures.test.ts         # Fixture loader tests
├── setup.ts                 # Global test setup
├── components/              # Component tests
├── views/                   # View tests
└── composables/             # Composable tests
```

### 3. Mock Complex Dependencies
Use fixtures for data, but mock complex UserFrosting dependencies:
```typescript
vi.mock('@userfrosting/sprinkle-core/stores', () => ({
  useAlertStore: () => ({
    success: vi.fn()
  })
}))
```

### 4. Run Tests Locally First
```bash
# Install dependencies
npm install

# Run tests
npm test

# Watch mode
npm run test:watch

# Coverage
npm run test:coverage
```

## Troubleshooting

### Fixtures Not Found
**Error**: `Failed to load schema fixture for "model_name"`

**Solution**: Ensure fixture files exist at correct paths:
- `examples/test/vitest/schemas/model_name.json`
- `examples/test/vitest/fixtures/model_name.json`

### Tests Not Running in CI
**Check**:
1. `frontend.enabled` is `true` in config
2. `package.json` has `test` script
3. Dependencies are installed (`npm ci` runs before tests)
4. Workflow was regenerated after config changes

### Coverage Not Uploading
**Check**:
1. `coverage.upload_artifacts` is `true`
2. Coverage path matches actual output location
3. Coverage generation succeeds (check for errors)

## Migration from Standalone Vitest

If you already have Vitest tests and want to integrate with the framework:

1. **Keep your existing test structure** - No changes needed!
2. **Add fixtures** (optional):
   ```bash
   mkdir -p examples/test/vitest/{fixtures,schemas}
   ```
3. **Update config** to enable frontend testing
4. **Regenerate workflow** to include frontend steps

Your existing tests will run alongside the framework's PHPUnit tests automatically!

## Complete Example: CRUD6

See the CRUD6 sprinkle for a complete working example:
- Config: `integration-test-config.json`
- Fixtures: `examples/test/vitest/`
- Tests: `app/assets/tests/`
- Workflow: `.github/workflows/integration-test.yml`

## References

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils](https://test-utils.vuejs.org/)
- [UserFrosting 6 Testing](https://learn.userfrosting.com/testing)
- [CRUD6 Testing Framework](../.github/TESTING_FRAMEWORK.md)

## Summary

With JSON configuration:
- ✅ **1 config file** - Defines both frontend and backend testing
- ✅ **Auto-generated workflow** - No manual workflow editing
- ✅ **Reusable fixtures** - Realistic data for all tests
- ✅ **Complete CI/CD** - Tests, coverage, artifacts
- ✅ **Any sprinkle** - Framework works for all UF6 sprinkles

Frontend testing is now as simple as backend testing - just configure and run!
