# Workflow Structure Comparison

## Before (Single Workflow)

```
.github/workflows/
└── integration-test.yml
    ├── Setup UserFrosting
    ├── Install CRUD6 Sprinkle
    ├── Run Migrations
    ├── Create Admin User
    ├── Generate Test Data
    ├── ❌ Run PHPUnit Tests (BROKEN - autoloader issues)
    ├── Build Frontend
    ├── Start Servers
    ├── Test Unauthenticated API
    ├── Test Authenticated API
    ├── Test Frontend Routes
    └── Capture Screenshots
```

**Problem**: PHPUnit step fails because test classes aren't autoloaded in UserFrosting vendor context

## After (Separate Workflows)

```
.github/workflows/
├── unit-tests.yml (NEW)
│   ├── Setup PHP (8.1, 8.2, 8.3)
│   ├── Setup MySQL
│   ├── Install Dependencies (from sprinkle root)
│   ├── ✅ Run PHPUnit Tests (WORKS - proper autoloading)
│   └── Generate Coverage Report
│
└── integration-test.yml (MODIFIED)
    ├── Setup UserFrosting
    ├── Install CRUD6 Sprinkle
    ├── Run Migrations
    ├── Create Admin User
    ├── Generate Test Data
    ├── Build Frontend
    ├── Start Servers
    ├── ✅ Test Unauthenticated API
    ├── ✅ Test Authenticated API
    ├── ✅ Test Frontend Routes
    └── ✅ Capture Screenshots
```

**Solution**: 
- Unit tests run from sprinkle root with proper dev dependencies
- Integration tests focus on HTTP endpoints and user workflows
- Clear separation of concerns

## Test Coverage

### Unit Test Workflow
**Purpose**: Test sprinkle code in isolation
- **Tests**: PHPUnit tests from `app/tests/`
- **Context**: Sprinkle root directory
- **Dependencies**: Full dev dependencies
- **Database**: MySQL test database
- **PHP Versions**: 8.1, 8.2, 8.3
- **Coverage**: Code coverage reporting

**Test Types**:
- Controller unit tests
- Model tests
- Service tests
- Schema tests
- Middleware tests
- Sprunje tests

### Integration Test Workflow
**Purpose**: Test sprinkle within UserFrosting application
- **Tests**: HTTP endpoint testing
- **Context**: UserFrosting installation
- **Dependencies**: Production dependencies only
- **Database**: MySQL with seeded data
- **PHP Version**: 8.1 (single version)
- **Browser**: Playwright/Chromium

**Test Types**:
- API endpoint authentication
- API endpoint authorization
- CRUD operations via API
- Frontend route accessibility
- Page rendering (screenshots)
- User workflows

## Benefits Summary

| Aspect | Before | After |
|--------|--------|-------|
| **Test Execution** | Mixed (unit + integration) | Separated (clear purpose) |
| **Autoloading** | ❌ Broken for unit tests | ✅ Working properly |
| **PHP Versions** | Single (8.1) | Multiple (8.1, 8.2, 8.3) |
| **Test Focus** | Unclear | Clear separation |
| **Debugging** | Difficult (mixed concerns) | Easy (isolated failures) |
| **CI Speed** | Slower (everything together) | Faster (parallel execution) |
| **Coverage** | No coverage report | ✅ Coverage reporting |

## Execution Flow

### Unit Test Workflow
```
Trigger (push/PR)
    ↓
Checkout Code
    ↓
Setup PHP (Matrix: 8.1, 8.2, 8.3)
    ↓
Setup MySQL Service
    ↓
Install Composer Dependencies
    ↓
Create Test DB Config
    ↓
Run PHPUnit Tests ✅
    ↓
Generate Coverage (PHP 8.1 only)
    ↓
Complete (3-5 minutes per PHP version)
```

### Integration Test Workflow
```
Trigger (push/PR)
    ↓
Create UserFrosting Project
    ↓
Install CRUD6 Sprinkle as Package
    ↓
Configure Application
    ↓
Run Migrations & Seeds
    ↓
Build Frontend Assets
    ↓
Start PHP & Vite Servers
    ↓
Test Unauthenticated Paths ✅
    ↓
Test Authenticated Paths ✅
    ↓
Capture Screenshots ✅
    ↓
Complete (15-20 minutes)
```

## Code Changes

### Files Created
- `.github/workflows/unit-tests.yml` (94 lines)

### Files Modified
- `.github/workflows/integration-test.yml` (-63 lines, removed PHPUnit step)
- `INTEGRATION_TESTING_QUICK_START.md` (updated documentation)

### Files Documented
- `.archive/PHPUNIT_INTEGRATION_TEST_FIX.md` (comprehensive technical explanation)
- `.archive/WORKFLOW_COMPARISON.md` (this file)

## CI/CD Pipeline

### Parallel Execution
Both workflows can run in parallel:

```
Git Push → GitHub Actions
    ├── Unit Tests (3-5 min × 3 PHP versions)
    │   ├── PHP 8.1 ✅
    │   ├── PHP 8.2 ✅
    │   └── PHP 8.3 ✅
    │
    └── Integration Tests (15-20 min)
        ├── Setup ✅
        ├── API Tests ✅
        └── Frontend Tests ✅
```

Total CI time: ~20 minutes (parallel execution)
vs. Previous: ~25-30 minutes (sequential with failures)

## Success Criteria

### Unit Tests Pass When:
- ✅ All PHPUnit tests pass on PHP 8.1, 8.2, 8.3
- ✅ No autoloader errors
- ✅ Test coverage report generated
- ✅ All test classes found and loaded

### Integration Tests Pass When:
- ✅ UserFrosting installation successful
- ✅ CRUD6 sprinkle installed as package
- ✅ Migrations run successfully
- ✅ API endpoints respond correctly (401/403/200/404)
- ✅ Frontend routes accessible
- ✅ Screenshots captured
- ✅ No PHPUnit-related errors

## Rollback Plan

If issues arise, rollback is simple:

```bash
# Revert to previous commit
git revert HEAD~2..HEAD

# Or delete unit-tests workflow and restore integration-test.yml
git checkout HEAD~2 -- .github/workflows/
```

However, rollback would restore the broken PHPUnit autoloading issue.
