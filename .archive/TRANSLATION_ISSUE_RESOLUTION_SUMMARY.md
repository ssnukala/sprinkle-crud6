# Translation Issue Resolution Summary

## Issue Resolved in CRUD6 Sprinkle

The translation issue where `ACTION.CANNOT_UNDO` and `VALIDATION.*` keys were showing as untranslated text has been fixed in the CRUD6 sprinkle.

### Changes Made

#### 1. ActionModal Component Updates
- **File**: `app/assets/components/CRUD6/ActionModal.vue`
- **Changes**:
  - Added `warning` property to ModalConfig interface
  - Created `warningMessage` computed property using translator composable
  - Replaced all inline `$t()` calls with computed helper functions
  - Helper functions ensure consistent translator usage:
    - `getFieldPlaceholder()` - Translates VALIDATION.ENTER_VALUE
    - `getConfirmPlaceholder()` - Translates VALIDATION.CONFIRM_PLACEHOLDER
    - `getConfirmPrefix()` - Translates VALIDATION.CONFIRM
    - `getMinLengthHint()` - Translates VALIDATION.MIN_LENGTH_HINT
    - `getMatchHint()` - Translates VALIDATION.MATCH_HINT

#### 2. ModalConfig Interface Update
- **File**: `app/assets/composables/useCRUD6Schema.ts`
- **Change**: Added optional `warning` property to ModalConfig interface
- **Default**: For confirm-type modals, defaults to 'ACTION.CANNOT_UNDO' if not specified
- **Usage**: Can be set to custom key or empty string to disable warning

#### 3. Documentation
- **Usage Guide**: `docs/NESTED_TRANSLATION_USAGE_GUIDE.md`
- **Solution Analysis**: `.archive/NESTED_TRANSLATION_PATTERN_SOLUTION.md`
- **Example Schema**: `examples/schema/users-translation-example.json`
- **Example Locale**: `examples/locale/translation-example-messages.php`

## What Still Needs to Be Done in sprinkle-c6admin

The sprinkle-c6admin repository needs to update its locale messages to properly use nested translation syntax.

### Current Problem in sprinkle-c6admin

**Current Locale Messages** (`app/locale/en_US/messages.php`):
```php
'ADMIN' => [
    'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
    'PASSWORD_RESET_CONFIRM' => 'Are you sure you want to send a password reset link to <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
],
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
```

These messages don't include the "This action cannot be undone" warning.

### Solution for sprinkle-c6admin

Update the locale messages to include nested translation keys using `{{&KEY}}` syntax:

**Updated Locale Messages**:
```php
'ADMIN' => [
    'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
    'PASSWORD_RESET_CONFIRM' => 'Are you sure you want to send a password reset link to <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
],
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
```

### Alternative Solution

Instead of embedding `{{&ACTION.CANNOT_UNDO}}` in the locale messages, update the schema to specify warning in modal_config:

**Current Schema** (`app/schema/crud6/users.json`):
```json
{
    "key": "disable_user",
    "confirm": "CRUD6.USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm"
    }
}
```

**Updated Schema**:
```json
{
    "key": "disable_user",
    "confirm": "CRUD6.USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": "ACTION.CANNOT_UNDO"
    }
}
```

This approach keeps the confirmation message clean and uses the modal_config to specify the warning explicitly.

### Recommended Approach

Use **both** approaches for flexibility:

1. **Update locale messages** to include `{{&ACTION.CANNOT_UNDO}}` for actions that should always show this warning
2. **Use modal_config.warning** for actions that need custom warnings or no warning

**Example - Disable User (Always show warning):**
```php
// Locale
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}}</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
```

```json
// Schema - warning is already in the message
{
    "key": "disable_user",
    "confirm": "CRUD6.USER.DISABLE_CONFIRM",
    "modal_config": {
        "type": "confirm"
    }
}
```

**Example - Enable User (No warning needed):**
```php
// Locale
'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}}</strong>?',
```

```json
// Schema - explicitly disable warning
{
    "key": "enable_user",
    "confirm": "CRUD6.USER.ENABLE_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": ""
    }
}
```

**Example - Password Reset (Custom warning):**
```php
// Locale
'PASSWORD_RESET_CONFIRM' => 'Send password reset link to <strong>{{email}}</strong>?',
'PASSWORD_RESET_WARNING' => 'A password reset email will be sent immediately.',
```

```json
// Schema - custom warning
{
    "key": "password_reset",
    "confirm": "CRUD6.USER.ADMIN.PASSWORD_RESET_CONFIRM",
    "modal_config": {
        "type": "confirm",
        "warning": "CRUD6.USER.PASSWORD_RESET_WARNING"
    }
}
```

## How Nested Translation Works

### The {{&KEY}} Syntax

UserFrosting 6's translator includes support for nested translation via the `{{&KEY}}` syntax. When the translator encounters `{{&KEY}}`, it:

1. Recognizes the `&` prefix as a flag for nested translation
2. Looks up the key in the translation dictionary
3. Translates that key and replaces `{{&KEY}}` with the translated value
4. This happens recursively, so nested translations can contain other nested translations

### Implementation in useTranslator.ts

From `node_modules/@userfrosting/sprinkle-core/app/assets/stores/useTranslator.ts` (lines 215-221):

```typescript
// We check for {{&...}} strings in the resulting message.
// While the previous loop pre-translated placeholder value, this one
// pre-translate the message string vars
// We use some regex magic to detect them !
message = message.replace(/{{&(([^}]+[^a-z]))}}/g, (match, p1) => {
    return translate(p1, placeholders)
})
```

This regex matches `{{&KEY}}` patterns and recursively calls `translate()` on them.

### Example Flow

**Locale File:**
```php
'DISABLE_CONFIRM' => 'Are you sure you want to disable {{user_name}}?<br/>{{&ACTION.CANNOT_UNDO}}',
'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

**Template Call:**
```typescript
translator.translate('DISABLE_CONFIRM', { user_name: 'jdoe' })
```

**Translation Flow:**
1. Looks up `DISABLE_CONFIRM` → `'Are you sure you want to disable {{user_name}}?<br/>{{&ACTION.CANNOT_UNDO}}'`
2. Replaces `{{user_name}}` with `'jdoe'` → `'Are you sure you want to disable jdoe?<br/>{{&ACTION.CANNOT_UNDO}}'`
3. Finds `{{&ACTION.CANNOT_UNDO}}` (note the `&`)
4. Recursively looks up `ACTION.CANNOT_UNDO` → `'This action cannot be undone.'`
5. Replaces `{{&ACTION.CANNOT_UNDO}}` → `'Are you sure you want to disable jdoe?<br/>This action cannot be undone.'`

**Final Result:**
```
Are you sure you want to disable jdoe?
This action cannot be undone.
```

## Testing the Fix

### In CRUD6 Sprinkle

The CRUD6 sprinkle changes can be tested with the example files:

1. **Copy example schema** to your app:
   ```bash
   cp examples/schema/users-translation-example.json app/schema/crud6/test-users.json
   ```

2. **Copy example locale** to your app:
   ```bash
   cp examples/locale/translation-example-messages.php app/locale/en_US/
   # Merge with existing messages.php
   ```

3. **Test each action**:
   - Navigate to `/crud6/test-users/1`
   - Click each action button (Disable, Enable, Change Password, etc.)
   - Verify translations appear correctly

### In sprinkle-c6admin

After updating sprinkle-c6admin locale files:

1. **Navigate to a user detail page**: `/crud6/users/8`
2. **Test Disable User**:
   - Click "Disable User" button
   - Verify: "Are you sure you want to disable John Doe (jdoe)?"
   - Verify: "This action cannot be undone." shows as translated text, not "ACTION.CANNOT_UNDO"
3. **Test Change Password**:
   - Click "Change Password" button
   - Verify: "Are you sure you want to change the password for John Doe (jdoe)?"
   - Verify field labels show "Password", not "CRUD6.USER.PASSWORD"
   - Verify placeholder shows "Enter value", not "VALIDATION.ENTER_VALUE"
   - Verify confirm field shows "Confirm Password", not "VALIDATION.CONFIRM Password"
   - Verify hints show translated text, not "VALIDATION.MIN_LENGTH_HINT" or "VALIDATION.MATCH_HINT"

### Expected Results

**Before Fix:**
```
Are you sure you want to disable ()?
ACTION.CANNOT_UNDO

Password
VALIDATION.ENTER_VALUE
VALIDATION.CONFIRM Password
```

**After Fix:**
```
Are you sure you want to disable John Doe (jdoe)?
This action cannot be undone.

Password
Enter value
Confirm Password
Minimum 8 characters
Values must match
```

## Migration Path

### For Existing Projects Using sprinkle-c6admin

1. **Update CRUD6 sprinkle** to latest version (includes this fix)
2. **Update sprinkle-c6admin** locale files with nested translation syntax
3. **Test all custom actions** to verify translations work
4. **(Optional)** Update schemas to use explicit `warning` in modal_config for more control

### For New Projects

1. **Follow the usage guide**: `docs/NESTED_TRANSLATION_USAGE_GUIDE.md`
2. **Use the examples** as templates: `examples/schema/users-translation-example.json`
3. **Structure locale files** properly: `examples/locale/translation-example-messages.php`

## Summary

### What's Fixed in CRUD6
✅ ActionModal now consistently uses translator composable  
✅ All VALIDATION.* keys translate properly  
✅ ACTION.CANNOT_UNDO translates properly  
✅ Warning message is configurable via modal_config  
✅ Complete documentation and examples provided

### What's Needed in sprinkle-c6admin
⚠️ Update locale messages to use `{{&ACTION.CANNOT_UNDO}}` syntax  
⚠️ OR update schemas to explicitly set `modal_config.warning`  
⚠️ Test all custom actions with the updated CRUD6 sprinkle

### Reference Documentation
- **CRUD6 Usage Guide**: `docs/NESTED_TRANSLATION_USAGE_GUIDE.md`
- **Technical Solution**: `.archive/NESTED_TRANSLATION_PATTERN_SOLUTION.md`
- **Example Schema**: `examples/schema/users-translation-example.json`
- **Example Locale**: `examples/locale/translation-example-messages.php`
