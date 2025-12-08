# Dropdown Styling Fix - Action Items in UIKit Navigation

## Problem Statement

The action dropdown in PageList.vue had inconsistent styling between different action items:

1. **View action** - Direct `<a>` tag inside `<li>`, styled correctly by UIKit's `uk-nav li > a` selector
2. **Other actions** (Edit, Delete, etc.) - Wrapped in `crud6-unified-modal-wrapper` div, NOT styled by `uk-nav li > a`

### HTML Structure Comparison

**View Action (Working):**
```html
<li>
  <a href="/crud6/model/123">
    <font-awesome-icon icon="eye" /> View
  </a>
</li>
```

**Other Actions (Not Working):**
```html
<li>
  <div class="crud6-unified-modal-wrapper">
    <a href="#modal-id" uk-toggle>
      <font-awesome-icon icon="edit" /> Edit
    </a>
  </div>
</li>
```

### Root Cause

1. UIKit's nav dropdown styles target `uk-nav li > a` (direct child selector)
2. The wrapper div breaks this direct parent-child relationship
3. `display: contents` makes the wrapper transparent to **layout** (flexbox/grid) but not to **CSS selectors**
4. Moving the slot out of the wrapper causes Vue 3 fragment root warnings

## Solution

Added CSS rules in a **non-scoped** style block to explicitly style links inside the wrapper to match UIKit's nav dropdown styling:

```css
/* Non-scoped styles - required for cross-component selector matching */
<style>
/* IMPORTANT: Use ul.uk-dropdown-nav and ul.uk-nav to scope ONLY to nav contexts */
ul.uk-dropdown-nav .crud6-unified-modal-wrapper > a,
ul.uk-nav .crud6-unified-modal-wrapper > a {
    display: block;
    padding: 5px 0;
    color: #999;
    text-decoration: none;
}

ul.uk-dropdown-nav .crud6-unified-modal-wrapper > a:hover,
ul.uk-nav .crud6-unified-modal-wrapper > a:hover {
    color: #666;
}

/* Button reset ONLY in nav context to preserve normal button styling elsewhere */
ul.uk-dropdown-nav .crud6-unified-modal-wrapper > .uk-button,
ul.uk-nav .crud6-unified-modal-wrapper > .uk-button {
    all: unset;
    display: block;
    padding: 5px 0;
    color: #999;
    cursor: pointer;
    text-align: left;
}
</style>
```

### Why This Works

1. **Keeps wrapper div** - No Vue 3 warnings
2. **Maintains `display: contents`** - Wrapper transparent to layout
3. **Uses non-scoped styles** - Critical for cross-component selector matching (`.uk-nav` is in PageList.vue)
4. **Adds explicit styles** - Links styled consistently regardless of wrapper
5. **Matches UIKit defaults** - Uses same padding, colors, and hover states
6. **Scoped to nav context ONLY** - Uses `ul.uk-nav` and `ul.uk-dropdown-nav` to prevent affecting buttons in other contexts

### Why Non-Scoped is Required

Vue's scoped styles add unique attributes to elements and transform selectors. This breaks the ability to match parent selectors from other components:
- `.uk-nav` and `.uk-dropdown-nav` are on elements in `PageList.vue`
- Scoped styles in `UnifiedModal.vue` can't reach across component boundaries
- Non-scoped styles allow proper parent-child selector matching

### Critical Fix for Button Styling

The selectors were updated to use `ul.uk-nav` and `ul.uk-dropdown-nav` (instead of just `.uk-nav` and `.uk-dropdown-nav`) to ensure the styles ONLY apply in dropdown nav contexts. This preserves normal button styling with different colors (primary, danger, warning) for action buttons on detail pages.

## Files Changed

- `app/assets/components/CRUD6/UnifiedModal.vue` - Added CSS rules in `<style scoped>` section

## Testing

### Visual Verification Required

1. Navigate to a CRUD6 list page (e.g., `/crud6/users`)
2. Click the "Actions" dropdown for any row
3. Verify all action items (View, Edit, Delete, etc.) have consistent styling:
   - Same padding (5px vertical)
   - Same text color (#999)
   - Same hover color (#666)
   - No button styling visible in dropdown context

### No Vue Warnings

The wrapper div remains in place, preventing Vue 3 fragment root warnings that would occur if the template had multiple root elements.

## Notes

- This is a CSS-only fix, no JavaScript or template structure changes
- The wrapper serves dual purposes:
  1. Fixes Vue 3 component root requirement
  2. Contains both trigger and modal elements together
- `display: contents` is still valuable for layout purposes (prevents extra box in layout calculations)
- The explicit CSS rules ensure consistent styling regardless of selector specificity
