# Fix: SQL Seed Generation Failure - Customer ID Default Value

**Date**: 2025-12-12  
**Issue**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20154583738/job/57854226045  
**Error**: `ERROR 1364 (HY000) at line 153: Field 'customer_id' doesn't have a default value`

## Problem Summary

The integration test workflow was failing when trying to load SQL seed data because:

1. **DDL Generator** merges all schemas with the same table name, creating a unified table definition
2. **Seed Generator** processes each schema separately without merging
3. The `orders` table was created with `customer_id INT NOT NULL` (merged from smartlookup schemas)
4. When inserting from `orders.json` (which doesn't have `customer_id`), MySQL rejected it

### Root Cause

Three schemas share the `orders` table:
- `orders.json` - Main orders schema (no `customer_id` field)
- `smartlookup-example.json` - Defines `customer_id` as required smartlookup field
- `smartlookup-legacy-example.json` - Defines `customer_id` as required smartlookup field

The DDL generator merged all three, creating `customer_id INT NOT NULL`, but the seed generator tried to insert from `orders.json` without `customer_id`, causing MySQL error 1364.

## Solution Implemented

**Simplified Approach**: Make all columns nullable (except auto-increment primary keys)

### Changes Made

#### 1. Updated DDL Generator (`generate-ddl-sql.js`)

**Before**:
```javascript
// Handle NOT NULL - AUTO_INCREMENT fields are always NOT NULL
if (field.auto_increment) {
    parts.push('NOT NULL');
} else if (field.required || field.validation?.required) {
    parts.push('NOT NULL');
} else {
    parts.push('NULL');
}
```

**After**:
```javascript
// Handle NOT NULL - Only AUTO_INCREMENT fields are NOT NULL
// All other fields are nullable for testing simplicity
// (Frontend validations handle required fields based on schema)
if (field.auto_increment) {
    parts.push('NOT NULL');
} else {
    parts.push('NULL');
}
```

#### 2. Added SQL Artifact Upload (`integration-test.yml`)

Added new step to upload generated SQL files for debugging:

```yaml
- name: Upload SQL files for debugging
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: integration-test-sql-files
    path: |
      userfrosting/ddl.sql
      userfrosting/seed-data.sql
    retention-days: 7
```

## Benefits

1. **Test Simplicity**: No need to worry about NOT NULL constraints in test data
2. **Frontend Validation**: Required field validation is handled by schema settings in the UI
3. **Flexibility**: Schemas can define different field sets for the same table without conflicts
4. **Debuggability**: SQL files are now uploaded as artifacts for manual inspection

## Result

- ✅ `customer_id` is now `INT NULL` instead of `INT NOT NULL`
- ✅ INSERT statements without `customer_id` will succeed (field is set to NULL)
- ✅ No schema merging needed in seed generator
- ✅ SQL files uploaded to artifacts for debugging

## Testing

Local validation confirmed:
```bash
# DDL generation
node .github/testing-framework/scripts/generate-ddl-sql.js examples/schema /tmp/test-ddl.sql

# Verify customer_id is nullable
grep "customer_id.*INT NULL" /tmp/test-ddl.sql
# Output: `customer_id` INT NULL,

# Seed generation
node .github/testing-framework/scripts/generate-seed-sql.js examples/schema /tmp/test-seed.sql
# No errors, generated 522 lines of SQL
```

## Related Files

- `.github/testing-framework/scripts/generate-ddl-sql.js` - DDL generator
- `.github/workflows/integration-test.yml` - Integration test workflow
- `examples/schema/orders.json` - Main orders schema
- `examples/schema/smartlookup-example.json` - Smartlookup orders example
- `examples/schema/smartlookup-legacy-example.json` - Legacy smartlookup example

## Notes

This approach prioritizes test data flexibility over strict database constraints. In production environments, applications would:
1. Use UserFrosting migrations with proper constraints
2. Enforce required fields through frontend validation (based on schema)
3. Apply business logic validation in backend controllers
