# CRUD6 Integration Testing - Quick Start Guide

## Overview

This guide provides a **clean and simple way** to:
1. ✅ Instantiate UserFrosting 6
2. ✅ Install the CRUD6 sprinkle
3. ✅ Create test data
4. ✅ Run all tests to validate the sprinkle works

## Automated Integration Test (Recommended)

The easiest way is to use the GitHub Actions workflow:

```bash
# Trigger the integration test workflow
git push origin main

# Or manually trigger via GitHub UI:
# Actions → sprinkle-crud6 Integration Test → Run workflow
```

The workflow automatically:
- Sets up UserFrosting 6 project
- Installs CRUD6 sprinkle
- Runs migrations
- Creates admin user
- Generates and loads test data via SQL
- Runs API tests (authentication, authorization, CRUD operations)
- Tests frontend routes and pages
- Takes screenshots
- Validates everything works

> **Note**: PHPUnit unit tests are run separately in the "Unit Tests" workflow to ensure proper test environment and autoloading.

## Manual Local Testing

### Prerequisites

```bash
# Required
PHP 8.1+
Node.js 20+
MySQL 8.0+
Composer
```

### Step 1: Create UserFrosting Project

```bash
# Create new UserFrosting 6 project
composer create-project userfrosting/userfrosting my-uf6-app "^6.0-beta"
cd my-uf6-app
```

### Step 2: Install CRUD6 Sprinkle

```bash
# Option A: From packagist (released version)
composer require ssnukala/sprinkle-crud6

# Option B: From local development (for testing)
composer config repositories.local path ../sprinkle-crud6
composer require ssnukala/sprinkle-crud6:@dev
```

### Step 3: Configure the Sprinkle

```bash
# Add CRUD6 to your app/src/MyApp.php
```

Edit `app/src/MyApp.php`:
```php
use UserFrosting\Sprinkle\CRUD6\CRUD6;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class,  // Add this line
    ];
}
```

### Step 4: Install NPM Dependencies

```bash
# Install and configure frontend
npm install
npm install @ssnukala/sprinkle-crud6
```

Edit `app/assets/main.ts`:
```typescript
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'
app.use(CRUD6Sprinkle)
```

Edit `app/assets/router/index.ts`:
```typescript
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

const routes = [
    ...AccountRoutes,
    ...CRUD6Routes,  // Add this line
]
```

### Step 5: Configure Database

```bash
# Copy environment file
cp app/.env.example app/.env

# Edit app/.env
DB_CONNECTION="mysql"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="userfrosting"
DB_USER="root"
DB_PASSWORD="your_password"
```

### Step 6: Run Migrations

```bash
# Create database tables
php bakery migrate
```

### Step 7: Create Admin User

```bash
# Create admin user (this creates user ID 1)
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com \
  --firstName=Admin \
  --lastName=User
```

### Step 8: Generate and Load Test Data

```bash
# Generate SQL seed data from CRUD6 schemas
cd vendor/ssnukala/sprinkle-crud6
node .github/testing-framework/scripts/generate-seed-sql.js \
  examples/schema \
  ../../seed-data.sql

# Load the SQL directly via MySQL
cd ../../../
mysql -u root -p userfrosting < seed-data.sql

# Verify data was loaded
mysql -u root -p userfrosting -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM roles; SELECT COUNT(*) FROM permissions;"
```

### Step 9: Build and Start Application

```bash
# Build frontend assets
php bakery bake

# Start development servers (in separate terminals)
# Terminal 1: PHP server
php bakery serve

# Terminal 2: Vite dev server
php bakery assets:vite
```

### Step 10: Test the Application

Open browser to `http://localhost:8080` and:
- ✅ Login with admin/admin123
- ✅ Navigate to CRUD6 pages (e.g., `/crud6/users`)
- ✅ Test create, read, update, delete operations
- ✅ Verify API endpoints work

## Understanding Test Data

### Reserved IDs
- **User ID 1**: Reserved for admin user (created by bakery command)
- **IDs 2+**: Available for test data (all tables)

### Test Data Structure
The generated SQL creates test records starting from ID 100:
- Users: IDs 2, 3, 4, ... (test users)
- Groups: IDs 2, 3, 4, ... (can be used freely)
- Roles: IDs 2, 3, 4, ... (can be used freely)
- Permissions: IDs 2, 3, 4, ... (can be used freely)
- Products: IDs 2, 3, 4, ... (test products)
- Categories: IDs 2, 3, 4, ... (test categories)

### Safe for DELETE/DISABLE Tests
Since only user ID 1 is reserved, you can safely:
- Delete users with ID >= 100
- Delete groups with ID >= 100
- Delete roles with ID >= 100
- Modify or remove any test data

## Running Unit Tests

```bash
# Run CRUD6 unit tests
cd vendor/ssnukala/sprinkle-crud6
composer test

# Or specific test suites
vendor/bin/phpunit app/tests/Database/
vendor/bin/phpunit app/tests/Integration/
```

## Troubleshooting

### CSRF Storage Error
**Symptom**: "Invalid CSRF storage. Use session_start()"

**Solution**: This should NOT happen anymore. The fix uses direct MySQL without UserFrosting bootstrap.

If you see this in custom scripts:
- ❌ Don't use `new Bakery(MyApp::class)` just to load SQL
- ✅ Use `mysql` CLI directly for SQL operations
- ✅ Use `mysql ... -e "SELECT ..."` for queries

### Database Connection Issues
```bash
# Test database connection
mysql -u root -p -e "SELECT VERSION();"

# Verify database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'userfrosting';"
```

### Migration Issues
```bash
# Reset and re-run migrations
php bakery migrate:rollback
php bakery migrate
```

### Frontend Build Issues
```bash
# Clean and rebuild
rm -rf node_modules package-lock.json
npm install
php bakery bake
```

## Quick Commands Reference

```bash
# Full setup from scratch
composer create-project userfrosting/userfrosting uf6 "^6.0-beta"
cd uf6
composer require ssnukala/sprinkle-crud6
# ... configure MyApp.php, main.ts, router/index.ts ...
npm install @ssnukala/sprinkle-crud6
php bakery migrate
php bakery create:admin-user --username=admin --password=admin123 --email=admin@example.com
# ... generate and load SQL ...
php bakery bake
php bakery serve &
php bakery assets:vite &

# Test
curl http://localhost:8080
```

## Next Steps

After validation:
1. ✅ Create your own CRUD6 schemas in `app/schema/crud6/`
2. ✅ Generate frontend routes and components
3. ✅ Customize permissions and roles
4. ✅ Add custom actions and relationships
5. ✅ Build your application!

## Support

- GitHub Issues: https://github.com/ssnukala/sprinkle-crud6/issues
- Documentation: See `docs/` folder
- Integration Test Workflow: `.github/workflows/integration-test.yml`
- Testing Framework: `.github/testing-framework/`
