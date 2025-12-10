# Summary: SQL Error Fix for CRUD6 Schema Configuration

## Quick Overview

**Problem**: Integration tests failing with SQL error when creating permissions via CRUD6 API  
**Cause**: Schema files missing default values for NOT NULL database fields  
**Solution**: Added default values to schema files matching database structure  
**Status**: ✅ Complete - Ready for testing

## What Was the Issue?

The workflow at https://github.com/ssnukala/sprinkle-crud6/actions/runs/20103511230 was failing during the `permissions_create` API test with a 500 Internal Server Error. The error was:

```
Field 'conditions' doesn't have a default value
```

This SQL error occurred because:
1. UserFrosting's `permissions` table defines `conditions` as `text()` without `->nullable()` (NOT NULL)
2. The CRUD6 `permissions.json` schema had `conditions` with `required: false` and no default value
3. When the test tried to create a permission without providing `conditions`, the SQL INSERT failed

## Is This a Controller Bug or Schema Configuration Issue?

**This is a schema configuration issue**, not a controller bug.

The controller code in `CreateAction.php` and `Base::prepareInsertData()` is working correctly. The logic is:
```php
foreach ($fields as $fieldName => $fieldConfig) {
    if (isset($data[$fieldName])) {
        $insertData[$fieldName] = $data[$fieldName];
    } elseif (isset($fieldConfig['default'])) {
        $insertData[$fieldName] = $fieldConfig['default'];  // ← This is the key
    }
}
```

The controller correctly includes fields with default values. The schemas just needed to **provide those defaults**.

## What Was Fixed?

Updated three schema files to add default values for fields that are NOT NULL in the database:

### 1. examples/schema/permissions.json
```json
"conditions": {
    "type": "text",
    "label": "CRUD6.PERMISSION.CONDITIONS",
    "required": false,
    "default": "",  // ← ADDED
    "show_in": ["form", "detail"]
}
```

### 2. examples/schema/groups.json
```json
"icon": {
    "type": "string",
    "label": "CRUD6.GROUP.ICON",
    "required": false,
    "default": "fas fa-user",  // ← ADDED (matches DB default)
    "show_in": ["form", "detail"]
}
```

### 3. examples/schema/users.json
```json
"group_id": {
    "type": "integer",
    "label": "CRUD6.USER.GROUP",
    "required": false,
    "default": 1,  // ← ADDED (matches DB default)
    "show_in": ["form", "detail"]
}
```

## How Was This Determined?

1. **Reviewed the workflow logs** to identify the exact SQL error
2. **Examined UserFrosting migrations** at https://github.com/userfrosting/sprinkle-account/tree/6.0/app/src/Database/Migrations
3. **Compared database schema** with CRUD6 schema configurations
4. **Created test scripts** to validate the fix before committing
5. **Ran code review** to ensure no issues with the changes

## Expected Outcome

When the integration tests run again:
- ✅ `permissions_create` test should pass (status 201 instead of 500)
- ✅ `groups_create` test should continue to pass with correct default icon
- ✅ Other create/update operations should work as before
- ✅ No breaking changes to existing functionality

## Best Practice Going Forward

When creating CRUD6 schemas for UserFrosting tables:

1. **Check the migration file** for the table structure
2. **For NOT NULL fields without database defaults:**
   - If `required: true` in schema → No default needed (user must provide)
   - If `required: false` in schema → **Must** have `default` value in schema
3. **Match database defaults** when they exist
4. **Use appropriate empty values** when no database default:
   - String/text: `""`
   - Integer: `0` or appropriate default
   - Boolean: `false` or `true` as appropriate

## Files Changed

- `examples/schema/permissions.json` (+1 line)
- `examples/schema/groups.json` (+1 line)
- `examples/schema/users.json` (+1 line)
- `.archive/SQL_ERROR_FIX_SCHEMA_DEFAULTS.md` (+139 lines - detailed documentation)

## Validation Performed

- ✅ JSON syntax validation (all files valid)
- ✅ Test script validation (prepareInsertData logic confirmed working)
- ✅ Code review (no issues found)
- ⏳ Integration tests (will run automatically via CI)

## References

- Failed workflow run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20103511230
- UserFrosting migrations: https://github.com/userfrosting/sprinkle-account/tree/6.0/app/src/Database/Migrations
- Detailed documentation: `.archive/SQL_ERROR_FIX_SCHEMA_DEFAULTS.md`
