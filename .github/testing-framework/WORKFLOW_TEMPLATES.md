# Workflow Templates

This directory contains two ready-to-copy GitHub Actions workflow templates for UserFrosting 6 sprinkles.

## Available Templates

### 1. CRUD6 Integration Test (`crud6-workflow-template.yml`)

**Purpose:** Test CRUD6 functionality in your sprinkle

**Use this when:**
- Your sprinkle uses/depends on CRUD6
- You want to verify CRUD6 features work correctly
- You want separate workflows for CRUD6 vs custom features

**What it does:**
- ✅ Auto-installs CRUD6 testing framework
- ✅ Uses CRUD6's own test configuration
- ✅ Tests standard CRUD6 models (users, groups, roles, permissions, activities)
- ✅ Validates CRUD6 seeds and permissions
- ✅ Captures screenshots of CRUD6 pages
- ✅ Runs independently from your custom workflows

**Setup:**
1. Copy `crud6-workflow-template.yml` to `.github/workflows/crud6-integration-test.yml`
2. Replace these placeholders:
   - `YOUR_SPRINKLE_NAME` → your sprinkle directory (e.g., `c6admin`)
   - `YOUR_COMPOSER_PACKAGE` → your composer package (e.g., `yourvendor/c6admin`)
   - `YOUR_NPM_PACKAGE` → your npm package (e.g., `@yourvendor/c6admin`)
3. Commit and push

**Framework installation:**
- First run: Downloads framework from sprinkle-crud6 automatically
- Subsequent runs: Uses downloaded framework from `.github/crud6-framework/`
- Optional: Commit `.github/crud6-framework/` for offline testing

**Example for c6admin:**
```yaml
env:
  SPRINKLE_DIR: c6admin
  COMPOSER_PACKAGE: ssnukala/sprinkle-c6admin
  NPM_PACKAGE: @ssnukala/sprinkle-c6admin
```

---

### 2. Full Integration Test (`workflow-template.yml`)

**Purpose:** Customize complete integration testing for your sprinkle

**Use this when:**
- You want full control over what's tested
- You need custom seed configurations
- You have sprinkle-specific test scenarios
- You want to test both CRUD6 and custom features together

**What it does:**
- ✅ Auto-installs testing framework if not present
- ✅ Uses local framework version if already installed
- ✅ Allows custom test configuration
- ✅ Tests your sprinkle's specific features
- ✅ Fully customizable workflow steps

**Setup:**
1. Copy `workflow-template.yml` to `.github/workflows/integration-test.yml`
2. Replace ALL UPPERCASE placeholders:
   - `YOUR_SPRINKLE_NAME` → directory name
   - `YOUR_VENDOR` → your vendor/org name
   - `YOUR_COMPOSER_PACKAGE` → composer package name
   - `YOUR_NPM_PACKAGE` → npm package name
   - `YOUR_NAMESPACE` → PHP namespace
   - `YOUR_SPRINKLE_CLASS` → main sprinkle class name
3. Customize frontend route configuration (uncomment pattern that matches yours)
4. Customize test configurations in `.github/config/`

**Frontend patterns:**
Choose the pattern that matches your sprinkle:
- **Pattern 1** (Simple): Like CRUD6 - `...CRUD6Routes`
- **Pattern 2** (Factory): Like C6Admin - `createC6AdminRoutes({ layoutComponent })`
- **Pattern 3** (Nested): Custom parent component with children

See [FRONTEND_INTEGRATION_PATTERNS.md](../docs/FRONTEND_INTEGRATION_PATTERNS.md) for details.

**Framework installation:**
- First run: Downloads and installs framework
- Subsequent runs: Uses local `.github/scripts/` and `.github/config/`
- Recommended: Commit config files for version control

---

## Comparison

| Feature | CRUD6 Template | Full Template |
|---------|---------------|---------------|
| **Purpose** | Test CRUD6 only | Test everything |
| **Configuration** | Uses CRUD6 defaults | Custom configs |
| **Setup Time** | 5 minutes | 10-15 minutes |
| **Customization** | Minimal | Full control |
| **Best For** | CRUD6 verification | Complete testing |
| **Can Coexist** | Yes | Yes |

## Recommended Approach

**For sprinkles that use CRUD6:**

1. **Use CRUD6 Template** for CRUD6 testing
   ```
   .github/workflows/crud6-integration-test.yml
   ```
   - Quick setup
   - Tests CRUD6 functionality
   - Separate from custom features

2. **Create Custom Workflows** for your features
   ```
   .github/workflows/custom-features-test.yml
   .github/workflows/custom-api-test.yml
   ```
   - Test your specific features
   - Use your own test tools
   - Independent from CRUD6 tests

**For sprinkles with complex testing needs:**

Use the Full Template and customize extensively for your specific requirements.

---

## Step-by-Step: Using CRUD6 Template

### For c6admin sprinkle:

```bash
# 1. Copy the CRUD6 workflow template
cp /path/to/crud6/.github/testing-framework/crud6-workflow-template.yml \
   .github/workflows/crud6-integration-test.yml

# 2. Edit the file
nano .github/workflows/crud6-integration-test.yml

# 3. Replace placeholders:
#    YOUR_SPRINKLE_NAME → c6admin
#    YOUR_COMPOSER_PACKAGE → ssnukala/sprinkle-c6admin
#    YOUR_NPM_PACKAGE → @ssnukala/sprinkle-c6admin

# 4. Commit and push
git add .github/workflows/crud6-integration-test.yml
git commit -m "Add CRUD6 integration test workflow"
git push

# 5. Check GitHub Actions tab for results
```

That's it! The workflow will:
- Auto-install the CRUD6 testing framework
- Download CRUD6 test configs
- Run all CRUD6 tests
- Upload screenshots and logs as artifacts

### Optional: Commit Framework Locally

To use the framework offline or customize it:

```bash
# After first workflow run, the framework is in .github/crud6-framework/
# Commit it to your repository:
git add .github/crud6-framework/
git commit -m "Add CRUD6 testing framework locally"
git push
```

Benefits:
- Works offline
- Can customize configs
- Faster CI (no download)
- Version controlled

---

## Step-by-Step: Using Full Template

### For a custom sprinkle:

```bash
# 1. Install framework locally first
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- myapp

# 2. Copy the workflow template
cp /path/to/crud6/.github/testing-framework/workflow-template.yml \
   .github/workflows/integration-test.yml

# 3. Edit all placeholders in the workflow file

# 4. Customize test configurations
nano .github/config/integration-test-paths.json
nano .github/config/integration-test-seeds.json

# 5. Commit everything
git add .github/
git commit -m "Add integration testing framework and workflow"
git push
```

---

## Troubleshooting

### CRUD6 Template Issues

**Q: Framework download fails**
```yaml
# Solution: Use local framework instead
# Before running workflow, install locally:
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle
git add .github/crud6-framework/
git commit -m "Add CRUD6 framework locally"
```

**Q: CRUD6 configs not found**
```yaml
# Solution: Copy CRUD6 configs manually
mkdir -p .github/config
curl -o .github/config/integration-test-paths.json \
  https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/config/integration-test-paths.json
curl -o .github/config/integration-test-seeds.json \
  https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/config/integration-test-seeds.json
```

### Full Template Issues

**Q: Wrong frontend pattern**
```yaml
# Solution: Check docs and use correct pattern
# See FRONTEND_INTEGRATION_PATTERNS.md
# Uncomment the pattern section that matches your sprinkle
```

**Q: Tests fail with wrong namespace**
```yaml
# Solution: Check namespace in composer.json
# Update YOUR_NAMESPACE in workflow to match
```

---

## Examples

### Real-World Usage

**sprinkle-crud6** (using CRUD6 template):
- Has own integration test for CRUD6 features
- Uses default CRUD6 configs
- Tests standard CRUD6 models

**sprinkle-c6admin** (can use CRUD6 template):
```yaml
env:
  SPRINKLE_DIR: c6admin
  COMPOSER_PACKAGE: ssnukala/sprinkle-c6admin
  NPM_PACKAGE: @ssnukala/sprinkle-c6admin
```
Plus separate workflows for c6admin-specific features.

---

## Need Help?

1. Check [Framework Documentation](../docs/)
2. See [FRONTEND_INTEGRATION_PATTERNS.md](../docs/FRONTEND_INTEGRATION_PATTERNS.md)
3. Review [CRUD6's own workflow](../../workflows/integration-test.yml)
4. Open issue on [GitHub](https://github.com/ssnukala/sprinkle-crud6/issues)

---

## Summary

- **CRUD6 Template**: Quick, focused CRUD6 testing - 5 minute setup
- **Full Template**: Complete control, custom everything - 15 minute setup
- **Both**: Can coexist in the same repository
- **Recommendation**: Start with CRUD6 template, add custom workflows as needed
