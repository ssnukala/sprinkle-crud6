# CRUD6 Sprinkle - GitHub Configuration

This directory contains the modular integration testing framework and GitHub Actions workflows for the CRUD6 sprinkle.

## 📚 Documentation

Start here based on what you need:

### For Users Wanting to Adapt This Framework
👉 **[QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)** - Step-by-step guide to adapt the testing framework for your sprinkle

### For Understanding the Framework
👉 **[MODULAR_TESTING_README.md](MODULAR_TESTING_README.md)** - Complete technical documentation

### For Implementation Details
👉 **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Overview of what was built and why

## 📁 Directory Structure

```
.github/
├── config/                          # JSON configuration files
│   ├── integration-test-paths.json      # CRUD6 path definitions
│   ├── integration-test-seeds.json      # CRUD6 seed definitions
│   ├── template-integration-test-paths.json  # Template for your sprinkle
│   └── template-integration-test-seeds.json  # Template for your sprinkle
│
├── scripts/                         # Reusable testing scripts
│   ├── run-seeds.php                    # Run seeds from JSON config
│   ├── check-seeds-modular.php          # Validate seeds
│   ├── test-seed-idempotency-modular.php # Test seed idempotency
│   ├── test-paths.php                   # Test API/frontend paths
│   ├── check-seeds.php                  # Original (for compatibility)
│   ├── test-seed-idempotency.php        # Original (for compatibility)
│   └── take-authenticated-screenshots.js # Screenshot utility
│
├── workflows/                       # GitHub Actions workflows
│   ├── integration-test.yml             # Main integration test workflow
│   └── integration-test.yml.backup      # Backup of original workflow
│
├── QUICK_START_GUIDE.md            # 🚀 Start here for adapting to your sprinkle
├── MODULAR_TESTING_README.md       # Complete framework documentation
├── IMPLEMENTATION_SUMMARY.md       # What was built and why
└── README.md                        # This file
```

## 🚀 Quick Start

To use this framework for your own sprinkle:

```bash
# 1. Copy template files
cp .github/config/template-integration-test-paths.json \
   your-sprinkle/.github/config/integration-test-paths.json

cp .github/config/template-integration-test-seeds.json \
   your-sprinkle/.github/config/integration-test-seeds.json

# 2. Copy scripts
cp .github/scripts/*.php your-sprinkle/.github/scripts/
cp .github/scripts/*.js your-sprinkle/.github/scripts/

# 3. Customize JSON files for your sprinkle
# - Replace 'yoursprinkle' with your sprinkle name
# - Replace 'yourmodel' with your model names
# - Update seed classes and validation rules

# 4. Update your GitHub Actions workflow
# See QUICK_START_GUIDE.md for workflow examples
```

See **[QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)** for detailed instructions.

## 🎯 Key Features

✅ **Configuration-Driven** - All test definitions in JSON files  
✅ **Reusable Scripts** - Same scripts work for all sprinkles  
✅ **Template-Based** - Copy and customize for your sprinkle  
✅ **Self-Documenting** - JSON structure explains what's tested  
✅ **Validated** - All syntax validated and tested  
✅ **Complete Examples** - Working CRUD6 implementation included  

## 📝 Configuration Files Explained

### Path Configuration (`integration-test-paths.json`)
Defines API and frontend paths to test:
- Authenticated vs. unauthenticated paths
- Expected HTTP status codes
- Response validation rules
- Screenshot configuration

### Seed Configuration (`integration-test-seeds.json`)
Defines database seeds and validation:
- Seed classes and execution order
- Validation rules for roles and permissions
- Idempotency testing configuration
- Admin user setup

## 🛠️ Testing Scripts

| Script | Purpose | Usage |
|--------|---------|-------|
| `run-seeds.php` | Run seeds from config | `php run-seeds.php config.json [sprinkle]` |
| `check-seeds-modular.php` | Validate seeds | `php check-seeds-modular.php config.json` |
| `test-seed-idempotency-modular.php` | Test idempotency | `php test-seed-idempotency-modular.php config.json` |
| `test-paths.php` | Test paths | `php test-paths.php config.json [auth] [type]` |

All scripts are reusable across sprinkles - just provide different configuration files!

## 📊 Validation Types

The framework supports various validation types:

- **Role Validation** - Check roles exist with correct count
- **Permission Validation** - Verify permissions and role assignments
- **JSON Response** - Validate API JSON responses
- **Redirect Validation** - Check redirects to login
- **Status Only** - Verify HTTP status codes

See [MODULAR_TESTING_README.md](MODULAR_TESTING_README.md) for complete validation documentation.

## 🔄 Workflow Integration

The GitHub Actions workflow (`.github/workflows/integration-test.yml`) uses the modular framework:

```yaml
- name: Seed database (Modular)
  run: |
    cp ../sprinkle-crud6/.github/config/integration-test-seeds.json .
    cp ../sprinkle-crud6/.github/scripts/run-seeds.php .
    php run-seeds.php integration-test-seeds.json
```

Original workflow preserved as `integration-test.yml.backup`.

## 🎓 Learning Resources

1. **New to the framework?** → Start with [QUICK_START_GUIDE.md](QUICK_START_GUIDE.md)
2. **Need full details?** → Read [MODULAR_TESTING_README.md](MODULAR_TESTING_README.md)
3. **Want implementation context?** → See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
4. **Looking for examples?** → Check `config/integration-test-*.json` files

## 💡 Benefits

### For Your Sprinkle
- No workflow code changes needed
- Just modify JSON configuration
- Same proven scripts as CRUD6
- Consistent testing approach

### For the Team
- Faster test development
- Reduced code duplication
- Better maintainability
- Self-documenting tests

## 🤝 Contributing

When adding new features to the framework:
1. Update template files
2. Document in MODULAR_TESTING_README.md
3. Add examples to QUICK_START_GUIDE.md
4. Test with actual sprinkle implementation

## 📞 Support

- Issues: Open an issue on GitHub
- Documentation: See files in this directory
- Examples: Review CRUD6 configuration files

---

**Built for UserFrosting 6** - A modular, reusable integration testing framework for all sprinkles.
