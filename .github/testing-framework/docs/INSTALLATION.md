# Installation Guide

Complete guide for installing the UserFrosting 6 integration testing framework into your sprinkle.

## Prerequisites

- UserFrosting 6.0.4 beta or later
- PHP 8.4 or later
- Node.js 20 or later (for screenshot tests)
- Git (recommended)

## Installation Methods

### Method 1: Quick Install (Recommended)

Use the installer script to automatically set up the framework:

```bash
# From your sprinkle root directory
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

Replace `your-sprinkle-name` with your actual sprinkle name (e.g., `myapp`, `c6admin`).

**What the installer does:**
- ✅ Creates `.github/config/` and `.github/scripts/` directories
- ✅ Copies template configuration files
- ✅ Replaces `yoursprinkle` placeholders with your sprinkle name
- ✅ Copies all reusable testing scripts
- ✅ Makes scripts executable
- ✅ Creates documentation

### Method 2: Download and Run Locally

For more control over the installation:

```bash
# 1. Download the installer
wget https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh

# 2. Make it executable
chmod +x install.sh

# 3. Run with your sprinkle name
./install.sh your-sprinkle-name
```

### Method 3: Clone and Install

If you want to review files before installing:

```bash
# 1. Clone the CRUD6 repository
git clone https://github.com/ssnukala/sprinkle-crud6.git /tmp/crud6

# 2. Navigate to your sprinkle directory
cd /path/to/your/sprinkle

# 3. Run the installer from the cloned repo
/tmp/crud6/.github/testing-framework/install.sh your-sprinkle-name
```

### Method 4: Manual Installation

For complete manual control:

```bash
# 1. Clone CRUD6 repo
git clone https://github.com/ssnukala/sprinkle-crud6.git /tmp/crud6

# 2. Create directories
mkdir -p .github/config .github/scripts

# 3. Copy template files
cp /tmp/crud6/.github/testing-framework/config/template-integration-test-paths.json \
   .github/config/integration-test-paths.json

cp /tmp/crud6/.github/testing-framework/config/template-integration-test-seeds.json \
   .github/config/integration-test-seeds.json

# 4. Copy all scripts
cp /tmp/crud6/.github/testing-framework/scripts/*.php .github/scripts/
cp /tmp/crud6/.github/testing-framework/scripts/*.js .github/scripts/

# 5. Make scripts executable
chmod +x .github/scripts/*.php

# 6. Replace placeholders (replace 'myapp' with your sprinkle name)
sed -i 's/yoursprinkle/myapp/g' .github/config/integration-test-*.json
```

## Advanced Installation Options

### Custom Namespace

If your sprinkle uses a custom PHP namespace:

```bash
./install.sh your-sprinkle-name --namespace "MyCompany\\MyApp"
```

### Dry Run

Preview what the installer will do without making changes:

```bash
./install.sh your-sprinkle-name --dry-run
```

### Custom Paths

Install to a different directory:

```bash
./install.sh your-sprinkle-name --target /path/to/directory
```

Use a different source:

```bash
./install.sh your-sprinkle-name --source /path/to/framework
```

## Post-Installation Steps

### 1. Verify Installation

Check that files were created:

```bash
ls -la .github/config/
ls -la .github/scripts/
```

You should see:
```
.github/
├── config/
│   ├── integration-test-paths.json
│   └── integration-test-seeds.json
├── scripts/
│   ├── run-seeds.php
│   ├── check-seeds-modular.php
│   ├── test-seed-idempotency-modular.php
│   ├── test-paths.php
│   └── take-screenshots-modular.js
└── TESTING_FRAMEWORK.md
```

### 2. Customize Configuration Files

Edit the configuration files to match your sprinkle:

```bash
# Edit paths configuration
nano .github/config/integration-test-paths.json

# Edit seeds configuration
nano .github/config/integration-test-seeds.json
```

See [CONFIGURATION.md](CONFIGURATION.md) for detailed customization guide.

### 3. Update Seed Class Names

In `integration-test-seeds.json`, update seed class names to match your actual classes:

```json
{
  "seeds": {
    "yoursprinkle": {
      "seeds": [
        {
          "class": "YourActual\\Namespace\\Database\\Seeds\\YourSeed",
          // ...
        }
      ]
    }
  }
}
```

### 4. Test Locally

Verify the installation works:

```bash
# Test seed configuration syntax
php -l .github/scripts/run-seeds.php

# Test path configuration syntax
php -l .github/scripts/test-paths.php

# Validate JSON syntax
cat .github/config/integration-test-paths.json | python3 -m json.tool
cat .github/config/integration-test-seeds.json | python3 -m json.tool
```

## Installing Dependencies

### PHP Dependencies

The framework requires your UserFrosting project to be set up:

```bash
# From your UserFrosting app root
composer install
```

### JavaScript Dependencies

For screenshot functionality:

```bash
# Install Playwright
npm install playwright

# Install Chromium browser
npx playwright install chromium --with-deps
```

## Integration with Existing Projects

### If You Already Have Tests

The framework is designed to coexist with existing tests:

1. Install the framework as described above
2. Keep your existing test files
3. Gradually migrate to the modular approach
4. Run both old and new tests during transition

### If You're Migrating from Manual Tests

1. Install the framework
2. Copy your existing test logic into the JSON configurations
3. Validate that the framework tests match your manual tests
4. Remove manual test code once validated

### If You're Starting Fresh

Perfect! Follow the installation guide and start building your test configurations.

## Updating the Framework

To get the latest version of the framework scripts:

```bash
# Re-run the installer (preserves your configurations)
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

The installer will:
- ✅ Update all scripts to latest versions
- ✅ Preserve your existing configuration files
- ✅ Create backups of any files it overwrites

Or manually update scripts:

```bash
# Clone latest CRUD6
git clone https://github.com/ssnukala/sprinkle-crud6.git /tmp/crud6

# Update scripts only
cp /tmp/crud6/.github/testing-framework/scripts/*.php .github/scripts/
cp /tmp/crud6/.github/testing-framework/scripts/*.js .github/scripts/
chmod +x .github/scripts/*.php
```

## Uninstallation

To remove the framework:

```bash
# Remove framework files
rm -rf .github/config/integration-test-*.json
rm -rf .github/scripts/
rm -f .github/TESTING_FRAMEWORK.md

# Or remove entire .github directory if it only contains the framework
rm -rf .github/
```

## Troubleshooting Installation

### Permission Denied

```bash
# Make installer executable
chmod +x install.sh

# Or run with bash explicitly
bash install.sh your-sprinkle-name
```

### curl: command not found

Use wget instead:

```bash
wget -qO- https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- your-sprinkle-name
```

### Directory Already Exists

The installer will skip creating existing directories and preserve existing files. If you want a fresh install:

```bash
# Backup existing files
mv .github .github.backup

# Run installer
./install.sh your-sprinkle-name

# Restore any custom files you want to keep
cp .github.backup/custom-file .github/
```

### Wrong Namespace Generated

Specify namespace manually:

```bash
./install.sh your-sprinkle-name --namespace "Your\\Correct\\Namespace"
```

### Files Not Found After Installation

Check that you're in the correct directory:

```bash
pwd  # Should show your sprinkle root

# List installed files
find .github -type f
```

## Next Steps

After successful installation:

1. ✅ Review the generated configuration files
2. ✅ Customize paths for your sprinkle
3. ✅ Update seed class names
4. ✅ Test locally: `php .github/scripts/test-paths.php .github/config/integration-test-paths.json`
5. ✅ Add to GitHub Actions (see [WORKFLOW_EXAMPLE.md](WORKFLOW_EXAMPLE.md))

## Support

- **Issues**: [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
- **Documentation**: See other files in `docs/` directory
- **Examples**: Check [CRUD6 implementation](https://github.com/ssnukala/sprinkle-crud6)

## Summary

The installation should take less than 5 minutes using the quick install method. You'll get:

- ✅ Fully configured testing framework
- ✅ Template files with your sprinkle name
- ✅ All reusable scripts
- ✅ Documentation

Ready to test? See [CONFIGURATION.md](CONFIGURATION.md) for customization guide.
