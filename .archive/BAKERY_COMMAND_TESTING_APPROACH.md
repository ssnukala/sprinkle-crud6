# Bakery Command Testing Approach

**Date:** 2025-12-18  
**Issue:** How to test bakery commands in sprinkle isolation

## Decision: Use Standalone Scripts for CI

After reviewing how UserFrosting 6 official sprinkles (sprinkle-core, sprinkle-account, sprinkle-admin) handle this, the correct approach is:

### ❌ What We DON'T Do
- **DO NOT** create a `bakery` file in the sprinkle repository
- Individual sprinkles do not provide their own bakery bootstrap
- The `bakery` command is part of the main UserFrosting application, not sprinkles

### ✅ What We DO Instead
- Use standalone scripts in `scripts/` folder for CI testing
- The bakery command (`crud6:generate-schema`) is available when the sprinkle is installed in a UserFrosting application
- CI tests use `php scripts/generate-test-schemas.php` which doesn't require bakery

## How Official Sprinkles Handle This

### sprinkle-admin, sprinkle-account, sprinkle-core
- ✅ No `bakery` file in sprinkle repositories
- ✅ Bakery commands are defined in `app/src/Bakery/` directory
- ✅ Commands are registered via `CommandRecipe` interface in main sprinkle class
- ✅ CI workflows don't test bakery commands directly (they test the underlying functionality)
- ✅ Bakery commands are tested when sprinkle is used in a full UserFrosting application

## Our Approach

### For CI Testing (Sprinkle in Isolation)
```yaml
# .github/workflows/unit-tests.yml
- name: Generate CRUD6 Schema Files and Translations
  run: php scripts/generate-test-schemas.php
```

Uses standalone script that:
- Doesn't require composer autoloader
- Doesn't require UserFrosting application context
- Works in isolated sprinkle testing environment

### For Production Use (Sprinkle in UserFrosting App)
```bash
# When sprinkle is installed in a UserFrosting application
php bakery crud6:generate-schema
php bakery crud6:generate-schema --schema-dir=custom/path
```

The bakery command:
- Is registered in `app/src/CRUD6.php` via `CommandRecipe`
- Uses the namespaced utilities in `app/src/Schema/`
- Works through the application's bakery CLI

### For Programmatic Use (Other Sprinkles)
```php
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator;

// Direct use of the utility classes
SchemaGenerator::generateToPath('path/to/schemas', 'path/to/locale');
```

## Testing Strategy

### What Gets Tested in CI
1. ✅ Standalone script functionality (`scripts/generate-test-schemas.php`)
2. ✅ Schema generation logic (via standalone script)
3. ✅ Translation merging (via standalone script)
4. ✅ Utility classes syntax and structure (via PHP unit tests)

### What Gets Tested in Production
1. ✅ Bakery command registration
2. ✅ Command execution in UserFrosting application context
3. ✅ Integration with UserFrosting's DI container
4. ✅ Interactive command features (prompts, confirmations, etc.)

## Why This Approach

### Advantages
- **Follows UserFrosting conventions** - No sprinkle has its own bakery file
- **CI independence** - Tests don't require full application bootstrap
- **Fast CI execution** - Standalone scripts are faster than full app bootstrap
- **Flexibility** - Provides both CI testing and production bakery command

### Pattern from Official Sprinkles
Looking at UserFrosting 6 official sprinkles:
- They define bakery commands in `app/src/Bakery/`
- They register commands via `CommandRecipe`
- They DON'T include a `bakery` file
- Their CI workflows test functionality, not bakery commands directly

## Conclusion

The correct approach for CRUD6 sprinkle is:
1. ✅ Define bakery command in `app/src/Bakery/GenerateSchemaCommand.php`
2. ✅ Register command in `app/src/CRUD6.php` via `CommandRecipe`
3. ✅ Use standalone script for CI testing
4. ✅ Document that bakery command is available in UserFrosting applications
5. ❌ Do NOT create a `bakery` file in the sprinkle repository

This matches the pattern used by all official UserFrosting 6 sprinkles.
