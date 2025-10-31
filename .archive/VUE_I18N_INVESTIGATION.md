# Vue I18n Investigation - Should UserFrosting 6 Adopt a `t()` Shorthand?

## Current State

UserFrosting 6 does NOT use vue-i18n. Instead, it has its own custom translation system implemented in `@userfrosting/sprinkle-core/stores/useTranslator`.

## Current Pattern in UserFrosting 6

### In Templates (Recommended ✅)
```vue
<template>
  <p>{{ $t('USER.SINGULAR') }}</p>
  <p>{{ $t('WELCOME', { name: user.name }) }}</p>
</template>
```

The `$t` global property is registered in sprinkle-core's index.ts:
```typescript
app.config.globalProperties.$t = translator.translate
```

### In Script Setup (Current Official Patterns)

UserFrosting 6 currently uses TWO patterns in their official theme-pink-cupcake:

**Pattern 1: Call useTranslator() at top level**
```typescript
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
const { getDateTime } = useTranslator()
// Use getDateTime() anywhere in component
```

**Pattern 2: Call useTranslator() inside computed properties**
```typescript
const searchLabel = computed(() => {
    const { translate } = useTranslator()
    return translate('SPRUNJE.SEARCH', { term: props.column })
})
```

## The Problem

Using `translator.translate()` is verbose compared to the vue-i18n standard of `t()`. This creates inconsistency:

- ❌ Script setup: `translator.translate('KEY')`
- ✅ Template: `$t('KEY')`

## Potential Solutions

### Option 1: Add a `t()` Composable to UserFrosting Core ⭐ (RECOMMENDED)

Create a new composable in `@userfrosting/sprinkle-core/composables/useTranslate.ts`:

```typescript
import { useTranslator } from '@userfrosting/sprinkle-core/stores'

/**
 * Shorthand composable for translations in script setup.
 * Provides a t() function similar to vue-i18n for consistency.
 */
export function useTranslate() {
    const { translate } = useTranslator()
    return { t: translate }
}
```

Then in components:
```typescript
import { useTranslate } from '@userfrosting/sprinkle-core/composables'
const { t } = useTranslate()

const message = computed(() => t('WELCOME', { name: user.name }))
```

**Pros:**
- ✅ Consistent with vue-i18n API
- ✅ Familiar to developers coming from vue-i18n
- ✅ Shorter, more readable code
- ✅ Doesn't break existing code
- ✅ No external dependencies

**Cons:**
- ⚠️ Requires updating UserFrosting core
- ⚠️ Small learning curve (one more composable to know)

### Option 2: Keep Current Pattern (translator.translate)

Keep using `useTranslator()` directly:
```typescript
const translator = useTranslator()
const message = computed(() => translator.translate('KEY'))
```

**Pros:**
- ✅ No changes needed
- ✅ Explicit about what's happening

**Cons:**
- ❌ Verbose
- ❌ Inconsistent with template usage ($t)
- ❌ Not familiar to vue-i18n developers

### Option 3: Use vue-i18n (NOT RECOMMENDED)

Add vue-i18n as a dependency and replace UserFrosting's custom translator.

**Pros:**
- ✅ Industry standard
- ✅ Well documented

**Cons:**
- ❌ Breaking change
- ❌ Additional dependency
- ❌ Would need to migrate all translations
- ❌ UserFrosting's translator has custom features (plural rules, nested translations)

## Recommendation

**Propose Option 1 to UserFrosting team**: Add a `useTranslate()` composable to sprinkle-core that provides a `t()` shorthand.

### Proposed Implementation for UserFrosting Core

File: `app/assets/composables/useTranslate.ts`
```typescript
import { useTranslator } from '@userfrosting/sprinkle-core/stores'

/**
 * Translation composable providing a shorthand t() function.
 * 
 * This composable is a convenience wrapper around useTranslator()
 * that provides a shorter t() function for use in script setup,
 * matching the $t() API available in templates.
 * 
 * @example
 * ```typescript
 * import { useTranslate } from '@userfrosting/sprinkle-core/composables'
 * 
 * const { t } = useTranslate()
 * const message = computed(() => t('WELCOME', { name: user.name }))
 * ```
 */
export function useTranslate() {
    const { translate, translateDate } = useTranslator()
    return { 
        t: translate,
        tdate: translateDate
    }
}
```

Then export it from the main composables index:
```typescript
export { useTranslate } from './useTranslate'
```

### Migration Path

1. Add `useTranslate()` to sprinkle-core
2. Update documentation to recommend `useTranslate()` for new code
3. Existing code using `useTranslator()` continues to work
4. Gradually migrate as components are updated

## Conclusion

While UserFrosting's custom translator works well, adding a `useTranslate()` composable would improve developer experience and make the API more consistent between templates and script setup. This is a non-breaking enhancement that could be proposed to the UserFrosting team.

For this sprinkle (CRUD6), we'll use `useTranslator()` directly since that's the current official pattern, but we should consider proposing the `useTranslate()` enhancement to the core team.
