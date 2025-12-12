# Integration Test Workflow - Final Execution Order

## Verified Correct Order ✅

The integration test workflow now follows the correct execution order that matches the old working workflow and satisfies all requirements:

### 1. Infrastructure Setup
```yaml
- Setup PHP, Node.js, etc.
- Create UserFrosting project
- Install dependencies
- Configure files
```

### 2. Database Setup - Migrations
```yaml
- Run migrations
  → Creates tables (users, groups, roles, permissions, etc.)
  → NO data created, only structure
```

### 3. Database Setup - UserFrosting Default Seeds
```yaml
- Run PHP seeds (UserFrosting default seeds)
  → Creates roles, groups, permissions (infrastructure)
  → Does NOT create any users
  → Required for admin user creation
```

### 4. Admin User Creation ⭐
```yaml
- Create admin user
  → Creates user ID 1 (admin)
  → Needs infrastructure from step 3 (roles, groups)
  → MUST happen BEFORE test data (step 5)
  → MUST happen BEFORE bakery bake (step 7)
```

### 5. Test Data Generation ⭐
```yaml
- Generate and load SQL seed data
  → Test data via direct MySQL
  → Starts from ID 2 (ID 1 reserved for admin)
  → Requires admin user to exist (from step 4)
```

### 6. Data Validation
```yaml
- Validate seed data
- Test seed idempotency
- Create test user for modification tests
```

### 7. Frontend Build ⭐
```yaml
- Build frontend assets (php bakery bake)
  → Happens AFTER admin user exists (from step 4)
  → Happens AFTER test data loaded (from step 5)
```

### 8. Testing
```yaml
- Start servers
- Test API endpoints
- Capture screenshots
- Upload artifacts
```

## Key Requirements Satisfied ✅

### Requirement 1: UserFrosting seeds BEFORE admin user
✅ **SATISFIED** (Step 3 → Step 4)
- UserFrosting default seeds (roles, groups, permissions) run BEFORE admin user
- These seeds don't create users, only infrastructure
- Admin user needs this infrastructure to be created properly

### Requirement 2: Admin user BEFORE SQL test data
✅ **SATISFIED** (Step 4 → Step 5)
- Admin user (ID 1) created BEFORE SQL test data generation
- SQL test data expects ID 1 to exist (reserved for admin)
- Test data starts from ID 2

### Requirement 3: Admin user BEFORE bakery bake
✅ **SATISFIED** (Step 4 → Step 7)
- Admin user created BEFORE frontend build
- Bakery bake runs after admin user exists

## Comparison with Old Workflow

### Old Workflow (PHP seeds - working)
1. Migrations
2. PHP seeds (roles, groups, permissions - no users)
3. Create admin user
4. Test data via PHP seeders (flexible IDs)

### New Workflow (SQL seeds - corrected)
1. Migrations
2. PHP seeds (roles, groups, permissions - no users) ← SAME
3. Create admin user ← SAME
4. Test data via SQL (fixed IDs starting from 2) ← NEW METHOD
5. Bakery bake ← AFTER admin user ✅

## Why This Order Works

### UserFrosting Seeds First
- Create infrastructure (roles, groups, permissions)
- NO users created
- Required for admin user to have proper role assignments

### Admin User Second  
- Needs infrastructure from UserFrosting seeds
- Creates user ID 1 (reserved)
- Required for SQL test data generation

### SQL Test Data Third
- Expects user ID 1 to exist
- Starts from ID 2 for all test records
- Uses direct MySQL (no UserFrosting bootstrap = no CSRF errors)

### Bakery Bake After Data
- Builds frontend assets
- Runs after admin user and test data exist
- Can access database if needed during build

## Direct MySQL Approach (No CSRF Errors)

All seed loading and validation uses direct MySQL CLI:
```php
// ✅ CORRECT - Direct MySQL
mysql -h host -u user -ppass database < seed-data.sql
mysql -h host -u user -ppass database -e "SELECT COUNT(*) FROM users"

// ❌ WRONG - UserFrosting bootstrap
require 'vendor/autoload.php';
$bakery = new Bakery(MyApp::class);  // Triggers CSRF Guard error
```

This avoids:
- CSRF Guard middleware initialization
- Session requirement in CLI context
- Complex framework dependencies
- Unnecessary code complexity

## Conclusion

The workflow now:
✅ Matches the old working pattern
✅ Satisfies all order requirements
✅ Uses direct MySQL (no CSRF errors)
✅ Follows UserFrosting seeding best practices
✅ Creates admin user before test data
✅ Creates admin user before bakery bake

**No further changes needed to execution order.**
