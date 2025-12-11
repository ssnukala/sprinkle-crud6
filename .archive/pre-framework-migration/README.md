# Pre-Framework Migration Backup

This directory contains backups of the CRUD6 sprinkle's integration testing files **before** migrating to use the reusable testing framework.

## Backup Date
December 11, 2024

## What's Backed Up

### 1. Integration Test Workflow
- **File**: `integration-test.yml.backup`
- **Original Location**: `.github/workflows/integration-test.yml`
- **Size**: ~52KB (1182 lines)
- **Description**: The original custom integration test workflow before framework migration

### 2. Integration Test Scripts
- **Directory**: `scripts-backup/`
- **Original Location**: `.github/scripts/`
- **Description**: All custom integration test scripts before framework standardization

**Scripts included:**
- `check-seeds-modular.php` - Seed validation (now in framework)
- `check-seeds.php` - Original seed checking
- `generate-seed-sql.js` - SQL generation from schemas (now in framework)
- `load-seed-sql.php` - SQL loading (now in framework)
- `run-seeds.php` - Seed execution (now in framework)
- `test-paths.php` - Path testing (now in framework)
- `test-seed-idempotency-modular.php` - Idempotency testing (now in framework)
- `take-screenshots-modular.js` - Screenshot capture (now in framework)
- And various other helper scripts

## Why This Backup Exists

The CRUD6 sprinkle originally had custom integration testing infrastructure that was later packaged into a reusable framework for other sprinkles. As part of "eating our own dog food," we migrated CRUD6's own tests to use the framework.

This backup preserves:
1. The original workflow for reference
2. Historical script versions
3. Ability to compare before/after
4. Rollback capability if needed

## Migration Changes

**Before (Custom):**
- 1182 line custom workflow
- Hardcoded steps and logic
- CRUD6-specific implementation
- Difficult to maintain

**After (Framework-based):**
- ~450 line workflow using framework
- Reusable scripts from `.github/testing-framework/scripts/`
- Same framework other sprinkles use
- Easier to maintain
- Demonstrates framework capabilities

## Restoration

If you need to restore the original files:

```bash
# Restore workflow
cp .archive/pre-framework-migration/integration-test.yml.backup \
   .github/workflows/integration-test.yml

# Restore scripts (if needed)
cp -r .archive/pre-framework-migration/scripts-backup/* .github/scripts/
```

## See Also

- `.github/testing-framework/` - The reusable framework created from these files
- `.github/workflows/integration-test.yml` - New framework-based workflow
- `.github/testing-framework/docs/MIGRATION.md` - Migration guide for other sprinkles

## Note

These files are kept for historical reference and comparison purposes. The framework versions in `.github/testing-framework/` are the maintained, current versions.
