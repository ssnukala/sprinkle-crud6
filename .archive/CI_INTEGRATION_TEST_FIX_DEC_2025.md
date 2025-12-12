# CI Integration Test Fix - December 2025

## Problem Statement
CI integration test failures in workflow run https://github.com/ssnukala/sprinkle-crud6/actions/runs/20174915418/job/57920321896

Two specific issues:
1. `app/assets/router/index.ts` in the UserFrosting 6 installation did not have `...CRUD6Routes` entry
2. DDL SQL (CREATE TABLE statements) were not being executed before seed SQL (INSERT statements), causing error: `Table 'userfrosting_test.categories' doesn't exist`

## Root Cause
The workflow file `.github/workflows/integration-test.yml` is **AUTO-GENERATED** from `integration-test-config.json` using the script `.github/testing-framework/scripts/generate-workflow.js`.

PR #289 added DDL generation support to `generate-workflow.js`, but the workflow file was **never regenerated** after that PR was merged. The comment at the top of the workflow file clearly states:

```yaml
# AUTO-GENERATED from integration-test-config.json
# To regenerate: node .github/testing-framework/scripts/generate-workflow.js
```

## Solution
Regenerated the workflow file by running:
```bash
node .github/testing-framework/scripts/generate-workflow.js
```

## Changes Made

### 1. Router Injection Fix
**File**: `.github/workflows/integration-test.yml` line 159

**Before**:
```yaml
sed -i '/\\.\\.\\.AccountRoutes,/a \\            ...CRUD6Routes,' app/assets/router/index.ts
```

**After**:
```yaml
sed -i "/\\.\\.\\.AccountRoutes,/a \\            ...CRUD6Routes," app/assets/router/index.ts
```

**Why**: Changed from single quotes to double quotes to enable bash variable expansion. The pattern `${importName}` and `${sprinkle.name}Routes` need double quotes to interpolate properly.

### 2. DDL Generation Step Added
**File**: `.github/workflows/integration-test.yml` lines 205-229

**New step added** before seed data loading:

```yaml
- name: Generate and create tables from schemas
  run: |
    cd userfrosting
    
    SCHEMA_DIR="../${{ env.SPRINKLE_DIR }}/${{ env.SCHEMA_PATH }}"
    if [ -z "${{ env.SCHEMA_PATH }}" ]; then
      SCHEMA_DIR="../${{ env.SPRINKLE_DIR }}/app/schema/crud6"
    fi
    
    if [ ! -d "$SCHEMA_DIR" ]; then
      echo "❌ ERROR: Schema directory not found: $SCHEMA_DIR"
      exit 1
    fi
    
    echo "✅ Using schemas from: $SCHEMA_DIR"
    
    # Generate DDL (CREATE TABLE statements) from schemas
    node ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/generate-ddl-sql.js \
      "$SCHEMA_DIR" tables.sql
    
    # Create tables from DDL
    php ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/load-seed-sql.php \
      tables.sql
    
    echo "✅ Tables created from schemas"
```

## Execution Sequence
The correct sequence after the fix:

1. **Run migrations** (line 200-203)
   - Creates UserFrosting core tables (users, roles, permissions, etc.)
   - Uses: `php bakery migrate --force`

2. **Generate and create tables from schemas** (line 205-229) ← **NEW STEP**
   - Reads JSON schema files from `examples/schema/`
   - Generates CREATE TABLE statements (DDL)
   - Executes DDL to create CRUD6 test tables
   - Uses: `generate-ddl-sql.js` + `load-seed-sql.php`

3. **Generate and load SQL seed data** (line 231-248)
   - Generates INSERT statements from schemas
   - Loads test data into tables
   - Uses: `generate-seed-sql.js` + `load-seed-sql.php`

4. **Run PHP seeds** (line 250-254)
   - Runs additional PHP-based seeders
   - Uses: `run-seeds.php` + config JSON

## Why This Fix Works

### Router Issue
The sed command now properly expands bash variables because it uses double quotes. This allows the workflow generator to dynamically inject the correct route names.

### DDL Issue
Tables must exist before data can be inserted. By adding the DDL generation step BEFORE the seed data step, we ensure:
1. Core UserFrosting tables are created by migrations
2. CRUD6 test tables are created from JSON schemas (DDL)
3. Test data is inserted into existing tables (seed SQL)
4. Additional seeding runs if needed (PHP seeds)

## Testing Framework Context
This sprinkle uses a **configuration-driven testing framework** introduced after the old manual workflow. The framework:
- Reads configuration from `integration-test-config.json`
- Generates workflow steps from templates in `generate-workflow.js`
- Provides reusable scripts in `.github/testing-framework/scripts/`
- Allows easy adaptation for other sprinkles

The old working workflow (`.archive/pre-framework-migration/integration-test.yml.backup`) did NOT have DDL generation because it relied on PHP migrations. The new framework generates DDL from JSON schemas dynamically, which is more flexible but requires the DDL step to be executed.

## References
- **PR #289**: https://github.com/ssnukala/sprinkle-crud6/pull/289 - Added DDL generation to `generate-workflow.js`
- **Failed CI Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20174915418
- **Working CI Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20174915418 (after this fix)
- **DDL Generator**: `.github/testing-framework/scripts/generate-ddl-sql.js`
- **Seed Generator**: `.github/testing-framework/scripts/generate-seed-sql.js`
- **SQL Loader**: `.github/testing-framework/scripts/load-seed-sql.php`

## Key Takeaway
**Always regenerate the workflow file after modifying `generate-workflow.js`!**

The workflow file is auto-generated and should never be manually edited. Any changes to the testing framework must go through:
1. Update `generate-workflow.js` or `integration-test-config.json`
2. Run `node .github/testing-framework/scripts/generate-workflow.js`
3. Commit the regenerated workflow file
