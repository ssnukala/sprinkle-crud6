# UserFrosting 6 Bootstrap Fix Summary

## Issue
The `check-seeds.php` and `test-seed-idempotency.php` scripts were attempting to bootstrap UserFrosting using `app/app.php`, which doesn't exist in UserFrosting 6.

## UserFrosting 6 Bootstrap Methods

### Web Bootstrap (for HTTP requests)
**File**: `public/index.php`
```php
require_once __DIR__ . '/../vendor/autoload.php';

use UserFrosting\App\MyApp;
use UserFrosting\UserFrosting;

$uf = new UserFrosting(MyApp::class);
$uf->run();
```

### CLI Bootstrap (for command-line scripts)
**File**: `bakery` (at project root)
```php
require_once __DIR__ . '/vendor/autoload.php';

use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);
$bakery->run();
```

## Solution Applied

The scripts `check-seeds.php` and `test-seed-idempotency.php` are **CLI scripts**, so they must use the **CLI bootstrap method**.

### Before (Incorrect)
```php
// Bootstrap UserFrosting 6 application
require 'vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Bootstrap the UserFrosting application
// In UF6, app/app.php returns the bootstrapped DI container
$app = require 'app/app.php';
```

### After (Correct)
```php
// Bootstrap UserFrosting 6 application
require 'vendor/autoload.php';

// Bootstrap the UserFrosting application using Bakery (CLI bootstrap method)
// This follows the same pattern as the bakery CLI tool in UserFrosting 6
use UserFrosting\App\MyApp;
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);
$container = $bakery->getContainer();
```

## Key Changes

1. **Removed**: Deprecated `app/app.php` require statement
2. **Removed**: Manual `.env` loading (Bakery handles this internally)
3. **Added**: Bakery class instantiation
4. **Added**: Container access via `$bakery->getContainer()`

## Benefits

1. ✅ Follows official UserFrosting 6 patterns
2. ✅ Matches the `bakery` CLI tool implementation
3. ✅ Automatically handles environment loading
4. ✅ Properly bootstraps all sprinkles and services
5. ✅ Compatible with UserFrosting 6.0.4+ beta installations

## Files Modified

- `.github/scripts/check-seeds.php`
- `.github/scripts/test-seed-idempotency.php`

## Testing

These scripts are designed to run in a UserFrosting 6 installation context:

```bash
# From UserFrosting project root (after installation)
php .github/scripts/check-seeds.php
php .github/scripts/test-seed-idempotency.php
```

## References

- [UserFrosting Monorepo - public/index.php](https://github.com/userfrosting/monorepo/blob/6.0/public/index.php)
- [UserFrosting Monorepo - bakery CLI](https://github.com/userfrosting/monorepo/blob/6.0/bakery)
- [UserFrosting Skeleton - bakery CLI](https://github.com/userfrosting/monorepo/blob/6.0/packages/skeleton/bakery)
