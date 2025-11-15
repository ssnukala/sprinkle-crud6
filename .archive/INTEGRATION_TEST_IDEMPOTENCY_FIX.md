# Integration Test Fix - Seed Idempotency JSON Parsing Error

**Date:** 2025-11-15  
**Issue:** Integration test failing with "ERROR: Failed to parse expected counts"  
**PR:** #184 (copilot/fix-reseed-count-parsing-error)

## Problem

The integration test workflow was failing at the seed idempotency test step with the error:
```
ERROR: Failed to parse expected counts
```

### Root Cause

The bash workflow was using `cut -d: -f2` to extract JSON data from a line formatted as:
```
BEFORE:{"crud6-admin":1,"permissions_create_crud6_delete_crud6":6}
```

The problem: `cut -d: -f2` splits on colons and returns only field 2 (the part between the first and second colon). Since JSON objects contain colons in their structure (`"key":value`), this resulted in:
```
cut -d: -f2 → {"crud6-admin"
```

This incomplete JSON string caused `json_decode()` to fail in PHP, leading to the error.

## Solution

Changed the extraction command from:
```bash
BEFORE_COUNTS=$(echo "$BEFORE_OUTPUT" | grep "BEFORE:" | cut -d: -f2)
```

To:
```bash
BEFORE_COUNTS=$(echo "$BEFORE_OUTPUT" | grep "BEFORE:" | cut -d: -f2-)
```

The `-` after `f2` tells `cut` to extract field 2 **and all subsequent fields**, which preserves the complete JSON string:
```
cut -d: -f2- → {"crud6-admin":1,"permissions_create_crud6_delete_crud6":6}
```

## Files Changed

1. `.github/workflows/integration-test.yml` (line 197)

**Note:** The `.github/workflows/integration-test.yml.backup` file was intentionally left unchanged as a point of reference, even though it has the same issue. The backup represents a working state from before the modular testing changes.

## Verification

Test command:
```bash
BEFORE_OUTPUT='BEFORE:{"crud6-admin":1,"permissions_create_crud6_delete_crud6":6}'
echo "$BEFORE_OUTPUT" | cut -d: -f2   # Wrong: {"crud6-admin"
echo "$BEFORE_OUTPUT" | cut -d: -f2-  # Correct: {"crud6-admin":1,"permissions_create_crud6_delete_crud6":6}
```

Result: ✅ Valid JSON that can be parsed by `json_decode()` in PHP

## Context

This test was introduced in PR #183 as part of the modular integration testing framework. The issue occurred because the JSON output format includes nested colons that weren't accounted for in the initial implementation.

The fix ensures the idempotency test can properly compare before/after seed counts to verify that re-running seeds doesn't create duplicate records.
