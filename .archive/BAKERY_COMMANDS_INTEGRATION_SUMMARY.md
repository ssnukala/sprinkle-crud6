# Integration Testing with Bakery Commands - Summary

**Date**: 2025-10-11  
**Issue**: Copy integration testing approach from ssnukala/sprinkle-learntegrate  
**PR**: #[TBD]

## Overview

Updated the integration testing workflow and documentation to use UserFrosting 6 bakery commands following the official installation pattern. This provides a more standardized and maintainable approach for testing sprinkle-crud6.

## Problem Statement

The previous integration testing approach used manual PHP server and direct npm commands. The goal was to update to:
1. Use `php bakery create:admin-user` to create admin user before testing
2. Use `php bakery serve` for PHP server
3. Run Vite development server alongside PHP server
4. Use `php bakery assets:vite` for building assets
5. Document the `php bakery bake` command
6. Ensure screenshots are captured and easily accessible

## Changes Made

### 1. GitHub Workflow (.github/workflows/integration-test.yml)

#### Admin User Creation
**Added new step after seeding:**
```yaml
- name: Create admin user
  run: |
    cd userfrosting
    # Create admin user for testing
    php bakery create:admin-user \
      --username=admin \
      --password=admin123 \
      --email=admin@example.com \
      --firstName=Admin \
      --lastName=User
    echo "✅ Admin user created successfully"
```

**Benefits:**
- Creates consistent admin credentials for testing
- Uses official bakery command
- Credentials documented: admin / admin123

#### Asset Building
**Changed from:**
```yaml
npm run build || echo "⚠️ Build failed but continuing with tests"
```

**Changed to:**
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

**Benefits:**
- Uses official bakery command following UF6 standards
- Includes `npm update` to fix known package issues
- More reliable asset compilation

#### Server Startup
**Changed from:**
```yaml
- name: Start PHP development server
  run: |
    cd userfrosting
    # Start PHP server in background
    php -S localhost:8080 -t public > /tmp/server.log 2>&1 &
    SERVER_PID=$!
    echo $SERVER_PID > /tmp/server.pid
    sleep 5
```

**Changed to:**
```yaml
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
    # Start Vite server in background for hot module replacement
    npm run dev &
    VITE_PID=$!
    echo $VITE_PID > /tmp/vite.pid
    sleep 10
    echo "✅ Vite server started"
```

**Benefits:**
- Uses official `php bakery serve` command
- Runs both PHP and Vite servers simultaneously
- Provides hot module replacement for frontend
- More robust server readiness check

#### Server Cleanup
**Changed from:**
```yaml
- name: Stop PHP server
  if: always()
  run: |
    if [ -f /tmp/server.pid ]; then
      kill $(cat /tmp/server.pid) || true
    fi
```

**Changed to:**
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

**Benefits:**
- Properly cleans up both servers
- Prevents orphaned processes

### 2. Integration Testing Documentation (INTEGRATION_TESTING.md)

#### Admin User Creation
**Added section after seeding:**
```bash
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com \
  --firstName=Admin \
  --lastName=User
```

**Benefits:**
- Documents exact commands for manual testing
- Provides consistent credentials
- Matches CI workflow approach

#### Asset Building
**Enhanced section with:**
```bash
# Update npm packages to fix any known issues
npm update

# Build frontend assets using bakery command (recommended)
php bakery assets:vite

# OR build manually with npm
npm run vite:build

# OR for development with hot reload:
npm run vite:dev
```

**Added new subsection:**
```markdown
**Alternative: Using php bakery bake**

The `php bakery bake` command is a convenience command that combines multiple operations:

```bash
# Build assets and clear cache in one command
php bakery bake
```

This command:
- Builds frontend assets using Vite
- Clears the application cache
- Optimizes the application for production/testing
```

**Benefits:**
- Documents multiple approaches
- Explains `php bakery bake` command
- Recommends official bakery commands

#### Server Startup
**Updated from:**
```bash
# In one terminal, run the PHP server:
php -S localhost:8080 -t public

# In another terminal (if using Vite for development):
npm run vite:dev
```

**Updated to:**
```bash
**Option 1: Using Bakery Commands (Recommended)**

# Terminal 1: Start PHP server using bakery
php bakery serve

# Terminal 2: Start Vite development server
npm run vite:dev

**Option 2: Manual Server Start**

# Terminal 1: Run the PHP server manually
php -S localhost:8080 -t public

# Terminal 2: Start Vite for hot module replacement
npm run vite:dev
```

**Benefits:**
- Recommends bakery command approach
- Provides fallback option
- Documents both servers running simultaneously
- Explains purpose of each server

#### Screenshot Viewing
**Enhanced instructions:**
```markdown
**Viewing CI Test Results and Screenshots**:

1. Go to the repository on GitHub: https://github.com/ssnukala/sprinkle-crud6
2. Navigate to the **Actions** tab
3. Select the latest **"Integration Test with UserFrosting 6"** workflow run
4. View test results in the workflow logs
5. **Download screenshots**: 
   - Scroll to the bottom of the workflow run page
   - Look for the **"Artifacts"** section
   - Download **"integration-test-screenshots"** artifact (ZIP file)
   - Extract the ZIP to view screenshots:
     - `screenshot_groups_list.png` - Groups list page at `/crud6/groups`
     - `screenshot_group_detail.png` - Group detail page at `/crud6/groups/1`
   - Screenshots are retained for 30 days
```

**Benefits:**
- Clear step-by-step instructions
- Lists exact screenshot filenames
- Documents retention period
- Explains what each screenshot shows

### 3. Quick Test Guide (QUICK_TEST_GUIDE.md)

**Updated setup steps:**
```bash
# 5. Install and build
composer install
npm install
npm update
php bakery assets:vite

# 6. Setup database
cp app/.env.example app/.env
# Edit app/.env with database credentials
# Add BAKERY_CONFIRM_SENSITIVE_COMMAND=false for CI/CD
php bakery migrate
php bakery seed --force

# 7. Create admin user
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com \
  --firstName=Admin \
  --lastName=User

# 9. Start servers (in two separate terminals)
# Terminal 1: PHP server
php bakery serve

# Terminal 2: Vite dev server
npm run vite:dev

# 10. Test in browser (login with admin / admin123):
# - http://localhost:8080/crud6/groups
# - http://localhost:8080/crud6/groups/1
```

**Benefits:**
- Concise reference guide
- Uses bakery commands throughout
- Documents dual server approach
- Clear admin credentials

## Benefits of This Approach

### 1. Standardization
- Uses official UserFrosting 6 bakery commands
- Follows recommended installation patterns
- Aligns with UserFrosting documentation

### 2. Reliability
- `php bakery serve` provides proper server configuration
- `npm update` fixes known package issues
- Better server readiness checks

### 3. Development Experience
- Both PHP and Vite servers running provides better development workflow
- Hot module replacement for frontend changes
- Consistent approach between local and CI testing

### 4. Documentation
- Clear instructions for manual testing
- Documented admin credentials
- Screenshot viewing instructions
- Multiple options for different use cases

### 5. Maintainability
- Less custom scripts
- Uses framework-provided tools
- Easier to update when framework changes

## Testing

### Validation
- ✅ YAML syntax validated with Python yaml parser
- ✅ All markdown files updated consistently
- ✅ Commands tested in devcontainer environment

### Expected Workflow
1. Seed database with default data
2. Create admin user with bakery command
3. Build assets with `php bakery assets:vite`
4. Start PHP server with `php bakery serve`
5. Start Vite server with `npm run dev`
6. Run API endpoint tests (expect 401 for unauthenticated)
7. Run frontend route tests (pages load)
8. Capture screenshots with Playwright
9. Upload screenshots as artifacts
10. Stop both servers

### Manual Testing Steps
Follow the updated INTEGRATION_TESTING.md guide to manually verify:
1. Admin user creation works
2. Asset building completes successfully
3. Both servers start and run simultaneously
4. Pages load correctly
5. Screenshots can be viewed from CI artifacts

## Related Files

### Modified
- `.github/workflows/integration-test.yml` - Main workflow file
- `INTEGRATION_TESTING.md` - Comprehensive testing guide
- `QUICK_TEST_GUIDE.md` - Quick reference guide

### Reference
- `.devcontainer/README.md` - Documents bakery commands
- `.archive/INTEGRATION_TEST_UPDATE_SUMMARY.md` - Previous updates
- `.archive/INTEGRATION_TEST_FINAL_SUMMARY.md` - Original implementation

## Notes

### Admin Credentials
- Username: `admin`
- Password: `admin123`
- Email: `admin@example.com`
- These credentials are used for testing only

### Screenshot Locations
CI artifacts contain two screenshots:
- `screenshot_groups_list.png` - Groups list at `/crud6/groups`
- `screenshot_group_detail.png` - Group detail at `/crud6/groups/1`

### Bakery Commands Used
- `php bakery create:admin-user` - Create admin user
- `php bakery serve` - Start PHP development server
- `php bakery assets:vite` - Build frontend assets with Vite
- `php bakery bake` - Build assets and clear cache (documented but not used in CI)
- `php bakery migrate` - Run database migrations
- `php bakery seed` - Seed database with initial data

## Future Improvements

1. **Authentication in CI**: Could add authenticated screenshot capture to show actual CRUD pages
2. **Multiple Screenshots**: Could capture create/edit/delete modals
3. **Performance Testing**: Could add performance metrics to workflow
4. **Multi-browser Testing**: Could test with multiple browsers using Playwright
5. **API Testing with Auth**: Could add authenticated API endpoint tests

## Conclusion

This update brings the integration testing approach in line with UserFrosting 6 best practices by using official bakery commands throughout. The dual server setup (PHP + Vite) provides a more realistic development environment and better testing coverage. The enhanced documentation makes it easier for contributors to understand and use the testing workflow.
