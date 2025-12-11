# Understanding API Test Reports - Quick Reference Guide

## Reading the Test Summary

When API tests complete, you'll see a summary like this:

```
=========================================
API Test Summary
=========================================
Total tests: 45
Passed: 38
Warnings: 5
Failed: 2
Skipped: 0
```

### What Each Number Means

- **Total tests**: Number of API endpoints tested across all schemas
- **Passed**: Tests that returned expected status codes (usually 200)
- **Warnings**: Tests with permission failures (HTTP 403) - expected for some endpoints
- **Failed**: Tests with server errors (HTTP 500+) or unexpected issues
- **Skipped**: Tests marked as disabled or skip in configuration

## Understanding Error Types

### Permission Errors (Warnings - Expected)
```
⚠️  WARNING: Permission failure (403)
⚠️  Required permission: create_crud6
```

**What it means:** User lacks the specific permission needed for this action  
**Action needed:** Usually none - this is expected for some schemas/actions  
**When to investigate:** If a previously passing test now shows permission error

### Database/SQL Errors (Critical - Investigate)
```
⚠️  CRITICAL WARNING: Status 500
⚠️  Server error detected - possible code/SQL failure
🗄️  DATABASE/SQL ERROR DETECTED
```

**What it means:** Schema definition or database constraint issue  
**Action needed:** Review schema definition and database structure  
**Common causes:**
- Missing required fields in payload
- Unique constraint violations
- Foreign key constraint violations
- Invalid field types in schema

### Server Errors (Critical - Investigate)
```
⚠️  CRITICAL WARNING: Status 500
⚠️  Server error detected - possible code/SQL failure
Type: server_error
```

**What it means:** PHP exception or application error  
**Action needed:** Check PHP error logs for stack trace  
**Common causes:**
- Null pointer exceptions
- Type errors
- Missing dependencies
- Configuration issues

### Unexpected Status Errors (Investigate)
```
⚠️  CRITICAL WARNING: Status 400 (expected 200)
Type: unexpected_status
```

**What it means:** Response status doesn't match expected  
**Action needed:** Review request payload and validation rules  
**Common causes:**
- Invalid payload format
- Missing required fields
- Validation rule failures
- Incorrect field types

### Exception Errors (Critical - Investigate)
```
⚠️  CRITICAL WARNING: Exception - Connection timeout
Type: exception
```

**What it means:** JavaScript exception during test execution  
**Action needed:** Check network connectivity and server status  
**Common causes:**
- Network timeouts
- Server not responding
- Invalid URL
- CSRF token issues

## Reading the Failure Report by Schema

```
=========================================
API Failure Report by Schema
=========================================

📋 Schema: users
   Status: 5 passed, 2 failed
   Failed actions:
      • create:
         Type: database_error
         Status: 500
         Message: SQLSTATE[23000]: Integrity constraint violation
         ⚠️  DATABASE/SQL ERROR - Check schema definition
      • delete:
         Type: permission
         Status: 403
         Message: Permission denied
         ⚠️  Permission required: delete_crud6
```

### How to Read This

1. **Schema name**: Which model/schema had failures (e.g., "users")
2. **Status summary**: Quick overview (e.g., "5 passed, 2 failed")
3. **Failed actions**: List of operations that failed
4. **For each failure:**
   - **Action**: What operation failed (create, update, delete, etc.)
   - **Type**: Category of error (see error types above)
   - **Status**: HTTP status code received
   - **Message**: Error message from server
   - **Additional info**: Hints for fixing the issue

## Reading the Success Report by Schema

```
=========================================
API Success Report by Schema
=========================================

✅ Schema: users
   Passed actions: list, read, update, update_field, schema

✅ Schema: groups
   Passed actions: list, read, create, update, delete, schema
```

### How to Read This

- Shows which schemas had successful tests
- Lists all actions that passed for each schema
- Useful for seeing what's working correctly

## Action Types Explained

Common action names you'll see in reports:

- **list**: GET /api/crud6/{schema} - List all records
- **read**: GET /api/crud6/{schema}/{id} - Get single record
- **create**: POST /api/crud6/{schema} - Create new record
- **update**: PUT /api/crud6/{schema}/{id} - Update entire record
- **update_field**: PUT /api/crud6/{schema}/{id}/{field} - Update single field
- **delete**: DELETE /api/crud6/{schema}/{id} - Delete record
- **schema**: GET /api/crud6/{schema}/schema - Get schema definition
- **attach**: POST /api/crud6/{schema}/{id}/{relation} - Attach relationship
- **detach**: DELETE /api/crud6/{schema}/{id}/{relation} - Detach relationship

## What to Do When Tests Fail

### Step 1: Check Error Type

Look at the "Type" field in the failure report:

- **permission** → Usually expected, verify user has correct role
- **database_error** → Check schema definition and database
- **server_error** → Check PHP error logs
- **unexpected_status** → Review request payload
- **exception** → Check server connectivity

### Step 2: Review Error Message

The message often tells you exactly what's wrong:

```
Message: SQLSTATE[23000]: Integrity constraint violation: 
         1062 Duplicate entry 'admin' for key 'user_name'
```

This clearly indicates a duplicate username issue.

### Step 3: Check Request Details

Review the URL, method, and payload that caused the error:

```
URL: POST http://localhost:8080/api/crud6/users
Payload: {
  "user_name": "admin",
  "email": "admin@example.com"
}
```

### Step 4: Investigate

Based on error type:

**For database_error:**
1. Check schema JSON file for field definitions
2. Verify database table structure matches schema
3. Review unique constraints and foreign keys
4. Test payload data against validation rules

**For permission errors:**
1. Verify user has required role
2. Check if permission exists in database
3. Ensure role-permission association is correct
4. Review permission names in schema

**For server_error:**
1. Check `php-error-logs` artifact in GitHub Actions
2. Look for stack traces
3. Review recent code changes
4. Check for missing dependencies

**For unexpected_status:**
1. Review validation rules in schema
2. Check if all required fields are provided
3. Verify field types match schema
4. Test with different payload values

## CI Workflow Impact

### Before Changes
- First failure → Entire workflow stops
- No complete test report
- Debugging requires multiple runs

### After Changes
- All tests run to completion
- Complete failure/success report by schema
- Single run shows all issues
- Failures don't stop other tests
- Always generates artifacts (screenshots, logs, reports)

## Artifacts to Download

When tests complete, check GitHub Actions artifacts:

1. **integration-test-screenshots**: Visual confirmation of frontend
2. **network-requests-summary**: API calls made during testing
3. **browser-console-errors**: Frontend JavaScript errors
4. **php-error-logs**: Backend PHP errors and stack traces

These artifacts are generated even when tests have warnings/failures.

## Expected Warnings

Some warnings are normal and expected:

### Permission Warnings (Normal)
```
⚠️  WARNING: Permission failure (403)
⚠️  Required permission: delete_crud6
```

**Why:** Not all users have all permissions  
**Expected for:** Restricted actions on production-like schemas

### Schema Validation Warnings (May be normal)
```
⚠️  CRITICAL WARNING: Status 400
Message: Validation failed: email already exists
```

**Why:** Test data may conflict with existing records  
**Expected when:** Using real database with existing data

## Critical Warnings to Investigate

These should always be investigated:

### Database Errors on Standard Operations
```
Type: database_error
Message: Unknown column 'bad_field' in 'field list'
```

**Why it's critical:** Schema doesn't match database structure  
**Fix:** Update schema JSON or run migrations

### Unexpected Server Errors
```
Type: server_error
Status: 500
Message: Call to undefined method
```

**Why it's critical:** Code bug or missing dependency  
**Fix:** Review code changes and check PHP error logs

### Repeated Failures Across Multiple Schemas
```
users: create failed
groups: create failed
roles: create failed
```

**Why it's critical:** Systematic issue affecting all schemas  
**Fix:** Check global configuration, CSRF tokens, authentication

## Quick Troubleshooting Checklist

- [ ] Check error type (permission vs database vs server)
- [ ] Review error message for specific details
- [ ] Examine request payload and URL
- [ ] Verify schema JSON is valid
- [ ] Check database structure matches schema
- [ ] Review PHP error logs artifact
- [ ] Look for recent code changes
- [ ] Test locally with same payload
- [ ] Verify permissions exist in database
- [ ] Check CSRF token configuration

## Getting Help

If you're stuck:

1. Review the complete failure report by schema
2. Check all error types and messages
3. Download and review PHP error logs artifact
4. Compare with previous successful runs
5. Test the specific failing endpoint locally
6. Review recent changes to that schema
7. Check if issue is widespread or specific to one schema

## Summary

**Good News:** Tests no longer fail hard - they report and continue  
**Better Visibility:** See all failures across all schemas in one run  
**Actionable Reports:** Know exactly what failed, why, and where  
**Non-Blocking:** Other functionality tested even when some APIs fail  
**Complete Artifacts:** All logs and screenshots generated regardless of failures
