# Maximum Call Stack Size Exceeded - Fix Summary

**Date:** December 12, 2025  
**Issue:** CI integration test failing with stack overflow  
**PR:** copilot/check-logs-for-errors  
**GitHub Actions Run:** [20170261722](https://github.com/ssnukala/sprinkle-crud6/actions/runs/20170261722/job/57903829518#step:32:1780)

## Problem

The integration test was failing with:
```
Fatal error: RangeError: Maximum call stack size exceeded
    at NetworkRequestTracker.isMainApiCall (take-screenshots-with-tracking.js:178:21)
```

## Root Cause

In `.github/testing-framework/scripts/take-screenshots-with-tracking.js`, the `NetworkRequestTracker` class had a duplicate method definition that called itself recursively:

```javascript
// Line 169-171: Correct implementation
isMainApiCall(url) {
    return url.includes(this.apiPatterns.main_api);
}

// Line 177-179: DUPLICATE that caused infinite recursion!
isMainApiCall(url) {
    return this.isMainApiCall(url);  // ❌ Calls itself infinitely
}
```

## Solution

### Changes Made (3 commits)

1. **Removed infinite recursion** (commit 414ef36)
   - Deleted the duplicate `isMainApiCall` method at lines 177-179

2. **Cleaned up duplicates** (commit e9a5af3)
   - Removed early duplicate definitions of `getSchemaCalls()` and `getCRUD6Calls()`

3. **Removed backward compatibility** (commit 9b082bb)
   - Eliminated deprecated `getCRUD6Calls()` legacy alias (no longer needed for first production release)
   - Renamed CRUD6-specific terminology to generic mainApi:
     - `crud6Calls` → `mainApiCalls`
     - "CRUD6 Calls" → "Main API Calls"
     - "Non-CRUD6" → "Non-API"

### Files Changed

- `.github/testing-framework/scripts/take-screenshots-with-tracking.js`
  - Removed duplicate method definitions
  - Removed backward compatibility code
  - Renamed CRUD6 references to mainApi

## Validation

✅ All JavaScript files pass syntax validation  
✅ No backward compatibility code remains  
✅ No duplicate method definitions  
✅ Generic naming improves code reusability  

## Lessons Learned

1. **Duplicate method definitions** in JavaScript create silent bugs - the last definition wins
2. **Self-referential methods** cause stack overflow when a duplicate calls itself
3. **Backward compatibility** should be removed before first production release to keep code clean
4. **Generic naming** (mainApi vs CRUD6) makes framework code more reusable
