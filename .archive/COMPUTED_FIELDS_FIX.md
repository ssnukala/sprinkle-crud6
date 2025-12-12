# Computed Fields SQL Generation Fix

## Issue
Integration tests were failing with:
```
ERROR 1054 (42S22) at line 173: Unknown column 'role_ids' in 'field list'
```

**Source:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20153670048/job/57851550907

## Root Cause
The SQL seed generator (`generate-seed-sql.js`) was including computed/virtual fields in INSERT statements. The `role_ids` field in both `permissions.json` and `users.json` schemas is marked as `"computed": true`, indicating it's:
- A virtual field used for relationship synchronization
- NOT a database column
- Only used during form submission to sync many-to-many relationships

## Solution
Updated the `shouldIncludeField()` function in all three copies of the SQL seed generator script to skip computed fields:

```javascript
function shouldIncludeField(fieldName, field) {
    // Skip auto-increment fields
    if (field.auto_increment) {
        return false;
    }
    
    // Skip computed/virtual fields (e.g., role_ids used for relationship sync)
    if (field.computed) {
        return false;
    }
    
    // ... rest of checks
}
```

## Files Modified
1. `.github/scripts/generate-seed-sql.js` (active script)
2. `.github/testing-framework/scripts/generate-seed-sql.js` (testing framework copy)
3. `.archive/pre-framework-migration/scripts-backup/generate-seed-sql.js` (archived backup)
4. `app/sql/seeds/crud6-test-data.sql` (regenerated without computed fields)

## Before/After Comparison

### Before (Incorrect)
```sql
INSERT INTO permissions (slug, name, conditions, description, role_ids)
VALUES ('test_slug_2', 'Test name 2', '', 'Test description for description - Record 2', NULL)
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), conditions = VALUES(conditions), description = VALUES(description), role_ids = VALUES(role_ids);
```

### After (Correct)
```sql
INSERT INTO permissions (slug, name, conditions, description)
VALUES ('test_slug_2', 'Test name 2', '', 'Test description for description - Record 2')
ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), conditions = VALUES(conditions), description = VALUES(description);
```

## Impact
- **File size reduction**: 36.08 KB → 33.42 KB
- **Affected tables**: `permissions` and `users` (both have `role_ids` computed field)
- **SQL statements affected**: 6 INSERT statements (3 for permissions, 3 for users)

## Testing
- ✅ JavaScript syntax validation passed for all 3 scripts
- ✅ SQL regeneration successful
- ✅ No `role_ids` references in generated SQL
- ⏳ Integration test validation pending

## Related Schema Fields
Currently only two fields are marked as `computed: true`:
1. `permissions.json` → `role_ids` (line 144-158)
2. `users.json` → `role_ids` (line 328-342)

Both are used for synchronizing many-to-many relationships via the relationship actions:
```json
"actions": {
    "on_update": {
        "sync": "role_ids",
        "description": "Sync user roles from form input"
    }
}
```

## Commit
- SHA: 256abfd
- Message: "Fix SQL seed generator: exclude computed fields like role_ids from INSERT statements"
- Date: 2025-12-12
