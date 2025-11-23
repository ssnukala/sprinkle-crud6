# Enhanced Error Logging for 400 Error Diagnosis - November 23, 2025

## Issue Summary

**Problem:** GitHub Actions integration tests showing HTTP 400 errors on authenticated API endpoints, but missing critical diagnostic information:
- No backend error logs captured
- No frontend console error details
- No validation error field-level details  
- No request/response payload logging

**Impact:** Unable to diagnose root cause of 400 errors without detailed error messages and validation failures.

## Root Cause Analysis

### Missing Diagnostic Information

1. **Frontend Logging Gaps:**
   - Request payloads not logged before API calls
   - Response headers not captured
   - Validation errors logged as objects without field details
   - No debugging hints for 400 errors
   - Browser console errors not saved to artifacts

2. **Backend Logging Gaps:**
   - CRUD6 debug mode disabled by default in CI
   - Comprehensive controller logging requires `crud6.debug_mode = true`
   - Backend logs not being captured in CI artifacts

3. **Existing Error Reporting:**
   - Basic error reporting existed (lines 388-420 in screenshot script)
   - But validation errors not parsed to show individual field failures
   - No request context (payload, headers) in error output

## Solution Implemented

### Part 1: Enhanced Frontend Logging

**File:** `.github/scripts/take-screenshots-with-tracking.js`

#### 1. Request Payload Logging (Lines 296-299)

**Before:**
```javascript
console.log(`   Path: ${path}`);

try {
    const url = `${baseUrl}${path}`;
```

**After:**
```javascript
console.log(`   Path: ${path}`);

// Log payload for debugging (only for non-GET requests)
if (method !== 'GET' && Object.keys(payload).length > 0) {
    console.log(`   📦 Payload:`, JSON.stringify(payload, null, 2));
}

try {
    const url = `${baseUrl}${path}`;
```

**Benefit:** See exactly what data is being sent to API

#### 2. Response Header Logging (Lines 331-337)

**Before:**
```javascript
const status = response.status();

// Validate status code
if (status === expectedStatus) {
```

**After:**
```javascript
const status = response.status();
const responseHeaders = response.headers();

// Log response headers for debugging (helps identify session/auth issues)
console.log(`   📡 Response Status: ${status}`);
if (responseHeaders['content-type']) {
    console.log(`   📄 Content-Type: ${responseHeaders['content-type']}`);
}

// Validate status code
if (status === expectedStatus) {
```

**Benefit:** Diagnose content-type and session issues

#### 3. Enhanced Error Details (Lines 390-467)

**Before:**
```javascript
} else {
    console.log(`   ❌ Status: ${status} (expected ${expectedStatus})`);
    
    // Try to get error details from response
    try {
        const responseText = await response.text();
        if (responseText) {
            try {
                const data = JSON.parse(responseText);
                if (data.message) {
                    console.log(`   ❌ Error: ${data.message}`);
                }
                if (data.errors) {
                    console.log(`   ❌ Validation errors:`, data.errors);
                }
```

**After:**
```javascript
} else {
    console.log(`   ❌ Status: ${status} (expected ${expectedStatus})`);
    
    // Log request details for debugging
    console.log(`   🔍 Request Details:`);
    console.log(`      URL: ${method} ${baseUrl}${path}`);
    if (method !== 'GET' && Object.keys(payload).length > 0) {
        console.log(`      Payload: ${JSON.stringify(payload)}`);
    }
    console.log(`      Headers: ${JSON.stringify(headers)}`);
    
    // Try to get error details from response
    try {
        const responseText = await response.text();
        if (responseText) {
            console.log(`   📝 Response Body (${responseText.length} bytes):`);
            
            try {
                const data = JSON.parse(responseText);
                
                // Log error message
                if (data.message) {
                    console.log(`   ❌ Error Message: ${data.message}`);
                }
                
                // Log validation errors with field details
                if (data.errors) {
                    console.log(`   ❌ Validation Errors:`);
                    if (typeof data.errors === 'object') {
                        // Fortress validation errors are typically an object with field names as keys
                        for (const [field, messages] of Object.entries(data.errors)) {
                            if (Array.isArray(messages)) {
                                messages.forEach(msg => {
                                    console.log(`      • ${field}: ${msg}`);
                                });
                            } else {
                                console.log(`      • ${field}: ${messages}`);
                            }
                        }
                    } else {
                        console.log(`      ${JSON.stringify(data.errors, null, 2)}`);
                    }
                }
                
                // Log status message
                if (data.status && data.status.message) {
                    console.log(`   ❌ Status Message: ${data.status.message}`);
                }
                
                // Log any other error details
                if (data.title) {
                    console.log(`   ❌ Title: ${data.title}`);
                }
                if (data.description) {
                    console.log(`   ❌ Description: ${data.description}`);
                }
                
                // For 400 errors specifically, provide debugging hints
                if (status === 400) {
                    console.log(`   💡 Debugging Hints for HTTP 400:`);
                    console.log(`      - Check if all required fields are provided`);
                    console.log(`      - Verify field types match schema expectations`);
                    console.log(`      - Check validation rules in schema`);
                    console.log(`      - Ensure unique fields don't conflict with existing data`);
                    if (!data.errors && !data.message) {
                        console.log(`      ⚠️  No error details in response - check backend logs`);
                    }
                }
```

**Benefits:**
- Request context (URL, payload, headers) visible in logs
- Validation errors parsed to show individual field failures
- Multiple error message formats handled
- Debugging hints for 400 errors
- Response body size shown
- Handles non-JSON responses gracefully

#### 4. Browser Console Error Artifact (Lines 1050-1096)

**New Feature:**
```javascript
// Save browser console errors to file for artifact upload
console.log('');
console.log('📝 Saving browser console errors/warnings to file...');

let consoleLogReport = '';
consoleLogReport += '═══════════════════════════════════════════════════════════\n';
consoleLogReport += 'BROWSER CONSOLE ERRORS AND WARNINGS\n';
consoleLogReport += 'UserFrosting CRUD6 Sprinkle Integration Test\n';
consoleLogReport += '═══════════════════════════════════════════════════════════\n';
consoleLogReport += `Generated: ${new Date().toISOString()}\n`;
consoleLogReport += `Base URL: ${baseUrl}\n`;
consoleLogReport += `Total Console Messages Captured: ${consoleErrors.length}\n`;
consoleLogReport += '\n';

if (consoleErrors.length > 0) {
    consoleLogReport += '─────────────────────────────────────────────────────────\n';
    consoleLogReport += 'CONSOLE ERRORS AND WARNINGS\n';
    consoleLogReport += '─────────────────────────────────────────────────────────\n';
    
    consoleErrors.forEach((error, idx) => {
        const time = new Date(error.timestamp).toISOString();
        consoleLogReport += `\n${idx + 1}. [${time}] ${error.type.toUpperCase()}\n`;
        consoleLogReport += `   Message: ${error.text}\n`;
        if (error.stack) {
            consoleLogReport += `   Stack Trace:\n`;
            const stackLines = error.stack.split('\n');
            stackLines.forEach(line => {
                consoleLogReport += `      ${line}\n`;
            });
        }
    });
} else {
    consoleLogReport += '✅ No browser console errors or warnings detected.\n';
}

consoleLogReport += '\n';
consoleLogReport += '═══════════════════════════════════════════════════════════\n';
consoleLogReport += 'END OF CONSOLE LOG REPORT\n';
consoleLogReport += '═══════════════════════════════════════════════════════════\n';

const consoleLogPath = '/tmp/browser-console-errors.txt';
try {
    writeFileSync(consoleLogPath, consoleLogReport, 'utf8');
    console.log(`✅ Browser console log saved to: ${consoleLogPath}`);
    console.log(`   Total errors/warnings: ${consoleErrors.length}`);
    console.log(`   File size: ${(consoleLogReport.length / 1024).toFixed(2)} KB`);
} catch (writeError) {
    console.error(`❌ Failed to save console log: ${writeError.message}`);
}
```

**Benefits:**
- All browser console errors/warnings saved to artifact
- Timestamps for correlation with test steps
- Stack traces for debugging JavaScript errors
- Available as downloadable artifact for 30 days

### Part 2: Backend Debug Logging

**File:** `.github/workflows/integration-test.yml`

#### Enable CRUD6 Debug Mode (Lines 256-273)

**Before:**
```yaml
echo "# Testing Configuration" >> app/.env
echo "TEST_SESSION_HANDLER=database" >> app/.env
```

**After:**
```yaml
echo "# Testing Configuration" >> app/.env
echo "TEST_SESSION_HANDLER=database" >> app/.env

# Create CRUD6 test configuration to enable debug mode
echo "Creating CRUD6 test configuration with debug mode enabled..."
mkdir -p app/config
cat > app/config/crud6-test.php << 'EOF'
<?php
declare(strict_types=1);

/**
 * CRUD6 test configuration
 * Enables debug mode for comprehensive backend logging during CI tests
 */
return [
    'crud6' => [
        'debug_mode' => true,
    ]
];
EOF

echo "✅ CRUD6 debug mode configuration created"
cat app/config/crud6-test.php
```

**What This Enables:**

From `app/src/Controller/Base.php`:
```php
protected bool $debugMode = false;

public function __construct(...) {
    $this->debugMode = (bool) $this->config->get('crud6.debug_mode', false);
}

protected function debugLog(string $message, array $context = []): void
{
    if ($this->debugMode) {
        $this->logger->debug($message, $context);
    }
}
```

**Backend Logs Will Now Include:**
- Schema loading and caching
- Controller invocations with parameters
- Request processing and data transformations
- Validation failures with error details
- Database operations (inserts, updates, deletes)
- Relationship processing
- Transaction handling

#### Browser Console Errors Artifact (Lines 679-686)

**New Step:**
```yaml
- name: Upload browser console errors as artifact
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: browser-console-errors
    path: /tmp/browser-console-errors.txt
    if-no-files-found: ignore
    retention-days: 30
```

**Benefits:**
- Console errors available as downloadable artifact
- Retained for 30 days
- Can be downloaded alongside screenshots and network summary

## Expected Output Examples

### Example 1: Missing Required Field

**Frontend Console (Before):**
```
Testing: users_create
   ❌ Status: 400 (expected 200)
   ❌ Validation errors: { email: [...] }
   ❌ FAILED
```

**Frontend Console (After):**
```
Testing: users_create
   Description: Create new user via CRUD6 API
   Method: POST
   Path: /api/crud6/users
   📦 Payload: {
     "user_name": "apitest",
     "first_name": "API",
     "last_name": "Test",
     "password": "TestPassword123"
   }
   📡 Response Status: 400
   📄 Content-Type: application/json
   📝 Response Body (234 bytes):
   ❌ Error Message: Validation failed
   ❌ Validation Errors:
      • email: The email field is required
   💡 Debugging Hints for HTTP 400:
      - Check if all required fields are provided
      - Verify field types match schema expectations
   ❌ FAILED
```

**Backend Logs (After):**
```
CRUD6 [CreateAction] ===== CREATE REQUEST START =====
CRUD6 [CreateAction] Request parameters received
   params: {"user_name":"apitest","first_name":"API",...}
   param_count: 4
CRUD6 [CreateAction] Data transformed
   transformed_data: {"user_name":"apitest",...}
CRUD6 [CreateAction] Starting validation
   data: {"user_name":"apitest",...}
CRUD6 [CreateAction] Validation failed
   errors: {"email":["The email field is required"]}
   error_count: 1
```

### Example 2: Unique Constraint Violation

**Frontend Console (After):**
```
Testing: users_create
   📦 Payload: {
     "user_name": "admin",
     "email": "admin@example.com",
     ...
   }
   📡 Response Status: 400
   ❌ Validation Errors:
      • user_name: Username already exists
      • email: Email already in use
   💡 Debugging Hints for HTTP 400:
      - Ensure unique fields don't conflict with existing data
```

## Artifacts Available After Test Run

1. **integration-test-screenshots** (existing)
   - Screenshots of all frontend pages
   
2. **network-requests-summary** (existing)
   - Detailed CRUD6 API call tracking
   
3. **browser-console-errors** (NEW)
   - All browser console errors/warnings
   - Timestamps and stack traces
   - JavaScript errors and exceptions

## Testing & Validation

### JavaScript Syntax
```bash
$ node --check .github/scripts/take-screenshots-with-tracking.js
✅ PASS
```

### Code Changes
- ✅ 150 lines added (comprehensive logging)
- ✅ 5 lines removed (replaced with enhanced versions)
- ✅ 2 files modified
- ✅ No breaking changes

### Expected CI Behavior

1. **When 400 Errors Occur:**
   - Frontend logs show exact request payload
   - Frontend logs show field-level validation errors
   - Backend logs show complete request processing
   - Console errors captured in artifact
   
2. **When Tests Pass:**
   - No console errors artifact (or empty file)
   - Network summary shows successful requests
   - Backend logs show successful operations

## Files Modified

### 1. `.github/scripts/take-screenshots-with-tracking.js`
- Added request payload logging
- Added response header logging
- Enhanced error detail parsing
- Added validation error field parsing
- Added debugging hints for 400 errors
- Added browser console error artifact generation

### 2. `.github/workflows/integration-test.yml`
- Created CRUD6 debug mode config file
- Added browser console errors artifact upload

## Benefits

### For Developers
- Immediately see what data was sent in failing requests
- See exact validation errors per field
- Debug hints guide investigation
- Browser errors captured automatically

### For CI/CD
- Comprehensive diagnostic information in logs
- Multiple artifact types for different issues
- No need to re-run tests to get more details
- Historical record of errors (30 day retention)

### For Issue Resolution
- Validation failures clearly identified
- Missing fields obvious from payload comparison
- Type mismatches visible in error messages
- Unique constraint violations explicit

## Related Documentation

- Original Issue: GitHub Actions run #19617479441
- Previous CSRF Analysis: `.archive/CSRF_ANALYSIS_2025_11_22.md`
- Previous 400 Fix: `.archive/400_ERRORS_FIX_SUMMARY.md`
- Controller Logging: `app/src/Controller/CreateAction.php` lines 75-117

## Commit Information

- **Commit:** e16ab22
- **Branch:** copilot/fix-api-test-errors
- **Date:** 2025-11-23
- **Message:** "Add comprehensive logging for 400 error diagnosis - frontend and backend"

## Next Steps

1. **Run Integration Tests:** Trigger CI workflow to collect enhanced logs
2. **Review Artifacts:** Download and analyze browser console errors and network summary
3. **Diagnose Root Cause:** Use detailed logs to identify missing fields or validation issues
4. **Fix Issues:** Update payloads or schemas based on validation errors
5. **Verify Fix:** Re-run tests to confirm 400 errors resolved
