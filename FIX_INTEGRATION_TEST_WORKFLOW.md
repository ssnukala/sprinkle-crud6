# Integration Test Workflow Fix

## Problem Statement

The integration test workflow (`integration-test.yml`) was incorrectly overwriting entire files with complete content, which:

1. **MyApp.php**: Included non-existent `getRoutes()` and `getServices()` methods referencing `MyAppRoutes::class` and `MyAppServicesProvider::class` - classes that don't exist in a fresh UserFrosting 6 installation
2. **main.ts**: Completely overwrote the file instead of adding CRUD6 references to the existing structure
3. **router/index.ts**: Completely overwrote the file instead of adding routes to the existing router

## Root Cause

According to the UserFrosting 6 skeleton pattern (from `@userfrosting/monorepo/files/packages/skeleton/app/src/MyApp.php`), the default `MyApp.php` only requires:
- `getName()` - returns the application name
- `getPath()` - returns the application path
- `getSprinkles()` - returns the list of sprinkles

Routes and services are provided by the sprinkles themselves (like CRUD6), not by the MyApp class.

## Solution

### 1. MyApp.php Configuration

**Before (WRONG):**
```yaml
cat > app/src/MyApp.php << 'EOF'
<?php
declare(strict_types=1);
namespace UserFrosting\App;
use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Sprinkle\SprinkleRecipe;
class MyApp implements SprinkleRecipe
{
    public function getName(): string { return 'My App'; }
    public function getPath(): string { return __DIR__ . '/../'; }
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
            CRUD6::class,
        ];
    }
    public function getRoutes(): array { return [MyAppRoutes::class]; }  // WRONG!
    public function getServices(): array { return [MyAppServicesProvider::class]; }  // WRONG!
}
EOF
```

**After (CORRECT):**
```yaml
# Add CRUD6 import after existing imports
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php
# Add CRUD6::class to getSprinkles() array
sed -i '/Admin::class,/a \            CRUD6::class,' app/src/MyApp.php
```

This approach:
- ✅ Preserves the existing MyApp.php structure
- ✅ Only adds the CRUD6 import and sprinkle reference
- ✅ Doesn't introduce non-existent classes
- ✅ Follows UserFrosting 6 skeleton pattern

### 2. main.ts Configuration

**Before (WRONG):**
```yaml
cat > app/assets/main.ts << 'EOF'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

/** Setup crud6 Sprinkle */
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(CRUD6Sprinkle)

app.mount('#app')
EOF
```

**After (CORRECT):**
```yaml
# Add import after existing imports
sed -i '/import App from/a \
\
/** Setup crud6 Sprinkle */\
import CRUD6Sprinkle from '\''@ssnukala/sprinkle-crud6'\''' app/assets/main.ts

# Add app.use(CRUD6Sprinkle) after app.use(router)
sed -i '/app.use(router)/a app.use(CRUD6Sprinkle)' app/assets/main.ts
```

This approach:
- ✅ Preserves the existing main.ts structure
- ✅ Adds CRUD6 plugin to the existing app setup
- ✅ Maintains any other customizations in main.ts

### 3. router/index.ts Configuration

**Before (WRONG):**
```yaml
cat > app/assets/router/index.ts << 'EOF'
import { createRouter, createWebHistory } from 'vue-router'
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      children: [
        ...CRUD6Routes
      ]
    }
  ]
})

export default router
EOF
```

**After (CORRECT):**
```yaml
# Add import at the top of the router file
sed -i '1i import CRUD6Routes from '\''@ssnukala/sprinkle-crud6/routes'\''' app/assets/router/index.ts

# Add route registration after router creation
sed -i '/export default router/i \
\
// Add CRUD6 routes\
router.addRoute({\
    path: '\''/crud6'\'',\
    children: CRUD6Routes\
})\
' app/assets/router/index.ts
```

This approach:
- ✅ Preserves the existing router structure
- ✅ Uses `router.addRoute()` to dynamically add CRUD6 routes
- ✅ Maintains any other routes defined in the router
- ✅ Follows Vue Router best practices for dynamic route registration

## Documentation Updates

### INTEGRATION_TESTING.md

**Updated Section 4 (Configure the Sprinkle in MyApp.php):**
- Removed `getRoutes()` and `getServices()` methods from example
- Added note explaining that default UserFrosting 6 MyApp.php only requires `getName()`, `getPath()`, and `getSprinkles()`

**Updated Section 6 (Configure Frontend Assets in main.ts):**
- Changed to show adding CRUD6 to existing file rather than complete file replacement
- Separated main.ts and router/index.ts configuration
- Showed clear examples of what to add to existing files

### QUICK_TEST_GUIDE.md

**Updated:**
- MyApp.php example (already correct - no changes needed)
- main.ts changes to use `import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'`
- Added separate section for router/index.ts changes
- Updated quick setup comments to match new patterns

## Testing

All changes were tested with:

1. **YAML Syntax Validation:**
   ```bash
   python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))"
   ```
   ✅ Passed

2. **Sed Command Validation:**
   - Created sample files matching UserFrosting 6 skeleton structure
   - Applied sed commands to verify correct output
   - Verified correct placement and formatting
   ✅ All commands tested and validated

## Benefits

1. **Correct UserFrosting 6 Pattern:** Follows the official skeleton structure
2. **Non-Destructive:** Preserves existing file content and customizations
3. **Maintainable:** Future changes to UF6 skeleton won't break our integration
4. **Flexible:** Works with any existing MyApp.php, main.ts, or router configuration
5. **Clear Documentation:** Users understand they're adding to existing files, not replacing them

## References

- UserFrosting 6 skeleton: `@userfrosting/monorepo/files/packages/skeleton/app/src/MyApp.php`
- CRUD6 Sprinkle class: `app/src/CRUD6.php` (shows proper pattern with getRoutes() and getServices() in the sprinkle itself)
- Issue description: Clear requirement to "add references for CRUD6 routes and not overwrite the whole file"
