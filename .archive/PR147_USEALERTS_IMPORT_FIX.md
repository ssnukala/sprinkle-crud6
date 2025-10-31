# PR #147 useAlerts Import Error - Fix Summary

## Issue
After merging PR #147 (Replace vue-i18n with UserFrosting's native useTranslator), the application threw a runtime error:

```
useCRUD6Actions.ts:7 Uncaught SyntaxError: The requested module '/assets/@fs/app/node_modules/@userfrosting/sprinkle-core/app/assets/stores/index.ts?v=e92c15ec' does not provide an export named 'useAlerts' (at useCRUD6Actions.ts:7:10)
```

## Root Cause
PR #147 created a new file `app/assets/composables/useCRUD6Actions.ts` during the language refactoring. When creating this file, an incorrect import was used:

```typescript
import { useAlerts, useTranslator } from '@userfrosting/sprinkle-core/stores'
```

**The problem:** UserFrosting 6's sprinkle-core does NOT export `useAlerts`. The correct export is `useAlertsStore`.

Additionally, the code attempted to use non-existent methods:
- `alerts.addSuccess(message)` ❌
- `alerts.addError(message)` ❌

These methods don't exist on the alerts store.

## UserFrosting 6 Alert Pattern

### Correct Imports
```typescript
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
```

### Correct Usage
```typescript
const alertsStore = useAlertsStore()

// Show success alert
alertsStore.push({
    title: 'Success',
    description: 'Operation completed successfully',
    style: Severity.Success
})

// Show error alert
alertsStore.push({
    title: 'Error',
    description: 'Operation failed',
    style: Severity.Danger
})
```

### Available Severity Values
From `@userfrosting/sprinkle-core/interfaces/severity.ts`:
- `Severity.Primary`
- `Severity.Secondary`
- `Severity.Success`
- `Severity.Warning`
- `Severity.Danger`
- `Severity.Info`
- `Severity.Muted`
- `Severity.Default`

## Changes Made

### File: `app/assets/composables/useCRUD6Actions.ts`

#### 1. Import Statement (Line 6-7)
```typescript
// Before:
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlerts, useTranslator } from '@userfrosting/sprinkle-core/stores'

// After:
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore, useTranslator } from '@userfrosting/sprinkle-core/stores'
```

#### 2. Store Initialization (Line 22)
```typescript
// Before:
const alerts = useAlerts()

// After:
const alertsStore = useAlertsStore()
```

#### 3. Error Alert (Lines 76-81)
```typescript
// Before:
alerts.addError(error.value.description || 'Action failed')

// After:
alertsStore.push({
    title: error.value.title || 'Action Failed',
    description: error.value.description || 'Action failed',
    style: Severity.Danger
})
```

#### 4. Success Alert - Field Update (Lines 132-136)
```typescript
// Before:
alerts.addSuccess(successMsg)

// After:
alertsStore.push({
    title: translator.translate('CRUD6.ACTION.SUCCESS_TITLE') || 'Success',
    description: successMsg,
    style: Severity.Success
})
```

#### 5. Success Alert - API Call (Lines 198-202)
```typescript
// Before:
alerts.addSuccess(successMsg)

// After:
alertsStore.push({
    title: translator.translate('CRUD6.ACTION.SUCCESS_TITLE') || 'Success',
    description: successMsg,
    style: Severity.Success
})
```

## Consistency with Other Files

This fix brings `useCRUD6Actions.ts` in line with the pattern used in other CRUD6 composables:

### useCRUD6Api.ts
```typescript
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'

useAlertsStore().push({
    title: response.data.title,
    description: response.data.description,
    style: Severity.Success
})
```

### useMasterDetail.ts
```typescript
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'

const alertsStore = useAlertsStore()
alertsStore.push({ /* ... */ })
```

### useCRUD6Relationships.ts
```typescript
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'

useAlertsStore().push({
    title: response.data.title,
    description: response.data.description,
    style: Severity.Success
})
```

## Reference: UserFrosting Core Pattern

This pattern is used throughout UserFrosting 6, as seen in `sprinkle-admin`:

### Example from GroupApi.php composable
```typescript
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'

useAlertsStore().push({
    title: response.data.title,
    description: response.data.description,
    style: Severity.Success
})
```

## Verification

### Before Fix
- ❌ Import error: `useAlerts` not found
- ❌ Runtime error when actions are executed
- ❌ No alerts displayed to users

### After Fix
- ✅ Correct `useAlertsStore` import
- ✅ Proper use of `push()` method with alert objects
- ✅ Correct `Severity` enum values
- ✅ Consistent with all other CRUD6 composables
- ✅ Follows UserFrosting 6 core patterns

## Related Files

All these files correctly use `useAlertsStore`:
- ✅ `app/assets/composables/useCRUD6Api.ts`
- ✅ `app/assets/composables/useMasterDetail.ts`
- ✅ `app/assets/composables/useCRUD6Relationships.ts`
- ✅ `app/assets/composables/useCRUD6Actions.ts` (after this fix)

## Lessons Learned

1. **Always verify exports** when importing from external packages
2. **Check existing patterns** in the codebase before implementing new code
3. **UserFrosting 6 uses `useAlertsStore`**, not `useAlerts`
4. **Alert objects require** `title`, `description`, and `style` properties
5. **Use `Severity` enum** from `@userfrosting/sprinkle-core/interfaces` for alert styling

## Date
Fixed: October 31, 2025
Original Issue: Introduced in PR #147 (merged October 31, 2025)
