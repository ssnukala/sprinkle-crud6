# MySQL Reserved Word Fix - Integration Test

## Issue

The integration test was failing with the following error:

```
ERROR 1064 (42000) at line 1: You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'groups LIMIT 5' at line 1
```

**Failed Run**: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18350655514/job/52269423424

## Root Cause

The word `groups` is a **reserved keyword** in MySQL because it's part of the `GROUP BY` SQL syntax. When reserved keywords are used as table or column names, they must be escaped with backticks (`` ` ``) in raw SQL queries.

From the MySQL documentation:
> Reserved words such as SELECT, DELETE, or BIGINT require special treatment for use as identifiers such as table and column names.

## The Fix

### Changed File
`.github/workflows/integration-test.yml` line 165

### Before
```sql
SELECT * FROM groups LIMIT 5;
```

### After
```sql
SELECT * FROM `groups` LIMIT 5;
```

### Diff
```diff
-          mysql -h 127.0.0.1 -uroot -proot userfrosting_test -e "SELECT * FROM groups LIMIT 5;"
+          mysql -h 127.0.0.1 -uroot -proot userfrosting_test -e "SELECT * FROM \`groups\` LIMIT 5;"
```

## Why This is the Correct Solution

### UserFrosting Context
1. The `groups` table is defined in the UserFrosting Account sprinkle (core framework)
2. It's a standard UserFrosting table used for user group management
3. Changing the table name would break compatibility with UserFrosting

### Technical Explanation
1. **Eloquent ORM handles this automatically**: When using Laravel's Eloquent ORM (which UserFrosting uses), table names are automatically escaped
2. **Raw SQL queries need manual escaping**: The integration test uses a raw `mysql` command for quick database verification
3. **Backticks are the MySQL standard**: Using backticks is the standard MySQL way to escape reserved keywords

### Alternative Approaches Considered

#### Option 1: Use a Different Table Name ❌
```sql
SELECT * FROM users LIMIT 5;
```
**Why not chosen**: 
- Still need to test the `groups` table specifically for the integration test
- Doesn't solve the underlying issue

#### Option 2: Use Eloquent Query ❌
```php
php artisan tinker --execute="App\Models\Group::limit(5)->get()"
```
**Why not chosen**:
- Requires bootstrapping the entire UserFrosting application
- Adds complexity to a simple connectivity test
- The backtick solution is simpler and more direct

#### Option 3: Use Backticks (Selected) ✅
```sql
SELECT * FROM `groups` LIMIT 5;
```
**Why chosen**:
- Minimal change (2 characters)
- Standard MySQL practice
- No side effects
- Follows MySQL documentation guidelines

## MySQL Reserved Keywords

Common MySQL reserved keywords that require escaping when used as identifiers:
- `groups` (part of GROUP BY)
- `order` (part of ORDER BY)
- `limit` (LIMIT clause)
- `table` (CREATE TABLE)
- `select`, `insert`, `update`, `delete` (DML statements)
- `where`, `having`, `join` (clauses)

Full list: https://dev.mysql.com/doc/refman/8.0/en/keywords.html

## Prevention

To avoid this issue in the future:

1. **Always use backticks for table/column names in raw SQL queries**
   ```sql
   SELECT * FROM `users` WHERE `id` = 1;
   ```

2. **Use Eloquent ORM when possible** - it handles escaping automatically
   ```php
   Group::limit(5)->get();
   ```

3. **Test with MySQL before deploying** - MySQL is stricter about reserved words than SQLite

4. **Check reserved word lists** when naming tables/columns

## Impact

- ✅ Fixes integration test failure
- ✅ No breaking changes
- ✅ No code modifications required
- ✅ Follows MySQL best practices
- ✅ Compatible with all MySQL versions

## Related Documentation

- [MySQL Reserved Keywords](https://dev.mysql.com/doc/refman/8.0/en/keywords.html)
- [MySQL Identifier Qualifiers](https://dev.mysql.com/doc/refman/8.0/en/identifiers.html)
- [UserFrosting Database Guide](https://learn.userfrosting.com/database/)
