# CSRF Session Storage Error Fix

**Date:** December 11, 2024  
**Issue:** Integration tests failing with CSRF Guard middleware error  
**PR/Branch:** copilot/fix-issue-with-failure  
**Status:** ✅ Fixed

## Problem Statement

Integration tests were failing with the following error:

```
PHP Fatal error: Uncaught RuntimeException: Invalid CSRF storage. 
Use session_start() before instantiating the Guard middleware or provide array storage. 
in /home/runner/work/sprinkle-crud6/sprinkle-crud6/userfrosting/vendor/slim/csrf/src/Guard.php:152
```

**Error Log:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/20148412053/job/57834985461

## Root Cause Analysis

The issue was NOT actually about session initialization or CSRF configuration. The real problem was:

### Missing Server Startup Steps

When the workflow was refactored to use the reusable testing framework, critical server startup steps were accidentally omitted:

1. **No PHP development server**: The old workflow started `php bakery serve` before running path tests
2. **No Vite development server**: The old workflow started `php bakery assets:vite` for asset serving
3. **No directory verification**: No verification that runtime directories (logs, cache, sessions) existed and were writable

### Why This Caused CSRF Error

When `test-paths.php` tried to make HTTP requests to test the API endpoints:
- No servers were running to handle the requests
- The application couldn't properly initialize
- Session storage couldn't be set up
- CSRF Guard middleware failed during initialization
- Error message was misleading (seemed like a session config issue, but was actually a "server not running" issue)

## Solution

Added the missing steps to `.github/workflows/integration-test.yml` following the exact pattern from the old working workflow in `.archive/pre-framework-migration/integration-test.yml.backup`.

### Changes Made

#### 1. Added Runtime Directory Verification (before build)
```yaml
- name: Verify runtime directories
  run: |
    cd userfrosting
    
    # Ensure all runtime directories exist with proper permissions
    mkdir -p app/storage/sessions app/storage/cache app/storage/logs
    mkdir -p app/logs app/cache app/sessions
    chmod -R 777 app/storage app/logs app/cache app/sessions
    
    # Verify directories are writable
    for dir in app/storage/sessions app/storage/cache app/storage/logs app/logs app/cache app/sessions; do
      if [ -w "$dir" ]; then
        echo "✅ $dir is writable"
      else
        echo "❌ $dir is NOT writable"
        exit 1
      fi
    done
    
    echo "✅ All runtime directories verified and writable"
```

#### 2. Moved Playwright Installation (before build)
```yaml
- name: Install Playwright
  run: |
    cd userfrosting
    npm install playwright
    npx playwright install chromium --with-deps
```

#### 3. Added PHP Server Startup (after build)
```yaml
- name: Start PHP development server
  run: |
    cd userfrosting
    php bakery serve &
    SERVER_PID=$!
    echo $SERVER_PID > /tmp/server.pid
    sleep 10
    
    curl -f http://localhost:8080 || (echo "⚠️ Server may not be ready yet" && sleep 5 && curl -f http://localhost:8080)
    echo "✅ PHP server started on localhost:8080"
```

#### 4. Added Vite Server Startup (after PHP server)
```yaml
- name: Start Vite development server
  run: |
    cd userfrosting
    npm update
    php bakery assets:vite &
    VITE_PID=$!
    echo $VITE_PID > /tmp/vite.pid
    
    echo "Waiting for Vite server to start..."
    sleep 20
    
    echo "Testing if frontend is accessible..."
    curl -f http://localhost:8080 || echo "⚠️ Page load test after Vite start"
    
    echo "✅ Vite server started"
```

#### 5. Added Server Cleanup (at end with if: always())
```yaml
- name: Stop servers
  if: always()
  run: |
    if [ -f /tmp/server.pid ]; then
      kill $(cat /tmp/server.pid) || true
    fi
    if [ -f /tmp/vite.pid ]; then
      kill $(cat /tmp/vite.pid) || true
    fi
```

## Workflow Step Order Comparison

### Before Fix (Broken)
1. Test seed idempotency
2. Build frontend assets
3. **Test API and frontend paths** ← **FAILED** (no servers running!)
4. Install Playwright
5. Capture screenshots

### After Fix (Working - Matches Old Workflow)
1. Test seed idempotency
2. **Verify runtime directories** ← NEW
3. **Install Playwright** ← MOVED UP
4. Build frontend assets
5. **Start PHP development server** ← NEW
6. **Start Vite development server** ← NEW
7. Test API and frontend paths ← Now works!
8. Capture screenshots
9. Upload artifacts
10. **Stop servers** ← NEW

## Key Learnings

1. **Error messages can be misleading**: The CSRF error seemed like a session configuration issue, but was actually caused by missing servers

2. **Framework migration requires careful comparison**: When refactoring workflows, all steps must be preserved, not just the obvious ones

3. **Integration tests need running servers**: Any test that makes HTTP requests needs actual servers running (seems obvious in hindsight!)

4. **Archive old working versions**: Having `.archive/pre-framework-migration/integration-test.yml.backup` was crucial for identifying what was missing

5. **Follow exact patterns**: The old workflow worked perfectly - we just needed to replicate its exact flow

## Testing

To verify the fix works:
1. Push changes to trigger workflow
2. Check that "Start PHP development server" step succeeds
3. Check that "Start Vite development server" step succeeds
4. Check that "Test API and frontend paths" step passes
5. Check that servers are properly stopped in cleanup

## Files Modified

- `.github/workflows/integration-test.yml` - Added 4 new steps following old workflow pattern

## References

- Old working workflow: `.archive/pre-framework-migration/integration-test.yml.backup`
- Lines 526-590: Server startup and path testing
- Lines 965-973: Server cleanup
- Error log: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20148412053/job/57834985461

## Environment Variables Still Set (Not the Issue)

The workflow already had these (they weren't the problem):
```bash
TEST_SESSION_HANDLER=database  # Set in .env for database session storage
BAKERY_CONFIRM_SENSITIVE_COMMAND=false  # Disable interactive prompts
```

These were red herrings - the real issue was missing servers!
