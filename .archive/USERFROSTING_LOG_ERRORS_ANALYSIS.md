# UserFrosting Log Errors Analysis

## Issue Source
GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20620475296/job/59221294993

Artifact: test-logs-php-8.4 (contains userfrosting.log)

## Identified Errors

### Error 1: Empty Column Names in SQL Queries
**Pattern**: `"groups".""` or `"users".""`

**Root Cause Hypothesis**:
- Schema `details` sections contain `list_fields` arrays
- These arrays may have empty strings or the processing creates empty strings
- When passed to `CRUD6Sprunje::filterSearch()`, empty field names generate invalid SQL

**Affected Code Locations**:
1. `app/src/Controller/SprunjeAction.php:245` - `$listFields = $detailConfig['list_fields'] ?? ...`
2. `app/src/Controller/SprunjeAction.php:259` - Passed to `setupSprunje()`
3. `app/src/Sprunje/CRUD6Sprunje.php:139-141` - Already has empty field filtering
4. `app/src/Controller/Base.php:240-249` - `getFilterableFields()` method

**Current Protection**:
- `CRUD6Sprunje::filterSearch()` already filters empty fields (lines 139-146)
- However, the issue may occur before reaching this method

**Potential Sources**:
1. `list_fields` array contains empty strings: `["name", "", "email"]`
2. Field extraction from schema produces empty strings
3. Array merging/filtering produces empty entries

### Error 2: ForbiddenException with Empty Error Message
**Error Details**:
```
[2025-12-31T08:59:33.947693-05:00] debug.ERROR: CRUD6 [CRUD6Injector] Controller invocation failed {
    "model": "users",
    "error_type": "UserFrosting\\Sprinkle\\Account\\Exceptions\\ForbiddenException",
    "error_message": "",
    "error_file": "/home/runner/work/sprinkle-crud6/sprinkle-crud6/app/src/Controller/Base.php",
    "error_line": 175
}
```

**Root Cause**:
- `Base.php:175` throws `ForbiddenException()` without a message parameter
- When permission check fails, no context about which permission was checked

**Affected Code**:
```php
// Base.php lines 173-176
if (!$this->authenticator->checkAccess($permission)) {
    // Throw without message to use UserFrosting's default permission error message
    throw new ForbiddenException();
}
```

**Issue**: Comment says to use UserFrosting's default message, but the exception is thrown without any context, making debugging difficult.

## Required Fixes

### Fix 1: Add Field Name Validation in SprunjeAction
**Location**: `app/src/Controller/SprunjeAction.php`

Add validation methods:
```php
/**
 * Filter out empty field names from array.
 * 
 * @param array $fields Array of field names
 * @return array Filtered array with no empty strings
 */
protected function filterEmptyFieldNames(array $fields): array
{
    return array_filter($fields, function($field) {
        return is_string($field) && trim($field) !== '';
    });
}
```

Apply to all field arrays before passing to setupSprunje:
```php
// Line 243-245
$sortableFields = $this->filterEmptyFieldNames($this->getSortableFieldsFromSchema($relatedSchema));
$filterableFields = $this->filterEmptyFieldNames($this->getFilterableFieldsFromSchema($relatedSchema));
$listFields = $this->filterEmptyFieldNames($detailConfig['list_fields'] ?? $this->getListableFieldsFromSchema($relatedSchema));
```

### Fix 2: Enhance ForbiddenException Error Message
**Location**: `app/src/Controller/Base.php`

Change line 173-176 to:
```php
if (!$this->authenticator->checkAccess($permission)) {
    $modelName = $schema['model'] ?? 'unknown';
    throw new ForbiddenException(
        "Access denied for action '{$action}' on model '{$modelName}' (requires permission: {$permission})"
    );
}
```

### Fix 3: Add Comprehensive Debug Logging

**In SprunjeAction.php** - Add before setupSprunje call:
```php
$this->debugLog("CRUD6 [SprunjeAction] Field arrays before setupSprunje", [
    'relation' => $relation,
    'sortable_raw' => $sortableFields,
    'filterable_raw' => $filterableFields,
    'list_fields_raw' => $listFields,
    'has_empty_sortable' => in_array('', $sortableFields),
    'has_empty_filterable' => in_array('', $filterableFields),
    'has_empty_listable' => in_array('', $listFields),
]);
```

**In CRUD6Sprunje.php** - Enhance filterSearch logging:
```php
protected function filterSearch($query, $value)
{
    // Log entry to filterSearch
    $this->debugLogger->debug("CRUD6 [CRUD6Sprunje] filterSearch() called", [
        'table' => $this->name,
        'search_value' => $value,
        'filterable_fields' => $this->filterable,
        'empty_fields_present' => in_array('', $this->filterable),
    ]);
    
    // ... existing code ...
}
```

### Fix 4: Schema Validation at Load Time
**Location**: `app/src/ServicesProvider/SchemaValidator.php`

Add validation for details section:
```php
// In validate() method, add:
if (isset($schema['details']) && is_array($schema['details'])) {
    foreach ($schema['details'] as $index => $detail) {
        if (isset($detail['list_fields']) && is_array($detail['list_fields'])) {
            foreach ($detail['list_fields'] as $fieldIdx => $fieldName) {
                if (!is_string($fieldName) || trim($fieldName) === '') {
                    throw new \RuntimeException(
                        "Schema '{$model}': details[{$index}].list_fields[{$fieldIdx}] contains empty or invalid field name"
                    );
                }
            }
        }
    }
}
```

## Testing Strategy

1. **Unit Tests**: Create tests with schemas containing empty strings in various field arrays
2. **Integration Tests**: Test permission failures to verify error messages are helpful
3. **Log Analysis**: Review generated logs to confirm debug information is sufficient
4. **SQL Query Inspection**: Verify no queries contain empty column names

## Implementation Priority

1. **CRITICAL**: Fix 1 - Field name validation (prevents SQL errors)
2. **HIGH**: Fix 2 - Permission error messages (helps debugging)
3. **MEDIUM**: Fix 3 - Enhanced debug logging (aids troubleshooting)
4. **LOW**: Fix 4 - Schema validation (catch issues early, but shouldn't occur with proper schemas)
