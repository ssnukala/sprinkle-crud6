# CRUD6 Seed Validation Fix Summary

**Date:** 2025-12-12  
**Issue:** Integration test failure - crud6-admin role and permissions not found after successful seed execution

## Problem Description

The integration test showed a confusing pattern:
- ✅ DefaultRoles seed reported "Seed successful!"
- ✅ DefaultPermissions seed reported "Seed successful!"
- ❌ Validation failed: Role 'crud6-admin' count mismatch. Expected: 1, Found: 0
- ❌ Validation failed: Permission count mismatch. Expected: 6, Found: 0

## Root Cause Analysis

### Execution Order
```
1. Run migrations (create tables)
2. Run PHP seeds (DefaultRoles, DefaultPermissions) ✅ Creates crud6-admin role
3. Create admin user
4. Generate and load DDL SQL (CREATE TABLE IF NOT EXISTS)
5. Generate and load seed data SQL ❌ OVERWRITES crud6-admin!
6. Validate ❌ crud6-admin is gone!
```

### The Problem

The SQL seed generation script (`generate-seed-sql.js`) was using:

```javascript
INSERT INTO `roles` (...) 
VALUES (...) AS new_values
ON DUPLICATE KEY UPDATE `field1` = new_values.`field1`, ...;
```

This `ON DUPLICATE KEY UPDATE` clause would:
1. Try to insert a new role with ID 2
2. Find that ID 2 might already exist (from auto-increment)
3. UPDATE the existing row, potentially overwriting crud6-admin data

Even worse, if the roles.json schema had any seed data, it would generate SQL that could conflict with or overwrite the PHP seed data.

## Solution

### 1. Use INSERT IGNORE Instead

Changed from:
```sql
INSERT INTO `roles` (...) VALUES (...) AS new_values
ON DUPLICATE KEY UPDATE ...;
```

To:
```sql
INSERT IGNORE INTO `roles` (...) VALUES (...);
```

This preserves existing data - if a row exists, it's simply skipped.

### 2. Explicit ID Management

Changed test data to start from ID 100 instead of ID 2:

**Before:**
- ID 1: Admin user (reserved)
- ID 2-4: Test data
- **Problem:** IDs 2-4 might conflict with PHP seed data

**After:**
- IDs 1-99: Reserved for PHP seed data (roles, permissions, groups, etc.)
- IDs 100+: Test data
- **Benefit:** No conflicts possible!

### 3. Explicitly Set IDs

Instead of relying on auto-increment, now explicitly set IDs:

```javascript
// Always include ID field
if (fieldName === 'id' || field.auto_increment) {
    return true;  // Changed from: return false
}

// Generate INSERT with explicit ID
const recordId = i + 100;  // Changed from: i + 2
values.push(recordId);     // Explicitly set ID value
```

### 4. Update Relationships

Updated pivot table inserts to use the new ID range:

**Before:**
```javascript
const testRelationships = [
    [2, 2],
    [3, 2],
    [3, 3],
];
```

**After:**
```javascript
const testRelationships = [
    [100, 100],
    [101, 100],
    [101, 101],
];
```

## Diagnostic Improvements

Added `display-roles-permissions.php` script to show database state at three checkpoints:

1. **After PHP seeds** - Should show crud6-admin role and 6 permissions
2. **After DDL loading** - Should still show crud6-admin (CREATE TABLE IF NOT EXISTS)
3. **After seed-data loading** - Should still show crud6-admin (INSERT IGNORE)

This will help identify exactly when/if data disappears.

## Files Modified

1. `.github/testing-framework/scripts/generate-seed-sql.js`
   - Changed to INSERT IGNORE
   - Start IDs from 100
   - Explicitly set ID values
   - Updated relationships to use IDs 100+

2. `.github/testing-framework/scripts/load-seed-sql.php`
   - Updated messages about ID ranges

3. `.github/workflows/integration-test.yml`
   - Added diagnostic steps at 3 checkpoints

4. `.github/testing-framework/scripts/display-roles-permissions.php` (NEW)
   - Displays all roles and permissions
   - Checks for crud6-admin specifically
   - Shows role-permission assignments

## Expected Results

After these fixes:

1. PHP seeds create crud6-admin role (IDs in range 1-99)
2. DDL generation creates tables with IF NOT EXISTS (no data loss)
3. Seed data generation creates test records with IDs 100+ (no conflicts)
4. INSERT IGNORE preserves existing PHP seed data
5. Validation passes because crud6-admin still exists

## Testing Plan

1. Run integration test workflow
2. Check diagnostic output at each checkpoint:
   - After PHP seeds: crud6-admin should exist
   - After DDL: crud6-admin should still exist
   - After seed-data: crud6-admin should still exist
3. Validation should pass

## Lessons Learned

1. **INSERT...ON DUPLICATE KEY UPDATE is dangerous** when mixing PHP seeds and SQL seeds
2. **ID ranges should be clearly separated** - reserve low IDs for system data
3. **Explicit IDs are better than auto-increment** for test data with relationships
4. **Diagnostic logging is essential** when debugging seed data issues
5. **Integration test order matters** - seeds that create system data must run before test data

## References

- Integration Test Workflow: `.github/workflows/integration-test.yml`
- Seed Config: `.github/config/integration-test-seeds.json`
- GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20155611764
