# UserFrosting 6 Sprinkle Integration Testing Framework

A reusable, configuration-driven integration testing framework for UserFrosting 6 sprinkles.

## 🎯 What This Is

This is a **complete integration testing framework** that can be used by any UserFrosting 6 sprinkle. Instead of replicating testing infrastructure across every sprinkle, you can use this framework by:

1. Running an installer script that adapts it for your sprinkle
2. Customizing JSON configuration files for your specific needs
3. Running the same proven testing scripts that CRUD6 uses

## 📦 What's Included

- **Reusable Testing Scripts** - PHP and JavaScript scripts that work for any sprinkle
- **Configuration Templates** - JSON templates you customize for your sprinkle
- **GitHub Actions Workflow** - Complete CI/CD workflow ready to use
- **Documentation** - Comprehensive guides and examples
- **Installer Script** - Automated setup with parameterization

## 🚀 Quick Installation

### Option 1: Use the Installer Script (Recommended)

```bash
# From your sprinkle root directory
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

Or download and run locally:

```bash
# Download the installer
wget https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh

# Make it executable
chmod +x install.sh

# Run with your sprinkle name
./install.sh your-sprinkle-name
```

The installer will:
- ✅ Create `.github/config/` and `.github/scripts/` directories
- ✅ Copy template files with your sprinkle name substituted
- ✅ Copy all reusable testing scripts
- ✅ Make scripts executable
- ✅ Provide next steps for customization

### Option 2: Manual Installation

If you prefer manual control:

```bash
# 1. Clone or download the CRUD6 repository
git clone https://github.com/ssnukala/sprinkle-crud6.git /tmp/crud6

# 2. Copy the testing framework to your sprinkle
mkdir -p .github/config .github/scripts

# 3. Copy and rename template files
cp /tmp/crud6/.github/testing-framework/config/template-integration-test-paths.json \
   .github/config/integration-test-paths.json

cp /tmp/crud6/.github/testing-framework/config/template-integration-test-seeds.json \
   .github/config/integration-test-seeds.json

# 4. Copy all reusable scripts
cp /tmp/crud6/.github/testing-framework/scripts/*.php .github/scripts/
cp /tmp/crud6/.github/testing-framework/scripts/*.js .github/scripts/
cp /tmp/crud6/.github/testing-framework/scripts/*.sh .github/scripts/

# 5. Make scripts executable
chmod +x .github/scripts/*.php .github/scripts/*.sh

# 6. Customize the configuration files (replace 'yoursprinkle' with your actual sprinkle name)
sed -i 's/yoursprinkle/mysprinkle/g' .github/config/integration-test-*.json
```

## 📝 Configuration

After installation, you'll have two JSON configuration files to customize:

### 1. `integration-test-paths.json`

Defines API endpoints and frontend routes to test.

**What to customize:**
- Replace `yoursprinkle` with your sprinkle's route prefix
- Replace `yourmodel` with your actual model names
- Add/remove paths as needed for your sprinkle
- Configure which pages should have screenshots taken

**Example:**
```json
{
  "paths": {
    "authenticated": {
      "api": {
        "products_list": {
          "method": "GET",
          "path": "/api/myapp/products",
          "expected_status": 200
        }
      }
    }
  }
}
```

### 2. `integration-test-seeds.json`

Defines database seeds and validation rules.

**What to customize:**
- Keep the `account` section as-is (required base seeds)
- Rename `yoursprinkle` section to your sprinkle name
- Update seed class names to match your sprinkle's namespace
- Configure validation rules for your roles and permissions

**Example:**
```json
{
  "seeds": {
    "account": { /* keep as-is */ },
    "myapp": {
      "order": 2,
      "seeds": [
        {
          "class": "MyApp\\Database\\Seeds\\DefaultRoles",
          "validation": {
            "type": "role",
            "slug": "myapp-admin"
          }
        }
      ]
    }
  }
}
```

## 🧪 Usage

Once configured, use these commands in your GitHub Actions workflow or locally:

```bash
# Run database seeds
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json

# Validate seeds were created correctly
php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json

# Test seed idempotency (can run multiple times without duplicates)
php .github/scripts/test-seed-idempotency-modular.php .github/config/integration-test-seeds.json

# Test API and frontend paths
php .github/scripts/test-paths.php .github/config/integration-test-paths.json

# Take screenshots of frontend pages
node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json
```

## ⚠️ Frontend Integration Nuances

**Important:** Different sprinkles have different frontend integration patterns. The framework provides basic examples, but you may need to customize based on your sprinkle's specific needs.

**Common patterns:**
- **Simple Array Import** (CRUD6): `import CRUD6Routes from '...'` then `...CRUD6Routes`
- **Factory Function** (C6Admin): `import { createC6AdminRoutes }` then `...createC6AdminRoutes({ layoutComponent: Layout })`
- **Nested Routes**: Using parent components with children routes

See [Frontend Integration Patterns](docs/FRONTEND_INTEGRATION_PATTERNS.md) for complete guide on handling different patterns.

## 🔧 GitHub Actions Integration

Add to your `.github/workflows/integration-test.yml`:

```yaml
- name: Run seeds from configuration
  run: |
    cd userfrosting
    cp ../my-sprinkle/.github/config/integration-test-seeds.json .
    cp ../my-sprinkle/.github/scripts/run-seeds.php .
    php run-seeds.php integration-test-seeds.json

- name: Validate seed data
  run: |
    cd userfrosting
    cp ../my-sprinkle/.github/scripts/check-seeds-modular.php .
    php check-seeds-modular.php integration-test-seeds.json

- name: Test API paths
  run: |
    cd userfrosting
    cp ../my-sprinkle/.github/config/integration-test-paths.json .
    cp ../my-sprinkle/.github/scripts/test-paths.php .
    php test-paths.php integration-test-paths.json
```

See the [complete workflow example](docs/WORKFLOW_EXAMPLE.md) for a full GitHub Actions setup.

## 📚 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed installation instructions
- **[Configuration Guide](docs/CONFIGURATION.md)** - How to customize for your sprinkle
- **[Workflow Example](docs/WORKFLOW_EXAMPLE.md)** - Complete GitHub Actions workflow
- **[Frontend Integration Patterns](docs/FRONTEND_INTEGRATION_PATTERNS.md)** - Handle different route configuration patterns
- **[Migration Guide](docs/MIGRATION.md)** - Migrating from hardcoded tests
- **[API Reference](docs/API_REFERENCE.md)** - Script usage and parameters

## 🎓 Examples

### Complete Working Example: CRUD6 Sprinkle

The CRUD6 sprinkle itself uses this framework. See:
- [CRUD6 Paths Config](../../config/integration-test-paths.json)
- [CRUD6 Seeds Config](../../config/integration-test-seeds.json)
- [CRUD6 Workflow](../../workflows/integration-test.yml)

### Real-World Usage

Check out these sprinkles using the framework:
- [sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6) - The original implementation
- [sprinkle-c6admin](https://github.com/ssnukala/sprinkle-c6admin) - Admin sprinkle example (coming soon)

## 🔍 How It Works

### Configuration-Driven Approach

Instead of writing custom PHP/JavaScript code for each test, you define **what to test** in JSON files, and the framework handles **how to test it**.

**Traditional Approach (Hard to Maintain):**
```php
// Custom test code for each sprinkle
$response = $this->get('/api/myapp/products');
$this->assertEquals(200, $response->getStatusCode());
// ... repeat for every endpoint
```

**Framework Approach (Easy to Maintain):**
```json
{
  "products_list": {
    "method": "GET",
    "path": "/api/myapp/products",
    "expected_status": 200
  }
}
```

### Parameterization

The installer script replaces placeholders with your actual sprinkle name:
- `yoursprinkle` → your sprinkle name
- `yourmodel` → your model names
- `Your\\Sprinkle\\Namespace` → your actual namespace

This means **zero code changes** to the testing scripts themselves!

## ✨ Benefits

### For Individual Sprinkles
- ✅ **Fast Setup** - Install and configure in minutes, not hours
- ✅ **Battle-Tested** - Same scripts proven in production use
- ✅ **Maintainable** - Update JSON, not code
- ✅ **Documented** - Clear examples and guides

### For the UserFrosting Ecosystem
- ✅ **Consistent Testing** - All sprinkles test the same way
- ✅ **Shared Improvements** - Framework updates benefit everyone
- ✅ **Reduced Duplication** - One framework, many sprinkles
- ✅ **Best Practices** - Encodes UF6 testing standards

## 🆚 Comparison with Manual Approach

| Aspect | Manual (sprinkle-c6admin style) | This Framework |
|--------|--------------------------------|----------------|
| Initial Setup | Write custom tests from scratch | Run installer, customize JSON |
| Code Duplication | Copy/paste across sprinkles | Reuse same scripts |
| Maintenance | Update code in each sprinkle | Update JSON config only |
| Learning Curve | Learn testing patterns each time | Learn once, use everywhere |
| Consistency | Varies by developer | Standardized approach |
| Updates | Manual updates to each sprinkle | Pull latest framework version |

## 🤝 Contributing

Found a bug or want to add a feature to the framework?

1. Open an issue on the [CRUD6 repository](https://github.com/ssnukala/sprinkle-crud6/issues)
2. Submit a PR with improvements to `.github/testing-framework/`
3. All sprinkles benefit from your contribution!

## 📄 License

This testing framework is part of the CRUD6 sprinkle and uses the same MIT license.

## 🙋 Support

- **Issues**: [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ssnukala/sprinkle-crud6/discussions)
- **Documentation**: See the `docs/` directory
- **Examples**: Check the CRUD6 sprinkle implementation

---

**Built for UserFrosting 6** - Making integration testing easy and consistent across all sprinkles.
