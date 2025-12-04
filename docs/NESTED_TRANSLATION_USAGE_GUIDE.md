# Translation Pattern - Usage Guide for Schema Authors (UserFrosting 6 Standards)

## Overview

This guide explains how to properly use translations in CRUD6 schemas following UserFrosting 6 standards, especially for custom actions that display confirmation messages and input forms.

## The Problem

When creating custom actions in your schema (like password changes, user enable/disable), you might encounter issues where translation keys appear as literal text instead of being translated:

**Bad Output:**
```
Are you sure you want to disable ()?
WARNING_CANNOT_UNDONE

Password
VALIDATION.ENTER_VALUE
```

**Good Output:**
```
Are you sure you want to disable John Doe (jdoe)?
This action cannot be undone.

Password
Enter password
```

## UserFrosting 6 Standard Pattern

UserFrosting 6 uses a **separation of concerns** approach for confirmation messages and warnings:
- **Confirmation messages** contain the specific question with record field placeholders
- **Warning messages** are handled by the modal component using UF6 core's `WARNING_CANNOT_UNDONE` key
- **Do NOT** embed warning messages in locale strings

### Standard Pattern (Recommended)

**In your locale file** (e.g., `app/locale/en_US/messages.php`):

```php
'USER' => [
    // Use specific field names as placeholders - UF6 standard
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
    // Warning is NOT included here - handled by modal component
],
```

**In your schema** (e.g., `app/schema/crud6/users.json`):

```json
{
    "actions": [
        {
            "key": "disable_user",
            "label": "USER.DISABLE",
            "confirm": "USER.DISABLE_CONFIRM",
            "modal_config": {
                "type": "confirm",
                "warning": "WARNING_CANNOT_UNDONE"  // From UF6 core - this is the default
            }
        }
    ]
}
```

### Key Principles

1. **Use specific field placeholders**: `{{first_name}}`, `{{last_name}}`, `{{user_name}}` - NOT generic `{{name}}`
2. **Warning is separate**: Use `WARNING_CANNOT_UNDONE` from UF6 core (in sprinkle-core/locale)
3. **No nested translations needed**: The `{{&KEY}}` syntax is only for special cases
4. **Modal handles warning**: ActionModal automatically shows the warning for confirm-type modals

## Examples Following UserFrosting 6 Standards

### Example 1: Confirm Action (No Input Fields)

**Schema:**
```json
{
    "key": "disable_user",
    "label": "USER.DISABLE",
    "icon": "user-slash",
    "type": "field_update",
    "field": "flag_enabled",
    "value": false,
    "style": "danger",
    "confirm": "USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm"
        // warning defaults to "WARNING_CANNOT_UNDONE" - no need to specify
    }
}
```

**Locale:**
```php
'USER' => [
    'DISABLE' => 'Disable User',
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
    // No ACTION or WARNING keys needed - using UF6 core's WARNING_CANNOT_UNDONE
],
```

**Result:**
- Modal title: "Disable User"
- Confirmation message: "Are you sure you want to disable **John Doe (jdoe)**?" (with user's actual data)
- Warning: "This action cannot be undone." (from UF6 core's WARNING_CANNOT_UNDONE)

### Example 2: Input Action (With Password Field)

**Schema:**
```json
{
    "key": "password_action",
    "label": "USER.CHANGE_PASSWORD",
    "icon": "key",
    "type": "field_update",
    "field": "password",
    "style": "warning",
    "confirm": "USER.ADMIN.PASSWORD_CHANGE_CONFIRM",
    "modal_config": {
        "type": "input",
        "fields": ["password"]
    }
}
```

**Locale:**
```php
'USER' => [
    'CHANGE_PASSWORD' => 'Change Password',
    'PASSWORD' => 'Password',
    
    'ADMIN' => [
        'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}}</strong>?',
    ],
],

'VALIDATION' => [
    'ENTER_VALUE' => 'Enter value',
    'CONFIRM' => 'Confirm',
    'CONFIRM_PLACEHOLDER' => 'Confirm value',
    'MIN_LENGTH_HINT' => 'Minimum {{min}} characters',
    'MATCH_HINT' => 'Values must match',
],
```

**Field Schema:**
```json
{
    "password": {
        "type": "password",
        "label": "USER.PASSWORD",
        "validation": {
            "length": {
                "min": 8
            },
            "match": true
        }
    }
}
```

**Result:**
- Modal title: "Change Password"
- Confirmation message: "Are you sure you want to change the password for **John Doe**?"
- Field label: "Password" (translated from USER.PASSWORD)
- Placeholder: "Enter value" (translated from VALIDATION.ENTER_VALUE)
- Confirm field label: "Confirm Password" (using VALIDATION.CONFIRM)
- Validation hints: "Minimum 8 characters" and "Values must match" (all translated)

### Example 3: Custom Warning Message

You can override the default warning message or disable it entirely:

**Schema (Custom Warning):**
```json
{
    "key": "delete_permanently",
    "label": "DELETE_PERMANENT",
    "confirm": "DELETE_PERMANENT_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": "ACTION.PERMANENT_DELETE_WARNING"
    }
}
```

**Schema (No Warning):**
```json
{
    "key": "soft_delete",
    "label": "SOFT_DELETE",
    "confirm": "SOFT_DELETE_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": ""
    }
}
```

**Locale:**
```php
'ACTION' => [
    'PERMANENT_DELETE_WARNING' => 'This will permanently delete the record and cannot be recovered!',
],
```

## Alternative Approach: Using Modal Config Warning

Instead of embedding the warning in the confirmation message, you can use the `warning` property in `modal_config`:

**Schema:**
```json
{
    "key": "disable_user",
    "label": "USER.DISABLE",
    "confirm": "USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": "ACTION.CANNOT_UNDO"
    }
}
```

**Locale:**
```php
'USER' => [
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}}</strong>?',
    // No need to include {{&ACTION.CANNOT_UNDO}} here
],
```

This approach separates the confirmation message from the warning, making it easier to reuse messages and customize warnings per action.

## Best Practices (UserFrosting 6 Standards)

### 1. Use UF6 Core Warning Key

Always use `WARNING_CANNOT_UNDONE` from UserFrosting 6 core for standard warnings:

✅ **Good (UF6 Standard):**
```php
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
// Warning handled by modal_config.warning = "WARNING_CANNOT_UNDONE"
```

❌ **Avoid (deprecated pattern):**
```php
'DISABLE_CONFIRM' => 'Are you sure you want to disable {{name}}?<br/>{{&ACTION.CANNOT_UNDO}}',
```

### 2. Use Specific Field Placeholders

Follow UF6 pattern of using actual field names, not generic placeholders:

✅ **Good:**
```php
'DELETE_CONFIRM' => 'Are you sure you want to delete the user <strong>{{full_name}} ({{user_name}})</strong>?',
```

❌ **Avoid:**
```php
'DELETE_CONFIRM' => 'Are you sure you want to delete <strong>{{name}}</strong>?',
```

### 3. Separate Warnings from Messages

Keep confirmation messages and warnings separate as per UF6 pattern:

✅ **Good:**
```php
// Locale
'USER' => [
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}}</strong>?',
],

// Schema
"modal_config": {
    "type": "confirm",
    "warning": "WARNING_CANNOT_UNDONE"  // From UF6 core
}
```

❌ **Avoid:**
```php
'DISABLE_CONFIRM' => 'Are you sure...?<br/>This action cannot be undone.',  // Hardcoded warning
```

### 4. Use Descriptive Locale Keys

Use clear, hierarchical keys following UF6 conventions:

✅ **Good:**
```php
'USER' => [
    'ADMIN' => [
        'PASSWORD_CHANGE_CONFIRM' => '...',
        'PASSWORD_RESET_CONFIRM' => '...',
    ],
],
```

❌ **Bad:**
```php
'PWD_CHG_CONF' => '...',
'PWD_RST_CONF' => '...',
```

❌ **Bad:**
```php
'DELETE_CONFIRM' => 'Are you sure you want to delete this user?',
```

### 5. Test All Locales

If you support multiple languages, make sure all nested translations are defined in each locale:

```php
// app/locale/en_US/messages.php
'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],

// app/locale/fr_FR/messages.php
'ACTION' => [
    'CANNOT_UNDO' => 'Cette action ne peut pas être annulée.',
],
```

## Common Translation Keys

### Warning Key (UserFrosting 6 Core)

This key is available in **sprinkle-core** and should be used for all standard warnings:

```php
// From UserFrosting 6 sprinkle-core
'WARNING_CANNOT_UNDONE' => 'This action cannot be undone.',
```

Use it in your schema:
```json
"modal_config": {
    "type": "confirm",
    "warning": "WARNING_CANNOT_UNDONE"  // This is the default for confirm-type modals
}
```

### Validation Keys (CRUD6 Sprinkle)

These keys are available in the CRUD6 sprinkle and are automatically used by ActionModal:

```php
'VALIDATION' => [
    'ENTER_VALUE' => 'Enter value',
    'CONFIRM' => 'Confirm',
    'CONFIRM_PLACEHOLDER' => 'Confirm value',
    'MIN_LENGTH_HINT' => 'Minimum {{min}} characters',
    'MATCH_HINT' => 'Values must match',
    'FIELDS_MUST_MATCH' => 'Fields must match',
    'MIN_LENGTH' => 'Minimum {{min}} characters required',
],
```

These are automatically applied to input fields - no action needed from schema authors.

## Troubleshooting

### Issue: Translation keys showing as literal text

**Symptom:** You see "WARNING_CANNOT_UNDONE" instead of "This action cannot be undone"

**Causes:**
1. Using wrong key name (e.g., `ACTION.CANNOT_UNDO` instead of `WARNING_CANNOT_UNDONE`)
2. Translation key not defined in UF6 core
3. Locale file not loaded properly

**Solution:**
- Use `WARNING_CANNOT_UNDONE` (the UF6 standard key from sprinkle-core)
- Verify your UserFrosting 6 installation includes sprinkle-core
- Check modal_config: `"warning": "WARNING_CANNOT_UNDONE"`

### Issue: Placeholders showing empty

**Symptom:** You see "Are you sure you want to delete ()?" 

**Causes:**
1. Record data not being passed to translator
2. Placeholder names don't match record field names (e.g., using `{{name}}` when field is `{{user_name}}`)
3. Record fields are null/undefined

**Solution:**
- Verify the action receives `:record="crud6"` prop
- Use specific field names that match your model: `{{first_name}}`, `{{last_name}}`, `{{user_name}}`
- Check that record actually has these fields populated

### Issue: Validation messages not translating

**Symptom:** You see "VALIDATION.ENTER_VALUE" in form placeholders

**Cause:** This was a bug in earlier versions that is now fixed.

**Solution:**
- Update to the latest version of CRUD6 sprinkle
- Validation keys are now properly translated using the translator composable
- The translator composable is now used consistently

## Summary

Following UserFrosting 6 standards ensures consistency and maintainability:

- ✅ Use `WARNING_CANNOT_UNDONE` from UF6 core for standard warnings
- ✅ Use specific field placeholders (`{{first_name}}`, `{{user_name}}`) not generic `{{name}}`
- ✅ Separate confirmation messages from warnings
- ✅ Set `modal_config.warning` to customize or disable warnings
- ✅ Follow UF6 patterns from sprinkle-admin and theme-pink-cupcake
- ✅ Test all locales to ensure complete translations

This approach ensures your custom actions integrate seamlessly with UserFrosting 6 and display properly translated messages across all modals and languages.
