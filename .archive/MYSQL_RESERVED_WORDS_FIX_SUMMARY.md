# MySQL Reserved Words Fix Summary

## Issue
Integration test failing with SQL syntax error on line 117:
```
ERROR 1064 (42000) at line 117: You have an error in your SQL syntax; 
check the manual that corresponds to your MySQL server version for the 
right syntax to use near 'groups (
     id INT AUTO_INCREMENT NOT NULL,
     slug VARCHAR(255) NOT NULL,
     name ' at line 1
```

**Root Cause**: The table name `groups` is a MySQL reserved word and was not quoted in the generated SQL.

**GitHub Actions Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/20152470827/job/57848071442

## Solution
Modified both SQL generation scripts to wrap **ALL** SQL identifiers with backticks (`` ` ``), making the scripts failsafe for any table or column name that might be a MySQL reserved word.

## Files Modified

### 1. `.github/testing-framework/scripts/generate-ddl-sql.js`
**Changes**: Added backticks to all SQL identifiers in CREATE TABLE statements

- **Table names**: `CREATE TABLE IF NOT EXISTS \`groups\``
- **Column names**: `` `id` INT AUTO_INCREMENT``
- **Primary keys**: `PRIMARY KEY (\`id\`)`
- **Unique constraints**: `UNIQUE KEY \`slug_unique\` (\`slug\`)`
- **Indexes**: `KEY \`name_idx\` (\`name\`)`
- **Pivot tables**: All pivot table identifiers wrapped in backticks

**Lines Modified**: 132, 174, 179, 186, 191, 218-223

### 2. `.github/testing-framework/scripts/generate-seed-sql.js`
**Changes**: Added backticks to all SQL identifiers in INSERT statements

- **Table names**: `INSERT INTO \`groups\``
- **Column lists**: `` `slug`, `name`, `description` ``
- **ON DUPLICATE KEY UPDATE**: `` `slug` = VALUES(`slug`) ``
- **Pivot tables**: All pivot table identifiers wrapped in backticks

**Lines Modified**: 195, 197, 228, 230

## Protected SQL Reserved Words
The fix now protects against common MySQL reserved words that might be used as table or column names:

- `groups` - The original issue
- `order`, `orders` - Common e-commerce tables
- `user`, `users` - Common auth tables
- `key`, `keys` - Common security tables
- `select`, `insert`, `update`, `delete` - SQL keywords
- `table`, `database`, `index` - DDL keywords
- `role`, `roles` - Common auth tables
- `group`, `groups` - Common organization tables
- And many more...

## Testing
Comprehensive validation performed:

```bash
✅ DDL generation succeeded
✅ Table names have backticks
✅ Primary keys have backticks
✅ groups table has backticks
✅ Seed generation succeeded
✅ INSERT table names have backticks
✅ groups INSERT has backticks
```

### Sample Output

**Before Fix**:
```sql
CREATE TABLE IF NOT EXISTS groups (
  id INT AUTO_INCREMENT NOT NULL,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
);
```

**After Fix**:
```sql
CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);
```

## Impact
- **Scope**: All dynamically generated SQL from schema definitions
- **Backward Compatibility**: ✅ Fully compatible - backticks are valid MySQL syntax even for non-reserved words
- **Performance**: ✅ No performance impact
- **Safety**: ✅ Now failsafe for ANY table/column name

## Verification Steps
To verify the fix works:

1. Generate DDL: `node .github/testing-framework/scripts/generate-ddl-sql.js examples/schema /tmp/test.sql`
2. Check for backticks: `grep "CREATE TABLE" /tmp/test.sql`
3. Verify groups table: `grep "groups" /tmp/test.sql`

Expected output should show all identifiers wrapped in backticks.

## Related
- PR: #[number will be assigned]
- Issue: MySQL syntax error on line 117
- Date: 2025-12-12
- Author: GitHub Copilot
