# Internationalization (i18n) Support for Custom Actions and Schema

## Overview

CRUD6 now supports full internationalization for all user-facing text, including schema-defined labels, action buttons, confirmation messages, and success messages. This allows your application to support multiple languages without code changes.

## Features

- **Schema Translation**: Model names, titles, and descriptions support translation keys
- **Action Labels**: Button labels can be translation keys
- **Confirmation Messages**: Confirmation dialogs support translation
- **Success Messages**: Success notifications support translation
- **Breadcrumb Translation**: Page titles in breadcrumbs are translated
- **Fallback Support**: Plain text still works for backward compatibility

## How It Works

### Translation Key Resolution

CRUD6 uses Vue i18n's `t()` function to resolve translation keys. If a key exists in your locale files, it will be translated. If not, the original value is returned.

**Example:**
```javascript
t('USER.SINGULAR')  // Returns "User" (if defined in locale)
t('Plain Text')     // Returns "Plain Text" (no translation key found)
```

### Locale Files

Translation keys are defined in `app/locale/{locale}/messages.php`:

```php
// app/locale/en_US/messages.php
return [
    'CRUD6' => [
        'ACTION' => [
            'SUCCESS'            => '{{action}} completed successfully',
            'TOGGLE_ENABLED'     => 'Toggle Enabled',
            'TOGGLE_VERIFIED'    => 'Toggle Verified',
            'CHANGE_PASSWORD'    => 'Change Password',
            'RESET_PASSWORD'     => 'Reset Password',
            'DISABLE_USER'       => 'Disable User',
        ],
    ],
    'USER' => [
        'SINGULAR'  => 'User',
        'PLURAL'    => 'Users',
    ],
    'ROLE' => [
        'SINGULAR'  => 'Role',
        'PLURAL'    => 'Roles',
    ],
];
```

## Schema Configuration with i18n

### Model Names

Use translation keys for model names:

```json
{
  "model": "users",
  "title": "USER.PLURAL",
  "singular_title": "USER.SINGULAR",
  "description": "Manage system users"
}
```

**Result:**
- Breadcrumb shows: "Users" (or translated equivalent)
- Edit button shows: "Edit User" (or translated equivalent)
- Delete button shows: "Delete User" (or translated equivalent)

### Action Labels

Use translation keys for action button labels:

```json
{
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "CRUD6.ACTION.TOGGLE_ENABLED",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true
    },
    {
      "key": "change_password",
      "label": "CRUD6.ACTION.CHANGE_PASSWORD",
      "type": "route",
      "route": "user.password"
    }
  ]
}
```

### Confirmation Messages

Use translation keys or plain text for confirmations:

```json
{
  "actions": [
    {
      "key": "delete_user",
      "label": "CRUD6.ACTION.DELETE_USER",
      "type": "field_update",
      "field": "deleted_at",
      "value": "NOW()",
      "confirm": "CRUD6.ACTION.DELETE_CONFIRM"
    }
  ]
}
```

### Success Messages

Use translation keys or plain text for success messages:

```json
{
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "CRUD6.ACTION.TOGGLE_ENABLED",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "success_message": "CRUD6.ACTION.SUCCESS"
    }
  ]
}
```

The `CRUD6.ACTION.SUCCESS` key uses a placeholder:
```php
'ACTION' => [
    'SUCCESS' => '{{action}} completed successfully',
]
```

This will display: "Toggle Enabled completed successfully" (or translated).

### Detail Section Titles

Use translation keys for relationship titles:

```json
{
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "ACTIVITY.PLURAL"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug"],
      "title": "ROLE.PLURAL"
    }
  ]
}
```

## Complete Example

### Schema File (`app/schema/crud6/users.json`)

```json
{
  "model": "users",
  "title": "USER.PLURAL",
  "singular_title": "USER.SINGULAR",
  "description": "CRUD6.USER.DESCRIPTION",
  "table": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "ACTIVITY.PLURAL"
    },
    {
      "model": "roles",
      "foreign_key": "user_id",
      "list_fields": ["name", "slug"],
      "title": "ROLE.PLURAL"
    }
  ],
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "CRUD6.ACTION.TOGGLE_ENABLED",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "success_message": "CRUD6.ACTION.SUCCESS"
    },
    {
      "key": "change_password",
      "label": "CRUD6.ACTION.CHANGE_PASSWORD",
      "icon": "key",
      "type": "route",
      "route": "user.password"
    },
    {
      "key": "reset_password",
      "label": "CRUD6.ACTION.RESET_PASSWORD",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/users/{id}/password/reset",
      "method": "POST",
      "confirm": "CRUD6.ACTION.RESET_PASSWORD_CONFIRM",
      "success_message": "CRUD6.ACTION.RESET_PASSWORD_SUCCESS"
    }
  ]
}
```

### Locale File (`app/locale/en_US/messages.php`)

```php
return [
    'CRUD6' => [
        'USER' => [
            'DESCRIPTION' => 'Manage system users',
        ],
        'ACTION' => [
            'SUCCESS'                    => '{{action}} completed successfully',
            'TOGGLE_ENABLED'             => 'Toggle Enabled',
            'CHANGE_PASSWORD'            => 'Change Password',
            'RESET_PASSWORD'             => 'Reset Password',
            'RESET_PASSWORD_CONFIRM'     => 'Send password reset email to this user?',
            'RESET_PASSWORD_SUCCESS'     => 'Password reset email sent successfully',
        ],
    ],
    'USER' => [
        'SINGULAR' => 'User',
        'PLURAL'   => 'Users',
    ],
    'ROLE' => [
        'SINGULAR' => 'Role',
        'PLURAL'   => 'Roles',
    ],
    'ACTIVITY' => [
        'SINGULAR' => 'Activity',
        'PLURAL'   => 'Activities',
    ],
];
```

### French Translation (`app/locale/fr_FR/messages.php`)

```php
return [
    'CRUD6' => [
        'USER' => [
            'DESCRIPTION' => 'Gérer les utilisateurs du système',
        ],
        'ACTION' => [
            'SUCCESS'                    => '{{action}} terminé avec succès',
            'TOGGLE_ENABLED'             => 'Activer/Désactiver',
            'CHANGE_PASSWORD'            => 'Changer le mot de passe',
            'RESET_PASSWORD'             => 'Réinitialiser le mot de passe',
            'RESET_PASSWORD_CONFIRM'     => 'Envoyer un email de réinitialisation à cet utilisateur?',
            'RESET_PASSWORD_SUCCESS'     => 'Email de réinitialisation envoyé avec succès',
        ],
    ],
    'USER' => [
        'SINGULAR' => 'Utilisateur',
        'PLURAL'   => 'Utilisateurs',
    ],
    'ROLE' => [
        'SINGULAR' => 'Rôle',
        'PLURAL'   => 'Rôles',
    ],
    'ACTIVITY' => [
        'SINGULAR' => 'Activité',
        'PLURAL'   => 'Activités',
    ],
];
```

## Breadcrumb Translation

Breadcrumbs automatically use the translated model name:

**PageRow.vue behavior:**
1. Immediately sets breadcrumb with capitalized model name: "Users"
2. Loads schema
3. Updates breadcrumb with translated `title` or `singular_title`
4. For detail pages, updates to show record name: "John Doe - User"

**Translation flow:**
```
1. /crud6/users → "Users" (capitalized)
2. Schema loads with singular_title: "USER.SINGULAR"
3. Translated to → "User" (EN) or "Utilisateur" (FR)
4. Final breadcrumb → "User" or "Utilisateur"
```

## Backward Compatibility

**Plain text still works!** You can mix translation keys and plain text:

```json
{
  "title": "USER.PLURAL",           // Translation key
  "singular_title": "User",         // Plain text
  "actions": [
    {
      "label": "CRUD6.ACTION.TOGGLE_ENABLED",  // Translation key
      "success_message": "Status updated!"     // Plain text
    }
  ]
}
```

If a translation key doesn't exist, the original value is displayed.

## Best Practices

### 1. Use Consistent Naming

Group related translations together:
```php
'CRUD6' => [
    'ACTION' => [
        'TOGGLE_ENABLED' => 'Toggle Enabled',
        'TOGGLE_VERIFIED' => 'Toggle Verified',
        // ... other actions
    ],
]
```

### 2. Use Placeholders

For dynamic content, use placeholders:
```php
'ACTION' => [
    'SUCCESS' => '{{action}} completed successfully',
]
```

### 3. Organize by Model

Create model-specific translation sections:
```php
'USER' => [
    'SINGULAR' => 'User',
    'PLURAL'   => 'Users',
],
'GROUP' => [
    'SINGULAR' => 'Group',
    'PLURAL'   => 'Groups',
],
```

### 4. Keep Keys Descriptive

Use clear, descriptive keys:
```
✅ CRUD6.ACTION.RESET_PASSWORD
❌ CRUD6.A.RP
```

### 5. Document Custom Keys

If you add custom translation keys, document them in your schema or README.

## Migration Guide

### From Plain Text to Translation Keys

**Step 1:** Identify text to translate
```json
{
  "singular_title": "User",
  "actions": [
    {
      "label": "Toggle Enabled"
    }
  ]
}
```

**Step 2:** Add translations to locale file
```php
'USER' => [
    'SINGULAR' => 'User',
],
'CRUD6' => [
    'ACTION' => [
        'TOGGLE_ENABLED' => 'Toggle Enabled',
    ],
]
```

**Step 3:** Update schema to use keys
```json
{
  "singular_title": "USER.SINGULAR",
  "actions": [
    {
      "label": "CRUD6.ACTION.TOGGLE_ENABLED"
    }
  ]
}
```

**Step 4:** Add translations for other languages
```php
// app/locale/fr_FR/messages.php
'USER' => [
    'SINGULAR' => 'Utilisateur',
],
'CRUD6' => [
    'ACTION' => [
        'TOGGLE_ENABLED' => 'Activer/Désactiver',
    ],
]
```

## Troubleshooting

### Translation Not Working

**Problem:** Translation key shows as-is (e.g., "USER.SINGULAR")

**Solutions:**
1. Check key exists in locale file
2. Verify locale file is properly formatted PHP
3. Clear cache if using caching
4. Check locale is properly set in UserFrosting

### Breadcrumb Shows Translation Key

**Problem:** Breadcrumb shows "USER.SINGULAR" instead of "User"

**Solution:** This shouldn't happen with the current implementation. The `t()` function will return the key itself if translation doesn't exist. Ensure:
- Locale files are loaded
- Translation key is properly defined
- UserFrosting i18n is configured

### Mixed Languages

**Problem:** Some text in English, some in French

**Solution:**
- Ensure ALL translation keys exist in ALL locale files
- Check for typos in translation keys
- Verify locale switching works in UserFrosting

## See Also

- [Custom Actions Feature](CUSTOM_ACTIONS_FEATURE.md)
- [Multiple Details Feature](MULTIPLE_DETAILS_FEATURE.md)
- [UserFrosting i18n Documentation](https://learn.userfrosting.com/i18n)
