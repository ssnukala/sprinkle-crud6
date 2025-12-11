# Frontend Integration Patterns

Guide for handling different frontend integration patterns across UserFrosting 6 sprinkles.

## Overview

Different sprinkles have different ways of integrating their frontend routes and components into a UserFrosting 6 application. This guide documents the common patterns and how to configure them in your integration tests.

## Pattern 1: Simple Route Array Import (CRUD6 Pattern)

**Used by:** sprinkle-crud6, most basic sprinkles

**router/index.ts configuration:**
```typescript
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

export default [
    ...CoreRoutes,
    ...AccountRoutes,
    ...AdminRoutes,
    ...CRUD6Routes,  // Simple array spread
]
```

**Workflow implementation:**
```bash
# Add import after AdminRoutes
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'" app/assets/router/index.ts

# Add routes after AccountRoutes
sed -i '/\.\.\.AccountRoutes,/a \            ...CRUD6Routes,' app/assets/router/index.ts
```

**main.ts configuration:**
```typescript
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

app.use(CoreSprinkle)
app.use(AccountSprinkle)
app.use(AdminSprinkle)
app.use(CRUD6Sprinkle)  // Simple app.use()
```

**Workflow implementation:**
```bash
# Add import after AdminSprinkle
sed -i "/import AdminSprinkle from '@userfrosting\/sprinkle-admin'/a import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'" app/assets/main.ts

# Add app.use() after AdminSprinkle
sed -i "/app.use(AdminSprinkle)/a app.use(CRUD6Sprinkle)" app/assets/main.ts
```

---

## Pattern 2: Factory Function with Layout (C6Admin Pattern)

**Used by:** sprinkle-c6admin, sprinkles with custom layouts

**router/index.ts configuration:**
```typescript
import { createC6AdminRoutes } from '@ssnukala/sprinkle-c6admin/routes'
import LayoutDashboard from '../layouts/LayoutDashboard.vue'

export default [
    ...CoreRoutes,
    ...AccountRoutes,
    ...AdminRoutes,
    // C6Admin routes with custom layout (includes CRUD6 routes)
    ...createC6AdminRoutes({ layoutComponent: LayoutDashboard })
]
```

**Workflow implementation:**
```bash
# Add imports (two separate lines)
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import { createC6AdminRoutes } from '@ssnukala\/sprinkle-c6admin\/routes'" app/assets/router/index.ts
sed -i "/import { createRouter, createWebHistory } from 'vue-router'/a import LayoutDashboard from '../layouts/LayoutDashboard.vue'" app/assets/router/index.ts

# Find last ] and insert before it
LAST_BRACKET_LINE=$(grep -n '\]' app/assets/router/index.ts | tail -1 | cut -d: -f1)
sed -i "${LAST_BRACKET_LINE}i\\        ,\\
// C6Admin routes with their own layout (includes CRUD6 routes)\\
...createC6AdminRoutes({ layoutComponent: LayoutDashboard })" app/assets/router/index.ts
```

**main.ts configuration:**
```typescript
import C6AdminSprinkle from '@ssnukala/sprinkle-c6admin'

app.use(CoreSprinkle)
app.use(AccountSprinkle)
app.use(AdminSprinkle)
app.use(C6AdminSprinkle)  // C6Admin automatically includes CRUD6
```

---

## Pattern 3: Nested Routes with Parent Component

**Used by:** Sprinkles with complex routing hierarchies

**router/index.ts configuration:**
```typescript
import MyAppRoutes from '@mycompany/sprinkle-myapp/routes'
import MyAppLayout from '@mycompany/sprinkle-myapp/layouts/MainLayout.vue'

export default [
    ...CoreRoutes,
    ...AccountRoutes,
    {
        path: '/myapp',
        component: MyAppLayout,
        children: MyAppRoutes
    }
]
```

**Workflow implementation:**
```bash
# Add imports
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import MyAppRoutes from '@mycompany\/sprinkle-myapp\/routes'" app/assets/router/index.ts
sed -i "/import { createRouter, createWebHistory } from 'vue-router'/a import MyAppLayout from '@mycompany\/sprinkle-myapp\/layouts\/MainLayout.vue'" app/assets/router/index.ts

# Add nested route structure
LAST_BRACKET_LINE=$(grep -n '\]' app/assets/router/index.ts | tail -1 | cut -d: -f1)
sed -i "${LAST_BRACKET_LINE}i\\        ,\\
{\\
    path: '/myapp',\\
    component: MyAppLayout,\\
    children: MyAppRoutes\\
}" app/assets/router/index.ts
```

---

## Pattern Comparison

| Pattern | Complexity | Use Case | Dependencies |
|---------|------------|----------|--------------|
| Simple Array | Low | Basic sprinkles | None |
| Factory Function | Medium | Custom layouts | Layout component |
| Nested Routes | High | Complex hierarchies | Parent component + routes |

---

## Configuring Your Sprinkle Pattern

### Step 1: Identify Your Pattern

Check your sprinkle's `app/assets/router/index.ts`:

```typescript
// Pattern 1: Simple export
export default [ ...routes ]

// Pattern 2: Factory function
export function createMyRoutes(options) { ... }

// Pattern 3: Nested structure
export default {
  path: '/myapp',
  children: [ ...routes ]
}
```

### Step 2: Document in Configuration

Add to your sprinkle's integration test workflow:

```yaml
# .github/config/frontend-integration.json
{
  "pattern": "factory-function",
  "imports": [
    {
      "statement": "import { createC6AdminRoutes } from '@ssnukala/sprinkle-c6admin/routes'",
      "position": "after_admin_routes"
    },
    {
      "statement": "import LayoutDashboard from '../layouts/LayoutDashboard.vue'",
      "position": "after_vue_router"
    }
  ],
  "routes_config": {
    "type": "factory",
    "function": "createC6AdminRoutes",
    "args": "{ layoutComponent: LayoutDashboard }",
    "position": "before_last_bracket"
  }
}
```

### Step 3: Implement in Workflow

Create a reusable script for your pattern:

```bash
# .github/scripts/configure-frontend.sh
#!/bin/bash

PATTERN=$1  # simple | factory | nested
SPRINKLE_NAME=$2

case $PATTERN in
  simple)
    # Pattern 1 implementation
    sed -i "/import AdminRoutes/a import ${SPRINKLE_NAME}Routes from '...'" app/assets/router/index.ts
    sed -i "/\.\.\.AccountRoutes,/a \            ...${SPRINKLE_NAME}Routes," app/assets/router/index.ts
    ;;
  factory)
    # Pattern 2 implementation
    sed -i "/import AdminRoutes/a import { create${SPRINKLE_NAME}Routes } from '...'" app/assets/router/index.ts
    # ... factory-specific logic
    ;;
  nested)
    # Pattern 3 implementation
    # ... nested-specific logic
    ;;
esac
```

---

## Testing Frontend Integration

### Verify Import Statements

```bash
# Check that imports were added
grep -q "import.*Routes.*from.*sprinkle-myapp" app/assets/router/index.ts
echo $? # Should be 0 (success)

# Check specific import patterns
if [[ "$PATTERN" == "factory" ]]; then
  grep -q "import { create.*Routes }" app/assets/router/index.ts
fi
```

### Verify Route Configuration

```bash
# Check that routes were added to array
grep -q "\.\.\.MyAppRoutes" app/assets/router/index.ts

# For factory pattern
grep -q "createMyAppRoutes(" app/assets/router/index.ts

# For nested pattern
grep -q "children: MyAppRoutes" app/assets/router/index.ts
```

### Validate Syntax

```bash
# TypeScript syntax check (if tsc available)
npx tsc --noEmit app/assets/router/index.ts

# Or just check that file is valid JavaScript
node -c app/assets/router/index.ts
```

---

## Common Issues and Solutions

### Issue 1: Import Statement in Wrong Location

**Problem:** Import added at end of file instead of with other imports

**Solution:** Be specific about insertion point
```bash
# Good - specific insertion point
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import MyRoutes ..." 

# Bad - appends to end
sed -i "$ a import MyRoutes ..."
```

### Issue 2: Routes Not Spread Correctly

**Problem:** Routes not included in array

**Solution:** Ensure proper array syntax
```bash
# Verify array structure
cat app/assets/router/index.ts | grep -A5 "export default"
```

### Issue 3: Factory Function Arguments

**Problem:** Missing or incorrect function arguments

**Solution:** Use proper quoting and escaping
```bash
# Escape special characters in sed
sed -i "s/REPLACE/...createRoutes({ layoutComponent: Layout })/"
```

### Issue 4: Multiple Bracket Locations

**Problem:** `sed` finds wrong bracket for insertion

**Solution:** Be more specific about context
```bash
# Instead of finding last ]
LAST_BRACKET_LINE=$(grep -n '\]' file.ts | tail -1 | cut -d: -f1)

# Find closing bracket of export default
EXPORT_CLOSE=$(sed -n '/export default \[/,/^\]/=' file.ts | tail -1)
```

---

## Best Practices

1. **Document Your Pattern** - Add comments in your workflow explaining which pattern you use
2. **Validate After Changes** - Always verify imports and route configuration
3. **Test Locally First** - Test frontend changes before committing to CI/CD
4. **Use Display Commands** - Use `cat` to show file contents in CI logs
5. **Handle Dependencies** - Document if your pattern requires specific components
6. **Version Compatibility** - Note which UserFrosting/Vue Router versions you support

---

## Framework Support

The testing framework can be extended to support all patterns:

### Template Configuration (Planned)

```json
{
  "frontend_integration": {
    "pattern": "factory|simple|nested",
    "router_config": {
      "imports": [ /* import statements */ ],
      "route_insertion": { /* how to add routes */ }
    },
    "main_config": {
      "imports": [ /* sprinkle imports */ ],
      "registration": { /* app.use() pattern */ }
    }
  }
}
```

### Workflow Template (Planned)

```bash
# Configure based on pattern from config
PATTERN=$(jq -r '.frontend_integration.pattern' config.json)

case $PATTERN in
  simple)   configure_simple_routes ;;
  factory)  configure_factory_routes ;;
  nested)   configure_nested_routes ;;
esac
```

---

## Contributing Patterns

Found a new pattern? Add it to this guide:

1. Document the pattern with examples
2. Show workflow implementation
3. Include validation steps
4. Add to pattern comparison table
5. Submit PR with your addition

---

## Summary

Different sprinkles use different frontend integration patterns. The key is to:

1. **Identify** which pattern your sprinkle uses
2. **Document** the pattern in your workflow/configuration
3. **Implement** pattern-specific logic in your CI/CD
4. **Validate** that integration worked correctly
5. **Test** frontend routes actually work

This ensures your integration tests accurately reflect how your sprinkle integrates into UserFrosting 6 applications.

---

**See Also:**
- [UserFrosting 6 Frontend Documentation](https://learn.userfrosting.com/)
- [Vue Router 4 Documentation](https://router.vuejs.org/)
- [Integration Test Workflow Example](WORKFLOW_EXAMPLE.md)
