<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

/**
 * Manual Integration Test for Boolean Toggle Endpoint
 * 
 * This test verifies that the PUT /api/crud6/users/{id}/flag_enabled endpoint
 * correctly handles boolean toggle actions.
 * 
 * To run this test:
 * 1. Deploy CRUD6 to a UserFrosting 6 application
 * 2. Ensure users table has flag_enabled and flag_verified columns
 * 3. Use curl or Postman to test the endpoints
 * 
 * Test Cases:
 * 
 * 1. Toggle flag_enabled from true to false
 * ----------------------------------------
 * Request:
 *   PUT /api/crud6/users/1/flag_enabled
 *   Content-Type: application/json
 *   Body: {"flag_enabled": false}
 * 
 * Expected Response:
 *   HTTP 200 OK
 *   {
 *     "title": "Success",
 *     "description": "User field updated successfully",
 *     "data": {
 *       "id": 1,
 *       "flag_enabled": false,
 *       ...other fields...
 *     }
 *   }
 * 
 * Verification:
 *   SELECT flag_enabled FROM users WHERE id = 1;
 *   -- Should return 0 (false)
 * 
 * 
 * 2. Toggle flag_enabled from false to true
 * -----------------------------------------
 * Request:
 *   PUT /api/crud6/users/1/flag_enabled
 *   Content-Type: application/json
 *   Body: {"flag_enabled": true}
 * 
 * Expected Response:
 *   HTTP 200 OK
 *   {
 *     "title": "Success",
 *     "description": "User field updated successfully",
 *     "data": {
 *       "id": 1,
 *       "flag_enabled": true,
 *       ...other fields...
 *     }
 *   }
 * 
 * Verification:
 *   SELECT flag_enabled FROM users WHERE id = 1;
 *   -- Should return 1 (true)
 * 
 * 
 * 3. Toggle flag_verified
 * -----------------------
 * Request:
 *   PUT /api/crud6/users/1/flag_verified
 *   Content-Type: application/json
 *   Body: {"flag_verified": false}
 * 
 * Expected Response:
 *   HTTP 200 OK
 *   {
 *     "title": "Success",
 *     "description": "User field updated successfully",
 *     "data": {
 *       "id": 1,
 *       "flag_verified": false,
 *       ...other fields...
 *     }
 *   }
 * 
 * 
 * 4. Test with non-existent field (should fail)
 * ---------------------------------------------
 * Request:
 *   PUT /api/crud6/users/1/nonexistent_field
 *   Content-Type: application/json
 *   Body: {"nonexistent_field": true}
 * 
 * Expected Response:
 *   HTTP 500 Internal Server Error
 *   {
 *     "title": "Error",
 *     "description": "Field 'nonexistent_field' does not exist in schema for model 'users'"
 *   }
 * 
 * 
 * 5. Test with readonly field (should fail)
 * -----------------------------------------
 * Request:
 *   PUT /api/crud6/users/1/id
 *   Content-Type: application/json
 *   Body: {"id": 999}
 * 
 * Expected Response:
 *   HTTP 500 Internal Server Error
 *   {
 *     "title": "Error",
 *     "description": "Field 'id' is readonly and cannot be updated"
 *   }
 * 
 * 
 * CURL COMMANDS FOR TESTING:
 * ===========================
 * 
 * # Get auth token first
 * TOKEN=$(curl -s -X POST http://localhost/api/account/login \
 *   -H "Content-Type: application/json" \
 *   -d '{"user_name":"admin","password":"your_password"}' \
 *   | jq -r '.token')
 * 
 * # Test toggle flag_enabled to false
 * curl -v -X PUT http://localhost/api/crud6/users/1/flag_enabled \
 *   -H "Content-Type: application/json" \
 *   -H "Authorization: Bearer $TOKEN" \
 *   -d '{"flag_enabled": false}'
 * 
 * # Test toggle flag_enabled to true
 * curl -v -X PUT http://localhost/api/crud6/users/1/flag_enabled \
 *   -H "Content-Type: application/json" \
 *   -H "Authorization: Bearer $TOKEN" \
 *   -d '{"flag_enabled": true}'
 * 
 * # Test toggle flag_verified
 * curl -v -X PUT http://localhost/api/crud6/users/1/flag_verified \
 *   -H "Content-Type: application/json" \
 *   -H "Authorization: Bearer $TOKEN" \
 *   -d '{"flag_verified": false}'
 * 
 * # Verify in database
 * mysql -u root -p -e "SELECT id, user_name, flag_enabled, flag_verified FROM uf_users WHERE id = 1;"
 * 
 * 
 * EXPECTED DEBUG LOGS:
 * ====================
 * 
 * When debug mode is enabled (crud6.debug_mode: true), you should see:
 * 
 * [CRUD6 UpdateFieldAction] ===== UPDATE FIELD REQUEST START =====
 * [CRUD6 UpdateFieldAction] Request parameters received
 *   - params: {"flag_enabled": false}
 * [CRUD6 UpdateFieldAction] Validation passed
 * [CRUD6 UpdateFieldAction] Data transformed
 *   - transformed_data: {"flag_enabled": false}  <- OR empty if bug exists
 * [CRUD6 UpdateFieldAction] Field added to data (no validation rules)  <- Only if fix is applied
 *   - type: boolean
 *   - value: false
 * [CRUD6 UpdateFieldAction] Field value updated
 *   - old_value: true
 *   - new_value: false
 * [CRUD6 UpdateFieldAction] Model saved to database
 * [CRUD6 UpdateFieldAction] Transaction committed
 * 
 * 
 * TROUBLESHOOTING:
 * ================
 * 
 * If you get 500 errors:
 * 
 * 1. Check application logs (storage/logs/userfrosting.log)
 * 2. Enable debug mode in config: crud6.debug_mode: true
 * 3. Look for the specific error message
 * 4. Verify schema file exists: app/schema/crud6/users.json
 * 5. Verify field is defined in schema
 * 6. Verify field is not marked as readonly or editable: false
 * 
 * Common issues:
 * - Missing authentication token
 * - Missing permissions (update_user_field)
 * - Field not in schema
 * - Field marked as readonly
 * - Validation rules rejecting boolean values
 */
class BooleanToggleEndpointTest
{
    /**
     * This is a documentation file for manual testing.
     * 
     * PHPUnit integration tests would require a full UserFrosting 6 application
     * with database setup, migrations, seeds, and authentication.
     * 
     * For automated testing in CI/CD, use the curl commands above or implement
     * browser automation with Playwright/Selenium.
     */
}
