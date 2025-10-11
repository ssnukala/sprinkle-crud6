# Visual Comparison: Integration Testing with Bakery Commands

## Overview

This document provides a visual comparison of the changes made to integrate UserFrosting 6 bakery commands into the integration testing workflow.

---

## Change 1: Admin User Creation

### ❌ BEFORE (No dedicated admin user creation)

```yaml
- name: Seed database
  run: |
    cd userfrosting
    php bakery seed --force
```

**Problems:**
- ❌ No explicit admin user creation step
- ❌ Unclear what credentials to use for testing
- ❌ Inconsistent across different test runs

### ✅ AFTER (Explicit admin user creation)

```yaml
- name: Seed database
  run: |
    cd userfrosting
    php bakery seed --force

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
- ✅ Clear admin user credentials: **admin / admin123**
- ✅ Uses official bakery command
- ✅ Reproducible test environment
- ✅ Documented in all guides

---

## Change 2: Asset Building

### ❌ BEFORE (Direct npm command)

```yaml
- name: Build frontend assets
  run: |
    cd userfrosting
    npm run build || echo "⚠️ Build failed but continuing with tests"
```

**Problems:**
- ❌ Uses npm directly instead of bakery command
- ❌ Allows build to fail silently
- ❌ Doesn't fix known package issues
- ❌ Not aligned with UF6 standards

### ✅ AFTER (Bakery command with npm update)

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
- ✅ Uses official `php bakery assets:vite` command
- ✅ Runs `npm update` to fix package issues
- ✅ Fails properly if build has issues
- ✅ Follows UserFrosting 6 standards
- ✅ More reliable asset compilation

---

## Change 3: Server Startup

### ❌ BEFORE (Manual PHP server only)

```yaml
- name: Start PHP development server
  run: |
    cd userfrosting
    # Start PHP server in background
    php -S localhost:8080 -t public > /tmp/server.log 2>&1 &
    SERVER_PID=$!
    echo $SERVER_PID > /tmp/server.pid
    sleep 5
    
    # Test if server is running
    curl -f http://localhost:8080 || (cat /tmp/server.log && exit 1)
    echo "✅ PHP server started on localhost:8080"
```

**Problems:**
- ❌ Uses manual `php -S` command
- ❌ No Vite development server
- ❌ No hot module replacement
- ❌ Doesn't follow UF6 patterns
- ❌ Single server only

### ✅ AFTER (Bakery serve + Vite dev server)

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
- ✅ Uses official `php bakery serve` command
- ✅ Runs both PHP and Vite servers simultaneously
- ✅ Provides hot module replacement
- ✅ More realistic development environment
- ✅ Better server readiness checking
- ✅ Dual server setup matches production workflow

---

## Change 4: Server Cleanup

### ❌ BEFORE (PHP server only)

```yaml
- name: Stop PHP server
  if: always()
  run: |
    if [ -f /tmp/server.pid ]; then
      kill $(cat /tmp/server.pid) || true
    fi
```

**Problems:**
- ❌ Only stops PHP server
- ❌ No Vite server cleanup
- ❌ Potential orphaned processes

### ✅ AFTER (Both servers)

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
- ✅ Stops both PHP and Vite servers
- ✅ Proper cleanup of all processes
- ✅ Prevents orphaned processes
- ✅ Complete resource management

---

## Documentation Changes

### INTEGRATION_TESTING.md

#### Admin User Section

**BEFORE:**
```markdown
### 10. Seed Initial Data

php bakery seed --force

This creates:
- Default groups
- Default roles
- Default permissions
- Default admin user
```

**AFTER:**
```markdown
### 10. Seed Initial Data and Create Admin User

php bakery seed --force

# Create admin user
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com \
  --firstName=Admin \
  --lastName=User

This creates:
- Default groups
- Default roles
- Default permissions
- Admin user with credentials: **admin / admin123**
```

---

#### Asset Building Section

**BEFORE:**
```markdown
### 7. Install Dependencies and Build

npm install
npm run vite:build
```

**AFTER:**
```markdown
### 7. Install Dependencies and Build Assets

npm install
npm update
php bakery assets:vite

**Alternative: Using php bakery bake**

php bakery bake  # Build assets and clear cache
```

---

#### Server Startup Section

**BEFORE:**
```markdown
### 12. Start the Development Server

# In one terminal:
php -S localhost:8080 -t public

# In another terminal:
npm run vite:dev
```

**AFTER:**
```markdown
### 12. Start the Development Server

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

---

#### Screenshot Instructions

**BEFORE:**
```markdown
5. Download screenshot artifacts to see visual results
```

**AFTER:**
```markdown
5. **Download screenshots**: 
   - Scroll to the bottom of the workflow run page
   - Look for the **"Artifacts"** section
   - Download **"integration-test-screenshots"** artifact (ZIP file)
   - Extract the ZIP to view screenshots:
     - `screenshot_groups_list.png` - Groups list page at `/crud6/groups`
     - `screenshot_group_detail.png` - Group detail page at `/crud6/groups/1`
   - Screenshots are retained for 30 days
```

---

## QUICK_TEST_GUIDE.md Changes

### Setup Steps

**BEFORE:**
```bash
# 5. Install and build
composer install
npm install
npm run vite:build

# 6. Setup database
php bakery migrate
php bakery seed

# 8. Start server
php -S localhost:8080 -t public
```

**AFTER:**
```bash
# 5. Install and build
composer install
npm install
npm update
php bakery assets:vite

# 6. Setup database
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

# 10. Test in browser (login with admin / admin123)
```

---

## Workflow Summary Changes

### BEFORE

```yaml
echo "✅ Integration test completed"
echo "✅ NPM package verified"
echo "✅ Schema file loaded successfully"
echo "✅ Frontend asset building completed"
```

### AFTER

```yaml
echo "✅ Integration test completed for PHP 8.1 with UserFrosting ^6.0-beta"
echo "✅ sprinkle-crud6 installed successfully"
echo "✅ Database migrations ran successfully"
echo "✅ Admin user created: admin / admin123"
echo "✅ NPM package verified"
echo "✅ Schema file loaded successfully"
echo "✅ Assets built with php bakery assets:vite"
echo "✅ PHP server started with php bakery serve"
echo "✅ Vite development server started"
echo "✅ API endpoint tests completed:"
echo "   - GET /api/crud6/groups (list)"
echo "   - GET /api/crud6/groups/1 (single record)"
echo "✅ Frontend route tests completed:"
echo "   - /crud6/groups (list page)"
echo "   - /crud6/groups/1 (detail page)"
echo "✅ Screenshots captured and uploaded as artifacts"
echo ""
echo "ℹ️  Note: Authentication tests verify 401 responses for unauthenticated requests"
echo "ℹ️  Screenshots may show login page if not authenticated"
echo "ℹ️  Both PHP and Vite servers were running during tests"
```

---

## Bakery Commands Reference

### Commands Added to Workflow

| Command | Purpose | When Used |
|---------|---------|-----------|
| `php bakery create:admin-user` | Create admin user | After seeding |
| `php bakery assets:vite` | Build frontend assets | After npm install |
| `php bakery serve` | Start PHP server | Before testing |
| `php bakery bake` | Build assets + clear cache | Documented (alternative) |

### Commands Already in Use

| Command | Purpose | When Used |
|---------|---------|-----------|
| `php bakery migrate` | Run migrations | After DB config |
| `php bakery seed` | Seed database | After migrations |

---

## Testing Workflow Comparison

### BEFORE

```
1. Install UserFrosting
2. Configure CRUD6
3. Install dependencies
4. Run migrations
5. Seed database
6. Build assets (npm run build)
7. Start PHP server (php -S)
8. Run tests
9. Take screenshots
10. Stop PHP server
```

### AFTER

```
1. Install UserFrosting
2. Configure CRUD6
3. Install dependencies
4. Run migrations
5. Seed database
6. Create admin user (bakery command) ⭐
7. Update npm packages ⭐
8. Build assets (php bakery assets:vite) ⭐
9. Start PHP server (php bakery serve) ⭐
10. Start Vite server ⭐
11. Run tests
12. Take screenshots
13. Stop both servers ⭐
```

**⭐ = New or significantly changed**

---

## Benefits Summary

### Standardization
✅ Uses official UserFrosting 6 bakery commands throughout  
✅ Follows recommended installation patterns  
✅ Aligns with UserFrosting documentation  

### Reliability
✅ `php bakery serve` provides proper server configuration  
✅ `npm update` fixes known package issues  
✅ Better server readiness checks  
✅ Explicit admin user creation  

### Development Experience
✅ Both PHP and Vite servers provide complete dev environment  
✅ Hot module replacement for frontend changes  
✅ Consistent approach between local and CI testing  
✅ Clear credentials for testing (admin/admin123)  

### Documentation
✅ Clear instructions for manual testing  
✅ Multiple approaches documented (bakery vs manual)  
✅ Screenshot viewing instructions  
✅ Comprehensive guides updated consistently  

### Maintainability
✅ Less custom scripts and workarounds  
✅ Uses framework-provided tools  
✅ Easier to update when framework changes  
✅ Better aligned with future UF6 releases  

---

## Validation Checklist

- [x] YAML syntax validated with Python yaml parser
- [x] 15 bakery command references in workflow
- [x] Admin credentials documented in 3 places
- [x] Key workflow steps present:
  - [x] Create admin user
  - [x] Build frontend assets
  - [x] Start PHP development server
  - [x] Start Vite development server
  - [x] Stop servers
- [x] Documentation updated:
  - [x] INTEGRATION_TESTING.md
  - [x] QUICK_TEST_GUIDE.md
  - [x] Archive summary created
- [x] Visual comparison created

---

## Next Steps

1. ✅ Merge PR to main branch
2. ✅ Workflow will run automatically
3. ✅ Screenshots will be available in Actions artifacts
4. ✅ Admin credentials (admin/admin123) ready for testing
5. ✅ Both servers will run during CI tests

---

## Conclusion

The integration of UserFrosting 6 bakery commands brings the testing workflow in line with official recommendations and provides a more robust, maintainable approach. The dual server setup (PHP + Vite) better reflects actual development workflows and the explicit admin user creation ensures consistent, reproducible tests.
