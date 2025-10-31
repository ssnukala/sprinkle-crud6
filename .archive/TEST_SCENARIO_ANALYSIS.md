# Test Scenario: Verify Duplicate Schema Call Fix

## Test Case: Navigate to /crud6/users/1

### Expected Behavior (After Fix)

#### Timeline of Events:

**T=0ms: Page Navigation**
- User navigates to `/crud6/users/1`
- Vue Router activates PageDynamic component

**T=10ms: PageDynamic Setup**
- ✅ NO schema loading
- ✅ Determines componentToRender = 'row' based on query params (or default)
- ✅ Renders PageRow component
- ❌ NO API call made

**T=20ms: PageRow Setup**
- Initializes useCRUD6Schema composable
- Initializes useCRUD6Api composable
  - Calls `loadSchema()` for validation (form context)
  - Store checks: Not in cache, not loading
  - Store calls `isRelatedContextLoading('users', 'form')`
  - **At this point, nothing is loading yet, so it would make an API call**
  
**T=30ms: PageRow Model Watcher Fires**
- Watcher detects model='users'
- Calls `loadSchema('users', false, 'detail,form')`
- Store checks: Not in cache, not loading
- Store calls `isRelatedContextLoading('users', 'detail,form')`
- No related context loading
- 🌐 **Makes API call to `/api/crud6/users/schema?context=detail%2Cform`**
- Sets loading state: `users:detail,form = true`

**Issue: Race Condition**
There's still a race condition! The useCRUD6Api might call loadSchema BEFORE the PageRow watcher fires, or they might both fire nearly simultaneously.

Let me check if this is actually a problem...

Actually, looking at the watcher in PageRow (line 283), it has `{ immediate: true }`, which means it fires synchronously during setup. So the order should be:

1. PageRow setup starts
2. useCRUD6Schema composable initialized
3. useCRUD6Api composable initialized (calls loadSchema for 'form')
4. Model watcher with `immediate: true` fires (calls loadSchema for 'detail,form')
5. PageRow setup complete

So they BOTH happen during setup, almost simultaneously. The question is: which one actually executes first?

### Actual Execution Order (Synchronous)

Looking at PageRow.vue more carefully:

```typescript
// Line 26-32: useCRUD6Schema initialized
const { schema, ... } = useCRUD6Schema()

// Line 34-43: useCRUD6Api initialized
// This calls loadSchema() which is ASYNC
const { ... } = useCRUD6Api()

// Line 283: Model watcher with immediate: true
watch(model, async (newModel) => {
    ...
    loadSchema(newModel, false, 'detail,form')  // Also ASYNC
}, { immediate: true })
```

Since both are async, they execute as:
1. useCRUD6Api calls `loadSchema('users', false, 'form')` → returns a Promise
2. Model watcher fires immediately, calls `loadSchema('users', false, 'detail,form')` → returns a Promise
3. Both Promises race to the store

The store processes them in the order they arrive. With my fix:

**Scenario A: PageRow's watcher wins the race**
1. PageRow: `loadSchema('users', false, 'detail,form')`
   - Not in cache, not loading → makes API call
   - Sets `loadingStates['users:detail,form'] = true`
2. useCRUD6Api: `loadSchema('users', false, 'form')`
   - Not in cache
   - `isRelatedContextLoading('users', 'form')` → finds 'users:detail,form' is loading
   - **Waits** for it to complete
   - Once complete, returns cached 'users:form'
   - ✅ No duplicate API call

**Scenario B: useCRUD6Api wins the race**
1. useCRUD6Api: `loadSchema('users', false, 'form')`
   - Not in cache, not loading → makes API call
   - Sets `loadingStates['users:form'] = true`
2. PageRow: `loadSchema('users', false, 'detail,form')`
   - Not in cache
   - `isRelatedContextLoading('users', 'detail,form')` → checks if broader context contains 'detail' and 'form'
   - 'users:form' is loading (only has 'form', not 'detail')
   - **Does NOT wait** (because 'form' doesn't contain 'detail')
   - Makes second API call
   - ❌ Still has duplicate calls

**The problem:** My fix only handles the case where a BROADER context is loading. It doesn't handle the reverse case where a NARROWER context is loading.

## Solution: Need Bidirectional Waiting

The fix needs to be enhanced to handle both cases:
1. If broader context is loading → wait for it (current fix)
2. If narrower context is loading that's a SUBSET of what we need → still make call, but cancel/ignore the narrower one

Actually, for case 2, we can't cancel the HTTP request once it's in flight. But we can make the store smarter about which request to prioritize.

A simpler solution: Make PageRow load schema EARLIER, before useCRUD6Api is initialized. This ensures PageRow always wins the race.

Or even simpler: Make useCRUD6Api NOT load schema automatically. Only load it when validation is actually needed (lazy loading).

Let me check if validation is actually used...
