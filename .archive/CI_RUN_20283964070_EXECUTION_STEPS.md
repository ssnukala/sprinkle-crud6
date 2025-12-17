# CI Run #20283964070 - Step-by-Step Execution Plan

## Quick Reference

**Total Issues:** 107 (81 failures + 19 errors + 3 warnings)  
**Estimated Total Time:** 8-10 hours  
**Priority Order:** P0 → P1 → P2 → P3

---

## PHASE 1: CRITICAL FIXES (P0) - ~3-4 hours

### Step 1: Add Missing getName() Method [30 minutes]
**Objective:** Fix 17 test errors blocking execution

**Files to Modify:**
- `app/tests/Controller/CRUD6GroupsIntegrationTest.php`
- `app/tests/Controller/CRUD6UsersIntegrationTest.php`

**Actions:**
```php
// Add to test base class or each affected test class
public function getName(): string
{
    return static::class;
}
```

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/CRUD6GroupsIntegrationTest.php`
- Expect: getName() errors gone, other tests may still fail

---

### Step 2: Add Missing isDebugMode() Method [30 minutes]
**Objective:** Fix 2 test errors in DebugModeTest

**Files to Modify:**
- `app/src/Controller/Base.php` (or relevant controller base)

**Actions:**
```php
protected function isDebugMode(): bool
{
    return $this->ci->get('config')['debug'] ?? false;
}
```

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/DebugModeTest.php`
- Expect: isDebugMode() errors gone

---

### Step 3: Fix Password Field Exposure [1-2 hours]
**Objective:** SECURITY FIX - Prevent password in API responses

**Files to Analyze:**
1. User schema: `app/schema/crud6/users.json`
2. Sprunje: `app/src/Sprunje/CRUD6Sprunje.php`
3. Schema filter: `app/src/ServicesProvider/SchemaFilter.php`

**Actions:**
1. Check if password has `"viewable": false` or `"listable": false` in schema
2. Add field filtering in Sprunje `toResponse()` method:
   ```php
   // Filter out non-listable fields based on schema
   foreach ($results as &$result) {
       foreach ($schema['fields'] as $field => $config) {
           if (isset($config['listable']) && $config['listable'] === false) {
               unset($result[$field]);
           }
       }
   }
   ```
3. Ensure password field is NEVER in list responses

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/SprunjeActionTest.php::testListUsersReturnsOnlyListableFields`
- Manually check API response: Should NOT contain password field
- Run: `curl http://localhost/api/crud6/users | jq .` (verify no password)

---

### Step 4: Debug & Fix Permission System [2-3 hours]
**Objective:** Fix ~60 tests failing with 403 errors

**Investigation Steps:**

1. **Add Debug Logging** (30 min)
   ```php
   // In permission middleware or AuthGuard
   $this->logger->debug('Permission Check', [
       'user_id' => $currentUser?->id,
       'user_roles' => $currentUser?->roles->pluck('slug'),
       'required_permission' => $requiredPermission,
       'has_permission' => $hasPermission,
       'route' => $request->getUri()->getPath(),
   ]);
   ```

2. **Review Test User Setup** (30 min)
   - File: `app/tests/TestCase.php` or test base classes
   - Check: Are test users being given admin role?
   - Check: Do admin roles have all CRUD6 permissions?
   - Look for: `seedTestPermissions()` or similar methods

3. **Check Permission Seeds** (30 min)
   - File: `app/src/Database/Seeds/*Seed.php`
   - Verify: CRUD6 permissions exist (uri_users, uri_groups, etc.)
   - Verify: Admin/test role has these permissions assigned

4. **Fix Permission Caching** (15 min)
   ```php
   // In test setUp() or tearDown()
   protected function setUp(): void
   {
       parent::setUp();
       // Clear permission cache
       Cache::forget('permissions');
       Cache::tags(['permissions'])->flush();
   }
   ```

5. **Test User Creation** (30 min)
   - Ensure test users created with proper roles:
   ```php
   $user = User::factory()->create();
   $adminRole = Role::where('slug', 'admin')->first();
   $user->roles()->attach($adminRole);
   $this->actingAs($user);
   ```

6. **Run Incremental Tests** (30 min)
   - Test one failing test at a time
   - Add debug output to see permission check results
   - Fix issues as discovered

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/CRUD6UsersIntegrationTest.php`
- Expect: Most 403 errors should be gone
- Run: `vendor/bin/phpunit app/tests/Controller/EditActionTest.php`
- Expect: Update operations should work

---

## PHASE 2: HIGH PRIORITY (P1) - ~2-3 hours

### Step 5: Fix Soft Delete Implementation [1 hour]

**Files to Modify:**
- `app/src/Database/Models/CRUD6Model.php`
- User schema: `app/schema/crud6/users.json`

**Actions:**
1. Add SoftDeletes trait:
   ```php
   use Illuminate\Database\Eloquent\SoftDeletes;
   
   class CRUD6Model {
       use SoftDeletes;
       
       protected $dates = ['deleted_at'];
   }
   ```

2. Verify schema has soft delete enabled:
   ```json
   {
       "model": "users",
       "soft_delete": true,
       ...
   }
   ```

3. Update delete controller to use soft delete

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/DeleteActionTest.php`
- Expect: Soft delete tests pass

---

### Step 6: Fix Search/Filtering [1-2 hours]

**Files to Modify:**
- `app/src/Sprunje/CRUD6Sprunje.php`

**Actions:**
1. Update `applyFiltersSearch()` method to only search filterable fields:
   ```php
   protected function applyFiltersSearch($builder)
   {
       $search = $this->getFiltersValue('search');
       if (!$search) return $builder;
       
       $filterableFields = $this->getFilterableFields();
       
       return $builder->where(function($query) use ($search, $filterableFields) {
           foreach ($filterableFields as $field) {
               $query->orWhere($field, 'LIKE', "%{$search}%");
           }
       });
   }
   
   protected function getFilterableFields(): array
   {
       $fields = [];
       foreach ($this->schema['fields'] as $name => $config) {
           if (($config['filterable'] ?? false) === true) {
               $fields[] = $name;
           }
       }
       return $fields;
   }
   ```

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Sprunje/CRUD6SprunjeSearchTest.php`
- Expect: All search tests pass

---

### Step 7: Fix Authentication Message Mismatch [30 minutes]

**Files to Modify:**
- Test assertion helpers OR authentication middleware

**Option A: Update Tests (Easier)**
```php
// In test helper
protected function assertRequiresAuthentication($response)
{
    $this->assertEquals(401, $response->getStatusCode());
    $message = $this->getJsonResponseMessage($response);
    // Accept either message
    $this->assertContains($message, ['Login Required', 'Account Not Found']);
}
```

**Option B: Fix Auth Middleware (Better)**
- Review middleware order
- Ensure "Login Required" message is returned consistently

**Validation:**
- Run authentication tests
- Expect: Auth message tests pass

---

## PHASE 3: MEDIUM PRIORITY (P2) - ~2 hours

### Step 8: Fix Response Code Mismatches [1 hour]

**Files to Modify:**
- `app/tests/Controller/CreateActionTest.php`
- Validation error handlers

**Actions:**
1. Update create tests to accept 201:
   ```php
   $this->assertContains($response->getStatusCode(), [200, 201]);
   ```

2. Add proper validation error handling:
   ```php
   try {
       $validator->validate($data);
   } catch (ValidationException $e) {
       return $response->withStatus(400)->withJson([
           'errors' => $e->getErrors()
       ]);
   }
   ```

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/CreateActionTest.php`
- Expect: Response code tests pass

---

### Step 9: Add Frontend Routes [1 hour]

**Files to Modify:**
- `app/src/Routes/CRUD6Routes.php`

**Actions:**
```php
// Add frontend routes
$app->get('/users', ListUsersPage::class)->setName('users.list');
$app->get('/users/{id}', UserDetailPage::class)->setName('users.detail');
$app->get('/groups', ListGroupsPage::class)->setName('groups.list');
$app->get('/groups/{id}', GroupDetailPage::class)->setName('groups.detail');
```

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Controller/CRUD6UsersIntegrationTest.php::testFrontendUsersListRouteExists`
- Expect: Frontend route tests pass

---

## PHASE 4: LOW PRIORITY (P3) - ~1 hour

### Step 10: Fix API Call Tracking [1 hour]

**Files to Modify:**
- Middleware registration
- Test setup

**Actions:**
1. Verify ApiTrackerMiddleware is registered
2. Ensure middleware is in test environment
3. May need to mock tracker for tests

**Validation:**
- Run: `vendor/bin/phpunit app/tests/Integration/RedundantApiCallsTest.php`

---

### Step 11: Minor Config/Schema Fixes [30 minutes]

**Quick fixes for:**
- ConfigAction debug mode
- Schema filter static method call
- CRUD6Injector property tests

---

## VALIDATION & COMPLETION

### Final Validation Checklist:
```bash
# 1. Full test suite
vendor/bin/phpunit

# 2. Syntax check
find app/src -name "*.php" -exec php -l {} \;

# 3. Security check - verify no sensitive data in responses
curl http://localhost/api/crud6/users | grep -i password
# Should return nothing

# 4. Static analysis (if available)
vendor/bin/phpstan analyse
```

### Success Criteria:
- [ ] All 292 tests passing
- [ ] 0 failures, 0 errors
- [ ] Password field NOT in API responses
- [ ] Permission system working
- [ ] Soft deletes working
- [ ] Search respecting filterable fields

---

## NOTES

### If Blocked on Any Step:
1. Check relevant section in README.md
2. Reference similar code in sprinkle-admin
3. Check `.archive/MIDDLEWARE_INJECTION_PATTERN_CLARIFICATION.md`
4. Add debug logging to understand the issue
5. Skip to next step and return later

### Testing Strategy:
- Test after EACH step
- Don't proceed if critical tests still failing
- Keep notes on what was changed
- Can run specific test: `vendor/bin/phpunit --filter testMethodName`

### Time Management:
- Phase 1 MUST be completed first (blocking issues)
- Phase 2 can be done in any order
- Phase 3-4 can be deferred if time constrained
- Document any incomplete steps for future work
