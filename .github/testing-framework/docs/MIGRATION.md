# Migration Guide

Guide for migrating existing testing infrastructure to the UserFrosting 6 integration testing framework.

## Overview

This guide helps you migrate from:
- **Manual hardcoded tests** → Configuration-driven tests
- **Custom test scripts** → Reusable framework scripts
- **Duplicated code** → Shared, maintained framework

## Who Should Migrate?

You should consider migrating if you have:
- ✅ Custom integration test scripts in your sprinkle
- ✅ Hardcoded API/frontend test paths
- ✅ Database seed testing code
- ✅ Screenshot capture scripts
- ✅ GitHub Actions workflows with custom test logic

## Migration Benefits

| Before | After |
|--------|-------|
| Custom test scripts per sprinkle | Reusable scripts from framework |
| Hardcoded paths in code | JSON configuration files |
| Manual workflow updates | Copy and customize template |
| Different approaches per sprinkle | Consistent testing across all sprinkles |
| Maintain test code yourself | Framework updates benefit everyone |

## Migration Process

### Step 1: Analyze Current Testing Setup

Document your current tests:

```bash
# List your current test files
find .github -name "*.php" -o -name "*.js" -o -name "*.sh"

# Review your GitHub Actions workflow
cat .github/workflows/*.yml
```

**Questions to answer:**
1. What API endpoints are you testing?
2. What frontend pages need screenshots?
3. What database seeds do you run?
4. How do you validate seed data?
5. What's in your workflow that's sprinkle-specific?

### Step 2: Install the Framework

Install the framework into your sprinkle:

```bash
# Quick install
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name

# Or manual install
mkdir -p .github/config .github/scripts
# ... copy files as per installation guide
```

See [INSTALLATION.md](INSTALLATION.md) for detailed instructions.

### Step 3: Migrate Path Tests

#### Before: Hardcoded Path Tests

**Old approach (custom script):**
```php
// test-api.php
$paths = [
    '/api/myapp/products',
    '/api/myapp/categories',
    '/api/myapp/orders'
];

foreach ($paths as $path) {
    $response = $client->get($path);
    assert($response->getStatusCode() === 200);
}
```

#### After: Configuration-Driven

**New approach (JSON config):**
```json
{
  "paths": {
    "authenticated": {
      "api": {
        "products_list": {
          "method": "GET",
          "path": "/api/myapp/products",
          "expected_status": 200,
          "validation": {
            "type": "json",
            "contains": ["rows"]
          }
        },
        "categories_list": {
          "method": "GET",
          "path": "/api/myapp/categories",
          "expected_status": 200
        },
        "orders_list": {
          "method": "GET",
          "path": "/api/myapp/orders",
          "expected_status": 200
        }
      }
    }
  }
}
```

**Run with:**
```bash
php .github/scripts/test-paths.php .github/config/integration-test-paths.json
```

### Step 4: Migrate Seed Tests

#### Before: Custom Seed Validation

**Old approach:**
```php
// validate-seeds.php
$role = Role::where('slug', 'myapp-admin')->first();
if (!$role) {
    throw new Exception("Role not found");
}

$permissions = Permission::whereIn('slug', ['perm1', 'perm2'])->count();
if ($permissions !== 2) {
    throw new Exception("Wrong permission count");
}
```

#### After: Configuration-Driven

**New approach:**
```json
{
  "seeds": {
    "myapp": {
      "seeds": [
        {
          "class": "MyApp\\Database\\Seeds\\DefaultRoles",
          "validation": {
            "type": "role",
            "slug": "myapp-admin",
            "expected_count": 1
          }
        },
        {
          "class": "MyApp\\Database\\Seeds\\DefaultPermissions",
          "validation": {
            "type": "permissions",
            "slugs": ["perm1", "perm2"],
            "expected_count": 2
          }
        }
      ]
    }
  }
}
```

**Run with:**
```bash
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json
php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json
```

### Step 5: Migrate Screenshot Tests

#### Before: Custom Screenshot Script

**Old approach:**
```javascript
// take-screenshots.js
const playwright = require('playwright');

(async () => {
    const browser = await playwright.chromium.launch();
    const page = await browser.newPage();
    
    // Login
    await page.goto('http://localhost:8080/account/sign-in');
    await page.fill('#username', 'admin');
    await page.fill('#password', 'admin123');
    await page.click('button[type=submit]');
    
    // Screenshot 1
    await page.goto('http://localhost:8080/myapp/products');
    await page.screenshot({ path: 'products.png' });
    
    // Screenshot 2
    await page.goto('http://localhost:8080/myapp/categories');
    await page.screenshot({ path: 'categories.png' });
    
    await browser.close();
})();
```

#### After: Configuration-Driven

**New approach:**
```json
{
  "paths": {
    "authenticated": {
      "frontend": {
        "products_page": {
          "path": "/myapp/products",
          "screenshot": true,
          "screenshot_name": "products"
        },
        "categories_page": {
          "path": "/myapp/categories",
          "screenshot": true,
          "screenshot_name": "categories"
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

**Run with:**
```bash
node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json
```

### Step 6: Migrate GitHub Actions Workflow

#### Before: Custom Workflow

**Old approach:**
```yaml
- name: Run custom tests
  run: |
    php scripts/test-api.php
    php scripts/validate-seeds.php
    node scripts/take-screenshots.js
```

#### After: Framework Workflow

**New approach:**
```yaml
- name: Run integration tests
  run: |
    cd userfrosting
    # Copy config and scripts
    cp ../myapp/.github/config/integration-test-seeds.json .
    cp ../myapp/.github/config/integration-test-paths.json .
    cp ../myapp/.github/scripts/*.php .
    cp ../myapp/.github/scripts/*.js .
    
    # Run tests using framework
    php run-seeds.php integration-test-seeds.json
    php check-seeds-modular.php integration-test-seeds.json
    php test-paths.php integration-test-paths.json
    node take-screenshots-modular.js integration-test-paths.json
```

See [WORKFLOW_EXAMPLE.md](WORKFLOW_EXAMPLE.md) for complete examples.

### Step 7: Validate Migration

Compare results before and after:

```bash
# Before migration
./old-scripts/run-tests.sh > old-results.txt

# After migration
php .github/scripts/test-paths.php .github/config/integration-test-paths.json > new-results.txt

# Compare
diff old-results.txt new-results.txt
```

### Step 8: Clean Up Old Code

Once validated, remove old test infrastructure:

```bash
# Backup old tests
mv scripts/test-*.php scripts/old/

# Or remove entirely
rm scripts/test-api.php
rm scripts/validate-seeds.php
rm scripts/take-screenshots.js
```

## Real-World Migration Example

### sprinkle-c6admin Migration

Here's how sprinkle-c6admin would migrate:

**Current state:**
```
sprinkle-c6admin/
├── .github/
│   └── workflows/
│       └── integration-test.yml  (custom workflow)
└── scripts/
    ├── test-admin-api.php        (custom API tests)
    ├── validate-admin-seeds.php  (custom seed validation)
    └── screenshot-admin.js       (custom screenshots)
```

**Migration steps:**

1. **Install framework:**
```bash
cd sprinkle-c6admin
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- c6admin
```

2. **Convert API tests to JSON:**
```json
{
  "paths": {
    "authenticated": {
      "api": {
        "admin_dashboard": {
          "method": "GET",
          "path": "/api/c6admin/dashboard",
          "expected_status": 200
        }
      }
    }
  }
}
```

3. **Convert seed validation to JSON:**
```json
{
  "seeds": {
    "c6admin": {
      "seeds": [
        {
          "class": "C6Admin\\Database\\Seeds\\AdminRoles",
          "validation": {
            "type": "role",
            "slug": "c6admin-super"
          }
        }
      ]
    }
  }
}
```

4. **Update workflow:**
Replace custom test commands with framework commands.

5. **Test and validate:**
```bash
# Test locally
php .github/scripts/test-paths.php .github/config/integration-test-paths.json

# Run full workflow
git push origin feature/migrate-to-framework
# Review GitHub Actions results
```

6. **Remove old code:**
```bash
rm scripts/test-admin-api.php
rm scripts/validate-admin-seeds.php
rm scripts/screenshot-admin.js
```

**After migration:**
```
sprinkle-c6admin/
├── .github/
│   ├── config/
│   │   ├── integration-test-paths.json    (new)
│   │   └── integration-test-seeds.json    (new)
│   ├── scripts/
│   │   ├── run-seeds.php                  (new, from framework)
│   │   ├── check-seeds-modular.php        (new, from framework)
│   │   ├── test-paths.php                 (new, from framework)
│   │   └── take-screenshots-modular.js    (new, from framework)
│   └── workflows/
│       └── integration-test.yml           (updated to use framework)
└── TESTING_FRAMEWORK.md                   (new, documentation)
```

## Migration Checklist

Use this checklist to track your migration progress:

- [ ] **Analysis Phase**
  - [ ] Document current API endpoints
  - [ ] Document current frontend pages
  - [ ] Document current database seeds
  - [ ] Document current validation logic
  - [ ] Review current GitHub Actions workflow

- [ ] **Installation Phase**
  - [ ] Install testing framework
  - [ ] Verify installation succeeded
  - [ ] Review generated configuration files

- [ ] **Configuration Phase**
  - [ ] Migrate API endpoint tests to JSON
  - [ ] Migrate frontend page tests to JSON
  - [ ] Migrate seed definitions to JSON
  - [ ] Migrate validation rules to JSON
  - [ ] Configure authentication credentials

- [ ] **Validation Phase**
  - [ ] Test seed running locally
  - [ ] Test seed validation locally
  - [ ] Test path testing locally
  - [ ] Test screenshot capture locally
  - [ ] Compare results with old tests

- [ ] **Workflow Migration Phase**
  - [ ] Update GitHub Actions workflow
  - [ ] Test workflow in CI/CD
  - [ ] Verify artifacts are uploaded
  - [ ] Check logs and output

- [ ] **Cleanup Phase**
  - [ ] Archive old test scripts
  - [ ] Remove unused dependencies
  - [ ] Update documentation
  - [ ] Train team on new approach

- [ ] **Documentation Phase**
  - [ ] Document custom configurations
  - [ ] Add team-specific notes
  - [ ] Update README references
  - [ ] Create runbook for common tasks

## Common Migration Challenges

### Challenge 1: Complex API Tests

**Problem:** Your API tests include complex request bodies, headers, or authentication.

**Solution:** Use the `skip` flag for complex tests and keep them as custom PHPUnit tests:

```json
{
  "products_create": {
    "method": "POST",
    "path": "/api/myapp/products",
    "skip": true,
    "skip_reason": "Requires complex request body - tested in PHPUnit"
  }
}
```

### Challenge 2: Custom Validation Logic

**Problem:** Your seed validation has complex business logic.

**Solution:** Use framework for basic validation, keep custom validation as separate script:

```bash
# Run framework validation
php .github/scripts/check-seeds-modular.php config.json

# Run custom validation
php scripts/custom-validation.php
```

### Challenge 3: Multiple Environments

**Problem:** You test against dev, staging, and production.

**Solution:** Use environment-specific config files:

```bash
# Development
php test-paths.php config-dev.json

# Staging
php test-paths.php config-staging.json

# Production
php test-paths.php config-prod.json
```

### Challenge 4: Existing Test Coverage

**Problem:** You have good test coverage and don't want to lose it during migration.

**Solution:** Gradual migration - run both old and new tests during transition:

```yaml
- name: Run old tests
  run: ./scripts/old-tests.sh

- name: Run new tests
  run: |
    php .github/scripts/test-paths.php .github/config/integration-test-paths.json
    # Compare results
```

## Post-Migration Maintenance

After migration:

1. **Update Framework**
   ```bash
   # Re-run installer to get latest scripts
   curl -sSL ... | bash -s -- your-sprinkle-name
   ```

2. **Add New Tests**
   - Just edit JSON configuration files
   - No code changes needed

3. **Share Improvements**
   - Framework benefits all sprinkles
   - Contribute improvements back to CRUD6

## Getting Help

If you encounter issues during migration:

1. Check [troubleshooting section](#troubleshooting) in this guide
2. Review [API Reference](API_REFERENCE.md) for script usage
3. See [Configuration Guide](CONFIGURATION.md) for config options
4. Open an issue on [GitHub](https://github.com/ssnukala/sprinkle-crud6/issues)

## Troubleshooting

### Tests Not Running

**Symptom:** Framework scripts don't execute

**Solution:**
```bash
# Make scripts executable
chmod +x .github/scripts/*.php

# Check PHP version
php --version  # Should be 8.1+
```

### Configuration Not Found

**Symptom:** "Configuration file not found" error

**Solution:**
```bash
# Check file exists
ls -la .github/config/integration-test-paths.json

# Verify path in command
php .github/scripts/test-paths.php .github/config/integration-test-paths.json
```

### Different Results

**Symptom:** Framework tests show different results than old tests

**Solution:**
1. Compare test coverage (are you testing same things?)
2. Check validation rules (are they equivalent?)
3. Review authentication (using same credentials?)
4. Verify environment (same server/database?)

### Workflow Failures

**Symptom:** GitHub Actions workflow fails after migration

**Solution:**
```bash
# Test locally first
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json

# Check workflow syntax
yamllint .github/workflows/integration-test.yml

# Review GitHub Actions logs for specific error
```

## Success Metrics

Your migration is successful when:

- ✅ All old test scenarios are covered by new configuration
- ✅ Framework tests pass with same reliability as old tests
- ✅ GitHub Actions workflow runs successfully
- ✅ Screenshots are captured correctly
- ✅ Seed validation works as expected
- ✅ Team understands how to add new tests (edit JSON)
- ✅ Old test code is archived or removed

## Next Steps

After successful migration:

1. Update team documentation
2. Train team on JSON configuration approach
3. Remove old test scripts
4. Contribute improvements to framework
5. Help other sprinkles migrate

---

**Migration Support:** Open an issue on [GitHub](https://github.com/ssnukala/sprinkle-crud6/issues) if you need help migrating your sprinkle.
