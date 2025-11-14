# Network Request Analysis for Troubleshooting Redundant API Calls

**Date:** November 14, 2025  
**Purpose:** Use integration test screenshots and network monitoring to identify and troubleshoot redundant API calls

## Overview

The integration test has been enhanced to capture and analyze all network requests made during CRUD6 page loads. This helps identify:

1. **Redundant schema API calls** - Multiple calls to `/api/crud6/{model}/schema`
2. **Unnecessary YAML imports** - YAML validation files being loaded despite using direct Regle adapter
3. **Duplicate data fetches** - Multiple calls to the same API endpoint
4. **Performance bottlenecks** - Excessive network requests on page load

## How It Works

### Enhanced Screenshot Script

The `.github/scripts/take-authenticated-screenshots.js` script now:

1. **Monitors all XHR/Fetch requests** during page loads
2. **Categorizes requests** by type:
   - Schema API calls (`/api/crud6/{model}/schema`)
   - YAML file imports (`.yaml` files)
   - CRUD6 API calls (`/api/crud6/{model}/*`)
   - Other requests
3. **Captures request details**:
   - URL with query parameters
   - HTTP method and status
   - Request/response timestamps
4. **Generates analysis report** in console output
5. **Saves detailed log** to JSON file

### What Gets Captured

For each page load (`/crud6/groups` and `/crud6/groups/1`):

```javascript
{
  "timestamp": "2025-11-14T18:34:38.141Z",
  "baseUrl": "http://localhost:8080",
  "pages": {
    "list": {
      "url": "http://localhost:8080/crud6/groups",
      "requests": [
        {
          "url": "http://localhost:8080/api/crud6/groups/schema?context=list",
          "method": "GET",
          "type": "xhr",
          "status": 200,
          "timestamp": "..."
        }
      ]
    },
    "detail": { ... }
  },
  "summary": {
    "totalRequests": 12,
    "schemaApiCalls": { "list": 1, "detail": 1 },
    "yamlImports": { "list": 0, "detail": 0 }
  }
}
```

## Using the Network Analysis

### Running Locally

1. **Start UserFrosting 6 application** with CRUD6 sprinkle installed
2. **Run the screenshot script**:
   ```bash
   node .github/scripts/take-authenticated-screenshots.js http://localhost:8080 admin admin123
   ```
3. **View console output** for immediate analysis
4. **Check detailed log** at `/tmp/network_requests.json`

### In CI/CD (GitHub Actions)

1. **Trigger the integration test** workflow
2. **Wait for completion**
3. **Download artifacts**:
   - `integration-test-screenshots` - Visual screenshots
   - `network-request-log` - Network analysis JSON
4. **Review the workflow logs** for console output

### Interpreting Results

#### ✅ Expected Behavior (No Issues)

```
### Groups List (/crud6/groups)

📊 Schema API Calls: 1
   1. GET /api/crud6/groups/schema
      Context: list
      Include Related: false
      Status: 200

✅ YAML File Imports: 0 (Good - no redundant YAML imports)

🔌 CRUD6 API Calls: 1
   1. GET /api/crud6/groups
      Status: 200

📈 Summary:
   Total XHR/Fetch Requests: 2
   Schema API Calls: 1
   YAML Imports: 0 ✅
   CRUD6 API Calls: 1
```

#### ⚠️ Problem: Multiple Schema Calls

```
### Groups List (/crud6/groups)

📊 Schema API Calls: 3
   1. GET /api/crud6/groups/schema
      Context: list
      Status: 200
   2. GET /api/crud6/groups/schema
      Context: form
      Status: 200
   3. GET /api/crud6/groups/schema
      Context: detail
      Status: 200

⚠️  Issues Detected:
   1. Multiple schema API calls detected (3)
```

**Root Cause:** Components are not sharing the schema from the global store. Each component is loading independently.

**Solution:** 
- Ensure components receive schema as props from parent
- Use `useCRUD6SchemaStore` to check cache before loading
- Load with `context=list,detail,form` once instead of three separate calls

#### ⚠️ Problem: YAML Imports Detected

```
### Groups List (/crud6/groups)

⚠️  YAML File Imports: 8 (SHOULD BE 0!)
   1. group.yaml
      Full URL: http://localhost:8080/schema/group.yaml?import
      Status: 304
   2. role.yaml
      Full URL: http://localhost:8080/schema/role.yaml?import
      Status: 304
   ...

⚠️  Issues Detected:
   1. YAML files being imported (8) - validation adapter not working correctly
```

**Root Cause:** The validation system is still using `useRuleSchemaAdapter` instead of `useCRUD6RegleAdapter`.

**Solution:**
- Check that `useCRUD6Api` uses `useCRUD6RegleAdapter` (already done)
- Verify no other components import `useRuleSchemaAdapter`
- Ensure the validation adapter is properly converting CRUD6 JSON to Regle rules

## Common Issues and Solutions

### Issue 1: Schema Loaded Multiple Times

**Symptom:** `Schema API Calls: 2` or more for the same page

**Debugging Steps:**
1. Check if parent component passes schema as prop
2. Verify child components use `setSchema()` when schema prop exists
3. Ensure `useCRUD6SchemaStore` cache is being checked
4. Look for components calling `loadSchema()` without checking cache

**Code Pattern to Fix:**
```typescript
// ❌ Bad - Always loads
const { loadSchema } = useCRUD6Schema()
await loadSchema(model)

// ✅ Good - Checks cache first
const schemaStore = useCRUD6SchemaStore()
const cached = schemaStore.getSchema(model, context)
if (!cached) {
  await schemaStore.loadSchema(model, false, context)
}
```

### Issue 2: YAML Files Still Loading

**Symptom:** YAML imports > 0 in network log

**Debugging Steps:**
1. Check which components are using validation
2. Verify they use `useCRUD6RegleAdapter` not `useRuleSchemaAdapter`
3. Look for any direct imports of UserFrosting validation schemas
4. Check if lazy-loaded components are bringing in unwanted dependencies

**Code Pattern to Fix:**
```typescript
// ❌ Bad - Imports YAML files
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/validation'
const adapter = useRuleSchemaAdapter()
const { r$ } = useRegle(formData, adapter.adapt(schemaPromise))

// ✅ Good - Direct Regle conversion
import { useCRUD6RegleAdapter } from '@ssnukala/sprinkle-crud6/composables'
const adapter = useCRUD6RegleAdapter()
const { r$ } = useRegle(formData, adapter.adapt(schemaPromise))
```

### Issue 3: Requests to Wrong Context

**Symptom:** Schema loaded with `context=full` instead of specific context

**Impact:** Larger payloads, slower responses, unnecessary data transfer

**Solution:**
```typescript
// ❌ Bad - Loads all fields
await schemaStore.loadSchema(model)

// ✅ Good - Context-specific
await schemaStore.loadSchema(model, false, 'list')  // List page
await schemaStore.loadSchema(model, false, 'form')  // Form validation
await schemaStore.loadSchema(model, false, 'detail') // Detail page
```

### Issue 4: Missing include_related Parameter

**Symptom:** Multiple schema API calls for related models

**Context:** Detail page with related models (e.g., users with roles)

**Solution:**
```typescript
// ❌ Bad - Loads parent, then each child separately
await schemaStore.loadSchema('users', false, 'detail')
await schemaStore.loadSchema('roles', false, 'list')
await schemaStore.loadSchema('groups', false, 'list')

// ✅ Good - One call includes all related
await schemaStore.loadSchema('users', false, 'detail', true) // includeRelated=true
// Now roles and groups schemas are cached automatically
```

## Workflow Integration

The integration test workflow automatically:

1. **Captures network requests** during screenshot capture
2. **Analyzes and reports** findings in console output
3. **Uploads artifacts**:
   - Screenshots for visual verification
   - Network log JSON for detailed analysis
4. **Reports summary** in GitHub Actions summary

### Viewing Results in GitHub Actions

1. Go to **Actions** tab in GitHub repository
2. Click on latest **Integration Test with UserFrosting 6** workflow run
3. Scroll to **Artifacts** section at bottom
4. Download:
   - `integration-test-screenshots.zip` - Screenshots
   - `network-request-log.zip` - Contains `network_requests.json`
5. Review workflow logs for console analysis

## Expected Results

### Groups List Page (`/crud6/groups`)

**Expected API Calls:**
- 1x Schema API: `GET /api/crud6/groups/schema?context=list`
- 1x Data API: `GET /api/crud6/groups` (Sprunje table data)
- 0x YAML imports

**Total:** 2 XHR/Fetch requests (plus static assets)

### Group Detail Page (`/crud6/groups/1`)

**Expected API Calls:**
- 1x Schema API: `GET /api/crud6/groups/schema?context=detail`
- 1x Data API: `GET /api/crud6/groups/1`
- 0x YAML imports

**Total:** 2 XHR/Fetch requests (plus static assets)

### With Related Models (e.g., users in group)

**Expected API Calls:**
- 1x Schema API: `GET /api/crud6/groups/schema?context=detail&include_related=true`
- 1x Data API: `GET /api/crud6/groups/1`
- 1x Related Data: `GET /api/crud6/groups/1/users`
- 0x YAML imports

**Total:** 3 XHR/Fetch requests

Related model schemas (users) should be cached from the `include_related=true` response.

## Troubleshooting Checklist

When network log shows issues, check:

- [ ] Are child components receiving schema as props?
- [ ] Are components checking global store cache before loading?
- [ ] Is validation using `useCRUD6RegleAdapter` (not `useRuleSchemaAdapter`)?
- [ ] Are schema loads using appropriate context parameter?
- [ ] Is `include_related=true` used for detail pages with relationships?
- [ ] Are there any lingering imports of UserFrosting YAML schemas?
- [ ] Is the Pinia schema store properly initialized?

## Related Files

- `.github/scripts/take-authenticated-screenshots.js` - Screenshot script with network monitoring
- `.github/workflows/integration-test.yml` - Integration test workflow
- `app/assets/stores/useCRUD6SchemaStore.ts` - Global schema store
- `app/assets/composables/useCRUD6Schema.ts` - Schema loading composable
- `app/assets/composables/useCRUD6Api.ts` - API composable with validation
- `app/assets/composables/useCRUD6ValidationAdapter.ts` - Direct Regle adapter

## Future Enhancements

Potential improvements to network analysis:

1. **Performance metrics** - Request duration, payload sizes
2. **Cache hit rate** - Percentage of requests served from cache
3. **Waterfall diagram** - Visual timeline of requests
4. **Comparison mode** - Before/after optimization metrics
5. **Automated alerts** - Fail build if redundant calls detected
6. **Historical tracking** - Track improvements over time

## Conclusion

This network request analysis tool provides visibility into API call patterns, helping identify and eliminate redundant requests for better performance and user experience.

Use it to:
- ✅ Verify schema caching is working
- ✅ Confirm YAML imports are eliminated
- ✅ Identify unnecessary duplicate calls
- ✅ Validate optimization efforts
- ✅ Document API call patterns
