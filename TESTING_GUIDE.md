# Quick Testing Guide for Issue Fixes

## Prerequisites
- UserFrosting 6 application running
- CRUD6 sprinkle installed and configured
- Groups model with users relationship configured
- At least one group with users assigned

## Test 1: Breadcrumb Visibility on List Page

### Steps:
1. Navigate to: `http://localhost/crud6/groups`
2. Observe the breadcrumb area at the top of the page

### Expected Result:
✅ Breadcrumb should display immediately (no flash of empty breadcrumb)
✅ Initial breadcrumb shows: "UserFrosting / Groups"
✅ After schema loads (~100ms), breadcrumb updates to: "UserFrosting / Group Management"

### Failure Indicators:
❌ Empty breadcrumb area
❌ Flash of blank content then breadcrumb appears
❌ Breadcrumb shows "UserFrosting / " with trailing space

---

## Test 2: Breadcrumb Visibility on Detail Page

### Steps:
1. Navigate to: `http://localhost/crud6/groups/1`
2. Observe the breadcrumb area at the top of the page

### Expected Result:
✅ Breadcrumb shows immediately: "UserFrosting / Groups / Group Management"
✅ Or: "UserFrosting / Group Management / [Record Name]"
✅ No flash of empty breadcrumb

### Failure Indicators:
❌ Empty breadcrumb area
❌ Breadcrumb missing intermediate level
❌ Breadcrumb shows "UserFrosting / "

---

## Test 3: Users Table Loads Data on Group Detail

### Steps:
1. Navigate to: `http://localhost/crud6/groups/1` (where group ID 1 has users)
2. Look for the "Users in this group" section on the right side
3. Observe the loading behavior

### Expected Result:
✅ Brief loading spinner appears (<500ms)
✅ Table header shows: "Users in this group" (translated)
✅ Table columns show: Username, Email, First Name, Last Name, Enabled
✅ Table shows user data rows
✅ Table pagination shows correct count: "Showing 1 - X of Y"

### Failure Indicators:
❌ Table shows "Showing 0 - 0 of 0"
❌ No loading spinner appears
❌ Table headers show field keys instead of labels (e.g., "user_name" instead of "Username")
❌ Table section is empty or doesn't render

### Debug Steps if Fails:
1. Open browser DevTools Console
2. Look for errors related to:
   - `/api/crud6/groups/1/users` endpoint
   - Schema loading for "users" model
3. Check Network tab:
   - Should see GET request to `/api/crud6/users/schema`
   - Should see GET request to `/api/crud6/groups/1/users`
4. Verify the groups schema has correct detail configuration:
   ```json
   "detail": {
     "model": "users",
     "foreign_key": "group_id",
     "list_fields": ["user_name", "email", "first_name", "last_name", "flag_enabled"],
     "title": "GROUP.USERS"
   }
   ```

---

## Test 4: Edit Button Opens Modal on First Click

### Steps:
1. Navigate to: `http://localhost/crud6/groups`
2. Click the "Actions" button on any group row
3. Click "Edit Group" in the dropdown menu

### Expected Result:
✅ Dropdown menu stays open after clicking Edit
✅ Edit modal opens immediately
✅ No need to click Actions button again

### Failure Indicators:
❌ Dropdown closes immediately after clicking Edit
❌ Modal doesn't open
❌ Need to click Actions again to see Edit option
❌ Need to click Edit twice to open modal

---

## Test 5: Delete Button Opens Modal on First Click

### Steps:
1. Navigate to: `http://localhost/crud6/groups`
2. Click the "Actions" button on any group row
3. Click "Delete Group" in the dropdown menu

### Expected Result:
✅ Dropdown menu stays open after clicking Delete
✅ Delete confirmation modal opens immediately
✅ No need to click Actions button again

### Failure Indicators:
❌ Dropdown closes immediately after clicking Delete
❌ Modal doesn't open
❌ Need to click Actions again to see Delete option
❌ Need to click Delete twice to open modal

---

## Performance Testing

### Schema Loading Performance:
1. Open browser DevTools Network tab
2. Navigate to `http://localhost/crud6/groups/1`
3. Check for duplicate schema requests

### Expected Result:
✅ Only ONE request to `/api/crud6/groups/schema`
✅ Only ONE request to `/api/crud6/users/schema` (for detail section)
✅ Total page load time < 1 second

### Failure Indicators:
❌ Multiple requests to same schema endpoint
❌ Schema requests fired in sequence instead of parallel
❌ Page load time > 2 seconds

---

## Browser Console Checks

### Expected Console Output:
```
[PageList] Loading schema for model: groups
[PageList] Schema loaded successfully for: groups
[Details] Loading schema for users
```

### Should NOT see:
```
❌ Failed to load schema
❌ Schema not found
❌ Duplicate schema load
❌ Error: Cannot read property 'fields' of null
```

---

## Visual Inspection Checklist

### List Page (`/crud6/groups`):
- [ ] Breadcrumb visible and correct
- [ ] Table loads with group data
- [ ] Actions dropdown works smoothly
- [ ] Edit modal opens on first click
- [ ] Delete modal opens on first click

### Detail Page (`/crud6/groups/1`):
- [ ] Breadcrumb visible and correct
- [ ] Left side shows group info card
- [ ] Right side shows users table
- [ ] Users table has data (if group has users)
- [ ] Loading spinner appears briefly
- [ ] No console errors

---

## Common Issues and Solutions

### Issue: Breadcrumb still empty
**Possible Causes:**
- Browser cache showing old version
- UserFrosting core breadcrumb component needs update
- Page metadata not syncing properly

**Solution:**
- Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
- Clear browser cache
- Check UserFrosting core version compatibility

### Issue: Users table still shows 0 records
**Possible Causes:**
- Group actually has no users assigned
- API endpoint not configured correctly
- Schema detail configuration missing

**Solution:**
- Assign users to the test group
- Verify `/api/crud6/groups/1/users` endpoint returns data
- Check `app/schema/crud6/groups.json` has detail configuration

### Issue: Modal still requires double-click
**Possible Causes:**
- Browser cache showing old version
- JavaScript not reloading properly

**Solution:**
- Hard refresh browser
- Clear browser cache and reload
- Check browser console for JavaScript errors

---

## Success Criteria

All tests pass if:
- ✅ Breadcrumbs appear immediately on all pages
- ✅ Detail section tables load data correctly
- ✅ Edit/Delete modals open on first click
- ✅ No console errors
- ✅ Performance is acceptable (< 1 second page load)
- ✅ User experience is smooth and responsive
