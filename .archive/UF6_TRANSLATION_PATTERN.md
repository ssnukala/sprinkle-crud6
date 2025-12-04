# UserFrosting 6 @TRANSLATION Pattern Implementation

## Background

UserFrosting 6 uses a special `@TRANSLATION` key in translation arrays to provide a **default translation** when accessing a parent key directly. This is a cleaner, more semantic approach than using numeric indices or other arbitrary keys.

## Pattern Explanation

### Traditional Approach (Before @TRANSLATION)

```php
'CRUD6' => [
    1 => 'CRUD6',  // Accessing 'CRUD6' returns this
    2 => 'CRUD6 All Rows',  // Plural form
    
    'CREATE' => [
        0 => 'Create {{model}}',  // Accessing 'CRUD6.CREATE' returns this
        'SUCCESS' => 'Successfully created {{model}}',
    ],
]
```

**Issues:**
- Numeric keys (0, 1, 2) are not semantic
- Unclear which key represents the "main" translation
- Inconsistent with UserFrosting 6 core conventions

### UserFrosting 6 Approach (With @TRANSLATION)

```php
'CRUD6' => [
    '@TRANSLATION' => 'CRUD6',  // Default translation for 'CRUD6'
    1 => 'CRUD6',  // Kept for backward compatibility
    2 => 'CRUD6 All Rows',
    
    'CREATE' => [
        '@TRANSLATION' => 'Create {{model}}',  // Default for 'CRUD6.CREATE'
        0 => 'Create {{model}}',  // Kept for backward compatibility
        'SUCCESS' => 'Successfully created {{model}}',
    ],
]
```

**Benefits:**
- ✅ Semantic and self-documenting
- ✅ Follows UserFrosting 6 core conventions
- ✅ Clearer intent (this is THE translation for this key)
- ✅ Backward compatible (numeric keys still work)

## Examples from UserFrosting Core

### Example 1: CAPTCHA

**From `sprinkle-core/app/locale/en_US/messages.php`:**

```php
'CAPTCHA' => [
    '@TRANSLATION' => 'Captcha',
    'FAIL'         => 'You did not enter the captcha code correctly.',
    'SPECIFY'      => 'Enter the captcha',
    'VERIFY'       => 'Verify the captcha',
],
```

**Usage:**
- `$t('CAPTCHA')` → `'Captcha'` (via @TRANSLATION)
- `$t('CAPTCHA.FAIL')` → `'You did not enter the captcha code correctly.'`
- `$t('CAPTCHA.SPECIFY')` → `'Enter the captcha'`

### Example 2: EMAIL

```php
'EMAIL' => [
    '@TRANSLATION' => 'Email',
    'YOUR'         => 'Your email address',
],
```

**Usage:**
- `$t('EMAIL')` → `'Email'`
- `$t('EMAIL.YOUR')` → `'Your email address'`

### Example 3: LOCALE

```php
'LOCALE' => [
    '@TRANSLATION' => 'Locale',
],
```

**Usage:**
- `$t('LOCALE')` → `'Locale'`

## Implementation in CRUD6 Sprinkle

### Updated Structure

```php
return [
    'CRUD6' => [
        '@TRANSLATION' => 'CRUD6',  // Default for 'CRUD6'
        1 => 'CRUD6',  // Backward compatibility
        2 => 'CRUD6 All Rows',

        'CREATE' => [
            '@TRANSLATION'  => 'Create {{model}}',  // Default for 'CRUD6.CREATE'
            0 => 'Create {{model}}',  // Backward compatibility
            'SUCCESS'       => 'Successfully created {{model}}',
            'SUCCESS_TITLE' => 'Created!',
            'ERROR'         => 'Failed to create {{model}}',
            'ERROR_TITLE'   => 'Error Creating',
        ],

        'DELETE' => [
            '@TRANSLATION'  => 'Delete {{model}}',  // Default for 'CRUD6.DELETE'
            0 => 'Delete {{model}}',  // Backward compatibility
            'SUCCESS'       => 'Successfully deleted {{model}}',
            'SUCCESS_TITLE' => 'Deleted!',
            'ERROR'         => 'Failed to delete {{model}}',
            'ERROR_TITLE'   => 'Error Deleting',
        ],

        'EDIT' => [
            '@TRANSLATION' => 'Edit {{model}}',  // Default for 'CRUD6.EDIT'
            0 => 'Edit {{model}}',  // Backward compatibility
            'SUCCESS' => 'Retrieved {{model}} for editing',
            'ERROR'   => 'Failed to retrieve {{model}}',
        ],

        'UPDATE' => [
            '@TRANSLATION'  => 'Update {{model}}',  // Default for 'CRUD6.UPDATE'
            0 => 'Details updated for {{model}} <strong>{{id}}</strong>',  // Backward compatibility
            'SUCCESS'       => 'Successfully updated {{model}}',
            'SUCCESS_TITLE' => 'Updated!',
            'ERROR'         => 'Failed to update {{model}}',
            'ERROR_TITLE'   => 'Error Updating',
        ],

        'RELATIONSHIP' => [
            '@TRANSLATION'   => 'Relationships',  // Default for 'CRUD6.RELATIONSHIP'
            'ATTACH_SUCCESS' => 'Successfully attached {{count}} {{relation}} to {{model}}',
            'DETACH_SUCCESS' => 'Successfully detached {{count}} {{relation}} from {{model}}',
        ],

        // Direct keys (no @TRANSLATION needed)
        'ADMIN_PANEL' => 'CRUD6 Admin Panel',  // For 'CRUD6.ADMIN_PANEL'
        'PAGE'        => '{{model}}',
        'NOT_FOUND'   => '{{model}} not found',
        // ... etc
    ],

    // Flat keys for backward compatibility
    'C6ADMIN_PANEL' => 'CRUD6 Admin Panel',
    'CRUD6_PANEL'   => 'CRUD6 Management',
];
```

### Translation Key Mapping

| Key | Translates To | Via |
|-----|---------------|-----|
| `CRUD6` | `CRUD6` | `@TRANSLATION` |
| `CRUD6.CREATE` | `Create {{model}}` | `@TRANSLATION` |
| `CRUD6.CREATE.SUCCESS` | `Successfully created {{model}}` | Direct |
| `CRUD6.DELETE` | `Delete {{model}}` | `@TRANSLATION` |
| `CRUD6.EDIT` | `Edit {{model}}` | `@TRANSLATION` |
| `CRUD6.UPDATE` | `Update {{model}}` | `@TRANSLATION` |
| `CRUD6.RELATIONSHIP` | `Relationships` | `@TRANSLATION` |
| `CRUD6.ADMIN_PANEL` | `CRUD6 Admin Panel` | Direct |
| `C6ADMIN_PANEL` | `CRUD6 Admin Panel` | Direct (flat) |

## When to Use @TRANSLATION

### Use @TRANSLATION When:

1. **Parent key might be accessed directly:**
   ```php
   'GROUP' => [
       '@TRANSLATION' => 'Group',  // Someone might call $t('GROUP')
       'NAME' => 'Group Name',
       'DESCRIPTION' => 'Group Description',
   ]
   ```

2. **Nested array has a primary/default meaning:**
   ```php
   'EMAIL' => [
       '@TRANSLATION' => 'Email',  // The main concept
       'YOUR' => 'Your email address',  // Specific variant
       'INVALID' => 'Invalid email',  // Error case
   ]
   ```

3. **Following UserFrosting 6 conventions:**
   - Check how similar keys are structured in `sprinkle-core`
   - Match the pattern for consistency

### Don't Need @TRANSLATION When:

1. **Direct key with no nested values:**
   ```php
   'ADMIN_PANEL' => 'CRUD6 Admin Panel',  // No nesting, no @TRANSLATION needed
   ```

2. **Key is never accessed directly:**
   ```php
   'VALIDATION' => [
       // If you NEVER call $t('VALIDATION'), only $t('VALIDATION.MIN_LENGTH')
       'MIN_LENGTH' => 'Minimum {{min}} characters',
       // No @TRANSLATION needed
   ]
   ```

## Backward Compatibility

The implementation maintains backward compatibility:

```php
'CRUD6' => [
    '@TRANSLATION' => 'CRUD6',  // NEW: UserFrosting 6 convention
    1 => 'CRUD6',               // OLD: Still works for existing code
    2 => 'CRUD6 All Rows',      // OLD: Still works for existing code
]
```

Both of these work:
- `$t('CRUD6')` → returns `'CRUD6'` (via `@TRANSLATION`)
- `$t('CRUD6', 1)` → returns `'CRUD6'` (via numeric key 1)
- `$t('CRUD6', 2)` → returns `'CRUD6 All Rows'` (via numeric key 2)

## Verification

```bash
# Test @TRANSLATION access
$ php -r "
\$messages = include 'app/locale/en_US/messages.php';
echo 'CRUD6: ' . \$messages['CRUD6']['@TRANSLATION'] . PHP_EOL;
echo 'CRUD6.CREATE: ' . \$messages['CRUD6']['CREATE']['@TRANSLATION'] . PHP_EOL;
echo 'CRUD6.ADMIN_PANEL: ' . \$messages['CRUD6']['ADMIN_PANEL'] . PHP_EOL;
"
```

**Output:**
```
CRUD6: CRUD6
CRUD6.CREATE: Create {{model}}
CRUD6.ADMIN_PANEL: CRUD6 Admin Panel
```

## Benefits for CRUD6 Sprinkle

1. **Consistency:** Matches UserFrosting 6 core conventions
2. **Clarity:** Self-documenting which translation is the "main" one
3. **Maintainability:** Easier for future developers to understand
4. **Compatibility:** Works with both old and new UserFrosting code
5. **Flexibility:** Can access both parent and child translations easily

## Related Documentation

- UserFrosting Core: `sprinkle-core/app/locale/en_US/messages.php`
- C6Admin Sprinkle: Uses similar patterns
- Translation system: UserFrosting's translator handles `@TRANSLATION` automatically

## Summary

The `@TRANSLATION` key is UserFrosting 6's convention for providing default translations for nested key groups. By implementing this pattern in the CRUD6 sprinkle, we:

1. ✅ Follow UserFrosting 6 best practices
2. ✅ Make the translation structure clearer and more semantic
3. ✅ Maintain backward compatibility with existing code
4. ✅ Align with patterns used in sprinkle-core and other official sprinkles

This enhancement, combined with the breadcrumb translation fix, ensures the CRUD6 sprinkle fully integrates with UserFrosting 6's translation system.
