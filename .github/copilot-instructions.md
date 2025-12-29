# UserFrosting 6 CRUD Sprinkle

UserFrosting 6 CRUD Sprinkle provides a generic API CRUD layer for any database table using JSON schema definitions. This sprinkle integrates with UserFrosting 6.0.4 beta or later and provides RESTful API endpoints for dynamic CRUD operations.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## 🚨 CRITICAL PATTERNS - DO NOT MODIFY 🚨

### Controller Parameter Injection Pattern (Established in PR #119)

**THIS PATTERN IS CORRECT AND WORKING - DO NOT CHANGE IT**

All CRUD6 controllers MUST use this exact signature pattern:

```php
public function __invoke(
    array $crudSchema,                      // ✅ Auto-injected from 'crudSchema' attribute
    CRUD6ModelInterface $crudModel,         // ✅ Auto-injected from 'crudModel' attribute
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
```

**Why this works:**
- UserFrosting 6 extends Slim 4 with automatic parameter injection from request attributes
- CRUD6Injector middleware sets BOTH `crudModel` and `crudSchema` request attributes
- UserFrosting's framework automatically injects these as method parameters
- This is NOT standard Slim 4 - it's a UserFrosting 6 enhancement

**DO NOT:**
- ❌ Change controllers to use `$request->getAttribute('crudSchema')` or `$request->getAttribute('crudModel')`
- ❌ Remove the `array $crudSchema, CRUD6ModelInterface $crudModel` parameters
- ❌ Assume this is wrong because "Slim 4 doesn't support this" (UserFrosting 6 does!)
- ❌ Try to "fix" this pattern - it's working correctly
- ❌ Modify CRUD6Injector to only inject one parameter

**History:**
- PR #119: Established this pattern for all controllers
- This pattern matches sprinkle-admin's GroupApi + GroupInjector
- Has been tested and verified working in production
- Breaking this pattern causes 500 errors

**Affected Files (ALL use this pattern):**
- `app/src/Controller/ApiAction.php`
- `app/src/Controller/CreateAction.php`
- `app/src/Controller/DeleteAction.php`
- `app/src/Controller/EditAction.php`
- `app/src/Controller/RelationshipAction.php`
- `app/src/Controller/SprunjeAction.php`
- `app/src/Controller/UpdateFieldAction.php`
- `app/src/Controller/Base.php`

**If you see a 500 error, it's NOT because of this pattern - look elsewhere!**

## UserFrosting 6 Framework Reference

### Core Philosophy
All code modifications and refactoring in this sprinkle **MUST** consider the UserFrosting 6 framework architecture, patterns, and standards. The goal is to **extend and reuse** existing patterns and core components already created in the framework rather than reinventing solutions.

### Reference Repositories
When developing or modifying code, always reference these official UserFrosting 6 repositories for patterns, standards, and component examples:

1. **[userfrosting/sprinkle-core (6.0 branch)](https://github.com/userfrosting/sprinkle-core/tree/6.0)**
   - Core sprinkle with fundamental services and patterns
   - Reference for: Service providers, middleware, base controllers, cache service, i18n service, session handling
   - Key patterns: `ServicesProviderInterface`, `RouteDefinitionInterface`, core middleware

2. **[userfrosting/sprinkle-admin (6.0 branch)](https://github.com/userfrosting/sprinkle-admin/tree/6.0)**
   - Admin interface sprinkle with CRUD operations
   - Reference for: Sprunje patterns, admin controllers, CRUD operations, data tables, permissions
   - Key patterns: Sprunje implementations, action controllers, admin routes

3. **[userfrosting/framework (6.0 branch)](https://github.com/userfrosting/framework/tree/6.0)**
   - Core framework components and interfaces
   - Reference for: Base interfaces, traits, service containers, testing utilities
   - Key patterns: Framework contracts, base classes, testing infrastructure

4. **[userfrosting/theme-pink-cupcake (6.0 branch)](https://github.com/userfrosting/theme-pink-cupcake/tree/6.0)**
   - Default theme implementation
   - Reference for: Frontend patterns, Vue.js components, UI/UX standards
   - Key patterns: Template structure, asset organization, frontend integration

### Code Modification Standards

#### 1. Follow UserFrosting 6 Patterns
- **Service Providers**: All services MUST implement `ServicesProviderInterface` and follow the DI container patterns from sprinkle-core
- **Controllers**: Use action-based controllers (one action per class) following sprinkle-admin patterns
- **Routes**: Implement `RouteDefinitionInterface` for route definitions
- **Models**: Extend Eloquent models and follow UserFrosting model patterns
- **Sprunje**: Use Sprunje pattern for data listing, filtering, and pagination
- **Middleware**: Follow middleware patterns from sprinkle-core

#### 2. Extend and Reuse Core Components
Before creating new components, check if UserFrosting 6 already provides:
- **Authentication/Authorization**: Use existing `AuthGuard` and permission system from sprinkle-account
- **Data Tables**: Extend Sprunje classes rather than creating custom solutions
- **Validation**: Use UserFrosting's validation system
- **Alerts/Notifications**: Use existing alert system from sprinkle-core
- **Caching**: Use `CacheService` from sprinkle-core
- **Internationalization**: Use `I18nService` for translations
- **Session Management**: Use `SessionService` from sprinkle-core

#### 3. Adhere to Framework Standards
- **PSR-12**: All code must follow PSR-12 coding standards
- **Type Declarations**: Use strict types (`declare(strict_types=1);`)
- **Dependency Injection**: Use constructor injection with PHP-DI
- **Logging**: ALWAYS use `DebugLoggerInterface` (injected as `$this->logger`) for debug logging
  - ❌ **DO NOT use `error_log()`** - this is not part of UserFrosting 6 standards
  - ✅ **USE `$this->logger->debug()`** instead with structured array parameters
  - Example: `$this->logger->debug("Message", ['key' => 'value'])`
  - Reference: See `app/src/Sprunje/CRUD6Sprunje.php` for proper usage
- **Naming Conventions**: Follow UserFrosting naming conventions
  - Controllers: `{Action}Action.php` (e.g., `CreateAction.php`)
  - Services: `{Name}Service.php` (e.g., `SchemaService.php`)
  - Service Providers: `{Name}ServiceProvider.php`
  - Sprunje: `{Model}Sprunje.php`
  - Middleware: `{Name}Injector.php` or `{Name}Middleware.php`
- **Folder Structure**: ONLY create folders when they contain actual content
  - DO NOT create empty folders or folders with only `.gitkeep` files
  - Runtime directories (cache, logs, sessions, storage, database) are excluded in `.gitignore` and created by the application
  - Test directories should ONLY contain CRUD6-specific tests, not copies from other sprinkles

#### 4. Middleware Injection Pattern
**⚠️ CRITICAL - SEE "CRITICAL PATTERNS" SECTION AT TOP OF FILE ⚠️**

UserFrosting 6 supports automatic injection of middleware-set request attributes as controller parameters. This is a core framework feature used throughout UserFrosting.

**THIS PATTERN IS WORKING CORRECTLY IN ALL CRUD6 CONTROLLERS - DO NOT MODIFY IT**

**Pattern from sprinkle-admin** (GroupApi + GroupInjector):
```php
// Middleware (GroupInjector)
class GroupInjector extends AbstractInjector
{
    protected string $attribute = 'group';  // Request attribute name
    
    protected function getInstance(?string $slug): GroupInterface
    {
        // Load and return the model instance
        return $group;
    }
}

// Controller (GroupApi)
class GroupApi
{
    public function __invoke(GroupInterface $group, Response $response): Response
    {
        // $group is automatically injected from request attribute 'group'
        // NO need to call $request->getAttribute('group')
    }
}
```

**CRUD6 Implementation** (CRUD6Injector + Controllers):
```php
// Middleware (CRUD6Injector.php) sets BOTH attributes
class CRUD6Injector extends AbstractInjector
{
    protected string $attribute = 'crudModel';  // Primary attribute for AbstractInjector
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // ... load schema and model ...
        
        // Set BOTH attributes - both will be auto-injected
        $request = $request
            ->withAttribute('crudModel', $instance)
            ->withAttribute('crudSchema', $schema);
        
        return $handler->handle($request);
    }
}

// Controllers receive BOTH as parameters - THIS IS THE CORRECT PATTERN
public function __invoke(
    array $crudSchema,                      // ✅ Auto-injected from 'crudSchema' attribute
    CRUD6ModelInterface $crudModel,         // ✅ Auto-injected from 'crudModel' attribute  
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    // Both $crudSchema and $crudModel are automatically available
    // NO need to call $request->getAttribute('crudSchema') or $request->getAttribute('crudModel')
}
```

**WHY THIS WORKS:**
- UserFrosting 6 extends Slim 4 with custom parameter resolution
- The framework inspects controller method parameters by name and type
- It automatically injects values from matching request attributes
- This is NOT standard Slim 4 behavior - it's a UserFrosting 6 enhancement
- Multiple parameters can be auto-injected (not limited to just one)

**ABSOLUTELY DO NOT:**
- ❌ Change controllers to retrieve from `$request->getAttribute('crudSchema')` or `$request->getAttribute('crudModel')`
- ❌ Remove `array $crudSchema, CRUD6ModelInterface $crudModel` parameters from controllers
- ❌ Assume Slim 4 doesn't support this (UserFrosting 6 does through its DI container!)
- ❌ Try to "fix" this pattern because it "looks wrong" - it's the correct UserFrosting 6 pattern
- ❌ Modify this pattern without checking ALL 8 controller files first
- ❌ Make changes based on assumptions - verify against working sprinkle-admin examples

**WHEN YOU SEE THIS PATTERN IN THE CODE:**
1. ✅ Leave it alone - it's working correctly
2. ✅ Trust that it matches the UserFrosting 6 framework design
3. ✅ Reference sprinkle-admin's GroupApi as confirmation
4. ✅ Check the git history - this was deliberately fixed in PR #119

**Reference:**
- [GroupApi.php](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupApi.php) - Official example
- [GroupInjector.php](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Middlewares/GroupInjector.php) - Official example
- [PR #119](https://github.com/ssnukala/sprinkle-crud6/pull/119) - Established this pattern for all CRUD6 controllers
- [.archive/MIDDLEWARE_INJECTION_PATTERN_CLARIFICATION.md](.archive/MIDDLEWARE_INJECTION_PATTERN_CLARIFICATION.md) - Detailed explanation

#### 5. Testing Standards
- Follow testing patterns from sprinkle-admin and sprinkle-account
- Use `RefreshDatabase` trait for database tests
- Use `AdminTestCase` or `AccountTestCase` as base classes
- Test service providers, controllers, and business logic separately
- Mock external dependencies appropriately

#### 6. Documentation Standards
- Document all public methods with PHPDoc blocks
- Include `@param`, `@return`, and `@throws` annotations
- Reference UserFrosting documentation patterns
- Keep inline comments minimal and meaningful

### Integration Guidelines

#### When Adding New Features
1. **Research First**: Check reference repositories for existing solutions
2. **Pattern Matching**: Match your implementation to UserFrosting patterns
3. **Component Reuse**: Extend existing components rather than creating new ones
4. **Consistency**: Maintain consistency with core sprinkles
5. **Testing**: Add tests following UserFrosting testing patterns

#### When Refactoring Code
1. **Review References**: Check how similar code is implemented in core sprinkles
2. **Service Container**: Ensure proper DI container usage
3. **Backwards Compatibility**: Maintain compatibility when possible
4. **Documentation**: Update documentation to reflect changes
5. **Testing**: Ensure existing tests pass and add new tests if needed

### Common Patterns to Follow

#### Service Provider Pattern (from sprinkle-core)
```php
class MyServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            MyService::class => \DI\autowire(MyService::class),
        ];
    }
}
```

#### Action Controller Pattern (from sprinkle-admin)
```php
class MyAction
{
    public function __construct(
        protected MyService $service,
        protected AlertStream $alert
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        // Action logic here
    }
}
```

#### Route Definition Pattern (from sprinkle-core)
```php
class MyRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->group('/api/myroute', function (RouteCollectorProxy $group) {
            $group->get('', ListAction::class)->setName('api.myroute.list');
            $group->post('', CreateAction::class)->setName('api.myroute.create');
        })->add(AuthGuard::class)->add(NoCache::class);
    }
}
```

#### Sprunje Pattern (from sprinkle-admin)
```php
class MySprunje extends Sprunje
{
    protected string $name = 'my_model';
    
    protected function baseQuery()
    {
        return $this->classMapper->createInstance(MyModel::class);
    }
}
```

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
- **JSON Schema Validation**: `php -r "echo json_encode(json_decode(file_get_contents('examples/schema/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"`

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
   - `php -r "echo json_decode(file_get_contents('examples/schema/products.json')) ? 'products.json valid' : 'products.json invalid';"`
3. **Schema Structure Check**: Verify required fields exist in JSON schemas:
   - `model`, `table`, `fields` keys must be present
   - Each field must have a `type` property
   - Valid types: string, integer, boolean, date, datetime, text, json, float, decimal
4. **API Route Structure**: Verify routes follow `/api/crud6/{model}` pattern in `app/src/Routes/CRUD6Routes.php`
5. **Controller Inheritance**: Check that controllers extend appropriate base classes in `app/src/Controller/Base/`

### CRITICAL Manual Validation Steps
After making ANY changes to CRUD6Model, schema files, or controllers:
1. Run full syntax check: `find app/src -name "*.php" -exec php -l {} \;`
2. Validate all JSON schemas: `for file in app/schema/crud6/*.json examples/schema/*.json; do php -r "if(json_decode(file_get_contents('$file'))) { echo '$file valid'; } else { echo '$file invalid'; } echo PHP_EOL;"; done`
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
2. Follow existing schema patterns in `examples/schema/products.json`
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
6. **Update Example Schemas**: After feature upgrades that affect schema structure, regenerate example schemas in `examples/schema/` using `php scripts/generate-test-schemas.php` and copy the updated schemas to keep them current for users of the sprinkle

### Working with Examples
- Review example schemas: `examples/schema/products.json` and `app/schema/crud6/users.json`
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

### Folder Creation Policy
**CRITICAL**: Do NOT create empty folders or folders with only `.gitkeep` files in this repository.

- **Runtime directories** (cache, logs, sessions, storage, database) are managed by the UserFrosting application at runtime and should NOT be created in the sprinkle repository
- **Test directories** should only contain CRUD6-specific tests, not tests from other UserFrosting sprinkles (e.g., admin sprinkle's Role/Group/User tests)
- Only create folders when they contain actual content relevant to the CRUD6 sprinkle
- The `.gitignore` file excludes runtime directories - they will be created by the application when needed
- If you find empty folders or non-CRUD6 test folders, they should be removed to keep the repository clean

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
3. Validate JSON schemas: `php -r "echo json_encode(json_decode(file_get_contents('examples/schema/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"`
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
php -r "echo json_encode(json_decode(file_get_contents('examples/schema/products.json')), JSON_PRETTY_PRINT) ? 'JSON valid' : 'JSON invalid';"

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

## Documentation Guidelines

### Archive Folder (.archive/)
All historical documentation, fix summaries, visual comparisons, and issue-specific guides should be created in the `.archive/` directory. This keeps the repository root clean and organized while preserving documentation for future reference.

**Important**: Files in `.archive/` ARE tracked by git and will be committed to the repository. The archive is not ignored - it's a permanent part of the repository structure.

#### Documentation Location Rules
- **Active Documentation** (keep in root):
  - `README.md` - Main project documentation
  - `CHANGELOG.md` - Version history
  - `INTEGRATION_TESTING.md` - Integration testing guide
  - `QUICK_TEST_GUIDE.md` - Quick reference for testing
  - `MIGRATION_FROM_THEME_CRUD6.md` - Migration guide

- **Historical Documentation** (place in `.archive/`):
  - All fix summaries (e.g., `*_FIX_SUMMARY.md`, `*_FIX.md`)
  - Visual comparison documents (e.g., `VISUAL_*.md`, `*_COMPARISON*.md`)
  - Issue-specific documentation (e.g., `ISSUE_*.md`, `PR*.md`)
  - Testing approach documents (e.g., `TESTING_APPROACH.md`, `TESTING_GUIDE.md`)
  - Implementation summaries (e.g., `*_IMPLEMENTATION_SUMMARY.md`)
  - Checklist documents (e.g., `*_CHECKLIST.md`)
  - Before/after comparison documents
  - Any other temporary or issue-specific documentation

#### Creating New Documentation
When creating documentation for fixes, features, or issues:
1. **Always create in `.archive/`**: All new documentation should go directly into `.archive/` unless it's a core documentation file
2. **Use descriptive names**: Name files clearly to indicate their purpose (e.g., `ISSUE_123_FIX_SUMMARY.md`, `FEATURE_XYZ_IMPLEMENTATION.md`)
3. **Include context**: Add issue/PR numbers and dates to help with future reference
4. **Keep root clean**: Never create fix summaries or temporary documentation in the repository root

#### Note
The `.archive/` directory is tracked by git and all files in it are committed to the repository. This approach keeps the repository root clean while maintaining a complete history of fixes and changes in an organized subdirectory.