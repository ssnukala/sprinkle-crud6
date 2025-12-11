# Streamlined Workflow Guide

## Overview

The streamlined workflow template minimizes custom code to just **configuration** and **one custom step** (route configuration). Everything else is automated by the testing framework.

## What's Automated (95% of the workflow)

All these steps are **common across all sprinkles** and handled automatically:

### Infrastructure Setup
- ✅ PHP 8.1 setup with required extensions
- ✅ Node.js 20 setup
- ✅ MySQL 8.0 service
- ✅ UserFrosting 6 project creation
- ✅ Runtime directory creation and permissions

### Dependency Management
- ✅ Composer configuration (local path repositories)
- ✅ CRUD6 installation from local or remote
- ✅ Your sprinkle installation from local path
- ✅ NPM package installation
- ✅ Minimum stability and beta package handling

### Application Configuration
- ✅ `MyApp.php` modification (add CRUD6 + your sprinkle)
- ✅ `main.ts` modification (add CRUD6 sprinkle)
- ✅ `vite.config.ts` optimization (limax, lodash.deburr)
- ✅ `.env` database configuration
- ✅ Session handler setup

### Database Operations
- ✅ Database migrations
- ✅ **Schema-driven SQL generation** from JSON schemas
- ✅ SQL seed data loading
- ✅ Custom test data loading (app/tests/test-data.sql if exists)
- ✅ PHP seed execution
- ✅ Seed data validation
- ✅ Seed idempotency testing

### Testing
- ✅ Frontend asset building
- ✅ API endpoint testing
- ✅ Frontend route testing
- ✅ Playwright screenshot capture
- ✅ Log and screenshot artifact upload

## What You Customize (5% of the workflow)

### Required: Environment Variables (4 values)

```yaml
env:
  SPRINKLE_DIR: your-sprinkle-name      # Your sprinkle directory
  COMPOSER_PACKAGE: vendor/sprinkle     # Your composer package
  NPM_PACKAGE: @vendor/sprinkle         # Your npm package (if you have one)
  SCHEMA_PATH: ""                       # Empty = app/schema/crud6/ (default)
```

### Optional: Route Configuration Pattern (1 custom step)

This is the **ONLY** step you might need to customize based on your sprinkle's route pattern:

#### Pattern 1: Simple Array Import (Like CRUD6)

```yaml
- name: Configure routes
  run: |
    cd userfrosting
    
    # Simple array spread pattern
    sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import MyRoutes from '@vendor\/my-sprinkle\/routes'" app/assets/router/index.ts
    sed -i '/\.\.\.AccountRoutes,/a \            ...MyRoutes,' app/assets/router/index.ts
```

#### Pattern 2: Factory Function (Like C6Admin)

```yaml
- name: Configure routes
  run: |
    cd userfrosting
    
    # Factory function pattern
    sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import { createMyRoutes } from '@vendor\/my-sprinkle\/routes'" app/assets/router/index.ts
    sed -i "/import Layout from '@userfrosting\/theme-adminlte\/layouts\/Layout.vue'/a const MyRoutes = createMyRoutes({ layoutComponent: Layout });" app/assets/router/index.ts
    sed -i '/\.\.\.AccountRoutes,/a \            ...MyRoutes,' app/assets/router/index.ts
```

#### Pattern 3: Nested Routes

```yaml
- name: Configure routes
  run: |
    cd userfrosting
    
    # Custom nested pattern - adjust as needed
    # Your specific route configuration here
```

### Optional: Custom Test Data

If you need additional test data beyond what's generated from schemas, create:

```
app/tests/test-data.sql
```

This SQL file will be automatically loaded if it exists. Use it for:
- Complex relationships not captured in schemas
- Edge case test data
- Specific test scenarios
- Mock data for testing

## Workflow Structure

```
┌─────────────────────────────────────────────────────────────┐
│ Environment Variables (4 values) - YOU CUSTOMIZE            │
├─────────────────────────────────────────────────────────────┤
│ Infrastructure Setup (PHP, Node, MySQL) - AUTOMATED         │
├─────────────────────────────────────────────────────────────┤
│ UserFrosting Installation - AUTOMATED                       │
├─────────────────────────────────────────────────────────────┤
│ Composer Configuration - AUTOMATED                          │
├─────────────────────────────────────────────────────────────┤
│ NPM Configuration - AUTOMATED                               │
├─────────────────────────────────────────────────────────────┤
│ MyApp.php Configuration - AUTOMATED                         │
├─────────────────────────────────────────────────────────────┤
│ main.ts Configuration - AUTOMATED                           │
├─────────────────────────────────────────────────────────────┤
│ Routes Configuration - YOU CUSTOMIZE (1 step)               │
├─────────────────────────────────────────────────────────────┤
│ vite.config.ts Configuration - AUTOMATED                    │
├─────────────────────────────────────────────────────────────┤
│ Environment Setup - AUTOMATED                               │
├─────────────────────────────────────────────────────────────┤
│ Database Migrations - AUTOMATED                             │
├─────────────────────────────────────────────────────────────┤
│ Schema-Driven SQL Generation - AUTOMATED                    │
├─────────────────────────────────────────────────────────────┤
│ Custom Test Data Loading (optional) - AUTOMATED             │
├─────────────────────────────────────────────────────────────┤
│ PHP Seed Execution - AUTOMATED                              │
├─────────────────────────────────────────────────────────────┤
│ Seed Validation - AUTOMATED                                 │
├─────────────────────────────────────────────────────────────┤
│ Idempotency Testing - AUTOMATED                             │
├─────────────────────────────────────────────────────────────┤
│ Frontend Build - AUTOMATED                                  │
├─────────────────────────────────────────────────────────────┤
│ Path Testing (API + Frontend) - AUTOMATED                   │
├─────────────────────────────────────────────────────────────┤
│ Screenshot Capture - AUTOMATED                              │
├─────────────────────────────────────────────────────────────┤
│ Artifact Upload - AUTOMATED                                 │
└─────────────────────────────────────────────────────────────┘
```

## Quick Start

### Step 1: Copy Template

```bash
cp .github/testing-framework/streamlined-workflow-template.yml \
   .github/workflows/integration-test.yml
```

### Step 2: Configure (Edit 4 values)

```yaml
env:
  SPRINKLE_DIR: my-sprinkle
  COMPOSER_PACKAGE: myvendor/my-sprinkle
  NPM_PACKAGE: @myvendor/my-sprinkle
  SCHEMA_PATH: ""  # or "custom/path" if not using app/schema/crud6/
```

### Step 3: Customize Route Pattern (If needed)

Find the "Configure routes" step and uncomment/modify the pattern that matches your sprinkle:
- Pattern 1: Simple array (default, works for most)
- Pattern 2: Factory function (if you use createRoutes pattern)
- Pattern 3: Custom (for complex cases)

### Step 4: Add Custom Test Data (Optional)

Create `app/tests/test-data.sql` if you need additional test data beyond schemas.

### Step 5: Ensure Schemas Exist

Make sure your sprinkle has schema files in:
- `app/schema/crud6/` (default), or
- Custom location specified in `SCHEMA_PATH`

### Step 6: Push and Run!

```bash
git add .github/workflows/integration-test.yml
git commit -m "Add CRUD6 integration testing"
git push
```

The workflow runs automatically on push to main/develop.

## Schema-Driven Testing

The framework automatically generates SQL seed data from your CRUD6 JSON schemas:

```
app/schema/crud6/
├── users.json       → generates user test data
├── groups.json      → generates group test data
├── roles.json       → generates role test data
├── permissions.json → generates permission test data
└── custom.json      → generates custom model test data
```

**No manual SQL writing required!** The framework:
1. Reads your schema files
2. Generates appropriate INSERT statements
3. Respects field types and validation rules
4. Creates idempotent SQL (safe to re-run)
5. Starts IDs from 2 (avoids system record conflicts)

## Advanced: Custom Test Data

If schemas don't cover all your test scenarios, create `app/tests/test-data.sql`:

```sql
-- app/tests/test-data.sql
-- Additional test data not covered by schemas

-- Complex many-to-many relationships
INSERT INTO user_groups (user_id, group_id) VALUES (2, 3), (3, 2);

-- Edge cases
INSERT INTO users (username, email, first_name, last_name) 
VALUES ('edge-case', 'edge@test.com', 'Edge', 'Case');

-- Mock external data
INSERT INTO external_integrations (name, api_key, enabled) 
VALUES ('test-api', 'test-key-123', 1);
```

This file is loaded automatically after schema-driven SQL.

## Comparison: Before vs After

### Before (Manual Workflow - 583 lines)

```yaml
# Lots of hardcoded steps
# Difficult to maintain
# Different for each sprinkle
# Lots of duplication
```

### After (Streamlined - ~400 lines, 95% automated)

```yaml
env:
  SPRINKLE_DIR: my-sprinkle        # 1
  COMPOSER_PACKAGE: vendor/sprinkle # 2
  NPM_PACKAGE: @vendor/sprinkle    # 3
  SCHEMA_PATH: ""                  # 4

# ... automated infrastructure setup ...

- name: Configure routes            # Only custom step!
  run: |
    # Your route pattern here (3-5 lines)

# ... automated testing ...
```

## Benefits

| Aspect | Manual Approach | Streamlined Approach |
|--------|----------------|---------------------|
| Configuration | Hardcoded throughout | 4 env variables |
| Custom Steps | ~40% of workflow | 1 step (routes) |
| SQL Generation | Manual SQL files | Auto-generated from schemas |
| Vite Config | Manual editing | Automated |
| Composer/NPM | Manual config | Automated |
| Maintenance | High (many changes) | Low (config only) |
| Consistency | Varies per sprinkle | Standardized |
| Setup Time | Hours | 5-10 minutes |

## Troubleshooting

### Schema Path Issues

**Error**: "Schema directory not found"

**Solution**: Ensure `SCHEMA_PATH` points to correct location:
```yaml
# For app/schema/crud6/
SCHEMA_PATH: ""

# For custom location
SCHEMA_PATH: "examples/schema"
```

### Route Configuration Issues

**Error**: Routes not loading in frontend

**Solution**: Check your route pattern matches your sprinkle:
1. Review your sprinkle's route export pattern
2. Use correct pattern (array vs factory vs custom)
3. Verify import paths match your package name

### Custom Test Data Not Loading

**Check**: File exists at `app/tests/test-data.sql`
**Check**: SQL syntax is valid
**Check**: Workflow logs show "Loading custom test data"

### NPM Package Issues

**Note**: NPM package is optional. If your sprinkle doesn't have one:
- Workflow will log "Sprinkle NPM package optional"
- This is normal and not an error
- Only PHP composer package is required

## Examples

### Example 1: C6Admin Pattern

```yaml
env:
  SPRINKLE_DIR: sprinkle-c6admin
  COMPOSER_PACKAGE: ssnukala/sprinkle-c6admin
  NPM_PACKAGE: @ssnukala/sprinkle-c6admin
  SCHEMA_PATH: ""  # Uses app/schema/crud6/

- name: Configure routes
  run: |
    cd userfrosting
    # Factory function pattern for c6admin
    sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import { createC6AdminRoutes } from '@ssnukala\/sprinkle-c6admin\/routes'" app/assets/router/index.ts
    sed -i "/import Layout from '@userfrosting\/theme-adminlte\/layouts\/Layout.vue'/a const C6AdminRoutes = createC6AdminRoutes({ layoutComponent: Layout });" app/assets/router/index.ts
    sed -i '/\.\.\.AccountRoutes,/a \            ...C6AdminRoutes,' app/assets/router/index.ts
```

### Example 2: Simple Sprinkle

```yaml
env:
  SPRINKLE_DIR: my-simple-sprinkle
  COMPOSER_PACKAGE: myvendor/simple-sprinkle
  NPM_PACKAGE: ""  # No NPM package
  SCHEMA_PATH: ""

- name: Configure routes
  run: |
    cd userfrosting
    # Simple array pattern (default)
    sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import SimpleRoutes from '@myvendor\/simple-sprinkle\/routes'" app/assets/router/index.ts
    sed -i '/\.\.\.AccountRoutes,/a \            ...SimpleRoutes,' app/assets/router/index.ts
```

### Example 3: CRUD6 Itself

```yaml
env:
  SPRINKLE_DIR: sprinkle-crud6
  COMPOSER_PACKAGE: ssnukala/sprinkle-crud6
  NPM_PACKAGE: @ssnukala/sprinkle-crud6
  SCHEMA_PATH: "examples/schema"  # Custom location

- name: Configure routes
  run: |
    cd userfrosting
    # CRUD6 uses simple array pattern
    sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'" app/assets/router/index.ts
    sed -i '/\.\.\.AccountRoutes,/a \            ...CRUD6Routes,' app/assets/router/index.ts
```

## Migration from Custom Workflow

If you have an existing custom workflow:

1. **Identify your configuration**: Extract env variables
2. **Identify your route pattern**: Which pattern do you use?
3. **Identify custom SQL**: Move to app/tests/test-data.sql
4. **Replace workflow**: Use streamlined template
5. **Test**: Run workflow and verify output

Most of your existing workflow code becomes **configuration** or is **automated**.

## Summary

**You configure**: 4 environment variables + 1 route pattern step
**Framework handles**: Everything else (95% of the workflow)

This approach:
- ✅ Minimizes custom code
- ✅ Standardizes across sprinkles
- ✅ Reduces maintenance burden
- ✅ Makes updates easy (re-run installer)
- ✅ Keeps sprinkle-specific logic minimal
- ✅ Automates complex tasks (SQL generation, vite config, etc.)

**Result**: Professional integration testing in 5-10 minutes instead of hours/days!
