# Workflow Comparison: Missing Steps Analysis

**Date**: 2025-12-13  
**Purpose**: Systematic comparison between current workflow and backup workflow  
**Reference**: `.archive/pre-framework-migration/integration-test.yml.backup`

## Overview

The backup workflow (`integration-test.yml.backup`) contains 42 steps with comprehensive testing capabilities. The current workflow (`integration-test.yml`) has 35 steps and is missing several important validation and testing steps.

## Current Status: Admin Login Fix (This PR)

✅ **FIXED**: Added "Create admin user" step (was missing entirely)
- Location: After "Test seed idempotency", before "Build frontend assets"
- Matches workflow-template.yml pattern (lines 291-299)
- Matches backup workflow (lines 466-476)

## Missing Steps Comparison

### ✅ Present in Both Workflows
1. Checkout sprinkle
2. Setup PHP
3. Setup Node.js  
4. Create UserFrosting project
5. Configure Composer dependencies
6. Configure NPM dependencies
7. Configure MyApp.php
8. Configure main.ts/router
9. Verify NPM package installation
10. Configure vite.config.ts
11. Setup environment
12. Run migrations
13. Run PHP seeds
14. Validate seed data
15. Test seed idempotency
16. **Create admin user** ✅ (FIXED in this PR)
17. Build frontend assets
18. Start PHP development server
19. Start Vite development server
20. Test unauthenticated paths
21. Install Playwright
22. Login as admin user
23. Test authenticated paths
24. Capture screenshots
25. Upload screenshots/logs
26. Stop servers

### ❌ Missing from Current Workflow

#### Critical Missing Steps (High Priority)

**1. Copy CRUD6 Schema Files from Examples**
- **Backup location**: Step 14 (lines 222-276)
- **Purpose**: Copies schema files from `examples/schema` to `app/schema/crud6`
- **Files copied**: users.json, groups.json, roles.json, permissions.json, activities.json
- **Impact**: Schema files may not be available for testing
- **Required for**: Schema loading tests, CRUD6 API endpoints

**2. Merge Locale Messages from Examples**
- **Backup location**: Step 15 (lines 278-394)
- **Purpose**: Merges model-specific translations from examples into app locale
- **Impact**: Missing translations for CRUD6 models
- **Required for**: Proper internationalization in tests

**3. Create Test User for Modification Tests**
- **Backup location**: Step 22 (lines 478-489)
- **Purpose**: Creates a second user (testuser) for modification/deletion tests
- **Impact**: Tests may try to modify/delete the admin user (ID 1)
- **Required for**: Safe user modification tests

**4. Test Schema Loading**
- **Backup location**: Step 23 (lines 491-519)
- **Purpose**: Validates all CRUD6 schemas can be loaded and parsed
- **Impact**: Schema loading errors won't be caught until later
- **Required for**: Early validation of schema files

**5. Test Database Connection**
- **Backup location**: Step 24 (lines 521-524)
- **Purpose**: Verifies MySQL connectivity and queries groups table
- **Impact**: Database connectivity issues won't be caught early
- **Required for**: Early validation of database setup

#### Advanced Testing Steps (Medium Priority)

**6. Copy Schema and Locale to Sprinkle for Test Generation**
- **Backup location**: Step 30 (lines 591-692)
- **Purpose**: Prepares sprinkle-crud6 directory with schemas for test generation
- **Impact**: Test generation step won't have necessary files
- **Required for**: Schema-driven test generation

**7. Generate Schema-Driven Tests**
- **Backup location**: Step 31 (lines 694-712)
- **Purpose**: Auto-generates PHPUnit tests from JSON schemas
- **Script**: `.github/scripts/generate-schema-tests.js`
- **Impact**: Missing comprehensive schema-based test coverage
- **Required for**: Automated test generation from schemas

**8. Configure PHPUnit for CRUD6 Tests**
- **Backup location**: Step 32 (lines 714-764)
- **Purpose**: Creates phpunit-crud6.xml and bootstrap-crud6.php
- **Impact**: PHPUnit tests can't run without proper configuration
- **Required for**: Running CRUD6-specific PHPUnit tests

**9. Verify Runtime Directories Before Tests**
- **Backup location**: Step 33 (lines 766-819)
- **Purpose**: Validates storage/sessions, storage/cache, storage/logs exist and are writable
- **Impact**: Session/storage issues won't be caught until tests fail
- **Required for**: Preventing runtime directory errors

**10. Run PHPUnit Integration Tests**
- **Backup location**: Step 34 (lines 821-873)
- **Purpose**: Runs comprehensive PHPUnit test suites
- **Test suites**: Integration Tests, Controller Tests, Generated Schema Tests
- **Impact**: Missing comprehensive programmatic API testing
- **Note**: Currently disabled in backup (if: false) due to session handler issues

#### Enhanced Features (Lower Priority)

**11. Take Screenshots with Network Tracking**
- **Backup location**: Step 35 (lines 875-913)
- **Purpose**: Screenshots + network request tracking + API testing in one step
- **Script**: `take-screenshots-with-tracking.js`
- **Current**: Uses simpler `take-screenshots-modular.js`
- **Impact**: Missing network request tracking and API efficiency analysis

**12. Capture and Display PHP Error Logs**
- **Backup location**: Step 36 (lines 915-925)
- **Purpose**: Captures and displays PHP error logs with script
- **Script**: `capture-php-error-logs.sh`
- **Current**: Only uploads logs as artifacts
- **Impact**: Error logs not displayed in workflow output

**13. Upload Browser Console Errors**
- **Backup location**: Step 39 (lines 945-952)
- **Purpose**: Uploads browser console errors as artifact
- **Current**: Not captured
- **Impact**: Missing browser-side error tracking

## Detailed Step-by-Step Comparison

| # | Backup Workflow Step | Current Workflow Step | Status | Notes |
|---|---|---|---|---|
| 1 | Checkout sprinkle-crud6 | Checkout sprinkle | ✅ | Different name, same purpose |
| 2 | Setup PHP | Setup PHP | ✅ | Identical |
| 3 | Setup Node.js | Setup Node.js | ✅ | Identical |
| 4 | Create UserFrosting project | Install testing framework + Create UF project | ✅ | Current has extra framework install step |
| 5 | Configure Composer | Configure Composer | ✅ | Similar functionality |
| 6 | Install PHP dependencies | (Part of Configure Composer) | ✅ | Merged in current |
| 7 | Package sprinkle-crud6 for NPM | Configure NPM dependencies | ✅ | Similar functionality |
| 8 | Install NPM dependencies | (Merged with step 7) | ✅ | Combined in current |
| 9 | Configure MyApp.php | Configure MyApp.php | ✅ | Identical |
| 10 | Configure router/index.ts | Configure routes (simple pattern) | ✅ | Similar |
| 11 | Configure /main.ts | Configure main.ts | ✅ | Identical |
| 12 | Verify NPM package installation | Verify NPM package installation | ✅ | Identical |
| 13 | Configure vite.config.ts | Configure vite.config.ts | ✅ | Identical |
| **14** | **Copy CRUD6 schema files** | **MISSING** | ❌ | **Schemas not copied** |
| **15** | **Merge locale messages** | **MISSING** | ❌ | **Translations not merged** |
| 16 | Setup environment | Setup environment | ✅ | Identical |
| 17 | Run migrations | Run migrations | ✅ | Identical |
| 18 | Seed database (Modular) | Generate and create tables + Generate seed data + Run PHP seeds | ✅ | Current has more granular steps |
| 19 | Validate CRUD6 seed data | Validate seed data | ✅ | Identical |
| 20 | Test seed idempotency | Test seed idempotency | ✅ | Identical |
| **21** | **Create admin user** | **Create admin user** | ✅ | **FIXED in this PR** |
| **22** | **Create test user** | **MISSING** | ❌ | **Test user not created** |
| **23** | **Test schema loading** | **MISSING** | ❌ | **Schema validation missing** |
| **24** | **Test database connection** | **MISSING** | ❌ | **DB test missing** |
| 25 | Install Playwright browsers | Install Playwright | ✅ | Similar |
| 26 | Build frontend assets | Build frontend assets | ✅ | Identical |
| 27 | Start PHP server | Start PHP server | ✅ | Identical |
| 28 | Start Vite server | Start Vite server | ✅ | Identical |
| 29 | Test Unauthenticated API paths | Test unauth API + frontend paths | ✅ | Current has both |
| **30** | **Copy schema/locale to sprinkle** | **MISSING** | ❌ | **Test generation prep missing** |
| **31** | **Generate Schema-Driven Tests** | **MISSING** | ❌ | **Test generation missing** |
| **32** | **Configure PHPUnit** | **MISSING** | ❌ | **PHPUnit config missing** |
| **33** | **Verify Runtime Directories** | **MISSING** | ❌ | **Directory validation missing** |
| **34** | **Run PHPUnit Tests** | **MISSING** | ❌ | **PHPUnit tests missing** |
| **35** | **Screenshots + Network Tracking** | Copy testing scripts + Login + Capture screenshots | ⚠️ | Current missing network tracking |
| **36** | **Capture PHP error logs** | **MISSING** | ❌ | **Log display missing** |
| 37 | Upload screenshots | Upload screenshots | ✅ | Identical |
| 38 | Upload network summary | **MISSING** | ❌ | **Not captured in current** |
| 39 | Upload browser console errors | **MISSING** | ❌ | **Not captured** |
| 40 | Upload PHP logs | Upload logs | ✅ | Similar |
| 41 | Stop servers | Stop servers | ✅ | Identical |
| 42 | Summary | (Part of Generate test summary) | ✅ | Similar |

## Priority Recommendations

### Immediate (This PR)
- [x] **Create admin user step** - COMPLETED ✅

### High Priority (Next PR)
1. Add "Copy CRUD6 schema files from examples" step
2. Add "Merge locale messages from examples" step
3. Add "Create test user for modification tests" step
4. Add "Test schema loading" step
5. Add "Test database connection" step

### Medium Priority (Future PR)
6. Add "Copy schema/locale to sprinkle for test generation" step
7. Add "Generate Schema-Driven Tests" step
8. Add "Configure PHPUnit for CRUD6 tests" step
9. Add "Verify Runtime Directories Before Tests" step
10. Add "Run PHPUnit Integration Tests" step (if session issues resolved)

### Low Priority (Optional Enhancements)
11. Upgrade to screenshots with network tracking
12. Add "Capture and display PHP error logs" step
13. Add "Upload browser console errors" step

## Implementation Strategy

### Approach 1: Incremental (Recommended)
- Add missing steps one PR at a time
- Test each addition in CI before moving to next
- Easier to debug if issues arise
- Better commit history

### Approach 2: Batch by Priority
- Add all High Priority steps in one PR
- Add all Medium Priority steps in another PR
- Add all Low Priority steps in final PR
- Fewer PRs but harder to isolate issues

### Approach 3: Full Restoration
- Copy entire backup workflow
- Update paths and variables
- Test everything at once
- Fastest but riskiest approach

## Notes

1. The backup workflow has better test coverage but may have session handler issues (PHPUnit tests disabled)
2. Current workflow is simpler and works but lacks comprehensive testing
3. Some steps in current workflow are more modular (e.g., separate DDL generation and seed data generation)
4. The framework installation step in current workflow is an enhancement not in backup

## Action Items

- [ ] Create PR to add "Copy CRUD6 schema files" step
- [ ] Create PR to add "Merge locale messages" step  
- [ ] Create PR to add "Create test user" step
- [ ] Create PR to add "Test schema loading" step
- [ ] Create PR to add "Test database connection" step
- [ ] Evaluate PHPUnit test approach (fix session issues or use alternative)
- [ ] Consider adding network tracking to screenshot step
- [ ] Consider adding error log capture script

## References

- Backup workflow: `.archive/pre-framework-migration/integration-test.yml.backup`
- Current workflow: `.github/workflows/integration-test.yml`
- Workflow template: `.github/testing-framework/workflow-template.yml`
- This analysis: `.archive/WORKFLOW_COMPARISON_MISSING_STEPS.md`
