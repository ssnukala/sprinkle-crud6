# Using CRUD6 Integration Test Reusable Workflow

**Created:** January 15, 2026  
**Workflow:** `.github/workflows/crud6-integration-reusable.yml`  
**Status:** Ready to use

## Overview

The CRUD6 Integration Test Reusable Workflow provides complete integration testing for any sprinkle that uses CRUD6. All 11 stages and every step from the original integration-test.yml are maintained exactly, ensuring complete test coverage.

## Quick Start

### Basic Usage

Create a workflow file in your sprinkle's `.github/workflows/` directory:

```yaml
# .github/workflows/crud6-test.yml
name: CRUD6 Integration Test

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:

jobs:
  crud6-integration:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: vendor/my-sprinkle
      npm-package: "@vendor/my-sprinkle"
    secrets:
      COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

That's it! The workflow will:
- Install all dependencies
- Set up UserFrosting 6
- Configure your sprinkle
- Run complete integration tests
- Generate screenshots
- Upload artifacts

## Input Parameters

### Required Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `sprinkle-name` | string | Sprinkle directory name | `my-sprinkle` |
| `composer-package` | string | Composer package name | `vendor/my-sprinkle` |
| `npm-package` | string | NPM package name | `@vendor/my-sprinkle` |

### Optional Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `php-version` | string | `"8.4"` | PHP version to use |
| `node-version` | string | `"20"` | Node.js version (>= 18) |
| `mysql-version` | string | `"8.0"` | MySQL version |
| `userfrosting-version` | string | `"^6.0-beta"` | UserFrosting constraint |
| `schema-path` | string | `"examples/schema"` | Path to schema files |
| `framework-repo` | string | `"ssnukala/sprinkle-crud6"` | Testing framework repo |
| `framework-branch` | string | `"main"` | Framework branch to use |
| `vite-optimize-deps` | string | `"limax,lodash.deburr"` | Vite deps to optimize |

### Secrets

| Secret | Required | Description |
|--------|----------|-------------|
| `COMPOSER_AUTH` | No | Authentication token for private packages |

## Complete Example

### With All Options

```yaml
name: Complete CRUD6 Integration Test

on:
  push:
    branches: [main, develop, feature/*]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:
  schedule:
    - cron: '0 0 * * 0'  # Weekly on Sunday

jobs:
  crud6-integration:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      # Required
      sprinkle-name: my-sprinkle
      composer-package: myvendor/my-sprinkle
      npm-package: "@myvendor/my-sprinkle"
      
      # Versions
      php-version: "8.4"
      node-version: "20"
      mysql-version: "8.0"
      userfrosting-version: "^6.0-beta"
      
      # Configuration
      schema-path: "app/schema/crud6"
      vite-optimize-deps: "limax,lodash.deburr,my-custom-dep"
      
      # Framework
      framework-repo: "ssnukala/sprinkle-crud6"
      framework-branch: "main"
    
    secrets:
      COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

## Version Pinning

### Pin to Specific Version

```yaml
jobs:
  crud6-integration:
    # Pin to tag
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@v1.0.0
    
    # Or pin to commit
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@abc1234
    
    # Or use latest
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
```

**Recommendations:**
- **Development:** Use `@main` for latest features
- **Production:** Pin to specific tag for stability
- **Testing:** Use commit hash for exact reproducibility

## What Gets Tested

### 11 Testing Stages (All Maintained)

1. **Environment Setup**
   - PHP, Node.js, MySQL
   - Testing framework installation

2. **UserFrosting Project Creation**
   - Fresh UF6 installation
   - Directory structure

3. **Dependency Configuration**
   - Composer dependencies
   - NPM packages

4. **Application Configuration**
   - MyApp.php (sprinkle registration)
   - main.ts (Vue plugin)
   - Routes (frontend routing)
   - Vite config (CommonJS deps)

5. **Database Setup**
   - Migrations
   - Admin user creation
   - Schema files
   - Locale messages

6. **Schema-Driven Testing**
   - DDL generation from JSON schemas
   - Table creation
   - Seed data generation and loading
   - Idempotency testing

7. **Frontend Build**
   - Bakery bake
   - Asset compilation

8. **Server Startup**
   - PHP development server
   - Vite dev server

9. **Integration Testing**
   - Unauthenticated API tests
   - Unauthenticated frontend tests
   - Playwright installation
   - Authenticated unified tests

10. **Test Results**
    - Summary generation
    - Screenshot capture

11. **Artifact Collection**
    - Screenshots upload
    - Logs upload
    - Diagnostics upload
    - Server cleanup

## Artifacts Generated

After the workflow completes, these artifacts are available:

| Artifact | Contains | Retention |
|----------|----------|-----------|
| `integration-test-screenshots-{sprinkle}` | All captured screenshots | 7 days |
| `login-diagnostics-{sprinkle}` | Login page diagnostics | 7 days |
| `integration-test-logs-{sprinkle}` | Application logs | 7 days |
| `api-test-log-{sprinkle}` | API test results (JSON) | 7 days |

## Custom Schema Path

If your schemas are in a different location:

```yaml
jobs:
  crud6-integration:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: vendor/my-sprinkle
      npm-package: "@vendor/my-sprinkle"
      schema-path: "app/schema/custom"  # Custom location
```

## Multiple Vite Dependencies

If your sprinkle needs additional Vite optimizations:

```yaml
jobs:
  crud6-integration:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: vendor/my-sprinkle
      npm-package: "@vendor/my-sprinkle"
      vite-optimize-deps: "limax,lodash.deburr,my-dep,another-dep"
```

## Using Different PHP/Node Versions

```yaml
jobs:
  test-matrix:
    strategy:
      matrix:
        php: ["8.3", "8.4", "8.5"]
        node: ["18", "20"]
    
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: vendor/my-sprinkle
      npm-package: "@vendor/my-sprinkle"
      php-version: ${{ matrix.php }}
      node-version: ${{ matrix.node }}
```

## Troubleshooting

### Workflow Not Found

**Error:** `Unable to resolve action ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main`

**Solutions:**
- Ensure you're using the correct repository path
- Verify the workflow file exists at that path
- Check if the branch/tag exists
- Ensure the repository is public or you have access

### Authentication Issues

**Error:** `Composer authentication failed`

**Solutions:**
```yaml
secrets:
  COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

Set the `COMPOSER_AUTH` secret in your repository settings:
```json
{
  "github-oauth": {
    "github.com": "your_token_here"
  }
}
```

### Schema Files Not Found

**Error:** `Schema directory not found`

**Solutions:**
- Verify `schema-path` parameter points to correct location
- Ensure schemas exist in your sprinkle
- Check file names match expected format (users.json, groups.json, etc.)

### NPM Package Issues

**Error:** `NPM package files not found`

**Solutions:**
- Ensure your sprinkle has a `package.json`
- Verify `exports` field includes required paths
- Check that `npm pack` works locally

## Comparison with Copy-Based Approach

### Reusable Workflow (This Approach)

**Pros:**
- ✅ No framework copying needed
- ✅ Always up-to-date
- ✅ Tiny workflow file (10-30 lines)
- ✅ Centralized maintenance
- ✅ Version pinning available

**Cons:**
- ❌ Requires internet access to CRUD6 repo
- ❌ Less customizable
- ❌ Debug harder (workflow in another repo)

### Copy-Based Framework

**Pros:**
- ✅ Full customization
- ✅ Works offline
- ✅ Debug locally
- ✅ Pin specific framework version

**Cons:**
- ❌ Framework can get out of sync
- ❌ Large workflow files
- ❌ Manual updates needed
- ❌ Framework code duplicated

## When to Use Which

### Use Reusable Workflow When:
- You want latest CRUD6 testing features
- You don't need workflow customization
- You want simple maintenance
- Your CI has internet access

### Use Copy-Based Framework When:
- You need heavy customization
- You work in air-gapped environment
- You want to debug framework locally
- You need specific framework version

## Migration from Copy-Based

### Step 1: Backup Current Workflow

```bash
cp .github/workflows/integration-test.yml .github/workflows/integration-test.yml.backup
```

### Step 2: Create New Workflow

Create `.github/workflows/crud6-test.yml` with reusable workflow call (see Quick Start above)

### Step 3: Test in Parallel

Run both workflows to compare results:
- Keep old workflow enabled
- Add new workflow
- Compare artifacts and results

### Step 4: Switch Over

Once confident:
```bash
rm .github/workflows/integration-test.yml.backup
# Optionally remove old workflow
```

## Support

### Issues

If you encounter issues with the reusable workflow:

1. Check workflow run logs
2. Download artifacts for diagnostics
3. Open issue at: https://github.com/ssnukala/sprinkle-crud6/issues
4. Include:
   - Workflow file
   - Error messages
   - Artifact logs

### Updates

Watch for updates:
- GitHub releases: https://github.com/ssnukala/sprinkle-crud6/releases
- Pin to stable tags for production
- Use `@main` for development

## Examples

### Example 1: Basic Sprinkle

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  crud6:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: acme/my-sprinkle
      npm-package: "@acme/my-sprinkle"
```

### Example 2: Custom Schema Location

```yaml
jobs:
  crud6:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: acme/my-sprinkle
      npm-package: "@acme/my-sprinkle"
      schema-path: "config/schemas"
```

### Example 3: Multiple Versions

```yaml
jobs:
  test:
    strategy:
      matrix:
        php: ["8.3", "8.4"]
    
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: acme/my-sprinkle
      npm-package: "@acme/my-sprinkle"
      php-version: ${{ matrix.php }}
```

## Conclusion

The CRUD6 Integration Test Reusable Workflow provides:
- ✅ Complete 11-stage integration testing
- ✅ All steps from original workflow maintained
- ✅ Simple setup (3 required parameters)
- ✅ Automatic updates
- ✅ Version pinning
- ✅ Comprehensive artifacts

**Perfect for:** Any sprinkle using CRUD6 that wants robust, maintainable integration testing.
