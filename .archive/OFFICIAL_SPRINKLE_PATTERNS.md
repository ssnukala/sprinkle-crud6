# Official UserFrosting 6 Sprinkle Patterns

**Date:** 2025-12-18  
**Research:** Analyzed sprinkle-core, sprinkle-account, and sprinkle-admin (6.0 branch)

## Investigation Summary

Cloned and analyzed all three official UserFrosting 6 sprinkles to understand the correct patterns for:
- Bakery command registration
- CI/Testing approach
- Repository structure

## Key Findings

### 1. Bakery Commands

#### sprinkle-core
- **Has Bakery commands**: YES (35 commands including migrate, seed, setup, etc.)
- **Has `bakery` file**: NO
- **Bakery directory**: `app/src/Bakery/` (commands + events + helpers)
- **Interface used**: `BakeryRecipe`
- **Method used**: `getBakeryCommands()`

**Commands include:**
```
AssetsBuildCommand, AssetsInstallCommand, BakeCommand, ClearCacheCommand,
DebugCommand, MigrateCommand, SeedCommand, ServeCommand, SetupCommand, etc.
```

#### sprinkle-account
- **Has Bakery commands**: YES (2 commands)
- **Has `bakery` file**: NO
- **Bakery directory**: `app/src/Bakery/`
- **Interface used**: `BakeryRecipe`
- **Method used**: `getBakeryCommands()`

**Commands include:**
```
CreateAdminUser.php
CreateUser.php
BakeCommandListener.php (event listener)
```

#### sprinkle-admin
- **Has Bakery commands**: NO
- **Has `bakery` file**: NO
- **Bakery directory**: Does not exist
- **Interface used**: N/A

### 2. CI/Testing

#### All three sprinkles:
- ❌ NO `.github/workflows/` directory
- ❌ NO CI workflows in the sprinkle repositories
- ❌ NO `phpunit.xml` in repository root
- ❌ NO automated testing infrastructure visible

**Conclusion**: Official sprinkles do NOT include CI/testing in the sprinkle repositories. Testing happens at the application level.

### 3. Bakery Command Registration Pattern

From `sprinkle-core/app/src/Core.php`:
```php
use UserFrosting\Sprinkle\BakeryRecipe;

class Core implements
    SprinkleRecipe,
    TwigExtensionRecipe,
    MarkdownExtensionRecipe,
    MigrationRecipe,
    EventListenerRecipe,
    MiddlewareRecipe,
    BakeryRecipe
{
    /**
     * Return an array of all registered Bakery Commands.
     *
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getBakeryCommands(): array
    {
        return [
            AssetsBuildCommand::class,
            AssetsUpdateCommand::class,
            // ... more commands
        ];
    }
}
```

From `sprinkle-account/app/src/Account.php`:
```php
use UserFrosting\Sprinkle\BakeryRecipe;

class Account implements
    SprinkleRecipe,
    TwigExtensionRecipe,
    MigrationRecipe,
    SeedRecipe,
    EventListenerRecipe,
    BakeryRecipe
{
    /**
     * @return string[]
     */
    public function getBakeryCommands(): array
    {
        return [
            CreateAdminUser::class,
            CreateUser::class,
        ];
    }
}
```

### 4. The Correct Pattern

#### ✅ DO
1. **Create command classes** in `app/src/Bakery/`
2. **Implement `BakeryRecipe`** interface in main sprinkle class
3. **Use `getBakeryCommands()`** method to register commands
4. **Return array of command classes** from `getBakeryCommands()`
5. **Add `@codeCoverageIgnore`** annotation to the method
6. **Use standalone scripts** for CI testing (if needed)

#### ❌ DON'T
1. **DON'T create a `bakery` file** in the sprinkle repository
2. **DON'T use `CommandRecipe`** interface (doesn't exist in UF6)
3. **DON'T use `getCommands()`** method name
4. **DON'T add `.github/workflows`** for CI in sprinkle repo
5. **DON'T try to test bakery commands** in sprinkle isolation

## CRUD6 Implementation

### What We Had Wrong

**Before:**
```php
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\CommandRecipe; // WRONG

class CRUD6 implements SprinkleRecipe, MigrationRecipe, SeedRecipe, CommandRecipe // WRONG
{
    public function getCommands(): array // WRONG METHOD NAME
    {
        return [
            GenerateSchemaCommand::class,
        ];
    }
}
```

Plus we had a `bakery` file in the repository root (WRONG).

### What We Fixed

**After:**
```php
use UserFrosting\Sprinkle\BakeryRecipe; // CORRECT

class CRUD6 implements SprinkleRecipe, MigrationRecipe, SeedRecipe, BakeryRecipe // CORRECT
{
    /**
     * Return an array of all registered Bakery Commands.
     *
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    public function getBakeryCommands(): array // CORRECT METHOD NAME
    {
        return [
            GenerateSchemaCommand::class,
        ];
    }
}
```

And removed the `bakery` file from repository.

## Testing Approach

### Official Sprinkles
- No CI in sprinkle repositories
- Testing happens at application level
- Commands are tested when sprinkle is used in a UserFrosting application

### CRUD6 Approach
Since we DO have CI testing (unlike official sprinkles), we use:
- **Standalone script** for CI: `php scripts/generate-test-schemas.php`
- **Bakery command** for production: `php bakery crud6:generate-schema`
- **Utility classes** for programmatic use: `SchemaGenerator::generateToPath()`

This gives us:
1. ✅ CI testing without needing full UF application
2. ✅ Bakery command available when installed in UF application  
3. ✅ Programmatic API for other sprinkles

## Repository Structure Comparison

### Official Sprinkles Structure
```
sprinkle-core/
├── app/
│   ├── src/
│   │   ├── Bakery/          # Bakery commands here
│   │   │   ├── MigrateCommand.php
│   │   │   ├── SeedCommand.php
│   │   │   └── ...
│   │   └── Core.php         # Main sprinkle class with getBakeryCommands()
│   ├── config/
│   ├── locale/
│   └── templates/
├── composer.json
└── README.md

NO bakery file
NO .github/workflows/
NO phpunit.xml
```

### CRUD6 Structure (Correct)
```
sprinkle-crud6/
├── app/
│   ├── src/
│   │   ├── Bakery/          # Bakery commands here
│   │   │   └── GenerateSchemaCommand.php
│   │   ├── Schema/          # Utility classes
│   │   │   ├── SchemaBuilder.php
│   │   │   └── SchemaGenerator.php
│   │   └── CRUD6.php        # Main sprinkle class with getBakeryCommands()
│   ├── tests/               # Our tests (not in official sprinkles)
│   └── locale/
├── scripts/                 # Standalone scripts for CI (our addition)
│   ├── generate-test-schemas.php
│   ├── SchemaBuilder.php
│   └── GenerateSchemas.php
├── .github/
│   └── workflows/           # Our CI (not in official sprinkles)
│       └── unit-tests.yml
├── composer.json
└── README.md

NO bakery file (removed!)
```

## Differences from Official Sprinkles

### What We Do Differently (OK)
1. **We have CI testing** - Official sprinkles don't have CI in their repos
2. **We have standalone scripts** - Needed for CI testing
3. **We have phpunit.xml** - For running our tests
4. **We have utility classes** - SchemaBuilder/SchemaGenerator for programmatic use

### What Must Match Official Pattern (FIXED)
1. ✅ NO `bakery` file in repository
2. ✅ Use `BakeryRecipe` interface
3. ✅ Use `getBakeryCommands()` method
4. ✅ Commands in `app/src/Bakery/` directory
5. ✅ Return array of command class names

## Why This Pattern?

### Separation of Concerns
- **Sprinkle** = Defines commands (registration only)
- **Application** = Provides bakery CLI (execution environment)
- **Framework** = Orchestrates everything (connects sprinkles to bakery)

### Flexibility
- Sprinkles can be tested in isolation (our choice)
- Sprinkles can be used in any UF application
- Commands automatically available when sprinkle is loaded
- No need for sprinkle to know about bakery bootstrap

### Convention
- All official UF6 sprinkles follow this pattern
- Framework expects this pattern
- Third-party sprinkles should follow this pattern

## Summary

**The official UserFrosting 6 sprinkle pattern is:**
1. Define bakery commands in `app/src/Bakery/`
2. Implement `BakeryRecipe` interface
3. Return command classes from `getBakeryCommands()`
4. NO `bakery` file in sprinkle repository
5. Commands automatically available when sprinkle installed in UF app

**CRUD6 now follows this pattern correctly** ✅
