# CRUD6 Scripts

This directory contains standalone scripts for CRUD6 sprinkle maintenance and CI operations.

## Files

### generate-test-schemas.php
Main script for generating test schemas and translations for CRUD6 testing.

**Usage:**
```bash
php scripts/generate-test-schemas.php
```

**What it does:**
- Generates 6 schema JSON files in `app/schema/crud6/`:
  - users.json
  - groups.json
  - products.json
  - roles.json
  - permissions.json
  - activities.json
- Generates translations in `app/locale/en_US/messages.php`
- Creates directories if they don't exist

**Dependencies:**
- None (standalone script)
- Does not require composer autoloader
- Self-contained with helper classes in this directory

### SchemaBuilder.php
Helper class for building CRUD6 JSON schemas programmatically.

**Features:**
- Fluent API for schema construction
- Support for all CRUD6 field types
- Automatic translation key generation
- Validation rules configuration

**Example:**
```php
$schema = SchemaBuilder::create('users', 'users')
    ->setTitleField('user_name')
    ->addStringField('user_name', required: true, listable: true)
    ->addEmailField('email', required: true, unique: true)
    ->addPermissions(['read' => 'uri_users', 'create' => 'create_user'])
    ->build();
```

### GenerateSchemas.php
Generator class that uses SchemaBuilder to create all test schemas and translations.

**Features:**
- Generates schemas for all test models
- Creates corresponding translation files
- Handles directory creation
- Validates generated files

## CI Integration

These scripts are used in the GitHub Actions workflow (`.github/workflows/unit-tests.yml`):

```yaml
- name: Generate CRUD6 Schema Files and Translations
  run: php scripts/generate-test-schemas.php
```

## Design Notes

### Why Standalone?

These scripts are **standalone** (no namespace, no autoloader dependency) because:

1. **CI Environment**: Test classes in `app/tests/` are not autoloaded by Composer
2. **Independence**: Scripts can run before composer dependencies are installed
3. **Simplicity**: No complex dependency management needed
4. **Portability**: Can be run in any environment with PHP 8.1+

### Why in scripts/ folder?

1. **Organization**: Keeps development scripts separate from application code
2. **Convention**: Standard practice for helper scripts
3. **Clarity**: Makes it obvious these are development tools, not application code

## Maintenance

When adding new test models:

1. Add schema definition to `GenerateSchemas.php` in `getSchemaDefinitions()`
2. Either use existing static helpers (`userSchema()`, `groupSchema()`, etc.) or create new one
3. Run `php scripts/generate-test-schemas.php` to regenerate all files
4. Commit the generated schemas and translations

## Related Files

- `app/tests/Testing/SchemaBuilder.php` - Test version (with namespace)
- `app/tests/Testing/GenerateSchemas.php` - Test version (with namespace)
- `.archive/CI_RUN_20318491945_FIX_SUMMARY.md` - Implementation details

## History

- **2025-12-17**: Created standalone scripts to fix CI fatal error
  - Moved from root `generate-test-schemas.php`
  - Made standalone (no namespace, no autoloader dependency)
  - Fixed file paths for correct directory resolution
  - Cleaned up debug output
