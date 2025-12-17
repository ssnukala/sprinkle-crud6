# CI Run #20293538413 - Quick Reference

**Status**: ❌ FAILED  
**Date**: December 17, 2025  
**Tests**: 292 total, 168 passed, 112 failed

---

## TL;DR - Top 3 Issues

1. **🔴 500 Errors (100+ failures)** - ALL CRUD operations returning 500 instead of proper status codes
2. **🟡 SQL Errors (4 failures)** - Empty column names in WHERE clauses during search
3. **🟠 Auth Failures (15+ failures)** - "Force" error instead of "Access Denied" messages

---

## Quick Fix List

### Immediate Actions

#### 1. Fix 500 Errors
**Location**: All controllers  
**Action**: Check exception handling and middleware stack

```bash
# Enable debug mode to see real errors
# Add to phpunit.xml or .env:
APP_DEBUG=true
LOG_LEVEL=debug

# Check logs
tail -f storage/logs/userfrosting.log
```

#### 2. Fix SQL Column Errors
**Location**: `app/src/Sprunje/CRUD6Sprunje.php`  
**Action**: Add validation for empty field arrays

```php
// Before building search query
if (empty($this->searchableFields)) {
    return $query; // Don't add WHERE clause
}
```

#### 3. Fix Permission Messages
**Action**: Verify AuthGuard middleware returns 403, not exception

---

## Error Categories Summary

| Category | Count | Severity | Fix Time |
|----------|-------|----------|----------|
| 500 Internal Errors | 100 | 🔴 Critical | 1-2h |
| SQL Column Errors | 4 | 🟡 Medium | 15m |
| Permission Failures | 15 | 🟠 Medium | 30m |
| Field Filtering | 2 | 🟢 Low | 30m |
| Config DI Error | 1 | 🟢 Low | 15m |
| Seed Data Mismatch | 1 | 🟢 Low | 5m |

---

## Most Common Failure Pattern

```
Failed asserting that 500 is identical to 200.
```

**Cause**: Exception handler catching all exceptions  
**Solution**: Fix exception handling in controllers or middleware

---

## Test Files with Most Failures

1. `CRUD6UsersIntegrationTest.php` - 12 failures
2. `EditActionTest.php` - 12 failures
3. `FrontendUserWorkflowTest.php` - 10 failures
4. `RelationshipActionTest.php` - 10 failures
5. `CreateActionTest.php` - 8 failures

---

## Commands to Run

```bash
# See detailed errors
vendor/bin/phpunit --verbose

# Test one file
vendor/bin/phpunit app/tests/Controller/ApiActionTest.php

# Test one method
vendor/bin/phpunit --filter testCreateUserSuccess

# Check for Force error in codebase (not found)
grep -r "great disturbance in the Force" app/
```

---

## Key Files to Check

1. `app/src/Controller/*.php` - All controllers returning 500
2. `app/src/Sprunje/CRUD6Sprunje.php` - SQL column errors
3. `app/src/Middlewares/` - Auth/exception handling
4. `app/src/Controller/Base.php` - Field filtering logic

---

## Success Criteria

- [ ] All API endpoints return correct status codes (not 500)
- [ ] Search works with empty field arrays
- [ ] Permission failures show "Access Denied" not "Force" message
- [ ] Password fields not in listable fields
- [ ] All tests pass

---

## Next Step

**Start Here**: Enable debug mode and check logs for the actual exception causing 500 errors.

```bash
# Run tests with debug output
APP_DEBUG=true vendor/bin/phpunit --verbose --stop-on-failure
```

Then fix the underlying exception causing the systemic 500 errors.
