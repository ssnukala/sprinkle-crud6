# Admin Login Fix Summary

**Issue**: Admin login failing in CI integration tests
**Date**: 2025-12-13
**PR**: copilot/fix-admin-login-issue

## Problem

The CI integration tests were failing with this error:

```
❌ Authentication failed: Still on login page
   Current URL: http://localhost:8080/account/sign-in
Error: Process completed with exit code 1.
```

## Root Cause

The `run-seeds.php` script was not creating the admin user, even though the configuration file `integration-test-seeds.json` had an `admin_user` section defined:

```json
"admin_user": {
    "enabled": true,
    "username": "admin",
    "password": "admin123",
    "email": "admin@example.com",
    "firstName": "Admin",
    "lastName": "User",
    "description": "Create admin user for testing authenticated routes"
}
```

The script was only running the seed classes but ignoring this configuration section entirely.

## Solution

### 1. Updated `run-seeds.php` Script

Added logic to handle the `admin_user` section after all seeds complete successfully:

```php
// Create admin user if configured
if (isset($config['admin_user']) && ($config['admin_user']['enabled'] ?? false)) {
    echo "=========================================\n";
    echo "Creating Admin User\n";
    echo "=========================================\n";
    
    $adminConfig = $config['admin_user'];
    $username = $adminConfig['username'] ?? 'admin';
    $password = $adminConfig['password'] ?? 'admin123';
    $email = $adminConfig['email'] ?? 'admin@example.com';
    $firstName = $adminConfig['firstName'] ?? 'Admin';
    $lastName = $adminConfig['lastName'] ?? 'User';
    
    // Build create:admin-user command
    $command = sprintf(
        "php bakery create:admin-user --username=%s --password=%s --email=%s --firstName=%s --lastName=%s 2>&1",
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($email),
        escapeshellarg($firstName),
        escapeshellarg($lastName)
    );
    
    // Execute and handle errors appropriately
    // ...
}
```

**Key Features**:
- Reads credentials from config with sensible defaults
- Uses UserFrosting's `bakery create:admin-user` command
- Handles "already exists" errors gracefully (non-fatal)
- Fails with clear error if user creation fails for other reasons

### 2. Enhanced `login-admin.js` Script

Added better error diagnostics when login fails:

```javascript
if (currentUrl.includes('/account/sign-in')) {
    console.error('❌ Authentication failed: Still on login page');
    console.error('   Current URL:', currentUrl);
    
    // Take screenshot for debugging
    try {
        const errorScreenshotPath = '/tmp/login-error-screenshot.png';
        await page.screenshot({ path: errorScreenshotPath, fullPage: true });
        console.error(`📸 Error screenshot saved: ${errorScreenshotPath}`);
    } catch (screenshotError) {
        console.error('⚠️  Could not save error screenshot');
    }
    
    // Check for error messages on the page
    const pageContent = await page.content();
    if (pageContent.includes('Invalid username or password') || 
        pageContent.includes('error') || 
        pageContent.includes('Error')) {
        console.error('⚠️  Login form may contain error messages');
    }
    
    await browser.close();
    process.exit(1);
}
```

**Key Features**:
- Captures screenshot on login failure for debugging
- Checks page content for error messages
- Provides better diagnostic information in CI logs

## Files Changed

1. `.github/testing-framework/scripts/run-seeds.php`
   - Added admin user creation after seeds complete
   - 57 lines added, 3 lines removed

2. `.github/testing-framework/scripts/login-admin.js`
   - Added error diagnostics on login failure
   - 18 lines added

## Testing

The fix will be tested in CI by:
1. Running the integration test workflow
2. Verifying that `run-seeds.php` creates the admin user
3. Confirming that `login-admin.js` successfully authenticates
4. Checking that authenticated API/frontend tests pass

## Expected CI Output

After the fix, we should see:

```
=========================================
Creating Admin User
=========================================
Username: admin
Email: admin@example.com
First Name: Admin
Last Name: User

✅ Admin user created successfully
```

And then:

```
========================================
Admin Login - Establishing Authenticated Session
========================================
Base URL: http://localhost:8080
Username: admin
State file: /tmp/admin-auth-state.json

📍 Navigating to login page...
✅ Login page loaded
🔐 Logging in...
✅ Logged in successfully
🔍 Verifying authentication...
✅ Authentication verified
   Current URL: http://localhost:8080/dashboard (or other landing page)
💾 Saving authenticated browser state...
✅ Browser state saved to: /tmp/admin-auth-state.json
✅ Saved 2 cookie(s):
   - uf_session: abc123...

========================================
✅ Admin login successful
========================================
```

## Reference

The old working script at `.archive/pre-framework-migration/scripts-backup/take-authenticated-screenshots.js` had the same login logic, confirming that the approach is correct. The only issue was missing admin user creation.

## Related Issues

- CI workflow run: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20185298492/job/57953972031
- Integration test configuration: `.github/config/integration-test-seeds.json`
- Workflow definition: `.github/workflows/integration-test.yml`
