# SQL Schema and Seed Data Generation

CRUD6 integration tests dynamically generate SQL files for creating database tables and seeding test data at runtime. These files are **not stored in the repository** but are generated on-demand from JSON schema files.

## How It Works

The testing framework uses these scripts to generate SQL dynamically:

- **`.github/testing-framework/scripts/generate-ddl-sql.js`** - Generates CREATE TABLE statements
- **`.github/testing-framework/scripts/generate-seed-sql.js`** - Generates INSERT statements for test data

During integration tests, these scripts:
1. Read JSON schemas from `examples/schema/`
2. Generate SQL files in the test environment
3. Execute the SQL to create tables and seed data

## Manual SQL Generation

If you need to generate SQL files manually for development or debugging:

```bash
# Generate DDL (CREATE TABLE statements)
node .github/testing-framework/scripts/generate-ddl-sql.js examples/schema tables.sql

# Generate seed data (INSERT statements)
node .github/testing-framework/scripts/generate-seed-sql.js examples/schema seed-data.sql

# Load into MySQL
mysql -u username -p database_name < tables.sql
mysql -u username -p database_name < seed-data.sql

# Load into PostgreSQL
psql -U username -d database_name -f tables.sql
psql -U username -d database_name -f seed-data.sql
```

## What Gets Generated

### DDL (Data Definition Language)
- Creates 12+ main tables from JSON schemas
- Creates pivot tables for many-to-many relationships
- Includes indexes and constraints based on schema definitions
- Foreign keys for relationships

### Seed Data
- Test data for 21+ models
- Relationship data for pivot tables
- Uses `INSERT...ON DUPLICATE KEY UPDATE` for idempotent seeding
- Proper ID handling (IDs 1-99 reserved for system data)

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
