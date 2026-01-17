# SQL Schema and Seed Data

This directory contains auto-generated SQL files used for creating database tables and seeding test data for CRUD6 integration testing.

## Directory Structure

### `/migrations/` - DDL Files
Contains CREATE TABLE statements for database schema creation:

- **crud6-tables.sql** - Auto-generated DDL for all test models
  - Creates 12 main tables from JSON schemas
  - Creates 2 pivot tables for many-to-many relationships
  - Includes indexes and constraints based on schema definitions

### `/seeds/` - Seed Data Files
Contains INSERT statements for populating test data:

- **crud6-test-data.sql** - Auto-generated seed data for all test models
  - Seeds test data for 21 models
  - Includes relationship data for pivot tables
  - Uses `INSERT...ON DUPLICATE KEY UPDATE` for safe re-seeding

## Usage

### For Integration Testing

These files are designed to run in this order during integration tests:

1. **Run UserFrosting migrations**: `php bakery migrate`
2. **Create admin user**: `php bakery create:admin-user`
3. **Run DDL**: Execute `migrations/crud6-tables.sql`
4. **Run seed data**: Execute `seeds/crud6-test-data.sql`
5. **Begin testing**: Start unauthenticated and authenticated path tests

### Example Commands

```bash
# MySQL
mysql -u username -p database_name < examples/sql/migrations/crud6-tables.sql
mysql -u username -p database_name < examples/sql/seeds/crud6-test-data.sql

# PostgreSQL
psql -U username -d database_name -f examples/sql/migrations/crud6-tables.sql
psql -U username -d database_name -f examples/sql/seeds/crud6-test-data.sql
```

## Important Notes

### ⚠️ Auto-Generated Files

**DO NOT EDIT THESE FILES MANUALLY**

These SQL files are automatically generated from JSON schema files in `examples/schema/` directory.

To update these files, regenerate them using:

```bash
# Regenerate DDL (CREATE TABLE statements)
php scripts/generate-schema-ddl.php

# Regenerate seed data (INSERT statements)
php scripts/generate-test-data.php
```

### Schema-Driven Generation

The SQL files are generated based on:
- **Field definitions** → Column types and constraints
- **Index definitions** → Database indexes
- **Relationship definitions** → Foreign keys and pivot tables
- **Validation rules** → NOT NULL constraints and defaults

### Test Data Constraints

- **User ID 1** and **Group ID 1** are reserved for system/admin
- Test data always starts from ID 2 or higher
- DELETE/DISABLE tests must not use ID 1 (system account protection)
- Uses `INSERT...ON DUPLICATE KEY UPDATE` for idempotent seeding

## Source Schema Files

These SQL files are generated from schema files in `examples/schema/`:

- activities.json
- categories.json
- contacts.json
- field-template-example.json
- groups.json
- order_details.json
- orders.json
- permissions.json
- product_categories.json
- products-1column.json
- roles.json
- users.json

See [examples/schema/README.md](../schema/README.md) for schema documentation.

## Related Documentation

- [Integration Testing Guide](../../INTEGRATION_TESTING.md) - Full integration testing workflow
- [Schema Documentation](../schema/README.md) - JSON schema file format and examples
- [Quick Test Guide](../../QUICK_TEST_GUIDE.md) - Quick reference for testing
