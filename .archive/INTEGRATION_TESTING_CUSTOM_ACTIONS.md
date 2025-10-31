# Integration Testing Guide - Custom Actions and Multiple Details

## Overview

This guide provides step-by-step instructions for testing the new Custom Actions and Multiple Detail Sections features in CRUD6.

## Prerequisites

- UserFrosting 6 application with CRUD6 sprinkle installed
- Database with users table and related tables (activities, roles, permissions)
- User account with appropriate permissions

## Test Scenarios

### Test 1: Multiple Detail Sections

**Objective**: Verify that multiple relationship tables display correctly on a user detail page.

**Steps:**

1. Create or update `app/schema/crud6/users.json` with multiple details:
```json
{
  "model": "users",
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id",
      "list_fields": ["type", "message", "created_at"],
      "title": "USER.ACTIVITIES"
    },
    {
      "model": "roles", 
      "foreign_key": "user_id",
      "list_fields": ["name", "slug"],
      "title": "USER.ROLES"
    }
  ]
}
```

2. Navigate to `/crud6/users/1` (or any valid user ID)

3. **Expected Results:**
   - User information card displays on the left (1/3 width)
   - Two detail sections display on the right (2/3 width):
     - Activities table with type, message, created_at columns
     - Roles table with name, slug columns
   - Both tables load data via separate API calls
   - Each table has its own card container with title

4. **Validation:**
   - Check browser console for schema loading logs
   - Verify API calls to `/api/crud6/users/1/activities` and `/api/crud6/users/1/roles`
   - Confirm no JavaScript errors
   - Verify tables display correct data

### Test 2: Custom Action Buttons - Field Update (Toggle)

**Objective**: Test toggle-type field update actions.

**Steps:**

1. Add toggle action to `app/schema/crud6/users.json`:
```json
{
  "actions": [
    {
      "key": "toggle_enabled",
      "label": "Toggle Enabled",
      "icon": "power-off",
      "type": "field_update",
      "field": "flag_enabled",
      "toggle": true,
      "style": "default",
      "success_message": "User status updated"
    }
  ],
  "fields": {
    "flag_enabled": {
      "type": "boolean",
      "label": "Enabled",
      "viewable": true
    }
  }
}
```

2. Navigate to `/crud6/users/1`

3. Click the "Toggle Enabled" button

4. **Expected Results:**
   - Button shows icon and label
   - Clicking button updates `flag_enabled` field
   - Boolean value toggles (true ↔ false)
   - Success message appears: "User status updated"
   - Info card refreshes showing new value
   - No page reload required

5. **Validation:**
   - Check API call to `/api/crud6/users/1/field`
   - Verify request payload contains field name and new value
   - Confirm database value actually changed
   - Verify UI updates without full refresh

### Test 3: Custom Action Buttons - Field Update (Set Value)

**Objective**: Test set-value type field update actions.

**Steps:**

1. Add disable action to schema:
```json
{
  "actions": [
    {
      "key": "disable_user",
      "label": "Disable User",
      "icon": "ban",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "style": "danger",
      "confirm": "Are you sure you want to disable this user?",
      "success_message": "User disabled successfully"
    }
  ]
}
```

2. Navigate to enabled user page `/crud6/users/1`

3. Click "Disable User" button

4. **Expected Results:**
   - Confirmation dialog appears with message
   - Clicking "OK" sets `flag_enabled` to false
   - Success message displays
   - Info card refreshes
   - User shown as disabled

5. **Validation:**
   - Confirm confirmation dialog appears before execution
   - Verify clicking "Cancel" aborts the action
   - Check that field is set to exact value (false)
   - Confirm database updated correctly

### Test 4: Custom Action Buttons - Route Navigation

**Objective**: Test route navigation actions.

**Steps:**

1. Add navigation action to schema:
```json
{
  "actions": [
    {
      "key": "change_password",
      "label": "Change Password",
      "icon": "key",
      "type": "route",
      "route": "user.password",
      "style": "primary"
    }
  ]
}
```

2. Navigate to `/crud6/users/1`

3. Click "Change Password" button

4. **Expected Results:**
   - Browser navigates to password change route
   - Route parameters include user ID
   - No API call made
   - Navigation is immediate

5. **Validation:**
   - Verify correct route name used
   - Check URL parameters passed correctly
   - Confirm browser history updated

### Test 5: Custom Action Buttons - API Call

**Objective**: Test custom API call actions.

**Steps:**

1. Add API call action to schema:
```json
{
  "actions": [
    {
      "key": "reset_password",
      "label": "Reset Password",
      "icon": "envelope",
      "type": "api_call",
      "endpoint": "/api/users/{id}/password/reset",
      "method": "POST",
      "style": "secondary",
      "confirm": "Send password reset email to this user?",
      "success_message": "Password reset email sent successfully"
    }
  ]
}
```

2. Navigate to `/crud6/users/1`

3. Click "Reset Password" button

4. **Expected Results:**
   - Confirmation dialog appears
   - Clicking OK makes POST request to `/api/users/1/password/reset`
   - Success message displays after completion
   - No page refresh
   - Loading indicator shown during request

5. **Validation:**
   - Check network tab for API call
   - Verify correct HTTP method (POST)
   - Verify `{id}` placeholder replaced with actual ID
   - Confirm success/error handling works
   - Check that email actually sent (if endpoint exists)

### Test 6: Permission-Based Visibility

**Objective**: Test that actions respect permissions.

**Steps:**

1. Add permission-restricted action:
```json
{
  "actions": [
    {
      "key": "admin_action",
      "label": "Admin Action",
      "type": "field_update",
      "field": "some_field",
      "value": true,
      "permission": "admin_only_permission"
    }
  ]
}
```

2. Test with user WITHOUT permission

3. **Expected Results:**
   - Action button does not appear
   - No errors in console

4. Test with user WITH permission

5. **Expected Results:**
   - Action button appears
   - Button is clickable and functional

6. **Validation:**
   - Verify `hasPermission()` function called
   - Confirm permission checking works correctly

### Test 7: Backward Compatibility - Single Detail

**Objective**: Verify existing single `detail` configuration still works.

**Steps:**

1. Use schema with single detail (old format):
```json
{
  "detail": {
    "model": "activities",
    "foreign_key": "user_id",
    "list_fields": ["type", "message"],
    "title": "Activities"
  }
}
```

2. Navigate to detail page

3. **Expected Results:**
   - Single detail section displays correctly
   - Internally converted to array format
   - No breaking changes
   - Same behavior as before

4. **Validation:**
   - Check that `detailConfigs` computed property converts to array
   - Verify single detail renders in same position
   - Confirm no console warnings

### Test 8: Action Button Styling

**Objective**: Test different button styles.

**Steps:**

1. Add actions with different styles:
```json
{
  "actions": [
    {"key": "a1", "label": "Primary", "type": "route", "route": "test", "style": "primary"},
    {"key": "a2", "label": "Secondary", "type": "route", "route": "test", "style": "secondary"},
    {"key": "a3", "label": "Default", "type": "route", "route": "test", "style": "default"},
    {"key": "a4", "label": "Danger", "type": "route", "route": "test", "style": "danger"}
  ]
}
```

2. Navigate to detail page

3. **Expected Results:**
   - Buttons display with correct UIKit classes
   - Primary: blue button
   - Secondary: gray button
   - Default: light button
   - Danger: red button

4. **Validation:**
   - Inspect button elements for `uk-button-{style}` classes
   - Verify visual appearance matches style

## Common Issues and Solutions

### Issue: Detail sections not displaying
**Solution:** 
- Check API endpoint exists: `/api/crud6/{model}/{id}/{relatedModel}`
- Verify foreign_key matches actual database column
- Check permissions for viewing related data

### Issue: Action buttons not appearing
**Solution:**
- Verify `actions` array exists in schema
- Check user has required permissions
- Ensure action has valid `key`, `label`, and `type`

### Issue: Field update not working
**Solution:**
- Verify field exists in database
- Check user has update permission
- Ensure field is not read-only

### Issue: API call fails
**Solution:**
- Verify endpoint URL is correct
- Check backend route exists
- Verify CSRF token handling

## Performance Testing

### Load Time Test
1. Navigate to page with 3 detail sections
2. Measure time to full render
3. Check number of API calls (should be 4: schema + 3 details)

### Multiple Actions Test
1. Add 10 custom actions to schema
2. Verify page still loads quickly
3. Check that buttons render without lag

## Browser Compatibility

Test in:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)

## Success Criteria

✅ Multiple detail sections display correctly
✅ Custom actions execute without errors
✅ Field updates persist to database
✅ Permissions respected
✅ Backward compatibility maintained
✅ No console errors
✅ Responsive layout works
✅ Loading states display properly

## Example Test Data

Create test users with:
- Various flag_enabled values (true/false)
- Associated activities records
- Associated roles
- Associated permissions

This ensures thorough testing of all features.
