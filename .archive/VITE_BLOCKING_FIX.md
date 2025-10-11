# Integration Test Workflow Fix - Vite Dev Server Blocking Issue

## Date
2025-10-11

## Issue
The integration test workflow was hanging indefinitely at the "Build frontend assets" step, as reported in:
- GitHub Actions Run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18435095758/job/52527490452

### Problem Details
The workflow step was running:
```bash
cd userfrosting
npm update
php bakery assets:vite
echo "✅ Assets built successfully"
```

**Root Cause:**
- The `php bakery assets:vite` command internally executes `npm run vite:dev`
- `npm run vite:dev` starts the Vite development server in **foreground mode**
- Vite starts successfully on port 5173 but never returns control
- The workflow waits indefinitely because the command never completes
- This is evident from the log output showing:
  ```
  VITE v6.3.6  ready in 633 ms
  ➜  Local:   http://localhost:5173/assets/
  ➜  Network: http://10.1.0.132:5173/assets/
  ```
  But the workflow step never finishes

## Solution

### Final Approach (Following UF6 Standards)
Run `php bakery assets:vite` in **background mode** using the `&` operator, similar to how `php bakery serve` is run. This follows UserFrosting 6 standards while preventing the workflow from blocking.

**File: `.github/workflows/integration-test.yml`**

**Changes:**
1. Removed the separate "Build frontend assets" step
2. Moved `npm update` into the "Start Vite development server" step
3. Changed command from `npm run dev &` to `php bakery assets:vite &`

```yaml
- name: Start Vite development server
  run: |
    cd userfrosting
    # Use npm update to fix any package issues
    npm update
    # Start Vite server in background using bakery command (follows UF6 standards)
    php bakery assets:vite &
    VITE_PID=$!
    echo $VITE_PID > /tmp/vite.pid
    sleep 10
    echo "✅ Vite server started"
```

### Why This Works

1. **Follows UF6 standards**: Uses the official `php bakery assets:vite` command
2. **Non-blocking**: The `&` operator runs the command in background
3. **Consistent pattern**: Matches how `php bakery serve &` is run
4. **Process management**: Captures PID for proper cleanup
5. **Same functionality**: Provides Vite dev server with HMR

### Benefits

✅ **Follows UserFrosting 6 standards** - uses official bakery command  
✅ **Non-blocking workflow** - runs in background with `&` operator  
✅ **Consistent pattern** - matches how `php bakery serve &` is run  
✅ **Vite dev server available** - provides HMR for development  
✅ **Proper process management** - PID captured for cleanup  

## Comparison

### Development Server Commands

| Command | Mode | Returns Control | Use Case |
|---------|------|----------------|----------|
| `php bakery assets:vite` | Dev server (foreground) | ❌ No | Local development only |
| `php bakery assets:vite &` | Dev server (background) | ✅ Yes | **CI/CD workflows** ✨ |
| `npm run vite:dev` | Dev server (foreground) | ❌ No | Alternative local dev |
| `npm run dev &` | Dev server (background) | ✅ Yes | Alternative CI/CD |
| `npm run build` | Production build | ✅ Yes | Production builds |

### Workflow Pattern

The correct CI/CD pattern following UF6 standards:
1. **Start PHP server in background** → `php bakery serve &`
2. **Start Vite server in background** → `php bakery assets:vite &` (follows UF6 standards)
3. **Run tests** → Tests execute against both servers
4. **Cleanup** → Stop both servers

## Testing

### Validation Steps
- ✅ YAML syntax validated with Python yaml parser
- ✅ Change follows UserFrosting 6 standards using bakery command
- ✅ Maintains existing workflow structure
- ✅ Background execution prevents blocking
- ⏳ Next workflow run will verify the fix

### Expected Behavior
1. `npm update` runs and completes
2. `php bakery assets:vite &` starts Vite dev server in background
3. Workflow proceeds to run tests immediately
4. All tests execute successfully
5. Screenshots are captured
6. Workflow completes without hanging

## Related Files
- `.github/workflows/integration-test.yml` - Integration test workflow (FIXED)
- `INTEGRATION_TESTING.md` - User-facing integration testing guide (UPDATED)
- `QUICK_TEST_GUIDE.md` - Quick test reference (UPDATED)
- `.archive/BAKERY_COMMANDS_INTEGRATION_SUMMARY.md` - Previous bakery commands integration

## Reference
- **Issue**: Integration test hanging at `php bakery assets:vite` step
- **Log**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18435095758/job/52527490452
- **Solution**: Run bakery command in background with `&` operator
- **UserFrosting 6**: Bakery commands are the standard way to manage assets

## Notes

### Why Use `php bakery assets:vite &`?
This approach:
- ✅ **Follows UserFrosting 6 standards** - uses official bakery command
- ✅ **Non-blocking** - runs in background with `&` operator
- ✅ **Consistent** - matches pattern of `php bakery serve &`
- ✅ **Simple** - no need for separate build step
- ✅ **HMR enabled** - provides hot module replacement for dev

### Previous Approach (Not Following UF6 Standards)
Initially considered using `npm run build` for a production build, but this doesn't follow UserFrosting 6 patterns. The bakery commands are the recommended approach for asset management in UF6.

## Conclusion

This fix follows UserFrosting 6 standards by:
- Using the official `php bakery assets:vite` command
- Running it in background mode with `&` to prevent blocking
- Maintaining consistency with how `php bakery serve` is run

The change is minimal and maintains all existing functionality while resolving the indefinite hang issue and following UF6 best practices.
