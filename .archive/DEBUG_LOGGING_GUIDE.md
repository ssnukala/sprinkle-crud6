# Debug Logging Guide for CRUD6 Sprinkle

This document provides a comprehensive guide to the debug logging added to the CRUD6 sprinkle to help diagnose API errors between frontend and backend.

## Overview

Debug statements have been added throughout the CRUD6 sprinkle to provide detailed visibility into:
- API request flow from frontend to backend
- Data transformation and validation
- Database operations
- Response generation
- Error conditions with full context

## Log Format Conventions

All logs follow consistent patterns for easy identification and filtering:

### Backend (PHP)
- **Prefix**: `CRUD6 [ComponentName]` (e.g., `CRUD6 [CreateAction]`, `CRUD6 [CRUD6Injector]`)
- **Section Markers**: `===== START/FAILED/COMPLETE =====`
- **Context**: Structured arrays with model name, IDs, data, etc.

Example:
```php
$this->logger->debug("CRUD6 [CreateAction] ===== CREATE REQUEST START =====", [
    'model' => $crudSchema['model'],
    'method' => $request->getMethod(),
    'uri' => (string) $request->getUri(),
]);
```

### Frontend (TypeScript/Vue)
- **Prefix**: `[useCRUD6Api]` or `[Form]`
- **Section Markers**: `===== START/FAILED/SUCCESS =====`
- **Context**: Objects with model, URL, data, errors, etc.

Example:
```typescript
console.log('[useCRUD6Api] ===== CREATE ROW REQUEST START =====', {
    model,
    url,
    data,
})
```

## Backend Components

### 1. CRUD6Injector Middleware

**Location**: `app/src/Middlewares/CRUD6Injector.php`

**Purpose**: Loads model and schema, injects into request

**Debug Points**:
- Middleware process start
- Route parsing (model name, connection)
- Model parameter validation
- Schema loading
- Model configuration
- Record lookup by ID
- Injection completion

**Key Logs**:
```php
// Process start
"CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS START ====="

// Model and connection parsing
"CRUD6 [CRUD6Injector] Parsed model with connection"
"CRUD6 [CRUD6Injector] Parsed model (no connection override)"

// Schema and model loading
"CRUD6 [CRUD6Injector] Schema loaded and model configured"

// Record lookup
"CRUD6 [CRUD6Injector] Looking up record by ID"
"CRUD6 [CRUD6Injector] Record found and loaded"
"CRUD6 [CRUD6Injector] Record not found" (ERROR)

// Process complete
"CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS COMPLETE ====="
```

### 2. CreateAction Controller

**Location**: `app/src/Controller/CreateAction.php`

**Purpose**: Handles POST requests to create new records

**Debug Points**:
- Request start
- Access validation
- Parameter parsing and transformation
- Data validation (success/failure)
- Database insert operations
- Transaction completion
- Response preparation
- Error handling

**Key Logs**:
```php
// Request start
"CRUD6 [CreateAction] ===== CREATE REQUEST START ====="

// Data processing
"CRUD6 [CreateAction] Request parameters received"
"CRUD6 [CreateAction] Data transformed"
"CRUD6 [CreateAction] Starting validation"
"CRUD6 [CreateAction] Validation successful"

// Database operations
"CRUD6 [CreateAction] Insert data prepared"
"CRUD6 [CreateAction] Record inserted into database"
"CRUD6 [CreateAction] Created record loaded from database"
"CRUD6 [CreateAction] Transaction completed successfully"

// Response
"CRUD6 [CreateAction] Response prepared successfully"

// Errors
"CRUD6 [CreateAction] Validation failed" (ERROR)
"CRUD6 [CreateAction] ===== CREATE REQUEST FAILED =====" (ERROR)
```

### 3. EditAction Controller

**Location**: `app/src/Controller/EditAction.php`

**Purpose**: Handles GET (read) and PUT (update) requests

**Debug Points**:
- Request start with method detection
- GET request handling
- PUT request handling
- Data transformation and validation
- Database update operations
- Model refresh
- Transaction completion
- Response preparation

**Key Logs**:
```php
// Request start
"CRUD6 [EditAction] ===== REQUEST START ====="
"CRUD6 [EditAction] Processing GET request (read)"
"CRUD6 [EditAction] Processing PUT request (update)"

// Read operation
"CRUD6 [EditAction] Record data retrieved"
"CRUD6 [EditAction] Read response prepared"

// Update operation
"CRUD6 [EditAction] Update parameters received"
"CRUD6 [EditAction] Data transformed"
"CRUD6 [EditAction] Data validation passed"
"CRUD6 [EditAction] Update data prepared"
"CRUD6 [EditAction] Database update executed"
"CRUD6 [EditAction] Model refreshed after update"
"CRUD6 [EditAction] Transaction completed successfully"
"CRUD6 [EditAction] Update response prepared"

// Errors
"CRUD6 [EditAction] Validation failed" (ERROR)
"CRUD6 [EditAction] ===== REQUEST FAILED =====" (ERROR)
```

### 4. DeleteAction Controller

**Location**: `app/src/Controller/DeleteAction.php`

**Purpose**: Handles DELETE requests to remove records

**Debug Points**:
- Request start
- Access validation
- Soft delete vs hard delete detection
- Delete operation execution
- Transaction completion
- Response preparation

**Key Logs**:
```php
// Request start
"CRUD6 [DeleteAction] ===== DELETE REQUEST START ====="

// Delete operation
"CRUD6 [DeleteAction] Starting delete operation"
"CRUD6 [DeleteAction] Soft deleted record"
"CRUD6 [DeleteAction] Hard deleted record"
"CRUD6 [DeleteAction] Transaction completed successfully"

// Response
"CRUD6 [DeleteAction] Delete response prepared"

// Errors
"CRUD6 [DeleteAction] ===== DELETE REQUEST FAILED =====" (ERROR)
```

### 5. UpdateFieldAction Controller

**Location**: `app/src/Controller/UpdateFieldAction.php`

**Purpose**: Handles PUT requests to update a single field

**Debug Points**:
- Request start with field name
- Field validation (exists, not readonly)
- Access validation
- Parameter parsing
- Data validation and transformation
- Database update with old/new values
- Transaction commit/rollback
- Response preparation

**Key Logs**:
```php
// Request start
"CRUD6 [UpdateFieldAction] ===== UPDATE FIELD REQUEST START ====="

// Field validation
"CRUD6 [UpdateFieldAction] Field does not exist" (ERROR)
"CRUD6 [UpdateFieldAction] Attempt to update readonly field" (WARNING)

// Data processing
"CRUD6 [UpdateFieldAction] Request parameters received"
"CRUD6 [UpdateFieldAction] Validation passed"
"CRUD6 [UpdateFieldAction] Data transformed"

// Database operations
"CRUD6 [UpdateFieldAction] Field value updated"
"CRUD6 [UpdateFieldAction] Model saved to database"
"CRUD6 [UpdateFieldAction] Transaction committed"

// Errors
"CRUD6 [UpdateFieldAction] Validation failed" (ERROR)
"CRUD6 [UpdateFieldAction] Transaction rolled back" (ERROR)
"CRUD6 [UpdateFieldAction] ===== UPDATE FIELD REQUEST FAILED =====" (ERROR)
```

### 6. SprunjeAction Controller

**Location**: `app/src/Controller/SprunjeAction.php`

**Purpose**: Handles GET requests for listing, filtering, sorting, and pagination

**Debug Points**:
- Request start with query parameters
- Relation detection and handling
- Schema loading for relations
- Sprunje configuration
- Field configuration (sortable, filterable, listable, searchable)
- Response generation

**Key Logs**:
```php
// Request start
"CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST START ====="

// Relation handling
"CRUD6 [SprunjeAction] Handling detail relation"
"CRUD6 [SprunjeAction] Related schema loaded"
"CRUD6 [SprunjeAction] Setting up relation sprunje"

// Configuration
"CRUD6 [SprunjeAction] Sprunje configuration prepared"
"CRUD6 [SprunjeAction] Setting up main model sprunje"

// Response
"CRUD6 [SprunjeAction] Relation sprunje configured, returning response"
"CRUD6 [SprunjeAction] Main sprunje configured, returning response"

// Errors
"CRUD6 [SprunjeAction] ===== SPRUNJE REQUEST FAILED =====" (ERROR)
```

## Frontend Components

### 1. useCRUD6Api Composable

**Location**: `app/assets/composables/useCRUD6Api.ts`

**Purpose**: API client for all CRUD operations

**Debug Points** (all operations):
- Request start with URL and data
- Response received with status and data
- Error with full context
- Request completion with loading state

**Key Logs**:

#### fetchRow
```typescript
console.log('[useCRUD6Api] ===== FETCH ROW REQUEST START =====', {
    model, id, url
})
console.log('[useCRUD6Api] Fetch row response received', {
    model, id, status, data
})
console.error('[useCRUD6Api] ===== FETCH ROW REQUEST FAILED =====', {
    model, id, url, error, response, responseData, status
})
```

#### createRow
```typescript
console.log('[useCRUD6Api] ===== CREATE ROW REQUEST START =====', {
    model, url, data
})
console.log('[useCRUD6Api] Create row response received', {
    model, status, data, title, description
})
console.error('[useCRUD6Api] ===== CREATE ROW REQUEST FAILED =====', {
    model, url, requestData, error, response, responseData, status
})
```

#### updateRow
```typescript
console.log('[useCRUD6Api] ===== UPDATE ROW REQUEST START =====', {
    model, id, url, data
})
console.log('[useCRUD6Api] Update row response received', {
    model, id, status, data, title, description
})
console.error('[useCRUD6Api] ===== UPDATE ROW REQUEST FAILED =====', {
    model, id, url, requestData, error, response, responseData, status, headers
})
```

#### updateField
```typescript
console.log('[useCRUD6Api] ===== UPDATE FIELD REQUEST START =====', {
    model, id, field, value, url
})
console.log('[useCRUD6Api] Update field response received', {
    model, id, field, status, data, title, description
})
console.error('[useCRUD6Api] ===== UPDATE FIELD REQUEST FAILED =====', {
    model, id, field, value, url, requestData, error, response, responseData, status
})
```

#### deleteRow
```typescript
console.log('[useCRUD6Api] ===== DELETE ROW REQUEST START =====', {
    model, id, url
})
console.log('[useCRUD6Api] Delete row response received', {
    model, id, status, data, title, description
})
console.error('[useCRUD6Api] ===== DELETE ROW REQUEST FAILED =====', {
    model, id, url, error, response, responseData, status
})
```

### 2. Form Component

**Location**: `app/assets/components/CRUD6/Form.vue`

**Purpose**: Dynamic form for creating and editing records

**Debug Points**:
- Form submission start
- Validation results
- Operation type detection (CREATE/UPDATE)
- API call preparation
- Success handling
- Failure handling

**Key Logs**:
```typescript
// Form submission
console.log('[Form] ===== FORM SUBMIT START =====', {
    model, hasCrud6, formData
})

// Validation
console.log('[Form] Validation result', {
    model, isValid, errors
})
console.warn('[Form] Validation failed, form not submitted', {
    model, errors
})

// API call
console.log('[Form] Preparing API call', {
    model, primaryKey, recordId, operation, formData
})

// Success
console.log('[Form] ===== FORM SUBMIT SUCCESS =====', {
    model, operation, recordId
})

// Failure
console.error('[Form] ===== FORM SUBMIT FAILED =====', {
    model, operation, recordId, error, formData
})
```

## Troubleshooting Workflow

### Step 1: Check Frontend Console

1. Open browser developer tools (F12)
2. Go to Console tab
3. Filter for `[useCRUD6Api]` or `[Form]`
4. Look for `===== FAILED =====` markers

**What to look for**:
- Request URL being called
- Request data being sent
- Response status code
- Response data or error message
- Full error object with details

### Step 2: Check Backend Logs

1. Check UserFrosting debug log file (usually `app/logs/userfrosting.log`)
2. Search for `CRUD6` entries
3. Look for `===== FAILED =====` markers

**What to look for**:
- Request entry point (which action was called)
- Request parameters received
- Validation errors
- Database errors
- Full stack traces

### Step 3: Trace Request Flow

Follow the request through both layers:

**Frontend → Backend**:
1. `[Form]` or component initiating request
2. `[useCRUD6Api]` API call with request data
3. `CRUD6 [CRUD6Injector]` middleware processing
4. `CRUD6 [ActionName]` controller handling

**Backend → Frontend**:
1. `CRUD6 [ActionName]` response preparation
2. `[useCRUD6Api]` response received
3. `[Form]` success/failure handling

### Step 4: Common Issues and Patterns

#### Validation Errors
**Frontend**:
```
[Form] Validation failed, form not submitted
```
Look at the `errors` object in the log.

**Backend**:
```
CRUD6 [CreateAction] Validation failed
```
Check the `errors` array in the log.

#### Database Errors
**Backend**:
```
CRUD6 [EditAction] Database update executed
```
Check `affected_rows` - if 0, update may have failed.

#### Permission Errors
**Backend**:
```
CRUD6 [CRUD6Injector] Record not found
```
Could be permission issue or record doesn't exist.

#### Response Format Errors
**Frontend**:
```
[useCRUD6Api] ===== UPDATE ROW REQUEST FAILED =====
responseData: { error: "..." }
```
Check if backend is returning expected format.

## Log Level Configuration

To enable debug logging in UserFrosting:

1. Edit `.env` file or `app/config/default.php`
2. Set debug level:
```php
'debug' => [
    'level' => 'debug',  // or 'info', 'warning', 'error'
]
```

3. For production, use 'info' or 'warning' to reduce log volume
4. For debugging, use 'debug' to see all messages

## Log Filtering and Searching

### Backend Logs (grep)
```bash
# All CRUD6 logs
grep "CRUD6" app/logs/userfrosting.log

# Only errors
grep "CRUD6.*FAILED" app/logs/userfrosting.log

# Specific action
grep "CRUD6 \[CreateAction\]" app/logs/userfrosting.log

# Specific model
grep "CRUD6.*model.*products" app/logs/userfrosting.log
```

### Frontend Console (Chrome DevTools)
```
# Filter by component
[useCRUD6Api]

# Filter by model
products

# Filter by operation
CREATE
UPDATE
DELETE

# Filter by error
FAILED
```

## Performance Considerations

Debug logging adds overhead. In production:

1. **Backend**: Set log level to 'info' or 'warning'
2. **Frontend**: Consider removing or commenting console.log statements
3. **Keep**: Error logging (`console.error`, `$this->logger->error`)
4. **Remove**: Debug logging in hot paths (loops, frequent operations)

## Future Enhancements

Potential improvements to debug logging:

1. **Structured Logging**: Use JSON format for easier parsing
2. **Request ID**: Track single request across frontend/backend
3. **Performance Timing**: Add timestamps and duration measurements
4. **Log Aggregation**: Send logs to central service (e.g., Sentry, LogRocket)
5. **Conditional Logging**: Enable/disable via configuration without code changes
6. **User Context**: Include user ID in all logs for multi-user debugging

## Summary

This comprehensive debug logging system provides:
- **Full visibility** into API request/response flow
- **Detailed context** for error diagnosis
- **Consistent format** for easy filtering and searching
- **Complete stack traces** for backend errors
- **Frontend/backend correlation** via model and operation names

Use this guide to quickly identify and resolve issues in the CRUD6 sprinkle.
