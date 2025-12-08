# Validation Checklist for Breadcrumb and Translation Fixes

**Branch:** copilot/fix-breadcrumb-translation-issues  
**Commits:** fab0055, e742c03  
**Testing Context:** https://github.com/ssnukala/sprinkle-c6admin with users.json schema

## Pre-Testing Setup

1. Install sprinkle-crud6 with this branch:
   ```bash
   composer require ssnukala/sprinkle-crud6:dev-copilot/fix-breadcrumb-translation-issues
   ```

2. Ensure sprinkle-c6admin is configured with users schema
3. Verify translation files are loaded (check locale/en_US/messages.php)
4. Clear any caches

## Breadcrumb Testing

### Test 1: Initial Page Load
**Steps:**
1. Navigate to users list page: `/crud6/users`
2. Click on a user (e.g., user with ID 8)
3. Observe breadcrumb trail

**Expected Result:**
```
UserFrosting / Admin Panel / User / user01
```
(or similar, with username from title_field)

**Not Expected:**
- Empty breadcrumb segments: `User /   /`
- Duplicate entries: `Users / User`

### Test 2: Page Refresh
**Steps:**
1. While on `/crud6/users/8`, press F5 to refresh
2. Wait for page to fully load
3. Observe breadcrumb trail

**Expected Result:**
```
UserFrosting / Admin Panel / User / user01
```
(Same as Test 1 - no change in structure)

**Not Expected:**
- Duplicate "Users" appearing: `Users / User / user01`
- Different structure from initial load

### Test 3: Direct URL Navigation
**Steps:**
1. Clear browser cache/storage
2. Navigate directly to `/crud6/users/8` via URL bar
3. Observe breadcrumb trail

**Expected Result:**
```
UserFrosting / Admin Panel / User / user01
```

**Not Expected:**
- Missing breadcrumb segments
- Placeholders like `{{model}}` or `CRUD6.PAGE`

### Test 4: Navigation Between Users
**Steps:**
1. On `/crud6/users/8`, observe breadcrumb
2. Navigate to `/crud6/users/9` (different user)
3. Observe breadcrumb update

**Expected Result:**
- Breadcrumb updates to show new user: `User / user02` (or new username)
- No duplicate "User" entries
- Clean transition

### Test 5: Back Navigation
**Steps:**
1. Navigate: List → User 8 → User 9 → Browser Back button
2. Observe breadcrumb on User 8 page

**Expected Result:**
- Breadcrumb shows correct user: `User / user01`
- No accumulated breadcrumbs from navigation history

## VALIDATION Translation Testing

### Test 6: Password Change Modal
**Steps:**
1. On user detail page, click "Change Password" action (if available in schema)
2. Observe modal labels and placeholders

**Expected Result:**
- Field label shows: "Enter value" (not "VALIDATION.ENTER_VALUE")
- Confirm field shows: "Confirm" (not "VALIDATION.CONFIRM")
- Placeholder shows: "Confirm value" (not "VALIDATION.CONFIRM_PLACEHOLDER")

**Not Expected:**
- Any translation keys visible (e.g., "VALIDATION.*")

### Test 7: Password Validation Errors
**Steps:**
1. In password change modal, enter a short password (e.g., "123")
2. Attempt to save
3. Observe error message

**Expected Result:**
- Error shows: "Minimum 8 characters required" (with actual number)
- Not: "VALIDATION.MIN_LENGTH"

### Test 8: Password Mismatch Error
**Steps:**
1. In password change modal, enter password in first field
2. Enter different password in confirm field
3. Observe error message

**Expected Result:**
- Error shows: "Fields must match" or "Values must match"
- Not: "VALIDATION.FIELDS_MUST_MATCH"

### Test 9: UFAlert Messages
**Steps:**
1. Perform any action that triggers an alert (create, update, delete user)
2. Observe the alert message that appears

**Expected Result:**
- Alert shows proper translated message
- No translation keys visible

**Not Expected:**
- Keys like "CRUD6.CREATE.SUCCESS" showing literally
- Keys like "VALIDATION.*" in alerts

### Test 10: Field Hints
**Steps:**
1. In any form with validation (create/edit user), hover over or focus on password field
2. Observe any hint/help text

**Expected Result:**
- Hint shows: "Minimum X characters" (with actual number)
- Not: "VALIDATION.MIN_LENGTH_HINT"

## Multi-Language Testing (Optional)

### Test 11: French Locale
**Steps:**
1. Switch UserFrosting locale to French (fr_FR)
2. Repeat Tests 1-5 for breadcrumbs
3. Observe breadcrumb labels

**Expected Result:**
- Breadcrumbs show French translations where available
- Structure remains consistent

### Test 12: French VALIDATION Messages
**Steps:**
1. With French locale active
2. Repeat Tests 6-10 for validation
3. Observe validation messages

**Expected Result:**
- Validation messages appear in French:
  - "Entrer une valeur"
  - "Confirmer"
  - "Minimum X caractères requis"
  - "Les champs doivent correspondre"

## Code Review Checks

### ✓ PageRow.vue Changes
- [x] Line 375: Uses `modelLabel.value` instead of `schemaTitle`
- [x] Comment added explaining why singular is used
- [x] No other unintended changes

### ✓ useCRUD6Breadcrumbs.ts Changes
- [x] New condition checks `crumb.to === listPath` (around line 376)
- [x] Empty recordTitle handling added (around line 387-392)
- [x] Debug logging added for troubleshooting
- [x] Deduplication logic remains intact

### ✓ UnifiedModal.vue Changes
- [x] Line 116-120: All VALIDATION keys prefixed with `CRUD6.`
- [x] Line 465: `CRUD6.VALIDATION.FIELDS_MUST_MATCH`
- [x] Line 472: `CRUD6.VALIDATION.MIN_LENGTH`
- [x] No other translation keys missed

### ✓ Translation Files
- [x] `en_US/messages.php`: VALIDATION moved under CRUD6 namespace
- [x] `fr_FR/messages.php`: VALIDATION moved under CRUD6 namespace
- [x] All VALIDATION keys present in both languages
- [x] No syntax errors in PHP files

## Regression Testing

### Test 13: List Page Still Works
**Steps:**
1. Navigate to `/crud6/users` (list page)
2. Verify page loads correctly
3. Check breadcrumb

**Expected Result:**
- List page works normally
- Breadcrumb shows: `UserFrosting / Admin Panel / Users` (plural OK for list)

### Test 14: Other CRUD6 Models
**Steps:**
1. Navigate to other CRUD6 model detail pages (e.g., roles, permissions)
2. Check breadcrumbs
3. Test validation in those models' forms

**Expected Result:**
- Same breadcrumb behavior (no duplication)
- Same validation translation behavior

### Test 15: Create Mode
**Steps:**
1. Navigate to create new user page
2. Check breadcrumb
3. Submit form with validation errors

**Expected Result:**
- Breadcrumb appropriate for create mode
- Validation messages translated

## Sign-Off

### Developer Testing
- [ ] All breadcrumb tests pass (Tests 1-5, 13-14)
- [ ] All validation tests pass (Tests 6-10, 14-15)
- [ ] Multi-language tests pass if applicable (Tests 11-12)
- [ ] Code review checks complete
- [ ] No console errors in browser
- [ ] No PHP errors in logs

### User Acceptance Testing  
- [ ] Breadcrumbs display correctly and consistently
- [ ] No untranslated keys visible to users
- [ ] Navigation feels natural and breadcrumbs update properly
- [ ] Forms show proper validation messages

### Performance
- [ ] No noticeable slowdown in page loads
- [ ] No memory leaks from breadcrumb updates
- [ ] Console logging not excessive (debug mode)

## Issues Found During Testing

Document any issues here:

1. Issue: 
   - Steps to reproduce:
   - Expected:
   - Actual:
   - Severity:

## Notes

- This fix is scoped to the CRUD6 sprinkle breadcrumb behavior
- VALIDATION translations now scoped to CRUD6 namespace to avoid conflicts
- Testing requires full UserFrosting 6 + sprinkle-c6admin setup
- Debug logging available via browser console for troubleshooting
