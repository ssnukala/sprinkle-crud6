# SQL Error Fix: Schema Default Values for NOT NULL Fields

## Issue Summary

Integration tests were failing when creating records via CRUD6 API. Specifically, the `permissions_create` test was failing with a 500 error due to SQL insertion failure.

## Root Cause

The UserFrosting account sprinkle database migrations define certain fields as NOT NULL without default values in the database. When CRUD6 schemas marked these fields as `required: false` without providing a `default` value, attempting to create a record without those fields caused SQL errors:

```
Field 'conditions' doesn't have a default value
```

## Technical Details

### The Problem Flow

1. **Database Schema**: UserFrosting's `permissions` table defines `conditions` as `text()` without `->nullable()`, making it NOT NULL in MySQL
2. **CRUD6 Schema**: The `permissions.json` schema defines `conditions` with `required: false`, meaning it won't be sent in POST requests
3. **Controller Logic**: `CreateAction::prepareInsertData()` only includes fields in INSERT if:
   - The field value is provided in `$data`, OR
   - The field has a `default` value in the schema
4. **SQL Failure**: When INSERT is executed without `conditions`, MySQL rejects it because the field is NOT NULL and has no database default

### Affected Fields by Table

#### permissions table (from PermissionsTable.php)
- `conditions`: `text()` - **NOT NULL**, no database default
  - **Fix**: Add `"default": ""` to schema

#### groups table (from GroupsTable.php)
- `icon`: `string(100)->nullable(false)->default('fas fa-user')`
  - **Fix**: Add `"default": "fas fa-user"` to schema

#### users table (from UsersTable.php)
- `group_id`: `integer()->unsigned()->default(1)` - **NOT NULL**, database default = 1
  - **Fix**: Add `"default": 1` to schema
- `password`: `string(255)` - **NOT NULL**, no database default
  - **Note**: Special case - handled by HashesPasswords trait, not typically created via CRUD6

## Solution

Updated the following schema files to include default values matching the database table structure:

### 1. examples/schema/permissions.json

```json
"conditions": {
    "type": "text",
    "label": "CRUD6.PERMISSION.CONDITIONS",
    "required": false,
    "default": "",  // ← ADDED
    "show_in": [
        "form",
        "detail"
    ]
}
```

### 2. examples/schema/groups.json

```json
"icon": {
    "type": "string",
    "label": "CRUD6.GROUP.ICON",
    "required": false,
    "default": "fas fa-user",  // ← ADDED
    "show_in": [
        "form",
        "detail"
    ],
    "validation": {
        "length": {
            "max": 100
        }
    }
}
```

### 3. examples/schema/users.json

```json
"group_id": {
    "type": "integer",
    "label": "CRUD6.USER.GROUP",
    "required": false,
    "default": 1,  // ← ADDED
    "show_in": [
        "form",
        "detail"
    ]
}
```

## Why This is a Schema Issue, Not a Controller Issue

The controller functionality is correct. The `prepareInsertData()` method in `Base.php` properly handles:
- Auto-increment fields (skipped)
- Computed/virtual fields (skipped)
- Fields with data (included)
- Fields with defaults in schema (included)

The issue was that schemas didn't provide defaults for fields that are NOT NULL in the database but optional in the UI.

## Best Practice

When creating CRUD6 schemas for existing UserFrosting tables:

1. **Review the migration file** for the table at:
   - https://github.com/userfrosting/sprinkle-account/tree/6.0/app/src/Database/Migrations

2. **For each field that is NOT NULL in the database:**
   - If marked `required: true` in schema → No default needed (user must provide)
   - If marked `required: false` in schema → Must have `default` value matching database

3. **Match database defaults** in the schema:
   - If database has `->default(value)`, use same value in schema
   - If database has no default but field is NOT NULL, use appropriate empty value:
     - String/text: `""`
     - Integer: `0` or appropriate default
     - Boolean: `false` or `true` as appropriate

## Testing

After this fix, the `permissions_create` test should pass successfully, as the schema now provides a default empty string for the `conditions` field when it's not included in the POST request.

## Files Modified

- `examples/schema/permissions.json` - Added default for `conditions` field
- `examples/schema/groups.json` - Added default for `icon` field  
- `examples/schema/users.json` - Added default for `group_id` field

## References

- UserFrosting PermissionsTable.php: https://github.com/userfrosting/sprinkle-account/blob/6.0/app/src/Database/Migrations/v400/PermissionsTable.php
- UserFrosting GroupsTable.php: https://github.com/userfrosting/sprinkle-account/blob/6.0/app/src/Database/Migrations/v400/GroupsTable.php
- UserFrosting UsersTable.php: https://github.com/userfrosting/sprinkle-account/blob/6.0/app/src/Database/Migrations/v400/UsersTable.php
- Failed workflow run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20103511230
