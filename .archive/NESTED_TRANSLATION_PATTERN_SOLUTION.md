# Nested Translation Pattern Solution for ActionModal

## Problem Statement

Custom actions defined in schemas (like `password_action`, `disable_user`) show untranslated keys in their modals:

**Disable User Modal:**
```
Are you sure you want to disable ()?
ACTION.CANNOT_UNDO
```

**Change Password Modal:**
```
Are you sure you want to change the password for ()?
Password
VALIDATION.ENTER_VALUE
VALIDATION.CONFIRM Password
VALIDATION.CONFIRM_PLACEHOLDER
VALIDATION.MIN_LENGTH_HINT
VALIDATION.MATCH_HINT
```

But the standard Delete User modal works correctly:
```
Are you sure you want to delete the User John Doe?
This action cannot be undone.
```

## Root Cause Analysis

### 1. Cross-Sprinkle Translation Issue

The CRUD6 sprinkle's ActionModal component uses `$t('ACTION.CANNOT_UNDO')` to display the warning message. However, this translation key is defined in the CRUD6 sprinkle's locale file (`app/locale/en_US/messages.php`):

```php
'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

The issue is that when ActionModal is used with schemas from sprinkle-c6admin, the translator may not have loaded the CRUD6 sprinkle's locale dictionary yet, OR there's a namespace issue preventing cross-sprinkle translation.

### 2. Template vs Script Translation

ActionModal uses two different translation approaches:

**In Script (Line 114):**
```typescript
const promptMessage = computed(() => {
    if (!props.action.confirm) return ''
    return translator.translate(props.action.confirm, props.record)
})
```

**In Template (Line 443):**
```vue
{{ $t('ACTION.CANNOT_UNDO') || 'This action cannot be undone.' }}
```

The script uses `translator.translate()` from the composable, while the template uses `$t()` from global properties. Both should work, but there might be a timing or dictionary loading issue.

### 3. Locale Message Pattern in External Sprinkles

The confirmation messages in sprinkle-c6admin (https://github.com/ssnukala/sprinkle-c6admin/blob/main/app/locale/en_US/messages.php) are defined as:

```php
'ADMIN' => [
    'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
    'PASSWORD_RESET_CONFIRM'  => 'Are you sure you want to send a password reset link to <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
],
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
```

These messages DON'T include the "This action cannot be undone" text or the field labels. ActionModal is supposed to add those automatically.

## UserFrosting 6 Translation System

### Nested Translation Syntax

UserFrosting 6's translator supports nested translation using the `{{&KEY}}` syntax (as seen in `useTranslator.ts` lines 215-221):

```typescript
// Pre-translate message string vars using {{&KEY}} syntax
message = message.replace(/{{&(([^}]+[^a-z]))}}/g, (match, p1) => {
    return translate(p1, placeholders)
})
```

This means locale messages can include translation keys that will be looked up and translated at render time.

### Example Pattern from UserFrosting Core

Looking at how UserFrosting handles similar cases, the pattern is to include the nested translation keys directly in the message:

**Before (doesn't work):**
```php
'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
```

**After (works with nested translation):**
```php
'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>? {{&ACTION.CANNOT_UNDO}}',
```

## Solutions

### Solution 1: Update External Sprinkle Locale Messages (Recommended)

Update the locale messages in sprinkle-c6admin to include nested translation keys:

```php
'ADMIN' => [
    'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
],
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
```

**Pros:**
- Clean separation of concerns
- Messages are self-contained
- Follows UserFrosting 6 patterns
- Works across sprinkles

**Cons:**
- Requires updating external sprinkle (sprinkle-c6admin)
- Every schema author needs to know this pattern

### Solution 2: Remove Hardcoded Warning from ActionModal

Remove lines 442-444 from ActionModal.vue and rely entirely on the confirmation message from the schema:

**Current Code:**
```vue
<div v-html="promptMessage"></div>
<div v-if="modalConfig.type === 'confirm'" class="uk-text-meta">
    {{ $t('ACTION.CANNOT_UNDO') || 'This action cannot be undone.' }}
</div>
```

**Updated Code:**
```vue
<div v-html="promptMessage"></div>
<!-- Warning is now part of the promptMessage itself via {{&ACTION.CANNOT_UNDO}} -->
```

**Pros:**
- Simpler component code
- More flexible - allows different warnings per action
- Follows the pattern used by UFModalConfirmation

**Cons:**
- Requires all schema authors to add `{{&ACTION.CANNOT_UNDO}}` to their messages
- Breaks backward compatibility with existing schemas that expect automatic warning

### Solution 3: Use Translator Composable Instead of $t (Hybrid Approach)

Update ActionModal to use the translator composable consistently:

```vue
<script setup lang="ts">
const translator = useTranslator()

// Add computed for translated warning
const warningMessage = computed(() => {
    return translator.translate('ACTION.CANNOT_UNDO')
})
</script>

<template>
    <div v-html="promptMessage"></div>
    <div v-if="modalConfig.type === 'confirm'" class="uk-text-meta">
        {{ warningMessage }}
    </div>
</template>
```

**Pros:**
- Consistent use of translator throughout component
- Ensures translations are loaded before rendering

**Cons:**
- Still doesn't solve cross-sprinkle loading issues
- Doesn't address the VALIDATION.* key issues

### Solution 4: Add Warning Prop to Modal Config (Recommended for CRUD6)

Add a `warning` property to the modal config, similar to UFModalConfirmation:

**Schema:**
```json
{
    "key": "disable_user",
    "label": "CRUD6.USER.DISABLE_USER",
    "confirm": "CRUD6.USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": "ACTION.CANNOT_UNDO"
    }
}
```

**ActionModal Update:**
```typescript
const modalConfig = computed((): ModalConfig => {
    const config = props.action.modal_config || {}
    return {
        type: config.type || 'confirm',
        title: config.title || props.action.label,
        fields: config.fields || [],
        buttons: config.buttons || 'yes_no',
        warning: config.warning || (config.type === 'confirm' ? 'ACTION.CANNOT_UNDO' : undefined)
    }
})

const warningMessage = computed(() => {
    if (!modalConfig.value.warning) return ''
    return translator.translate(modalConfig.value.warning)
})
```

**Template:**
```vue
<div v-html="promptMessage"></div>
<div v-if="warningMessage" class="uk-text-meta">
    {{ warningMessage }}
</div>
```

**Pros:**
- Explicit control over warning message per action
- Default behavior remains the same
- Can override or disable warning per action
- Follows UFModalConfirmation pattern

**Cons:**
- Requires schema updates to override default
- Adds complexity to modal config

## Recommended Implementation

**Combine Solution 1 and Solution 4:**

1. **Update CRUD6's ActionModal** to support `warning` property in modal config (Solution 4)
2. **Document the pattern** for schema authors to use `{{&ACTION.CANNOT_UNDO}}` in their locale messages (Solution 1)
3. **Create examples** showing both approaches

This provides flexibility while maintaining backward compatibility.

## Field Label Translation Issue

The VALIDATION.* keys (ENTER_VALUE, CONFIRM, etc.) are showing untranslated because they're being used in template expressions like:

```vue
:placeholder="$t('VALIDATION.ENTER_VALUE') || `Enter ${getFieldLabel(field.key, field.config).toLowerCase()}`"
```

These should work if the CRUD6 locale is loaded. The issue might be:

1. Dictionary not loaded yet when modal opens
2. Keys not in the loaded dictionary
3. $t function not working correctly

**Fix:** Use the translator composable consistently:

```vue
<script setup lang="ts">
const getPlaceholder = (field: { key: string, config?: SchemaField }) => {
    const label = getFieldLabel(field.key, field.config)
    return translator.translate('VALIDATION.ENTER_VALUE') || `Enter ${label.toLowerCase()}`
}
</script>

<template>
    :placeholder="getPlaceholder(field)"
</template>
```

## Testing Recommendations

1. **Verify locale loading:** Add debug logging to confirm dictionary includes ACTION.CANNOT_UNDO and VALIDATION.* keys
2. **Test timing:** Check if dictionary is loaded before ActionModal renders
3. **Test cross-sprinkle:** Verify c6admin locale keys are accessible from CRUD6 components
4. **Test placeholder interpolation:** Verify user data is being passed to translator correctly

## Next Steps

1. Implement Solution 4 in ActionModal.vue
2. Document the `{{&KEY}}` pattern for schema authors
3. Create example schemas showing proper usage
4. Update sprinkle-c6admin to include `{{&ACTION.CANNOT_UNDO}}` in confirmation messages
5. Add tests to verify translations work across sprinkles

## References

- UserFrosting Translator: `node_modules/@userfrosting/sprinkle-core/app/assets/stores/useTranslator.ts`
- UFModalConfirmation: `node_modules/@userfrosting/theme-pink-cupcake/src/components/Modals/UFModalConfirmation.vue`
- CRUD6 Locale: `app/locale/en_US/messages.php`
- C6Admin Schema: https://github.com/ssnukala/sprinkle-c6admin/blob/main/app/schema/crud6/users.json
- C6Admin Locale: https://github.com/ssnukala/sprinkle-c6admin/blob/main/app/locale/en_US/messages.php
