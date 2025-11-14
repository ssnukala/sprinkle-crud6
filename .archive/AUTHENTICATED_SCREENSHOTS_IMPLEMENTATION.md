# Authenticated Screenshot Implementation

## Problem
The integration test screenshots were showing the login page instead of the actual CRUD6 groups pages because:
- The `/crud6/groups` routes require authentication
- Screenshots were taken without logging in first
- An admin user was created but never used to authenticate

## Solution
Created a Playwright script that handles authentication before taking screenshots.

### Script: `.github/scripts/take-authenticated-screenshots.js`
This script:
1. Navigates to the UserFrosting login page (`/account/sign-in`)
2. Fills in the login form with admin credentials (username: `admin`, password: `admin123`)
3. Submits the form and waits for navigation
4. Takes screenshots of CRUD6 pages after authentication:
   - `/crud6/groups` - Groups list page
   - `/crud6/groups/1` - Single group detail page

### Workflow Changes: `.github/workflows/integration-test.yml`
Updated the "Take screenshots of frontend pages" step to:
- Copy the authenticated screenshot script from the sprinkle repository
- Run the script with admin credentials
- Provide clearer messaging about authenticated screenshots

## Technical Details

### Login Form Fields
The script expects these form fields on the login page:
- `input[name="user_name"]` - Username field
- `input[name="password"]` - Password field
- `button[type="submit"]` - Submit button

### Error Handling
The script includes several safety features:
- Timeout handling for all navigation and waiting operations
- URL checking to detect if still on login page (indicates auth failure)
- Error screenshots saved to `/tmp/screenshot_error.png` if something fails
- Graceful handling of navigation failures

### Usage
The script is called from the workflow like this:
```bash
node take-authenticated-screenshots.js http://localhost:8080 admin admin123
```

Arguments:
1. `base_url` - The base URL of the application (e.g., `http://localhost:8080`)
2. `username` - Admin username (created earlier in the workflow)
3. `password` - Admin password

## Benefits
1. **Accurate Screenshots**: Shows the actual CRUD6 interface, not the login page
2. **Better Testing**: Validates that authenticated users can access CRUD6 routes
3. **Debugging Support**: Provides clear console output and error screenshots
4. **Maintainability**: Self-contained script that's easy to update

## Future Enhancements
Potential improvements could include:
- Testing with different user roles (not just admin)
- Testing permission-based access control
- Adding screenshots of other CRUD operations (create, edit, delete)
- Capturing network requests to validate API calls
