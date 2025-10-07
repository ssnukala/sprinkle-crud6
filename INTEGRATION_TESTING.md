# Integration Testing Guide for sprinkle-crud6

This guide provides step-by-step instructions for testing the sprinkle-crud6 package with a fresh UserFrosting 6 installation.

## Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 18+ and npm
- MySQL/MariaDB or SQLite database
- Git

## Setup Instructions

### 1. Install UserFrosting 6.0.0-beta.5

```bash
# Create a new directory for testing
mkdir uf6-crud6-test
cd uf6-crud6-test

# Clone UserFrosting 6.0.0-beta.5
git clone --branch 6.0.0-beta.5 https://github.com/userfrosting/UserFrosting.git .

# Remove git history (optional, for clean testing)
rm -rf .git
```

### 2. Configure Composer to Accept Beta Packages

Edit `composer.json` and ensure it has:

```json
{
    "minimum-stability": "beta",
    "prefer-stable": true
}
```

### 3. Install sprinkle-crud6 via Composer

```bash
composer require ssnukala/sprinkle-crud6
```

This will install the CRUD6 sprinkle and all its dependencies.

### 4. Configure the Sprinkle in MyApp.php

Edit `app/src/MyApp.php` and add the CRUD6 sprinkle to your sprinkles list:

```php
<?php

declare(strict_types=1);

namespace UserFrosting\App;

use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\CRUD6\CRUD6;  // Add this import
use UserFrosting\Sprinkle\SprinkleRecipe;

class MyApp implements SprinkleRecipe
{
    public function getName(): string
    {
        return 'My App';
    }

    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
            CRUD6::class,        // Add this line
        ];
    }
}
```

> **Note:** The default UserFrosting 6 `MyApp.php` only requires `getName()`, `getPath()`, and `getSprinkles()` methods. Routes and services are provided by the sprinkles themselves (like CRUD6). The PinkCupcake theme is optional and not required for CRUD6 functionality. If you have a custom theme or want to use PinkCupcake, you can add it to the sprinkles list and install it separately with `composer require userfrosting/theme-pink-cupcake`.

### 5. Install NPM Package

```bash
npm install @ssnukala/sprinkle-crud6
```

### 6. Configure Frontend Assets in main.ts

Edit `app/assets/main.ts` to import and use the CRUD6 plugin. Add these lines to your existing main.ts file:

```typescript
// Add this import after your existing imports
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

// Add this after creating your app and router
app.use(CRUD6Sprinkle)
```

For reference, your complete main.ts should look something like:

```typescript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import router from './router'
import App from './App.vue'
import AdminSprinkle from '@userfrosting/sprinkle-admin'

/** Setup CRUD6 Sprinkle */
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'

const app = createApp(App)

// Setup Pinia
const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
app.use(pinia)

// Register AdminSprinkle
app.use(AdminSprinkle)

// Register CRUD6 plugin (after AdminSprinkle)
app.use(CRUD6Sprinkle)

// Register router
app.use(router)

app.mount('#app')
```

Then edit `app/assets/router/index.ts` to add CRUD6 routes. Add these lines to your existing router file:

```typescript
// Add this import at the top
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'

// Then in your routes configuration, add ...CRUD6Routes to the children array
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      children: [
        // ... existing routes ...
        ...CRUD6Routes,  // Add as last entry in children array
      ]
    }
  ]
})
```

### 7. Install Dependencies and Build

```bash
# Install PHP dependencies
composer install

# Install Node dependencies  
npm install

# Build frontend assets
npm run vite:build
# OR for development with hot reload:
npm run vite:dev
```

### 8. Configure Database

Copy `app/.env.example` to `app/.env` and configure your database connection:

```bash
cp app/.env.example app/.env
```

Edit `app/.env` and set your database credentials:

```env
DB_CONNECTION="mysql"
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="userfrosting"
DB_USER="root"
DB_PASSWORD="your_password"
```

### 9. Run Database Migrations

```bash
php bakery migrate
```

This will create all necessary tables including the default `groups` table.

### 10. Seed Initial Data (Optional)

```bash
php bakery seed
# For automated/CI environments, use --force to skip confirmation:
# php bakery seed --force
```

This creates default admin user and groups for testing.

### 11. Create CRUD6 Schema for Groups

Create the schema file `app/schema/crud6/groups.json`:

```json
{
  "model": "groups",
  "title": "Group Management",
  "description": "Manage user groups",
  "table": "groups",
  "primary_key": "id",
  "timestamps": true,
  "soft_delete": false,
  "permissions": {
    "read": "uri_groups",
    "create": "create_group",
    "update": "update_group_field",
    "delete": "delete_group"
  },
  "default_sort": {
    "name": "asc"
  },
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "searchable": false
    },
    "slug": {
      "type": "string",
      "label": "Slug",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "max": 255
      }
    },
    "name": {
      "type": "string",
      "label": "Name",
      "required": true,
      "sortable": true,
      "filterable": true,
      "searchable": true,
      "validation": {
        "required": true,
        "max": 255
      }
    },
    "description": {
      "type": "text",
      "label": "Description",
      "required": false,
      "sortable": false,
      "filterable": false,
      "searchable": true
    },
    "icon": {
      "type": "string",
      "label": "Icon",
      "required": false,
      "sortable": false,
      "filterable": false,
      "searchable": false,
      "validation": {
        "max": 100
      }
    },
    "created_at": {
      "type": "datetime",
      "label": "Created At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "searchable": false
    },
    "updated_at": {
      "type": "datetime",
      "label": "Updated At",
      "readonly": true,
      "sortable": true,
      "filterable": false,
      "searchable": false
    }
  }
}
```

### 12. Start the Development Server

```bash
# In one terminal, run the PHP server:
php -S localhost:8080 -t public

# In another terminal (if using Vite for development):
npm run vite:dev
```

### 13. Test the Application

1. **Login**: Navigate to `http://localhost:8080` and login with the admin user created during seeding (typically `admin` / `password`)

2. **Test Groups List Page**: Navigate to `http://localhost:8080/crud6/groups`
   - You should see a list of all groups
   - Verify you can search, filter, and sort groups
   - Check that action buttons (Edit, Delete) are visible

3. **Test Group Detail Page**: Navigate to `http://localhost:8080/crud6/groups/1`
   - You should see the details of group with ID 1
   - Verify all fields are displayed correctly
   - Check that Edit and Delete buttons work

4. **Test Create Functionality**:
   - Click "Create New Group" button on the list page
   - Fill in the form fields
   - Submit and verify the new group appears in the list

5. **Test Edit Functionality**:
   - Click Edit button on a group
   - Modify fields
   - Save and verify changes are persisted

6. **Test Delete Functionality**:
   - Click Delete button on a group
   - Confirm deletion
   - Verify the group is removed from the list

## Expected Results

### Groups List Page (`/crud6/groups`)

You should see:
- ✅ Page title: "Group Management"
- ✅ Data table with columns: ID, Slug, Name, Description, Icon
- ✅ Search bar working
- ✅ Sort functionality on sortable columns
- ✅ Action buttons (View, Edit, Delete) in dropdown menu
- ✅ "Create New Group" button (if user has create permission)
- ✅ Pagination controls (if more than 10 groups)

### Group Detail Page (`/crud6/groups/1`)

You should see:
- ✅ Group information displayed in a structured format
- ✅ All fields from the schema shown with labels
- ✅ Edit button (if user has update permission)
- ✅ Delete button (if user has delete permission)
- ✅ Back to list button

## Troubleshooting

### Schema Not Loading
- Verify the schema file exists at `app/schema/crud6/groups.json`
- Check file permissions (should be readable)
- Verify JSON syntax is valid

### Import Errors
- Clear Node cache: `rm -rf node_modules && npm install`
- Rebuild assets: `npm run vite:build`
- Check browser console for JavaScript errors

### Database Errors
- Verify database connection in `.env`
- Run migrations: `php bakery migrate`
- Check that `groups` table exists in database

### Permission Errors
- Ensure logged-in user has appropriate permissions
- Check permission slugs match those in schema
- Verify user's role has the required permissions

## Clean Up

To remove the test installation:

```bash
cd ..
rm -rf uf6-crud6-test
```

## Additional Testing

### Test with Custom Models

Create additional schema files for other tables in your database and test the same workflows.

### Test API Endpoints Directly

Use tools like Postman or curl to test the API endpoints:

```bash
# Get all groups
curl -X GET http://localhost:8080/api/crud6/groups

# Get specific group
curl -X GET http://localhost:8080/api/crud6/groups/1

# Create new group (requires authentication)
curl -X POST http://localhost:8080/api/crud6/groups \
  -H "Content-Type: application/json" \
  -d '{"slug":"test-group","name":"Test Group","description":"A test group"}'
```

## Documentation Updates

After testing, update your project documentation with:
- Any issues encountered and solutions
- Screenshots of working pages
- Performance notes
- Browser compatibility notes

## Continuous Integration

Consider adding these tests to your CI pipeline:
1. Install UserFrosting 6.0.0-beta.5
2. Install sprinkle-crud6
3. Configure sprinkle
4. Run migrations
5. Run automated tests (if available)
6. Build frontend assets

## Support

If you encounter issues:
1. Check the [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
2. Verify UserFrosting version compatibility
3. Check the [UserFrosting Documentation](https://learn.userfrosting.com/v6/)
