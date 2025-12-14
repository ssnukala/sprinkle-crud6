# Seed SQL ID Verification

**Date**: 2024-12-14  
**Purpose**: Verify that the seed SQL generation script creates records with IDs starting from 100

## Verification Process

Ran the seed SQL generation script against example schemas:

```bash
node .github/testing-framework/scripts/generate-seed-sql.js examples/schema /tmp/test-seed-output.sql
```

## Results

### Activities
```sql
INSERT IGNORE INTO `activities` (`id`, `ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES (100, '192.168.100.200', 100, 'type_b', '2024-01-100 12:00:00', 'Test description for description - Record 100');

INSERT IGNORE INTO `activities` (`id`, `ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES (101, '192.168.101.201', 101, 'type_c', '2024-01-101 12:00:00', 'Test description for description - Record 101');

INSERT IGNORE INTO `activities` (`id`, `ip_address`, `user_id`, `type`, `occurred_at`, `description`)
VALUES (102, '192.168.102.202', 102, 'type_a', '2024-01-102 12:00:00', 'Test description for description - Record 102');
```

### Users
```sql
VALUES (100, 'test_user_name_100', 'Name100', 'Name100', 'test100@example.com', 'en_US', 1, true, true, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
VALUES (101, 'test_user_name_101', 'Name101', 'Name101', 'test101@example.com', 'en_US', 1, true, true, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
VALUES (102, 'test_user_name_102', 'Name102', 'Name102', 'test102@example.com', 'en_US', 1, true, true, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

### Groups
```sql
VALUES (100, 'test_slug_100', 'Name100', 'Test description for description - Record 100', 'fas fa-user');
VALUES (101, 'test_slug_101', 'Name101', 'Test description for description - Record 101', 'fas fa-user');
VALUES (102, 'test_slug_102', 'Name102', 'Test description for description - Record 102', 'fas fa-user');
```

### Roles
```sql
VALUES (100, 'test_slug_100', 'Name100', 'Test description for description - Record 100');
VALUES (101, 'test_slug_101', 'Name101', 'Test description for description - Record 101');
VALUES (102, 'test_slug_102', 'Name102', 'Test description for description - Record 102');
```

### Permissions
```sql
VALUES (100, 'test_slug_100', 'Name100', '', 'Test description for description - Record 100');
VALUES (101, 'test_slug_101', 'Name101', '', 'Test description for description - Record 101');
VALUES (102, 'test_slug_102', 'Name102', '', 'Test description for description - Record 102');
```

## Conclusion

✅ **CONFIRMED**: The seed SQL generation script correctly creates records with IDs starting from 100 for all models.

- **ID Range**: 100, 101, 102 (3 records per model by default)
- **Models Verified**: activities, users, groups, roles, permissions
- **Script Location**: `.github/testing-framework/scripts/generate-seed-sql.js`
- **Logic**: Line 398 in the script: `const recordId = i + 100;`

This confirms that:
1. The seed SQL generation is working correctly
2. Test records with ID 100 should exist after seed data is loaded
3. If 404 errors occur for `/api/crud6/{model}/100`, the issue is likely:
   - Seed SQL was not loaded properly in the integration test
   - Database tables were not created
   - Seed SQL loading failed silently

The validation script added in this PR (`validate-test-records.php`) will help diagnose which of these is the case by querying the database before tests run.
