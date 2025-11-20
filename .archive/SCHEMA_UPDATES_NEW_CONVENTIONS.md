# Schema File Updates - New Convention Summary

This document outlines the updates needed for all schema files to follow the new conventions established in this PR.

## New Conventions

### 1. Action Key Naming: `{fieldname}_action` Pattern
- **Old**: Custom keys like `"change_password"`, `"reset_password"`, etc.
- **New**: Use `{fieldname}_action` pattern (e.g., `"password_action"`)
- **Exception**: Actions that don't update a specific field can keep descriptive names (e.g., `"reset_password"` for API calls)

### 2. Action Type Simplification
- **Old**: Custom `"password_update"` type
- **New**: Use standard `"field_update"` type with `validation.match` flag
- **Benefit**: Generic, works for any field type

### 3. Field Validation with Match
- **Old**: Separate modal logic for passwords
- **New**: Add `"validation": { "match": true }` to any field requiring confirmation
- **Benefit**: Works for password, email, or any field type

### 4. Custom Action Route Shortcode
- **Old**: `/api/crud6/{model}/{id}/actions/{actionKey}`
- **New**: `/api/crud6/{model}/{id}/a/{actionKey}`
- **Benefit**: Shorter, cleaner URLs

### 5. Auto-Inferred Endpoints for API Calls
- **Old**: Must specify `"endpoint": "/api/crud6/users/{id}/a/reset_password"`
- **New**: Endpoint is auto-generated from model and action key if not specified
- **Format**: `/api/crud6/{model}/{id}/a/{actionKey}`
- **Benefit**: Less redundancy, DRY principle
- **Note**: You can still specify custom endpoints when needed

### 6. Virtual/Computed Fields
- **New**: Mark non-database fields with `"computed": true`
- **Example**: `role_ids` for many-to-many relationship management

---

## Files Requiring Updates

### Priority 1: Core Schema Files (app/schema/crud6/)

#### ✅ users.json (UPDATED)
- ✅ Added `password_action` with `field_update` type
- ✅ Added `"computed": true` to `role_ids`
- ✅ Added `"validation": { "match": true }` to password field
- ✅ Updated `reset_password` endpoint to use `/a/`

#### permissions.json
**Current Issues:**
- Has `role_ids` field without `"computed": true` ✅ (Already fixed in earlier commit)

**Suggested Updates:**
```json
"role_ids": {
    "type": "multiselect",
    "computed": true,
    "validation": {
        "match": false
    }
}
```

#### roles.json
**Check for:**
- Any virtual fields that need `"computed": true`
- Any password or confirmation-requiring fields

#### groups.json
**Check for:**
- Any virtual fields that need `"computed": true`

#### activities.json
**Check for:**
- Any virtual fields that need `"computed": true`

---

### Priority 2: Example Schema Files (examples/schema/)

#### c6admin-users.json
**Current Issues:**
1. `reset_password` action uses old endpoint pattern
2. Missing `password_action` for direct password changes
3. Password field missing `"validation": { "match": true }`

**Suggested Updates:**
```json
{
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "USER.ADMIN.TOGGLE_ENABLED",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field",
      "success_message": "USER.ADMIN.TOGGLE_ENABLED_SUCCESS"
    },
    {
      "key": "toggle_verified",
      "label": "USER.ADMIN.TOGGLE_VERIFIED",
      "icon": "check-circle",
      "type": "field_update",
      "field": "flag_verified",
      "toggle": true,
      "style": "default",
      "permission": "update_user_field",
      "success_message": "USER.ADMIN.TOGGLE_VERIFIED_SUCCESS"
    },
    {
      "key": "reset_password",
      "label": "USER.ADMIN.PASSWORD_RESET",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/crud6/users/{id}/a/reset_password",
      "method": "POST",
      "style": "secondary",
      "permission": "update_user_field",
      "confirm": "USER.ADMIN.PASSWORD_RESET_CONFIRM",
      "success_message": "USER.ADMIN.PASSWORD_RESET_SUCCESS"
    },
    {
      "key": "password_action",
      "label": "USER.ADMIN.PASSWORD_CHANGE",
      "icon": "key",
      "type": "field_update",
      "field": "password",
      "style": "warning",
      "permission": "update_user_field",
      "confirm": "USER.ADMIN.PASSWORD_CHANGE_CONFIRM",
      "success_message": "USER.ADMIN.PASSWORD_CHANGE_SUCCESS"
    },
    {
      "key": "disable_user",
      "label": "USER.DISABLE",
      "icon": "ban",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "style": "danger",
      "permission": "update_user_field",
      "confirm": "USER.DISABLE_CONFIRM",
      "success_message": "DISABLE_SUCCESSFUL"
    },
    {
      "key": "enable_user",
      "label": "USER.ENABLE",
      "icon": "check",
      "type": "field_update",
      "field": "flag_enabled",
      "value": true,
      "style": "primary",
      "permission": "update_user_field",
      "confirm": "USER.ENABLE_CONFIRM",
      "success_message": "ENABLE_SUCCESSFUL"
    }
  ],
  "fields": {
    "password": {
      "type": "password",
      "label": "Password",
      "required": false,
      "listable": false,
      "viewable": false,
      "editable": true,
      "validation": {
        "length": {
          "min": 8,
          "max": 255
        },
        "match": true
      }
    }
  }
}
```

#### c6admin-permissions.json
**Check for:**
- Virtual fields like `role_ids` need `"computed": true` ✅ (Already fixed)
- Any custom actions needing `/a/` route updates

#### c6admin-roles.json
**Check for:**
- Virtual fields that need `"computed": true`
- Any custom actions

#### c6admin-groups.json
**Check for:**
- Virtual fields that need `"computed": true`
- Any custom actions

#### c6admin-activities.json
**Check for:**
- Virtual fields that need `"computed": true`

#### users-extended.json
**Suggested Updates:**
- Add `"validation": { "match": true }` to password field if editing is enabled
- Check for any `/actions/` endpoints to change to `/a/`

#### users-relationship-actions.json
**Current Status:**
- Already has `role_ids` with `"computed": true` ✅ (Fixed in earlier commit)

**Check for:**
- Any password fields that need `"validation": { "match": true }`

---

## Migration Checklist

### For Each Schema File:

- [ ] **Actions Section:**
  - [ ] Update action keys to use `{fieldname}_action` pattern where applicable
  - [ ] Change `"password_update"` type to `"field_update"`
  - [ ] Update any `/actions/` endpoints to `/a/`
  - [ ] Ensure `confirm` messages are translation keys or clean HTML

- [ ] **Fields Section:**
  - [ ] Add `"computed": true` to virtual/calculated fields (multiselect, etc.)
  - [ ] Add `"validation": { "match": true }` to fields requiring confirmation (password, email, etc.)
  - [ ] Verify field types are correct (password, email, text, number, etc.)

- [ ] **Validation:**
  - [ ] Run JSON validator: `php -r "echo json_decode(file_get_contents('path/to/schema.json')) ? 'valid' : 'invalid';"`
  - [ ] Check schema loads correctly in application
  - [ ] Test actions work with new conventions

---

## Example: Complete Updated User Schema

```json
{
  "model": "users",
  "actions": [
    {
      "key": "toggle_enabled",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true
    },
    {
      "key": "password_action",
      "type": "field_update",
      "field": "password",
      "confirm": "Change <strong>{{full_name}}</strong>'s password?"
    },
    {
      "key": "reset_password",
      "type": "api_call",
      "method": "POST"
      // endpoint auto-generated as: /api/crud6/users/{id}/a/reset_password
    }
  ],
  "fields": {
    "password": {
      "type": "password",
      "validation": {
        "length": { "min": 8 },
        "match": true
      }
    },
    "role_ids": {
      "type": "multiselect",
      "computed": true
    }
  }
}
```

---

## Testing After Updates

1. **JSON Validation:**
   ```bash
   find . -name "*.json" -path "*/schema/*" -exec php -r "echo json_decode(file_get_contents('{}')) ? '{} valid\n' : '{} INVALID\n';" \;
   ```

2. **Schema Loading:**
   - Load each schema in the application
   - Verify fields render correctly
   - Test actions execute properly

3. **Field Edit Modal:**
   - Test password change with confirmation
   - Test other fields with `validation.match`
   - Verify field types display correct input types

4. **Custom Actions:**
   - Test `/a/` routes work correctly
   - Verify permissions are enforced
   - Check success/error messages display

---

## Benefits of New Conventions

1. **Consistency**: All schemas follow same patterns
2. **Maintainability**: Easier to understand and update
3. **Flexibility**: Generic patterns work for all field types
4. **Cleaner URLs**: Shorter `/a/` instead of `/actions/`
5. **Better UX**: Modal dialogs with proper HTML rendering
6. **Type Safety**: Field types determine input behavior automatically
