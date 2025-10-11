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

### Change Made
**File: `.github/workflows/integration-test.yml`**

**BEFORE:**
```yaml
- name: Build frontend assets
  run: |
    cd userfrosting
    # Use npm update to fix any package issues
    npm update
    # Build assets using bakery command
    php bakery assets:vite
    echo "✅ Assets built successfully"
```

**AFTER:**
```yaml
- name: Build frontend assets
  run: |
    cd userfrosting
    # Use npm update to fix any package issues
    npm update
    # Build assets for production (this command completes and returns)
    npm run build
    echo "✅ Assets built successfully"
```

### Why This Works

1. **`npm run build`** (or `vite build`) performs a **one-time production build**
   - Compiles all assets
   - Completes and returns control to the workflow
   - Generates static assets in the `public/` directory

2. **Separate background Vite server** is already configured in a later step:
   ```yaml
   - name: Start Vite development server
     run: |
       cd userfrosting
       npm run dev &
       VITE_PID=$!
       echo $VITE_PID > /tmp/vite.pid
   ```
   - This step properly runs Vite in **background mode** using `&`
   - Provides hot module replacement (HMR) for development
   - Doesn't block the workflow

### Benefits

✅ **Asset building completes and returns** - workflow can proceed  
✅ **Production-optimized build** - generates minified, optimized assets  
✅ **Vite dev server still available** - runs in background for HMR  
✅ **No workflow blocking** - tests can run immediately after build  
✅ **Proper CI/CD pattern** - build once, serve separately  

## Comparison

### Development Server Commands

| Command | Mode | Returns Control | Use Case |
|---------|------|----------------|----------|
| `php bakery assets:vite` | Dev server (foreground) | ❌ No | Local development only |
| `npm run vite:dev` | Dev server (foreground) | ❌ No | Local development only |
| `npm run dev &` | Dev server (background) | ✅ Yes | CI with background process |
| `npm run build` | Production build | ✅ Yes | **CI/CD builds** ✨ |
| `vite build` | Production build | ✅ Yes | **CI/CD builds** ✨ |

### Workflow Pattern

The correct CI/CD pattern is:
1. **Build assets once** → `npm run build` (completes)
2. **Start PHP server in background** → `php bakery serve &`
3. **Start Vite dev server in background** → `npm run dev &` (optional for HMR)
4. **Run tests** → Tests execute against both servers
5. **Cleanup** → Stop both servers

## Testing

### Validation Steps
- ✅ YAML syntax validated with Python yaml parser
- ✅ Change targets the exact blocking command
- ✅ Maintains existing workflow structure
- ✅ Background Vite server step remains unchanged
- ⏳ Next workflow run will verify the fix

### Expected Behavior
1. `npm run build` completes in ~30 seconds
2. Workflow proceeds to start PHP server
3. Workflow proceeds to start Vite dev server in background
4. All tests execute successfully
5. Screenshots are captured
6. Workflow completes without hanging

## Related Files
- `.github/workflows/integration-test.yml` - Integration test workflow (FIXED)
- `INTEGRATION_TESTING.md` - User-facing integration testing guide
- `.archive/BAKERY_COMMANDS_INTEGRATION_SUMMARY.md` - Previous bakery commands integration

## Reference
- **Issue**: Integration test hanging at `php bakery assets:vite` step
- **Log**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18435095758/job/52527490452
- **Vite Documentation**: https://vitejs.dev/guide/build.html
- **UserFrosting 6**: Uses Vite for asset management

## Notes

### Why Not Use `php bakery assets:vite`?
The `assets:vite` bakery command is designed for **local development** where you want to:
- Start the Vite dev server interactively
- Keep it running in the foreground
- Watch for file changes
- Provide HMR to the browser

In **CI/CD environments**, we need:
- Commands that complete and return
- Production-optimized builds
- Background processes when needed
- Clear separation between build and serve phases

### Alternative Solutions Considered
1. **Run `php bakery assets:vite &` in background** - Not recommended, as the command is meant for interactive use
2. **Use `timeout` to kill the command** - Hacky and doesn't follow best practices
3. **Use `npm run build`** - ✅ **CHOSEN** - Proper CI/CD pattern

## Conclusion

This fix follows the standard CI/CD pattern of:
- Building assets as a discrete step that completes
- Running servers in background when needed
- Keeping the workflow non-blocking

The change is minimal (one line) and maintains all existing functionality while resolving the indefinite hang issue.
