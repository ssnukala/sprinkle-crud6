# Frontend + Backend Testing - Now Reusable for Any UF6 Sprinkle!

## Summary

The CRUD6 testing infrastructure is now **fully reusable** for any UserFrosting 6 sprinkle. With a single JSON configuration file, you get:

✅ **Frontend Testing** (Vitest) - Vue component tests with JSON fixtures  
✅ **Backend Testing** (PHPUnit) - API integration tests  
✅ **CI/CD Automation** - GitHub Actions workflow auto-generated  
✅ **Test Fixtures** - Realistic JSON data for tests  
✅ **Complete Coverage** - Frontend + backend + integration

## What Was Added

### 1. Frontend Testing Configuration

Added to `integration-test-config.json`:

```json
{
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
    }
  },
  "phpunit": {
    "enabled": true,
    "test_command": "vendor/bin/phpunit",
    "testsuites": {
      "integration": "vendor/bin/phpunit --testsuite Integration"
    }
  }
}
```

### 2. Test Fixtures System

Created reusable fixture structure:

```
examples/test/vitest/
├── fixtures/          # Test data records (JSON)
│   ├── products.json
│   └── users.json
└── schemas/           # Schema definitions (JSON)
    ├── products.json
    └── users.json
```

**Fixture loader utility** (`app/assets/tests/fixtures.ts`):
- `loadSchemaFixture(model)` - Load schema definitions
- `loadDataFixture(model)` - Load test data
- `getEditableFields(schema)` - Extract editable fields
- `getViewableFields(schema)` - Extract viewable fields
- `filterFields(data, fields)` - Filter record data

### 3. Documentation

Created comprehensive guides:
- **FRONTEND_TESTING.md** - Complete frontend testing guide for any sprinkle
- Updated **README.md** - Added frontend testing section
- Updated **integration-test-config.json** - Template with frontend options

## How to Use in Your Sprinkle

### Step 1: Install Framework

```bash
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

### Step 2: Create Configuration

Create `integration-test-config.json` in your sprinkle root:

```json
{
  "sprinkle": {
    "name": "my-sprinkle",
    "composer_package": "vendor/my-sprinkle",
    "npm_package": "@vendor/my-sprinkle"
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
      "upload_artifacts": true
    }
  },
  "phpunit": {
    "enabled": true,
    "test_command": "vendor/bin/phpunit"
  }
}
```

### Step 3: Setup Test Fixtures (Optional)

Copy fixture templates from CRUD6:

```bash
mkdir -p examples/test/vitest/{fixtures,schemas}
cp -r /path/to/crud6/examples/test/vitest/* examples/test/vitest/
```

Edit fixtures to match your models.

### Step 4: Generate Workflow

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

### Step 5: Push and Test!

```bash
git add .
git commit -m "Add frontend and backend testing"
git push
```

Your CI/CD workflow now runs:
- ✅ Vitest component tests
- ✅ PHPUnit integration tests
- ✅ Coverage reports
- ✅ Artifact uploads

## Example: Complete Working Implementation

See CRUD6 sprinkle for a complete example:

**Configuration:**
- `integration-test-config.json` - Main config with frontend + backend
- `.github/testing-framework/config/` - Templates

**Frontend Tests:**
- `app/assets/tests/*.test.ts` - Component tests
- `app/assets/tests/fixtures.ts` - Fixture loader
- `examples/test/vitest/` - Test fixtures

**Backend Tests:**
- `app/tests/Integration/FrontendComponentDataTest.php` - API tests for frontend

**Generated Workflow:**
- `.github/workflows/vitest-tests.yml` - Frontend CI (manually created initially)
- `.github/workflows/phpunit-tests.yml` - Backend CI
- `.github/workflows/integration-test.yml` - Full integration CI

## Key Benefits

### For Sprinkle Developers

**Before:**
- Manual workflow creation
- Inline test mocks everywhere
- Separate frontend/backend test setup
- Hard to maintain test data

**After:**
- JSON config → auto-generated workflow
- Reusable JSON fixtures
- Unified frontend + backend testing
- Update once, affects all tests

### Configuration-Driven

**Just like CRUD6 makes CRUD operations configurable:**
```json
{
  "model": "Product",
  "fields": { "name": "string" }
}
```

**Testing is now configurable:**
```json
{
  "frontend": { "enabled": true },
  "phpunit": { "enabled": true }
}
```

**Same philosophy. Same simplicity.**

## What Makes This Reusable

### 1. Framework-Agnostic Fixtures

Fixtures use standard JSON - works for any sprinkle:
```json
// Any sprinkle can use this structure
{
  "model": "your_model",
  "fields": { /* your fields */ }
}
```

### 2. Configuration-Based

All sprinkle-specific details in JSON config:
- Sprinkle name
- Package names
- Test commands
- Fixture paths
- Coverage settings

### 3. Template-Based

Start from templates and customize:
- Copy template config
- Replace placeholders
- Generate workflow
- Done!

### 4. Documentation

Every feature documented with examples:
- Config options explained
- Usage examples provided
- Troubleshooting guides included
- Migration paths documented

## Testing Philosophy

### Two-Layer Approach

**Vitest (Frontend)** → Fast unit tests for UI:
- Component rendering
- User interactions
- Props and events
- **Uses JSON fixtures for data**

**PHPUnit (Backend)** → Integration tests for API:
- API response structure
- Database operations
- Complete workflows
- **Tests real backend with DB**

### Why Both?

- **Vitest** catches UI bugs fast
- **PHPUnit** catches integration issues
- **Together** provide complete coverage
- **Both** automated in CI

## Migration Path

### If You Have Existing Tests

**Frontend tests (Vitest):**
1. Enable in config: `"frontend": { "enabled": true }`
2. Add fixtures (optional): Copy from CRUD6
3. Regenerate workflow
4. Push - tests run automatically!

**Backend tests (PHPUnit):**
1. Enable in config: `"phpunit": { "enabled": true }`
2. No changes needed to existing tests
3. Regenerate workflow
4. Push - tests run automatically!

**No breaking changes** - your existing tests continue to work!

## Future Enhancements

Possible additions:
- [ ] E2E testing with Playwright
- [ ] Visual regression testing
- [ ] Performance testing
- [ ] Accessibility testing
- [ ] Load testing

All would follow the same pattern:
1. Add to config JSON
2. Framework handles the rest
3. Reusable for all sprinkles

## Files Modified

### Configuration
- ✅ `.github/testing-framework/config/integration-test-config.json` - Added frontend section
- ✅ `integration-test-config.json` - Updated with frontend options

### Documentation
- ✅ `.github/testing-framework/docs/FRONTEND_TESTING.md` - Complete frontend guide (11KB)
- ✅ `.github/testing-framework/README.md` - Updated with frontend info
- ✅ `.github/testing-framework/docs/FRONTEND_FIXTURES_SUMMARY.md` - This file

### Test Infrastructure
- ✅ `app/assets/tests/fixtures.ts` - Fixture loader utility
- ✅ `app/assets/tests/fixtures.test.ts` - Fixture tests
- ✅ `examples/test/vitest/` - Example fixtures

### Workflows
- ✅ `.github/workflows/vitest-tests.yml` - Frontend CI workflow

## Resources

**Documentation:**
- [Frontend Testing Guide](.github/testing-framework/docs/FRONTEND_TESTING.md)
- [Framework README](.github/testing-framework/README.md)
- [JSON-Driven Testing](.github/testing-framework/docs/JSON_DRIVEN_TESTING.md)

**Examples:**
- CRUD6 sprinkle - Complete working implementation
- Template configs - In `.github/testing-framework/config/`

**Support:**
- GitHub Issues - [sprinkle-crud6/issues](https://github.com/ssnukala/sprinkle-crud6/issues)
- UserFrosting Forums - [forums.userfrosting.com](https://forums.userfrosting.com/)

## Conclusion

Frontend testing is now integrated into the reusable UF6 testing framework!

**For users**: Configure once, test everywhere  
**For developers**: Maintain once, benefit all sprinkles  
**For community**: Consistent testing across ecosystem

**Just like CRUD6 makes CRUD simple, the testing framework makes testing simple.**

🎉 **Complete testing infrastructure for any UF6 sprinkle in ~50 lines of JSON!**
