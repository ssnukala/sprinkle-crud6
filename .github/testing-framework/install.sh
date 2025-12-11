#!/bin/bash

##############################################################################
# UserFrosting 6 Sprinkle Integration Testing Framework - Installer
#
# This script installs the CRUD6 integration testing framework into your
# UserFrosting 6 sprinkle with automatic parameterization.
#
# Usage:
#   ./install.sh <sprinkle-name> [options]
#
# Arguments:
#   sprinkle-name   Required. Your sprinkle name (e.g., 'myapp', 'c6admin')
#                   Will be used to replace 'yoursprinkle' in templates
#
# Options:
#   --namespace     Your sprinkle's PHP namespace (default: auto-generated from sprinkle-name)
#   --source        Source directory for framework files (default: auto-detect)
#   --target        Target directory for installation (default: current directory)
#   --dry-run       Show what would be done without making changes
#   --help          Show this help message
#
# Examples:
#   ./install.sh myapp
#   ./install.sh c6admin --namespace "MyCompany\\C6Admin"
#   ./install.sh myapp --dry-run
#
##############################################################################

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
DRY_RUN=false
SOURCE_DIR=""
TARGET_DIR="."
SPRINKLE_NAME=""
NAMESPACE=""

##############################################################################
# Helper Functions
##############################################################################

print_header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ ERROR: $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  WARNING: $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

show_help() {
    grep "^#" "$0" | grep -v "^#!/" | sed 's/^# //' | sed 's/^#//'
    exit 0
}

##############################################################################
# Parse Arguments
##############################################################################

while [[ $# -gt 0 ]]; do
    case $1 in
        --help|-h)
            show_help
            ;;
        --namespace)
            NAMESPACE="$2"
            shift 2
            ;;
        --source)
            SOURCE_DIR="$2"
            shift 2
            ;;
        --target)
            TARGET_DIR="$2"
            shift 2
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        -*)
            print_error "Unknown option: $1"
            show_help
            ;;
        *)
            if [ -z "$SPRINKLE_NAME" ]; then
                SPRINKLE_NAME="$1"
            else
                print_error "Unexpected argument: $1"
                show_help
            fi
            shift
            ;;
    esac
done

# Validate required arguments
if [ -z "$SPRINKLE_NAME" ]; then
    print_error "Sprinkle name is required"
    echo ""
    echo "Usage: $0 <sprinkle-name> [options]"
    echo "Run '$0 --help' for more information"
    exit 1
fi

##############################################################################
# Auto-detect Source Directory
##############################################################################

if [ -z "$SOURCE_DIR" ]; then
    # Try to find the framework directory
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    
    # If running from within the framework directory
    if [ -f "$SCRIPT_DIR/README.md" ] && [ -d "$SCRIPT_DIR/scripts" ]; then
        SOURCE_DIR="$SCRIPT_DIR"
        print_info "Detected framework directory: $SOURCE_DIR"
    # If running from CRUD6 root
    elif [ -d "$SCRIPT_DIR/../testing-framework" ]; then
        SOURCE_DIR="$SCRIPT_DIR/../testing-framework"
        print_info "Detected framework directory: $SOURCE_DIR"
    # Try relative to current directory
    elif [ -d ".github/testing-framework" ]; then
        SOURCE_DIR=".github/testing-framework"
        print_info "Using framework directory: $SOURCE_DIR"
    else
        print_error "Could not auto-detect framework source directory"
        print_info "Please specify --source /path/to/testing-framework"
        exit 1
    fi
fi

# Validate source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    print_error "Source directory not found: $SOURCE_DIR"
    exit 1
fi

if [ ! -d "$SOURCE_DIR/scripts" ] || [ ! -d "$SOURCE_DIR/config" ]; then
    print_error "Invalid framework directory: missing scripts/ or config/ subdirectories"
    exit 1
fi

##############################################################################
# Generate Namespace if not provided
##############################################################################

if [ -z "$NAMESPACE" ]; then
    # Convert sprinkle name to PascalCase for namespace
    # Example: c6admin -> C6Admin, my-app -> MyApp
    NAMESPACE=$(echo "$SPRINKLE_NAME" | sed -r 's/(^|-)([a-z])/\U\2/g')
    print_info "Auto-generated namespace: $NAMESPACE"
fi

##############################################################################
# Summary and Confirmation
##############################################################################

print_header "UserFrosting 6 Integration Testing Framework - Installer"

echo "Configuration:"
echo "  Sprinkle Name:    $SPRINKLE_NAME"
echo "  Namespace:        $NAMESPACE"
echo "  Source Directory: $SOURCE_DIR"
echo "  Target Directory: $TARGET_DIR"
echo "  Dry Run:          $DRY_RUN"
echo ""

if [ "$DRY_RUN" = true ]; then
    print_warning "DRY RUN MODE - No changes will be made"
    echo ""
fi

##############################################################################
# Create Directory Structure
##############################################################################

print_header "Step 1: Creating Directory Structure"

GITHUB_DIR="$TARGET_DIR/.github"
CONFIG_DIR="$GITHUB_DIR/config"
SCRIPTS_DIR="$GITHUB_DIR/scripts"

for dir in "$GITHUB_DIR" "$CONFIG_DIR" "$SCRIPTS_DIR"; do
    if [ "$DRY_RUN" = true ]; then
        echo "[DRY RUN] Would create directory: $dir"
    else
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            print_success "Created directory: $dir"
        else
            print_info "Directory already exists: $dir"
        fi
    fi
done

##############################################################################
# Copy and Parameterize Configuration Files
##############################################################################

print_header "Step 2: Installing Configuration Files"

# Copy template files with parameterization
TEMPLATE_FILES=(
    "template-integration-test-paths.json:integration-test-paths.json"
    "template-integration-test-seeds.json:integration-test-seeds.json"
)

for file_mapping in "${TEMPLATE_FILES[@]}"; do
    SRC_FILE="${file_mapping%%:*}"
    DEST_FILE="${file_mapping##*:}"
    
    SRC_PATH="$SOURCE_DIR/config/$SRC_FILE"
    DEST_PATH="$CONFIG_DIR/$DEST_FILE"
    
    if [ ! -f "$SRC_PATH" ]; then
        print_warning "Template file not found: $SRC_PATH (skipping)"
        continue
    fi
    
    if [ "$DRY_RUN" = true ]; then
        echo "[DRY RUN] Would copy and parameterize: $SRC_FILE -> $DEST_FILE"
    else
        # Copy file and replace placeholders
        sed -e "s/yoursprinkle/$SPRINKLE_NAME/g" \
            -e "s/Your\\\\\\\\Sprinkle\\\\\\\\Namespace/$NAMESPACE/g" \
            -e "s/YourSprinkle/$NAMESPACE/g" \
            "$SRC_PATH" > "$DEST_PATH"
        
        print_success "Installed: $DEST_FILE (parameterized with '$SPRINKLE_NAME')"
    fi
done

##############################################################################
# Copy Testing Scripts
##############################################################################

print_header "Step 3: Installing Testing Scripts"

# Count scripts
PHP_SCRIPTS=$(find "$SOURCE_DIR/scripts" -name "*.php" 2>/dev/null | wc -l)
JS_SCRIPTS=$(find "$SOURCE_DIR/scripts" -name "*.js" 2>/dev/null | wc -l)
SH_SCRIPTS=$(find "$SOURCE_DIR/scripts" -name "*.sh" -not -name "install.sh" 2>/dev/null | wc -l)

print_info "Found $PHP_SCRIPTS PHP scripts, $JS_SCRIPTS JS scripts, $SH_SCRIPTS shell scripts"

# Copy all scripts
for script in "$SOURCE_DIR/scripts"/*.php "$SOURCE_DIR/scripts"/*.js "$SOURCE_DIR/scripts"/*.sh; do
    if [ -f "$script" ]; then
        SCRIPT_NAME=$(basename "$script")
        
        # Skip the install script itself
        if [ "$SCRIPT_NAME" = "install.sh" ]; then
            continue
        fi
        
        DEST_PATH="$SCRIPTS_DIR/$SCRIPT_NAME"
        
        if [ "$DRY_RUN" = true ]; then
            echo "[DRY RUN] Would copy: $SCRIPT_NAME"
        else
            cp "$script" "$DEST_PATH"
            chmod +x "$DEST_PATH"
            print_success "Installed: $SCRIPT_NAME"
        fi
    fi
done

##############################################################################
# Create README
##############################################################################

print_header "Step 4: Creating Documentation"

README_PATH="$GITHUB_DIR/TESTING_FRAMEWORK.md"

if [ "$DRY_RUN" = true ]; then
    echo "[DRY RUN] Would create: TESTING_FRAMEWORK.md"
else
    cat > "$README_PATH" << EOF
# Integration Testing Framework for $NAMESPACE

This directory contains the integration testing framework for the **$SPRINKLE_NAME** sprinkle.

## 📁 Structure

\`\`\`
.github/
├── config/
│   ├── integration-test-paths.json   # API and frontend path definitions
│   └── integration-test-seeds.json   # Database seed configurations
├── scripts/
│   ├── run-seeds.php                 # Run seeds from configuration
│   ├── check-seeds-modular.php       # Validate seed data
│   ├── test-seed-idempotency-modular.php  # Test idempotency
│   ├── test-paths.php                # Test API/frontend paths
│   └── take-screenshots-modular.js   # Capture screenshots
└── TESTING_FRAMEWORK.md             # This file
\`\`\`

## 🚀 Quick Start

### Run All Tests Locally

\`\`\`bash
# 1. Run database seeds
php .github/scripts/run-seeds.php .github/config/integration-test-seeds.json

# 2. Validate seeds were created
php .github/scripts/check-seeds-modular.php .github/config/integration-test-seeds.json

# 3. Test API and frontend paths
php .github/scripts/test-paths.php .github/config/integration-test-paths.json

# 4. Take screenshots (requires Playwright)
node .github/scripts/take-screenshots-modular.js .github/config/integration-test-paths.json
\`\`\`

## 📝 Configuration

### Customizing Paths (\`integration-test-paths.json\`)

This file defines which API endpoints and frontend routes to test.

**Add a new API endpoint:**
\`\`\`json
{
  "authenticated": {
    "api": {
      "my_new_endpoint": {
        "method": "GET",
        "path": "/api/$SPRINKLE_NAME/mymodel",
        "expected_status": 200,
        "validation": {
          "type": "json",
          "contains": ["rows"]
        }
      }
    }
  }
}
\`\`\`

**Add a frontend page with screenshot:**
\`\`\`json
{
  "authenticated": {
    "frontend": {
      "my_page": {
        "path": "/$SPRINKLE_NAME/mypage",
        "description": "My custom page",
        "screenshot": true,
        "screenshot_name": "my_page"
      }
    }
  }
}
\`\`\`

### Customizing Seeds (\`integration-test-seeds.json\`)

This file defines database seeds and how to validate them.

**Add a new seed:**
\`\`\`json
{
  "seeds": {
    "$SPRINKLE_NAME": {
      "seeds": [
        {
          "class": "$NAMESPACE\\\\Database\\\\Seeds\\\\MySeed",
          "description": "My custom seed",
          "required": true,
          "validation": {
            "type": "role",
            "slug": "my-role-slug",
            "expected_count": 1
          }
        }
      ]
    }
  }
}
\`\`\`

## 📚 Framework Documentation

For complete framework documentation, see:
- [Framework README](https://github.com/ssnukala/sprinkle-crud6/tree/main/.github/testing-framework)
- [Configuration Guide](https://github.com/ssnukala/sprinkle-crud6/blob/main/.github/testing-framework/docs/CONFIGURATION.md)
- [API Reference](https://github.com/ssnukala/sprinkle-crud6/blob/main/.github/testing-framework/docs/API_REFERENCE.md)

## 🔄 Updating the Framework

To update to the latest framework version:

\`\`\`bash
# Re-run the installer (will overwrite scripts but preserve configs)
curl -sSL https://raw.githubusercontent.com/ssnukala/sprinkle-crud6/main/.github/testing-framework/install.sh | bash -s -- $SPRINKLE_NAME
\`\`\`

Or manually:
\`\`\`bash
# Download latest framework
git clone https://github.com/ssnukala/sprinkle-crud6.git /tmp/crud6

# Update scripts only (preserves your configurations)
cp /tmp/crud6/.github/testing-framework/scripts/*.php .github/scripts/
cp /tmp/crud6/.github/testing-framework/scripts/*.js .github/scripts/
chmod +x .github/scripts/*.php
\`\`\`

## 🤝 Contributing

Found a bug or want to improve the framework?
Open an issue at: https://github.com/ssnukala/sprinkle-crud6/issues

---

**Installed:** $(date)
**Framework Source:** https://github.com/ssnukala/sprinkle-crud6
EOF
    print_success "Created: TESTING_FRAMEWORK.md"
fi

##############################################################################
# Final Summary
##############################################################################

print_header "Installation Complete!"

if [ "$DRY_RUN" = true ]; then
    print_warning "DRY RUN completed - no actual changes were made"
    echo ""
    print_info "Remove --dry-run flag to perform actual installation"
else
    print_success "Integration testing framework installed successfully!"
    echo ""
    echo "Files installed:"
    echo "  📁 Configuration files: $CONFIG_DIR/"
    echo "     - integration-test-paths.json"
    echo "     - integration-test-seeds.json"
    echo ""
    echo "  📁 Testing scripts: $SCRIPTS_DIR/"
    echo "     - $PHP_SCRIPTS PHP scripts"
    echo "     - $JS_SCRIPTS JavaScript scripts"
    echo "     - $SH_SCRIPTS shell scripts"
    echo ""
    echo "  📄 Documentation: $README_PATH"
fi

echo ""
print_header "Next Steps"

echo "1️⃣  Customize your configuration files:"
echo "   - Edit $CONFIG_DIR/integration-test-paths.json"
echo "   - Edit $CONFIG_DIR/integration-test-seeds.json"
echo "   - Replace placeholder values with your actual models and routes"
echo ""
echo "2️⃣  Update your seed class namespaces:"
echo "   - Verify seed class names match your actual seed classes"
echo "   - Update validation rules to match your roles/permissions"
echo ""
echo "3️⃣  Test locally:"
echo "   - Run: php $SCRIPTS_DIR/run-seeds.php $CONFIG_DIR/integration-test-seeds.json"
echo "   - Run: php $SCRIPTS_DIR/check-seeds-modular.php $CONFIG_DIR/integration-test-seeds.json"
echo "   - Run: php $SCRIPTS_DIR/test-paths.php $CONFIG_DIR/integration-test-paths.json"
echo ""
echo "4️⃣  Add to GitHub Actions:"
echo "   - See example workflow at:"
echo "     https://github.com/ssnukala/sprinkle-crud6/blob/main/.github/workflows/integration-test.yml"
echo ""

print_info "For detailed documentation, see:"
echo "   - $README_PATH (local)"
echo "   - https://github.com/ssnukala/sprinkle-crud6/tree/main/.github/testing-framework (online)"

echo ""
print_success "Happy testing! 🚀"
echo ""
