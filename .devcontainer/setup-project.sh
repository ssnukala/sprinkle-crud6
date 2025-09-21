#!/bin/bash

# UserFrosting CRUD6 Sprinkle Development Setup Script
# This script sets up a complete development environment for CRUD6 sprinkle development

set -e  # Exit on any error

echo "🚀 Setting up UserFrosting CRUD6 development environment..."

# Environment variables
PACKAGE_DIR=${PACKAGE_DIR:-"/workspace/userfrosting"}
SPRINKLE_DIR=${SPRINKLE_DIR:-"/workspace"}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_step() {
    echo -e "${GREEN}[STEP]${NC} $1"
}

print_info() {
    echo -e "${YELLOW}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[====]${NC} $1"
}

print_header "Verifying environment..."
node --version
npm --version
php --version
composer --version

print_step "Checking if UserFrosting project exists..."

# Create UserFrosting 6 project if it doesn't exist
if [ ! -d "$PACKAGE_DIR" ]; then
    print_step "Creating new UserFrosting 6 project at $PACKAGE_DIR..."
    composer create-project userfrosting/userfrosting "$PACKAGE_DIR" "^6.0-beta" --no-interaction
    
    # Set proper permissions
    sudo chown -R vscode:vscode "$PACKAGE_DIR"
    
    print_info "UserFrosting 6 project created successfully!"
else
    print_info "UserFrosting project already exists at $PACKAGE_DIR"
fi

print_step "Configuring UserFrosting project for CRUD6 sprinkle development..."

cd "$PACKAGE_DIR"

# Backup original composer.json and package.json if they exist
if [ -f "composer.json" ]; then
    cp composer.json composer.json.backup
fi

if [ -f "package.json" ]; then
    cp package.json package.json.backup
fi

print_step "Adding CRUD6 sprinkle dependencies to composer.json..."

# Add the CRUD6 sprinkle as a local path dependency
composer config repositories.crud6-sprinkle path "$SPRINKLE_DIR"

# Add the sprinkle dependency
composer require "ssnukala/sprinkle-crud6:*" --no-update

# Ensure minimum-stability and prefer-stable are set
composer config minimum-stability beta
composer config prefer-stable true

print_step "Installing PHP dependencies..."
composer install --no-interaction

print_step "Adding CRUD6 sprinkle dependencies to package.json..."

# Check if package.json exists, if not create a basic one
if [ ! -f "package.json" ]; then
    npm init -y
fi

# Add CRUD6 sprinkle to package.json dependencies
npm pkg set dependencies.@ssnukala/sprinkle-crud6="file:$SPRINKLE_DIR"

# Add other required dependencies for UserFrosting development
npm pkg set devDependencies.@userfrosting/sprinkle-admin="^6.0.0-beta"
npm pkg set devDependencies.@userfrosting/sprinkle-core="^6.0.0-beta"
npm pkg set devDependencies.axios="^1.12.0"
npm pkg set devDependencies.limax="^4.1.0"
npm pkg set devDependencies.pinia="^2.1.6"
npm pkg set devDependencies."pinia-plugin-persistedstate"="^3.2.0"
npm pkg set devDependencies.vue="^3.4.21"
npm pkg set devDependencies."vue-router"="^4.2.4"

print_step "Installing JavaScript dependencies..."
npm install

print_step "Setting up development environment..."

# Create necessary directories
mkdir -p app/logs
mkdir -p app/cache
mkdir -p app/sessions

# Set proper permissions for UserFrosting directories
sudo chown -R vscode:vscode app/logs app/cache app/sessions 2>/dev/null || true
chmod -R 775 app/logs app/cache app/sessions 2>/dev/null || true

print_step "Creating symbolic links for CRUD6 sprinkle development..."

# Create a symbolic link to make development easier
if [ ! -L "app/sprinkles/crud6" ]; then
    mkdir -p app/sprinkles
    ln -sf "$SPRINKLE_DIR" app/sprinkles/crud6
    print_info "Created symbolic link: app/sprinkles/crud6 -> $SPRINKLE_DIR"
fi

print_step "Setting up database configuration..."
if [ ! -f "app/.env" ]; then
    cat > app/.env << EOF
# Database Configuration for Development
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=userfrosting
DB_USERNAME=userfrosting
DB_PASSWORD=userfrosting

# Debug Configuration
DEBUG=true
CACHE_ENABLED=false

# UserFrosting Configuration
UF_MODE=development
EOF
    print_info "Created development .env file"
fi

print_step "Running initial validations..."

# Validate PHP syntax in CRUD6 sprinkle
cd "$SPRINKLE_DIR"
echo "Validating PHP syntax in CRUD6 sprinkle..."
find app/src -name "*.php" -exec php -l {} \; > /dev/null
print_info "✅ PHP syntax validation passed"

# Validate JSON schemas
echo "Validating JSON schemas..."
for schema_file in app/schema/crud6/*.json examples/*.json; do
    if [ -f "$schema_file" ]; then
        php -r "
            \$file = '$schema_file';
            \$content = file_get_contents(\$file);
            \$json = json_decode(\$content);
            if (\$json === null) {
                echo 'INVALID: ' . \$file . PHP_EOL;
                exit(1);
            } else {
                echo 'VALID: ' . \$file . PHP_EOL;
            }
        "
    fi
done
print_info "✅ JSON schema validation passed"

cd "$PACKAGE_DIR"

print_step "Setup completed successfully! 🎉"
echo ""
print_info "Development Environment Summary:"
print_info "  📁 UserFrosting project: $PACKAGE_DIR"
print_info "  📁 CRUD6 sprinkle source: $SPRINKLE_DIR"
print_info "  🔗 Sprinkle symlink: $PACKAGE_DIR/app/sprinkles/crud6"
print_info "  🐘 PHP version: $(php --version | head -n1)"
print_info "  📦 Composer version: $(composer --version | head -n1)"
print_info "  🟢 Node.js version: $(node --version)"
print_info "  📦 npm version: $(npm --version)"
echo ""
print_info "Next steps:"
print_info "  1. Configure your UserFrosting app to include CRUD6::class in getSprinkles()"
print_info "  2. Run database migrations: composer migrate"
print_info "  3. Start development server: composer serve"
print_info "  4. Start frontend development: npm run dev"
echo ""
print_info "Happy coding! 🚀"