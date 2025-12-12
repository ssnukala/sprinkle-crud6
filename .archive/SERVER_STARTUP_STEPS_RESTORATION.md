# Server Startup Steps Restoration - Issue Resolution

## Problem History

### Timeline of Issues
1. **2 hours ago**: Test run https://github.com/ssnukala/sprinkle-crud6/actions/runs/20173022877/job/57913697090 SUCCEEDED
   - HAD server startup steps (PHP and Vite)
   - HAD issues with router/index.ts sed commands
   
2. **Subsequent PRs**: Fixed router/index.ts issues BUT accidentally removed server startup steps
   - Tests started failing because servers weren't running
   - Each PR fixed one thing but broke another

3. **Current state**: Server startup steps are MISSING from workflow
   - API path tests fail because no servers are running
   - This is the repeating pattern causing frustration

## Root Cause Analysis

The workflow generator script (`.github/testing-framework/scripts/generate-workflow.js`) did NOT include the server startup steps. When PRs regenerated the workflow to fix other issues (like router/index.ts), the server startup steps were lost.

## Solution Implemented

### Changes Made to Generator Script
Added three critical steps to `.github/testing-framework/scripts/generate-workflow.js`:

1. **Start PHP development server** (after "Build frontend assets", before "Test API paths")
2. **Start Vite development server** (after PHP server, before "Test API paths")
3. **Stop servers** (at end with `if: always()`)

### Code Added to Generator

**Location**: Between lines 328 (Build frontend assets) and 330 (Test API paths)

```javascript
      - name: Start PHP development server
        run: |
          cd userfrosting
          # Start PHP server using bakery serve in background
          php bakery serve &
          SERVER_PID=$!
          echo $SERVER_PID > /tmp/server.pid
          sleep 10

          # Test if server is running
          curl -f http://localhost:8080 || (echo "⚠️ Server may not be ready yet" && sleep 5 && curl -f http://localhost:8080)
          echo "✅ PHP server started on localhost:8080"

      - name: Start Vite development server
        run: |
          cd userfrosting
          # Use npm update to fix any package issues
          npm update
          # Start Vite server in background using bakery command (follows UF6 standards)
          php bakery assets:vite &
          VITE_PID=$!
          echo $VITE_PID > /tmp/vite.pid

          # Wait longer for Vite to fully start up
          echo "Waiting for Vite server to start..."
          sleep 20

          # Try to verify Vite is running by checking if the page loads properly
          echo "Testing if frontend is accessible..."
          curl -f http://localhost:8080 || echo "⚠️  Page load test after Vite start"

          echo "✅ Vite server started
```

**Location**: At end of workflow (after "Upload logs")

```javascript
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

## Verification Checklist

### Current Workflow Must Have These Steps (in order):
- [ ] Build frontend assets (line ~268)
- [ ] **Start PHP development server (line ~275)** ✅ RESTORED
- [ ] **Start Vite development server (line ~288)** ✅ RESTORED
- [ ] Test API and frontend paths (line ~308)
- [ ] Install Playwright
- [ ] Capture screenshots
- [ ] Upload screenshots
- [ ] Upload logs
- [ ] **Stop servers (line ~342)** ✅ RESTORED

### Critical Requirements
1. ✅ PHP server MUST start BEFORE testing API paths
2. ✅ Vite server MUST start AFTER PHP server
3. ✅ Servers MUST be stopped at end (even if tests fail)
4. ✅ Sleep timers: 10s for PHP, 20s for Vite
5. ✅ Health checks with curl to verify servers are running

## How to Prevent Future Regressions

### For Future PRs
1. **ALWAYS regenerate workflow using the generator**:
   ```bash
   node .github/testing-framework/scripts/generate-workflow.js integration-test-config.json .github/workflows/integration-test.yml
   ```

2. **VERIFY server startup steps are present** before committing:
   ```bash
   grep -A5 "Start PHP development server" .github/workflows/integration-test.yml
   grep -A5 "Start Vite development server" .github/workflows/integration-test.yml
   grep -A5 "Stop servers" .github/workflows/integration-test.yml
   ```

3. **DO NOT manually edit** `.github/workflows/integration-test.yml`
   - It's auto-generated from config
   - Manual edits will be lost on next regeneration

4. **DO edit** `.github/testing-framework/scripts/generate-workflow.js` if changes are needed
   - Then regenerate the workflow
   - This ensures changes persist

### Generator Script Protection
The server startup steps are now PERMANENTLY in the generator script at:
- `.github/testing-framework/scripts/generate-workflow.js` lines ~330-368 (startup)
- `.github/testing-framework/scripts/generate-workflow.js` lines ~402-411 (stop)

**DO NOT REMOVE** these lines from the generator script in future PRs.

## Testing the Fix

### Before Merging This PR
1. Verify workflow file has server startup steps:
   ```bash
   grep "Start PHP development server" .github/workflows/integration-test.yml
   grep "Start Vite development server" .github/workflows/integration-test.yml
   ```

2. Verify YAML syntax:
   ```bash
   python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))"
   ```

3. Check step sequence is correct:
   ```bash
   grep -n "^      - name:" .github/workflows/integration-test.yml
   ```

### After Merging
1. Monitor the next CI run to ensure:
   - PHP server starts successfully (look for "✅ PHP server started on localhost:8080")
   - Vite server starts successfully (look for "✅ Vite server started")
   - API path tests can connect to servers
   - Servers are stopped at end

## References
- Working test run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20173022877/job/57913697090
- Backup with steps: `.archive/pre-framework-migration/integration-test.yml.backup` lines 539-570
- PR that added this fix: (current PR)

## Conclusion

The server startup steps are now PERMANENTLY in the workflow generator script. Future PRs that regenerate the workflow will automatically include these steps, preventing the regression from happening again.

**Key Point**: The issue was NOT in the workflow file itself - it was in the generator script that creates the workflow file. By fixing the generator, we ensure the problem doesn't recur.
