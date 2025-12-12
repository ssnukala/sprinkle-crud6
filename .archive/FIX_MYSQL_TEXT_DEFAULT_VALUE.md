# Fix: MySQL ERROR 1101 - TEXT Column Default Value Issue

## Issue Summary

**Problem:** CI build failed with MySQL error:
```
ERROR 1101 (42000) at line 184: BLOB, TEXT, GEOMETRY or JSON column 'conditions' can't have a default value
```

**Root Cause:** The `permissions.json` schema file defined the `conditions` field as type `text` with a default value of empty string (`"default": ""`). MySQL does not allow default values for TEXT, BLOB, JSON, or GEOMETRY column types.

## Solution

Modified `.github/testing-framework/scripts/generate-ddl-sql.js` to:

1. **Check column type before adding DEFAULT clause** - Extract the base column type and check if it's one of the types that doesn't support defaults
2. **Skip default values for incompatible types** - TEXT, JSON, BLOB, MEDIUMTEXT, LONGTEXT, TINYTEXT, and GEOMETRY
3. **Log warnings** - Inform users when a default value is skipped during DDL generation

### Code Changes

**File:** `.github/testing-framework/scripts/generate-ddl-sql.js`

**Lines 143-161:** Added type checking logic:

```javascript
// Handle DEFAULT values
// NOTE: MySQL does not allow default values for TEXT, BLOB, JSON, or GEOMETRY columns
const typesWithoutDefaults = ['TEXT', 'JSON', 'BLOB', 'MEDIUMTEXT', 'LONGTEXT', 'TINYTEXT', 'GEOMETRY'];
// Extract base type (e.g., "INT AUTO_INCREMENT" -> "INT")
const baseType = columnType.split(' ')[0].toUpperCase();
const hasInvalidDefaultType = typesWithoutDefaults.includes(baseType);

if (field.default !== undefined && !hasInvalidDefaultType) {
    if (typeof field.default === 'string') {
        parts.push(`DEFAULT '${field.default}'`);
    } else if (typeof field.default === 'boolean') {
        parts.push(`DEFAULT ${field.default ? 1 : 0}`);
    } else {
        parts.push(`DEFAULT ${field.default}`);
    }
} else if (hasInvalidDefaultType && field.default !== undefined) {
    // Skip default value for TEXT/JSON/BLOB/GEOMETRY types and log a warning
    console.warn(`   ⚠️  Skipping default value for ${fieldName} (${columnType}): MySQL does not support defaults for TEXT/JSON/BLOB/GEOMETRY columns`);
}
```

## Testing

### Before Fix
DDL generation attempted to create:
```sql
`conditions` TEXT NULL DEFAULT '',
```

This caused MySQL to reject the DDL with ERROR 1101.

### After Fix
DDL generation creates:
```sql
`conditions` TEXT NULL,
```

This is valid MySQL syntax and loads successfully.

### Test Output
```
⚠️  Skipping default value for conditions (TEXT): MySQL does not support defaults for TEXT/JSON/BLOB/GEOMETRY columns
✅ Processing: product_categories.json (table: product_categories)
```

## MySQL Compatibility

**MySQL Limitation:** MySQL does not support default values for the following column types:
- TEXT (all variants: TEXT, TINYTEXT, MEDIUMTEXT, LONGTEXT)
- BLOB (all variants)
- JSON
- GEOMETRY

**Why?** These are variable-length types that MySQL stores separately from the main table row. Default values would require additional storage management that MySQL doesn't implement.

**Workaround:** 
1. Set the column as NULL (allows NULL as implicit default)
2. Handle default values in application code
3. Use BEFORE INSERT triggers to set defaults (advanced)

## Impact

**Affected Files:**
- `.github/testing-framework/scripts/generate-ddl-sql.js` - DDL generator

**Affected Schemas:**
- `examples/schema/permissions.json` - `conditions` field

**Behavior Change:**
- DDL generator now skips default values for TEXT/JSON/BLOB/GEOMETRY columns
- A warning is logged when a default value is skipped
- Generated SQL is now MySQL-compatible

## Code Review Feedback Addressed

1. **Type Matching:** Changed from `.includes()` to exact matching by extracting base type
2. **GEOMETRY Support:** Added GEOMETRY to the list of types without defaults
3. **Logic Simplification:** Removed redundant condition check in else-if

## Security Scan

CodeQL security scan completed with **0 alerts** - no security issues introduced.

## Related Links

- GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20152950270/job/57849460901
- MySQL Documentation: https://dev.mysql.com/doc/refman/8.0/en/data-type-defaults.html
- Pull Request: [To be added]

## Lessons Learned

1. **Schema Design:** Avoid setting default values for TEXT fields in schema definitions
2. **Database Compatibility:** Different databases have different limitations for default values
3. **Error Handling:** DDL generators should validate SQL compatibility before generation
4. **Testing:** Integration tests should include DDL loading to catch SQL syntax errors early

## Recommendations

**For Schema Authors:**
- Don't use `"default"` property for TEXT, JSON, or BLOB fields
- Handle default values in application code instead
- Use NULL-able columns if empty values are acceptable

**For Future Improvements:**
- Add schema validation to warn about incompatible default values
- Document SQL type limitations in schema documentation
- Consider adding schema linting tool to catch these issues earlier
