# Beta.8 Reusable Workflows Analysis

**Date:** January 15, 2026  
**Context:** UserFrosting 6.0.0-beta.8 changelog mentions "Convert GitHub Actions workflow files to reusable templates"  
**Previous Issue:** PR #366 broke integration testing by removing critical steps  

## Executive Summary

**RECOMMENDATION: DO NOT convert CRUD6's integration-test.yml to reusable workflows at this time.**

**Reasons:**
1. Beta.8's "reusable templates" are actually **template files** (disabled, for manual copying), NOT reusable workflows
2. CRUD6's integration test is highly specialized for sprinkle testing (not a standard app)
3. The workflow is auto-generated from config (already has abstraction)
4. PR #366 showed that workflow changes are high-risk for CRUD6
5. No actual compatibility requirement from beta.8

## What Beta.8 Actually Did

### Investigation of Beta.8's "Reusable Templates"

Examined UserFrosting 6.0.0-beta.8 `.github/workflows/`:
- **Files:** `PHPUnit.yml`, `PHPStan.yml`
- **Purpose:** Template workflows (disabled by default)
- **Status:** `on:` section is commented out
- **Usage:** Meant to be copied and customized by users

```yaml
# Beta.8 PHPUnit.yml header:
name: PHPUnit

on:
#   push:
#     branches: ['*']
#   pull_request:
#     branches: ['*']
  workflow_dispatch:
```

**Key Finding:** These are **NOT** reusable workflows using `workflow_call`. They are disabled template files for users to copy and enable.

### What "Reusable Workflow" Would Mean

True GitHub Actions reusable workflows use `workflow_call`:

```yaml
# Example of ACTUAL reusable workflow (NOT what beta.8 did)
on:
  workflow_call:
    inputs:
      php-version:
        required: true
        type: string
```

Then called from another workflow:
```yaml
jobs:
  call-reusable:
    uses: org/repo/.github/workflows/reusable.yml@main
    with:
      php-version: '8.4'
```

**Beta.8 does NOT use this pattern.** It's just providing disabled template files.

## CRUD6 Integration Test Workflow Analysis

### Current Workflow Characteristics

**File:** `.github/workflows/integration-test.yml`  
**Lines:** 826 lines  
**Complexity:** High - specialized for sprinkle integration testing  
**Status:** Auto-generated from `integration-test-config.json`

### Unique Features (Not in Standard UF Workflows)

1. **Sprinkle-Specific Setup** (lines 43-86)
   - Clones sprinkle separately
   - Installs custom testing framework
   - Handles framework installation from git or local

2. **UserFrosting Project Creation** (lines 88-96)
   - Creates UF project as separate entity
   - Sprinkle installed as dependency into UF

3. **Complex Dependency Management** (lines 98-136)
   - Composer path repositories for local dev
   - NPM package packing and local installation
   - Handles both git-cloned and local sprinkle

4. **Code Injection** (lines 138-173)
   - Modifies MyApp.php to register sprinkle
   - Modifies main.ts to add Vue plugin
   - Modifies router/index.ts to add routes
   - These are CRITICAL for sprinkle integration

5. **Schema File Management** (lines 314-486)
   - Copies schema files from examples
   - Merges locale messages
   - Validates schema presence
   - Custom PHP merge script for messages

6. **DDL Generation** (lines 488-531)
   - Generates CREATE TABLE statements from JSON schemas
   - Loads SQL programmatically
   - Schema-driven table creation (unique to CRUD6)

7. **Custom Seeding** (lines 533-549)
   - PHP seed scripts
   - Validation of seed data
   - Idempotency testing
   - Uses custom framework scripts

8. **Dual Server Setup** (lines 558-589)
   - PHP development server (bakery serve)
   - Vite development server (bakery assets:vite)
   - Coordination between servers

9. **Multi-Stage Testing** (lines 591-653)
   - Unauthenticated API tests
   - Unauthenticated frontend tests
   - Authenticated unified tests (login + API + frontend)
   - Custom PHP and Node.js test scripts

10. **Playwright Integration** (lines 621-780)
    - Install Playwright
    - Login automation
    - Screenshot capture
    - Authentication state management

11. **Comprehensive Artifacts** (lines 782-815)
    - Screenshots
    - Login diagnostics
    - Application logs
    - API test logs

### What Makes This Workflow Non-Reusable

| Feature | CRUD6 Specific? | Why Not Generic? |
|---------|----------------|------------------|
| Testing Framework Installation | ✅ Yes | CRUD6-specific framework with DDL generation, seeding, path testing |
| Schema File Copying | ✅ Yes | CRUD6 examples/schema → app/schema/crud6 |
| Locale Message Merging | ✅ Yes | Custom PHP script to merge translation files |
| DDL Generation from Schemas | ✅ Yes | JSON schema → SQL CREATE TABLE (unique to CRUD6) |
| Code Injection (MyApp.php, main.ts) | ✅ Yes | Registers CRUD6 sprinkle in host application |
| Custom Seeding Scripts | ✅ Yes | CRUD6 testing framework scripts |
| Path-based Testing | ✅ Yes | Tests CRUD6 API endpoints and views |
| Vite CommonJS Config | ✅ Yes | Limax dependency specific to CRUD6 |

**Conclusion:** 90%+ of the workflow is CRUD6-specific and cannot be abstracted.

## Comparison: Beta.8 vs CRUD6

### Beta.8 PHPUnit Workflow
- **Purpose:** Run PHPUnit tests on UserFrosting app
- **Scope:** Test app with multiple databases and PHP versions
- **Complexity:** Medium - matrix testing
- **Lines:** ~314 lines
- **Reusability:** Template for copying

### CRUD6 Integration Test
- **Purpose:** Test sprinkle integration into UF app
- **Scope:** Full integration - backend + frontend + schemas + API
- **Complexity:** Very High - 11 major stages
- **Lines:** ~826 lines
- **Reusability:** Not suitable (too specialized)

## PR #366 Failure Analysis

### What PR #366 Did Wrong

Based on git history, PR #366 was reverted because it:
1. **Deleted critical steps** from integration-test.yml
2. **Broke sprinkle installation process**
3. **Failed integration tests**
4. **Attempted to "simplify" the workflow** without understanding CRUD6's needs

### Why the Revert Happened

The integration test workflow is:
- Carefully crafted over many iterations
- Each step has a specific purpose
- Dependencies between steps are critical
- Changes require extensive testing

**Lesson:** Don't simplify what appears complex without full understanding.

## Auto-Generation System

### Current Architecture

CRUD6 already has abstraction:
```
integration-test-config.json
         ↓
generate-workflow.js
         ↓
integration-test.yml (auto-generated)
```

**Comment in workflow (line 3):**
```yaml
# AUTO-GENERATED from integration-test-config.json
# To regenerate: node .github/testing-framework/scripts/generate-workflow.js
```

### Benefits of Current System

1. ✅ Configuration is centralized in JSON
2. ✅ Workflow can be regenerated from config
3. ✅ Comments indicate auto-generation
4. ✅ Generator script handles complexity

### Why This is Better Than "Reusable Workflows"

- Reusable workflows have input limitations (strings, booleans, numbers)
- CRUD6 needs complex data structures (schemas, paths, seeds)
- Auto-generation from JSON is more flexible
- Can customize entire workflow structure per config

## Recommendations

### ❌ DO NOT Do These

1. **DO NOT convert to reusable workflows**
   - CRUD6 workflow is too specialized
   - Beta.8 doesn't actually use reusable workflows anyway
   - PR #366 showed this is high-risk

2. **DO NOT simplify the workflow**
   - Each step serves a critical purpose
   - Removing steps breaks integration testing

3. **DO NOT copy beta.8's template structure**
   - Beta.8's templates are for simple PHPUnit/PHPStan
   - CRUD6 needs full integration testing

### ✅ DO Consider These

1. **✅ Keep current auto-generation system**
   - Already provides abstraction
   - Working well
   - Flexible and maintainable

2. **✅ Document workflow stages better**
   - Add more comments explaining each major stage
   - Create documentation of workflow architecture
   - Help future developers understand complexity

3. **✅ Update Node version references if needed**
   - Change `NODE_VERSION: "20"` → could stay as 20 (compatible with >= 18)
   - Or parameterize in integration-test-config.json

4. **✅ Consider modular improvements**
   - Extract long inline scripts to separate files
   - Keep same functionality, improve organization
   - Don't change workflow structure

## Specific Changes That ARE Safe

### 1. Update Node Action Version (SAFE)

Current (line 56):
```yaml
- name: Setup Node.js
  uses: actions/setup-node@v4
```

Beta.8 uses: `actions/setup-node@v3` (older!)

**Decision:** Keep v4 (newer is better)

### 2. Add Comments for Clarity (SAFE)

Add section headers:
```yaml
# ============================================
# STAGE 1: Environment Setup
# ============================================

# ============================================
# STAGE 2: UserFrosting Project Creation
# ============================================

# etc.
```

### 3. Update ENV Variables (SAFE)

Current:
```yaml
NODE_VERSION: "20"
```

Beta.8 compatible:
```yaml
NODE_VERSION: "20"  # Compatible with beta.8 (>= 18)
```

Just add clarifying comment.

## Conclusion

### The Answer to "Should we use reusable workflows?"

**NO** - for these reasons:

1. ✅ **Beta.8 doesn't actually use them** - it just provides disabled template files
2. ✅ **CRUD6 workflow is too specialized** - 90%+ is sprinkle-specific
3. ✅ **Already have abstraction** - auto-generation from JSON config
4. ✅ **PR #366 proved it's risky** - workflow changes broke integration tests
5. ✅ **No compatibility requirement** - beta.8 doesn't impose this

### What We Should Do Instead

1. **Keep current workflow** - it's working correctly
2. **Add clarifying comments** - explain beta.8 compatibility
3. **Document the architecture** - this analysis document
4. **Update Node version comment** - note >= 18 compatibility
5. **Consider future modularization** - extract inline scripts to files (low priority)

## Files Referenced

- `/home/runner/work/sprinkle-crud6/sprinkle-crud6/.github/workflows/integration-test.yml` (826 lines)
- `/tmp/uf-beta8/.github/workflows/PHPUnit.yml` (314 lines)
- `/tmp/uf-beta8/.github/workflows/PHPStan.yml` (35 lines)
- Beta.8 CHANGELOG.md: "Convert GitHub Actions workflow files to reusable templates"

## Next Steps for Review

**For User Review:**

1. ✅ Confirm: Beta.8's "reusable templates" are just disabled template files
2. ✅ Confirm: CRUD6's workflow is too specialized to abstract
3. ✅ Decision: Keep current workflow with minimal changes
4. ✅ Approve: Add clarifying comments about beta.8 compatibility
5. ❌ Decline: Converting to reusable workflows (not needed, high risk)

**Minimal Safe Changes:**
- Add comment noting Node 20 is compatible with beta.8's >= 18 requirement  
- Add section header comments for better readability
- Update documentation (this file)
- NO structural changes to workflow

## Risk Assessment

| Change Type | Risk Level | Rationale |
|-------------|-----------|-----------|
| Convert to reusable workflows | 🔴 **CRITICAL** | PR #366 showed this breaks integration tests |
| Simplify/remove steps | 🔴 **CRITICAL** | Each step is necessary for sprinkle testing |
| Update Node action version | 🟢 **LOW** | Already using v4 (newer than beta.8's v3) |
| Add clarifying comments | 🟢 **LOW** | No functional change |
| Extract scripts to files | 🟡 **MEDIUM** | Same functionality, different organization |

**Recommendation:** Only proceed with 🟢 LOW risk changes.
