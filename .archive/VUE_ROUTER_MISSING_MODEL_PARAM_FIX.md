# Vue Router Error Fix - Missing Required Param "model"

## Issue Summary
When launching the UserFrosting 6 landing page, the following error occurred:

```
vue-router.mjs:1147 Uncaught Error: Missing required param "model"
    at Object.stringify (vue-router.mjs:1147:35)
    at Object.resolve (vue-router.mjs:1602:28)
    at resolve (vue-router.mjs:3206:38)
    at pushWithRedirect (vue-router.mjs:3295:51)
```

## Root Cause Analysis

The error was caused by an invalid route redirect configuration in the CRUD6 sprinkle:

### File: `app/assets/routes/index.ts`
```typescript
const CRUD6Routes = [
    { path: '', redirect: { name: 'crud6.list' } },  // ❌ PROBLEMATIC
    ...CRUD6RoutesImport,
]
```

### File: `app/assets/routes/CRUD6Routes.ts`
```typescript
{
    path: '/crud6/:model',  // Requires :model parameter
    children: [
        {
            path: '',
            name: 'crud6.list',  // This is the target of the redirect
            // ...
        }
    ]
}
```

**The Problem**: The redirect tried to navigate to `crud6.list`, which is a child route of `/crud6/:model`. This route requires a `:model` parameter, but the redirect didn't provide it. Vue Router failed during initialization because it couldn't resolve the redirect without the required parameter.

## Secondary Issue

Additionally, in `app/assets/components/CRUD6/Info.vue`, there was an inconsistency where the legacy delete modal redirect was missing the model parameter:

```vue
<!-- Line 237 - Missing model parameter -->
@deleted="router.push({ name: 'crud6.list' })"

<!-- Line 200 - Correct (has model parameter) -->
@deleted="router.push({ name: 'crud6.list', params: { model: model } })"
```

## Solution Implemented

### 1. Removed Invalid Redirect
**File**: `app/assets/routes/index.ts`

```typescript
const CRUD6Routes = [
    // Removed: { path: '', redirect: { name: 'crud6.list' } },
    ...CRUD6RoutesImport,
]
```

**Rationale**: The redirect doesn't make sense in this context because:
- The CRUD6 routes are designed to be accessed with a specific model (e.g., `/crud6/users`)
- There's no sensible default model to redirect to
- Applications using this sprinkle should link directly to specific model routes

### 2. Updated Route Tests
**File**: `app/assets/tests/router/routes.test.ts`

Changed the test to expect 1 route instead of 2:
```typescript
test('CRUD6Routes should contain the main crud6 route', () => {
    expect(CRUD6Routes.length).toBe(1) // main crud6 route only
    expect(CRUD6Routes[0].path).toBe('/crud6/:model')
    expect(CRUD6Routes[0].children.length).toBe(2) // list and detail routes
})
```

And updated the route access from `CRUD6Routes[1]` to `CRUD6Routes[0]` since the redirect was removed.

### 3. Fixed Missing Model Parameter
**File**: `app/assets/components/CRUD6/Info.vue`

```vue
<!-- Before -->
@deleted="router.push({ name: 'crud6.list' })"

<!-- After -->
@deleted="router.push({ name: 'crud6.list', params: { model: model } })"
```

## Impact

This fix ensures that:
1. ✅ Vue Router initializes without errors when the CRUD6 sprinkle routes are loaded
2. ✅ The UserFrosting 6 landing page loads properly
3. ✅ Delete modal redirects work correctly in both schema-based and legacy modes
4. ✅ All route navigations include the required `:model` parameter

## Files Changed

1. `app/assets/routes/index.ts` - Removed invalid redirect
2. `app/assets/tests/router/routes.test.ts` - Updated test expectations
3. `app/assets/components/CRUD6/Info.vue` - Added missing model parameter to redirect

## Testing Recommendations

When testing this fix in a UserFrosting 6 application:

1. **Landing Page Load**: The main landing page should load without Vue Router errors
2. **Direct Access**: Access CRUD6 routes directly (e.g., `/crud6/users`) should work
3. **Delete Actions**: After deleting a record, the redirect to the list page should work correctly
4. **Legacy Support**: Both schema-based and legacy code paths should handle redirects properly

## Related Code Patterns

All redirects to `crud6.list` must include the model parameter:

```typescript
// ✅ Correct
router.push({ name: 'crud6.list', params: { model: 'users' } })
router.push({ name: 'crud6.list', params: { model: model } })

// ❌ Incorrect - Will fail
router.push({ name: 'crud6.list' })
```

Similarly for `crud6.view`:

```typescript
// ✅ Correct
router.push({ name: 'crud6.view', params: { model: 'users', id: 123 } })

// ❌ Incorrect - Will fail
router.push({ name: 'crud6.view', params: { id: 123 } })
```
