# Converting CRUD6 Testing Framework to GitHub Actions Reusable Workflows

**Date:** January 15, 2026  
**Context:** CRUD6 has a reusable testing framework. Beta.8 mentions "reusable templates". Can we create TRUE GitHub Actions reusable workflows?  
**Status:** Analysis for review before implementation

## Current State

### What CRUD6 Has NOW

1. **Reusable Testing Framework** (✅ Working)
   - Location: `.github/testing-framework/`
   - Scripts: PHP and JavaScript tools
   - Templates: `workflow-template.yml`, `crud6-workflow-template.yml`
   - Generator: `generate-workflow.js` creates workflows from JSON config
   - Approach: **Copy framework to other sprinkles**

2. **JSON-Driven Configuration** (✅ Powerful)
   - File: `integration-test-config.json`
   - Defines: Sprinkle name, packages, routes, schemas, tests
   - Auto-generates: Complete 826-line workflow

3. **Installation Methods**
   - Auto-install: Downloads framework on first workflow run
   - Manual: Copy framework directory to sprinkle
   - Generated: Use `generate-workflow.js` to create custom workflow

### What Beta.8 Actually Did

Beta.8 **did NOT** create reusable workflows. It created:
- **Template files** (commented out, for manual copying)
- **Not workflow_call** patterns

## The Opportunity: True Reusable Workflows

### What GitHub Actions Reusable Workflows Are

**Reusable workflows** use `workflow_call` trigger:

```yaml
# .github/workflows/crud6-integration-reusable.yml
name: CRUD6 Integration Test (Reusable)

on:
  workflow_call:
    inputs:
      sprinkle-name:
        required: true
        type: string
      composer-package:
        required: true
        type: string
      npm-package:
        required: true
        type: string
      php-version:
        required: false
        type: string
        default: "8.4"
      node-version:
        required: false
        type: string
        default: "20"
      schema-path:
        required: false
        type: string
        default: "examples/schema"
    secrets:
      COMPOSER_AUTH:
        required: false

jobs:
  integration-test:
    # ... full workflow here
```

Then **called from** other sprinkles:

```yaml
# In another sprinkle's .github/workflows/test.yml
name: My Sprinkle Tests

on: [push, pull_request]

jobs:
  crud6-tests:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      sprinkle-name: my-sprinkle
      composer-package: vendor/my-sprinkle
      npm-package: "@vendor/my-sprinkle"
      php-version: "8.4"
      node-version: "20"
    secrets:
      COMPOSER_AUTH: ${{ secrets.COMPOSER_AUTH }}
```

### Benefits of True Reusable Workflows

1. **✅ No Framework Installation Needed**
   - Workflow runs directly from CRUD6 repository
   - No copying, cloning, or downloading

2. **✅ Always Up-to-Date**
   - Pin to branch: `@main` (latest)
   - Pin to tag: `@v1.0.0` (stable)
   - Pin to commit: `@abc123` (exact)

3. **✅ Centralized Maintenance**
   - Fix bugs once in CRUD6
   - All dependent sprinkles benefit immediately
   - Version control via git tags

4. **✅ Simpler Setup for Users**
   - 5-line workflow file vs 826-line generated file
   - Just specify inputs
   - No framework installation scripts

5. **✅ Multiple Workflow Options**
   - Can call different reusable workflows for different needs
   - Example: `crud6-basic.yml`, `crud6-full.yml`, `crud6-frontend-only.yml`

## Proposed Architecture

### Reusable Workflows to Create

#### 1. `crud6-integration-reusable.yml`
**Purpose:** Complete CRUD6 integration testing  
**Inputs:** 15-20 configuration options  
**Use Case:** Full testing with all features

#### 2. `crud6-basic-reusable.yml`
**Purpose:** Basic CRUD6 testing (no frontend, no screenshots)  
**Inputs:** 8-10 essential options  
**Use Case:** Quick backend API validation

#### 3. `crud6-frontend-reusable.yml`
**Purpose:** Frontend-only testing (Vitest + screenshots)  
**Inputs:** 10-12 frontend options  
**Use Case:** Vue component testing

### Input Parameters (Full Version)

```yaml
inputs:
  # === REQUIRED: Sprinkle Identity ===
  sprinkle-name:
    required: true
    type: string
    description: "Sprinkle directory name (e.g., 'my-sprinkle')"
  
  composer-package:
    required: true
    type: string
    description: "Composer package name (e.g., 'vendor/my-sprinkle')"
  
  npm-package:
    required: true
    type: string
    description: "NPM package name (e.g., '@vendor/my-sprinkle')"
  
  # === OPTIONAL: Version Configuration ===
  php-version:
    required: false
    type: string
    default: "8.4"
    description: "PHP version to use"
  
  node-version:
    required: false
    type: string
    default: "20"
    description: "Node.js version to use (>= 18)"
  
  userfrosting-version:
    required: false
    type: string
    default: "^6.0-beta"
    description: "UserFrosting version constraint"
  
  # === OPTIONAL: Schema & Data Configuration ===
  schema-path:
    required: false
    type: string
    default: ""
    description: "Path to schema files (empty = app/schema/crud6/)"
  
  use-example-schemas:
    required: false
    type: boolean
    default: false
    description: "Copy example schemas from examples/schema/"
  
  # === OPTIONAL: Route Configuration ===
  route-pattern:
    required: false
    type: string
    default: "simple"
    description: "Route pattern: 'simple', 'factory', or 'nested'"
  
  route-module:
    required: false
    type: string
    default: ""
    description: "Route module to import (e.g., '@vendor/pkg/routes')"
  
  route-name:
    required: false
    type: string
    default: ""
    description: "Route export name (e.g., 'MyRoutes')"
  
  # === OPTIONAL: Testing Configuration ===
  enable-frontend-tests:
    required: false
    type: boolean
    default: true
    description: "Run Vitest frontend tests"
  
  enable-phpunit-tests:
    required: false
    type: boolean
    default: true
    description: "Run PHPUnit backend tests"
  
  enable-screenshots:
    required: false
    type: boolean
    default: true
    description: "Capture Playwright screenshots"
  
  enable-api-tests:
    required: false
    type: boolean
    default: true
    description: "Test authenticated API endpoints"
  
  # === OPTIONAL: Vite Configuration ===
  vite-optimize-deps:
    required: false
    type: string
    default: "limax,lodash.deburr"
    description: "Comma-separated list of deps to optimize"
  
  # === OPTIONAL: Custom Scripts ===
  custom-setup-script:
    required: false
    type: string
    default: ""
    description: "Path to custom setup script to run after installation"
  
  custom-test-script:
    required: false
    type: string
    default: ""
    description: "Path to custom test script to run after standard tests"
```

## Implementation Plan

### Phase 1: Create Reusable Workflow Files (Low Risk)

**Goal:** Add reusable workflows WITHOUT breaking existing setup

**Files to Create:**
1. `.github/workflows/crud6-integration-reusable.yml` - Full integration testing
2. `.github/workflows/crud6-basic-reusable.yml` - Basic testing
3. `.github/workflows/crud6-frontend-reusable.yml` - Frontend only

**Key Decision:** These are **NEW** files, don't modify existing workflow

### Phase 2: Test with CRUD6 Itself (Validation)

**Goal:** Validate that CRUD6 can call its own reusable workflow

**Create:** `.github/workflows/test-reusable.yml`
```yaml
name: Test Reusable Workflow

on:
  workflow_dispatch:
  push:
    branches: [feature/reusable-workflows]

jobs:
  test:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@feature/reusable-workflows
    with:
      sprinkle-name: sprinkle-crud6
      composer-package: ssnukala/sprinkle-crud6
      npm-package: "@ssnukala/sprinkle-crud6"
      schema-path: "examples/schema"
```

**Validation:**
- Run workflow on feature branch
- Verify all steps execute correctly
- Compare results with existing integration-test.yml
- Fix any issues before merging

### Phase 3: Document Migration Path (User Guidance)

**Create:** `.github/testing-framework/docs/REUSABLE_WORKFLOWS.md`

**Content:**
- How to use reusable workflows
- Migration guide from copy-based approach
- Comparison of approaches
- When to use which approach
- Examples for common scenarios

### Phase 4: Update Main Integration Test (Optional)

**Decision Point:** Should CRUD6's own workflow use the reusable version?

**Option A:** Keep current workflow (safe, proven)
- Pro: No risk to existing CI
- Pro: Can test both approaches
- Con: Duplication of workflow logic

**Option B:** Convert to call reusable workflow
- Pro: Dog-fooding (we use what we provide)
- Pro: Single source of truth
- Con: Risk if reusable workflow has issues

**Recommendation:** Start with Option A, move to Option B after proven

## Advantages Over Current Approach

### Current: Copy Framework Approach

```
Other Sprinkle:
1. Copy .github/testing-framework/ to sprinkle
2. Or download on first run
3. Run generate-workflow.js
4. Generates 826-line workflow
5. Updates require re-copying or re-downloading
```

**Issues:**
- Framework can get out of sync
- Each sprinkle has copy of framework code
- Updates require manual intervention
- Large generated workflow files

### Proposed: Reusable Workflow Approach

```
Other Sprinkle:
1. Create simple workflow file (10-20 lines)
2. Call CRUD6's reusable workflow
3. Pass configuration as inputs
4. Updates automatic (if using @main)
```

**Benefits:**
- Always latest version from CRUD6
- No framework copying needed
- Tiny workflow files
- Centralized bug fixes

## Both Approaches Can Coexist

**Important:** We can support BOTH approaches:

1. **Reusable Workflows** (recommended for most)
   - Simple setup
   - Auto-updates
   - Centralized maintenance

2. **Copy Framework** (for special cases)
   - Air-gapped environments
   - Heavy customization needed
   - Pinning specific framework version
   - Running framework locally for debugging

**Users choose** based on their needs.

## Limitations of Reusable Workflows

### GitHub Actions Constraints

1. **Input Complexity**
   - Can only pass: strings, booleans, numbers
   - Cannot pass: objects, arrays, JSON
   - **Workaround:** JSON as string, parse in workflow

2. **Secrets**
   - Must be explicitly passed
   - Cannot access caller's secrets automatically
   - **Workaround:** Inherit secrets via `secrets: inherit`

3. **Artifacts**
   - Artifacts are scoped to calling workflow
   - Can be accessed by caller
   - **No issue:** Works as expected

4. **Matrix Strategy**
   - Cannot be parameterized via inputs
   - Must be defined in reusable workflow
   - **Limitation:** Testing multiple PHP/Node versions requires separate calls

5. **Custom Actions**
   - Cannot reference calling repo's actions
   - Must use public actions or actions from CRUD6 repo
   - **Workaround:** Keep custom actions in CRUD6

### Workarounds for JSON Configuration

**Problem:** Can't pass `integration-test-config.json` directly

**Solution:** Pass JSON as string input:

```yaml
# Caller
jobs:
  test:
    uses: ssnukala/sprinkle-crud6/.github/workflows/crud6-integration-reusable.yml@main
    with:
      config-json: ${{ toJSON(fromJSON(readFile('.github/config.json'))) }}
```

**Or:** Accept individual parameters (simpler, more explicit)

## Recommendation: Hybrid Approach

### For CRUD6 Itself
- Keep current `integration-test.yml` as primary workflow (proven, working)
- Add reusable workflows as **additional** option
- Test reusable workflows thoroughly before promoting

### For Documentation
- Document reusable workflows as **recommended** approach
- Keep copy-based approach as **alternative**
- Provide migration guide

### Implementation Priority
1. ✅ **Phase 1:** Create reusable workflow files (NEW files)
2. ✅ **Phase 2:** Test on feature branch
3. ✅ **Phase 3:** Document usage
4. ⏸️ **Phase 4:** Consider for CRUD6 itself (later decision)

## Next Steps for Review

**Questions for Approval:**

1. **Should we create reusable workflows?**
   - Benefit: Modern, maintainable approach
   - Risk: New functionality to test and support

2. **Which workflows to create first?**
   - Full integration (highest value)
   - Basic testing (quickest to implement)
   - Frontend only (specialized use case)

3. **How to handle JSON configuration?**
   - Pass as string input (flexible, complex)
   - Use individual parameters (simple, verbose)
   - Support both approaches

4. **Should CRUD6 use its own reusable workflow?**
   - Yes (dog-fooding, single source of truth)
   - No (keep proven workflow, less risk)
   - Later (prove it works first)

5. **Timeline?**
   - Add to current PR (beta.8 alignment)
   - Separate PR (focused on this feature)
   - Future enhancement (post-beta.8)

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Reusable workflow has bugs | Medium | High | Test thoroughly on feature branch |
| Users prefer old approach | Low | Low | Support both methods |
| GitHub Actions limitations | Medium | Medium | Document workarounds |
| Breaking existing users | Low | High | Don't modify existing workflows |
| Maintenance burden | Low | Medium | Reusable = less maintenance |

**Overall Risk:** 🟡 **MEDIUM** - New feature, but non-breaking

## Conclusion

**RECOMMENDATION:** Create reusable workflows as **NEW OPTION**, don't replace existing approach.

**Why:**
1. ✅ Modern GitHub Actions best practice
2. ✅ Aligns with beta.8's "reusable" theme (even if different approach)
3. ✅ Reduces maintenance for dependent sprinkles
4. ✅ Non-breaking (adds option, doesn't remove)
5. ✅ Can be tested incrementally

**Implementation:**
- Start with Phase 1 (create reusable workflow file)
- Test thoroughly (Phase 2)
- Document well (Phase 3)
- Consider for CRUD6 later (Phase 4)

**This aligns with beta.8 spirit while going beyond what beta.8 actually did.**
