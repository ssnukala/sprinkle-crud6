# Database Seeding Fix for Unit Tests

## Issue
GitHub Actions workflow run #20250974546 failed with SQL errors:
- `SQLSTATE[HY000] [2002] No such file or directory`
- Database connection issues when running unit tests
- Tests using `RefreshDatabase` trait were failing because migrations ran but no seed data was created

## Root Cause
Tests were using `RefreshDatabase` trait which:
1. Drops all tables
2. Runs migrations to recreate tables
3. BUT does not seed any data

This caused failures when tests tried to:
- Create users (requires groups and roles to exist)
- Check permissions (requires permissions table to be seeded)
- Use any foreign key relationships

## Solution Implemented

### 1. Created `WithDatabaseSeeds` Trait
**File**: `app/tests/Testing/WithDatabaseSeeds.php`

This trait provides three methods:
- `seedDatabase()` - Main method that seeds both Account and CRUD6 data
- `seedAccountData()` - Creates base Account sprinkle data:
  - Default group (terran)
  - Site admin role
  - Base permissions
- `seedCRUD6Data()` - Runs CRUD6 seeds:
  - DefaultRoles (creates crud6-admin role)
  - DefaultPermissions (creates CRUD6 permissions and syncs with roles)

### 2. Updated Base Test Case
**File**: `app/tests/CRUD6TestCase.php`

Added `use WithDatabaseSeeds;` trait to make seeding available to all test classes.

### 3. Updated All Tests Using RefreshDatabase
Added `$this->seedDatabase();` after `$this->refreshDatabase();` in setUp() methods for:

**Controller Tests** (8 files):
- `SprunjeActionTest.php`
- `UpdateFieldActionTest.php`
- `CreateActionTest.php`
- `DeleteActionTest.php`
- `EditActionTest.php`
- `SchemaActionTest.php`
- `CRUD6UsersIntegrationTest.php`
- `CRUD6GroupsIntegrationTest.php`
- `RelationshipActionTest.php`
- `CustomActionTest.php`

**Integration Tests** (5 files):
- `SchemaBasedApiTest.php`
- `RoleUsersRelationshipTest.php`
- `NestedEndpointsTest.php`
- `RedundantApiCallsTest.php`
- `FrontendUserWorkflowTest.php`

**Other Tests**:
- `Sprunje/CRUD6SprunjeSearchTest.php`
- `Database/Seeds/DefaultSeedsTest.php` (removed duplicate seedAccountData() method)

## Benefits

1. **Consistent Test Setup**: All tests now have the same base data
2. **DRY Principle**: Single source of truth for test seeding (WithDatabaseSeeds trait)
3. **Matches Integration Pattern**: Follows the same pattern used in integration tests
4. **Future Proof**: New tests automatically get seeding by extending CRUD6TestCase

## Testing Pattern

Before:
```php
public function setUp(): void
{
    parent::setUp();
    $this->refreshDatabase();
}
```

After:
```php
public function setUp(): void
{
    parent::setUp();
    $this->refreshDatabase();
    $this->seedDatabase();  // ← Added this line
}
```

## Related Files
- Issue: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20250974546/job/58142654269
- PR: Will be created from branch `copilot/create-seed-tables-and-data`

## Verification
All modified files pass PHP syntax validation:
```bash
find app/tests -name "*.php" -type f -exec php -l {} \;
# Result: No syntax errors detected
```

## Future Considerations
- If more seed data is needed, add methods to WithDatabaseSeeds trait
- Tests can override seedAccountData() or seedCRUD6Data() if they need different data
- Consider creating separate seed methods for different scenarios (e.g., seedWithUsers(), seedWithPermissions())
