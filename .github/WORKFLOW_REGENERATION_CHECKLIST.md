# Workflow Regeneration Checklist

## ⚠️ CRITICAL: Always Follow This Process

This checklist prevents the pattern of "fixing one thing, breaking another" that has been causing issues.

## When to Regenerate the Workflow

Regenerate `.github/workflows/integration-test.yml` whenever you:
- ✅ Modify `.github/testing-framework/scripts/generate-workflow.js`
- ✅ Modify `integration-test-config.json`
- ✅ Add new testing steps
- ✅ Change route configuration patterns
- ✅ Update Vite dependencies

## Regeneration Command

```bash
node .github/testing-framework/scripts/generate-workflow.js integration-test-config.json .github/workflows/integration-test.yml
```

## Verification Checklist (Run BEFORE Committing)

### 1. Check AUTO-GENERATED Comment
```bash
head -4 .github/workflows/integration-test.yml
```
Should see: `# AUTO-GENERATED from integration-test-config.json`

### 2. Verify Server Startup Steps Are Present
```bash
grep -n "Start PHP development server\|Start Vite development server\|Stop servers" .github/workflows/integration-test.yml
```
Should see THREE lines:
- `Start PHP development server` (around line 275)
- `Start Vite development server` (around line 288)
- `Stop servers` (around line 342)

### 3. Verify Step Sequence
```bash
grep "^      - name:" .github/workflows/integration-test.yml | grep -n "Build frontend\|Start PHP\|Start Vite\|Test API\|Stop servers"
```
Should see correct order:
- Build frontend assets
- Start PHP development server
- Start Vite development server
- Test API and frontend paths
- ... (other steps)
- Stop servers

### 4. Validate YAML Syntax
```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))"
```
Should output: `✅ YAML valid` (or no output if valid)

### 5. Count Total Steps
```bash
grep -c "^      - name:" .github/workflows/integration-test.yml
```
Should be: 27 steps (streamlined workflow)

## Quick Verification Script

Run this one-liner to check everything:

```bash
cd /home/runner/work/sprinkle-crud6/sprinkle-crud6 && \
echo "=== WORKFLOW VERIFICATION ===" && \
grep -q "AUTO-GENERATED" .github/workflows/integration-test.yml && echo "✅ Auto-generated comment present" || echo "❌ Missing auto-generated comment" && \
grep -q "Start PHP development server" .github/workflows/integration-test.yml && echo "✅ PHP server startup present" || echo "❌ Missing PHP server startup" && \
grep -q "Start Vite development server" .github/workflows/integration-test.yml && echo "✅ Vite server startup present" || echo "❌ Missing Vite server startup" && \
grep -q "Stop servers" .github/workflows/integration-test.yml && echo "✅ Server stop present" || echo "❌ Missing server stop" && \
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))" && echo "✅ YAML syntax valid" || echo "❌ YAML syntax invalid" && \
echo "=== VERIFICATION COMPLETE ==="
```

## ⛔ DO NOT

- ❌ Manually edit `.github/workflows/integration-test.yml` 
  - It's auto-generated and changes will be lost
  - Edit the generator script or config instead

- ❌ Skip regeneration after modifying generator script
  - This causes the "fixed one thing, broke another" pattern
  - Always regenerate and verify

- ❌ Remove server startup steps from generator script
  - They are CRITICAL for tests to run
  - Located at lines ~330-368 and ~402-411

## ✅ DO

- ✅ Modify the generator script if you need workflow changes
- ✅ Regenerate the workflow after generator changes
- ✅ Run ALL verification checks before committing
- ✅ Include both generator AND workflow in your commit
- ✅ Test the workflow in CI before merging

## Critical Server Startup Steps

These MUST be present in the generated workflow:

### Step 1: Start PHP Server (after Build, before Tests)
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

### Step 2: Start Vite Server (after PHP Server, before Tests)
```yaml
- name: Start Vite development server
  run: |
    cd userfrosting
    npm update
    php bakery assets:vite &
    VITE_PID=$!
    echo $VITE_PID > /tmp/vite.pid
    sleep 20
    curl -f http://localhost:8080 || echo "⚠️  Page load test after Vite start"
    echo "✅ Vite server started"
```

### Step 3: Stop Servers (at end, always run)
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

## History Reference

See `.archive/SERVER_STARTUP_STEPS_RESTORATION.md` for:
- Complete history of the issue
- Why these steps are critical
- How they were restored
- Prevention guidelines

## Questions?

If unsure about workflow changes:
1. Check the generator script: `.github/testing-framework/scripts/generate-workflow.js`
2. Check the config: `integration-test-config.json`
3. Review documentation: `.archive/SERVER_STARTUP_STEPS_RESTORATION.md`
4. Look at backup: `.archive/pre-framework-migration/integration-test.yml.backup`

## Remember

**The workflow is AUTO-GENERATED. Edit the generator, not the workflow.**
