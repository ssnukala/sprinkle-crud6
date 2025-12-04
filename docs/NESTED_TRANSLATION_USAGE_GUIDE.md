# Nested Translation Pattern - Usage Guide for Schema Authors

## Overview

This guide explains how to properly use translations in CRUD6 schemas, especially for custom actions that display confirmation messages and input forms.

## The Problem

When creating custom actions in your schema (like password changes, user enable/disable), you might encounter issues where translation keys appear as literal text instead of being translated:

**Bad Output:**
```
Are you sure you want to disable ()?
ACTION.CANNOT_UNDO

Password
VALIDATION.ENTER_VALUE
```

**Good Output:**
```
Are you sure you want to disable John Doe?
This action cannot be undone.

Password
Enter password
```

## Solution: Use Nested Translation Syntax

UserFrosting 6's translator supports nested translation using the `{{&KEY}}` syntax. This allows you to embed translation keys within your locale messages, and they will be translated at render time.

### Basic Pattern

**In your locale file** (e.g., `app/locale/en_US/messages.php`):

```php
'USER' => [
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
],

'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

**In your schema** (e.g., `app/schema/crud6/users.json`):

```json
{
    "actions": [
        {
            "key": "disable_user",
            "label": "CRUD6.USER.DISABLE_USER",
            "confirm": "CRUD6.USER.DISABLE_CONFIRM",
            "modal_config": {
                "type": "confirm"
            }
        }
    ]
}
```

### How It Works

1. The schema references the translation key: `"confirm": "CRUD6.USER.DISABLE_CONFIRM"`
2. The translator looks up that key and finds: `'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}'`
3. The translator replaces `{{first_name}}`, `{{last_name}}`, `{{user_name}}` with values from the record
4. The translator sees `{{&ACTION.CANNOT_UNDO}}` (note the `&` prefix) and recursively translates it
5. `ACTION.CANNOT_UNDO` is looked up and replaced with `'This action cannot be undone.'`

## Examples

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
    }
}
```

**Locale:**
```php
'USER' => [
    'DISABLE' => 'Disable User',
    'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}}</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
],

'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

**Result:**
- Modal title: "Disable User"
- Confirmation message: "Are you sure you want to disable **John Doe**?" (with user's actual name)
- Warning: "This action cannot be undone." (translated from ACTION.CANNOT_UNDO)

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

## Best Practices

### 1. Use Nested Translation for Common Messages

For messages like "This action cannot be undone", use `{{&ACTION.CANNOT_UNDO}}` instead of duplicating the text:

✅ **Good:**
```php
'DISABLE_CONFIRM' => 'Are you sure you want to disable {{name}}?<br/>{{&ACTION.CANNOT_UNDO}}',
```

❌ **Bad:**
```php
'DISABLE_CONFIRM' => 'Are you sure you want to disable {{name}}?<br/>This action cannot be undone.',
```

### 2. Define Reusable Translation Keys

Create common keys in a central namespace:

```php
'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
    'PERMANENT_WARNING' => 'This action is permanent and cannot be reversed.',
    'CONFIRM_ACTION' => 'Please confirm to proceed.',
],

'VALIDATION' => [
    'ENTER_VALUE' => 'Enter value',
    'CONFIRM' => 'Confirm',
    'CONFIRM_PLACEHOLDER' => 'Confirm value',
    'MIN_LENGTH_HINT' => 'Minimum {{min}} characters',
    'MATCH_HINT' => 'Values must match',
],
```

### 3. Use Descriptive Locale Keys

Use clear, hierarchical keys:

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

### 4. Always Provide Placeholders

Make sure your confirmation messages use the available record data:

✅ **Good:**
```php
'DELETE_CONFIRM' => 'Are you sure you want to delete <strong>{{first_name}} {{last_name}}</strong>?',
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

### Actions

These keys are available in the CRUD6 sprinkle:

```php
'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

Use them in your messages with `{{&ACTION.CANNOT_UNDO}}`.

### Validation

These keys are available in the CRUD6 sprinkle:

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

These are automatically used by ActionModal for input fields, but you can also reference them in your messages if needed.

## Troubleshooting

### Issue: Translation keys showing as literal text

**Symptom:** You see "ACTION.CANNOT_UNDO" instead of "This action cannot be undone"

**Causes:**
1. Missing `&` prefix: Use `{{&ACTION.CANNOT_UNDO}}` not `{{ACTION.CANNOT_UNDO}}`
2. Translation key not defined in locale file
3. Locale file not loaded for the current sprinkle

**Solution:**
- Add `&` prefix for nested translations
- Verify the key exists in your locale file
- Check that your sprinkle's locale directory is in the correct location (`app/locale/en_US/messages.php`)

### Issue: Placeholders showing empty

**Symptom:** You see "Are you sure you want to delete ()?" 

**Causes:**
1. Record data not being passed to translator
2. Placeholder names don't match record field names
3. Record fields are null/undefined

**Solution:**
- Verify the action receives `:record="crud6"` prop
- Check that placeholder names match your model's field names
- Use fallback values: `{{first_name|Anonymous}}`

### Issue: Validation messages not translating

**Symptom:** You see "VALIDATION.ENTER_VALUE" in form placeholders

**Cause:** This was a bug in earlier versions that is now fixed.

**Solution:**
- Update to the latest version of CRUD6 sprinkle
- The translator composable is now used consistently

## Summary

- ✅ Use `{{&KEY}}` syntax for nested translations
- ✅ Define common messages in reusable keys (ACTION.CANNOT_UNDO, etc.)
- ✅ Use the `warning` property in `modal_config` for flexibility
- ✅ Always provide placeholders for user data
- ✅ Test all locales to ensure complete translations

This pattern ensures your custom actions display properly translated messages across all modals and languages.
