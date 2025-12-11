# JSON-Driven Integration Testing

## Overview

Just like CRUD6 makes CRUD operations simple with JSON configuration files, the integration testing framework makes testing equally simple with JSON configuration.

**No code required!** Configure everything in one JSON file.

## Quick Start (3 Steps!)

### Step 1: Create Configuration File

```bash
cp .github/testing-framework/config/integration-test-config.json \
   integration-test-config.json
```

### Step 2: Edit JSON Configuration

```json
{
  "sprinkle": {
    "name": "my-sprinkle",
    "composer_package": "myvendor/my-sprinkle",
    "npm_package": "@myvendor/my-sprinkle"
  },
  "schemas": {
    "path": ""
  },
  "routes": {
    "pattern": "simple",
    "import": {
      "module": "@myvendor/my-sprinkle/routes",
      "name": "MyRoutes"
    }
  }
}
```

### Step 3: Generate Workflow

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

**That's it!** Your complete integration test workflow is ready.

## Configuration Reference

### Sprinkle Configuration

```json
{
  "sprinkle": {
    "name": "my-sprinkle",              // Directory name
    "composer_package": "vendor/pkg",    // Composer package name
    "npm_package": "@vendor/pkg",        // NPM package (optional)
    "namespace": "Vendor\\Sprinkle"      // PHP namespace (optional)
  }
}
```

### Schema Configuration

```json
{
  "schemas": {
    "path": "",  // Empty = app/schema/crud6/ (default)
                 // Or specify custom: "examples/schema"
  }
}
```

### Route Configuration

#### Pattern 1: Simple Array (Most Common)

```json
{
  "routes": {
    "pattern": "simple",
    "import": {
      "module": "@vendor/sprinkle/routes",
      "name": "MyRoutes"
    }
  }
}
```

Generates:
```typescript
import MyRoutes from '@vendor/sprinkle/routes'
...AccountRoutes,
...MyRoutes,
```

#### Pattern 2: Factory Function

```json
{
  "routes": {
    "pattern": "factory",
    "import": {
      "module": "@vendor/sprinkle/routes",
      "name": "createMyRoutes"
    },
    "factory": {
      "enabled": true,
      "layout_component": "Layout"
    }
  }
}
```

Generates:
```typescript
import { createMyRoutes } from '@vendor/sprinkle/routes'
const MyRoutes = createMyRoutes({ layoutComponent: Layout });
...AccountRoutes,
...MyRoutes,
```

#### Pattern 3: Custom

```json
{
  "routes": {
    "pattern": "custom",
    "custom_setup": {
      "enabled": true,
      "commands": [
        "sed -i 's/pattern1/pattern2/' app/assets/router/index.ts",
        "echo 'Custom route setup' >> app/assets/router/index.ts"
      ]
    }
  }
}
```

### Custom Test Data

```json
{
  "custom_test_data": {
    "enabled": true,
    "path": "app/tests/test-data.sql"
  }
}
```

This SQL file is loaded AFTER schema-driven SQL generation.

### Framework Configuration

```json
{
  "framework": {
    "version": "main"  // Branch/tag to use from sprinkle-crud6
  }
}
```

### Testing Environment

```json
{
  "testing": {
    "php_version": "8.1",
    "node_version": "20",
    "mysql_version": "8.0",
    "userfrosting_version": "^6.0-beta"
  }
}
```

### Vite Dependencies

```json
{
  "vite": {
    "optimize_deps": ["limax", "lodash.deburr", "your-dep"]
  }
}
```

## Complete Examples

### Example 1: CRUD6 Itself

```json
{
  "sprinkle": {
    "name": "sprinkle-crud6",
    "composer_package": "ssnukala/sprinkle-crud6",
    "npm_package": "@ssnukala/sprinkle-crud6"
  },
  "schemas": {
    "path": "examples/schema"
  },
  "routes": {
    "pattern": "simple",
    "import": {
      "module": "@ssnukala/sprinkle-crud6/routes",
      "name": "CRUD6Routes"
    }
  }
}
```

### Example 2: C6Admin (Factory Pattern)

```json
{
  "sprinkle": {
    "name": "sprinkle-c6admin",
    "composer_package": "ssnukala/sprinkle-c6admin",
    "npm_package": "@ssnukala/sprinkle-c6admin"
  },
  "schemas": {
    "path": ""
  },
  "routes": {
    "pattern": "factory",
    "import": {
      "module": "@ssnukala/sprinkle-c6admin/routes",
      "name": "createC6AdminRoutes"
    },
    "factory": {
      "enabled": true,
      "layout_component": "Layout"
    }
  },
  "custom_test_data": {
    "enabled": true,
    "path": "app/tests/test-data.sql"
  }
}
```

### Example 3: Simple Sprinkle

```json
{
  "sprinkle": {
    "name": "my-simple-sprinkle",
    "composer_package": "myvendor/simple",
    "npm_package": ""
  },
  "schemas": {
    "path": ""
  },
  "routes": {
    "pattern": "simple",
    "import": {
      "module": "@myvendor/simple/routes",
      "name": "SimpleRoutes"
    }
  }
}
```

## Workflow Generation

### Manual Generation

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

### During Installation

The installer can also generate the workflow:

```bash
.github/testing-framework/install.sh my-sprinkle --generate-workflow
```

### CI Auto-Generation

Add to your repository:

```yaml
# .github/workflows/generate-tests.yml
name: Generate Test Workflow

on:
  push:
    paths:
      - 'integration-test-config.json'

jobs:
  generate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Generate workflow
        run: |
          node .github/testing-framework/scripts/generate-workflow.js
      - name: Commit if changed
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git add .github/workflows/integration-test.yml
          git commit -m "Auto-generate integration test workflow" || true
          git push
```

## What Gets Automated

With JSON configuration, **everything** is automated:

### ✅ Infrastructure Setup (100% Automated)
- PHP, Node.js, MySQL versions
- UserFrosting installation
- Runtime directory creation

### ✅ Dependency Management (100% Automated)
- Composer configuration
- NPM package installation
- Local path repositories
- Beta package handling

### ✅ Application Configuration (100% Automated)
- MyApp.php modification
- main.ts sprinkle registration
- router/index.ts route configuration (from JSON!)
- vite.config.ts optimization (from JSON!)
- .env database setup

### ✅ Database Operations (100% Automated)
- Migrations
- Schema-driven SQL generation
- Custom SQL loading
- Seed execution
- Seed validation
- Idempotency testing

### ✅ Testing (100% Automated)
- Frontend build
- API testing
- Frontend testing
- Screenshot capture
- Artifact upload

## Comparison: JSON vs Manual

### Before (Manual Workflow)

```yaml
# 583 lines of YAML
# Hardcoded values throughout
# Different for each sprinkle
# Difficult to maintain
# Easy to make mistakes
```

### After (JSON-Driven)

```json
{
  "sprinkle": { "name": "my-sprinkle", ... },
  "routes": { "pattern": "simple", ... }
}
```

```bash
node generate-workflow.js config.json workflow.yml
```

Result: Perfect workflow, generated automatically!

## Benefits

| Aspect | Manual | JSON-Driven |
|--------|--------|-------------|
| Configuration | Scattered in YAML | Single JSON file |
| Lines of Code | 583 (YAML) | ~50 (JSON) |
| Validation | Manual | JSON schema |
| Updates | Edit YAML | Regenerate |
| Errors | Easy to make | Validated |
| Patterns | Hardcoded | Configurable |
| Maintenance | High | Low |
| Learning Curve | Steep | Gentle |

## Advanced: JSON Schema Validation

Create `.github/testing-framework/config/integration-test-config.schema.json`:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["sprinkle", "routes"],
  "properties": {
    "sprinkle": {
      "type": "object",
      "required": ["name", "composer_package"],
      "properties": {
        "name": { "type": "string" },
        "composer_package": { "type": "string" },
        "npm_package": { "type": "string" }
      }
    },
    "routes": {
      "type": "object",
      "required": ["pattern"],
      "properties": {
        "pattern": {
          "type": "string",
          "enum": ["simple", "factory", "custom"]
        }
      }
    }
  }
}
```

Validate before generation:

```bash
npx ajv-cli validate -s schema.json -d config.json
```

## Migration from Existing Workflow

If you have an existing workflow:

1. **Extract configuration** to JSON:
   - Sprinkle name
   - Packages
   - Route pattern
   - Schema path

2. **Create config file**:
   ```bash
   cp .github/testing-framework/config/integration-test-config.json .
   ```

3. **Edit JSON** with your values

4. **Generate workflow**:
   ```bash
   node .github/testing-framework/scripts/generate-workflow.js
   ```

5. **Delete old workflow** (backed up in `.archive/`)

## Troubleshooting

### Invalid JSON

```bash
# Validate JSON syntax
node -e "console.log(JSON.parse(fs.readFileSync('integration-test-config.json')))"
```

### Route Pattern Issues

Check the generated workflow's route configuration step matches your sprinkle's pattern.

### Schema Path Issues

Ensure the path in JSON matches your actual schema location:
- Default: `""` = `app/schema/crud6/`
- Custom: `"examples/schema"` = `examples/schema/`

## Best Practices

1. **Keep JSON in repository root** for visibility
2. **Commit both JSON and generated workflow** for transparency
3. **Use JSON schema validation** in CI
4. **Document custom patterns** in JSON comments
5. **Version your configuration** alongside code

## Philosophy

Just as CRUD6 enables:
```json
{
  "model": "Product",
  "fields": { "name": "string" }
}
```

Integration testing should enable:
```json
{
  "sprinkle": { "name": "my-sprinkle" },
  "routes": { "pattern": "simple" }
}
```

**Same simplicity. Same power. Pure configuration.**

## Summary

**Configuration**: One JSON file (~50 lines)
**Generation**: One command
**Result**: Complete integration testing

No YAML editing. No code duplication. Just simple JSON configuration.

**Integration testing as simple as CRUD6 itself!**
