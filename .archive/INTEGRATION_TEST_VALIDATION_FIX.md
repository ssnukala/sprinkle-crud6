# Integration Test Validation Fix Summary

## Issue Description
The integration test workflow (`integration-test.yml`) was failing with the following error:

```
PHP Warning:  require(app/app.php): Failed to open stream: No such file or directory
PHP Fatal error:  Uncaught Error: Failed opening required 'app/app.php'
```

The validation scripts were trying to bootstrap a UserFrosting application using:
```php
require 'vendor/autoload.php';
$app = require 'app/app.php';
```

This doesn't work because:
1. In UserFrosting 6, there is no `app/app.php` file in the standard location
2. The integration test runs from the `userfrosting` directory (the UF6 project root)
3. UserFrosting 6 uses a different bootstrapping mechanism

## Root Cause
The validation scripts were using inline PHP code (`php -r "..."`) that assumed a specific application structure that doesn't exist in UserFrosting 6. The scripts needed to:
- Bootstrap the database connection properly
- Load Eloquent models
- Query the database to validate seed data

## Solution
Created dedicated PHP scripts that properly bootstrap UserFrosting 6 database access:

### 1. `.github/scripts/check-seeds.php`
- Validates that CRUD6 seeds have been run successfully
- Checks for crud6-admin role
- Checks for all 6 CRUD6 permissions
- Verifies permission assignments to roles
- Uses `Illuminate\Database\Capsule\Manager` for database connection
- Reads database configuration from environment variables

### 2. `.github/scripts/test-seed-idempotency.php`
- Tests that seeds can be run multiple times without creating duplicates
- Counts records before and after re-seeding
- Compares counts to ensure they're identical
- Provides clear pass/fail messages

### 3. Updated `.github/workflows/integration-test.yml`
- Copies scripts from sprinkle repository to UserFrosting project
- Executes scripts using `php script-name.php` instead of inline code
- Maintains all validation checks but with proper bootstrapping

## Technical Implementation

### Database Connection Pattern
```php
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => getenv('DB_CONNECTION') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'userfrosting_test',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: 'root',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
```

This approach:
- Uses Illuminate's Capsule Manager (same as UserFrosting uses)
- Connects directly to the database without full app bootstrap
- Reads configuration from environment variables
- Enables Eloquent ORM for model access

### Workflow Integration
```yaml
- name: Validate CRUD6 seed data
  run: |
    cd userfrosting
    cp ../sprinkle-crud6/.github/scripts/check-seeds.php .
    php check-seeds.php
```

The workflow:
1. Changes to the UserFrosting project directory
2. Copies the validation script from the sprinkle repository
3. Executes the script with proper environment variables already set

## Benefits
1. **Proper UserFrosting 6 Compatibility**: Uses UF6's actual database connection method
2. **Maintainable**: Scripts are separate files, easier to test and modify
3. **Reusable**: Scripts can be run locally for debugging
4. **Clear Output**: Provides structured validation messages
5. **Proper Exit Codes**: Returns 0 for success, 1 for failure (CI-friendly)

## Files Changed
- `.github/scripts/check-seeds.php` (new)
- `.github/scripts/test-seed-idempotency.php` (new)
- `.github/workflows/integration-test.yml` (modified)

## Validation
- [x] PHP syntax check passed for all scripts
- [x] No security vulnerabilities detected (CodeQL)
- [x] Workflow YAML syntax is valid
- [x] Scripts follow UserFrosting 6 database access patterns

## Notes
- Scripts were initially named `validate-seeds.php` but renamed to `check-seeds.php` to avoid `.gitignore` pattern `validate-*.php`
- Scripts are executable (`chmod +x`) for convenience
- Scripts include detailed comments and docblocks
- Error messages are descriptive and actionable

## Testing Recommendations
1. Push to GitHub to trigger integration test workflow
2. Verify all validation checks pass
3. Check that seed idempotency test passes
4. Review workflow logs for proper output format

## Reference
- Failed workflow run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19347216710
- Error details: Line 2025-11-13T22:05:07.1903041Z shows the fatal error
