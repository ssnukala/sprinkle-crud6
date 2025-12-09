# Translation Issues - Test Cases

## Overview
This document outlines test cases to verify the translation fixes for Action Modals.
These tests should be run manually in a UserFrosting 6 application with the CRUD6 sprinkle installed.

## Prerequisites
- UserFrosting 6 application running
- CRUD6 sprinkle installed
- Users model with test data (e.g., user ID 8 with first_name="John", last_name="Doe", user_name="johndoe")
- Browser console open to view debug logs

## Test Case 1: Change Password Modal - Validation Strings

**URL**: `/crud6/users/8`

**Steps**:
1. Navigate to user detail page
2. Click "Change Password" button
3. Observe modal content

**Expected Results**:
- ✅ Modal title: "Change User's Password"
- ✅ Confirmation message: "Are you sure you want to change the password for <strong>John Doe (johndoe)</strong>?"
  - NOT "for ()?""
  - NOT "for <strong> ()</strong>?"
- ✅ Field label: "Password" 
  - NOT "CRUD6.VALIDATION.ENTER_VALUE"
- ✅ Field placeholder: "Enter value"
  - NOT "CRUD6.VALIDATION.ENTER_VALUE"
- ✅ Confirm field label: "Confirm Password"
  - NOT "CRUD6.VALIDATION.CONFIRM Password"
- ✅ Confirm placeholder: "Confirm value"
  - NOT "CRUD6.VALIDATION.CONFIRM_PLACEHOLDER"
- ✅ Hint text: "Minimum 8 characters"
  - NOT "CRUD6.VALIDATION.MIN_LENGTH_HINT"
- ✅ Match hint: "Values must match"
  - NOT "CRUD6.VALIDATION.MATCH_HINT"

**Debug Console Output**:
```
[UnifiedModal] Building translation context: {
  actionKey: "password_action",
  modelLabel: "User",
  hasRecord: true,
  recordKeys: ["id", "user_name", "first_name", "last_name", "email", ...],
  contextKeys: ["model", "id", "user_name", "first_name", "last_name", ...],
  sampleContext: {
    id: 8,
    user_name: "johndoe",
    first_name: "John",
    last_name: "Doe",
    ...
  }
}
```

## Test Case 2: Toggle Enabled Modal - Record Data

**URL**: `/crud6/users/8`

**Steps**:
1. Navigate to user detail page
2. Click "Toggle Enabled" button
3. Observe modal content

**Expected Results**:
- ✅ Modal title: "Toggle Enabled"
- ✅ Confirmation message: "Are you sure you want to toggle <strong>Enabled</strong> for <strong>johndoe</strong>?"
  - NOT "toggle for ?"
  - NOT "toggle <strong>Enabled</strong> for <strong></strong>?"
- ✅ Warning text: "This action cannot be undone."
  - NOT "WARNING_CANNOT_UNDONE"
  - NOT "ACTION.CANNOT_UNDO"

**Debug Console Output**:
```
[UnifiedModal] Building translation context: {
  actionKey: "toggle_enabled",
  modelLabel: "User",
  hasRecord: true,
  recordKeys: [...],
  contextKeys: ["model", "field", "title", ...],
  sampleContext: {
    id: 8,
    user_name: "johndoe",
    first_name: "John",
    last_name: "Doe",
    title: "johndoe"  // from schema.title_field
  }
}
[UnifiedModal] Final translation context: {
  model: "User",
  field: "Enabled",  // translated from field.label
  title: "johndoe",  // from schema.title_field
  id: 8,
  user_name: "johndoe",
  first_name: "John",
  last_name: "Doe",
  ...
}
```

## Test Case 3: Toggle Verified Modal - Field Label Translation

**URL**: `/crud6/users/8`

**Steps**:
1. Navigate to user detail page
2. Click "Toggle Verified" button
3. Observe modal content

**Expected Results**:
- ✅ Modal title: "Toggle Verified"
- ✅ Confirmation message: "Are you sure you want to toggle <strong>Verified</strong> for <strong>johndoe</strong>?"
  - Field name "Verified" should be translated from `CRUD6.USER.VERIFIED`
  - Username "johndoe" should come from record data
- ✅ Warning text: "This action cannot be undone."

## Test Case 4: Delete User Modal - Complete Translation

**URL**: `/crud6/users/8`

**Steps**:
1. Navigate to user detail page
2. Click "Delete" button
3. Observe modal content

**Expected Results**:
- ✅ Modal title: "Delete User"
- ✅ Confirmation message: "Are you sure you want to delete the user <strong>John Doe (johndoe)</strong>?"
  - Full name and username should be populated
  - HTML `<strong>` tags should render properly
- ✅ Warning text: "This action cannot be undone."
- ✅ Button text: "Yes, delete User"

## Test Case 5: Translation Fallback Chain

**URL**: Any CRUD6 model detail page

**Setup**: Temporarily modify locale file to test fallback

**Steps**:
1. Remove `CRUD6.VALIDATION.ENTER_VALUE` from locale file
2. Reload page
3. Open password modal
4. Observe placeholder text

**Expected Results**:
- ✅ Should fallback to `VALIDATION.ENTER_VALUE` (root level)
- ✅ If that's missing, should show "Enter value" (hardcoded fallback)
- ✅ Should NOT show "CRUD6.VALIDATION.ENTER_VALUE" as raw key

**Debug Console Output**:
```
translateWithFallback: CRUD6.VALIDATION.ENTER_VALUE -> not found
translateWithFallback: VALIDATION.ENTER_VALUE -> found: "Enter value"
```

## Test Case 6: Locale File Compatibility

**URL**: Any CRUD6 model detail page

**Setup**: Test with examples locale file structure

**Steps**:
1. Ensure application is using `examples/locale/en_US/messages.php`
2. Navigate to user detail page
3. Test all modals

**Expected Results**:
- ✅ All validation strings should translate correctly
- ✅ Should work with both namespace structures:
  - `CRUD6.VALIDATION.ENTER_VALUE` (preferred)
  - `VALIDATION.ENTER_VALUE` (backward compat)

## Test Case 7: French Locale (fr_FR)

**URL**: `/crud6/users/8`

**Setup**: Change user locale to French

**Steps**:
1. Set user locale to `fr_FR`
2. Navigate to user detail page
3. Click "Change Password" button

**Expected Results**:
- ✅ Modal title: "Changer le mot de passe de l'utilisateur"
- ✅ Confirmation: "Êtes-vous sûr de vouloir changer le mot de passe pour <strong>John Doe (johndoe)</strong>?"
- ✅ Field label: "Mot de passe"
- ✅ Placeholder: "Entrer une valeur"
- ✅ Confirm label: "Confirmer Mot de passe"
- ✅ Hint: "Minimum 8 caractères"
- ✅ Match hint: "Les valeurs doivent correspondre"
- ✅ Warning: "Cette action ne peut pas être annulée."

## Regression Tests

### Ensure No Breaking Changes

**Test**: All existing modals still work
- ✅ Create modal
- ✅ Edit modal
- ✅ Custom action modals
- ✅ Relationship modals

**Test**: All existing translations still work
- ✅ Page titles
- ✅ Breadcrumbs
- ✅ Success/error messages
- ✅ Form labels

## Known Issues / Edge Cases

### Case 1: Record Not Loaded Yet
If modal is triggered before record data loads:
- **Expected**: Should show ID instead of name (fallback behavior)
- **Debug**: Console will show `hasRecord: false` or empty recordKeys

### Case 2: Missing Title Field
If schema doesn't define `title_field`:
- **Expected**: Should fallback to `id`
- **Debug**: Console will show `title: 8` instead of `title: "johndoe"`

### Case 3: Translation Key Typo
If schema uses wrong translation key:
- **Expected**: Should show the key itself (not crash)
- **Debug**: Console may show translation attempt for non-existent key

## Performance Considerations

### Translation Caching
- Translations are cached by UserFrosting translator
- Multiple calls to same key should be fast

### Computed Reactivity
- `translationContext` recomputes when `props.record` changes
- `promptMessage` recomputes when `translationContext` changes
- This is efficient due to Vue's computed property caching

### Debug Logging
- Debug logs only show in development mode
- Production build should strip debug statements
- No performance impact in production

## Troubleshooting Guide

### Problem: Validation strings show as raw keys

**Check**:
1. Locale file has `CRUD6.VALIDATION.*` keys
2. Or has root-level `VALIDATION.*` keys
3. `translateWithFallback()` is being called
4. Console shows translation attempts

**Fix**: Verify locale file structure matches one of the supported patterns

### Problem: Confirmation message shows empty parentheses "()"

**Check**:
1. Console log for `Building translation context`
2. Look at `recordKeys` - should include `first_name`, `last_name`, `user_name`
3. Look at `sampleContext` - should have values, not undefined

**Fix**: 
- If `hasRecord: false` - record prop not being passed
- If recordKeys are empty - API not returning fields
- If values are undefined - field names don't match

### Problem: "This action cannot be undone" shows as key

**Check**:
1. UserFrosting core provides `WARNING_CANNOT_UNDONE`
2. Fallback `ACTION.CANNOT_UNDO` exists in locale
3. Both translation lookups failing

**Fix**: Add fallback key to locale file or update to use core key

### Problem: Field label not translating

**Check**:
1. Schema defines `field.label` with translation key
2. Translation key exists in locale file
3. `getFieldLabel()` is being called

**Fix**: Add missing translation or check schema field configuration

## Success Criteria

All tests pass when:
- ✅ No raw translation keys visible to users
- ✅ All placeholders filled with actual data
- ✅ HTML formatting works correctly
- ✅ Both English and French locales work
- ✅ Fallback chain works as expected
- ✅ Debug logs provide useful information
- ✅ No console errors
- ✅ No breaking changes to existing functionality
