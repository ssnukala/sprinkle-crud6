# Integration Test Validation Fix Summary

## Issue Description
The integration test workflow (`integration-test.yml`) was failing with the following error:

```
PHP Warning:  require(app/app.php): Failed to open stream: No such file or directory
PHP Fatal error:  Uncaught Error: Failed opening required 'app/app.php'
```

The validation scripts were using inline PHP code that tried to load `app/app.php` but were running from the wrong directory context.

## Root Cause
The validation scripts were using inline PHP code (`php -r "..."`) that assumed they were running from the UserFrosting 6 project root directory with proper access to `app/app.php`. However:
1. The inline code ran in the `userfrosting` directory
2. It incorrectly tried to manually bootstrap the database instead of using UserFrosting 6's standard app bootstrapping
3. The scripts needed to properly bootstrap the UF6 application container to access database models

## Solution
Created dedicated PHP scripts that properly bootstrap UserFrosting 6 using its standard application structure:

### 1. `.github/scripts/check-seeds.php`
- Validates that CRUD6 seeds have been run successfully
- Checks for crud6-admin role
- Checks for all 6 CRUD6 permissions
- Verifies permission assignments to roles
- **Uses UserFrosting 6's standard app bootstrapping via `app/app.php`**
- Loads environment configuration via Dotenv

### 2. `.github/scripts/test-seed-idempotency.php`
- Tests that seeds can be run multiple times without creating duplicates
- Counts records before and after re-seeding
- Compares counts to ensure they're identical
- **Uses UserFrosting 6's standard app bootstrapping via `app/app.php`**
- Provides clear pass/fail messages

### 3. Updated `.github/workflows/integration-test.yml`
- Copies scripts from sprinkle repository to UserFrosting project root
- Executes scripts using `php script-name.php` from the correct directory
- Maintains all validation checks but with proper UF6 bootstrapping

## Technical Implementation

### UserFrosting 6 Bootstrap Pattern
```php
// Load composer autoloader
require 'vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Bootstrap the UserFrosting 6 application
// app/app.php returns the bootstrapped DI container
$app = require 'app/app.php';

// Now Eloquent models work via the bootstrapped app
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;

$role = Role::where('slug', 'crud6-admin')->first();
```

This approach:
- Uses UserFrosting 6's standard application bootstrapping
- Loads configuration from .env file (database connection, etc.)
- Properly initializes the DI container and all services
- Enables Eloquent ORM for model access through the framework
- Follows UserFrosting 6 best practices for application initialization

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
