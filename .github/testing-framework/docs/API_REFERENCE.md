# API Reference

Complete reference for all scripts in the UserFrosting 6 integration testing framework.

## PHP Scripts

### run-seeds.php

Runs database seeds based on JSON configuration.

**Synopsis:**
```bash
php run-seeds.php <config_file> [sprinkle_name]
```

**Arguments:**
- `config_file` (required) - Path to JSON configuration file
- `sprinkle_name` (optional) - Specific sprinkle to seed (default: all)

**Configuration Format:**
```json
{
  "seeds": {
    "sprinkle_name": {
      "order": 1,
      "seeds": [
        {
          "class": "Namespace\\Seeds\\SeedClass",
          "description": "Seed description",
          "required": true
        }
      ]
    }
  }
}
```

**Examples:**
```bash
# Run all seeds
php run-seeds.php integration-test-seeds.json

# Run only CRUD6 seeds
php run-seeds.php integration-test-seeds.json crud6

# Run account sprinkle seeds
php run-seeds.php integration-test-seeds.json account
```

**Exit Codes:**
- `0` - Success
- `1` - Configuration error or required seed failed
- `2` - Seed class not found

**Output:**
- Displays progress for each seed
- Shows warnings for failed optional seeds
- Shows errors for failed required seeds
- Returns summary of seeds run

---

### check-seeds-modular.php

Validates that seeds were created correctly based on configuration.

**Synopsis:**
```bash
php check-seeds-modular.php <config_file>
```

**Arguments:**
- `config_file` (required) - Path to JSON configuration file

**Validation Types:**

**Role Validation:**
```json
{
  "validation": {
    "type": "role",
    "slug": "role-slug",
    "expected_count": 1
  }
}
```

**Permission Validation:**
```json
{
  "validation": {
    "type": "permissions",
    "slugs": ["perm1", "perm2"],
    "expected_count": 2,
    "role_assignments": {
      "role-slug": 2
    }
  }
}
```

**Examples:**
```bash
# Validate all seeds
php check-seeds-modular.php integration-test-seeds.json

# Use with CI/CD
php check-seeds-modular.php integration-test-seeds.json && echo "Validation passed"
```

**Exit Codes:**
- `0` - All validations passed
- `1` - Validation failed

**Output:**
- Shows validation progress
- Reports missing roles/permissions
- Shows incorrect counts
- Displays role-permission assignment issues

---

### test-seed-idempotency-modular.php

Tests that seeds can be run multiple times without creating duplicates.

**Synopsis:**
```bash
# Get initial counts
php test-seed-idempotency-modular.php <config_file>

# Validate after re-seeding
php test-seed-idempotency-modular.php <config_file> after <before_counts>
```

**Arguments:**
- `config_file` (required) - Path to JSON configuration file
- `after` (optional) - Mode: validate after re-seeding
- `before_counts` (optional with 'after') - Counts from first run

**Examples:**
```bash
# Step 1: Get before counts
BEFORE=$(php test-seed-idempotency-modular.php integration-test-seeds.json | grep "BEFORE:")

# Step 2: Re-run seeds
php run-seeds.php integration-test-seeds.json

# Step 3: Validate counts match
php test-seed-idempotency-modular.php integration-test-seeds.json after "$BEFORE"
```

**Exit Codes:**
- `0` - Counts match (idempotent)
- `1` - Counts differ (duplicates created)

**Output:**
- Shows before/after counts for each validated entity
- Reports differences if found
- Confirms idempotency on success

---

### test-paths.php

Tests API endpoints and frontend routes based on configuration.

**Synopsis:**
```bash
php test-paths.php <config_file> [auth_mode] [path_type]
```

**Arguments:**
- `config_file` (required) - Path to JSON configuration file
- `auth_mode` (optional) - `auth`, `unauth`, or `both` (default: `both`)
- `path_type` (optional) - `api`, `frontend`, or `both` (default: `both`)

**Examples:**
```bash
# Test all paths (authenticated and unauthenticated, API and frontend)
php test-paths.php integration-test-paths.json

# Test only authenticated API paths
php test-paths.php integration-test-paths.json auth api

# Test only unauthenticated paths
php test-paths.php integration-test-paths.json unauth

# Test only frontend paths
php test-paths.php integration-test-paths.json both frontend
```

**Validation Types:**

**JSON Validation:**
```json
{
  "validation": {
    "type": "json",
    "contains": ["rows", "count"]
  }
}
```
Checks that response is valid JSON and contains specified keys.

**Status Only:**
```json
{
  "validation": {
    "type": "status_only"
  }
}
```
Only checks HTTP status code.

**Redirect to Login:**
```json
{
  "validation": {
    "type": "redirect_to_login",
    "contains": ["/account/sign-in"]
  }
}
```
Checks that unauthenticated access redirects to login.

**Exit Codes:**
- `0` - All tests passed
- `1` - One or more tests failed

**Output:**
- Shows test progress for each path
- Reports pass/fail status
- Shows validation errors
- Displays summary at end

---

## JavaScript Scripts

### take-screenshots-modular.js

Captures screenshots of frontend pages using Playwright.

**Synopsis:**
```bash
node take-screenshots-modular.js <config_file> [base_url] [username] [password]
```

**Arguments:**
- `config_file` (required) - Path to JSON configuration file
- `base_url` (optional) - Override base URL from config
- `username` (optional) - Override username from config
- `password` (optional) - Override password from config

**Configuration:**

Enable screenshots for specific paths:
```json
{
  "paths": {
    "authenticated": {
      "frontend": {
        "my_page": {
          "path": "/myapp/page",
          "screenshot": true,
          "screenshot_name": "my_page"
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

**Examples:**
```bash
# Use config file credentials
node take-screenshots-modular.js integration-test-paths.json

# Override base URL
node take-screenshots-modular.js integration-test-paths.json http://localhost:3000

# Override credentials
node take-screenshots-modular.js integration-test-paths.json http://localhost:8080 testuser testpass
```

**Output:**
- Screenshots saved to `/tmp/screenshot_<name>.png`
- Shows progress for each screenshot
- Reports login success/failure
- Lists all captured screenshots

**Requirements:**
- Node.js 20 or later
- Playwright installed: `npm install playwright`
- Chromium browser: `npx playwright install chromium`

**Exit Codes:**
- `0` - All screenshots captured
- `1` - Login failed or screenshot capture failed

---

## Configuration File Reference

### integration-test-paths.json

Defines API endpoints and frontend routes to test.

**Structure:**
```json
{
  "description": "Path testing configuration",
  "paths": {
    "authenticated": {
      "api": {
        "path_name": {
          "method": "GET|POST|PUT|DELETE",
          "path": "/api/sprinkle/model",
          "description": "Human-readable description",
          "expected_status": 200,
          "validation": {
            "type": "json|status_only",
            "contains": ["required", "keys"]
          },
          "skip": false,
          "skip_reason": "Optional reason"
        }
      },
      "frontend": {
        "page_name": {
          "path": "/sprinkle/page",
          "description": "Human-readable description",
          "screenshot": true,
          "screenshot_name": "unique_name",
          "skip": false
        }
      }
    },
    "unauthenticated": {
      "api": { /* Same structure, expect 401 */ },
      "frontend": { /* Same structure, expect redirect */ }
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

**Field Descriptions:**

- `method` - HTTP method (GET, POST, PUT, DELETE)
- `path` - URL path relative to base_url
- `expected_status` - Expected HTTP status code
- `validation.type` - Type of validation (json, status_only, redirect_to_login)
- `validation.contains` - Required keys for JSON validation
- `skip` - Skip this test (default: false)
- `skip_reason` - Explanation for skipping
- `screenshot` - Capture screenshot (frontend only)
- `screenshot_name` - Filename for screenshot

---

### integration-test-seeds.json

Defines database seeds and validation rules.

**Structure:**
```json
{
  "description": "Seed configuration",
  "seeds": {
    "sprinkle_name": {
      "description": "Sprinkle description",
      "order": 1,
      "seeds": [
        {
          "class": "Full\\Namespace\\Seeds\\SeedClass",
          "description": "Seed description",
          "required": true,
          "validation": {
            "type": "role|permissions",
            /* type-specific fields */
          }
        }
      ]
    }
  },
  "validation": {
    "idempotency": {
      "check_tables": ["roles", "permissions"]
    }
  },
  "admin_user": {
    "username": "admin",
    "password": "admin123",
    "email": "admin@example.com"
  }
}
```

**Field Descriptions:**

- `order` - Execution order (lower runs first)
- `class` - Fully qualified seed class name
- `required` - If true, failure stops execution
- `validation.type` - Type of validation (role, permissions)

**Role Validation Fields:**
- `slug` - Role slug to check
- `expected_count` - Expected number of roles

**Permission Validation Fields:**
- `slugs` - Array of permission slugs
- `expected_count` - Expected total count
- `role_assignments` - Object mapping role slugs to permission counts

---

## Common Usage Patterns

### Complete Test Suite

```bash
#!/bin/bash
# run-tests.sh - Complete integration test suite

CONFIG_DIR=".github/config"
SCRIPTS_DIR=".github/scripts"

# 1. Run database seeds
echo "Running seeds..."
php $SCRIPTS_DIR/run-seeds.php $CONFIG_DIR/integration-test-seeds.json

# 2. Validate seeds
echo "Validating seeds..."
php $SCRIPTS_DIR/check-seeds-modular.php $CONFIG_DIR/integration-test-seeds.json

# 3. Test idempotency
echo "Testing idempotency..."
BEFORE=$(php $SCRIPTS_DIR/test-seed-idempotency-modular.php $CONFIG_DIR/integration-test-seeds.json | grep "BEFORE:")
php $SCRIPTS_DIR/run-seeds.php $CONFIG_DIR/integration-test-seeds.json
php $SCRIPTS_DIR/test-seed-idempotency-modular.php $CONFIG_DIR/integration-test-seeds.json after "$BEFORE"

# 4. Test API paths
echo "Testing API paths..."
php $SCRIPTS_DIR/test-paths.php $CONFIG_DIR/integration-test-paths.json both api

# 5. Test frontend paths
echo "Testing frontend paths..."
php $SCRIPTS_DIR/test-paths.php $CONFIG_DIR/integration-test-paths.json both frontend

# 6. Capture screenshots
echo "Capturing screenshots..."
node $SCRIPTS_DIR/take-screenshots-modular.js $CONFIG_DIR/integration-test-paths.json

echo "✅ All tests completed!"
```

### CI/CD Pipeline

```yaml
- name: Run integration tests
  run: |
    # Run seeds
    php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json
    
    # Validate
    php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json
    
    # Test paths
    php .github/scripts/test-paths.php .github/config/integration-test-paths.json
    
    # Screenshots
    node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json
```

### Selective Testing

```bash
# Test only API paths for authenticated users
php test-paths.php config.json auth api

# Test only unauthenticated API access (verify 401 responses)
php test-paths.php config.json unauth api

# Test only frontend pages
php test-paths.php config.json both frontend

# Run only specific sprinkle seeds
php run-seeds.php seeds.json mysprinkle
```

---

## Troubleshooting

### Script Not Found

```bash
# Make scripts executable
chmod +x .github/scripts/*.php

# Or run with php explicitly
php .github/scripts/script-name.php
```

### Permission Errors

```bash
# Ensure UserFrosting directories are writable
chmod -R 777 app/storage app/logs app/cache
```

### Configuration Errors

```bash
# Validate JSON syntax
cat config.json | python3 -m json.tool

# Or use jq
jq . config.json
```

### Seed Failures

```bash
# Check seed class exists
php artisan list | grep seed

# Check database connection
php -r "require 'vendor/autoload.php'; /* test connection */"
```

---

## Support

For issues or questions:
- Check the [Installation Guide](INSTALLATION.md)
- Review the [Configuration Guide](CONFIGURATION.md)
- See [Workflow Examples](WORKFLOW_EXAMPLE.md)
- Open an issue on [GitHub](https://github.com/ssnukala/sprinkle-crud6/issues)
