# Quick Start: Network Request Analysis

## Running the Analysis Locally

### Prerequisites

1. UserFrosting 6 application with CRUD6 sprinkle installed
2. Node.js 18+ installed
3. Playwright installed: `npm install playwright`

### Steps

1. **Start your UserFrosting 6 application**:
   ```bash
   cd /path/to/userfrosting
   php bakery serve &
   php bakery assets:vite &
   ```

2. **Run the analysis script**:
   ```bash
   cd /path/to/sprinkle-crud6
   node .github/scripts/take-authenticated-screenshots.js http://localhost:8080 admin admin123
   ```

3. **View results**:
   - Console output shows immediate analysis
   - Screenshots saved to `/tmp/screenshot_*.png`
   - Network log saved to `/tmp/network_requests.json`

### What You'll See

```
========================================
Network Request Analysis
========================================

### Groups List (/crud6/groups)

📊 Schema API Calls: 1
   1. GET /api/crud6/groups/schema
      Context: list
      Include Related: false
      Status: 200

✅ YAML File Imports: 0 (Good - no redundant YAML imports)

🔌 CRUD6 API Calls: 1
   1. GET /api/crud6/groups
      Status: 200

📈 Summary:
   Total XHR/Fetch Requests: 2
   Schema API Calls: 1
   YAML Imports: 0 ✅
   CRUD6 API Calls: 1
```

### Understanding the Output

- **Schema API Calls**: Should be 1 per page (with proper caching)
- **YAML File Imports**: Should be 0 (validation adapter working correctly)
- **CRUD6 API Calls**: 1 for data fetch (list or detail)

### If You See Issues

#### Multiple Schema Calls
```
📊 Schema API Calls: 3  ← Problem!
```
**Fix**: Check schema caching in components

#### YAML Imports Detected
```
⚠️  YAML File Imports: 8 (SHOULD BE 0!)
   1. group.yaml
   2. role.yaml
   ...
```
**Fix**: Ensure using `useCRUD6RegleAdapter` for validation

### Detailed Analysis

View the JSON file for complete details:
```bash
cat /tmp/network_requests.json | jq .
```

Or use the built-in analyzer:
```bash
node .github/scripts/analyze-network-log.js /tmp/network_requests.json
```

The analyzer will:
- Categorize all requests
- Detect issues automatically
- Provide recommendations
- Exit with error code if high severity issues found

Or open in your editor:
```bash
code /tmp/network_requests.json
```

## CI/CD Usage

The analysis runs automatically in GitHub Actions:

1. Go to: https://github.com/ssnukala/sprinkle-crud6/actions
2. Click on latest "Integration Test with UserFrosting 6" workflow
3. View console output in "Take screenshots" step
4. Download "network-request-log" artifact for JSON file

## Troubleshooting Common Issues

### Issue: "Authentication failed"
- Verify username and password are correct
- Check that admin user exists in database

### Issue: "No screenshots generated"
- Check that servers are running
- Verify URLs are accessible
- Review error messages in output

### Issue: "Connection refused"
- Ensure PHP server is running on port 8080
- Ensure Vite server is running
- Check firewall settings

## Next Steps

After running the analysis:

1. Review the output for issues
2. Check specific request URLs in the JSON log
3. Fix any identified problems
4. Re-run to verify fixes
5. Compare before/after network logs

## More Information

See `.archive/NETWORK_REQUEST_ANALYSIS.md` for comprehensive documentation including:
- Detailed interpretation guide
- Common issues and solutions
- Code patterns to fix problems
- Expected request patterns
