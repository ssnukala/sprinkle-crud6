# Translation {{model}} Placeholder Fix - Summary

## Issue Description
The `{{model}}` placeholder in translation strings was not being populated with actual model names, resulting in:
- PageList showing "{{model}}" instead of "Group Management" or similar model titles
- PageRow showing "{{model}}" in page titles and button labels
- Info component buttons showing "{{model}}" instead of actual model names
- Record details not showing in PageRow view mode due to incorrect data access

## Root Causes

### 1. Missing Translation Parameters
UserFrosting's i18n system requires passing data objects as the second parameter to `$t()` to populate placeholders like `{{model}}`. The Vue components were calling `$t('CRUD6.CREATE')` without passing the model name.

**Example from locale file:**
```php
'CREATE' => 'Create {{model}}',
'EDIT' => 'Edit {{model}}',
'DELETE' => 'Delete {{model}}',
```

**Before (incorrect):**
```vue
{{ $t('CRUD6.CREATE') }}
```

**After (correct):**
```vue
{{ $t('CRUD6.CREATE', { model: schema.title || model }) }}
```

### 2. Incorrect Data Property Access in PageRow
The `fetchRow()` composable already unwraps the API response, returning the data directly. The PageRow component was incorrectly accessing `fetchedRow.data`, trying to access a non-existent nested property.

**Before (incorrect):**
```typescript
fetchPromise.then((fetchedRow) => {
    CRUD6Row.value = fetchedRow.data  // ❌ Wrong - .data doesn't exist
    record.value = fetchedRow.data
})
```

**After (correct):**
```typescript
fetchPromise.then((fetchedRow) => {
    CRUD6Row.value = fetchedRow  // ✅ Correct - fetchRow already returns unwrapped data
    record.value = fetchedRow
})
```

## Files Modified

### 1. PageList.vue
- **Line 125**: Added model parameter to CREATE button: `$t('CRUD6.CREATE', { model: schema.title || model })`
- **Line 200**: Added model parameter to EDIT action: `$t('CRUD6.EDIT', { model: schema.title || model })`
- **Line 217**: Added model parameter to DELETE action: `$t('CRUD6.DELETE', { model: schema.title || model })`
- **Lines 82-89**: Updated page metadata (title and description) to use schema title

### 2. PageRow.vue
- **Line 303**: Added model parameter to CREATE/EDIT title: `$t('CRUD6.CREATE', { model: schema?.title || model })`
- **Lines 121-123**: Fixed data access - removed incorrect `.data` property access
- **Lines 245-254**: Added page title/description updates when schema loads

### 3. Info.vue
- **Line 169**: Added model parameter to EDIT button: `$t('CRUD6.EDIT', { model: finalSchema.title || model })`
- **Line 186**: Added model parameter to DELETE button: `$t('CRUD6.DELETE', { model: finalSchema.title || model })`

### 4. CreateModal.vue
- **Line 38**: Added model parameter to button slot: `$t('CRUD6.CREATE', { model: schema?.title || model })`
- **Line 43**: Added model parameter to modal header: `$t('CRUD6.CREATE', { model: schema?.title || model })`

### 5. EditModal.vue
- **Line 37**: Added model parameter to button slot: `$t('CRUD6.EDIT', { model: schema?.title || model })`
- **Line 42**: Added model parameter to modal header: `$t('CRUD6.EDIT', { model: schema?.title || model })`

### 6. DeleteModal.vue
- **Line 49**: Added model parameter to button slot: `$t('CRUD6.DELETE', { model: schema?.title || model })`
- **Line 55**: Added model parameter to modal title: `:title="$t('CRUD6.DELETE', { model: schema?.title || model })"`
- **Line 57**: Added model parameter to accept button: `:acceptLabel="$t('CRUD6.DELETE_YES', { model: schema?.title || model })"`
- **Line 62**: Added model parameter to confirmation prompt: `$t('CRUD6.DELETE_CONFIRM', { ...props.crud6, model: schema?.title || model })`

## Translation Strategy

The fix uses a consistent pattern across all components:

```vue
$t('CRUD6.TRANSLATION_KEY', { model: schema?.title || model })
```

This ensures:
1. **Primary**: Uses `schema.title` if available (e.g., "Group Management")
2. **Fallback**: Uses raw `model` name if schema not yet loaded (e.g., "groups")
3. **Safe**: Uses optional chaining (`?.`) to prevent errors during initial load

## Page Metadata Updates

Both PageList and PageRow now properly set page metadata:

**PageList.vue:**
```typescript
page.title = schema.value.title || model.value
page.description = schema.value.description || `A listing of the ${schema.value.title || model.value}...`
```

**PageRow.vue:**
```typescript
// On schema load
page.title = `Create ${schema.value.title || newModel}`
page.description = schema.value.description || `Create a new ${schema.value.title || newModel}`

// On record fetch
page.title = `${recordName} - ${schema.value?.title || model.value}`
```

## Testing Recommendations

Since this sprinkle requires a full UserFrosting 6 application context, testing should be performed in an integrated environment:

1. **Navigate to any CRUD6 list page** (e.g., `/crud6/groups`)
   - Verify page title shows schema title (e.g., "Group Management")
   - Verify "Create" button shows "Create Group Management" not "Create {{model}}"
   - Verify table headers and actions show proper model names

2. **Navigate to a CRUD6 record detail page** (e.g., `/crud6/groups/1`)
   - Verify page title shows record name and model (e.g., "Administrators - Group Management")
   - Verify record details are displayed in the Info card
   - Verify "Edit" button shows "Edit Group Management"
   - Verify "Delete" button shows "Delete Group Management"

3. **Test modal dialogs**
   - Click "Edit" and verify modal title shows "Edit Group Management"
   - Click "Delete" and verify confirmation dialog shows proper model name
   - Click "Create" and verify modal title shows "Create Group Management"

## Benefits

1. **User Experience**: Properly labeled buttons and titles improve clarity
2. **Internationalization**: Translations work correctly with model names
3. **Consistency**: All components use the same translation pattern
4. **Schema-Driven**: Uses schema titles when available for better UX (e.g., "Group Management" instead of "groups")
5. **Bug Fix**: Record details now display correctly in PageRow view mode

## Schema Title vs Model Name

The fix prioritizes using `schema.title` over the raw model name:
- **schema.title**: "Group Management" (user-friendly, defined in schema)
- **model**: "groups" (technical, from route parameter)

Example from schema:
```json
{
  "model": "groups",
  "title": "Group Management",
  "description": "Manage user groups and roles"
}
```

This provides better UX by showing human-readable titles instead of technical model names.
