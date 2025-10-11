# Quick Integration Test Guide

Quick reference for testing sprinkle-crud6 with UserFrosting 6.0.0-beta.5

## Quick Setup (5 minutes)

```bash
# 1. Clone UserFrosting 6.0.0-beta.5
git clone --branch 6.0.0-beta.5 https://github.com/userfrosting/UserFrosting.git uf6-test
cd uf6-test

# 2. Install sprinkle-crud6
composer require ssnukala/sprinkle-crud6
npm install @ssnukala/sprinkle-crud6

# 3. Edit app/src/MyApp.php - Add to getSprinkles():
#    use UserFrosting\Sprinkle\CRUD6\CRUD6;
#    CRUD6::class,

# 4. Edit app/assets/main.ts - Add after app.use(AdminSprinkle):
#    import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'
#    app.use(CRUD6Sprinkle)
#
#    Edit app/assets/router/index.ts - Add to children array:
#    import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'
#    ...CRUD6Routes,  // as last entry in children array

# 5. Install and build
composer install
npm install
npm update
npm run build  # Build assets for production

# For CI/CD: Use npm run build (completes and returns)
# For local dev: Use php bakery assets:vite (runs dev server in foreground)

# 6. Setup database
cp app/.env.example app/.env
# Edit app/.env with database credentials
# Add BAKERY_CONFIRM_SENSITIVE_COMMAND=false for CI/CD
php bakery migrate
php bakery seed --force

# 7. Create admin user
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com \
  --firstName=Admin \
  --lastName=User

# 8. Create schema file: app/schema/crud6/groups.json
# (See INTEGRATION_TESTING.md for complete schema)

# 9. Start servers (in two separate terminals)
# Terminal 1: PHP server
php bakery serve

# Terminal 2: Vite dev server
npm run vite:dev

# 10. Test in browser (login with admin / admin123):
# - http://localhost:8080/crud6/groups
# - http://localhost:8080/crud6/groups/1
```

## MyApp.php Changes

```php
use UserFrosting\Sprinkle\CRUD6\CRUD6;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class,        // Add this
    ];
}
```

> **Note:** PinkCupcake theme is optional. If you want to use it, install with `composer require userfrosting/theme-pink-cupcake` and add `PinkCupcake::class` to the sprinkles list.

## main.ts Changes

```typescript
/** Setup crud6 Sprinkle */
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

// After app.use(AdminSprinkle):
app.use(CRUD6Sprinkle)
```

## router/index.ts Changes

```typescript
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

// In the children array of your routes:
children: [
    // ... existing routes ...
    ...CRUD6Routes,  // Add as last entry
]
```

## Minimal groups.json Schema

```json
{
  "model": "groups",
  "title": "Groups",
  "table": "groups",
  "primary_key": "id",
  "permissions": {
    "read": "uri_groups",
    "create": "create_group",
    "update": "update_group_field",
    "delete": "delete_group"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID", "readonly": true },
    "slug": { "type": "string", "label": "Slug", "required": true },
    "name": { "type": "string", "label": "Name", "required": true },
    "description": { "type": "text", "label": "Description" }
  }
}
```

## Test Pages

1. **List**: `http://localhost:8080/crud6/groups`
2. **Detail**: `http://localhost:8080/crud6/groups/1`

## Expected Features

- ✅ List view with search/sort/filter
- ✅ Detail view with full record info
- ✅ Create/Edit/Delete modals
- ✅ Permission-based access control
- ✅ API endpoints at `/api/crud6/groups`

See [INTEGRATION_TESTING.md](./INTEGRATION_TESTING.md) for complete guide.
