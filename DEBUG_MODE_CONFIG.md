# Debug Mode Configuration

The CRUD6 sprinkle provides a `debug_mode` configuration option that allows you to control debug logging for all CRUD6 operations.

## Configuration

Edit your `app/config/default.php` file (or create an environment-specific config):

```php
return [
    'crud6' => [
        'debug_mode' => false,  // Set to true to enable debug logging
    ]
];
```

## Usage Scenarios

### Development Environment
```php
'debug_mode' => true,  // Enable to see all CRUD6 debug logs
```

### Production Environment
```php
'debug_mode' => false,  // Disable to reduce log volume
```

### Troubleshooting
When you encounter issues with CRUD6 operations, temporarily enable debug mode to get detailed logs:
1. Set `'debug_mode' => true` in config
2. Reproduce the issue
3. Check logs for detailed information
4. Set `'debug_mode' => false` when done

## What Gets Logged

When `debug_mode` is enabled, the following information is logged:

### SchemaService
- Schema file loading attempts
- Schema caching (hits and misses)
- Context filtering operations
- Cache clearing operations

### Controllers (Base, CreateAction, EditAction, DeleteAction, UpdateFieldAction, ApiAction, SprunjeAction)
- Controller invocations
- Request parameters and routing
- Data validation and transformation
- Database operations
- Response preparation
- Error conditions

### Log Format

All logs use structured logging with context data:

```php
$this->debugLog("CRUD6 [ComponentName] Message", [
    'key1' => 'value1',
    'key2' => 'value2',
]);
```

Example output in logs:
```
[2025-11-03 02:15:45] app.DEBUG: CRUD6 [SchemaService] Schema loaded successfully and CACHED {"model":"users","table":"users","field_count":12,"cache_key":"users:default"}
```

## Implementation Details

### Controllers (extend Base)

All controllers extending the `Base` class use the `debugLog()` helper method:

```php
protected function debugLog(string $message, array $context = []): void
{
    if ($this->isDebugMode()) {
        $this->logger->debug($message, $context);
    }
}
```

This method:
- Checks if `debug_mode` is enabled
- Only calls the logger if debug mode is true
- Uses `DebugLoggerInterface` from UserFrosting core

### SchemaService

SchemaService has its own `debugLog()` implementation (does not extend Base):

```php
protected function debugLog(string $message, array $context = []): void
{
    if (!$this->isDebugMode()) {
        return;
    }

    if ($this->logger !== null) {
        $this->logger->debug($message, $context);
    } else {
        // Fallback to error_log if logger not available
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        error_log($message . $contextStr);
    }
}
```

This implementation:
- Checks if `debug_mode` is enabled
- Uses `DebugLoggerInterface` if available
- Falls back to `error_log()` if logger is not available (respects previous architectural decisions)

## Performance Impact

### With debug_mode = false (default)
- **Minimal overhead**: Only one config lookup per log call
- **No logging**: Early return prevents any logging operations
- **Production-safe**: No performance degradation

### With debug_mode = true
- **Full debug logging**: All CRUD6 operations are logged
- **Acceptable overhead**: Suitable for development and troubleshooting
- **Not recommended for production**: May impact performance and increase log volume

## Log Locations

Depending on your UserFrosting configuration, logs will appear in:

1. **UserFrosting logs**: `app/logs/userfrosting.log` (default)
2. **PHP error log**: Configured in `php.ini` (fallback for SchemaService when logger unavailable)

To filter for CRUD6-specific logs:
```bash
# Linux/Mac
grep "CRUD6" app/logs/userfrosting.log

# Or tail in real-time
tail -f app/logs/userfrosting.log | grep "CRUD6"
```

## Frontend Logging

The `debug_mode` configuration currently only affects backend (PHP) logging. Frontend (JavaScript/TypeScript) logging uses `console.log()` statements that are always active.

If you need to control frontend logging, you can:
1. Create a similar frontend config option
2. Conditionally enable/disable console statements
3. Use a frontend logging library with configurable log levels

## Testing

Tests for debug_mode functionality can be found in:
- `app/tests/Controller/DebugModeTest.php` - Tests Base controller debugLog()
- `app/tests/ServicesProvider/SchemaServiceDebugModeTest.php` - Tests SchemaService debugLog()
- `app/tests/Integration/DebugModeIntegrationTest.php` - Integration tests

Run tests:
```bash
vendor/bin/phpunit app/tests/Controller/DebugModeTest.php
vendor/bin/phpunit app/tests/ServicesProvider/SchemaServiceDebugModeTest.php
vendor/bin/phpunit app/tests/Integration/DebugModeIntegrationTest.php
```

## Examples

### Enable Debug Mode for Development

```php
// app/config/default.php
return [
    'crud6' => [
        'debug_mode' => true,
    ]
];
```

### Environment-Specific Configuration

```php
// app/config/development.php
return [
    'crud6' => [
        'debug_mode' => true,  // Always on in development
    ]
];

// app/config/production.php
return [
    'crud6' => [
        'debug_mode' => false,  // Always off in production
    ]
];
```

### Conditional Configuration Based on Environment Variable

```php
// app/config/default.php
return [
    'crud6' => [
        'debug_mode' => filter_var(getenv('CRUD6_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    ]
];
```

Then in your `.env` file:
```
CRUD6_DEBUG=true
```

## Troubleshooting

### Debug logs not appearing

1. **Check config**: Verify `debug_mode` is set to `true`
2. **Check log file**: Ensure `app/logs/userfrosting.log` exists and is writable
3. **Check log level**: UserFrosting's log level must be set to `debug` or lower
4. **Clear cache**: Run `php bakery clear-cache` to clear configuration cache

### Too many debug logs

1. **Disable debug_mode**: Set to `false` in config
2. **Filter logs**: Use grep or log viewing tools to filter for specific components
3. **Increase log level**: Set UserFrosting's log level to `info` or higher to suppress debug logs

### Error logs in unexpected location

If using SchemaService's error_log fallback, check:
1. PHP's `error_log` directive in `php.ini`
2. Web server error logs (Apache, Nginx)
3. System logs (`/var/log/messages`, etc.)

## See Also

- [UserFrosting Logging Documentation](https://learn.userfrosting.com/logging)
- [Previous Debug Logging Documentation](.archive/DEBUG_LOGGING_GUIDE.md)
- [Schema Optimization Summary](.archive/SCHEMA_OPTIMIZATION_SUMMARY_2025-10-31.md)

## Frontend Debug Mode

The frontend also supports debug mode through a JavaScript/TypeScript utility that matches the backend pattern.

### Setup

Import and initialize the debug utility early in your application:

```typescript
import { setDebugMode } from '@/utils/debug';

// Enable debug mode
setDebugMode(true);

// Or get from config/environment
const debugMode = import.meta.env.VITE_DEBUG_MODE === 'true';
setDebugMode(debugMode);
```

### Usage

Replace `console.log()` calls with `debugLog()`:

```typescript
import { debugLog, debugWarn, debugError, logError } from '@/utils/debug';

// Debug logging (only when debug mode enabled)
debugLog('[useCRUD6Api] ===== CREATE ROW REQUEST START =====', {
    model: 'users',
    data: formData
});

// Debug warnings (only when debug mode enabled)
debugWarn('[Form] Validation failed', { errors });

// Debug errors (only when debug mode enabled)
debugError('[API] Request failed', { error, response });

// Critical errors (always logged, bypasses debug mode)
logError('[CRITICAL] Unhandled exception', { error });
```

### Environment Configuration

Add to your `.env` file:

```bash
# Enable frontend debug logging
VITE_DEBUG_MODE=true
```

Then in your app initialization:

```typescript
import { setDebugMode } from '@/utils/debug';

setDebugMode(import.meta.env.VITE_DEBUG_MODE === 'true');
```

### API

The frontend debug utility provides:

- **`setDebugMode(enabled: boolean)`** - Enable or disable debug mode
- **`isDebugMode(): boolean`** - Check if debug mode is enabled
- **`debugLog(message, ...args)`** - Conditional console.log
- **`debugWarn(message, ...args)`** - Conditional console.warn
- **`debugError(message, ...args)`** - Conditional console.error
- **`logError(message, ...args)`** - Always log errors (bypasses debug mode)

### Migration Guide

To migrate existing console.log statements:

**Before:**
```typescript
console.log('[useCRUD6Api] Request start', { model, id });
console.warn('[Form] Validation issue', { errors });
console.error('[API] Request failed', { error });
```

**After:**
```typescript
import { debugLog, debugWarn, debugError } from '@/utils/debug';

debugLog('[useCRUD6Api] Request start', { model, id });
debugWarn('[Form] Validation issue', { errors });
debugError('[API] Request failed', { error });
```

### Current Status

- **Debug utility created**: `app/assets/utils/debug.ts`
- **Console.log statements**: ~83 statements identified for migration
- **Migration**: Not yet complete - statements still use console.log directly

To complete the migration, replace console.log/warn/error calls with the debug utilities.

### Performance Impact

When debug mode is disabled (`setDebugMode(false)`):
- **No logging**: All debugLog/debugWarn/debugError calls are no-ops
- **Minimal overhead**: Simple boolean check before returning
- **Production-safe**: Can safely leave debug calls in production code

When debug mode is enabled (`setDebugMode(true)`):
- **Full console logging**: All debug statements output to browser console
- **Suitable for development**: Helps debug issues during development
- **Not recommended for production**: May impact performance and expose internal details

