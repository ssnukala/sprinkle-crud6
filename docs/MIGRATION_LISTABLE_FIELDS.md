# Migration Guide: Listable Fields Security Update

## Overview

This update changes how the `listable` field property works in CRUD6 schemas to improve security by preventing accidental exposure of sensitive data.

## What Changed

### Before (Insecure Default)
- Fields were **shown by default** in list views unless they were marked as `readonly: true`
- Sensitive fields like `password`, `created_at`, `updated_at` would be visible unless explicitly hidden
- This could lead to accidental data exposure

### After (Secure Default)
- Fields are **hidden by default** in list views
- Only fields with **explicit `listable: true`** are shown in list views
- This prevents sensitive data from being accidentally exposed

## Impact

### Who is Affected?
This change affects any schema that:
1. Does not have explicit `listable` properties on fields
2. Relies on the old default behavior of showing all non-readonly fields

### What You'll See
If your schema doesn't have explicit `listable` settings:
- **Before**: All fields except readonly ones appeared in list views
- **After**: Only fields marked with `listable: true` appear in list views
- **Result**: Your list views may appear empty or show fewer columns than before

## How to Update Your Schemas

### Step 1: Review Your Current Schema
Check which fields are currently being displayed in your list views and determine which should remain visible.

### Step 2: Add Explicit `listable: true` to Fields
Update your schema files to explicitly mark fields that should be visible:

```json
{
  "fields": {
    "id": {
      "type": "integer",
      "listable": true  // ✅ Add this to show in lists
    },
    "user_name": {
      "type": "string",
      "listable": true  // ✅ Add this to show in lists
    },
    "password": {
      "type": "string",
      "listable": false  // ⚠️ Or omit entirely - sensitive field should be hidden
    },
    "created_at": {
      "type": "datetime",
      "listable": false  // ⚠️ Or omit entirely - typically hidden by default
    }
  }
}
```

### Step 3: Security Review
Review each field in your schema and ask:
1. **Should this field be visible in list views?**
   - User names, emails, titles → Usually YES (`listable: true`)
   - Passwords, API keys, sensitive IDs → Always NO (`listable: false` or omit)
   - Timestamps, internal fields → Usually NO unless specifically needed

2. **Does this field contain sensitive data?**
   - If YES → Do NOT set `listable: true`
   - If NO → Safe to set `listable: true`

## Example: Users Schema Migration

### Before (Insecure)
```json
{
  "fields": {
    "id": { "type": "integer" },
    "user_name": { "type": "string" },
    "email": { "type": "string" },
    "password": { "type": "string" },  // ⚠️ Would be shown!
    "created_at": { "type": "datetime", "readonly": true },  // Would be hidden
    "updated_at": { "type": "datetime", "readonly": true }   // Would be hidden
  }
}
```

### After (Secure)
```json
{
  "fields": {
    "id": { 
      "type": "integer",
      "listable": true  // ✅ Explicitly show
    },
    "user_name": { 
      "type": "string",
      "listable": true  // ✅ Explicitly show
    },
    "email": { 
      "type": "string",
      "listable": true  // ✅ Explicitly show
    },
    "password": { 
      "type": "string",
      "listable": false  // ⚠️ Explicitly hide (or omit property)
    },
    "created_at": { 
      "type": "datetime",
      "readonly": true,
      "listable": false  // ⚠️ Explicitly hide (or omit property)
    },
    "updated_at": { 
      "type": "datetime",
      "readonly": true,
      "listable": false  // ⚠️ Explicitly hide (or omit property)
    }
  }
}
```

## Testing Your Changes

After updating your schemas:

1. **Clear cache** if your application caches schemas
2. **Load list views** for each model
3. **Verify** that:
   - Expected fields are visible
   - Sensitive fields are hidden
   - No data exposure issues

## Common Fields to Show

Typically safe to set `listable: true`:
- `id` - Record identifier
- `name`, `title`, `user_name` - Primary identifying information
- `email` - Contact information (if appropriate for your use case)
- `status`, `is_active`, `flag_enabled` - Status indicators
- `first_name`, `last_name` - User names
- `price`, `quantity`, `sku` - Product information

## Common Fields to Hide

Should typically have `listable: false` or omit the property:
- `password`, `password_hash` - Authentication credentials
- `api_key`, `api_secret`, `token` - API credentials
- `created_at`, `updated_at` - Internal timestamps (unless specifically needed)
- `deleted_at` - Soft delete timestamps
- `last_activity_id` - Internal tracking IDs
- `locale`, `theme` - User preferences (unless needed for admin)

## Need Help?

If you're unsure whether a field should be visible:
1. **Default to hidden** - It's safer to hide fields by default
2. **Add visibility gradually** - Only add `listable: true` when you're certain it's needed
3. **Review with your security team** - For any fields that might contain sensitive data

## References

- See `examples/users.json` for a complete example
- See `examples/products.json` for another reference implementation
- Check the README.md for full field property documentation
