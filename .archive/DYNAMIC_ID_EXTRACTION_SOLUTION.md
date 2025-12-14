# Dynamic ID Extraction Solution for Integration Tests

**Date**: 2024-12-14  
**Issue**: 404 errors for `/api/crud6/{model}/100` endpoints  
**Root Cause**: Hardcoded ID 100 in test paths may not exist in all environments

## Solution Overview

Instead of using hardcoded test IDs, the integration tests now dynamically extract IDs from list API responses and use those IDs for subsequent detail calls. This guarantees that detail tests use valid, existing record IDs.

## How It Works

### 1. Test Execution Flow

```
1. Login to application
2. Call GET /api/crud6/activities (LIST API)
   └─> Response: { rows: [{ id: 2, ... }, { id: 3, ... }, { id: 100, ... }] }
3. Extract IDs from response
   └─> Available IDs: [2, 3, 100]
4. Select appropriate ID based on model rules
   └─> Selected ID: 2 (prefer non-1 for activities)
5. Call GET /api/crud6/activities/2 (DETAIL API - using extracted ID)
   └─> Guaranteed to work because ID 2 was in the list response
```

### 2. ID Selection Strategy

**For Users and Groups** (MUST use ID 1):
- Users: ID 1 is the admin user (created by bakery)
- Groups: ID 1 is the default group
- If ID 1 exists in list: use ID 1
- If ID 1 missing: use first available ID (fallback)

**For All Other Models** (prefer non-1 IDs):
- Roles: Avoid ID 1, prefer ID 2, 3, etc.
- Permissions: Avoid ID 1, prefer ID 2, 3, etc.
- Activities: Avoid ID 1, prefer ID 2, 3, etc.
- If multiple records exist: use first non-1 ID
- If only ID 1 exists: use ID 1 (fallback)

### 3. Implementation Details

#### Modified File: `test-authenticated-unified.js`

**Before**:
```javascript
// Hardcoded ID 100
const apiPath = {
  path: "/api/crud6/activities/100"
};
```

**After**:
```javascript
// Track extracted IDs
const extractedModelIds = {};

// After list API call succeeds
if (isListEndpoint && responseBody.rows) {
  const modelName = "activities";
  const availableIds = responseBody.rows.map(row => row.id);
  
  // Select appropriate ID
  if (mustUseIdOne.includes(modelName)) {
    selectedId = availableIds.includes(1) ? 1 : availableIds[0];
  } else {
    // Prefer non-1 IDs
    const nonOneIds = availableIds.filter(id => id !== 1);
    selectedId = nonOneIds.length > 0 ? nonOneIds[0] : availableIds[0];
  }
  
  extractedModelIds[modelName] = selectedId;
}

// Before detail API call
if (extractedModelIds[modelName] && path.includes('/100')) {
  path = path.replace('/100', `/${extractedModelIds[modelName]}`);
}
```

## Benefits

### 1. Guaranteed Success
- Detail calls always use IDs that exist in the database
- No more 404 errors due to missing test data

### 2. Environment Flexibility
- Works with any ID range (1-99, 100+, or custom)
- Adapts to different seed data strategies
- No dependency on specific ID values

### 3. Conflict Avoidance
- Users/Groups use ID 1 (system records)
- Other models avoid ID 1 (prevents interference with system data)
- Safer for testing operations like DELETE/UPDATE

### 4. Real-World Testing
- Tests use actual database records
- Validates that list and detail APIs are consistent
- Catches issues with data synchronization

## Test Path Examples

### Before (Hardcoded)
```
GET /api/crud6/users -> Returns rows with IDs [1, 2, 3]
GET /api/crud6/users/100 -> 404 NOT FOUND (ID 100 doesn't exist)
```

### After (Dynamic)
```
GET /api/crud6/users -> Returns rows with IDs [1, 2, 3]
  └─> Extract IDs: [1, 2, 3]
  └─> Select ID 1 for users (required)
GET /api/crud6/users/1 -> 200 OK (ID 1 exists, was in list response)
```

### Another Example (Activities)
```
GET /api/crud6/activities -> Returns rows with IDs [1, 100, 101]
  └─> Extract IDs: [1, 100, 101]
  └─> Select ID 100 (prefer non-1, so choose 100 over 1)
GET /api/crud6/activities/100 -> 200 OK (ID 100 exists, was in list response)
```

## Configuration

### test-record-validation.json
```json
{
  "models": [
    {
      "model": "users",
      "must_use_id_1": true,
      "description": "User records - ID 1 is admin user"
    },
    {
      "model": "activities",
      "must_use_id_1": false,
      "description": "Activity records - prefer non-1 ID"
    }
  ]
}
```

## Logging Output

When tests run, you'll see:
```
🔍 Testing API: activities_list
   Path: /api/crud6/activities
   ✅ PASSED (status 200)
   📋 Extracted ID 100 for activities (avoid ID 1 for other models)
   💾 Stored ID 100 for future activities detail calls

🔍 Testing API: activities_single
   🔄 Using extracted ID 100 for activities (was 100)
   Path: /api/crud6/activities/100
   ✅ PASSED (status 200)
```

## Fallback Behavior

If a list API fails or returns no rows:
- The detail test will still attempt with hardcoded ID 100
- Debug logs will show no ID was extracted
- This helps identify data seeding issues

## Related Files

- `test-authenticated-unified.js` - Main test script with dynamic ID extraction
- `test-record-validation.json` - Model configuration (must_use_id_1 flags)
- `extract-test-ids-from-api.php` - Standalone script to extract IDs
- `determine-test-ids.php` - Database query script to find available IDs
- `validate-test-records.php` - Validates that expected IDs exist

## Testing the Solution

To verify this works locally:

```bash
# 1. Start UserFrosting application
cd userfrosting
php bakery serve

# 2. Run integration tests
node .github/testing-framework/scripts/test-authenticated-unified.js \
  .github/config/integration-test-paths.json \
  http://localhost:8080 admin admin123

# 3. Check logs for ID extraction messages
# Look for: "📋 Extracted ID X for model Y"
# Look for: "🔄 Using extracted ID X for model Y"
```

## Conclusion

This solution eliminates 404 errors caused by hardcoded test IDs by dynamically extracting IDs from list API responses. It's more robust, flexible, and provides better test coverage by validating data consistency between list and detail endpoints.
