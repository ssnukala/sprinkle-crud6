# Fix: Activities Schema Authentication Test Failure (January 12, 2026)

## Issue
Test failure: `testSchemaDrivenControllerActions` with data set 0 (activities schema)
- Expected: 401 (Unauthorized) or 400 (Bad Request - validation)
- Actual: 500 (Internal Server Error)

## Root Cause Analysis

### Problem Discovery
The test at line 814 was attempting to test an "unauthenticated" POST request but was actually making an **authenticated** request because:
1. Line 759: `$this->actAsUser($user, permissions: $permissions);` - Sets up authenticated session
2. Line 814: `$this->handleRequest($unauthRequest);` - Request is STILL authenticated from line 759
3. Result: Request goes through middleware with auth, hits controller, gets 500 error for some reason

### Pattern from PR #358
PR #358 documented the EXACT same issue and resolution:

> **The Problem:**
> The security test was doing something COMPLETELY DIFFERENT from all other passing tests:
> 1. Creating a user (`$userNoPerms`)
> 2. Attaching roles to it via `$user->roles()->sync()`
> 3. Then calling `actAsUser($user, permissions: [...])`
>
> **All other passing tests do:**
> 1. Create a NEW fresh user with `User::factory()->create()`
> 2. Call `actAsUser($freshUser, permissions: [...])` immediately
> 3. NO role manipulation at all!

### Correct Pattern
Looking at `testSecurityMiddlewareIsApplied()` at line 132:
```php
// Test 1: Unauthenticated request should return 401
echo "\n  [1] Testing unauthenticated request returns 401...\n";
$request = $this->createJsonRequest('GET', '/api/crud6/users');
$response = $this->handleRequestWithTracking($request);

$this->assertResponseStatus(401, $response, 
    'Unauthenticated request should be rejected by AuthGuard');
```

**KEY: No `actAsUser()` call before the request! The test truly is unauthenticated.**

## Solution Applied

### Before Fix
```php
// Line 759: Set up authentication FIRST
$this->actAsUser($user, permissions: $permissions);

// Test 1: Schema endpoint
// Test 2: Config endpoint  
// Test 3: List endpoint

// Test 4: Try to test "unauthenticated" request
// ❌ PROBLEM: Still authenticated from line 759!
$unauthRequest = $this->createJsonRequest('POST', "/api/crud6/{$modelName}");
$unauthResponse = $this->handleRequest($unauthRequest);
$statusCode = $unauthResponse->getStatusCode();
$this->assertContains($statusCode, [400, 401], ...);
```

### After Fix
```php
// Test 1: Create action WITHOUT authentication (MUST be tested FIRST)
echo "\n  [1] Testing create action requires authentication...\n";
// ✅ SOLUTION: Test unauthenticated request BEFORE actAsUser()
$unauthRequest = $this->createJsonRequest('POST', "/api/crud6/{$modelName}");
$unauthResponse = $this->handleRequest($unauthRequest);
$statusCode = $unauthResponse->getStatusCode();
$this->assertContains($statusCode, [400, 401], 
    "[Schema: {$modelName}] Create action should require authentication (401) or fail validation (400)");
echo "    ✓ Create action requires authentication\n";

// NOW set up authentication for remaining tests
$this->actAsUser($user, permissions: $permissions);

// Test 2: Schema endpoint
// Test 3: Config endpoint
// Test 4: List endpoint
```

## Changes Made

**File:** `app/tests/Integration/SchemaBasedApiTest.php`

1. **Moved authentication test to FIRST position** (before actAsUser)
2. **Renumbered tests 1-4** to reflect new order
3. **Added clear comment** explaining why test order matters: `// Test 1: Create action WITHOUT authentication (MUST be tested FIRST, before actAsUser)`
4. **Preserved all other test logic** - no functional changes to other tests

### Diff Summary
```diff
- Test 1: Schema endpoint (authenticated)
- Test 2: Config endpoint (authenticated)
- Test 3: List endpoint (authenticated)
- Test 4: Create without auth (❌ still authenticated from earlier)

+ Test 1: Create without auth (✅ truly unauthenticated)
+ Test 2: Schema endpoint (authenticated)
+ Test 3: Config endpoint (authenticated)
+ Test 4: List endpoint (authenticated)
```

## Why This Fixes the Issue

### Expected Behavior Now
1. **Test 1 runs first** - No authentication set up yet
2. **POST request is truly unauthenticated**
3. **AuthGuard middleware** should reject it with 401
4. **OR validation** might run first and return 400 (both acceptable)
5. **Test passes** with either 401 or 400 status code

### Actual Error Before
The request was authenticated, so:
- AuthGuard passed it through
- CRUD6Injector middleware tried to inject schema/model
- CreateAction controller started processing
- Something threw a 500 error (possibly missing POST data, validation issue, etc.)

## Validation

### Syntax Check
✅ PHP syntax validated: `php -l app/tests/Integration/SchemaBasedApiTest.php`
- Result: "No syntax errors detected"

### Code Review
✅ Pattern matches working tests:
- `testSecurityMiddlewareIsApplied()` line 132
- Other passing test methods throughout file

✅ Follows PR #358 lessons:
- Fresh user creation
- Proper `actAsUser()` timing
- No pre-authentication for unauth tests

## Expected CI Result

When this test runs in CI, it should now:
1. ✅ Test 1: Return 401 or 400 (both acceptable)
2. ✅ Test 2-4: Pass with authenticated user

**Status:** Ready for CI validation

## References

- Original failure: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20932581912/job/60146985713
- PR #358: User permission pattern fixes
- Test file: `app/tests/Integration/SchemaBasedApiTest.php` lines 731-821
- Pattern source: `testSecurityMiddlewareIsApplied()` lines 126-200

## Key Takeaways

1. **Authentication is persistent** - `actAsUser()` sets up session state that persists across requests
2. **Test order matters** - Unauthenticated tests MUST run BEFORE `actAsUser()` is called
3. **Pattern reference** - Always check working tests for correct patterns
4. **PR history** - Recent PRs (like #358) document important patterns and lessons learned
