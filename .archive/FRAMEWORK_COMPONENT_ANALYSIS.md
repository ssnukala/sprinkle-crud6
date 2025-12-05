# UserFrosting Framework Component Analysis

## Current Usage Review

Based on analysis of our refactored Schema services, here's what we're currently using and what we could leverage from the UserFrosting framework and monorepo.

### Currently Used UserFrosting Components

1. **YamlFileLoader** (`UserFrosting\Support\Repository\Loader\YamlFileLoader`)
   - Used in: SchemaLoader
   - Purpose: Loading JSON/YAML schema files
   - ✅ **Correctly using framework component**

2. **Config** (`UserFrosting\Config\Config`)
   - Used in: SchemaService, SchemaCache
   - Purpose: Configuration repository access
   - ✅ **Correctly using framework component**

3. **DebugLoggerInterface** (`UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface`)
   - Used in: All service classes
   - Purpose: Debug logging
   - ✅ **Correctly using framework component**

4. **Translator** (`UserFrosting\I18n\Translator`)
   - Used in: SchemaService, SchemaTranslator
   - Purpose: Internationalization
   - ✅ **Correctly using framework component**

5. **ResourceLocatorInterface** (`UserFrosting\UniformResourceLocator\ResourceLocatorInterface`)
   - Used in: SchemaService
   - Purpose: Locating schema files in sprinkle filesystem
   - ✅ **Correctly using framework component**

6. **ServicesProviderInterface** (`UserFrosting\ServicesProvider\ServicesProviderInterface`)
   - Used in: SchemaServiceProvider
   - Purpose: DI container service registration
   - ✅ **Correctly using framework component**

7. **CacheInterface** (`Psr\SimpleCache\CacheInterface`)
   - Used in: SchemaCache
   - Purpose: PSR-16 cache abstraction
   - ✅ **Correctly using PSR standard**

## Opportunities to Leverage Framework Components

### 1. Exception Handling ⚠️

**Current State:**
```php
// SchemaValidator.php
throw new \RuntimeException("Schema validation failed");
```

**Recommendation:**
Use UserFrosting's exception classes from the framework for better error handling and consistency.

**Available from Framework:**
- `UserFrosting\Support\Exception\BadRequestException` - For validation errors
- `UserFrosting\Support\Exception\NotFoundException` - For schema not found
- `UserFrosting\Sprinkle\Core\Exceptions\ValidationException` - For detailed validation errors

**Action:** Create custom exceptions extending UserFrosting base exceptions:
```php
namespace UserFrosting\Sprinkle\CRUD6\Exceptions;

use UserFrosting\Support\Exception\BadRequestException;

class SchemaValidationException extends BadRequestException
{
    // Custom schema validation exception
}

class SchemaNotFoundException extends NotFoundException
{
    // Custom schema not found exception
}
```

### 2. Repository Pattern 💡

**Current State:**
SchemaLoader directly uses YamlFileLoader without following repository pattern.

**Recommendation:**
Consider extending UserFrosting's Repository pattern for better abstraction.

**Available from Framework:**
- `UserFrosting\Support\Repository\Repository` - Base repository class
- `UserFrosting\Support\Repository\PathBuilder` - Path building utilities

**Evaluation:** 
- ✅ YamlFileLoader is appropriate for our use case
- ℹ️ Repository pattern might be overkill for simple schema loading
- **Decision:** Keep current implementation but document why

### 3. Validation Framework 🔍

**Current State:**
Custom validation logic in SchemaValidator.

**UserFrosting Framework Validation:**
The framework uses **Fortress** for validation (built on Valitron), but this is primarily for form/request validation, not schema structure validation.

**Available from Framework:**
- `UserFrosting\Fortress\RequestSchema` - For validating user input
- `UserFrosting\Fortress\RequestDataTransformer` - For transforming request data
- `UserFrosting\Fortress\ServerSideValidator` - Server-side validation

**Evaluation:**
- Fortress is designed for validating user input against schemas
- We need to validate schema structure itself
- Schema validation is meta-validation (validating the schema definition)
- **Decision:** Keep custom SchemaValidator - it serves a different purpose

### 4. Cache Store Pattern 💡

**Current State:**
Custom two-tier caching in SchemaCache.

**Framework Pattern:**
UserFrosting uses a similar pattern in sprinkle-core's cache service.

**Available from Framework:**
- PSR-16 `CacheInterface` abstraction (already used)
- Cache configuration patterns from sprinkle-core

**Evaluation:**
- ✅ Already following the framework pattern
- ✅ Using PSR-16 correctly
- **Decision:** Current implementation is optimal

### 5. Service Location Pattern 🔍

**Current State:**
SchemaLoader has hardcoded path `'schema://crud6/'`

**Framework Pattern:**
UserFrosting uses ResourceLocator stream wrappers (we're using this correctly).

**Recommendation:**
Make schema path configurable through Config.

**Enhancement:**
```php
class SchemaLoader
{
    public function __construct(protected Config $config)
    {
        $this->schemaPath = $config->get('crud6.schema_path', 'schema://crud6/');
    }
}
```

### 6. Event/Hook System 💡

**Opportunity:**
UserFrosting has an event system that could be useful for schema loading hooks.

**Available from Framework:**
- `UserFrosting\Event\EventDispatcher` - PSR-14 event dispatcher
- Event listener registration

**Potential Use Cases:**
- Fire events before/after schema loading
- Allow other sprinkles to modify schemas
- Hook into cache clear operations

**Example:**
```php
class SchemaLoadedEvent
{
    public function __construct(
        public string $model,
        public array $schema
    ) {}
}

// In SchemaService
$this->eventDispatcher->dispatch(new SchemaLoadedEvent($model, $schema));
```

**Evaluation:**
- Could enhance extensibility
- Not critical for current functionality
- **Decision:** Document as future enhancement

### 7. Debug/Profiling Tools 🔍

**Current State:**
Using DebugLoggerInterface for debug output.

**Framework Pattern:**
UserFrosting has debug bar integration and profiling tools.

**Available from Framework:**
- Debug bar integration
- Performance monitoring

**Evaluation:**
- ✅ Current debug logging is appropriate
- ℹ️ Could integrate with debug bar for better visibility
- **Decision:** Current implementation sufficient

## Recommended Immediate Actions

### Priority 1: Exception Handling ✅

Create proper exception classes:

1. Create `app/src/Exceptions/SchemaException.php` (base)
2. Create `app/src/Exceptions/SchemaValidationException.php`
3. Create `app/src/Exceptions/SchemaNotFoundException.php`
4. Update SchemaValidator and SchemaService to use these

### Priority 2: Configuration Enhancement ✅

Make schema path configurable:

1. Inject Config into SchemaLoader
2. Read schema path from config
3. Update SchemaServiceProvider

### Priority 3: Documentation 📝

Document why we're not using certain framework components:

1. Why custom validation vs Fortress
2. Why custom cache vs extending framework cache
3. Framework components we ARE using and why

## Components NOT Applicable

### 1. Fortress Validation
- **Reason:** Designed for user input validation, not schema structure validation
- **Our Need:** Meta-validation of schema definitions themselves

### 2. Eloquent Repository
- **Reason:** Schema loading is file-based, not database-based
- **Our Need:** File system access through ResourceLocator

### 3. Bakery Commands
- **Reason:** We might want these later for cache management
- **Future:** Consider adding Bakery commands for:
  - `php bakery crud6:cache-clear`
  - `php bakery crud6:schema-validate`

## Integration Checklist

- [x] Using ResourceLocatorInterface for file location
- [x] Using YamlFileLoader for schema loading
- [x] Using Config for configuration access
- [x] Using DebugLoggerInterface for logging
- [x] Using Translator for i18n
- [x] Using PSR-16 CacheInterface
- [x] Using ServicesProviderInterface for DI
- [ ] Using UserFrosting exceptions (RECOMMENDED)
- [ ] Configuration-driven schema path (RECOMMENDED)
- [ ] Event hooks for extensibility (FUTURE)
- [ ] Bakery commands (FUTURE)

## Conclusion

Our current implementation is **well-aligned** with UserFrosting 6 framework patterns. We're correctly using:

✅ Core services (Config, Logger, Translator)
✅ Resource location (ResourceLocatorInterface)
✅ File loading (YamlFileLoader)
✅ DI container patterns
✅ PSR standards (PSR-16 cache, PSR-3 logging)

**Recommended enhancements:**
1. Add proper exception classes extending UserFrosting exceptions
2. Make schema path configurable
3. Document our design decisions

**Not recommended:**
- Don't force-fit Fortress validation (different use case)
- Don't over-engineer with Repository pattern (YamlFileLoader is sufficient)
- Don't add events/hooks yet (YAGNI - add when needed)

The refactoring already follows UserFrosting 6 best practices. The recommendations above would further improve alignment and robustness.
