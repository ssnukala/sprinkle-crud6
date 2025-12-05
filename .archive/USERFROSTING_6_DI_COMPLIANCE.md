# UserFrosting 6 Dependency Injection Compliance

## Issue Identified

The initial refactoring extracted service classes but didn't follow UserFrosting 6's dependency injection patterns:

**Problem:** Service classes were instantiated directly with `new` in SchemaService constructor:
```php
public function __construct(...) {
    $this->loader = new SchemaLoader();
    $this->validator = new SchemaValidator();
    $this->normalizer = new SchemaNormalizer();
    // etc...
}
```

**This violated UserFrosting 6 patterns because:**
1. Services should be registered in the DI container
2. Dependencies should be injected through constructor injection
3. `new` keyword should be avoided for service instantiation
4. Makes testing difficult (can't mock dependencies)

## Solution: Proper DI Container Registration

### 1. SchemaServiceProvider Updated

All service classes are now registered in the DI container:

```php
class SchemaServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            // Simple services use autowire
            SchemaLoader::class => \DI\autowire(SchemaLoader::class),
            SchemaValidator::class => \DI\autowire(SchemaValidator::class),
            SchemaNormalizer::class => \DI\autowire(SchemaNormalizer::class),
            SchemaFilter::class => \DI\autowire(SchemaFilter::class),
            SchemaTranslator::class => \DI\autowire(SchemaTranslator::class),
            
            // Services with optional dependencies use factory functions
            SchemaCache::class => function (ContainerInterface $c) {
                $config = $c->get(Config::class);
                $logger = $c->get(DebugLoggerInterface::class);
                
                // PSR-16 cache is optional
                $cache = null;
                if ($c->has(CacheInterface::class)) {
                    try {
                        $cache = $c->get(CacheInterface::class);
                    } catch (\Exception $e) {
                        // Cache not available, continue without it
                    }
                }
                
                return new SchemaCache($config, $logger, $cache);
            },
            
            // Services with inter-dependencies use factory functions
            SchemaActionManager::class => function (ContainerInterface $c) {
                return new SchemaActionManager(
                    $c->get(SchemaValidator::class),
                    $c->get(DebugLoggerInterface::class)
                );
            },
            
            // Main service receives all dependencies from container
            SchemaService::class => function (ContainerInterface $c) {
                return new SchemaService(
                    $c->get(ResourceLocatorInterface::class),
                    $c->get(Config::class),
                    $c->get(DebugLoggerInterface::class),
                    $c->get(Translator::class),
                    $c->get(SchemaLoader::class),
                    $c->get(SchemaValidator::class),
                    $c->get(SchemaNormalizer::class),
                    $c->get(SchemaCache::class),
                    $c->get(SchemaFilter::class),
                    $c->get(SchemaTranslator::class),
                    $c->get(SchemaActionManager::class)
                );
            },
        ];
    }
}
```

### 2. SchemaService Updated

Constructor now receives all dependencies through injection:

```php
public function __construct(
    protected ResourceLocatorInterface $locator,
    protected Config $config,
    protected DebugLoggerInterface $logger,
    protected Translator $i18n,
    protected SchemaLoader $loader,
    protected SchemaValidator $validator,
    protected SchemaNormalizer $normalizer,
    protected SchemaCache $cache,
    protected SchemaFilter $filter,
    protected SchemaTranslator $translator,
    protected SchemaActionManager $actionManager
) {
    // All services are now injected - no instantiation needed
}
```

### 3. Service Classes Updated

All service classes now require their dependencies (no optional parameters except where truly optional like PSR-16 cache):

**Before:**
```php
public function __construct(
    protected ?DebugLoggerInterface $logger = null
) {
}

protected function debugLog(string $message, array $context = []): void
{
    if ($this->logger === null) {
        return;
    }
    $this->logger->debug($message, $context);
}
```

**After:**
```php
public function __construct(
    protected DebugLoggerInterface $logger
) {
}

protected function debugLog(string $message, array $context = []): void
{
    $this->logger->debug($message, $context);
}
```

## Alignment with UserFrosting 6 Patterns

### Reference: sprinkle-core CacheService

Our implementation now follows the same pattern as UserFrosting's core services:

```php
// From sprinkle-core
class CacheServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            CacheInterface::class => \DI\autowire(ArrayCache::class),
            // Other services...
        ];
    }
}
```

### Reference: sprinkle-admin Services

Similar to how sprinkle-admin registers its services:

```php
// Pattern from sprinkle-admin
return [
    UserService::class => \DI\autowire(UserService::class),
    GroupService::class => \DI\autowire(GroupService::class),
    // etc...
];
```

## Benefits of Proper DI

### 1. Testability
```php
// Can now mock dependencies in tests
$mockLoader = $this->createMock(SchemaLoader::class);
$mockValidator = $this->createMock(SchemaValidator::class);

$service = new SchemaService(
    $locator, $config, $logger, $translator,
    $mockLoader, $mockValidator, ...
);
```

### 2. Flexibility
```php
// Can swap implementations
$container->set(SchemaCache::class, function ($c) {
    return new RedisSchemaCache(...); // Custom implementation
});
```

### 3. Single Responsibility
- Each service has clear dependencies
- No hidden dependencies or service locator anti-pattern
- Dependencies are explicit in constructor

### 4. Framework Consistency
- Follows UserFrosting 6 conventions
- Works seamlessly with other UF6 sprinkles
- Uses standard PSR-11 container patterns

## Comparison Table

| Aspect | Before | After |
|--------|--------|-------|
| Service Creation | `new SchemaLoader()` | `$c->get(SchemaLoader::class)` |
| Dependency Management | Hard-coded in constructor | Injected through container |
| Testability | Difficult (hard dependencies) | Easy (injectable mocks) |
| Flexibility | Fixed implementations | Swappable implementations |
| UserFrosting Compliance | ❌ Non-standard | ✅ Standard pattern |
| Service Reusability | Limited | Full (registered in container) |
| Optional Dependencies | Mixed with required | Clear separation |

## Migration Impact

**For Users:** None - the DI container handles all instantiation automatically.

```php
// Both before and after, users just do:
$schemaService = $container->get(SchemaService::class);

// The container resolves all dependencies automatically
```

**For Tests:** Improved - can now inject mocks:

```php
// Before: Couldn't inject custom services
$service = new SchemaService($locator, $config);

// After: Full control over dependencies
$service = new SchemaService(
    $locator,
    $config,
    $mockLogger,
    $mockTranslator,
    $mockLoader,
    $mockValidator,
    $mockNormalizer,
    $mockCache,
    $mockFilter,
    $mockTranslator,
    $mockActionManager
);
```

## Conclusion

The refactored code now fully complies with UserFrosting 6 dependency injection patterns:

✅ All services registered in DI container
✅ Constructor injection for dependencies
✅ No `new` instantiation in service classes
✅ Follows sprinkle-core and sprinkle-admin patterns
✅ Uses `\DI\autowire()` appropriately
✅ Factory functions for complex dependencies
✅ Clear separation of required vs optional dependencies
✅ Improved testability and maintainability

This ensures the code integrates properly with the UserFrosting 6 ecosystem and can be extended or customized through standard DI patterns.
