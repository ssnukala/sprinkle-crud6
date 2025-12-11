# Configuration Guide

This guide explains how to customize the integration testing framework for your UserFrosting 6 sprinkle.

## Configuration Files

The framework uses two main JSON configuration files:

1. **`integration-test-paths.json`** - Defines API endpoints and frontend routes to test
2. **`integration-test-seeds.json`** - Defines database seeds and validation rules

## integration-test-paths.json

### Structure

```json
{
  "description": "Path testing configuration for your sprinkle",
  "paths": {
    "authenticated": {
      "api": { /* API endpoints requiring authentication */ },
      "frontend": { /* Frontend pages requiring authentication */ }
    },
    "unauthenticated": {
      "api": { /* API endpoints without authentication */ },
      "frontend": { /* Frontend pages without authentication */ }
    }
  },
  "config": {
    "base_url": "http://localhost:8080",
    "auth": {
      "username": "admin",
      "password": "admin123"
    },
    "timeout": {
      "api": 10,
      "frontend": 30
    }
  }
}
```

### API Path Definition

Each API endpoint is defined with:

```json
{
  "endpoint_name": {
    "method": "GET|POST|PUT|DELETE",
    "path": "/api/yoursprinkle/yourmodel",
    "description": "Human-readable description",
    "expected_status": 200,
    "validation": {
      "type": "json|status_only|redirect_to_login",
      "contains": ["required", "keys"]
    },
    "skip": false,
    "skip_reason": "Optional reason for skipping"
  }
}
```

#### Validation Types

**`json` - Validate JSON response**
```json
{
  "validation": {
    "type": "json",
    "contains": ["rows", "count", "total"]
  }
}
```
Checks that:
- Response is valid JSON
- Contains all specified keys at the root level

**`status_only` - Check HTTP status only**
```json
{
  "validation": {
    "type": "status_only"
  }
}
```
Only verifies the HTTP status code matches `expected_status`.

**`redirect_to_login` - Check redirect to login**
```json
{
  "validation": {
    "type": "redirect_to_login",
    "contains": ["/account/sign-in", "login"]
  }
}
```
Used for unauthenticated paths - verifies redirect to login page.

### Frontend Path Definition

Each frontend page is defined with:

```json
{
  "page_name": {
    "path": "/yoursprinkle/page",
    "description": "Human-readable description",
    "screenshot": true,
    "screenshot_name": "unique_filename",
    "skip": false,
    "skip_reason": "Optional reason for skipping"
  }
}
```

#### Screenshot Options

- `screenshot: true` - Enable screenshot capture for this page
- `screenshot_name: "my_page"` - Filename for screenshot (saved as `/tmp/screenshot_my_page.png`)

### Examples

#### CRUD API Endpoints

```json
{
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
      },
      "products_single": {
        "method": "GET",
        "path": "/api/myapp/products/2",
        "description": "Get single product by ID",
        "expected_status": 200,
        "validation": {
          "type": "json",
          "contains": ["id", "name", "price"]
        }
      },
      "products_create": {
        "method": "POST",
        "path": "/api/myapp/products",
        "description": "Create new product",
        "expected_status": 200,
        "skip": true,
        "skip_reason": "Requires request body - tested separately"
      },
      "products_update": {
        "method": "PUT",
        "path": "/api/myapp/products/2",
        "description": "Update product",
        "expected_status": 200,
        "skip": true,
        "skip_reason": "Requires request body - tested separately"
      },
      "products_delete": {
        "method": "DELETE",
        "path": "/api/myapp/products/2",
        "description": "Delete product",
        "expected_status": 200,
        "skip": true,
        "skip_reason": "Destructive operation - tested separately"
      }
    }
  }
}
```

#### Frontend Pages

```json
{
  "authenticated": {
    "frontend": {
      "products_list": {
        "path": "/myapp/products",
        "description": "Products list page",
        "screenshot": true,
        "screenshot_name": "products_list"
      },
      "products_detail": {
        "path": "/myapp/products/2",
        "description": "Product detail page",
        "screenshot": true,
        "screenshot_name": "products_detail"
      },
      "dashboard": {
        "path": "/myapp/dashboard",
        "description": "Application dashboard",
        "screenshot": true,
        "screenshot_name": "dashboard"
      }
    }
  }
}
```

#### Unauthenticated Paths

```json
{
  "unauthenticated": {
    "api": {
      "products_list_unauth": {
        "method": "GET",
        "path": "/api/myapp/products",
        "description": "Verify products API requires authentication",
        "expected_status": 401,
        "validation": {
          "type": "status_only"
        }
      }
    },
    "frontend": {
      "products_page_unauth": {
        "path": "/myapp/products",
        "description": "Verify products page requires authentication",
        "validation": {
          "type": "redirect_to_login",
          "contains": ["/account/sign-in"]
        }
      }
    }
  }
}
```

## integration-test-seeds.json

### Structure

```json
{
  "description": "Database seed configuration for your sprinkle",
  "seeds": {
    "account": {
      "description": "Account sprinkle base seeds (required)",
      "order": 1,
      "seeds": [ /* Account seed definitions */ ]
    },
    "yoursprinkle": {
      "description": "Your sprinkle seeds",
      "order": 2,
      "seeds": [ /* Your seed definitions */ ]
    }
  },
  "validation": {
    "idempotency": { /* Idempotency test config */ },
    "relationships": { /* Relationship validation */ }
  },
  "admin_user": {
    "username": "admin",
    "password": "admin123",
    "email": "admin@example.com"
  }
}
```

### Seed Definition

Each seed is defined with:

```json
{
  "class": "Your\\Namespace\\Database\\Seeds\\SeedClass",
  "description": "Human-readable description",
  "required": true,
  "validation": {
    "type": "role|permissions",
    /* type-specific validation fields */
  }
}
```

### Validation Types

#### Role Validation

```json
{
  "validation": {
    "type": "role",
    "slug": "role-slug",
    "expected_count": 1
  }
}
```

Validates that:
- A role with the specified slug exists
- The count matches expected_count

#### Permission Validation

```json
{
  "validation": {
    "type": "permissions",
    "slugs": ["perm1", "perm2", "perm3"],
    "expected_count": 3,
    "role_assignments": {
      "role-slug": 3,
      "another-role": 2
    }
  }
}
```

Validates that:
- All specified permissions exist
- The total count matches expected_count
- Permissions are correctly assigned to roles (count per role)

### Examples

#### Complete Seed Configuration

```json
{
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
          "class": "MyApp\\Database\\Seeds\\DefaultRoles",
          "description": "Create MyApp-specific roles",
          "required": true,
          "validation": {
            "type": "role",
            "slug": "myapp-admin",
            "expected_count": 1
          }
        },
        {
          "class": "MyApp\\Database\\Seeds\\DefaultPermissions",
          "description": "Create MyApp permissions",
          "required": true,
          "validation": {
            "type": "permissions",
            "slugs": [
              "create_products",
              "edit_products",
              "delete_products",
              "view_products"
            ],
            "expected_count": 4,
            "role_assignments": {
              "myapp-admin": 4,
              "site-admin": 4
            }
          }
        }
      ]
    }
  },
  "admin_user": {
    "username": "admin",
    "password": "admin123",
    "email": "admin@example.com",
    "firstName": "Admin",
    "lastName": "User"
  }
}
```

## Common Customization Scenarios

### Adding a New Model

1. Add API endpoints:
```json
{
  "authenticated": {
    "api": {
      "mymodel_list": {
        "method": "GET",
        "path": "/api/myapp/mymodel",
        "expected_status": 200,
        "validation": {
          "type": "json",
          "contains": ["rows"]
        }
      }
    }
  }
}
```

2. Add frontend page:
```json
{
  "authenticated": {
    "frontend": {
      "mymodel_list": {
        "path": "/myapp/mymodel",
        "screenshot": true,
        "screenshot_name": "mymodel_list"
      }
    }
  }
}
```

### Adding Custom Permissions

1. Create seed class for permissions
2. Add to seeds configuration:
```json
{
  "class": "MyApp\\Database\\Seeds\\CustomPermissions",
  "validation": {
    "type": "permissions",
    "slugs": ["custom_perm1", "custom_perm2"],
    "expected_count": 2,
    "role_assignments": {
      "myapp-admin": 2
    }
  }
}
```

### Skipping Destructive Tests

For operations that modify data:

```json
{
  "products_delete": {
    "method": "DELETE",
    "path": "/api/myapp/products/2",
    "expected_status": 200,
    "skip": true,
    "skip_reason": "Destructive - requires separate test with data reset"
  }
}
```

## Best Practices

1. **Use meaningful names** - Name your paths clearly (e.g., `products_list`, not `pl`)
2. **Add descriptions** - Help future maintainers understand what's being tested
3. **Test unauthenticated access** - Always verify security with unauthenticated tests
4. **Skip when appropriate** - Skip tests that need request bodies or are destructive
5. **Use consistent IDs** - Use ID 2+ for tests (ID 1 is reserved for admin)
6. **Validate thoroughly** - Use appropriate validation for each endpoint type
7. **Document skips** - Always provide skip_reason when skipping tests
8. **Order seeds properly** - Account seeds first (order: 1), your sprinkle seeds second (order: 2)
9. **Test relationships** - Verify role-permission assignments in validation

## Troubleshooting

### Tests Failing with 404

- Check that paths match your actual routes
- Verify sprinkle is loaded in your app
- Ensure models exist in database

### Tests Failing with 401

- Check authentication credentials in config
- Verify admin user was created
- Ensure permissions are assigned correctly

### Screenshot Failures

- Ensure Playwright is installed: `npm install playwright`
- Install browser: `npx playwright install chromium`
- Check that frontend pages are accessible
- Verify Vite dev server is running

### Seed Validation Failures

- Check seed class namespaces match actual classes
- Verify seeds ran successfully (check logs)
- Ensure validation rules match actual data
- Check role-permission assignments in database

## Next Steps

After configuring:

1. Test locally: `php .github/scripts/test-paths.php .github/config/integration-test-paths.json`
2. Validate seeds: `php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json`
3. Add to CI/CD workflow
4. Take screenshots: `node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json`

See [WORKFLOW_EXAMPLE.md](WORKFLOW_EXAMPLE.md) for GitHub Actions integration.
