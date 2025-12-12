# PR Summary: Fix SQL Seed Generation Issues

## Overview
This PR resolves three critical issues in the SQL seed generation script that were causing integration test failures in CI.

## Problems Addressed

### 1. SQL Error 1054 (42S22)
**Error**: `Unknown column 'permission_ids' in 'field list' at line 389`

**Root Cause**: Virtual/computed fields of type `multiselect` were being included in SQL INSERT statements. These fields (`permission_ids` in roles schema, `role_ids` in users schema) are form inputs for relationship synchronization, NOT actual database columns.

**Solution**: Added exclusion check in `shouldIncludeField()` function to skip multiselect type fields.

### 2. Null Password Column
**Problem**: Users table had null password values, preventing password feature testing.

**Root Cause**: Generated SQL used placeholder password hashes (`$2y$10$test.password.hash.N`) which are invalid (only 32 characters instead of 60).

**Solution**: Implemented proper bcrypt password hash generation with security warning.

### 3. MySQL Deprecation Warning
**Warning**: `'VALUES function' is deprecated and will be removed in a future release`

**Root Cause**: SQL was using deprecated `VALUES(column)` syntax in `ON DUPLICATE KEY UPDATE` clauses (deprecated in MySQL 8.0.20+).

**Solution**: Replaced with modern alias syntax (AS new_values / AS new_rel).

## Validation Results

All 7 validation tests passed:
1. ✅ No `permission_ids` field in SQL (0 occurrences)
2. ✅ No `role_ids` field in SQL (0 occurrences)
3. ✅ No deprecated `VALUES()` syntax (0 occurrences)
4. ✅ Found 3 bcrypt password hashes (valid 60-char format)
5. ✅ New alias syntax present (75 usages)
6. ✅ Scripts synchronized across directories
7. ✅ Documentation complete

## Expected CI Impact

### Before
- ❌ Integration tests fail at SQL seed loading
- ❌ MySQL deprecation warnings
- ❌ Password tests cannot run

### After
- ✅ Integration tests pass SQL seed loading
- ✅ No MySQL warnings
- ✅ Password tests work

## Files Modified
1. `.github/testing-framework/scripts/generate-seed-sql.js` - Core logic
2. `.github/scripts/generate-seed-sql.js` - Synchronized copy
3. `app/sql/seeds/crud6-test-data.sql` - Regenerated SQL
4. `.archive/SQL_SEED_GENERATION_FIX_2025-12-12.md` - Full documentation
5. `.archive/PR_SUMMARY.md` - This summary

## Risk: LOW
Changes isolated to test data generation only. Easy rollback available.
