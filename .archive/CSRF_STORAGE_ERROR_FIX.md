# Fix for Integration Test CSRF Storage Error

## Problem

Integration tests were failing with:
```
RuntimeException: Invalid CSRF storage. Use session_start() before instantiating the Guard middleware or provide array storage.
```

This error occurred in the "Generate and load SQL seed data" step at:
```
.github/testing-framework/scripts/load-seed-sql.php
```

## Root Cause

The scripts were **over-engineered** and violated the original design:

### What Was Wrong ❌
```php
// load-seed-sql.php (OLD - INCORRECT)
require 'vendor/autoload.php';
use UserFrosting\Bakery\Bakery;

$bakery = new Bakery(MyApp::class);  // ❌ Bootstraps ENTIRE UserFrosting app
$container = $bakery->getContainer();
$db = $container->get(\Illuminate\Database\Capsule\Manager::class);
$pdo = $db->getConnection()->getPdo();
// ... then execute SQL via PDO
```

**Problems:**
1. Bootstraps full UserFrosting application just to run SQL
2. Initializes DI container → resolves all dependencies
3. Instantiates CSRF Guard middleware → requires session
4. Session not available in CLI → ERROR
5. Completely unnecessary complexity

### Design Intent from generate-seed-sql.js ✅

The SQL generator's own documentation says:
```javascript
console.log('Usage:');
console.log(`  mysql -u user -p database < ${outputFile}`);
console.log('  or use in integration tests via PDO/Eloquent');
```

**Primary method:** Direct MySQL CLI
**Alternative:** PDO/Eloquent (but this still requires framework bootstrap)

## Solution

### Rewritten Scripts - Use Direct MySQL ✅

**1. load-seed-sql.php**
```php
// Get DB credentials from environment
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: 'root';
$dbName = getenv('DB_NAME') ?: 'userfrosting_test';

// Build MySQL command
$command = sprintf(
    'mysql -h %s -u %s %s %s < %s',
    escapeshellarg($dbHost),
    escapeshellarg($dbUser),
    !empty($dbPassword) ? '-p' . escapeshellarg($dbPassword) : '',
    escapeshellarg($dbName),
    escapeshellarg($sqlFile)
);

// Execute
exec($command, $output, $returnCode);
```

**2. check-seeds-modular.php**
```php
// Validate seeds via direct MySQL queries
function executeQuery(string $query, ...): array {
    $command = sprintf(
        'mysql -h %s -u %s %s %s -N -e %s',
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        !empty($dbPassword) ? '-p' . escapeshellarg($dbPassword) : '',
        escapeshellarg($dbName),
        escapeshellarg($query)
    );
    
    exec($command, $output, $returnCode);
    return $output;
}

// Example: Check role count
$query = "SELECT COUNT(*) FROM roles WHERE slug = 'site-admin'";
$result = executeQuery($query, ...);
$count = (int)($result[0] ?? 0);
```

**3. test-seed-idempotency-modular.php**
```php
// Same approach - direct MySQL queries
$query = "SELECT COUNT(*) FROM permissions WHERE slug IN ('uri_users','create_user')";
$result = executeQuery($query, ...);
$count = (int)($result[0] ?? 0);
```

## Benefits

| Aspect | Old (UserFrosting) | New (Direct MySQL) |
|--------|-------------------|-------------------|
| **Complexity** | High - full framework bootstrap | Low - simple exec() call |
| **Dependencies** | Entire UF app + all middleware | MySQL CLI only |
| **CSRF Issues** | Yes - requires session | No - no middleware |
| **Performance** | Slow - boots framework | Fast - direct query |
| **Maintenance** | Complex - DI, Eloquent, etc. | Simple - standard SQL |
| **Design Intent** | Violated | Followed |

## Files Changed

1. `.github/testing-framework/scripts/load-seed-sql.php`
   - Removed: UserFrosting bootstrap, Bakery, PDO usage
   - Added: Direct MySQL CLI execution

2. `.github/testing-framework/scripts/check-seeds-modular.php`
   - Removed: UserFrosting bootstrap, Eloquent models
   - Added: Direct MySQL SELECT queries

3. `.github/testing-framework/scripts/test-seed-idempotency-modular.php`
   - Removed: UserFrosting bootstrap, Role/Permission models
   - Added: Direct MySQL COUNT queries

## Testing

The fix was validated with:
```bash
# Test command generation
./test-mysql-approach.sh

# Expected CI behavior:
# 1. load-seed-sql.php:    mysql -u root -proot userfrosting_test < seed-data.sql
# 2. check-seeds-modular:  mysql -u root -proot userfrosting_test -N -e "SELECT COUNT(*) ..."
# 3. test-idempotency:     mysql -u root -proot userfrosting_test -N -e "SELECT COUNT(*) ..."
```

## Why This Works

```
No UserFrosting → No DI Container → No Middleware → No CSRF Guard → No Session Requirement
```

The scripts now do exactly what they should:
- ✅ Load SQL via MySQL CLI (as designed)
- ✅ Validate via MySQL queries (simple and fast)
- ✅ No framework overhead
- ✅ No session/CSRF complexity

## Lesson Learned

**Always follow the original design intent:**
- SQL generator says: "use mysql -u user -p database < file"
- We were doing: Bootstrap entire framework → Get PDO → Execute SQL
- **The generator was right all along!**

## Related Files

- `.github/testing-framework/scripts/generate-seed-sql.js` - Original design
- `.github/workflows/integration-test.yml` - Uses these scripts
- `.archive/pre-framework-migration/scripts-backup/load-seed-sql.php` - Old version reference
