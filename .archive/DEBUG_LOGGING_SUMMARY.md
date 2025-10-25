# Debug Logging Implementation Summary

This document summarizes the debug logging implementation added to the CRUD6 sprinkle.

## Quick Reference

### How to Use

#### For Users Reporting Errors:

1. **Open Browser Console** (Press F12)
2. **Reproduce the error** (e.g., try to create/update a record)
3. **Look for red errors** with `FAILED` in the message
4. **Copy the entire log output** from the console
5. **Check backend logs**: Look in `app/logs/userfrosting.log` for `CRUD6` entries
6. **Share both frontend and backend logs** with the developer

#### For Developers Debugging Issues:

1. **Filter frontend logs**: Search console for `[useCRUD6Api]` or `[Form]`
2. **Filter backend logs**: `grep "CRUD6" app/logs/userfrosting.log`
3. **Look for FAILED markers**: These indicate where errors occurred
4. **Trace the request flow**: Follow the same model/operation through both layers
5. **Check the context**: All logs include relevant data (model, IDs, request data, errors)

## What Was Added

### Backend (PHP) - 6 Components

1. **CRUD6Injector** (Middleware) - Logs model/schema loading
2. **CreateAction** - Logs POST request handling (create records)
3. **EditAction** - Logs GET/PUT request handling (read/update records)
4. **DeleteAction** - Logs DELETE request handling (delete records)
5. **UpdateFieldAction** - Logs PUT request handling (update single field)
6. **SprunjeAction** - Logs GET request handling (list/filter/sort/paginate)

### Frontend (TypeScript/Vue) - 2 Components

7. **useCRUD6Api** - Logs all API calls (fetchRow, createRow, updateRow, updateField, deleteRow)
8. **Form** - Logs form submission, validation, success/failure

### Documentation

9. **DEBUG_LOGGING_GUIDE.md** - Comprehensive guide in `.archive/` folder (14KB)

## Log Format

### Backend Pattern
```
CRUD6 [ComponentName] Message
```

Examples:
- `CRUD6 [CreateAction] ===== CREATE REQUEST START =====`
- `CRUD6 [EditAction] Data validation passed`
- `CRUD6 [CRUD6Injector] Record found and loaded`

### Frontend Pattern
```
[ComponentName] Message
```

Examples:
- `[useCRUD6Api] ===== CREATE ROW REQUEST START =====`
- `[Form] Validation result`
- `[useCRUD6Api] ===== UPDATE ROW REQUEST FAILED =====`

## Section Markers

All major operations use section markers for easy identification:

- `===== START =====` - Operation beginning
- `===== COMPLETE =====` or `===== SUCCESS =====` - Operation succeeded
- `===== FAILED =====` - Operation failed (ERROR)

## Example Log Traces

### Successful Create Operation

**Frontend Console**:
```
[Form] ===== FORM SUBMIT START =====
[Form] Validation result
[Form] Preparing API call
[useCRUD6Api] ===== CREATE ROW REQUEST START =====
[useCRUD6Api] Create row response received
[Form] ===== FORM SUBMIT SUCCESS =====
```

**Backend Log**:
```
CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS START =====
CRUD6 [CRUD6Injector] Schema loaded and model configured
CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS COMPLETE =====
CRUD6 [CreateAction] ===== CREATE REQUEST START =====
CRUD6 [CreateAction] Request parameters received
CRUD6 [CreateAction] Data transformed
CRUD6 [CreateAction] Validation successful
CRUD6 [CreateAction] Insert data prepared
CRUD6 [CreateAction] Record inserted into database
CRUD6 [CreateAction] Transaction completed successfully
CRUD6 [CreateAction] Response prepared successfully
```

### Failed Update Operation

**Frontend Console**:
```
[Form] ===== FORM SUBMIT START =====
[Form] Validation result
[Form] Preparing API call
[useCRUD6Api] ===== UPDATE ROW REQUEST START =====
[useCRUD6Api] ===== UPDATE ROW REQUEST FAILED =====
  error: {...}
  responseData: {...}
  status: 500
[Form] ===== FORM SUBMIT FAILED =====
```

**Backend Log**:
```
CRUD6 [CRUD6Injector] ===== MIDDLEWARE PROCESS START =====
CRUD6 [CRUD6Injector] Looking up record by ID
CRUD6 [CRUD6Injector] Record found and loaded
CRUD6 [EditAction] ===== REQUEST START =====
CRUD6 [EditAction] Processing PUT request (update)
CRUD6 [EditAction] Update parameters received
CRUD6 [EditAction] Validation failed
  errors: {...}
CRUD6 [EditAction] ===== REQUEST FAILED =====
  error_type: ValidationException
  error_message: "Validation failed"
  trace: ...
```

## Filtering Examples

### Backend (grep commands)

```bash
# All CRUD6 logs
grep "CRUD6" app/logs/userfrosting.log

# Only errors
grep "CRUD6.*FAILED" app/logs/userfrosting.log

# Specific component
grep "CRUD6 \[CreateAction\]" app/logs/userfrosting.log

# Specific model
grep "CRUD6.*'model' => 'products'" app/logs/userfrosting.log

# Recent logs (last 100 lines)
tail -n 100 app/logs/userfrosting.log | grep "CRUD6"
```

### Frontend (Console filter)

In Chrome DevTools Console, use the filter box:

- `[useCRUD6Api]` - All API calls
- `[Form]` - All form operations
- `FAILED` - All errors
- `products` - All operations on products model
- `CREATE` - All create operations
- `UPDATE` - All update operations

## What Problems This Solves

✅ **"I get an error but don't know what's wrong"**
   → Check console for `FAILED` marker with full error details

✅ **"The API call isn't working"**
   → Check both frontend console and backend logs for the request flow

✅ **"Validation is failing but I don't see why"**
   → Search for "Validation failed" in logs to see specific errors

✅ **"Record not found but I'm sure it exists"**
   → Check CRUD6Injector logs for record lookup details

✅ **"Update isn't saving my changes"**
   → Check EditAction logs for data transformation and database update

✅ **"I need to debug the exact data being sent/received"**
   → All logs include full request/response data

## Performance Impact

### Development Mode
- **Backend**: Full debug logging (safe, normal overhead)
- **Frontend**: All console logs enabled (safe for debugging)

### Production Mode
- **Backend**: Set log level to 'info' or 'warning' in config
- **Frontend**: Consider removing/commenting console.log statements
- **Keep**: All `console.error` and `$this->logger->error` for critical errors

## Files Modified

- `app/src/Middlewares/CRUD6Injector.php` - 70 lines added
- `app/src/Controller/CreateAction.php` - 100 lines added
- `app/src/Controller/EditAction.php` - 150 lines added
- `app/src/Controller/DeleteAction.php` - 50 lines added
- `app/src/Controller/UpdateFieldAction.php` - 120 lines added
- `app/src/Controller/SprunjeAction.php` - 80 lines added
- `app/assets/composables/useCRUD6Api.ts` - 200 lines added
- `app/assets/components/CRUD6/Form.vue` - 30 lines added

**Total**: ~800 lines of debug logging code

## Next Steps for Users

When you encounter an error:

1. **Reproduce the issue** with browser console open (F12)
2. **Copy frontend logs** from the console (look for FAILED markers)
3. **Copy backend logs**: `grep "CRUD6.*FAILED" app/logs/userfrosting.log`
4. **Create an issue** with:
   - Description of what you were trying to do
   - Frontend console logs (paste as text, not screenshot)
   - Backend log excerpt (paste as text)
   - Browser and OS information
5. **Developer will analyze logs** and identify the root cause quickly

## Support

For questions about the debug logging system:
- See `.archive/DEBUG_LOGGING_GUIDE.md` for detailed documentation
- Check the inline code comments in each file
- All debug statements follow the same pattern for consistency

---

**Implementation Date**: 2025-10-24
**PR**: Add debug statements to frontend and backend
**Purpose**: Enable users to report errors with full context and enable developers to diagnose issues quickly
