# Investigation: Source of Redundant API Calls and YAML Imports

**Date:** November 14, 2025  
**Issue:** Redundant API calls including YAML validation file imports on CRUD6 pages

## Problem Statement

When loading CRUD6 pages (e.g., `/crud6/groups` and `/crud6/groups/1`), the browser network log shows:

- YAML validation files being loaded: `group.yaml`, `role.yaml`, `login.yaml`, `register.yaml`, `profile-settings.yaml`, etc.
- Multiple schema API calls
- Total: 7+ requests per page instead of expected 2

**Key Question:** Are these coming from CRUD6 sprinkle code or from UserFrosting core dependencies?

## Investigation Findings

### 1. YAML Files Are NOT from CRUD6

The YAML files mentioned (`group.yaml`, `role.yaml`, `login.yaml`, etc.) are **NOT** part of the CRUD6 sprinkle. They are validation schema files from:

- `@userfrosting/sprinkle-admin` (group.yaml, role.yaml)
- `@userfrosting/sprinkle-account` (login.yaml, register.yaml, profile-settings.yaml, account-settings.yaml)

**Source location in UserFrosting:**
- `vendor/userfrosting/sprinkle-admin/app/schema/*.yaml`
- `vendor/userfrosting/sprinkle-account/app/schema/*.yaml`

### 2. CRUD6 Code Does NOT Import These Files

**Evidence:**
```bash
# Search for direct YAML imports in CRUD6
grep -r "group.yaml\|role.yaml\|login.yaml" app/assets/
# Result: Only found in comments explaining what NOT to do
```

**CRUD6 Validation Approach:**
- Uses `useCRUD6RegleAdapter` which converts CRUD6 JSON schemas directly to Regle rules
- Does NOT use `useRuleSchemaAdapter` which would import YAML files
- See: `app/assets/composables/useCRUD6ValidationAdapter.ts`

### 3. Likely Source: TypeScript Type References

**env.d.ts:**
```typescript
/// <reference types="vite/client" />
/// <reference types="@userfrosting/sprinkle-core" />
/// <reference types="@userfrosting/sprinkle-admin" />  // ← This line
```

The `/// <reference types="@userfrosting/sprinkle-admin" />` directive tells TypeScript to load type definitions from sprinkle-admin. This might be causing Vite to eagerly load modules from that package.

### 4. Import Chain Analysis

**CRUD6 Vue Pages:**
- `PageList.vue` - Only imports from `@userfrosting/sprinkle-core/stores`
- `PageRow.vue` - Only imports from `@userfrosting/sprinkle-core/stores`
- No direct imports from sprinkle-admin or sprinkle-account

**Composables:**
- `useCRUD6Api.ts` - Uses `useCRUD6RegleAdapter` (correct)
- `useCRUD6Schema.ts` - No UserFrosting validation imports
- All other composables - Clean

**Components:**
- All CRUD6 components use proper subpath imports
- No global component registration (which could cause eager loading)

### 5. Hypothesis: Vite/Build System Side Effects

The YAML imports are likely happening due to:

1. **TypeScript Reference Directive**: `env.d.ts` referencing `@userfrosting/sprinkle-admin`
2. **Vite Module Resolution**: When Vite resolves the type reference, it may process the admin sprinkle's index
3. **Transitive Imports**: Admin sprinkle's validation composables import YAML files
4. **Eager Loading**: Even though CRUD6 doesn't use them, they get bundled/loaded

**Evidence:**
The network log shows these as `?import` requests, which is Vite's import syntax:
```
group.yaml?import        304  script  useGroupApi.ts:16
role.yaml?import         304  script  useRoleApi.ts:16
login.yaml?import        304  script  useLoginApi.ts:9
```

The referrer files (`useGroupApi.ts`, `useRoleApi.ts`, `useLoginApi.ts`) are from sprinkle-admin and sprinkle-account, NOT from CRUD6.

## Root Cause Analysis

### Primary Cause: TypeScript Type Reference

**File:** `env.d.ts` line 3
```typescript
/// <reference types="@userfrosting/sprinkle-admin" />
```

**Why it exists:**
- Provides TypeScript IntelliSense for UserFrosting types
- Needed for IDE autocomplete and type checking

**Problem:**
- May cause Vite to process admin sprinkle's modules during build
- Admin sprinkle's composables (`useGroupApi`, `useRoleApi`) use `useRuleSchemaAdapter`
- `useRuleSchemaAdapter` imports YAML validation schemas
- Even if CRUD6 doesn't use these composables, they get loaded

### Secondary Cause: Vite Import Analysis

Vite performs static analysis of imports to build the dependency graph. When it sees the type reference, it may:

1. Process `@userfrosting/sprinkle-admin/package.json`
2. Find the main entry point
3. Process imports from that entry
4. Include validation composables and their YAML dependencies
5. Bundle them even if unused by CRUD6

## Verification Steps Needed

To confirm the root cause, we need to:

### 1. Check Vite Build Output

```bash
# Run Vite build with debug
npm run build -- --debug
# Look for which files are being included and why
```

### 2. Analyze Dependency Graph

```bash
# Use vite-bundle-visualizer or similar
npm install -D vite-bundle-visualizer
# Add to vite.config.ts and rebuild
```

### 3. Test TypeScript Reference Removal

**Experiment:**
```typescript
// env.d.ts - Comment out admin reference temporarily
/// <reference types="vite/client" />
/// <reference types="@userfrosting/sprinkle-core" />
// /// <reference types="@userfrosting/sprinkle-admin" />  // COMMENTED FOR TEST
```

Then check if YAML imports disappear.

### 4. Check Browser Network Tab

With browser DevTools Network tab open:
1. Clear cache
2. Navigate to `/crud6/groups`
3. Filter for `.yaml` files
4. Check "Initiator" column to see what triggered the import

## Potential Solutions

### Solution 1: Remove Type Reference (if possible)

**Risk:** Lose TypeScript IntelliSense for admin types

```typescript
// env.d.ts
/// <reference types="vite/client" />
/// <reference types="@userfrosting/sprinkle-core" />
// Remove: /// <reference types="@userfrosting/sprinkle-admin" />
```

### Solution 2: Configure Vite to Exclude YAML Files

```typescript
// vite.config.ts
export default defineConfig({
  build: {
    rollupOptions: {
      external: [
        /\.yaml$/,  // Exclude all YAML files from bundle
      ]
    }
  }
})
```

### Solution 3: Use Dynamic Imports

If any CRUD6 code needs admin types, use dynamic imports:

```typescript
// Instead of: import type { AdminType } from '@userfrosting/sprinkle-admin'
// Use: type AdminType = import('@userfrosting/sprinkle-admin').AdminType
```

### Solution 4: UserFrosting Core Fix

The real fix should be in UserFrosting packages:

**In sprinkle-admin and sprinkle-account:**
- Use dynamic imports for YAML files
- Lazy load validation schemas only when needed
- Don't import YAML at module top level

**Example change in useGroupApi.ts:**
```typescript
// ❌ Current (causes eager loading)
import schema from '../schema/group.yaml'

// ✅ Better (lazy load)
const loadSchema = async () => {
  const schema = await import('../schema/group.yaml')
  return schema.default
}
```

## Recommended Next Steps

1. **Verify with Network Tab**
   - Open browser DevTools
   - Navigate to CRUD6 pages
   - Check which files actually trigger YAML imports
   - Confirm they come from admin/account, not CRUD6

2. **Test Type Reference Removal**
   - Comment out sprinkle-admin reference in env.d.ts
   - Rebuild and test
   - See if YAML imports disappear

3. **Report to UserFrosting**
   - If type reference is the cause, this affects all sprinkles
   - Should be fixed in UserFrosting core
   - YAML files should be lazy-loaded

4. **Workaround for CRUD6**
   - If type reference must stay, document the behavior
   - Note that YAML files are cached (304) so minimal performance impact
   - Focus on eliminating duplicate schema API calls instead

## Conclusion

**The redundant YAML file imports are NOT caused by CRUD6 code.**

They are likely caused by:
1. TypeScript type reference to `@userfrosting/sprinkle-admin` in `env.d.ts`
2. Vite's module resolution bringing in admin/account composables
3. Those composables using `useRuleSchemaAdapter` which imports YAML files
4. Eager loading during build even though CRUD6 doesn't use them

**CRUD6 is using the correct validation approach** (`useCRUD6RegleAdapter`) and is NOT importing YAML files directly.

**The fix should be in UserFrosting core** to make YAML validation schemas lazy-loaded instead of eagerly imported.

For now, the YAML imports are cached (304 status) so the performance impact is minimal compared to actual redundant API calls.
