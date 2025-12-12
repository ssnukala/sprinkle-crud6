# Modular Integration Testing with Configurable API Patterns

## Overview

The integration testing framework is now fully modular and can work with **any UserFrosting 6 sprinkle's API structure**. The key enhancement is configurable API patterns that replace hardcoded paths.

## Key Enhancements

### 1. Configurable API Patterns

Instead of hardcoding `/api/crud6/`, the framework now reads API patterns from configuration:

```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/crud6/",
      "schema_api": "/api/crud6/{model}/schema"
    }
  }
}
```

### 2. Framework Components Updated

#### NetworkRequestTracker Class

**Before (Hardcoded)**:
```javascript
class NetworkRequestTracker {
    isCRUD6Call(url) {
        return url.includes("/api/crud6/");
    }
}
```

**After (Configurable)**:
```javascript
class NetworkRequestTracker {
    constructor(apiPatterns = {}) {
        this.apiPatterns = {
            main_api: apiPatterns.main_api || "/api/crud6/",
            schema_api: apiPatterns.schema_api || "/api/crud6/{model}/schema"
        };
    }
    
    isMainApiCall(url) {
        return url.includes(this.apiPatterns.main_api);
    }
    
    isSchemaCall(url) {
        const regexPattern = this.apiPatterns.schema_api.replace(/\{[^}]+\}/g, "[^/]+");
        return new RegExp(regexPattern).test(url);
    }
    
    // Legacy method for backward compatibility
    isCRUD6Call(url) {
        return this.isMainApiCall(url);
    }
}
```

## Usage for Different Sprinkles

### CRUD6 Sprinkle (Original)

**Configuration** (`.github/config/integration-test-paths.json`):
```json
{
  "config": {
    "base_url": "http://localhost:8080",
    "auth": {
      "username": "admin",
      "password": "admin123"
    },
    "api_patterns": {
      "main_api": "/api/crud6/",
      "schema_api": "/api/crud6/{model}/schema"
    }
  },
  "paths": {
    "authenticated": {
      "api": {
        "users_list": {
          "method": "GET",
          "path": "/api/crud6/users",
          "expected_status": 200
        }
      }
    }
  }
}
```

### Custom Sprinkle Example: "MyApp"

**Configuration** (`.github/config/integration-test-paths.json`):
```json
{
  "config": {
    "base_url": "http://localhost:8080",
    "auth": {
      "username": "admin",
      "password": "admin123"
    },
    "api_patterns": {
      "main_api": "/api/myapp/",
      "schema_api": "/api/myapp/schema/{model}"
    }
  },
  "paths": {
    "authenticated": {
      "api": {
        "products_list": {
          "method": "GET",
          "path": "/api/myapp/products",
          "expected_status": 200
        },
        "products_schema": {
          "method": "GET",
          "path": "/api/myapp/schema/products",
          "expected_status": 200
        }
      }
    }
  }
}
```

**Result**: The same `take-screenshots-with-tracking.js` script will:
- Track `/api/myapp/` calls instead of `/api/crud6/`
- Detect schema calls at `/api/myapp/schema/{model}`
- Generate reports showing "MyApp API" requests
- No code changes required!

### Admin Sprinkle Example: "C6Admin"

**Configuration**:
```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/admin/",
      "schema_api": "/api/admin/{model}/metadata"
    }
  },
  "paths": {
    "authenticated": {
      "api": {
        "users_list": {
          "method": "GET",
          "path": "/api/admin/users",
          "expected_status": 200
        }
      }
    }
  }
}
```

### Multi-API Sprinkle

If your sprinkle has multiple API patterns, you can define the primary one and the framework will track it:

```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/v1/",
      "schema_api": "/api/v1/{model}/definition"
    }
  }
}
```

## Backward Compatibility

The framework maintains **full backward compatibility**:

### Legacy Methods Still Work

```javascript
// Old code using legacy methods still works
const crud6Calls = networkTracker.getCRUD6Calls();
const filtered = networkTracker.getFilteredCRUD6Requests();
```

### Default Values

If no `api_patterns` are provided in config, the framework defaults to CRUD6 patterns:
```javascript
const apiPatterns = config.config?.api_patterns || {
    main_api: "/api/crud6/",
    schema_api: "/api/crud6/{model}/schema"
};
```

## Pattern Matching

### Main API Pattern
Simple string matching:
```javascript
isMainApiCall(url) {
    return url.includes(this.apiPatterns.main_api);
}
```

**Examples**:
- Pattern: `/api/myapp/`
- Matches: `/api/myapp/users`, `/api/myapp/products/123`
- Doesn't match: `/api/other/`, `/myapp/users`

### Schema API Pattern
Regex matching with placeholder support:
```javascript
isSchemaCall(url) {
    // Convert: "/api/crud6/{model}/schema" 
    // To regex: /api/crud6/[^/]+/schema
    const regexPattern = this.apiPatterns.schema_api.replace(/\{[^}]+\}/g, "[^/]+");
    return new RegExp(regexPattern).test(url);
}
```

**Examples**:
- Pattern: `/api/myapp/schema/{model}`
- Matches: `/api/myapp/schema/users`, `/api/myapp/schema/products`
- Doesn't match: `/api/myapp/users`, `/api/myapp/schema/`

## Installation for Other Sprinkles

### Step 1: Install Framework
```bash
cd your-sprinkle
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

### Step 2: Configure API Patterns

Edit `.github/config/integration-test-paths.json`:
```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/your-sprinkle/",
      "schema_api": "/api/your-sprinkle/{model}/schema"
    }
  }
}
```

### Step 3: Define Test Paths

Add your API endpoints:
```json
{
  "paths": {
    "authenticated": {
      "api": {
        "your_endpoint": {
          "method": "GET",
          "path": "/api/your-sprinkle/your-endpoint",
          "expected_status": 200
        }
      }
    }
  }
}
```

### Step 4: Run Tests
```bash
# Test unauthenticated paths
export CONTINUE_ON_FAILURE=true
php .github/scripts/test-paths.php .github/config/integration-test-paths.json unauth

# Test authenticated paths with screenshots
node .github/scripts/take-screenshots-with-tracking.js .github/config/integration-test-paths.json
```

## Network Request Report

The generated network request report will use your API pattern:

**For CRUD6**:
```
NETWORK REQUEST SUMMARY
═══════════════════════════════════════
Total Requests:        42
API Requests:          15
  - Main API:          12
  - Schema API:        3
Other Requests:        27

PER-PAGE BREAKDOWN (API REQUESTS ONLY)
═══════════════════════════════════════
Page: Dashboard
   Total Requests: 8 (3 API, 5 other)
   
   API Request Details:
   1. GET /api/crud6/users
   2. GET /api/crud6/users/schema
   3. GET /api/crud6/groups
```

**For MyApp**:
```
NETWORK REQUEST SUMMARY
═══════════════════════════════════════
Total Requests:        42
API Requests:          15
  - Main API:          12
  - Schema API:        3
Other Requests:        27

PER-PAGE BREAKDOWN (API REQUESTS ONLY)
═══════════════════════════════════════
Page: Dashboard
   Total Requests: 8 (3 API, 5 other)
   
   API Request Details:
   1. GET /api/myapp/products
   2. GET /api/myapp/schema/products
   3. GET /api/myapp/categories
```

## Benefits

### For Framework Users

✅ **Zero Code Changes**: Configure patterns, same scripts work
✅ **Flexible**: Any API structure supported
✅ **Accurate Reports**: Shows your API calls, not generic names
✅ **Network Insights**: Track redundant calls, performance issues
✅ **Battle-Tested**: Same proven scripts from CRUD6

### For Framework Maintainers

✅ **Single Codebase**: One script for all sprinkles
✅ **No Duplication**: Share improvements across sprinkles
✅ **Easy Updates**: Update once, all sprinkles benefit
✅ **Backward Compatible**: Existing sprinkles keep working

## Advanced Configuration

### Custom Schema Patterns

Different sprinkles may have different schema API structures:

```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/v2/",
      "schema_api": "/api/v2/metadata/{model}.json"
    }
  }
}
```

This will match:
- `/api/v2/metadata/users.json`
- `/api/v2/metadata/products.json`

### Multiple Pattern Support (Future Enhancement)

Currently supports one main API pattern. Future versions could support multiple:

```json
{
  "config": {
    "api_patterns": {
      "main_api": ["/api/v1/", "/api/v2/"],
      "schema_api": "/api/{version}/{model}/schema"
    }
  }
}
```

## Troubleshooting

### API Calls Not Being Tracked

**Problem**: Network report shows 0 API calls

**Solution**: Check your `api_patterns` configuration matches your actual API structure:
```bash
# In browser console during test
console.log(window.location.href);  // Check actual URLs being called
```

Then update config:
```json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/actual-path/"  // Must match actual URLs
    }
  }
}
```

### Schema Calls Not Detected

**Problem**: Schema API calls show as regular API calls

**Solution**: Verify schema pattern includes `{model}` placeholder:
```json
{
  "config": {
    "api_patterns": {
      "schema_api": "/api/myapp/{model}/schema"  // ✅ Correct
      // "schema_api": "/api/myapp/schema"       // ❌ Wrong - too generic
    }
  }
}
```

## Migration from Hardcoded Scripts

If you have existing integration tests with hardcoded API paths:

### Before
```javascript
// custom-test-script.js
const apiCalls = requests.filter(r => r.url.includes('/api/myapp/'));
const schemaCalls = requests.filter(r => r.url.match(/\/api\/myapp\/[^\/]+\/schema/));
```

### After
```json
// .github/config/integration-test-paths.json
{
  "config": {
    "api_patterns": {
      "main_api": "/api/myapp/",
      "schema_api": "/api/myapp/{model}/schema"
    }
  }
}
```

```bash
# Use framework script instead
node .github/scripts/take-screenshots-with-tracking.js .github/config/integration-test-paths.json
```

**Benefits**: No more custom script maintenance, automatic updates, better reporting.

## Related Documentation

- [Framework README](.github/testing-framework/README.md) - Full framework guide
- [SUMMARY](.github/testing-framework/SUMMARY.md) - Framework objectives
- [Configuration Guide](.github/testing-framework/docs/CONFIGURATION.md) - Complete config reference
- [API Reference](.github/testing-framework/docs/API_REFERENCE.md) - Script parameters

---

**Version**: 1.1.0 (Modular API Patterns)  
**Date**: 2025-12-12  
**Status**: ✅ Production Ready  
**Compatibility**: All UserFrosting 6 sprinkles
