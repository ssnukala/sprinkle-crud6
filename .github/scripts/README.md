# Integration Test Scripts

This directory contains scripts used by the GitHub Actions integration test workflow.

## Scripts

### 1. take-authenticated-screenshots.js

**Purpose:** Capture screenshots and network requests from CRUD6 pages

**Usage:**
```bash
node take-authenticated-screenshots.js <base_url> <username> <password>
```

**Example:**
```bash
node take-authenticated-screenshots.js http://localhost:8080 admin admin123
```

**What it does:**
- Launches Playwright browser
- Logs into UserFrosting application
- Navigates to CRUD6 pages
- Captures all XHR/fetch requests
- Takes screenshots
- Analyzes network requests
- Saves results to files

**Output Files:**
- `/tmp/screenshot_groups_list.png` - Screenshot of groups list page
- `/tmp/screenshot_group_detail.png` - Screenshot of group detail page
- `/tmp/network_requests.json` - Network request log

**Console Output:**
- Authentication status
- Screenshot confirmation
- Network request analysis
  - Schema API calls
  - YAML file imports
  - CRUD6 API calls
  - Issue detection

**Exit Codes:**
- `0` - Success
- `1` - Error (authentication failed, timeout, etc.)

**Dependencies:**
- Playwright
- Node.js 18+

### 2. analyze-network-log.js

**Purpose:** Analyze network request logs to identify issues

**Usage:**
```bash
node analyze-network-log.js <path-to-network-log>
```

**Example:**
```bash
node analyze-network-log.js /tmp/network_requests.json
```

**What it does:**
- Reads network log JSON file
- Categorizes requests by type
- Detects common issues
- Assigns severity levels
- Provides recommendations

**Console Output:**
```
========================================
Network Request Analysis Tool
========================================

📊 Overall Statistics
Total XHR/Fetch Requests: 4
Total Schema API Calls: 2
Total YAML Imports: 0 ✅

📄 Groups List
Request Breakdown:
  Schema API: 1
  YAML Imports: 0 ✅
  CRUD6 API: 1

✅ No Issues Detected
```

**Issue Detection:**

**HIGH Severity:**
- YAML files being imported (validation adapter not working)
- Too many total schema API calls (caching not working)

**MEDIUM Severity:**
- Multiple schema calls on same page (components not sharing schema)

**Exit Codes:**
- `0` - No high severity issues found
- `1` - High severity issues detected

**Use Cases:**
- Local development debugging
- CI/CD quality gates
- Performance regression testing
- Post-deployment verification

### 3. check-seeds.php

**Purpose:** Validate CRUD6 database seeding

**Usage:**
```bash
php check-seeds.php
```

**What it does:**
- Verifies crud6-admin role exists
- Checks CRUD6 permissions created
- Validates permission assignments
- Confirms role relationships

**Exit Codes:**
- `0` - All seeds validated
- `1` - Validation failed

### 4. test-seed-idempotency.php

**Purpose:** Test that seeds can be run multiple times without duplicates

**Usage:**
```bash
# Count before
php test-seed-idempotency.php

# Re-run seeds
php bakery seed ...

# Count after and compare
php test-seed-idempotency.php after "<before_counts>"
```

**What it does:**
- Counts records in database
- Compares before/after counts
- Ensures no duplicates created

**Exit Codes:**
- `0` - Idempotency verified (counts match)
- `1` - Duplicates detected (counts increased)

## Common Workflows

### Local Development Testing

**Step 1: Start UserFrosting**
```bash
cd /path/to/userfrosting
php bakery serve &
php bakery assets:vite &
```

**Step 2: Capture Network Requests**
```bash
cd /path/to/sprinkle-crud6
node .github/scripts/take-authenticated-screenshots.js http://localhost:8080 admin admin123
```

**Step 3: Analyze Results**
```bash
node .github/scripts/analyze-network-log.js /tmp/network_requests.json
```

**Step 4: View Screenshots**
```bash
open /tmp/screenshot_*.png
```

### CI/CD Integration

The integration test workflow (`.github/workflows/integration-test.yml`) uses these scripts automatically:

1. **Setup** - Install UserFrosting, CRUD6, dependencies
2. **Seed** - Run database migrations and seeds
3. **Validate** - Check seeds with `check-seeds.php`
4. **Test Idempotency** - Verify with `test-seed-idempotency.php`
5. **Capture** - Screenshots and network requests with `take-authenticated-screenshots.js`
6. **Upload** - Artifacts to GitHub Actions

**Artifacts Generated:**
- `integration-test-screenshots` - Screenshots
- `network-request-log` - Network analysis JSON

### Troubleshooting Network Issues

**Scenario 1: YAML imports detected**

```bash
# Run capture
node .github/scripts/take-authenticated-screenshots.js http://localhost:8080 admin admin123

# Console shows:
⚠️  YAML File Imports: 3 (SHOULD BE 0!)
   1. group.yaml
   2. role.yaml
   3. login.yaml
```

**Action:**
- Check validation adapter in components
- Ensure using `useCRUD6RegleAdapter`
- Search codebase for `useRuleSchemaAdapter` imports

**Scenario 2: Multiple schema calls**

```bash
# Analyzer shows:
📊 Schema API Calls: 3
   1. GET /api/crud6/groups/schema
   2. GET /api/crud6/groups/schema
   3. GET /api/crud6/groups/schema
```

**Action:**
- Check component hierarchy
- Ensure schema passed as props
- Verify schema store caching
- Look for duplicate `loadSchema()` calls

## Example Network Logs

See `examples/` directory:
- `network_requests_good.json` - Optimal pattern (no issues)
- `network_requests_with_yaml.json` - Problematic pattern (YAML imports)

Use these to test the analyzer:

```bash
# Test with good pattern
node .github/scripts/analyze-network-log.js examples/network_requests_good.json
# Exit: 0 (no issues)

# Test with problematic pattern
node .github/scripts/analyze-network-log.js examples/network_requests_with_yaml.json
# Exit: 1 (high severity issues)
```

## Documentation

For comprehensive documentation, see:
- **Quick Start:** `../../QUICK_NETWORK_ANALYSIS.md`
- **Detailed Guide:** `../../.archive/NETWORK_REQUEST_ANALYSIS.md`
- **Implementation Summary:** `../../.archive/REDUNDANT_API_CALL_TROUBLESHOOTING_SUMMARY.md`

## Requirements

**For Screenshot/Network Scripts:**
- Node.js 18+
- Playwright (`npm install playwright`)
- Playwright browsers (`npx playwright install chromium`)

**For PHP Scripts:**
- PHP 8.1+
- UserFrosting 6 application
- Database connection configured

## Contributing

When modifying these scripts:

1. **Maintain backward compatibility** - Don't break existing workflows
2. **Update documentation** - Keep this README current
3. **Test thoroughly** - Run locally before committing
4. **Validate syntax** - Use `node --check` for JavaScript
5. **Add examples** - Include sample output in documentation

## Troubleshooting

### Script fails with "Authentication failed"
- Verify username/password are correct
- Check that user exists in database
- Ensure database is seeded

### Script fails with "Connection refused"
- Verify servers are running (bakery serve, assets:vite)
- Check firewall/port settings
- Ensure correct base URL

### No screenshots generated
- Check Playwright browser installation
- Verify page actually loads
- Review error messages in console

### Network log is empty
- Ensure pages are making XHR/fetch requests
- Check that JavaScript is enabled
- Verify Vite server is running

## License

Same as sprinkle-crud6 repository.
