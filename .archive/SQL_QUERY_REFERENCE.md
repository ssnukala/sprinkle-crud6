# SQL Query Reference: Many-to-Many Relationships

## Overview
This document provides the exact SQL queries generated for different relationship types in the CRUD6 SprunjeAction implementation.

---

## 1. One-to-Many: Activities

### Schema Configuration
```json
{
  "model": "activities",
  "foreign_key": "user_id",
  "list_fields": ["occurred_at", "type", "description", "ip_address"]
}
```

### Generated SQL
```sql
SELECT occurred_at, type, description, ip_address
FROM activities
WHERE user_id = ?
ORDER BY occurred_at DESC
LIMIT 10 OFFSET 0
```

### Parameters
- `?` = User ID (e.g., 1)

### Notes
- Simple WHERE clause on foreign key
- No JOINs needed
- Most efficient query type

---

## 2. Many-to-Many: Roles

### Schema Configuration

**Relationships Section**:
```json
{
  "name": "roles",
  "type": "many_to_many",
  "pivot_table": "role_user",
  "foreign_key": "user_id",
  "related_key": "role_id"
}
```

**Details Section**:
```json
{
  "model": "roles",
  "foreign_key": "user_id",
  "list_fields": ["name", "slug", "description"]
}
```

### Generated SQL
```sql
SELECT roles.*
FROM roles
INNER JOIN role_user 
  ON roles.id = role_user.role_id
WHERE role_user.user_id = ?
ORDER BY roles.name ASC
LIMIT 10 OFFSET 0
```

### Parameters
- `?` = User ID (e.g., 1)

### Query Breakdown
1. **FROM roles**: Main table to query
2. **JOIN role_user**: Pivot table connection
   - `roles.id = role_user.role_id`: Links role to pivot
3. **WHERE role_user.user_id = ?**: Filters by user
4. **SELECT roles.***: Returns all role columns

### Notes
- Single JOIN through pivot table
- Efficient for most use cases
- Automatically filters by user ID

---

## 3. Nested Many-to-Many: Permissions

### Schema Configuration

**Relationships Section** (for roles):
```json
{
  "name": "roles",
  "type": "many_to_many",
  "pivot_table": "role_user",
  "foreign_key": "user_id",
  "related_key": "role_id"
}
```

**Details Section**:
```json
{
  "model": "permissions",
  "foreign_key": "user_id",
  "list_fields": ["slug", "name", "description"]
}
```

### Generated SQL
```sql
SELECT DISTINCT permissions.*
FROM permissions
INNER JOIN role_permission 
  ON permissions.id = role_permission.permission_id
INNER JOIN role_user 
  ON role_permission.role_id = role_user.role_id
WHERE role_user.user_id = ?
ORDER BY permissions.slug ASC
LIMIT 10 OFFSET 0
```

### Parameters
- `?` = User ID (e.g., 1)

### Query Breakdown
1. **FROM permissions**: Main table to query
2. **JOIN role_permission**: Links permissions to roles
   - `permissions.id = role_permission.permission_id`
3. **JOIN role_user**: Links roles to users
   - `role_permission.role_id = role_user.role_id`
4. **WHERE role_user.user_id = ?**: Filters by user
5. **SELECT DISTINCT**: Removes duplicates
6. **permissions.***: Returns all permission columns

### Notes
- Two JOINs to traverse the relationship chain
- DISTINCT prevents duplicate permissions
- Slightly more expensive than single JOIN
- Automatically aggregates permissions from all user's roles

---

## Table Structure Requirements

### For One-to-Many (Activities)
```sql
CREATE TABLE activities (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  occurred_at DATETIME NOT NULL,
  type VARCHAR(255) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45),
  INDEX idx_user_id (user_id)
);
```

### For Many-to-Many (Roles)

**Main Table**:
```sql
CREATE TABLE roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT
);
```

**Pivot Table**:
```sql
CREATE TABLE role_user (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at DATETIME,
  updated_at DATETIME,
  PRIMARY KEY (user_id, role_id),
  INDEX idx_user_id (user_id),
  INDEX idx_role_id (role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

### For Nested Many-to-Many (Permissions)

**Main Table**:
```sql
CREATE TABLE permissions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT
);
```

**Role-Permission Pivot Table**:
```sql
CREATE TABLE role_permission (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  created_at DATETIME,
  updated_at DATETIME,
  PRIMARY KEY (role_id, permission_id),
  INDEX idx_role_id (role_id),
  INDEX idx_permission_id (permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

---

## Performance Considerations

### Index Recommendations

**Critical Indexes**:
```sql
-- For activities
ALTER TABLE activities ADD INDEX idx_user_id (user_id);

-- For role_user pivot
ALTER TABLE role_user ADD INDEX idx_user_id (user_id);
ALTER TABLE role_user ADD INDEX idx_role_id (role_id);

-- For role_permission pivot
ALTER TABLE role_permission ADD INDEX idx_role_id (role_id);
ALTER TABLE role_permission ADD INDEX idx_permission_id (permission_id);
```

### Query Performance

**Activities (One-to-Many)**:
- ⚡ Very Fast (single table, indexed WHERE)
- Typical: < 1ms for 100s of records
- Scales well up to 10,000+ records per user

**Roles (Many-to-Many)**:
- ⚡ Fast (single JOIN, both sides indexed)
- Typical: 1-5ms for 10s of roles
- Scales well up to 100+ roles per user

**Permissions (Nested Many-to-Many)**:
- 🔸 Moderate (double JOIN, DISTINCT)
- Typical: 5-20ms for 100s of permissions
- Scales reasonably up to 1,000+ permissions
- Consider caching for very large permission sets

### EXPLAIN Plans

**Activities**:
```
+----+-------------+------------+------+---------------+---------+
| id | select_type | table      | type | key           | rows    |
+----+-------------+------------+------+---------------+---------+
|  1 | SIMPLE      | activities | ref  | idx_user_id   | 10      |
+----+-------------+------------+------+---------------+---------+
```

**Roles**:
```
+----+-------------+-----------+------+---------------+---------+
| id | select_type | table     | type | key           | rows    |
+----+-------------+-----------+------+---------------+---------+
|  1 | SIMPLE      | role_user | ref  | idx_user_id   | 3       |
|  1 | SIMPLE      | roles     | ref  | PRIMARY       | 1       |
+----+-------------+-----------+------+---------------+---------+
```

**Permissions**:
```
+----+-------------+------------------+------+------------------+---------+
| id | select_type | table            | type | key              | rows    |
+----+-------------+------------------+------+------------------+---------+
|  1 | SIMPLE      | role_user        | ref  | idx_user_id      | 3       |
|  1 | SIMPLE      | role_permission  | ref  | idx_role_id      | 20      |
|  1 | SIMPLE      | permissions      | ref  | PRIMARY          | 1       |
+----+-------------+------------------+------+------------------+---------+
```

---

## Testing Queries Manually

### Test Data Setup
```sql
-- Insert test user
INSERT INTO users (id, user_name, email) VALUES (1, 'testuser', 'test@example.com');

-- Insert test roles
INSERT INTO roles (id, name, slug) VALUES 
  (1, 'Administrator', 'admin'),
  (2, 'Editor', 'editor');

-- Assign roles to user
INSERT INTO role_user (user_id, role_id) VALUES 
  (1, 1),
  (1, 2);

-- Insert test permissions
INSERT INTO permissions (id, slug, name) VALUES 
  (1, 'uri_users', 'View users'),
  (2, 'create_user', 'Create user'),
  (3, 'edit_user', 'Edit user');

-- Assign permissions to roles
INSERT INTO role_permission (role_id, permission_id) VALUES 
  (1, 1),  -- Admin has view users
  (1, 2),  -- Admin has create user
  (1, 3),  -- Admin has edit user
  (2, 1);  -- Editor has view users

-- Insert test activity
INSERT INTO activities (user_id, occurred_at, type, description) VALUES 
  (1, NOW(), 'sign_in', 'User signed in');
```

### Verify Queries
```sql
-- Test activities query
SELECT occurred_at, type, description 
FROM activities 
WHERE user_id = 1;
-- Expected: 1 row

-- Test roles query
SELECT roles.* 
FROM roles 
JOIN role_user ON roles.id = role_user.role_id 
WHERE role_user.user_id = 1;
-- Expected: 2 rows (admin, editor)

-- Test permissions query
SELECT DISTINCT permissions.* 
FROM permissions 
JOIN role_permission ON permissions.id = role_permission.permission_id 
JOIN role_user ON role_permission.role_id = role_user.role_id 
WHERE role_user.user_id = 1;
-- Expected: 3 rows (all 3 permissions - admin has all, editor has 1)
```

---

## Troubleshooting

### Query Not Returning Data

**Check 1: Data Exists**
```sql
-- For roles
SELECT COUNT(*) FROM role_user WHERE user_id = 1;

-- For permissions
SELECT COUNT(*) FROM role_permission WHERE role_id IN (
  SELECT role_id FROM role_user WHERE user_id = 1
);
```

**Check 2: Indexes Exist**
```sql
SHOW INDEX FROM role_user;
SHOW INDEX FROM role_permission;
```

**Check 3: Foreign Keys Valid**
```sql
-- Check for orphaned pivot records
SELECT ru.* FROM role_user ru 
LEFT JOIN users u ON ru.user_id = u.id 
WHERE u.id IS NULL;

SELECT ru.* FROM role_user ru 
LEFT JOIN roles r ON ru.role_id = r.id 
WHERE r.id IS NULL;
```

### Slow Queries

**Enable Query Logging**:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;  -- Log queries > 100ms
```

**Check Execution Plan**:
```sql
EXPLAIN SELECT DISTINCT permissions.* 
FROM permissions 
JOIN role_permission ON permissions.id = role_permission.permission_id 
JOIN role_user ON role_permission.role_id = role_user.role_id 
WHERE role_user.user_id = 1;
```

Look for:
- `type = ref` (good) vs `type = ALL` (table scan, bad)
- `key` shows index being used
- `rows` shows estimated rows scanned

---

## References

- Laravel Query Builder Documentation: https://laravel.com/docs/10.x/queries
- MySQL JOIN Optimization: https://dev.mysql.com/doc/refman/8.0/en/join-optimization.html
- UserFrosting 6 Database: https://learn.userfrosting.com/database
