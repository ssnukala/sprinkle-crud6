# Testing the UserFrosting Log Error Fixes

This guide helps you test the fixes for empty column names and permission error messages locally.

## Prerequisites

### 1. Install Dependencies

#### Set up GitHub Token (Choose one method)

**Method A: Environment Variable (Recommended)**
```bash
export COMPOSER_AUTH='{"github-oauth":{"github.com":"YOUR_GITHUB_TOKEN"}}'
composer install
```

**Method B: Composer Config**
```bash
composer config -g github-oauth.github.com YOUR_GITHUB_TOKEN
composer install
```

#### Create GitHub Token
1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Name: "Composer CRUD6 Development"
4. Scopes: Check `repo` (required)
5. Generate and copy the token

### 2. Set Up Test Environment

```bash
# Create runtime directories
mkdir -p app/storage/sessions app/storage/cache app/storage/logs
mkdir -p app/logs app/cache app/sessions
chmod -R 777 app/storage app/sessions app/logs app/cache

# Create test database configuration
cat > app/config/.env.test << 'EOF'
UF_MODE=testing
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=userfrosting_test
DB_USER=root
DB_PASSWORD=root
SMTP_HOST=localhost
SMTP_USER=
SMTP_PASSWORD=
CRUD6_DEBUG_MODE=true
EOF

# Generate test schemas
php scripts/generate-test-schemas.php
```

### 3. Start MySQL (if not running)

```bash
# Using Docker
docker run --name mysql-test -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=userfrosting_test -p 3306:3306 -d mysql:8.0

# Or use your local MySQL server
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS userfrosting_test;"
```

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit --testdox --colors=always
```

### Run Specific Test Suites
```bash
# Controller tests only
vendor/bin/phpunit app/tests/Controller --testdox

# Integration tests only  
vendor/bin/phpunit app/tests/Integration --testdox

# Sprunje tests only
vendor/bin/phpunit app/tests/Sprunje --testdox
```

## Analyzing Logs

### Check for Empty Field Filtering
After running tests, check the logs:

```bash
# View userfrosting.log
cat app/logs/userfrosting.log | grep "Filtered out empty field names"

# View storage logs
cat app/storage/logs/*.log | grep "filterSearch"
```

### Look for These Patterns

**✅ Good - Empty fields detected and filtered:**
```
[DEBUG] CRUD6 [SprunjeAction] Filtered out empty field names {
    "original_count": 3,
    "filtered_count": 2,
    "removed_count": 1,
    "original_fields": ["name", "", "email"],
    "filtered_fields": ["name", "email"]
}
```

**✅ Good - filterSearch called with valid fields:**
```
[DEBUG] CRUD6 [CRUD6Sprunje] filterSearch() called {
    "table": "users",
    "filterable_fields": ["name", "email"],
    "has_empty_strings": false
}
```

**❌ Bad - Would indicate fix didn't work:**
```
SQLSTATE[42000]: Syntax error ... "users".""
```

### Check Permission Error Messages

**✅ Good - Detailed error message:**
```
[ERROR] Access denied for action 'create' on model 'users' (requires permission: 'create_user')
```

**❌ Bad - Empty error message:**
```
[ERROR] ForbiddenException {
    "error_message": ""
}
```

## Testing Empty Field Names Manually

### Create Test Schema with Empty Fields

```bash
cat > app/schema/crud6/test_empty.json << 'EOF'
{
  "model": "test_empty",
  "table": "test_table",
  "fields": {
    "name": {
      "type": "string",
      "filterable": true,
      "sortable": true
    },
    "": {
      "type": "string",
      "filterable": true
    },
    "email": {
      "type": "string",
      "filterable": true
    }
  },
  "details": [
    {
      "model": "users",
      "list_fields": ["name", "", "email", ""]
    }
  ]
}
EOF
```

### Run Schema Service Test

```php
<?php
// test_empty_fields.php
require 'vendor/autoload.php';

use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

// Get DI container (this is simplified - actual setup requires full UF bootstrap)
$schemaService = /* get from DI container */;

try {
    $schema = $schemaService->getSchema('test_empty');
    echo "Schema loaded successfully\n";
    print_r($schema['details'][0]['list_fields']);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Expected Test Results

### Before Fix
- ❌ Tests fail with SQL syntax errors
- ❌ Queries show `"table".""`
- ❌ Permission errors have empty messages
- ❌ Logs show no field validation

### After Fix
- ✅ Tests pass (or fail for unrelated reasons)
- ✅ No `"table".""` patterns in queries
- ✅ Permission errors show model and permission details
- ✅ Logs show "Filtered out empty field names" when applicable
- ✅ filterSearch logs show field analysis

## Troubleshooting

### Issue: Composer Install Fails with GitHub Auth Error

**Solution**: Set up GitHub token (see Prerequisites section above)

### Issue: Tests Fail with Database Connection Error

**Solution**: Verify MySQL is running and accessible:
```bash
mysql -h 127.0.0.1 -u root -proot -e "SELECT 1"
```

### Issue: No Logs Generated

**Solution**: Ensure directories exist and are writable:
```bash
mkdir -p app/logs app/storage/logs
chmod -R 777 app/logs app/storage
```

### Issue: Debug Logs Not Appearing

**Solution**: Enable debug mode in config:
```bash
echo "CRUD6_DEBUG_MODE=true" >> app/config/.env.test
```

## Validating the Fixes

### 1. Check SprunjeAction.php
```bash
grep -A 20 "filterEmptyFieldNames" app/src/Controller/SprunjeAction.php
```

Should show the new filtering method and usage.

### 2. Check Base.php
```bash
grep -A 5 "ForbiddenException" app/src/Controller/Base.php
```

Should show enhanced error message with model, action, and permission.

### 3. Check CRUD6Sprunje.php
```bash
grep -B 2 -A 10 "filterSearch() called" app/src/Sprunje/CRUD6Sprunje.php
```

Should show enhanced debug logging.

## CI/CD Testing

The fixes will be automatically tested when pushed to GitHub:

1. GitHub Actions will run PHPUnit tests
2. Logs will be uploaded as artifact: `test-logs-php-8.4`
3. Download artifact to review logs
4. Look for the patterns described in "Analyzing Logs" section

## Success Criteria

- [x] PHP syntax check passes for all files
- [ ] PHPUnit tests pass (or existing failures remain, new failures are fixed)
- [ ] No SQL errors with empty column names in logs
- [ ] Permission errors include model and permission information  
- [ ] Debug logs show field validation when enabled
- [ ] filterSearch correctly handles empty field arrays

## Additional Resources

- Analysis Document: `.archive/USERFROSTING_LOG_ERRORS_ANALYSIS.md`
- Fix Summary: `.archive/USERFROSTING_LOG_ERRORS_FIX_SUMMARY.md`
- GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions
