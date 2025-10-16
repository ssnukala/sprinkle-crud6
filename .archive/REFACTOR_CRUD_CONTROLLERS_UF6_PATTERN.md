# CRUD6 Controller Refactoring - UserFrosting 6 Pattern Implementation

**Date:** October 16, 2025  
**Issue:** Refactor CRUD functions to match UserFrosting 6 Admin Groups pattern  
**PR:** [Link to PR]

## Problem Statement

The CRUD6 controllers were not following the UserFrosting 6 patterns as demonstrated in the Admin Groups controllers. Key issues included:

1. **UpdateAction should not exist** - UF6 uses `EditAction` for both GET (read) and PUT (update) operations
2. **Manual service instantiation** - Controllers were instantiating `ServerSideValidator` and `RequestDataTransformer` directly, violating DI principles
3. **Inconsistent method structure** - Controllers didn't follow the `handle()` method pattern used in UF6
4. **Missing ApiResponse usage** - Not using UF6's `ApiResponse` utility for consistent API responses
5. **No activity logging** - Missing integration with `UserActivityLogger` for audit trails

## Reference Implementation

Based on UserFrosting Admin Sprinkle Group controllers:
- `GroupCreateAction.php` - Pattern for create operations
- `GroupEditAction.php` - Pattern for read/update operations
- `GroupDeleteAction.php` - Pattern for delete operations

Repository: https://github.com/userfrosting/sprinkle-admin (6.0 branch)

## Changes Made

### 1. CreateAction Refactoring

**Before:**
```php
class CreateAction extends Base
{
    // Manual instantiation of services
    protected function validateInputData(string $modelName, array $data): void
    {
        $requestSchema = new RequestSchema($rules);
        $transformer = new RequestDataTransformer($requestSchema);
        $validator = new ServerSideValidator($requestSchema);
        // ...
    }
}
```

**After:**
```php
class CreateAction
{
    public function __construct(
        protected RequestDataTransformer $transformer,
        protected ServerSideValidator $validator,
        protected UserActivityLogger $userActivityLogger,
        // ... other dependencies
    ) {}
    
    protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
    {
        // Implementation using injected services
    }
}
```

**Key Improvements:**
- Removed inheritance from Base class (not needed for CRUD actions)
- Injected `RequestDataTransformer` and `ServerSideValidator` via constructor
- Added `UserActivityLogger` for audit trails
- Implemented `handle()` method pattern
- Using `ApiResponse` for consistent responses
- Separated validation into dedicated methods

### 2. EditAction Refactoring

**Before:**
```php
class EditAction extends Base
{
    // Only handled GET requests for reading
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Read logic only
    }
}
```

**After:**
```php
class EditAction
{
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $method = $request->getMethod();
        
        if ($method === 'GET') {
            return $this->handleRead($crudSchema, $crudModel, $request, $response);
        }
        
        if ($method === 'PUT') {
            return $this->handleUpdate($crudSchema, $crudModel, $request, $response);
        }
    }
    
    protected function handleRead(...): Response { /* ... */ }
    protected function handleUpdate(...): Response { /* ... */ }
    protected function handle(...): CRUD6ModelInterface { /* ... */ }
}
```

**Key Improvements:**
- Removed inheritance from Base class
- Now handles both GET (read) and PUT (update) operations
- Injected validation services via constructor
- Added `UserActivityLogger` for audit trails
- Implemented `handle()` method pattern for updates
- Using `ApiResponse` for consistent responses
- Separated read and update logic into dedicated methods

### 3. DeleteAction Refactoring

**Before:**
```php
class DeleteAction extends Base
{
    public function __invoke(CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Direct implementation with manual error handling
    }
}
```

**After:**
```php
class DeleteAction
{
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Response $response): Response
    {
        $userMessage = $this->handle($crudSchema, $crudModel);
        $message = $this->translator->translate($userMessage->message, $userMessage->parameters);
        $payload = new ApiResponse($message);
        // ...
    }
    
    protected function handle(array $crudSchema, CRUD6ModelInterface $crudModel): UserMessage
    {
        // Implementation with transaction handling
    }
}
```

**Key Improvements:**
- Removed inheritance from Base class
- Added `UserActivityLogger` for audit trails
- Implemented `handle()` method pattern
- Using `UserMessage` and `ApiResponse` for consistent responses
- Cleaner transaction handling

### 4. UpdateAction Removal

**Action:** Deleted `app/src/Controller/UpdateAction.php`

**Rationale:** 
- In UserFrosting 6, the EditAction handles both GET (read) and PUT (update) operations
- Having a separate UpdateAction was inconsistent with UF6 patterns
- This matches the pattern used in Admin Groups where `GroupEditAction` handles both operations

### 5. Routes Update

**Before:**
```php
$group->get('/{id}', EditAction::class)->setName('api.crud6.read');
$group->put('/{id}', UpdateAction::class)->setName('api.crud6.update');
```

**After:**
```php
$group->get('/{id}', EditAction::class)->setName('api.crud6.read');
$group->put('/{id}', EditAction::class)->setName('api.crud6.update');
```

**Changes:**
- Both GET and PUT for `/{id}` now use `EditAction`
- Removed `UpdateAction` import from routes file
- Updated route comments to reflect EditAction handles both operations

## Pattern Compliance

### Dependency Injection ✅
All services are now injected via constructor:
- `RequestDataTransformer`
- `ServerSideValidator`
- `UserActivityLogger`
- `Translator`
- `Authenticator`
- `Connection`
- `SchemaService`

### Method Structure ✅
All actions follow the UF6 pattern:
```php
public function __invoke(...): Response
{
    $this->validateAccess(...);
    $result = $this->handle(...);
    // Create response with ApiResponse
}

protected function handle(...): ResultType
{
    // Get and validate data
    // Transaction handling
    // Activity logging
}
```

### Response Handling ✅
- Using `ApiResponse` utility for consistent API responses
- Using `UserMessage` for translatable messages
- Proper HTTP status codes (201 for create, etc.)

### Activity Logging ✅
All CRUD operations now log activities:
```php
$this->userActivityLogger->info("User {$currentUser->user_name} created {$modelDisplayName} record.", [
    'type'    => "crud6_{$schema['model']}_create",
    'user_id' => $currentUser->id,
]);
```

### Validation ✅
All validation uses injected services:
```php
protected function validateData(RequestSchemaInterface $schema, array $data): void
{
    $errors = $this->validator->validate($schema, $data);
    if (count($errors) !== 0) {
        $e = new ValidationException();
        $e->addErrors($errors);
        throw $e;
    }
}
```

## Base Controller Status

The `Base.php` controller is still used by:
- `ApiAction` - Handles API metadata/schema endpoints
- `SprunjeAction` - Handles data listing with Sprunje

These controllers appropriately extend Base as they need shared functionality for:
- Schema access methods
- Field configuration helpers
- Route parameter extraction
- Validation rules retrieval

## Testing

### Syntax Validation ✅
All PHP files pass syntax checks:
```bash
find app/src -name "*.php" -exec php -l {} \;
```

### No Breaking References ✅
Verified no remaining references to `UpdateAction` in:
- Source code (`app/src/`)
- Tests (`app/tests/`)
- Configuration files

### Integration Tests
Tests should be run to validate:
- POST `/api/crud6/{model}` - Create operations work correctly
- GET `/api/crud6/{model}/{id}` - Read operations work correctly
- PUT `/api/crud6/{model}/{id}` - Update operations work correctly
- DELETE `/api/crud6/{model}/{id}` - Delete operations work correctly

## Benefits

1. **Consistency** - Now matches UserFrosting 6 Admin patterns exactly
2. **Maintainability** - Following established patterns makes code easier to understand
3. **Testability** - Dependency injection makes unit testing simpler
4. **Auditability** - Activity logging provides complete audit trail
5. **Code Quality** - Proper separation of concerns with dedicated validation methods
6. **Framework Compliance** - Uses UF6 utilities (ApiResponse, UserMessage) properly

## Files Modified

- `app/src/Controller/CreateAction.php` - Complete refactor
- `app/src/Controller/EditAction.php` - Complete refactor, added PUT support
- `app/src/Controller/DeleteAction.php` - Complete refactor
- `app/src/Controller/UpdateAction.php` - **DELETED**
- `app/src/Routes/CRUD6Routes.php` - Updated to route PUT to EditAction

## Files Unchanged

- `app/src/Controller/Base.php` - Still used by ApiAction and SprunjeAction
- `app/src/Controller/ApiAction.php` - Appropriately extends Base
- `app/src/Controller/SprunjeAction.php` - Appropriately extends Base

## Validation Commands

```bash
# Syntax check all files
find app/src -name "*.php" -exec php -l {} \;

# Search for UpdateAction references (should return nothing)
grep -r "UpdateAction" app/ --include="*.php"

# Check controller directory
ls -la app/src/Controller/
```

## Future Considerations

1. Consider adding integration tests specifically for the refactored controllers
2. Monitor for any edge cases in PUT request handling by EditAction
3. Ensure frontend code properly handles the new response format with ApiResponse
4. Document the activity log format for administrators

## Conclusion

The CRUD6 controllers now fully comply with UserFrosting 6 patterns as demonstrated in the Admin Groups implementation. This refactoring improves code quality, maintainability, and consistency with the framework standards.
