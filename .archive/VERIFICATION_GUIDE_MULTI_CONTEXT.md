# Manual Verification Guide - Multi-Context Schema API

## How to Verify the Changes

### 1. Backend Verification (PHP)

**Check that the SchemaService handles multi-context requests:**

```bash
# All PHP files should have valid syntax
find app/src -name "*.php" -exec php -l {} \;

# Specifically check SchemaService
php -l app/src/ServicesProvider/SchemaService.php
```

**Verify the method exists:**

```php
<?php
// In a PHP console or script with autoloader
$reflection = new ReflectionClass('UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService');
$method = $reflection->getMethod('filterSchemaForContext');
var_dump($method->isPublic()); // Should be true
```

### 2. API Endpoint Testing

**Test the schema endpoint with different context combinations:**

```bash
# Single context (backward compatibility)
curl "http://localhost/api/crud6/users/schema?context=list"

# Multi-context (new feature)
curl "http://localhost/api/crud6/users/schema?context=list,form"

# Three contexts
curl "http://localhost/api/crud6/users/schema?context=list,form,detail"

# Full schema (backward compatibility)
curl "http://localhost/api/crud6/users/schema"
```

**Expected response for multi-context:**

```json
{
  "message": "Schema retrieved successfully",
  "model": "users",
  "modelDisplayName": "User",
  "schema": {
    "model": "users",
    "title": "User Management",
    "singular_title": "User",
    "primary_key": "id",
    "permissions": { ... },
    "contexts": {
      "list": {
        "fields": { ... },
        "default_sort": { ... }
      },
      "form": {
        "fields": { ... }
      }
    }
  }
}
```

### 3. Frontend Verification (Browser DevTools)

**Open browser DevTools Network tab and navigate to a CRUD6 list page:**

1. Open DevTools (F12)
2. Go to Network tab
3. Filter by "schema" or "crud6"
4. Navigate to `/crud6/users` or `/crud6/groups`

**Before the fix:**
- You should see 1 request: `schema?context=list,form`

**What you're checking:**
- Only ONE schema API call is made on page load
- The request includes `context=list,form` parameter
- The response includes a `contexts` object with both `list` and `form` sections

**To verify no additional calls when opening modals:**

1. Clear the Network log
2. Click "Create User" or "Create Group" button
3. The modal should open immediately
4. Check Network tab - should see NO new schema API calls (it uses cached data)

### 4. Frontend Component Testing

**PageList component:**

```javascript
// In browser console on a CRUD6 list page
// Check if schema has contexts
console.log('Schema structure:', window.$vm?.$refs?.schema)
// Or inspect via Vue DevTools
```

**Schema Store:**

```javascript
// In browser console
// Check cached schemas
const schemaStore = window.$pinia?.state?.value?.['crud6-schemas']
console.log('Cached schemas:', schemaStore?.schemas)
// Should see entries for both 'users:list,form' and individual 'users:list', 'users:form'
```

### 5. Functional Testing Checklist

Test these scenarios to ensure everything works:

#### List Page
- [ ] Navigate to `/crud6/users` (or any CRUD6 model)
- [ ] Table displays correctly with all columns
- [ ] Sorting works on sortable columns
- [ ] Search/filter works
- [ ] Only ONE schema API call in Network tab

#### Create Modal
- [ ] Click "Create User" button
- [ ] Modal opens immediately (no loading delay)
- [ ] Form displays with all editable fields
- [ ] Required fields are marked with asterisk
- [ ] Validation rules work (try submitting empty form)
- [ ] NO schema API call when modal opens
- [ ] Create operation works

#### Edit Modal
- [ ] Click edit icon/button on a row
- [ ] Modal opens immediately with populated data
- [ ] All editable fields are shown and populated
- [ ] NO schema API call when modal opens
- [ ] Update operation works

#### Delete Modal
- [ ] Click delete icon/button on a row
- [ ] Confirmation modal opens
- [ ] NO schema API call when modal opens
- [ ] Delete operation works

### 6. Performance Comparison

**Measure the improvement:**

1. **Before (hypothetically, if we didn't have the fix):**
   - Initial page load: 1 schema call for list
   - Open create modal: 1 schema call for form
   - Total: 2 API calls

2. **After (with the fix):**
   - Initial page load: 1 schema call for list+form
   - Open create modal: 0 API calls (uses cached form schema)
   - Total: 1 API call

**Expected improvements:**
- 50% reduction in schema API calls
- ~100-200ms faster modal open time
- Better user experience (no loading spinner when opening modals)

### 7. Backward Compatibility Testing

Ensure old code still works:

```bash
# Single context should still work
curl "http://localhost/api/crud6/users/schema?context=list"
# Response should have fields at root level (not in contexts object)

# No context should return full schema
curl "http://localhost/api/crud6/users/schema"
# Response should have complete schema with all fields
```

### 8. Error Cases

Test error handling:

```bash
# Invalid context (should handle gracefully)
curl "http://localhost/api/crud6/users/schema?context=invalid"

# Empty context (should return full schema)
curl "http://localhost/api/crud6/users/schema?context="

# Mixed valid and invalid contexts
curl "http://localhost/api/crud6/users/schema?context=list,invalid,form"
```

## Common Issues and Solutions

### Issue: Modal still makes schema API call

**Solution:** 
- Check that PageList is passing the schema prop to CreateModal/EditModal
- Verify Form component is receiving the schema prop
- Check browser console for errors

### Issue: Table doesn't display correctly

**Solution:**
- Verify PageList is extracting fields from `schema.contexts.list` 
- Check browser console for schema structure
- Ensure schemaFields computed property is updated

### Issue: Form validation not working

**Solution:**
- Verify Form component is extracting `schema.contexts.form`
- Check that validation rules are present in form context
- Look for console errors

## Success Criteria

✅ All checks passed when:

1. Only ONE schema API call on list page load
2. No schema API calls when opening create/edit modals
3. List table displays correctly
4. Create/edit forms work with validation
5. All CRUD operations (create, read, update, delete) function properly
6. No console errors
7. Backward compatibility maintained (single-context requests still work)
8. Network tab shows reduced API calls compared to before

## Notes

- The feature is backward compatible - old code continues to work
- Multi-context is opt-in by using comma-separated contexts
- The schema store automatically caches individual contexts from multi-context responses
- Form component automatically detects and uses multi-context schemas
