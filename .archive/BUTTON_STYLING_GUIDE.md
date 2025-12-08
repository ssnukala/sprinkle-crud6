# Button Styling Configuration Guide

## Overview

Action button styling in CRUD6 is fully configurable via JSON schema attributes. The `style` attribute determines the color and appearance of action buttons.

## Supported Style Values

The `style` attribute in action definitions supports the following values:

| Style Value | UIKit Class | Visual Result | Recommended Use |
|------------|-------------|---------------|-----------------|
| `"primary"` | `uk-button-primary` | Green button | Main/positive actions (enable, activate, confirm) |
| `"secondary"` | `uk-button-secondary` | Light blue button | Secondary actions (reset, refresh) |
| `"danger"` | `uk-button-danger` | Red button | Destructive actions (delete, disable, remove) |
| `"warning"` | `uk-button-warning` | Orange button | Warning actions (password change, critical updates) |
| `"default"` | `uk-button-default` | Gray/transparent button | Neutral actions |

## Schema Configuration

### Example 1: Toggle Action with Primary Style
```json
{
  "key": "toggle_enabled",
  "label": "CRUD6.USER.TOGGLE_ENABLED",
  "icon": "toggle-on",
  "type": "field_update",
  "field": "flag_enabled",
  "toggle": true,
  "style": "primary",
  "permission": "update_user_field"
}
```

### Example 2: Destructive Action with Danger Style
```json
{
  "key": "disable_user",
  "label": "CRUD6.USER.DISABLE_USER",
  "icon": "user-slash",
  "type": "field_update",
  "field": "flag_enabled",
  "value": false,
  "style": "danger",
  "permission": "update_user_field",
  "confirm": "CRUD6.USER.DISABLE_CONFIRM"
}
```

### Example 3: Warning Action
```json
{
  "key": "password_action",
  "label": "CRUD6.USER.CHANGE_PASSWORD",
  "icon": "key",
  "type": "field_update",
  "field": "password",
  "style": "warning",
  "permission": "update_user_field"
}
```

## Default Behavior

If the `style` attribute is **not** specified in the schema, the system uses intelligent defaults:

1. **Action Inference** (`actionInference.ts`):
   - Toggle actions (`action.toggle === true`) → `'default'`
   - API call actions (`action.type === 'api_call'`) → `'secondary'`
   - Other actions → `'default'`

2. **Pattern Matching**: The system checks action keys for patterns:
   - Keys containing 'delete' or 'remove' → `'danger'`
   - Keys containing 'create' or 'new' → `'primary'`
   - Keys containing 'edit' or 'update' → `'secondary'`

## Implementation Details

### Component: UnifiedModal.vue

The default trigger button uses dynamic class binding:

```vue
<a
    :href="`#${modalId}`"
    uk-toggle
    class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-small"
    :class="getButtonClass(action.style)">
    <font-awesome-icon v-if="action.icon" :icon="action.icon" fixed-width />
    {{ actionLabel }}
</a>
```

### Function: getButtonClass

```typescript
const getButtonClass = (style?: string) => {
    switch (style) {
        case 'danger':
            return 'uk-button-danger'
        case 'warning':
            return 'uk-button-warning'
        case 'primary':
            return 'uk-button-primary'
        case 'secondary':
            return 'uk-button-secondary'
        default:
            return 'uk-button-default'
    }
}
```

## Context-Specific Styling

### Detail Pages (Info.vue)
Buttons display with their configured colors:
- Primary → Green background
- Secondary → Light blue background
- Danger → Red background
- Warning → Orange background
- Default → Gray border, transparent background

### Dropdown Nav (PageList.vue)
All action items are styled as nav links (gray text, no background) regardless of their `style` attribute. This is intentional to maintain consistent dropdown navigation appearance.

## Best Practices

1. **Always specify `style` for important actions**: Don't rely on defaults for critical actions
2. **Use semantic colors**:
   - Primary for positive/enabling actions
   - Danger for destructive/disabling actions
   - Warning for actions that need user attention
   - Secondary for auxiliary actions
3. **Consistency**: Use the same style for similar actions across different models

## Troubleshooting

### Issue: Buttons appear with no background (transparent)

**Cause**: Action has no `style` attribute or `style: "default"`

**Solution**: Add explicit `style` attribute:
```json
{
  "key": "my_action",
  "style": "primary"
}
```

### Issue: Buttons in dropdown don't show colors

**Expected Behavior**: Buttons in dropdown navigation (`PageList.vue`) are intentionally styled as nav links (gray text) to maintain visual consistency, regardless of their `style` attribute.

## Examples

See `examples/schema/users.json` for complete examples of properly styled actions.
