# Fully Schema-Driven Testing and Database Management

## Overview

The CRUD6 sprinkle now uses a **fully schema-driven approach** where everything is generated directly from JSON schema files with no intermediary configuration files needed.

## Architecture

```
JSON Schema Files (examples/schema/*.json)
         ↓
    ┌────┴────┬────────────┬─────────────┐
    ↓         ↓            ↓             ↓
 Tables   Test Paths   Seed Data   Documentation
```

All database structure, tests, and seed data are generated from the single source of truth: **JSON schemas**.

## Scripts

### 1. generate-paths-from-schema.js ✨ NEW

**Generates integration test paths directly from schema files.**

```bash
node .github/scripts/generate-paths-from-schema.js \
  examples/schema \
  .github/config/integration-test-paths.json
```

**Features**:
- Reads schemas directly (no intermediary config)
- Extracts ALL schema features automatically:
  - Relationships → specific nested endpoints
  - Custom actions → specific action tests  
  - Field toggles → specific toggle tests
  - Detail models → specific detail tests
- Generates 404 comprehensive paths (21 schemas)
- Future-proof: schema changes → automatic test coverage

**Example Output** (users.json):
- Standard CRUD: 11 paths
- Relationships (roles, permissions): 6 paths
- Custom actions (toggle_enabled, reset_password, etc.): 5 paths
- Detail models (activities, roles, permissions): 3 paths
- Field toggles (flag_enabled, flag_verified): 2 paths
- Unauthenticated variants: 13 paths
- **Total: 32 paths for users alone**

### 2. generate-tables-from-schema.js ✨ NEW

**Creates database tables from schema definitions.**

```bash
node .github/scripts/generate-tables-from-schema.js \
  examples/schema \
  app/sql/migrations/schema-tables.sql \
  mysql
```

**Features**:
- Generates CREATE TABLE IF NOT EXISTS statements
- Maps CRUD6 field types to SQL:
  - `string` → `VARCHAR(n)`
  - `integer` → `INT` (with AUTO_INCREMENT support)
  - `boolean` → `TINYINT(1)` (MySQL) or `BOOLEAN` (PostgreSQL)
  - `date` → `DATE`
  - `datetime` → `DATETIME`
  - `text` → `TEXT`
  - `decimal` → `DECIMAL(p,s)`
  - `json` → `JSON`
- Creates indexes for foreign keys (_id fields)
- Adds unique constraints from validation rules
- Generates pivot tables for many-to-many relationships
- Supports MySQL and PostgreSQL

**Generated Example**:
```sql
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
  `user_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `group_id` INT NOT NULL,
  `flag_enabled` TINYINT(1) DEFAULT 1,
  `flag_verified` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX `idx_users_group_id` ON `users`(`group_id`);
CREATE UNIQUE INDEX `uniq_users_user_name` ON `users`(`user_name`);
CREATE UNIQUE INDEX `uniq_users_email` ON `users`(`email`);

-- Pivot table
CREATE TABLE IF NOT EXISTS `role_users` (
  `role_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. generate-seed-sql.js (Enhanced)

**Generates test seed data from schemas.**

```bash
node .github/scripts/generate-seed-sql.js \
  examples/schema \
  app/sql/seeds/crud6-test-data.sql
```

**Features**:
- Generates INSERT statements based on field types
- Respects validation rules (required, unique, length)
- All test data starts from ID 2 (protects system records)
- Idempotent with ON DUPLICATE KEY UPDATE
- Generates relationship seed data

### 4. load-seed-sql.php

**Loads generated SQL into database.**

```bash
php .github/scripts/load-seed-sql.php app/sql/seeds/crud6-test-data.sql
```

### 5. validate-integration-config.js

**Validates generated configuration.**

```bash
node .github/scripts/validate-integration-config.js
```

## Complete Workflow

### Initial Setup (One Time)

```bash
# 1. Generate table creation SQL from schemas
node .github/scripts/generate-tables-from-schema.js \
  examples/schema \
  app/sql/migrations/schema-tables.sql

# 2. Generate integration test paths from schemas
node .github/scripts/generate-paths-from-schema.js \
  examples/schema \
  .github/config/integration-test-paths.json

# 3. Generate seed data from schemas
node .github/scripts/generate-seed-sql.js \
  examples/schema \
  app/sql/seeds/crud6-test-data.sql
```

### Database Setup

```bash
# 1. Run UserFrosting migrations
php bakery migrate --force

# 2. Create tables from schemas (if needed)
mysql -u user -p database < app/sql/migrations/schema-tables.sql

# 3. Create admin user (ID 1 reserved)
php bakery create:admin-user \
  --username=admin \
  --password=admin123 \
  --email=admin@example.com

# 4. Load test seed data (ID 2+)
php .github/scripts/load-seed-sql.php \
  app/sql/seeds/crud6-test-data.sql
```

### Run Tests

```bash
# Validate configuration
node .github/scripts/validate-integration-config.js

# Test unauthenticated paths
php .github/scripts/test-paths.php \
  .github/config/integration-test-paths.json \
  unauth

# Test authenticated paths
php .github/scripts/test-paths.php \
  .github/config/integration-test-paths.json \
  auth
```

## Schema Format for Full Coverage

To ensure maximum test coverage, schemas should include:

```json
{
  "model": "users",
  "table": "users",
  "primary_key": "id",
  "title_field": "user_name",
  
  "permissions": {
    "read": "uri_users",
    "create": "create_user",
    "update": "update_user_field",
    "delete": "delete_user"
  },
  
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true,
      "readonly": true
    },
    "user_name": {
      "type": "string",
      "required": true,
      "validation": {
        "required": true,
        "unique": true,
        "length": {"max": 255}
      }
    },
    "flag_enabled": {
      "type": "boolean",
      "default": true
    }
  },
  
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id"
    }
  ],
  
  "actions": [
    {
      "key": "toggle_enabled",
      "type": "field_update",
      "field": "flag_enabled",
      "method": "POST",
      "permission": "update_user_field"
    }
  ],
  
  "details": [
    {
      "model": "activities",
      "foreign_key": "user_id"
    }
  ]
}
```

## What Gets Generated

### From Relationships
Each relationship generates:
- `GET /api/crud6/{model}/{id}/{relation}` - List related items
- `POST /api/crud6/{model}/{id}/{relation}` - Attach (many-to-many)
- `DELETE /api/crud6/{model}/{id}/{relation}` - Detach (many-to-many)

### From Actions
Each action generates:
- `POST /api/crud6/{model}/{id}/a/{actionKey}` - Execute action

### From Details
Each detail model generates:
- `GET /api/crud6/{model}/{id}/{detailModel}` - Get detail data

### From Field Toggles
Each boolean field generates:
- `PUT /api/crud6/{model}/{id}/{field}` - Toggle field

### Standard CRUD
Always generated:
- `GET /api/crud6/{model}/schema` - Schema endpoint
- `GET /api/crud6/{model}` - List endpoint
- `POST /api/crud6/{model}` - Create endpoint
- `GET /api/crud6/{model}/{id}` - Read endpoint
- `PUT /api/crud6/{model}/{id}` - Update endpoint
- `PUT /api/crud6/{model}/{id}/{field}` - Update field
- `DELETE /api/crud6/{model}/{id}` - Delete endpoint

## Benefits

### 1. Zero Configuration
- No intermediary files to maintain
- Single source of truth: JSON schemas
- Changes to schemas automatically reflected in tests

### 2. Comprehensive Coverage
- ALL schema features tested automatically
- 404 paths generated from 21 schemas
- Every relationship, action, and field tested

### 3. Future-Proof
- Add new relationship → test automatically created
- Add new action → test automatically created
- Add new field toggle → test automatically created
- Modify schema → tests stay in sync

### 4. Database Bootstrap
- Can create tables from schemas
- Safe re-run with IF NOT EXISTS
- Supports multiple database types

### 5. Maintainability
- Schema changes propagate automatically
- No manual test path updates needed
- Clear dependency chain: Schema → Everything

## Migration from Old Approach

### Old: Manual Configuration
```json
// integration-test-models.json (manual configuration)
{
  "models": {
    "users": {
      "test_id": 2,
      "relationships": [...],  // Manually maintained
      "custom_actions": [...]  // Manually maintained
    }
  },
  "path_templates": {...}  // Generic templates
}
```

### New: Schema-Driven
```bash
# Just point to schema directory
node generate-paths-from-schema.js examples/schema output.json
```

**Result**: All relationships, actions, and fields automatically extracted and tested.

## Comparison

| Feature | Old Approach | New Approach |
|---------|-------------|--------------|
| **Configuration** | Manual JSON config | Direct from schemas |
| **Paths Generated** | 105 generic | 404 specific |
| **Maintenance** | Update 2 files | Update 1 schema |
| **Coverage** | Generic templates | ALL schema features |
| **Future-Proof** | Manual updates | Automatic |
| **Table Creation** | Manual migrations | Generated from schema |

## Example: Adding a New Feature

### Old Approach
1. Update schema with new action
2. Update integration-test-models.json
3. Regenerate paths
4. Manually verify coverage

### New Approach
1. Update schema with new action
2. Regenerate: `node generate-paths-from-schema.js`
3. Done! Test automatically included

## Conclusion

The fully schema-driven approach means:
- ✅ Schemas are the single source of truth
- ✅ Everything generated automatically
- ✅ Comprehensive test coverage
- ✅ Future-proof and maintainable
- ✅ Can bootstrap database from schemas
- ✅ Zero configuration overhead

All you need to do is **maintain good schemas** and everything else follows.
