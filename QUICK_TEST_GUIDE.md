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

# 4. Edit app/assets/main.ts - Add:
#    import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'
#    app.use(CRUD6Sprinkle)
#
#    Edit app/assets/router/index.ts - Add:
#    import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'
#    router.addRoute({ path: '/crud6', children: CRUD6Routes })

# 5. Install and build
composer install
npm install
npm run vite:build

# 6. Setup database
cp app/.env.example app/.env
# Edit app/.env with database credentials
php bakery migrate
php bakery seed

# 7. Create schema file: app/schema/crud6/groups.json
# (See INTEGRATION_TESTING.md for complete schema)

# 8. Start server
php -S localhost:8080 -t public

# 9. Test in browser:
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
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

// After creating app and router:
app.use(CRUD6Sprinkle)
```

## router/index.ts Changes

```typescript
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

// After creating router, before export:
router.addRoute({
    path: '/crud6',
    children: CRUD6Routes
})
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
