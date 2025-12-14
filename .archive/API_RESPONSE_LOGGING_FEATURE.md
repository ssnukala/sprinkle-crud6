# API Response Logging Feature

## Overview
Added comprehensive logging of all API calls and responses during authenticated testing. The log is saved as a JSON file and uploaded as a CI artifact for analysis.

## Feature Details

### What Gets Logged
Each API test generates a detailed log entry containing:

1. **Request Information**:
   - Test name and description
   - HTTP method (GET, POST, PUT, DELETE)
   - Full URL and path
   - Request headers (with CSRF token redacted for security)
   - Request payload/body

2. **Response Information**:
   - HTTP status code
   - Response headers
   - Full response body (parsed as JSON if possible, otherwise as text)
   - Response timestamp

3. **Test Results**:
   - Expected vs actual status code
   - Test result (PASSED/FAILED/ERROR)
   - Error details if the request failed

### Log File Location
- **Path**: `/tmp/api-test-log.json`
- **Format**: Pretty-printed JSON (2-space indentation)
- **Availability**: Uploaded as GitHub Actions artifact named `api-test-log`

### Log File Structure
```json
{
  "test_run": {
    "timestamp": "2025-12-14T00:00:00.000Z",
    "base_url": "http://localhost:8080",
    "username": "admin",
    "total_tests": 25,
    "passed": 23,
    "failed": 2
  },
  "api_calls": [
    {
      "test_name": "users_create",
      "timestamp": "2025-12-14T00:00:01.000Z",
      "request": {
        "method": "POST",
        "url": "http://localhost:8080/api/crud6/users",
        "path": "/api/crud6/users",
        "headers": {
          "Accept": "application/json",
          "Content-Type": "application/json",
          "X-CSRF-Token": "[REDACTED]"
        },
        "payload": {
          "user_name": "apitest",
          "first_name": "API",
          "last_name": "Test",
          "email": "apitest@example.com",
          "password": "TestPassword123"
        }
      },
      "response": {
        "status": 200,
        "timestamp": "2025-12-14T00:00:01.500Z",
        "headers": {
          "content-type": "application/json",
          "cache-control": "no-cache"
        },
        "body": {
          "data": {
            "id": 150,
            "user_name": "apitest",
            "email": "apitest@example.com"
          }
        }
      },
      "expected_status": 200,
      "result": "PASSED",
      "description": "Create new user via CRUD6 API"
    }
  ]
}
```

## Implementation

### Changes Made

#### 1. Updated `test-authenticated-unified.js`
**File**: `.github/testing-framework/scripts/test-authenticated-unified.js`

**Imports**:
```javascript
import { writeFileSync } from 'fs';  // Added writeFileSync
```

**Log Collection**:
```javascript
const apiLogEntries = [];  // Collects all API call logs
```

**Per-Request Logging**:
- Captures full request details (method, URL, headers, payload)
- Records timestamps for both request and response
- Captures complete response (headers and body)
- Handles JSON and non-JSON responses
- Logs errors with stack traces

**File Writing**:
```javascript
writeFileSync(apiLogFile, JSON.stringify(apiLogData, null, 2), 'utf8');
```

#### 2. Updated Workflow
**File**: `.github/workflows/integration-test.yml`

Added new artifact upload step:
```yaml
- name: Upload API test log
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: api-test-log
    path: /tmp/api-test-log.json
    retention-days: 7
```

## Benefits

### 1. **Debugging Failed Tests**
- See exact request payload that was sent
- See complete response including error messages
- Compare expected vs actual status codes
- Trace request/response timing

### 2. **API Contract Validation**
- Verify request formats match API expectations
- Validate response structures
- Ensure headers are correct (CSRF tokens, content-type, etc.)

### 3. **Historical Analysis**
- Compare API behavior across test runs
- Track changes in response formats
- Identify patterns in failures
- Document working API examples

### 4. **Security Auditing**
- CSRF tokens are redacted in logs for security
- Full audit trail of all API interactions
- Can verify proper authentication flow

## Usage

### Accessing Logs in GitHub Actions
1. Go to the Actions tab in the repository
2. Select the failed/completed workflow run
3. Scroll to "Artifacts" section at the bottom
4. Download the `api-test-log` artifact
5. Extract and open `api-test-log.json`

### Example Use Cases

#### Debugging a Failed POST Request
```json
{
  "test_name": "users_create",
  "result": "FAILED",
  "request": {
    "method": "POST",
    "payload": { "user_name": "test", ... }
  },
  "response": {
    "status": 400,
    "body": {
      "message": "Validation failed",
      "errors": { "email": "Email is required" }
    }
  },
  "expected_status": 200
}
```
**Analysis**: The request failed validation because email was missing from payload.

#### Verifying CSRF Token Usage
```json
{
  "request": {
    "method": "PUT",
    "headers": {
      "X-CSRF-Token": "[REDACTED]"
    }
  }
}
```
**Analysis**: CSRF token was correctly included (redacted in log for security).

#### Checking Response Format
```json
{
  "response": {
    "headers": {
      "content-type": "application/json"
    },
    "body": {
      "data": { ... },
      "meta": { ... }
    }
  }
}
```
**Analysis**: Response has expected structure with `data` and `meta` fields.

## Security Considerations

1. **CSRF Token Redaction**: CSRF tokens are replaced with `[REDACTED]` in logs
2. **No Sensitive Data**: Passwords in payloads are visible (use test data only)
3. **Artifact Retention**: Logs are kept for 7 days only
4. **Access Control**: Only repository collaborators can download artifacts

## Future Enhancements

Possible improvements:
- Add request/response size metrics
- Include network timing data
- Add diff comparison between runs
- Generate HTML report from JSON log
- Add filtering options for specific test types
- Include frontend page request logs

## Related Files
- Script: `.github/testing-framework/scripts/test-authenticated-unified.js`
- Workflow: `.github/workflows/integration-test.yml`
- Documentation: `.archive/API_RESPONSE_LOGGING_FEATURE.md`

## Commit
Added API call and response logging to integration tests with artifact upload.
