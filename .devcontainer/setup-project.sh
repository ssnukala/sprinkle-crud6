#!/bin/bash

# UserFrosting CRUD6 Sprinkle Development Setup Script
# This script mirrors the integration-test.yml workflow to set up a complete development environment

set -e  # Exit on any error

echo "🚀 Setting up UserFrosting CRUD6 development environment..."
echo "This setup mirrors the integration-test.yml workflow"

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

# ============================================================================
# STEP 1: Create UserFrosting 6 project at /workspace
# ============================================================================
print_step "Creating UserFrosting 6 project at /workspace..."

# Check if /workspace is empty or has only .gitkeep type files
if [ -z "$(ls -A /workspace 2>/dev/null | grep -v '^\.')" ]; then
    # Create UserFrosting project directly in /workspace
    cd /tmp
    composer create-project userfrosting/userfrosting userfrosting-temp "^6.0-beta" --no-scripts --no-install --ignore-platform-reqs
    
    # Move all files from temp directory to /workspace
    sudo mv userfrosting-temp/* /workspace/ 2>/dev/null || true
    sudo mv userfrosting-temp/.* /workspace/ 2>/dev/null || true
    rm -rf userfrosting-temp
    
    sudo chown -R vscode:vscode /workspace
    print_info "UserFrosting 6 project created at /workspace"
else
    print_info "UserFrosting project already exists at /workspace"
fi

cd /workspace

# ============================================================================
# STEP 2: Configure Composer for beta packages and local sprinkle-crud6
# ============================================================================
print_step "Configuring Composer for local sprinkle..."

# Add local path to composer.json for sprinkle-crud6 from /repos/sprinkle-crud6
composer config repositories.local-crud6 path /repos/sprinkle-crud6
composer require ssnukala/sprinkle-crud6:@dev --no-update

composer config minimum-stability beta
composer config prefer-stable true

# ============================================================================
# STEP 3: Install PHP dependencies
# ============================================================================
print_step "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

# ============================================================================
# STEP 4: Package sprinkle-crud6 for NPM
# ============================================================================
print_step "Packaging sprinkle-crud6 for NPM..."

cd /repos/sprinkle-crud6
npm pack
mv ssnukala-sprinkle-crud6-*.tgz /workspace/

cd /workspace

# ============================================================================
# STEP 5: Install NPM dependencies
# ============================================================================
print_step "Installing NPM dependencies..."

npm update
npm install ./ssnukala-sprinkle-crud6-*.tgz

# ============================================================================
# STEP 6: Configure MyApp.php
# ============================================================================
print_step "Configuring MyApp.php to include CRUD6 sprinkle..."

# Add CRUD6 import after existing imports
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php

# Add CRUD6::class to getSprinkles() array after Admin::class
sed -i '/Admin::class,/a \            CRUD6::class,' app/src/MyApp.php

print_info "MyApp.php configured"

# ============================================================================
# STEP 7: Configure router/index.ts
# ============================================================================
print_step "Configuring router/index.ts to include CRUD6 routes..."

# Add CRUD6Routes import after AdminRoutes import
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'" app/assets/router/index.ts

# Add ...CRUD6Routes after ...AccountRoutes
sed -i '/\.\.\.AccountRoutes,/a \            ...CRUD6Routes,' app/assets/router/index.ts

print_info "router/index.ts configured"

# ============================================================================
# STEP 8: Configure main.ts
# ============================================================================
print_step "Configuring main.ts to include CRUD6 sprinkle..."

# Add CRUD6Sprinkle import after AdminSprinkle import
sed -i "/import AdminSprinkle from '@userfrosting\/sprinkle-admin'/a import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'" app/assets/main.ts

# Add app.use(CRUD6Sprinkle) after app.use(AdminSprinkle)
sed -i "/app.use(AdminSprinkle)/a app.use(CRUD6Sprinkle)" app/assets/main.ts

print_info "main.ts configured"

# ============================================================================
# STEP 9: Create groups schema
# ============================================================================
print_step "Creating groups.json schema..."

mkdir -p app/schema/crud6
cat > app/schema/crud6/groups.json << 'EOF'
{
  "model": "groups",
  "title": "Group Management",
  "table": "groups",
  "primary_key": "id",
  "timestamps": true,
  "permissions": {
    "read": "uri_groups",
    "create": "create_group",
    "update": "update_group_field",
    "delete": "delete_group"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID", "readonly": true, "sortable": true },
    "slug": { "type": "string", "label": "Slug", "required": true, "sortable": true },
    "name": { "type": "string", "label": "Name", "required": true, "sortable": true },
    "description": { "type": "text", "label": "Description" }
  }
}
EOF

print_info "groups.json schema created"

# ============================================================================
# STEP 10: Setup environment (.env)
# ============================================================================
print_step "Setting up .env file..."

if [ ! -f "app/.env" ]; then
    # Use .env.example as the base
    cp app/.env.example app/.env
    
    # Update database configuration for Docker environment
    sed -i 's/DB_CONNECTION=.*/DB_CONNECTION="mysql"/' app/.env
    sed -i 's/DB_HOST=.*/DB_HOST="mysql"/' app/.env
    sed -i 's/DB_PORT=.*/DB_PORT="3306"/' app/.env
    sed -i 's/DB_NAME=.*/DB_NAME="userfrosting"/' app/.env
    sed -i 's/DB_USER=.*/DB_USER="userfrosting"/' app/.env
    sed -i 's/DB_PASSWORD=.*/DB_PASSWORD="userfrosting"/' app/.env
    
    # Disable interactive prompts for bakery commands
    echo "" >> app/.env
    echo "# Bakery Configuration" >> app/.env
    echo "BAKERY_CONFIRM_SENSITIVE_COMMAND=false" >> app/.env
    
    print_info ".env file created"
else
    print_info ".env file already exists"
fi

# ============================================================================
# STEP 11: Wait for MySQL to be ready
# ============================================================================
print_step "Waiting for MySQL database to be ready..."

max_attempts=30
attempt=0
until mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT 1" &>/dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Waiting for MySQL... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    print_error "MySQL not available after $max_attempts attempts"
    print_info "Skipping database setup - run migrations manually when MySQL is ready"
else
    print_info "MySQL is ready"
    
    # ============================================================================
    # STEP 12: Run migrations
    # ============================================================================
    print_step "Running database migrations..."
    php bakery migrate --force
    print_info "Migrations completed"
    
    # ============================================================================
    # STEP 13: Seed database
    # ============================================================================
    print_step "Seeding database..."
    
    # Seed Account sprinkle data first (required base data)
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups --force
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultPermissions --force
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultRoles --force
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\UpdatePermissions --force
    
    # Then seed CRUD6 sprinkle data
    php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
    php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force
    
    print_info "Database seeding completed"
    
    # ============================================================================
    # STEP 14: Create admin user
    # ============================================================================
    print_step "Creating admin user..."
    
    php bakery create:admin-user \
      --username=admin \
      --password=admin123 \
      --email=admin@example.com \
      --firstName=Admin \
      --lastName=User || print_info "Admin user may already exist"
    
    print_info "Admin user setup completed (username: admin, password: admin123)"
    
    # ============================================================================
    # STEP 15: Run php bakery bake to build assets and install NPM dependencies
    # ============================================================================
    print_step "Running php bakery bake to build assets..."
    
    # bakery bake will automatically build the frontend assets
    php bakery bake || print_info "⚠️ Build failed but continuing with setup"
    
    print_info "Assets built"
fi

# ============================================================================
# STEP 16: Final setup
# ============================================================================
print_step "Finalizing setup..."

# Create necessary directories
mkdir -p app/logs app/cache app/sessions
chmod -R 775 app/logs app/cache app/sessions 2>/dev/null || true

# ============================================================================
# Setup Complete
# ============================================================================
print_header "✅ Setup completed successfully!"
echo ""
print_info "Development Environment Summary:"
print_info "  📁 UserFrosting project: /workspace (current directory)"
print_info "  📁 CRUD6 sprinkle source: /repos/sprinkle-crud6"
print_info "  🐘 PHP version: $(php --version | head -n1)"
print_info "  📦 Composer version: $(composer --version | head -n1)"
print_info "  🟢 Node.js version: $(node --version)"
print_info "  📦 npm version: $(npm --version)"
echo ""
print_info "Database Configuration:"
print_info "  🗄️  Database: userfrosting (MySQL 8.0)"
print_info "  👤 Username: userfrosting / userfrosting"
print_info "  🔐 Admin user: admin / admin123"
echo ""
print_info "Next steps:"
print_info "  1. Start PHP server: php bakery serve"
print_info "  2. Start Vite dev server (in another terminal): php bakery assets:vite"
print_info "  3. Open browser: http://localhost:8080"
print_info "  4. Login with admin / admin123"
echo ""
print_info "Development Commands:"
print_info "  • Rebuild assets: php bakery bake"
print_info "  • View routes: php bakery route:list"
print_info "  • Clear cache: php bakery clear:cache"
print_info "  • Run tests: vendor/bin/phpunit"
echo ""
print_info "Happy coding! 🚀"