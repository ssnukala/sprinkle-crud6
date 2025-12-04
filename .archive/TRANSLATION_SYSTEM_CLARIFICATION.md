# UserFrosting 6 Translation System - Official Pattern Clarification

## Summary

The CRUD6 sprinkle correctly follows official UserFrosting 6 translation conventions. The use of `useTranslator()` for `{{placeholder}}` interpolation is the **official UserFrosting 6 pattern**, not a custom workaround.

## UserFrosting 6 Translation Architecture

UserFrosting 6 provides **two complementary methods** for translations in Vue components:

### 1. Simple Translations (No Placeholders)

**Use**: `$t()` function directly in templates  
**When**: Translation keys have no `{{placeholder}}` syntax  
**Example from theme-pink-cupcake**:

```vue
<template>
    {{ $t('USER') }}
    {{ $t('STATUS') }}
    {{ $t('ENABLED') }}
    {{ $t('DISABLED') }}
</template>
```

**Locale File**:
```php
'USER' => 'User',
'STATUS' => 'Status',
'ENABLED' => 'Enabled',
'DISABLED' => 'Disabled',
```

### 2. Translations with Placeholders

**Use**: `useTranslator()` composable + `translator.translate(key, params)`  
**When**: Translation keys contain `{{placeholder}}` syntax  
**Example from sprinkle-admin pattern**:

```vue
<script setup>
import { useTranslator } from '@userfrosting/sprinkle-core/stores'

const translator = useTranslator()
const groupName = 'Administrators'
</script>

<template>
    {{ translator.translate('GROUP.DELETE_CONFIRM', { name: groupName }) }}
    <!-- Output: "Are you sure you want to delete the group Administrators?" -->
</template>
```

**Locale File** (from sprinkle-admin):
```php
'GROUP' => [
    'DELETE_CONFIRM' => 'Are you sure you want to delete the group <strong>{{name}}</strong>?',
    'DELETION_SUCCESSFUL' => 'Successfully deleted group <strong>{{name}}</strong>',
    'UPDATE' => 'Details updated for group <strong>{{name}}</strong>',
],
```

## Why Two Methods?

1. **`$t()` from Vue i18n**: Handles simple key-value translations
   - Part of Vue i18n plugin installed by UserFrosting framework
   - Available globally in all templates via `app.use(i18n)`
   - Works for static translations without dynamic values

2. **`useTranslator()` from UserFrosting**: Handles `{{placeholder}}` interpolation
   - Provided by `@userfrosting/sprinkle-core/stores`
   - Required for UserFrosting's custom `{{}}` placeholder syntax
   - Vue i18n's standard interpolation uses `{placeholder}` (single braces), not `{{placeholder}}`
   - UserFrosting maintains `{{}}` syntax for consistency with backend PHP templates

## CRUD6 Implementation - Correct Pattern

### What We Use

All CRUD6 components that need placeholder interpolation use the official pattern:

```vue
<script setup>
import { useTranslator } from '@userfrosting/sprinkle-core/stores'

const translator = useTranslator()

function t(key: string, params?: Record<string, any>, fallback?: string): string {
    const translated = translator.translate(key, params)
    return (translated === key && fallback) ? fallback : translated
}
</script>

<template>
    <!-- With placeholders -->
    {{ t('CRUD6.DELETE_CONFIRM', { name: userName, model: 'User' }) }}
    
    <!-- Simple keys with fallback -->
    {{ t('VALIDATION.ENTER_VALUE', {}, 'Enter value') }}
</template>
```

### Components Using This Pattern

1. ✅ ActionModal.vue - VALIDATION.*, ACTION.* keys
2. ✅ DeleteModal.vue - CRUD6.DELETE_CONFIRM with {{name}}
3. ✅ CreateModal.vue - CRUD6.CREATE with {{model}}
4. ✅ EditModal.vue - CRUD6.EDIT with {{model}}
5. ✅ FieldEditModal.vue - VALIDATION.MIN_LENGTH_HINT with {{min}}
6. ✅ Form.vue - LOADING, CANCEL, SAVE
7. ✅ Details.vue - LOADING, ENABLED, DISABLED
8. ✅ Info.vue - USER with {{count}}
9. ✅ MasterDetailForm.vue - LOADING
10. ✅ useCRUD6Actions.ts - All action confirmations and success messages

## Evidence from Official UserFrosting Sprinkles

### sprinkle-admin Locale Files

```php
// app/locale/en_US/messages.php
'USER' => [
    'DELETION_SUCCESSFUL' => 'User <strong>{{user_name}}</strong> has been successfully deleted.',
    'DETAILS_UPDATED' => 'Account details updated for user <strong>{{user_name}}</strong>',
],
'GROUP' => [
    'DELETE_CONFIRM' => 'Are you sure you want to delete the group <strong>{{name}}</strong>?',
    'CREATION_SUCCESSFUL' => 'Successfully created group <strong>{{name}}</strong>',
    'NOT_EMPTY' => "You can't do that because there are still users associated with the group <strong>{{name}}</strong>.",
],
```

### Official UserFrosting Components

All official UserFrosting sprinkles use this same pattern:
- Import: `import { useTranslator } from '@userfrosting/sprinkle-core/stores'`
- Usage: `translator.translate(key, params)` for `{{placeholder}}` interpolation

## Common Misconceptions Addressed

### ❌ "We should only use $t() like theme-pink-cupcake"
**Incorrect**. theme-pink-cupcake uses `$t()` because GroupUsers.vue only has simple translations without placeholders. When they need placeholders, they use `useTranslator()`.

### ❌ "useTranslator() is a custom workaround"
**Incorrect**. `useTranslator()` is the **official UserFrosting 6 composable** provided by `@userfrosting/sprinkle-core/stores` specifically for handling `{{placeholder}}` interpolation.

### ❌ "We're creating a different convention"
**Incorrect**. We're following the exact same pattern used by official UserFrosting sprinkles (sprinkle-admin, sprinkle-core).

### ✅ Correct Understanding
UserFrosting 6 provides both methods:
1. `$t()` for simple translations
2. `useTranslator()` + `translator.translate()` for `{{placeholder}}` interpolation

Both are official, both are necessary, both are part of the UserFrosting 6 framework.

## Conclusion

The CRUD6 sprinkle's translation implementation is **100% compliant** with UserFrosting 6 conventions:

- ✅ Uses official `@userfrosting/sprinkle-core/stores` package
- ✅ Follows same pattern as official sprinkles (sprinkle-admin)
- ✅ Handles `{{placeholder}}` interpolation correctly
- ✅ Single locale folder: `app/locale/` (no duplicate files)
- ✅ Framework automatically loads locale files

**No changes needed** - our implementation is correct and follows official UserFrosting 6 patterns.

## References

- UserFrosting sprinkle-core: `useTranslator()` composable
- UserFrosting sprinkle-admin: Locale files with `{{placeholder}}` syntax
- UserFrosting theme-pink-cupcake: Simple `$t()` usage (no placeholders in that component)
- CRUD6 useCRUD6Actions.ts: Official pattern implementation
