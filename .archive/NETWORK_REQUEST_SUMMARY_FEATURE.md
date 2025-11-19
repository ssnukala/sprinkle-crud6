# Network Request Summary Feature

## Overview

As of this implementation, the integration test workflow now generates a detailed network request summary file that provides comprehensive information about all HTTP requests made during frontend page testing.

## What's Included

The network request summary file (`network-requests-summary.txt`) contains:

### 1. Summary Statistics
- Total number of requests
- Count of CRUD6 API calls
- Count of Schema API calls
- Number of redundant call groups detected

### 2. Per-Page Breakdown
For each tested page:
- Page name and path
- List of all requests made during that page load
- Timestamp for each request
- HTTP method (GET, POST, etc.)
- Resource type (xhr, script, stylesheet, etc.)
- Identification of CRUD6 and Schema API calls

### 3. Redundant Call Detection
- Lists any API endpoints called multiple times
- Shows the count of redundant calls
- Provides timestamps for each occurrence
- Helps identify performance optimization opportunities

### 4. Chronological Timeline
- Complete list of all requests in the order they occurred
- Useful for understanding request sequencing
- Helps debug loading issues

## How to Access

### During Workflow Run
The workflow outputs a preview of the first 30 lines of the summary in the "Take screenshots of frontend pages with Network Tracking" step.

### After Workflow Completes
1. Navigate to the workflow run page
2. Scroll to the bottom to the "Artifacts" section
3. Click on "network-requests-summary" to download
4. Extract the ZIP file to access `network-requests-summary.txt`

### Retention
The network request summary artifact is retained for 30 days, matching the retention period for screenshots.

## Use Cases

### Performance Analysis
- Identify pages with excessive API calls
- Find redundant or duplicate requests
- Optimize request patterns

### Debugging
- Understand request sequencing issues
- Verify API endpoints are being called
- Check for missing or unexpected requests

### Documentation
- Document actual API usage patterns
- Create test data for performance testing
- Share request examples with team members

## Example Output Structure

```
═══════════════════════════════════════════════════════════════════════════════
NETWORK REQUEST TRACKING DETAILED REPORT
UserFrosting CRUD6 Sprinkle Integration Test
═══════════════════════════════════════════════════════════════════════════════
Generated: 2025-11-19T17:06:20.000Z
Base URL: http://localhost:8080
Total Pages Tested: 2
Total Network Requests: 45

───────────────────────────────────────────────────────────────────────────────
SUMMARY BY TYPE
───────────────────────────────────────────────────────────────────────────────
Total Requests:        45
CRUD6 API Calls:       8
Schema API Calls:      2
Redundant Call Groups: 0

───────────────────────────────────────────────────────────────────────────────
PER-PAGE BREAKDOWN
───────────────────────────────────────────────────────────────────────────────

1. groups_list
   Path: /crud6/groups
   Requests: 23
   
   Request Details:
   1. [2025-11-19T17:06:15.123Z] GET /api/crud6/groups/schema
      Resource Type: xhr
      📌 CRUD6 API Call
      📌 Schema API Call
   2. [2025-11-19T17:06:15.234Z] GET /api/crud6/groups
      Resource Type: xhr
      📌 CRUD6 API Call
   ...
```

## Technical Details

### Implementation
- Uses Playwright's request interception to track all network activity
- Accumulates requests across all page loads during a test run
- Generates a text-based report for easy reading and sharing
- Automatically uploaded as a GitHub Actions artifact

### File Format
- Plain text (.txt) format
- UTF-8 encoding
- Uses Unicode box-drawing characters for formatting
- Emoji markers (📌, ⚠️, ✅) for visual clarity

### Performance Impact
- Minimal overhead during testing
- File generation takes <1 second
- File size typically 5-20 KB depending on test scope

## Related Files

- Script: `.github/scripts/take-screenshots-with-tracking.js`
- Workflow: `.github/workflows/integration-test.yml`
- Config: `.github/config/integration-test-paths.json`

## Issue Reference

This feature was implemented in response to:
- Issue: Request to add network results summary to test results page
- Workflow Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19509702030
- Requirement: Downloadable text file with network requests from tests
