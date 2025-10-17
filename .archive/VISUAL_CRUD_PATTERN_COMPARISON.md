# CRUD6 Controller Refactoring - Visual Pattern Comparison

## Before: Non-UF6 Pattern

```
┌─────────────────────────────────────────────────────────┐
│                    CRUD Operations                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  CreateAction (extends Base)                           │
│  ├─ Manual new RequestSchema()                         │
│  ├─ Manual new RequestDataTransformer()                │
│  ├─ Manual new ServerSideValidator()                   │
│  └─ Direct __invoke() implementation                   │
│                                                         │
│  EditAction (extends Base)                             │
│  └─ Only handles GET (read)                            │
│                                                         │
│  UpdateAction (extends Base)  ← PROBLEM!               │
│  ├─ Manual new RequestSchema()                         │
│  ├─ Manual new RequestDataTransformer()                │
│  ├─ Manual new ServerSideValidator()                   │
│  └─ Direct __invoke() implementation                   │
│                                                         │
│  DeleteAction (extends Base)                           │
│  └─ Direct __invoke() implementation                   │
│                                                         │
└─────────────────────────────────────────────────────────┘

Routes:
  GET  /{id} → EditAction
  PUT  /{id} → UpdateAction  ← INCONSISTENT!
```

## After: UF6 Admin Groups Pattern

```
┌─────────────────────────────────────────────────────────┐
│                    CRUD Operations                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  CreateAction (standalone)                             │
│  ├─ Injected RequestDataTransformer                    │
│  ├─ Injected ServerSideValidator                       │
│  ├─ Injected UserActivityLogger                        │
│  ├─ __invoke() → handle()                              │
│  ├─ Uses ApiResponse                                   │
│  └─ Proper validation methods                          │
│                                                         │
│  EditAction (standalone)                               │
│  ├─ Injected RequestDataTransformer                    │
│  ├─ Injected ServerSideValidator                       │
│  ├─ Injected UserActivityLogger                        │
│  ├─ Handles GET (read) via handleRead()                │
│  ├─ Handles PUT (update) via handleUpdate()            │
│  ├─ __invoke() → handle()                              │
│  ├─ Uses ApiResponse                                   │
│  └─ Proper validation methods                          │
│                                                         │
│  DeleteAction (standalone)                             │
│  ├─ Injected UserActivityLogger                        │
│  ├─ __invoke() → handle()                              │
│  ├─ Uses ApiResponse + UserMessage                     │
│  └─ Proper validation methods                          │
│                                                         │
└─────────────────────────────────────────────────────────┘

Routes:
  GET  /{id} → EditAction  ← CONSISTENT!
  PUT  /{id} → EditAction  ← CONSISTENT!
```

## Pattern Comparison Table

| Aspect | Before (Non-UF6) | After (UF6 Pattern) |
|--------|------------------|---------------------|
| **Service Injection** | ❌ Manual `new` instantiation | ✅ Constructor injection |
| **EditAction** | GET only | ✅ GET + PUT operations |
| **UpdateAction** | ❌ Separate class | ✅ Removed (not needed) |
| **Method Structure** | Direct `__invoke()` | ✅ `__invoke()` → `handle()` |
| **Response Handling** | Manual JSON encoding | ✅ `ApiResponse` utility |
| **Activity Logging** | ❌ None | ✅ `UserActivityLogger` |
| **Validation** | Inline with manual objects | ✅ Dedicated methods |
| **Base Class** | All extend `Base` | ✅ Only API/Sprunje extend `Base` |
| **Transaction Handling** | Mixed | ✅ Consistent DB transactions |
| **Error Messages** | Manual translation | ✅ `UserMessage` objects |

## Code Pattern Comparison

### Constructor Injection

**Before:**
```php
class CreateAction extends Base
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        // ... minimal dependencies
    ) {
        parent::__construct($authorizer, $authenticator, $logger, $schemaService);
    }
}
```

**After (UF6 Pattern):**
```php
class CreateAction  // No inheritance!
{
    public function __construct(
        protected Translator $translator,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected Connection $db,
        protected SchemaService $schemaService,
        protected UserActivityLogger $userActivityLogger,  // NEW!
        protected RequestDataTransformer $transformer,     // NEW!
        protected ServerSideValidator $validator,          // NEW!
    ) {}
}
```

### Validation Pattern

**Before:**
```php
protected function validateInputData(string $modelName, array $data): void
{
    $rules = $this->getValidationRules($modelName);
    if (!empty($rules)) {
        $requestSchema = new RequestSchema($rules);           // ❌ Manual
        $transformer = new RequestDataTransformer($requestSchema); // ❌ Manual
        $transformedData = $transformer->transform($data);
        $validator = new ServerSideValidator($requestSchema); // ❌ Manual
        $errors = $validator->validate($transformedData);
        // ...
    }
}
```

**After (UF6 Pattern):**
```php
protected function validateData(RequestSchemaInterface $schema, array $data): void
{
    $errors = $this->validator->validate($schema, $data);  // ✅ Injected service
    if (count($errors) !== 0) {
        $e = new ValidationException();
        $e->addErrors($errors);
        throw $e;
    }
}
```

### Handle Method Pattern

**Before:**
```php
public function __invoke(CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
{
    // All logic directly in __invoke()
    $data = $request->getParsedBody();
    $this->validateInputData($modelName, $data);
    try {
        $insertData = $this->prepareInsertData($schema, $data);
        $insertId = $this->db->table($table)->insertGetId($insertData);
        // ... manual response building
    } catch (\Exception $e) {
        // ... manual error handling
    }
}
```

**After (UF6 Pattern):**
```php
public function __invoke(CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
{
    $modelName = $this->getModelNameFromRequest($request);
    $schema = $this->schemaService->getSchema($modelName);
    
    $this->validateAccess($modelName, $schema);
    $record = $this->handle($crudModel, $schema, $request);  // ✅ Delegate to handle()

    // Write response using ApiResponse
    $message = $this->translator->translate('CRUD6.CREATE.SUCCESS', ['model' => $modelDisplayName]);
    $payload = new ApiResponse($message);  // ✅ UF6 utility
    $response->getBody()->write((string) $payload);

    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
}

protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
{
    // Get POST parameters
    $params = (array) $request->getParsedBody();

    // Load the request schema
    $requestSchema = $this->getRequestSchema($schema);

    // Whitelist and set parameter defaults
    $data = $this->transformer->transform($requestSchema, $params);  // ✅ Injected

    // Validate request data
    $this->validateData($requestSchema, $data);  // ✅ Dedicated method

    // Transaction with activity logging
    $record = $this->db->transaction(function () use ($crudModel, $schema, $data, $currentUser) {
        $insertData = $this->prepareInsertData($schema, $data);
        $table = $crudModel->getTable();
        $primaryKey = $schema['primary_key'] ?? 'id';
        $insertId = $this->db->table($table)->insertGetId($insertData, $primaryKey);
        $crudModel = $crudModel->newQuery()->find($insertId);

        // Activity logging
        $this->userActivityLogger->info("User {$currentUser->user_name} created record.", [
            'type'    => "crud6_{$schema['model']}_create",
            'user_id' => $currentUser->id,
        ]);

        return $crudModel;
    });

    return $record;
}
```

### EditAction: Handling Multiple HTTP Methods

**Before:**
```php
class EditAction extends Base
{
    public function __invoke(..., Request $request, Response $response): Response
    {
        // Only handles GET requests
        $recordId = $crudModel->getAttribute($primaryKey);
        return $response->withJson($crudModel->toArray());
    }
}

// Separate UpdateAction for PUT requests ❌
class UpdateAction extends Base { /* ... */ }
```

**After (UF6 Pattern):**
```php
class EditAction
{
    public function __invoke(array $crudSchema, CRUD6ModelInterface $crudModel, Request $request, Response $response): Response
    {
        $method = $request->getMethod();
        
        if ($method === 'GET') {
            return $this->handleRead($crudSchema, $crudModel, $request, $response);  // ✅
        }
        
        if ($method === 'PUT') {
            return $this->handleUpdate($crudSchema, $crudModel, $request, $response);  // ✅
        }
        
        return $response->withStatus(405);  // Method not allowed
    }

    protected function handleRead(...): Response { /* GET logic */ }
    protected function handleUpdate(...): Response { /* PUT logic */ }
    protected function handle(...): CRUD6ModelInterface { /* Core update logic */ }
}
```

## Benefits Summary

### Code Quality
- ✅ **Testability**: Easier to mock injected dependencies
- ✅ **Maintainability**: Follows established UF6 patterns
- ✅ **Readability**: Clear separation of concerns
- ✅ **Consistency**: All CRUD actions follow same pattern

### Framework Compliance
- ✅ **Dependency Injection**: Proper DI container usage
- ✅ **Response Utilities**: Using ApiResponse and UserMessage
- ✅ **Activity Logging**: Integrated audit trail
- ✅ **Error Handling**: Consistent exception handling

### Architecture
- ✅ **Single Responsibility**: Each action has one purpose
- ✅ **DRY Principle**: Shared logic in dedicated methods
- ✅ **SOLID Principles**: Proper dependency management
- ✅ **RESTful Design**: EditAction handles related GET/PUT operations

## Migration Path

For developers updating existing CRUD6 implementations:

1. **CreateAction**: Update constructor to inject `RequestDataTransformer`, `ServerSideValidator`, and `UserActivityLogger`
2. **EditAction**: No changes needed in routes - now handles both GET and PUT automatically
3. **UpdateAction**: Remove references - EditAction handles updates now
4. **DeleteAction**: Update to use `UserActivityLogger` if needed
5. **Routes**: Change PUT `/{id}` route from UpdateAction to EditAction

## Reference

Based on UserFrosting Admin Sprinkle (6.0 branch):
- https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupCreateAction.php
- https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupEditAction.php
- https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupDeleteAction.php
