# CRUD6 DDL Generator

## Overview

The DDL (Data Definition Language) generator automatically creates `CREATE TABLE` statements from CRUD6 JSON schema definitions. This ensures that database tables are created correctly with proper column types, constraints, and indexes based on the schema configuration.

## Usage

### Command Line

```bash
node .github/testing-framework/scripts/generate-ddl-sql.js [schema_directory] [output_file]
```

### Examples

```bash
# Generate DDL from example schemas
node .github/testing-framework/scripts/generate-ddl-sql.js examples/schema app/sql/migrations/crud6-tables.sql

# Generate DDL from custom schemas
node .github/testing-framework/scripts/generate-ddl-sql.js path/to/schemas output.sql
```

## Field Type Mapping

The generator maps CRUD6 field types to SQL column types as follows:

| CRUD6 Type | SQL Type | Notes |
|------------|----------|-------|
| `integer` | `INT` | `INT AUTO_INCREMENT` if `auto_increment: true` |
| `string` | `VARCHAR(n)` | Length from `validation.length.max`, default 255 |
| `text` | `TEXT` | For long text content |
| `boolean` | `TINYINT(1)` | 0 = false, 1 = true |
| `boolean-yn` | `TINYINT(1)` | Boolean with Yes/No representation |
| `date` | `DATE` | Date only (YYYY-MM-DD) |
| `datetime` | `TIMESTAMP` | Date and time |
| `decimal` | `DECIMAL(10,2)` | For prices, amounts |
| `float` | `FLOAT` | Floating point numbers |
| `json` | `JSON` | JSON data (MySQL 5.7.8+) |
| `email` | `VARCHAR(255)` | Email addresses |
| `password` | `VARCHAR(255)` | Password hashes (bcrypt, etc.) |
| `phone` | `VARCHAR(20)` | Phone numbers |
| `url` | `VARCHAR(2048)` | URLs |
| `zip` | `VARCHAR(10)` | ZIP/postal codes |
| `multiselect` | `TEXT` | Multi-select values |
| `textarea` | `TEXT` | Text areas (any variant) |

## Generated Features

### Column Constraints

- **NOT NULL**: Applied when `required: true` or `validation.required: true`
- **NULL**: Applied for optional fields
- **DEFAULT**: Applied when `default` value is specified in schema
- **AUTO_INCREMENT**: Applied when `auto_increment: true`

### Indexes

- **PRIMARY KEY**: Auto-increment fields
- **UNIQUE KEY**: Fields with `validation.unique: true`
- **KEY (Index)**: Filterable or sortable fields (up to 5 per table)

### Pivot Tables

For many-to-many relationships defined in schema `relationships`:

```json
{
  "relationships": [
    {
      "type": "many_to_many",
      "pivot_table": "permission_roles",
      "foreign_key": "permission_id",
      "related_key": "role_id"
    }
  ]
}
```

Generates:

```sql
CREATE TABLE IF NOT EXISTS permission_roles (
  permission_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (permission_id, role_id),
  KEY permission_id_idx (permission_id),
  KEY role_id_idx (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Table Creation Options

All tables are created with:

- `CREATE TABLE IF NOT EXISTS` - Safe for re-running
- `ENGINE=InnoDB` - For transaction support and foreign keys
- `DEFAULT CHARSET=utf8mb4` - Full Unicode support
- `COLLATE=utf8mb4_unicode_ci` - Case-insensitive Unicode collation

## Execution Order

In integration tests and deployments, the DDL should be executed in this order:

1. Run UserFrosting core migrations: `php bakery migrate`
2. **Run DDL generation and loading** (this script)
3. Create admin user: `php bakery create:admin-user`
4. Run seed data: INSERT statements

## Loading DDL SQL

Use the `load-seed-sql.php` script to load the generated DDL:

```bash
php .github/testing-framework/scripts/load-seed-sql.php app/sql/migrations/crud6-tables.sql
```

Or manually with MySQL:

```bash
mysql -h 127.0.0.1 -u root -p database_name < app/sql/migrations/crud6-tables.sql
```

## Schema Processing

- Processes all `.json` files in the schema directory
- Skips duplicate tables (when multiple schemas reference the same table)
- Generates unique table definitions based on the first schema encountered
- Creates pivot tables for all many-to-many relationships

## Output Format

The generated SQL file includes:

```sql
-- ═══════════════════════════════════════════════════════════════
-- CRUD6 DDL - CREATE TABLE Statements
-- Generated from JSON schemas
-- ═══════════════════════════════════════════════════════════════

-- Disable foreign key checks during table creation
SET FOREIGN_KEY_CHECKS=0;

-- Table definitions...

CREATE TABLE IF NOT EXISTS table_name (
  -- column definitions
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;
```

## Integration with CI/CD

The DDL generator is integrated into the GitHub Actions workflow:

```yaml
- name: Generate and load DDL (CREATE TABLE statements)
  run: |
    cd userfrosting
    node ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/generate-ddl-sql.js \
      "$SCHEMA_DIR" ddl.sql
    php ../${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/load-seed-sql.php \
      ddl.sql
```

## Validation

After generating DDL, validate the SQL file:

```bash
# Check syntax
grep -E "CREATE TABLE|PRIMARY KEY|UNIQUE KEY|ENGINE=InnoDB" ddl.sql

# Count tables
grep -c "CREATE TABLE" ddl.sql
```

## Troubleshooting

### Unknown field type warnings

If you see warnings like:

```
⚠️ Unknown field type: custom_type for field field_name, defaulting to VARCHAR(255)
```

Add the field type mapping to the `mapFieldTypeToSQL()` function in `generate-ddl-sql.js`.

### Duplicate table skipped

If you see:

```
⏭️ Skipping duplicate table: table_name (from schema.json)
```

This is normal - multiple schemas can reference the same table, and only the first one is used for DDL generation.

### Foreign key errors

If you encounter foreign key constraint errors, ensure:

1. Tables are created in the correct order
2. Referenced tables exist before creating foreign keys
3. Column types match between related tables

## Related Scripts

- `generate-seed-sql.js` - Generates INSERT statements for seed data
- `load-seed-sql.php` - Loads SQL files into the database
- Integration test workflow in `.github/workflows/integration-test.yml`
