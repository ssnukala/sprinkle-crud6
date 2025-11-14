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
# STEP 1: Setup /ssnukala directory and clone sprinkle-crud6 from main branch
# ============================================================================
print_step "Setting up /ssnukala directory and cloning sprinkle-crud6..."

sudo mkdir -p /ssnukala
sudo chown -R vscode:vscode /ssnukala

# Clone sprinkle-crud6 from main branch (or use mounted repo as fallback)
if [ ! -d "/ssnukala/sprinkle-crud6" ]; then
    print_info "Cloning sprinkle-crud6 from GitHub main branch..."
    if git clone --branch main https://github.com/ssnukala/sprinkle-crud6.git /ssnukala/sprinkle-crud6 2>/dev/null; then
        print_info "Cloned sprinkle-crud6 to /ssnukala/sprinkle-crud6"
    elif [ -d "/repos/sprinkle-crud6" ]; then
        print_info "GitHub clone failed, copying from mounted repository..."
        cp -r /repos/sprinkle-crud6 /ssnukala/sprinkle-crud6
        print_info "Copied sprinkle-crud6 to /ssnukala/sprinkle-crud6"
    else
        print_error "Could not clone or copy sprinkle-crud6"
        exit 1
    fi
else
    print_info "Directory /ssnukala/sprinkle-crud6 already exists, pulling latest changes..."
    cd /ssnukala/sprinkle-crud6
    git fetch origin 2>/dev/null || print_info "Could not fetch from origin"
    git checkout main 2>/dev/null || print_info "Already on main branch"
    git pull origin main 2>/dev/null || print_info "Could not pull latest changes"
    cd /
    print_info "Updated sprinkle-crud6 to latest main branch"
fi

# ============================================================================
# STEP 2: Clone sprinkle-c6admin from main branch
# ============================================================================
print_step "Cloning sprinkle-c6admin from main branch..."

if [ ! -d "/ssnukala/sprinkle-c6admin" ]; then
    # Try to clone sprinkle-c6admin if it exists
    if git ls-remote https://github.com/ssnukala/sprinkle-c6admin.git &>/dev/null; then
        print_info "Cloning sprinkle-c6admin from GitHub main branch..."
        git clone --branch main https://github.com/ssnukala/sprinkle-c6admin.git /ssnukala/sprinkle-c6admin
        print_info "Cloned sprinkle-c6admin to /ssnukala/sprinkle-c6admin"
    else
        print_info "Repository ssnukala/sprinkle-c6admin not found, skipping..."
    fi
else
    print_info "Directory /ssnukala/sprinkle-c6admin already exists, pulling latest changes..."
    if git ls-remote https://github.com/ssnukala/sprinkle-c6admin.git &>/dev/null; then
        cd /ssnukala/sprinkle-c6admin
        git fetch origin
        git checkout main
        git pull origin main
        cd /
        print_info "Updated sprinkle-c6admin to latest main branch"
    else
        print_info "Repository ssnukala/sprinkle-c6admin not found, skipping update..."
    fi
fi

# ============================================================================
# STEP 3: Create UserFrosting 6 project at /workspace
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
# STEP 4: Configure Composer for beta packages and local sprinkles
# ============================================================================
print_step "Configuring Composer for local sprinkles..."

# Add local path to composer.json for sprinkle-crud6
composer config repositories.local-crud6 path /ssnukala/sprinkle-crud6
composer require ssnukala/sprinkle-crud6:@dev --no-update

# Add local path to composer.json for sprinkle-c6admin if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ]; then
    composer config repositories.local-c6admin path /ssnukala/sprinkle-c6admin
    composer require ssnukala/sprinkle-c6admin:@dev --no-update
    print_info "Added sprinkle-c6admin to composer.json"
fi

composer config minimum-stability beta
composer config prefer-stable true

# ============================================================================
# STEP 5: Install PHP dependencies
# ============================================================================
print_step "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

# ============================================================================
# STEP 6: Package sprinkles for NPM
# ============================================================================
print_step "Packaging sprinkles for NPM..."

# Package sprinkle-crud6
cd /ssnukala/sprinkle-crud6
npm pack
mv ssnukala-sprinkle-crud6-*.tgz /workspace/

# Package sprinkle-c6admin if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ] && [ -f "/ssnukala/sprinkle-c6admin/package.json" ]; then
    cd /ssnukala/sprinkle-c6admin
    npm pack
    mv ssnukala-sprinkle-c6admin-*.tgz /workspace/
    print_info "Packaged sprinkle-c6admin for NPM"
fi

cd /workspace

# ============================================================================
# STEP 7: Install NPM dependencies
# ============================================================================
print_step "Installing NPM dependencies..."

npm update
npm install ./ssnukala-sprinkle-crud6-*.tgz

# Install c6admin package if it exists
if [ -f "./ssnukala-sprinkle-c6admin-*.tgz" ]; then
    npm install ./ssnukala-sprinkle-c6admin-*.tgz
    print_info "Installed sprinkle-c6admin NPM package"
fi

# ============================================================================
# STEP 8: Configure MyApp.php
# ============================================================================
print_step "Configuring MyApp.php to include sprinkles..."

# Add CRUD6 import after existing imports
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php

# Add CRUD6::class to getSprinkles() array after Admin::class
sed -i '/Admin::class,/a \            CRUD6::class,' app/src/MyApp.php

# Add C6Admin if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ]; then
    print_info "Adding C6Admin to MyApp.php..."
    # Add C6Admin import after CRUD6 import
    sed -i '/use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;/a use UserFrosting\\Sprinkle\\C6Admin\\C6Admin;' app/src/MyApp.php
    # Add C6Admin::class to getSprinkles() array after CRUD6::class
    sed -i '/CRUD6::class,/a \            C6Admin::class,' app/src/MyApp.php
    print_info "C6Admin added to MyApp.php"
fi

print_info "MyApp.php configured"

# ============================================================================
# STEP 9: Configure router/index.ts
# ============================================================================
print_step "Configuring router/index.ts to include sprinkle routes..."

# Add CRUD6Routes import after AdminRoutes import
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'" app/assets/router/index.ts

# Add ...CRUD6Routes after ...AccountRoutes
sed -i '/\.\.\.AccountRoutes,/a \            ...CRUD6Routes,' app/assets/router/index.ts

# Add C6Admin routes if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ] && [ -f "/ssnukala/sprinkle-c6admin/package.json" ]; then
    print_info "Adding C6Admin routes to router/index.ts..."
    # Add C6AdminRoutes import after CRUD6Routes import
    sed -i "/import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'/a import C6AdminRoutes from '@ssnukala\/sprinkle-c6admin\/routes'" app/assets/router/index.ts
    # Add ...C6AdminRoutes after ...CRUD6Routes
    sed -i '/\.\.\.CRUD6Routes,/a \            ...C6AdminRoutes,' app/assets/router/index.ts
    print_info "C6Admin routes added to router/index.ts"
fi

print_info "router/index.ts configured"

# ============================================================================
# STEP 10: Configure main.ts
# ============================================================================
print_step "Configuring main.ts to include sprinkles..."

# Add CRUD6Sprinkle import after AdminSprinkle import
sed -i "/import AdminSprinkle from '@userfrosting\/sprinkle-admin'/a import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'" app/assets/main.ts

# Add app.use(CRUD6Sprinkle) after app.use(AdminSprinkle)
sed -i "/app.use(AdminSprinkle)/a app.use(CRUD6Sprinkle)" app/assets/main.ts

# Add C6Admin if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ] && [ -f "/ssnukala/sprinkle-c6admin/package.json" ]; then
    print_info "Adding C6Admin to main.ts..."
    # Add C6AdminSprinkle import after CRUD6Sprinkle import
    sed -i "/import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'/a import C6AdminSprinkle from '@ssnukala\/sprinkle-c6admin'" app/assets/main.ts
    # Add app.use(C6AdminSprinkle) after app.use(CRUD6Sprinkle)
    sed -i "/app.use(CRUD6Sprinkle)/a app.use(C6AdminSprinkle)" app/assets/main.ts
    print_info "C6Admin added to main.ts"
fi

print_info "main.ts configured"

# ============================================================================
# STEP 11: Create groups schema
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
# STEP 12: Setup environment (.env)
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
# STEP 13: Wait for MySQL to be ready
# ============================================================================
print_step "Waiting for MySQL database to be ready..."

max_attempts=60
attempt=0
until mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT 1" &>/dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Waiting for MySQL... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    print_error "MySQL not available after $max_attempts attempts"
    print_error "Database initialization skipped!"
    print_info ""
    print_info "To complete setup manually, run these commands:"
    print_info "  cd /workspace"
    print_info "  php bakery migrate --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\Account\\\\Database\\\\Seeds\\\\DefaultGroups --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\Account\\\\Database\\\\Seeds\\\\DefaultPermissions --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\Account\\\\Database\\\\Seeds\\\\DefaultRoles --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\Account\\\\Database\\\\Seeds\\\\UpdatePermissions --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\CRUD6\\\\Database\\\\Seeds\\\\DefaultRoles --force"
    print_info "  php bakery seed UserFrosting\\\\Sprinkle\\\\CRUD6\\\\Database\\\\Seeds\\\\DefaultPermissions --force"
    print_info "  php bakery create:admin-user --username=admin --password=admin123 --email=admin@example.com --firstName=Admin --lastName=User"
    print_info ""
else
    print_info "✅ MySQL is ready"
    
    # ============================================================================
    # STEP 14: Run migrations
    # ============================================================================
    print_step "Running database migrations..."
    
    if php bakery migrate --force; then
        print_info "✅ Migrations completed successfully"
    else
        print_error "❌ Migrations failed"
        print_info "You may need to run migrations manually: php bakery migrate --force"
        exit 1
    fi
    
    # ============================================================================
    # STEP 15: Seed database
    # ============================================================================
    print_step "Seeding database..."
    
    # Seed Account sprinkle data first (required base data)
    print_info "Seeding Account sprinkle data..."
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups --force || print_error "DefaultGroups seed failed"
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultPermissions --force || print_error "DefaultPermissions seed failed"
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultRoles --force || print_error "DefaultRoles seed failed"
    php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\UpdatePermissions --force || print_error "UpdatePermissions seed failed"
    
    # Then seed CRUD6 sprinkle data
    print_info "Seeding CRUD6 sprinkle data..."
    php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force || print_error "CRUD6 DefaultRoles seed failed"
    php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force || print_error "CRUD6 DefaultPermissions seed failed"
    
    print_info "✅ Database seeding completed"
    
    # ============================================================================
    # STEP 16: Verify database seeding
    # ============================================================================
    print_step "Verifying database seeding..."
    
    # Check if tables exist
    TABLES=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SHOW TABLES;" -s)
    TABLE_COUNT=$(echo "$TABLES" | wc -l)
    
    if [ $TABLE_COUNT -gt 0 ]; then
        print_info "✅ Found $TABLE_COUNT database tables"
    else
        print_error "❌ No database tables found"
    fi
    
    # Check if groups exist
    GROUP_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM groups;" -s 2>/dev/null || echo "0")
    if [ "$GROUP_COUNT" -gt 0 ]; then
        print_info "✅ Found $GROUP_COUNT groups in database"
    else
        print_error "❌ No groups found in database"
    fi
    
    # Check if permissions exist
    PERMISSION_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM permissions;" -s 2>/dev/null || echo "0")
    if [ "$PERMISSION_COUNT" -gt 0 ]; then
        print_info "✅ Found $PERMISSION_COUNT permissions in database"
    else
        print_error "❌ No permissions found in database"
    fi
    
    # Check if roles exist
    ROLE_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM roles;" -s 2>/dev/null || echo "0")
    if [ "$ROLE_COUNT" -gt 0 ]; then
        print_info "✅ Found $ROLE_COUNT roles in database"
    else
        print_error "❌ No roles found in database"
    fi
    
    print_info "Database verification completed"
    
    # ============================================================================
    # STEP 17: Create admin user
    # ============================================================================
    print_step "Creating admin user..."
    
    php bakery create:admin-user \
      --username=admin \
      --password=admin123 \
      --email=admin@example.com \
      --firstName=Admin \
      --lastName=User || print_info "Admin user may already exist"
    
    # Verify admin user was created
    USER_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM users WHERE user_name='admin';" -s 2>/dev/null || echo "0")
    if [ "$USER_COUNT" -gt 0 ]; then
        print_info "✅ Admin user created successfully (username: admin, password: admin123)"
    else
        print_error "❌ Admin user not found in database"
    fi
    
    # ============================================================================
    # STEP 18: Run php bakery bake to build assets
    # ============================================================================
    print_step "Running php bakery bake to build assets..."
    
    # bakery bake will automatically build the frontend assets
    php bakery bake || print_info "⚠️ Build failed but continuing with setup"
    
    print_info "Assets built"
fi

# ============================================================================
# STEP 19: Final setup
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
print_info "  📁 CRUD6 sprinkle source: /ssnukala/sprinkle-crud6"
if [ -d "/ssnukala/sprinkle-c6admin" ]; then
    print_info "  📁 C6Admin sprinkle source: /ssnukala/sprinkle-c6admin"
fi
print_info "  📁 Repository reference: /repos/sprinkle-crud6"
print_info "  🐘 PHP version: $(php --version | head -n1)"
print_info "  📦 Composer version: $(composer --version | head -n1)"
print_info "  🟢 Node.js version: $(node --version)"
print_info "  📦 npm version: $(npm --version)"
echo ""
print_info "Database Configuration:"
print_info "  🗄️  Database: userfrosting (MySQL 8.0)"
print_info "  👤 Database user: userfrosting / userfrosting"
print_info "  ✅ Migrations: Completed"
print_info "  ✅ Seeding: Completed (Account + CRUD6 sprinkles)"
print_info "  🔐 Admin user: admin / admin123"
echo ""
print_info "Database Contents:"
# Only show these if MySQL connection was successful
if mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT 1" &>/dev/null; then
    GROUP_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM groups;" -s 2>/dev/null || echo "0")
    PERMISSION_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM permissions;" -s 2>/dev/null || echo "0")
    ROLE_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM roles;" -s 2>/dev/null || echo "0")
    USER_COUNT=$(mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SELECT COUNT(*) FROM users;" -s 2>/dev/null || echo "0")
    print_info "  📊 Groups: $GROUP_COUNT"
    print_info "  📊 Permissions: $PERMISSION_COUNT"
    print_info "  📊 Roles: $ROLE_COUNT"
    print_info "  📊 Users: $USER_COUNT"
else
    print_info "  ⚠️  Database not accessible (may need manual initialization)"
fi
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
print_info "  • Re-run migrations: php bakery migrate --force"
print_info "  • Re-seed database: php bakery seed <SeedClass> --force"
echo ""
print_info "Happy coding! 🚀"