# Edit and Save Fix Summary

## Issues Fixed

This document summarizes the fixes applied to resolve the Edit and Save functionality issues reported in the GitHub issue.

### Problem 1: Modal Requires Double-Click to Open
**Root Cause**: PageList.vue was tracking modals by `row.id || row.slug` while EditModal.vue used `props.crud6.id` for the modal element ID. When rows had a slug but the id wasn't directly accessible, the tracking key didn't match the modal ID.

**Fix**: 
- Updated PageList.vue to consistently use `row[schema.value?.primary_key || 'id']` for modal tracking
- Updated EditModal.vue to use `recordId` computed property based on `schema.primary_key`
- Updated DeleteModal.vue similarly for consistency

**Files Changed**:
- `app/assets/views/PageList.vue`: Lines 197-211, 214-228
- `app/assets/components/CRUD6/EditModal.vue`: Lines 23-40, template lines 36-46
- `app/assets/components/CRUD6/DeleteModal.vue`: Lines 23-45, template lines 48-65

### Problem 2: PUT Request Uses Slug Instead of ID
**Root Cause**: Form.vue line 130 was calling `updateRow(props.crud6.slug, formData.value)` which sent the slug value to the API instead of the primary key (id).

**Fix**:
- Updated Form.vue to extract the primary key from schema and use it for update operations
- Changed from `props.crud6.slug` to `props.crud6[primaryKey]` where primaryKey is `schema.value?.primary_key || 'id'`

**Files Changed**:
- `app/assets/components/CRUD6/Form.vue`: Lines 129-135

**Before**:
```javascript
const apiCall = props.crud6
    ? updateRow(props.crud6.slug, formData.value)
    : createRow(formData.value)
```

**After**:
```javascript
// Use primary_key from schema, fallback to 'id'
const primaryKey = schema.value?.primary_key || 'id'
const recordId = props.crud6 ? props.crud6[primaryKey] : null

const apiCall = recordId
    ? updateRow(recordId, formData.value)
    : createRow(formData.value)
```

### Problem 3: Button Labels Show "Group Management" Instead of "Group"
**Root Cause**: All buttons were using `schema.title` which contains verbose names like "Group Management". This is appropriate for page headers but too long for button labels.

**Fix**:
- Added `singular_title` field to schema (e.g., "Group" vs "Group Management")
- Added `modelLabel` computed property to all components that use button labels
- ModelLabel prioritizes `schema.singular_title` over capitalized model name

**Files Changed**:
- `app/schema/crud6/groups.json`: Added `"singular_title": "Group"`
- `app/assets/components/CRUD6/EditModal.vue`: Lines 30-40, template lines 37, 42
- `app/assets/components/CRUD6/DeleteModal.vue`: Lines 30-40, template lines 49, 55, 57, 62
- `app/assets/components/CRUD6/CreateModal.vue`: Lines 21-31, template lines 38, 43
- `app/assets/views/PageList.vue`: Lines 50-58, template lines 134, 200, 217
- `app/assets/views/PageRow.vue`: Lines 113-123, lines 127, 254-259
- `app/assets/components/CRUD6/Info.vue`: Lines 67-77, template lines 178, 195

**ModelLabel Logic**:
```javascript
const modelLabel = computed(() => {
    if (props.schema?.singular_title) {
        return props.schema.singular_title
    }
    // Capitalize first letter of model name as fallback
    return props.model ? props.model.charAt(0).toUpperCase() + props.model.slice(1) : 'Record'
})
```

### Problem 4: All Buttons Show Verbose Labels
**Root Cause**: Same as Problem 3 - all Create, Edit, Delete buttons were using `schema.title`.

**Fix**: Applied the same modelLabel solution to all button components (covered in Problem 3).

## Schema Changes

### New Field: `singular_title`
Added optional `singular_title` field to schema definition for cleaner button labels.

**Example**:
```json
{
  "model": "groups",
  "title": "Group Management",
  "singular_title": "Group",
  ...
}
```

**Fallback Behavior**:
- If `singular_title` is not defined, the system capitalizes the model name (e.g., "groups" → "Groups")
- Page headers still use `title` (e.g., "Group Management")
- Button labels use `singular_title` (e.g., "Edit Group")

## Testing Checklist

### Manual Testing Required

1. **Modal Opens on First Click**
   - [ ] Navigate to `/crud6/groups`
   - [ ] Click "Edit" button for any group
   - [ ] Verify modal opens immediately (not on second click)

2. **PUT Request Uses ID Not Slug**
   - [ ] Open browser DevTools Network tab
   - [ ] Edit a group (e.g., group with slug "hippo" and id 1)
   - [ ] Submit the form
   - [ ] Verify the PUT request goes to `/api/crud6/groups/1` not `/api/crud6/groups/hippo`

3. **Button Labels Are Clean**
   - [ ] On `/crud6/groups` page, verify "Create Group" button (not "Create Group Management")
   - [ ] In the actions dropdown, verify "Edit Group" (not "Edit Group Management")
   - [ ] Verify "Delete Group" (not "Delete Group Management")

4. **Page Titles Are Appropriate**
   - [ ] List page shows "Group Management" as header (full title is OK here)
   - [ ] View page shows "Hippos - Group" not "Hippos - Group Management"
   - [ ] Edit modal header shows "Edit Group"

5. **Delete Modal Works**
   - [ ] Click delete button for a group
   - [ ] Verify modal opens on first click
   - [ ] Verify API call uses ID not slug

## API Compatibility

These changes are **backward compatible**:
- Routes still accept ID in the URL (`/api/crud6/{model}/{id}`)
- Old schemas without `singular_title` will fall back to capitalized model name
- Primary key is determined from schema or defaults to 'id'

## Migration Guide for Other Models

To apply these fixes to other models (users, roles, etc.):

1. Add `singular_title` to the schema:
   ```json
   {
     "model": "users",
     "title": "User Management",
     "singular_title": "User",
     ...
   }
   ```

2. No code changes needed - the components automatically use `singular_title` if available

3. If model uses a primary key other than 'id', ensure it's defined in schema:
   ```json
   {
     "primary_key": "id",
     ...
   }
   ```

## Verification Commands

```bash
# Validate JSON schema
php -r "echo json_encode(json_decode(file_get_contents('app/schema/crud6/groups.json')), JSON_PRETTY_PRINT) ? 'groups.json valid' : 'groups.json invalid';"

# Check Vue syntax (requires node/npm)
npm run lint

# Run tests (requires composer dependencies)
vendor/bin/phpunit
```

## Related Files

### Vue Components
- `app/assets/components/CRUD6/Form.vue`
- `app/assets/components/CRUD6/EditModal.vue`
- `app/assets/components/CRUD6/DeleteModal.vue`
- `app/assets/components/CRUD6/CreateModal.vue`
- `app/assets/components/CRUD6/Info.vue`

### Views
- `app/assets/views/PageList.vue`
- `app/assets/views/PageRow.vue`

### Schema
- `app/schema/crud6/groups.json`

### Routes (No changes)
- `app/src/Routes/CRUD6Routes.php`

### API Composables (No changes)
- `app/assets/composables/useCRUD6Api.ts`

## Notes

- The fix maintains the distinction between page titles (use full `title`) and button labels (use `singular_title`)
- All modal IDs now consistently use the schema's primary_key field
- The primary_key field is respected throughout the component hierarchy
- DeleteModal also updated to use primary_key for consistency, preventing similar issues
