# UserFrosting 6 CRUD Sprinkle

UserFrosting 6 CRUD Sprinkle provides a generic API CRUD layer for any database table using JSON schema definitions. This sprinkle integrates with UserFrosting 6.0.4 beta or later and provides RESTful API endpoints for dynamic CRUD operations.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap and Setup
- Install PHP 8.1 or later: `php --version` (required: PHP 8.1+)
- Install Composer: `composer --version` (required for dependency management)
- Bootstrap dependencies: `composer install` -- takes 3-5 minutes depending on network. NEVER CANCEL. Set timeout to 15+ minutes.
  - **CRITICAL**: In CI environments, composer may require GitHub token. Use: `export COMPOSER_AUTH='{"github-oauth":{"github.com":"your_token"}}'` before running composer commands
  - **Alternative for auth issues**: `composer install --ignore-platform-reqs --no-scripts` (limited functionality)
  - **If install fails**: `composer dump-autoload` will still generate basic autoloader
- Verify syntax: `find app/src -name "*.php" -exec php -l {} \;` -- takes under 1 second. All files should show "No syntax errors detected"

### Testing
- **DEPENDENCY REQUIREMENT**: Full testing requires `composer install` to complete successfully
- Run unit tests: `vendor/bin/phpunit` -- takes 2-3 minutes. NEVER CANCEL. Set timeout to 10+ minutes.
  - **CRITICAL**: This sprinkle requires a UserFrosting 6 application context for full functionality
  - If dependencies not installed: Tests will fail with missing class errors
  - Unit tests for models: `vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php`
- **MANUAL VALIDATION**: When composer install fails, validate JSON schemas and PHP syntax manually
- **JSON Schema Validation**: `php -r "echo json_encode(json_decode(file_get_contents('examples/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"`

### Development Tools
- **Requires successful composer install** for development tools to be available
- Code formatting: `vendor/bin/php-cs-fixer fix` -- takes 1-2 minutes for full codebase. NEVER CANCEL. Set timeout to 10+ minutes.
- Static analysis: `vendor/bin/phpstan analyse` -- takes 2-3 minutes. NEVER CANCEL. Set timeout to 10+ minutes.
- **If vendor tools unavailable**: Use syntax check as primary validation: `find app/src -name "*.php" -exec php -l {} \;`
- **ALWAYS** run syntax validation before committing: `find app/src -name "*.php" -exec php -l {} \;`

### Integration with UserFrosting 6
- **CRITICAL**: This is a UserFrosting 6 sprinkle, not a standalone application
- To use in a UserFrosting 6 project:
  1. Add to composer.json: `"minimum-stability": "beta", "prefer-stable": true`
  2. Install: `composer require ssnukala/sprinkle-crud6`
  3. Add to sprinkles in your main sprinkle class: `CRUD6::class`
- **CANNOT RUN STANDALONE**: This sprinkle requires a full UserFrosting 6 application to function

## Validation

### Critical Testing Scenarios
- **Schema Validation**: Test JSON schema loading and validation using examples in `app/schema/crud6/` and `examples/`
- **Model Configuration**: Verify CRUD6Model can be configured from schema definitions
- **API Endpoint Structure**: Validate that controllers follow UserFrosting 6 patterns for `/api/crud6/{model}` routes
- **Field Type Mapping**: Test all supported field types (string, integer, boolean, date, datetime, text, json, float, decimal)
- **Validation Rules**: Test required fields, length constraints, email validation, and unique constraints

### Manual Testing Requirements
Always test these scenarios after making changes:
1. **Syntax Validation**: `find app/src -name "*.php" -exec php -l {} \;` - must pass for all files
2. **JSON Schema Validation**: 
   - `php -r "echo json_decode(file_get_contents('app/schema/crud6/users.json')) ? 'users.json valid' : 'users.json invalid';"`
   - `php -r "echo json_decode(file_get_contents('examples/products.json')) ? 'products.json valid' : 'products.json invalid';"`
3. **Schema Structure Check**: Verify required fields exist in JSON schemas:
   - `model`, `table`, `fields` keys must be present
   - Each field must have a `type` property
   - Valid types: string, integer, boolean, date, datetime, text, json, float, decimal
4. **API Route Structure**: Verify routes follow `/api/crud6/{model}` pattern in `app/src/Routes/CRUD6Routes.php`
5. **Controller Inheritance**: Check that controllers extend appropriate base classes in `app/src/Controller/Base/`

### CRITICAL Manual Validation Steps
After making ANY changes to CRUD6Model, schema files, or controllers:
1. Run full syntax check: `find app/src -name "*.php" -exec php -l {} \;`
2. Validate all JSON schemas: `for file in app/schema/crud6/*.json examples/*.json; do php -r "if(json_decode(file_get_contents('$file'))) { echo '$file valid'; } else { echo '$file invalid'; } echo PHP_EOL;"; done`
3. Check autoloader: `composer dump-autoload`
4. If dependencies available, run tests: `vendor/bin/phpunit`

### Code Quality
- All PHP files must pass syntax check: `php -l filename.php`
- Code must be PSR-12 compliant: run `vendor/bin/php-cs-fixer fix` before committing
- Static analysis must pass: run `vendor/bin/phpstan analyse` before committing
- **ALWAYS** run the full test suite before submitting changes: `vendor/bin/phpunit`

## Common Development Scenarios

### Working Without Full Dependencies
When `composer install` fails due to authentication issues:
1. Use `composer dump-autoload` to generate basic autoloader
2. Run syntax validation: `find app/src -name "*.php" -exec php -l {} \;`
3. Validate JSON schemas with manual commands
4. Make code changes focusing on syntax and structure
5. Test schema format and API patterns manually

### Adding New Model Support
1. Create JSON schema file in `app/schema/crud6/model_name.json`
2. Follow existing schema patterns in `examples/products.json`
3. Test schema: `php -r "echo json_decode(file_get_contents('app/schema/crud6/model_name.json')) ? 'valid' : 'invalid';"`
4. Verify required fields: `model`, `table`, `fields`
5. Add database migration if needed in `app/src/Database/Migrations/`

### Debugging Schema Issues
1. **Invalid JSON**: Use `php -r "json_decode('content'); echo json_last_error_msg();"`
2. **Missing fields**: Check for required `model`, `table`, `fields` keys
3. **Field validation**: Ensure each field has `type` property
4. **Type validation**: Use only supported types (string, integer, boolean, date, datetime, text, json, float, decimal)

### Integration Testing Approach
Since this is a UserFrosting 6 sprinkle:
1. **Unit testing**: Focus on model configuration and schema loading logic
2. **Integration testing**: Requires full UserFrosting 6 application setup
3. **API testing**: Test RESTful endpoints at `/api/crud6/{model}` within UserFrosting context
4. **Schema testing**: Validate JSON schema loading and field mapping

### Adding New Functionality
1. **Schema Changes**: Modify or add JSON schema files in `app/schema/crud6/`
2. **Model Updates**: Update `app/src/Database/Models/CRUD6Model.php` for new field types or behaviors
3. **Controller Extensions**: Extend base controllers in `app/src/Controller/Base/`
4. **Route Additions**: Update `app/src/Routes/CRUD6Routes.php` for new API endpoints
5. **Testing**: Add corresponding tests in `app/tests/` following the existing pattern

### Working with Examples
- Review example schemas: `examples/products.json` and `app/schema/crud6/users.json`
- Test model usage: Run through scenarios in `examples/model-usage-examples.php`
- API documentation: Check `examples/README.md` for API usage patterns

## Project Structure

### Key Directories
```
app/
├── src/                     # Main source code
│   ├── CRUD6.php           # Main sprinkle class
│   ├── Controller/Base/    # CRUD controllers
│   ├── Database/Models/    # CRUD6Model and interfaces
│   ├── Routes/             # API route definitions
│   └── ServicesProvider/   # DI container services
├── schema/crud6/           # Example schema definitions
└── tests/                  # PHPUnit tests

examples/                   # Usage examples and documentation
```

### Important Files
- `app/src/CRUD6.php`: Main sprinkle registration class
- `app/src/Database/Models/CRUD6Model.php`: Generic Eloquent model for any table
- `app/src/ServicesProvider/SchemaService.php`: JSON schema loading and validation
- `app/src/Routes/CRUD6Routes.php`: RESTful API route definitions
- `composer.json`: Dependencies and autoloading configuration
- `phpunit.xml`: Test configuration

### Configuration Files
- `composer.json`: Project dependencies and scripts
- `phpunit.xml`: PHPUnit test suite configuration
- `app/schema/crud6/*.json`: Schema definitions for different models

## Time Expectations

### Build and Test Times
- **Composer install**: 5-15 minutes (network and auth dependent). NEVER CANCEL - use 20+ minute timeout
- **Syntax validation**: Under 1 second for all files
- **PHPUnit tests**: 2-3 minutes for full suite (requires dependencies). NEVER CANCEL - use 10+ minute timeout  
- **PHP-CS-Fixer**: 1-2 minutes for full codebase (requires dependencies). NEVER CANCEL - use 10+ minute timeout
- **PHPStan analysis**: 2-3 minutes (requires dependencies). NEVER CANCEL - use 10+ minute timeout

### Development Workflow
1. Make changes to source files
2. **ALWAYS run syntax check first**: `find app/src -name "*.php" -exec php -l {} \;` (under 1 second)
3. Validate JSON schemas: `php -r "echo json_encode(json_decode(file_get_contents('examples/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"`
4. **If dependencies available**: Run specific tests: `vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php` (30 seconds)
5. **If dependencies available**: Run full tests: `vendor/bin/phpunit` (3 minutes - use 10+ minute timeout)
6. **If dependencies available**: Format code: `vendor/bin/php-cs-fixer fix` (2 minutes - use 10+ minute timeout) 
7. **If dependencies available**: Analyze code: `vendor/bin/phpstan analyse` (3 minutes - use 10+ minute timeout)

## Common Validation Commands

### Required Before Every Commit
```bash
# ALWAYS run - Syntax check (under 1 second)
find app/src -name "*.php" -exec php -l {} \;

# Validate JSON schemas
php -r "echo json_encode(json_decode(file_get_contents('examples/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"

# If dependencies available (run these if vendor/bin exists):
# Code formatting (2 minutes - NEVER CANCEL)
vendor/bin/php-cs-fixer fix

# Static analysis (3 minutes - NEVER CANCEL)  
vendor/bin/phpstan analyse

# Full test suite (3 minutes - NEVER CANCEL)
vendor/bin/phpunit
```

### Quick Development Checks
```bash
# Fast syntax validation (ALWAYS works)
php -l app/src/CRUD6.php

# Validate main JSON schema files
php -r "echo json_decode(file_get_contents('app/schema/crud6/users.json')) ? 'users.json valid' : 'users.json invalid';"

# Test autoloader generation
composer dump-autoload

# If dependencies available:
# Single test class
vendor/bin/phpunit app/tests/Database/Models/CRUD6ModelTest.php
```

## Troubleshooting

### Common Issues
- **Composer timeout**: Use longer timeout (20+ minutes) for `composer install`
- **GitHub token required in CI**: Set `COMPOSER_AUTH` environment variable or use `--ignore-platform-reqs` 
- **"Class not found" errors**: Indicates missing dependencies - run `composer install` successfully first
- **UserFrosting integration**: This sprinkle requires a UserFrosting 6 application - cannot run standalone
- **PHP version**: Requires PHP 8.1 or later
- **Beta dependencies**: Project uses UserFrosting 6 beta packages
- **Vendor tools missing**: If `vendor/bin/` doesn't exist, use basic validation only (syntax check, JSON validation)