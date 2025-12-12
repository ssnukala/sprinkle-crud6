# Quick Start Guide: Adapting Modular Testing for Your Sprinkle

This guide shows you how to quickly adapt the CRUD6 modular testing framework for your own UserFrosting 6 sprinkle.

## Step 1: Copy Template Files

Copy the template configuration files to your sprinkle's `.github/config/` directory:

```bash
# Create config directory in your sprinkle
mkdir -p .github/config

# Copy templates from CRUD6 sprinkle
cp path/to/crud6/.github/config/template-integration-test-paths.json \
   .github/config/integration-test-paths.json

cp path/to/crud6/.github/config/template-integration-test-seeds.json \
   .github/config/integration-test-seeds.json
```

## Step 2: Copy Testing Scripts

Copy the reusable testing scripts to your sprinkle's `.github/testing-framework/scripts/` directory:

```bash
# Create scripts directory
mkdir -p .github/scripts

# Copy all modular testing scripts from CRUD6 sprinkle
cp path/to/crud6/.github/testing-framework/scripts/run-seeds.php .github/testing-framework/scripts/
cp path/to/crud6/.github/testing-framework/scripts/check-seeds-modular.php .github/testing-framework/scripts/
cp path/to/crud6/.github/testing-framework/scripts/test-seed-idempotency-modular.php .github/testing-framework/scripts/
cp path/to/crud6/.github/testing-framework/scripts/test-paths.php .github/testing-framework/scripts/
cp path/to/crud6/.github/testing-framework/scripts/take-screenshots-modular.js .github/testing-framework/scripts/

# Make scripts executable
chmod +x .github/testing-framework/scripts/*.php
```

## Step 3: Customize Path Configuration

Edit `.github/config/integration-test-paths.json`:

### Replace Model Names

Find and replace:
- `yoursprinkle` → your sprinkle's route prefix (e.g., `myapp`)
- `yourmodel` → your model name (e.g., `products`, `customers`)

### Enable Screenshots

For frontend paths where you want screenshots, add:
```json
{
  "screenshot": true,
  "screenshot_name": "unique_name_for_file"
}
```

### Example Customization

**Before (Template):**
```json
{
  "your_model_list": {
    "method": "GET",
    "path": "/api/yoursprinkle/yourmodel",
    ...
  }
}
```

**After (For Products Model):**
```json
{
  "products_list": {
    "method": "GET",
    "path": "/api/myapp/products",
    ...
  }
}
```

### Add More Paths

Add additional API endpoints and frontend routes as needed:

```json
{
  "authenticated": {
    "api": {
      "products_list": { ... },
      "products_single": { ... },
      "categories_list": { ... },  // Add more models
      "orders_list": { ... }
    },
    "frontend": {
      "products_list": { ... },
      "dashboard": { ... }  // Add custom pages
    }
  }
}
```

## Step 4: Customize Seeds Configuration

Edit `.github/config/integration-test-seeds.json`:

### Update Sprinkle Name and Order

```json
{
  "seeds": {
    "account": { ... },  // Keep as-is
    "mysprinkle": {  // Change from "yoursprinkle"
      "description": "My Sprinkle seeds",
      "order": 2,
      ...
    }
  }
}
```

### Update Seed Classes

Replace template seed classes with your actual seed classes:

```json
{
  "seeds": [
    {
      "class": "MyCompany\\MySprinkle\\Database\\Seeds\\DefaultRoles",
      "description": "Create my sprinkle roles",
      "required": true,
      "validation": {
        "type": "role",
        "slug": "my-admin",
        "expected_count": 1
      }
    }
  ]
}
```

### Update Validation Rules

Customize validation rules to match your seeds:

```json
{
  "validation": {
    "type": "permissions",
    "slugs": [
      "create_product",
      "delete_product",
      "view_product"
    ],
    "expected_count": 3,
    "role_assignments": {
      "my-admin": 3,
      "site-admin": 3
    }
  }
}
```

## Step 5: Create GitHub Actions Workflow

Copy the workflow structure from CRUD6 and customize:

```yaml
name: Integration Test with UserFrosting 6

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  integration-test:
    runs-on: ubuntu-latest
    
    # ... (copy setup steps from CRUD6 workflow)
    
    - name: Seed database (Modular)
      run: |
        cd userfrosting
        cp ../my-sprinkle/.github/config/integration-test-seeds.json .
        cp ../my-sprinkle/.github/testing-framework/scripts/run-seeds.php .
        php run-seeds.php integration-test-seeds.json

    - name: Validate seed data (Modular)
      run: |
        cd userfrosting
        cp ../my-sprinkle/.github/testing-framework/scripts/check-seeds-modular.php .
        php check-seeds-modular.php integration-test-seeds.json

    - name: Test paths (Modular)
      run: |
        cd userfrosting
        cp ../my-sprinkle/.github/config/integration-test-paths.json .
        cp ../my-sprinkle/.github/testing-framework/scripts/test-paths.php .
        php test-paths.php integration-test-paths.json unauth

    - name: Take screenshots (Modular)
      run: |
        cd userfrosting
        cp ../my-sprinkle/.github/testing-framework/scripts/take-screenshots-modular.js .
        # Screenshots read from integration-test-paths.json (already copied)
        node take-screenshots-modular.js integration-test-paths.json
```

## Step 6: Test Locally (Optional)

You can test the scripts locally before running in CI:

```bash
# In your UserFrosting project root

# Test seeds
php run-seeds.php path/to/integration-test-seeds.json

# Validate seeds
php check-seeds-modular.php path/to/integration-test-seeds.json

# Test paths (requires running servers)
php test-paths.php path/to/integration-test-paths.json unauth api

# Take screenshots (requires running servers and Playwright)
node take-screenshots-modular.js path/to/integration-test-paths.json
php test-paths.php path/to/integration-test-paths.json unauth api
```

## Complete Example: MyApp Sprinkle

### File: `.github/config/integration-test-paths.json`

```json
{
  "description": "Integration test paths for MyApp sprinkle",
  "paths": {
    "authenticated": {
      "api": {
        "products_list": {
          "method": "GET",
          "path": "/api/myapp/products",
          "description": "Get list of products",
          "expected_status": 200,
          "validation": {
            "type": "json",
            "contains": ["rows"]
          }
        }
      },
      "frontend": {
        "products_list": {
          "path": "/myapp/products",
          "description": "Products list page",
          "screenshot": true,
          "screenshot_name": "products_list"
        }
      }
    },
    "unauthenticated": {
      "api": {
        "products_list": {
          "method": "GET",
          "path": "/api/myapp/products",
          "description": "Attempt to access products without auth",
          "expected_status": 401,
          "validation": {
            "type": "status_only"
          }
        }
      }
    }
  },
  "config": {
    "base_url": "http://localhost:8080",
    "auth": {
      "username": "admin",
      "password": "admin123"
    }
  }
}
```

### File: `.github/config/integration-test-seeds.json`

```json
{
  "description": "Integration test seeds for MyApp sprinkle",
  "seeds": {
    "account": {
      "description": "Account sprinkle base seeds",
      "order": 1,
      "seeds": [
        {
          "class": "UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups",
          "description": "Create default user groups",
          "required": true
        },
        {
          "class": "UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultPermissions",
          "description": "Create default permissions",
          "required": true
        },
        {
          "class": "UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultRoles",
          "description": "Create default roles",
          "required": true
        }
      ]
    },
    "myapp": {
      "description": "MyApp sprinkle seeds",
      "order": 2,
      "seeds": [
        {
          "class": "MyCompany\\MyApp\\Database\\Seeds\\DefaultRoles",
          "description": "Create MyApp roles",
          "required": true,
          "validation": {
            "type": "role",
            "slug": "myapp-admin",
            "expected_count": 1
          }
        },
        {
          "class": "MyCompany\\MyApp\\Database\\Seeds\\DefaultPermissions",
          "description": "Create MyApp permissions",
          "required": true,
          "validation": {
            "type": "permissions",
            "slugs": [
              "create_product",
              "delete_product",
              "view_product"
            ],
            "expected_count": 3,
            "role_assignments": {
              "myapp-admin": 3,
              "site-admin": 3
            }
          }
        }
      ]
    }
  },
  "validation": {
    "idempotency": {
      "enabled": true,
      "test_seeds": ["myapp"]
    }
  },
  "admin_user": {
    "enabled": true,
    "username": "admin",
    "password": "admin123",
    "email": "admin@example.com",
    "firstName": "Admin",
    "lastName": "User"
  }
}
```

## Benefits

✅ **Copy once, use everywhere** - Same scripts work for all sprinkles  
✅ **No code changes needed** - Just modify JSON configuration  
✅ **Self-documenting** - JSON structure explains what's being tested  
✅ **Consistent testing** - Same approach across all your sprinkles  
✅ **Easy maintenance** - Update config, not workflow code  

## Need Help?

- See full documentation: `.github/MODULAR_TESTING_README.md`
- Check CRUD6 examples: `.github/config/integration-test-*.json`
- Review CRUD6 workflow: `.github/workflows/integration-test.yml`

## Common Customizations

### Testing Multiple Models

```json
{
  "authenticated": {
    "api": {
      "products_list": { "path": "/api/myapp/products", ... },
      "categories_list": { "path": "/api/myapp/categories", ... },
      "orders_list": { "path": "/api/myapp/orders", ... }
    }
  }
}
```

### Skip Destructive Tests

```json
{
  "products_delete": {
    "method": "DELETE",
    "path": "/api/myapp/products/1",
    "skip": true,
    "skip_reason": "Would delete test data"
  }
}
```

### Custom Validation

```json
{
  "validation": {
    "type": "json",
    "contains": ["id", "name", "price", "created_at"]
  }
}
```

That's it! You now have a modular, reusable testing framework for your sprinkle.
