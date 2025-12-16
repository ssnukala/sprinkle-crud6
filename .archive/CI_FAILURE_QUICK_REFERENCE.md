# CI Failure Quick Reference - Run #20283052726

**Date**: December 16, 2025  
**Status**: ❌ FAILED (114 failures out of 297 tests)

---

## 📊 At a Glance

| Metric | Count | Percentage |
|--------|-------|------------|
| Total Tests | 297 | 100% |
| **Passing** | **175** | **59%** |
| **Failing** | **114** | **38%** |
| Skipped | 1 | <1% |
| Risky | 1 | <1% |
| Errors | 22 | 7% |
| Failures | 92 | 31% |
| Warnings | 8 | 3% |

---

## 🎯 Top 5 Issues by Impact

### 1. 🔴 Permission/Auth Failures - **40+ tests**
```
Status: 403 Forbidden (expected 200)
Impact: CRITICAL - Blocks ~40% of test suite
Priority: P0 - Fix IMMEDIATELY
```
**What's happening**: Tests grant permissions but still get 403  
**Where to look**: 
- Middleware order
- Permission string matching
- Test session setup

### 2. 🟡 Schema Structure - **3 tests**
```
Issue: Missing 'table' key in schema response
Impact: HIGH - Core API contract broken
Priority: P0 - Fix IMMEDIATELY
```
**What's happening**: API response incomplete  
**Where to look**: `app/src/Controller/ApiAction.php`

### 3. 🟡 Null IDs in Create - **3 tests**
```
Issue: Created records return null IDs
Impact: HIGH - Breaks CRUD operations
Priority: P0 - Fix IMMEDIATELY
```
**What's happening**: Model serialization hiding IDs  
**Where to look**: `CRUD6Model`, `CreateAction`

### 4. ⚠️ Mockery Errors - **5 tests**
```
Issue: Cannot mock final RequestDataTransformer
Impact: MEDIUM - Isolated to password tests
Priority: P2 - Refactor approach
```
**What's happening**: Test design incompatible with framework  
**Where to look**: `PasswordFieldTest.php`

### 5. 🟠 Nested Endpoints - **4 tests**
```
Issue: Relationships returning empty/null data
Impact: MEDIUM - Feature not working
Priority: P1 - Fix after P0 issues
```
**What's happening**: Relationship queries broken  
**Where to look**: `RelationshipAction.php`

---

## 🔍 Failure Breakdown by Category

### Authorization (40+ tests) 🔴
```
❌ RedundantApiCallsTest       (9/9 failed)
❌ RelationshipActionTest      (4/8 failed)
❌ UpdateFieldActionTest       (5/7 failed)
❌ SchemaBasedApiTest          (2/5 failed)
❌ CreateActionTest            (some affected)
❌ EditActionTest              (some affected)
```

### Data Structure (6 tests) 🟡
```
❌ SchemaActionTest            (2 tests - missing 'table')
❌ SchemaBasedApiTest          (3 tests - null IDs)
❌ NestedEndpointsTest         (2 tests - null slugs)
```

### Test Design (5 tests) ⚠️
```
❌ PasswordFieldTest           (5/5 - mock issues)
```

### Frontend Integration (5 tests) 🟠
```
❌ FrontendUserWorkflowTest    (5/9 failed)
  - 500 error on edit (1 test)
  - Status code expectations (2 tests)
  - Data size mismatches (2 tests)
```

### Field Visibility (2 tests) 🟢
```
❌ SprunjeActionTest           (1 test)
❌ SchemaFilteringTest         (1 test)
```

### TypeScript (1 test) 🟢
```
❌ SchemaCachingContextTest    (1 test)
```

---

## 🚀 Quick Fix Guide

### Fix #1: Permission System (Est: 2-4 hours)
```bash
# Step 1: Add debug logging
# In CRUD6Injector or authorization middleware:
echo "User: " . $user->id . "\n";
echo "Permissions: " . json_encode($user->permissions) . "\n";
echo "Required: " . $requiredPermission . "\n";

# Step 2: Run single test with output
vendor/bin/phpunit --verbose \
  app/tests/Integration/RedundantApiCallsTest.php::testSingleListCallNoRedundantCalls

# Step 3: Check schema permission config
cat app/schema/crud6/groups.json | grep -A 5 permissions
```

### Fix #2: Schema Structure (Est: 15 min)
```php
// In app/src/Controller/ApiAction.php
public function __invoke(...): ResponseInterface
{
    $schema = $this->schemaService->getSchema($model);
    
    // ADD THIS:
    $responseData = [
        'table' => $schema['table'] ?? $model,  // ✅ Include table
        'fields' => $schema['fields'],
        'relationships' => $schema['relationships'] ?? [],
        // ... rest of response
    ];
    
    return $this->respond($responseData);
}
```

### Fix #3: ID Serialization (Est: 30 min)
```php
// In app/src/Database/Models/CRUD6Model.php
protected $hidden = ['password']; // ✅ Remove 'id' if present

// OR ensure $visible includes 'id'
protected $visible = ['id', 'name', 'email', ...];

// In CreateAction, ensure ID is in response:
$responseData = [
    'id' => $model->id,  // ✅ Explicitly include
    ...$model->toArray()
];
```

---

## 📋 Test Command Reference

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit app/tests/Integration/RedundantApiCallsTest.php

# Run single test method
vendor/bin/phpunit --filter testSingleListCallNoRedundantCalls

# Run with verbose output
vendor/bin/phpunit --verbose

# Run only failed tests (after first run)
vendor/bin/phpunit --group failed

# Stop on first failure
vendor/bin/phpunit --stop-on-failure

# Run tests for specific category
vendor/bin/phpunit app/tests/Controller/  # All controller tests
vendor/bin/phpunit app/tests/Integration/ # All integration tests
```

---

## 🎯 Phase-by-Phase Approach

### Phase 1: Critical (Target: 0 failures) 
**Time Estimate: 4-6 hours**
- [ ] Fix permission/authorization system (40+ tests)
- [ ] Add 'table' to schema response (3 tests)
- [ ] Fix ID serialization (3 tests)
- **Expected Result**: ~46 tests fixed, 68 remaining

### Phase 2: High Priority (Target: 10 failures)
**Time Estimate: 2-3 hours**
- [ ] Fix nested endpoint issues (4 tests)
- [ ] Resolve 500 error in edit workflow (1 test)
- **Expected Result**: ~5 tests fixed, 63 remaining

### Phase 3: Medium Priority (Target: 5 failures)
**Time Estimate: 3-4 hours**
- [ ] Refactor password field tests (5 tests)
- [ ] Update test expectations (2 tests)
- **Expected Result**: ~7 tests fixed, 56 remaining

### Phase 4: Low Priority (Target: 0 failures)
**Time Estimate: 2 hours**
- [ ] Add field filtering (2 tests)
- [ ] Update TypeScript test (1 test)
- **Expected Result**: All tests passing ✅

**Total Estimated Time**: 11-15 hours

---

## 🏁 Success Checklist

- [ ] All 297 tests passing
- [ ] 0 errors
- [ ] 0 failures
- [ ] 0 warnings
- [ ] CI workflow green
- [ ] Code coverage maintained
- [ ] No new technical debt introduced

---

## 📞 Need Help?

1. **Start Here**: `.archive/CI_RUN_20283052726_FAILURE_ANALYSIS.md`
2. **Run Tests**: Follow commands in "Test Command Reference"
3. **Debug**: Add logging to suspected code areas
4. **Ask**: Open an issue with test output

---

**Last Updated**: December 16, 2025  
**Document Type**: Quick Reference  
**Full Analysis**: `.archive/CI_RUN_20283052726_FAILURE_ANALYSIS.md`
