# Fix: AdminTestCase Class Not Found in CI Tests

## Issue
GitHub Actions workflow failed with error:
```
An error occurred inside PHPUnit.
Message:  Class "UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase" not found
Location: /home/runner/work/sprinkle-crud6/sprinkle-crud6/sprinkle-crud6/app/tests/Controller/CRUD6GroupsIntegrationTest.php:36
```

**GitHub Actions Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20214935486/job/58026414957

## Root Cause
The test classes (including `AdminTestCase`) were defined in the `autoload-dev` section of `composer.json`:

```json
"autoload-dev": {
    "psr-4": {
        "UserFrosting\\Sprinkle\\CRUD6\\Tests\\": "app/tests/"
    }
}
```

When the CRUD6 sprinkle is installed as a dependency in a UserFrosting project (via `composer require ssnukala/sprinkle-crud6:@dev`), Composer **does NOT include** the `autoload-dev` mappings. This is by design - `autoload-dev` is only used when developing the package directly.

The CI workflow runs tests using:
```bash
vendor/bin/phpunit vendor/ssnukala/sprinkle-crud6/app/tests
```

This runs from within the UserFrosting project context, where CRUD6 is installed as a dependency, so the test namespace was not available.

## Solution
Move the test namespace from `autoload-dev` to `autoload` section:

```json
"autoload": {
    "psr-4": {
        "UserFrosting\\Sprinkle\\CRUD6\\": "app/src/",
        "UserFrosting\\Sprinkle\\CRUD6\\Tests\\": "app/tests/"
    }
},
"autoload-dev": {
    "psr-4": {}
}
```

This ensures test classes are always available, even when the sprinkle is installed as a dependency.

## Why This Is Safe
1. **Standard Practice**: Many PHP packages that run integration tests include their test namespace in `autoload` (e.g., Laravel, Symfony test traits)
2. **Minimal Overhead**: The test classes are only loaded when explicitly referenced
3. **Required for CI**: The integration test workflow needs access to test base classes like `AdminTestCase`
4. **UserFrosting Pattern**: This follows similar patterns in UserFrosting core sprinkles

## Files Changed
- `composer.json`: Moved test namespace from `autoload-dev` to `autoload`

## Verification
- ✅ `composer validate` passed
- ✅ `composer dump-autoload` succeeded
- ⏳ Waiting for CI to verify the fix

## Date
December 14, 2024
