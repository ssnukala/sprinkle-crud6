# Modular Integration Testing for UserFrosting Sprinkles

This directory contains a modular, reusable integration testing framework designed for UserFrosting 6 sprinkles. The framework uses JSON configuration files to define test paths, seeds, and validation rules, making it easy to adapt for different sprinkles.

## Overview

The modular testing framework consists of:

1. **JSON Configuration Files** - Define what to test
2. **Reusable Scripts** - Execute tests based on configurations
3. **GitHub Actions Workflow** - Orchestrate the testing pipeline

## Quick Start

### For CRUD6 Sprinkle

```bash
# Run seeds from configuration
php run-seeds.php integration-test-seeds.json

# Validate seeds were created correctly
php check-seeds-modular.php integration-test-seeds.json

# Test seed idempotency
php test-seed-idempotency-modular.php integration-test-seeds.json

# Test API and frontend paths
php test-paths.php integration-test-paths.json
```

### Adapting for Your Sprinkle

1. Copy the configuration templates
2. Modify paths and seeds for your sprinkle
3. Run the same scripts with your configuration files

## Configuration Files

### 1. integration-test-paths.json

Defines API and frontend paths to test, both authenticated and unauthenticated.

> **NEW**: Paths can now be auto-generated from model definitions! See [MODULAR_PATH_GENERATION_README.md](MODULAR_PATH_GENERATION_README.md) for details.

**Manual Approach** (defining each path):

**Structure:**
```json
{
  "paths": {
    "authenticated": {
      "api": { /* API endpoints requiring auth */ },
      "frontend": { /* Frontend pages requiring auth */ }
    },
    "unauthenticated": {
      "api": { /* API endpoints without auth */ },
      "frontend": { /* Frontend pages without auth */ }
    }
  },
  "config": {
    "base_url": "http://localhost:8080",
    "auth": { "username": "admin", "password": "admin123" },
    "timeout": { "api": 10, "frontend": 30 }
  }
}
```

**Example Path Definition:**
```json
{
  "groups_list": {
    "method": "GET",
    "path": "/api/crud6/groups",
    "description": "Get list of groups via CRUD6 API",
    "expected_status": 200,
    "validation": {
      "type": "json",
      "contains": ["rows"]
    }
  }
}
```

**Modular Approach** (auto-generating from models):

Instead of manually defining 40+ paths, define models once and generate all paths:

```bash
# 1. Define models in integration-test-models.json (5 model definitions)
# 2. Generate paths from models
node .github/scripts/generate-paths-from-models.js \
  .github/config/integration-test-models.json \
  .github/config/integration-test-paths.json
# 3. Result: 40 paths generated automatically!
```

See [MODULAR_PATH_GENERATION_README.md](MODULAR_PATH_GENERATION_README.md) for full documentation.

### 2. integration-test-seeds.json

Defines database seeds to run and how to validate them.

**Structure:**
```json
{
  "seeds": {
    "account": {
      "description": "Account sprinkle base seeds (required)",
      "order": 1,
      "seeds": [ /* seed configurations */ ]
    },
    "crud6": {
      "description": "CRUD6 sprinkle seeds",
      "order": 2,
      "seeds": [ /* seed configurations */ ]
    }
  },
  "validation": {
    "idempotency": { /* idempotency test config */ },
    "relationships": { /* relationship validation */ }
  },
  "admin_user": { /* admin user configuration */ }
}
```

**Example Seed Definition:**
```json
{
  "class": "UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles",
  "description": "Create CRUD6-specific roles (crud6-admin)",
  "required": true,
  "validation": {
    "type": "role",
    "slug": "crud6-admin",
    "expected_count": 1
  }
}
```

## Testing Scripts

### run-seeds.php

Runs database seeds based on configuration.

**Usage:**
```bash
php run-seeds.php <config_file> [sprinkle_name]

# Run all seeds
php run-seeds.php integration-test-seeds.json

# Run only CRUD6 seeds
php run-seeds.php integration-test-seeds.json crud6
```

**Features:**
- Runs seeds in order defined by sprinkle `order` field
- Exits on required seed failure
- Provides detailed output for each seed
- Automatically uses `php bakery seed` command

### check-seeds-modular.php

Validates that seeds were created correctly.

**Usage:**
```bash
php check-seeds-modular.php <config_file>

php check-seeds-modular.php integration-test-seeds.json
```

**Features:**
- Validates roles exist with correct count
- Validates permissions exist and are assigned to roles
- Checks role-permission relationships
- Exits with error if validation fails

### test-seed-idempotency-modular.php

Tests that seeds can be run multiple times without creating duplicates.

**Usage:**
```bash
# Get before counts
php test-seed-idempotency-modular.php integration-test-seeds.json

# After re-running seeds, check counts match
php test-seed-idempotency-modular.php integration-test-seeds.json after "$BEFORE_COUNTS"
```

**Features:**
- Counts all database records defined in validation
- Compares before/after counts
- Verifies no duplicates created on re-run

### test-paths.php

Tests API and frontend paths.

**Usage:**
```bash
php test-paths.php <config_file> [auth|unauth|both] [api|frontend|both]

# Test all paths
php test-paths.php integration-test-paths.json

# Test only authenticated API paths
php test-paths.php integration-test-paths.json auth api

# Test only unauthenticated paths
php test-paths.php integration-test-paths.json unauth
```

**Features:**
- Tests HTTP status codes
- Validates JSON responses
- Checks for redirects to login
- Supports skip flags for destructive operations
- Provides detailed pass/fail reporting

### take-screenshots-modular.js

Takes screenshots of frontend pages based on configuration.

**Usage:**
```bash
node take-screenshots-modular.js <config_file> [base_url] [username] [password]

# Use credentials from config file
node take-screenshots-modular.js integration-test-paths.json

# Override with custom credentials
node take-screenshots-modular.js integration-test-paths.json http://localhost:8080 admin admin123
```

**Features:**
- Reads paths from JSON configuration
- Automatically logs in with configured credentials
- Takes screenshots for all paths with `"screenshot": true`
- Uses `screenshot_name` from config for file naming
- Saves screenshots to `/tmp/screenshot_*.png`
- Full page screenshots by default
- Respects skip flags

**Screenshot Configuration Example:**
```json
{
  "authenticated": {
    "frontend": {
      "groups_list": {
        "path": "/crud6/groups",
        "description": "Groups list page",
        "screenshot": true,
        "screenshot_name": "groups_list"
      }
    }
  }
}
```

## Adapting for Your Sprinkle

### Step 1: Create Your Paths Configuration

Copy `integration-test-paths.json` and modify:

```json
{
  "paths": {
    "authenticated": {
      "api": {
        "your_model_list": {
          "method": "GET",
          "path": "/api/yoursprinkle/yourmodel",
          "description": "Get list of your model",
          "expected_status": 200,
          "validation": {
            "type": "json",
            "contains": ["rows"]
          }
        }
      },
      "frontend": {
        "your_model_list": {
          "path": "/yoursprinkle/yourmodel",
          "description": "Your model list page",
          "screenshot": true,
          "screenshot_name": "yourmodel_list"
        }
      }
    }
  }
}
```

### Step 2: Create Your Seeds Configuration

Copy `integration-test-seeds.json` and modify:

```json
{
  "seeds": {
    "account": { /* Keep Account seeds as-is */ },
    "yoursprinkle": {
      "description": "Your sprinkle seeds",
      "order": 2,
      "seeds": [
        {
          "class": "Your\\Sprinkle\\Database\\Seeds\\YourSeed",
          "description": "Your seed description",
          "required": true,
          "validation": {
            "type": "role",
            "slug": "your-role-slug",
            "expected_count": 1
          }
        }
      ]
    }
  }
}
```

### Step 3: Update GitHub Actions Workflow

Replace hardcoded paths and seeds with script calls:

```yaml
- name: Run seeds
  run: |
    cd userfrosting
    cp ../your-sprinkle/.github/config/integration-test-seeds.json .
    php run-seeds.php integration-test-seeds.json

- name: Validate seeds
  run: |
    cd userfrosting
    php check-seeds-modular.php integration-test-seeds.json

- name: Test paths
  run: |
    cd userfrosting
    cp ../your-sprinkle/.github/config/integration-test-paths.json .
    php test-paths.php integration-test-paths.json unauth api
```

## Validation Types

### Role Validation

```json
{
  "type": "role",
  "slug": "role-slug",
  "expected_count": 1
}
```

Validates that a role with the specified slug exists.

### Permission Validation

```json
{
  "type": "permissions",
  "slugs": ["perm1", "perm2"],
  "expected_count": 2,
  "role_assignments": {
    "role-slug": 2
  }
}
```

Validates:
- Permissions exist
- Correct count of permissions
- Permissions are assigned to specified roles

### JSON Response Validation

```json
{
  "type": "json",
  "contains": ["key1", "key2"]
}
```

Validates that API response is valid JSON and contains expected keys.

### Redirect Validation

```json
{
  "type": "redirect_to_login",
  "contains": ["/account/sign-in", "login"]
}
```

Validates that page redirects to login (for unauthenticated access).

## Benefits of Modular Testing

1. **Reusability** - Same scripts work for all sprinkles
2. **Maintainability** - Update configuration, not code
3. **Clarity** - JSON format is easy to read and modify
4. **Flexibility** - Add new tests without changing scripts
5. **Consistency** - Same testing approach across all sprinkles
6. **Documentation** - Configuration serves as documentation

## File Structure

```
.github/
├── config/
│   ├── integration-test-paths.json    # Path definitions
│   └── integration-test-seeds.json    # Seed definitions
├── scripts/
│   ├── run-seeds.php                  # Run seeds from config
│   ├── check-seeds-modular.php        # Validate seeds
│   ├── test-seed-idempotency-modular.php  # Test idempotency
│   ├── test-paths.php                 # Test API/frontend paths
│   ├── take-screenshots-modular.js    # Take screenshots from config
│   ├── check-seeds.php                # Original seed checker (kept for compatibility)
│   ├── test-seed-idempotency.php      # Original idempotency test
│   └── take-authenticated-screenshots.js  # Original screenshot script (kept for compatibility)
└── workflows/
    ├── integration-test.yml           # Main workflow
    └── integration-test.yml.backup    # Backup of original
```

## Migration from Hardcoded Tests

The original integration test workflow has been backed up to `integration-test.yml.backup`. The modular approach:

- Replaces hardcoded seed commands with `run-seeds.php`
- Replaces hardcoded validation with `check-seeds-modular.php`
- Replaces hardcoded path tests with `test-paths.php`
- Maintains all original functionality while being more flexible

## Examples

See the CRUD6 configuration files for complete examples:
- `.github/config/integration-test-paths.json`
- `.github/config/integration-test-seeds.json`

## Support

For questions or issues with the modular testing framework, please open an issue on the sprinkle-crud6 repository.
