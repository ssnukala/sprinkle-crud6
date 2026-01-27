# Bakery Components Integration Summary

## Overview

This document summarizes the integration of advanced database scanning and schema generation bakery commands from `sprinkle-learntegrate` into `sprinkle-crud6`.

## Source Repository

The bakery components were copied from:
- Repository: https://github.com/ssnukala/sprinkle-learntegrate
- Source directory: `app/src/Bakery/`
- Test directory: `app/tests/Bakery/`

## Components Integrated

### 1. Helper Classes (app/src/Bakery/Helper/)

#### DatabaseScanner.php
- **Purpose**: Scans database structure using Doctrine DBAL schema introspection
- **Features**:
  - Lists all tables, columns, indexes, and foreign keys
  - Detects explicit foreign key relationships from database constraints
  - Detects implicit foreign key relationships based on naming conventions
  - Validates implicit relationships through data sampling
  - Supports multiple database connections
  - Configurable naming patterns and table prefixes
  - Auto-detects common table prefixes from actual database
  - Confidence-based validation for implicit relationships

#### SchemaGenerator.php
- **Purpose**: Generates CRUD6-compatible JSON schema files from table metadata
- **Features**:
  - Maps database column types to CRUD6 field types
  - Generates validation rules based on column constraints
  - Creates permissions configuration
  - Detects and configures detail relationships (one-to-many)
  - Generates human-readable labels from column names
  - Configurable CRUD operations per schema
  - Two-phase generation to resolve cross-schema references
  - Extracts listable fields from related schemas

### 2. Bakery Commands (app/src/Bakery/)

#### ScanDatabaseCommand.php
- **Command Name**: `crud6:scan`
- **Purpose**: Interactive database structure viewer
- **Options**:
  - `--database`: Select database connection
  - `--tables`: Filter specific tables
  - `--output`: Choose output format (table/json)
  - `--detect-implicit`: Enable implicit relationship detection
  - `--sample-size`: Configure data sampling size
- **Usage**: `php bakery crud6:scan --detect-implicit --output=json`

#### GenerateSchemaCommand.php
- **Command Name**: `crud6:generate`
- **Purpose**: Automated schema file generation from database
- **Options**:
  - `--database`: Select database connection
  - `--tables`: Filter specific tables
  - `--output-dir`: Custom schema directory
  - `--no-create/--no-update/--no-delete/--no-list`: Disable CRUD operations
  - `--detect-implicit`: Enable implicit relationship detection
  - `--sample-size`: Configure data sampling size
- **Usage**: `php bakery crud6:generate --detect-implicit --tables=users,products`

### 3. Service Provider (app/src/ServicesProvider/)

#### BakeryServicesProvider.php
- **Purpose**: Registers bakery services with DI container
- **Services Registered**:
  - `DatabaseScanner::class`: Auto-wired with Capsule dependency
  - `SchemaGenerator::class`: Configured with config values for directory and CRUD options
- **Configuration Keys**:
  - `crud6.schema_directory`: Schema output directory
  - `crud6.crud_options`: Default CRUD operation configuration

### 4. Tests (app/tests/Bakery/)

#### DatabaseScannerTest.php
- Tests database scanning functionality
- Tests table structure retrieval
- Tests explicit relationship detection
- Tests implicit relationship detection
- Tests naming pattern matching
- Tests data sampling validation

#### SchemaGeneratorTest.php
- Tests schema generation from metadata
- Tests field type mapping
- Tests validation rule generation
- Tests permission configuration
- Tests detail relationship detection
- Tests two-phase schema generation

#### ScanDatabaseCommandTest.php
- Tests command execution
- Tests database connection selection
- Tests table filtering
- Tests output formats
- Tests relationship detection options

#### GenerateSchemaCommandTest.php
- Tests schema file generation
- Tests CRUD operation configuration
- Tests implicit relationship detection
- Tests output directory customization
- Tests table exclusion

## Integration Changes

### Namespace Updates
- **Old**: `UserFrosting\Sprinkle\LearnIntegrate\Bakery`
- **New**: `UserFrosting\Sprinkle\CRUD6\Bakery`

### Command Name Changes
- **Old**: `learntegrate:scan`, `learntegrate:generate`
- **New**: `crud6:scan`, `crud6:generate`

### Configuration Key Changes
- **Old**: `learntegrate.*`
- **New**: `crud6.*`

### Copyright Header Updates
- Updated to CRUD6 sprinkle copyright and license

## Configuration

Users can configure bakery commands in `app/config/default.php`:

```php
return [
    'crud6' => [
        // Schema generation settings
        'schema_directory' => 'app/schema/crud6',
        'exclude_tables' => ['migrations', 'sessions'],
        
        // Default CRUD operations
        'crud_options' => [
            'create' => true,
            'read' => true,
            'update' => true,
            'delete' => true,
            'list' => true,
        ],
        
        // Relationship detection settings
        'relationship_detection' => [
            'detect_implicit' => false,
            'sample_size' => 100,
            'confidence_threshold' => 0.8,
            'naming_patterns' => [
                '/^(.+)_id$/i',      // user_id, category_id
                '/^(.+)Id$/i',       // userId, categoryId
            ],
            'table_prefixes' => ['tbl_', 'test_'],
        ],
    ],
];
```

## Features

### Explicit Relationship Detection
- Reads foreign key constraints from database schema
- Always enabled
- 100% accurate based on DDL

### Implicit Relationship Detection
- Identifies relationships from column naming conventions
- Optional feature (enable with `--detect-implicit`)
- Validates relationships through data sampling
- Configurable confidence threshold (default: 80%)
- Supports multiple naming patterns:
  - Snake case: `user_id`, `category_id`
  - Camel case: `userId`, `categoryId`

### Data Sampling
- Samples database records to validate relationships
- Configurable sample size (default: 100 rows)
- Calculates confidence score based on match rate
- Only accepts relationships above confidence threshold

### Auto-detection Features
- Automatically detects table prefixes from database
- Generates singular forms for table names
- Maps database types to CRUD6 field types
- Infers validation rules from constraints
- Detects timestamp columns (created_at, updated_at)
- Identifies password fields for special handling

## Benefits

1. **Rapid Prototyping**: Generate complete CRUD schemas from existing databases in seconds
2. **Accuracy**: Uses Doctrine DBAL for reliable schema introspection
3. **Flexibility**: Supports both explicit and implicit relationship detection
4. **Configuration**: Highly configurable through command options and config file
5. **Multi-Database**: Works with any database connection configured in UserFrosting
6. **Validation**: Data sampling ensures high-quality implicit relationships
7. **Maintainability**: Generated schemas serve as starting point for customization

## Documentation

Comprehensive documentation has been added to README.md covering:
- Command usage and options
- Configuration examples
- Relationship detection explanation
- Integration examples
- Configuration file structure

## Testing

All bakery components include comprehensive test coverage:
- Unit tests for helper classes
- Command integration tests
- Relationship detection tests
- Schema generation tests

## Future Enhancements

Potential improvements that could be added:
1. Support for many-to-many relationship detection
2. Custom field type mappings
3. Schema validation against existing database
4. Incremental schema updates
5. Migration generation from schema changes
6. Support for composite foreign keys
7. Enum type detection and configuration
8. Custom validation rule templates
