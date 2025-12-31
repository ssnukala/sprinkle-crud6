# SQL Error Fix: Empty Column Name in Groups Query

**Issue**: Unit tests failed with SQL error:
```
SQLSTATE[HY000]: General error: 1 no such column: groups.
(Connection: memory, SQL: select count(*) as aggregate from "groups" where "groups"."" is null)
```

**GitHub Workflow Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20566499178/job/59065581440

## Root Cause Analysis

The SQL query shows `"groups".""` - the column name is literally an **empty string**, not `"groups"."deleted_at"`. This indicates that `getDeletedAtColumn()` was returning an empty string `""` instead of either `null` or `"deleted_at"`.

### How This Happens

1. CRUD6Model uses Laravel's `SoftDeletes` trait
2. The model has a protected property: `protected $deleted_at = null;`
3. When configuring from schema with `soft_delete: false`, we set `$this->deleted_at = null`
4. However, somewhere in the instantiation process (possibly from Eloquent hydration or trait initialization), this property can become an empty string `""`
5. The original `getDeletedAtColumn()` method checked `if ($this->deleted_at !== null)` which would PASS for empty string
6. This caused the method to return `""` instead of `null`
7. Eloquent then tried to build a WHERE clause: `WHERE "groups"."" IS NULL` → SQL error

## Solution Implemented

### 1. Fixed `getDeletedAtColumn()` Method

**File**: `app/src/Database/Models/CRUD6Model.php`

**Changes**:
- Added check for empty string: `if ($this->deleted_at !== null && $this->deleted_at !== '')`
- Applied same check to static storage fallback
- Method now returns `null` for both `null` and `""` values
- Added comprehensive debug logging

**Before**:
```php
public function getDeletedAtColumn(): ?string
{
    // Check instance property first
    if ($this->deleted_at !== null) {
        return $this->deleted_at;  // ← Could return ""
    }
    // ...
}
```

**After**:
```php
public function getDeletedAtColumn(): ?string
{
    $columnName = null;
    
    // Check instance property first
    if ($this->deleted_at !== null && $this->deleted_at !== '') {
        $columnName = $this->deleted_at;
        $this->logDebug('[CRUD6Model] getDeletedAtColumn from instance property', [
            'table' => $this->table,
            'column' => $columnName,
            'source' => 'instance_property',
        ]);
    }

    // Fall back to static storage for hydrated instances
    if ($columnName === null && $this->table && isset(static::$staticSchemaConfig[$this->table]['deleted_at'])) {
        $storedValue = static::$staticSchemaConfig[$this->table]['deleted_at'];
        // Only use the stored value if it's not empty
        if ($storedValue !== null && $storedValue !== '') {
            $columnName = $storedValue;
            $this->logDebug('[CRUD6Model] getDeletedAtColumn from static storage', [
                'table' => $this->table,
                'column' => $columnName,
                'source' => 'static_storage',
            ]);
        }
    }

    if ($columnName === null) {
        $this->logDebug('[CRUD6Model] getDeletedAtColumn returning NULL (soft deletes disabled)', [
            'table' => $this->table,
            'instance_deleted_at' => $this->deleted_at ?? 'not_set',
            'has_static_config' => isset(static::$staticSchemaConfig[$this->table]),
        ]);
    }

    return $columnName;
}
```

### 2. Added Debug Logging Infrastructure

**File**: `app/src/Database/Models/CRUD6Model.php`

**Changes**:
- Added static logger property: `protected static ?\UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface $debugLogger = null`
- Added `setDebugLogger()` static method for logger injection
- Added `logDebug()` helper method for conditional logging
- Added debug logs to:
  - `configureFromSchema()` - tracks schema configuration and soft delete setup
  - `getDeletedAtColumn()` - traces column name resolution
  - `newQuery()` - logs when soft delete filter is applied

### 3. Logger Injection in SchemaService

**File**: `app/src/ServicesProvider/SchemaService.php`

**Changes**:
- Modified `getModelInstance()` to inject logger into CRUD6Model
- Logger is now available for all model instances created through SchemaService

**Code**:
```php
public function getModelInstance(string $model): \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model
{
    $schema = $this->getSchema($model);

    $modelInstance = new \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model();
    
    // Inject logger for debug logging
    \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model::setDebugLogger($this->logger);
    
    $modelInstance->configureFromSchema($schema);

    return $modelInstance;
}
```

### 4. Enabled Debug Mode

**File**: `app/config/default.php`

**Changes**:
- Changed `'debug_mode' => false` to `'debug_mode' => true`
- This enables debug logging across all CRUD6 operations

### 5. Log Artifact Collection

**File**: `.github/workflows/phpunit-tests.yml`

**Changes**:
- Added step to upload test logs as artifacts
- Captures `app/logs/userfrosting.log` and `app/storage/logs/*.log`
- Logs are uploaded even when tests fail (`if: always()`)
- Artifact name: `test-logs-php-{version}`

**Code**:
```yaml
- name: Upload test logs
  if: always()
  uses: actions/upload-artifact@v3
  with:
    name: test-logs-php-${{ matrix.php-version }}
    path: |
      app/logs/userfrosting.log
      app/storage/logs/*.log
    if-no-files-found: warn
```

### 6. Comprehensive Tests

**File**: `app/tests/Database/Models/CRUD6ModelTest.php`

**Added Tests**:
1. `testGetDeletedAtColumnReturnsNullForEmptyString()` - Validates fix for empty string
2. `testGetDeletedAtColumnWhenSoftDeleteDisabled()` - Tests soft_delete: false scenario
3. `testGetDeletedAtColumnWhenSoftDeleteEnabled()` - Tests soft_delete: true scenario
4. `testGetDeletedAtColumnWithEmptyStringInStaticConfig()` - Tests empty string in static storage

## Expected Outcome

With these changes:

1. **SQL Error Fixed**: `getDeletedAtColumn()` will never return an empty string, preventing the SQL error
2. **Debug Logs Available**: When tests run, detailed debug logs will be captured in artifacts showing:
   - How models are configured
   - What value `getDeletedAtColumn()` returns
   - When soft delete filters are applied
3. **Future Debugging**: If similar issues occur, debug logs will immediately show the root cause
4. **Test Coverage**: Comprehensive tests prevent regression of this issue

## Debug Log Output Examples

When the fix works correctly, debug logs will show:

```
[CRUD6Model] Configuring model from schema
  table: groups
  has_soft_delete: false
  has_timestamps: true

[CRUD6Model] Soft deletes DISABLED
  table: groups
  deleted_at_column: null

[CRUD6Model] getDeletedAtColumn returning NULL (soft deletes disabled)
  table: groups
  instance_deleted_at: null
  has_static_config: true

[CRUD6Model] NOT applying soft delete filter in newQuery
  table: groups
  deleted_at_column: null
  reason: column is null
```

## Files Modified

1. `app/src/Database/Models/CRUD6Model.php` - Core fix + debug logging
2. `app/src/ServicesProvider/SchemaService.php` - Logger injection
3. `app/config/default.php` - Enable debug mode
4. `.github/workflows/phpunit-tests.yml` - Log artifact collection
5. `app/tests/Database/Models/CRUD6ModelTest.php` - Test coverage

## Verification

To verify the fix works:

1. Check GitHub Actions workflow runs for test success
2. Download log artifacts from workflow run
3. Inspect `userfrosting.log` for debug messages
4. Run unit tests locally: `vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php`

## Related Issues

- Original error: SQL query with empty column name `"groups".""` 
- Context: UserFrosting's `AssignDefaultGroups` listener triggering User::factory()->create()
- Solution in config: Already set `'group' => null` in `app/config/default.php` and `app/tests/config/testing.php`
- Root cause was in CRUD6Model, not the config

## Prevention

This fix includes multiple safety layers:

1. **Double validation** in `getDeletedAtColumn()` - checks both null AND empty string
2. **Static storage check** - validates stored values before use
3. **Comprehensive tests** - prevents regression
4. **Debug logging** - makes future issues visible immediately
5. **Explicit null returns** - clear, predictable behavior
