# Quick Reference Guide

One-page reference for the UserFrosting 6 Integration Testing Framework.

## Installation

```bash
# Quick install
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- YOUR-SPRINKLE-NAME

# With custom namespace
curl -sSL ... | bash -s -- YOUR-SPRINKLE-NAME --namespace "Your\\Namespace"

# Dry run (preview only)
./install.sh YOUR-SPRINKLE-NAME --dry-run
```

## Files Created

```
.github/
├── config/
│   ├── integration-test-paths.json      # API/frontend test definitions
│   └── integration-test-seeds.json      # Database seed configurations
├── scripts/
│   ├── run-seeds.php                    # Run seeds
│   ├── check-seeds-modular.php          # Validate seeds
│   ├── test-seed-idempotency-modular.php # Test idempotency
│   ├── test-paths.php                   # Test paths
│   └── take-screenshots-modular.js      # Capture screenshots
└── TESTING_FRAMEWORK.md                 # Local documentation
```

## Basic Usage

```bash
# Run database seeds
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json

# Validate seeds were created
php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json

# Test idempotency
BEFORE=$(php .github/scripts/test-seed-idempotency-modular.php .github/config/integration-test-seeds.json | grep "BEFORE:")
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json
php .github/scripts/test-seed-idempotency-modular.php .github/config/integration-test-seeds.json after "$BEFORE"

# Test all paths
php .github/scripts/test-paths.php .github/config/integration-test-paths.json

# Test only API paths
php .github/scripts/test-paths.php .github/config/integration-test-paths.json both api

# Test only authenticated paths
php .github/scripts/test-paths.php .github/config/integration-test-paths.json auth

# Capture screenshots
node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json
```

## Configuration Examples

### API Endpoint (paths.json)

```json
{
  "authenticated": {
    "api": {
      "products_list": {
        "method": "GET",
        "path": "/api/myapp/products",
        "expected_status": 200,
        "validation": {
          "type": "json",
          "contains": ["rows"]
        }
      }
    }
  }
}
```

### Frontend Page with Screenshot (paths.json)

```json
{
  "authenticated": {
    "frontend": {
      "products_page": {
        "path": "/myapp/products",
        "screenshot": true,
        "screenshot_name": "products_list"
      }
    }
  }
}
```

### Database Seed (seeds.json)

```json
{
  "seeds": {
    "myapp": {
      "order": 2,
      "seeds": [
        {
          "class": "MyApp\\Database\\Seeds\\DefaultRoles",
          "description": "Create myapp roles",
          "required": true,
          "validation": {
            "type": "role",
            "slug": "myapp-admin",
            "expected_count": 1
          }
        }
      ]
    }
  }
}
```

### Permission Validation (seeds.json)

```json
{
  "validation": {
    "type": "permissions",
    "slugs": ["create_products", "edit_products"],
    "expected_count": 2,
    "role_assignments": {
      "myapp-admin": 2
    }
  }
}
```

## GitHub Actions Integration

```yaml
- name: Run seeds
  run: |
    cd userfrosting
    cp ../myapp/.github/config/integration-test-seeds.json .
    cp ../myapp/.github/scripts/run-seeds.php .
    php run-seeds.php integration-test-seeds.json

- name: Validate seeds
  run: |
    cd userfrosting
    cp ../myapp/.github/scripts/check-seeds-modular.php .
    php check-seeds-modular.php integration-test-seeds.json

- name: Test paths
  run: |
    cd userfrosting
    cp ../myapp/.github/config/integration-test-paths.json .
    cp ../myapp/.github/scripts/test-paths.php .
    php test-paths.php integration-test-paths.json
```

## Common Commands

```bash
# Validate JSON syntax
cat .github/config/integration-test-paths.json | python3 -m json.tool

# Make scripts executable
chmod +x .github/scripts/*.php

# Update to latest framework
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- YOUR-SPRINKLE-NAME
```

## Validation Types

| Type | Purpose | Example |
|------|---------|---------|
| `json` | Validate JSON response | API endpoints |
| `status_only` | Check HTTP status | Simple endpoints |
| `redirect_to_login` | Check auth redirect | Unauthenticated access |
| `role` | Validate role exists | Seed validation |
| `permissions` | Validate permissions | Seed validation |

## Path Test Modes

| Mode | Tests | Usage |
|------|-------|-------|
| `both` (default) | Auth + unauth | All paths |
| `auth` | Authenticated only | Logged-in tests |
| `unauth` | Unauthenticated only | Security tests |

## Path Test Types

| Type | Tests | Usage |
|------|-------|-------|
| `both` (default) | API + frontend | All paths |
| `api` | API only | Backend tests |
| `frontend` | Frontend only | UI tests |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Permission denied | `chmod +x .github/scripts/*.php` |
| Config not found | Check file path in command |
| JSON syntax error | Validate with `python3 -m json.tool` |
| Seed class not found | Check namespace in config |
| Screenshot fails | Install Playwright: `npm install playwright` |

## Documentation

| Guide | Purpose |
|-------|---------|
| [README.md](README.md) | Overview and quick start |
| [INSTALLATION.md](docs/INSTALLATION.md) | Installation methods |
| [CONFIGURATION.md](docs/CONFIGURATION.md) | Config reference |
| [WORKFLOW_EXAMPLE.md](docs/WORKFLOW_EXAMPLE.md) | GitHub Actions |
| [API_REFERENCE.md](docs/API_REFERENCE.md) | Script docs |
| [MIGRATION.md](docs/MIGRATION.md) | Migration guide |
| [SUMMARY.md](SUMMARY.md) | Complete overview |

## Support

- **Issues**: [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
- **Documentation**: `.github/testing-framework/docs/`
- **Examples**: [CRUD6 Implementation](https://github.com/ssnukala/sprinkle-crud6)

## Next Steps After Installation

1. ✅ Edit `.github/config/integration-test-paths.json`
   - Replace `yoursprinkle` with actual routes
   - Add your API endpoints
   - Add your frontend pages
   - Enable screenshots where needed

2. ✅ Edit `.github/config/integration-test-seeds.json`
   - Update seed class names
   - Configure validation rules
   - Add your sprinkle's seeds

3. ✅ Test locally
   - Run seeds
   - Validate seeds
   - Test paths
   - Capture screenshots

4. ✅ Add to GitHub Actions
   - Copy workflow template
   - Customize for your sprinkle
   - Test in CI/CD

5. ✅ Remove old tests (if migrating)
   - Archive old scripts
   - Update documentation
   - Train team

---

**Quick Links:**
- [Framework Source](https://github.com/ssnukala/sprinkle-crud6/tree/main/.github/testing-framework)
- [Installation Command](#installation)
- [Configuration Examples](#configuration-examples)
- [Troubleshooting](#troubleshooting)
