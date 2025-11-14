# Quick Test Guide - Integration Test Fix

This guide provides quick validation steps to verify the integration test fix works correctly.

## Local Testing (Optional)

### 1. Verify Script Syntax
```bash
cd /home/runner/work/sprinkle-crud6/sprinkle-crud6
php -l .github/scripts/check-seeds.php
php -l .github/scripts/test-seed-idempotency.php
```
Expected: "No syntax errors detected" for both files

### 2. Verify YAML Syntax
```bash
python3 -c "import yaml; yaml.safe_load(open('.github/workflows/integration-test.yml'))"
```
Expected: No output (successful parse)

### 3. Check Script Permissions
```bash
ls -la .github/scripts/
```
Expected: Both scripts should have execute permissions (755)

## CI Testing (Required)

### Push to GitHub
The fix has been committed to branch `copilot/fix-integration-test-issues`.

When pushed to GitHub, the integration test workflow will:
1. Run automatically on push to the branch
2. Execute all setup steps (composer, npm, migrations, seeds)
3. Run the new validation scripts
4. Report success or failure

### Expected Workflow Behavior

#### Before Fix (Failed)
```
PHP Warning:  require(app/app.php): Failed to open stream: No such file or directory
PHP Fatal error:  Uncaught Error: Failed opening required 'app/app.php'
Process completed with exit code 255.
```

#### After Fix (Success)
```
=========================================
Validating CRUD6 Seed Data
=========================================

Checking crud6-admin role...
✅ crud6-admin role exists
   Name: CRUD6 Administrator
   Description: This role is meant for "CRUD6 administrators"...

Checking CRUD6 permissions...
✅ create_crud6 permission exists
✅ delete_crud6 permission exists
✅ update_crud6_field permission exists
✅ uri_crud6 permission exists
✅ uri_crud6_list permission exists
✅ view_crud6_field permission exists

Checking permission assignments to crud6-admin role...
✅ crud6-admin role has 6 permissions assigned

Checking permission assignments to site-admin role...
✅ site-admin role has CRUD6 permissions (6 permissions)

=========================================
✅ All CRUD6 seed data validated successfully
=========================================
```

### Validation Checklist

Check the workflow run for these key indicators:

- [ ] "Validate CRUD6 seed data" step completes successfully
- [ ] All 6 CRUD6 permissions are found:
  - create_crud6
  - delete_crud6
  - update_crud6_field
  - uri_crud6
  - uri_crud6_list
  - view_crud6_field
- [ ] crud6-admin role exists with correct name and description
- [ ] crud6-admin role has 6 permissions assigned
- [ ] site-admin role has CRUD6 permissions assigned
- [ ] "Test seed idempotency" step completes successfully
- [ ] Seed counts remain the same after re-running seeds
- [ ] No PHP errors or warnings
- [ ] Exit code is 0 for both validation steps

## Troubleshooting

### If validation still fails:

1. **Check environment variables**: Ensure DB_HOST, DB_NAME, DB_USER, DB_PASSWORD are set correctly
2. **Check database connection**: Verify the database service is running
3. **Check seeds ran**: Ensure DefaultRoles and DefaultPermissions seeds executed successfully
4. **Check script location**: Scripts should be copied to UserFrosting project root
5. **Check PHP version**: UserFrosting 6 requires PHP 8.1+

### Common Issues

**Issue**: "vendor/autoload.php not found"
- **Cause**: Script is not running from UserFrosting project root
- **Fix**: Ensure workflow copies script and runs from `cd userfrosting` directory

**Issue**: "Class not found" errors
- **Cause**: Composer dependencies not installed
- **Fix**: Ensure `composer install` completed successfully before validation

**Issue**: "Connection refused" database errors
- **Cause**: Database service not ready or wrong credentials
- **Fix**: Check database service health and environment variables

## Success Criteria

The fix is successful when:
1. Integration test workflow completes without errors
2. All validation checks pass
3. Seed idempotency test passes
4. No PHP warnings or errors in logs
5. Workflow shows green checkmark on GitHub

## Reference

- **Failed Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/19347216710
- **Issue**: Integration test validation scripts couldn't bootstrap UserFrosting 6
- **Solution**: Created standalone scripts using Illuminate Capsule for database access
- **Files**: 
  - `.github/scripts/check-seeds.php`
  - `.github/scripts/test-seed-idempotency.php`
  - `.github/workflows/integration-test.yml`

## Next Steps

1. Verify workflow runs successfully on GitHub Actions
2. If successful, merge PR to main branch
3. Delete temporary validation files if any exist
4. Update documentation if needed
