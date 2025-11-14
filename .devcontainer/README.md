# Development Container Setup for CRUD6 Sprinkle

This repository includes a complete development container setup that mirrors the `integration-test.yml` workflow to provide a full UserFrosting 6 development environment with the CRUD6 sprinkle.

> **📚 New to GitHub Codespaces?** See [GITHUB_CODESPACES_GUIDE.md](GITHUB_CODESPACES_GUIDE.md) for official GitHub documentation links and a comprehensive guide to using DevContainers with GitHub Codespaces.

## What's Included

### Full Stack Development Environment
- **PHP 8.2** with UserFrosting 6 beta
- **Node.js 20** for frontend development (installed via multi-stage Docker build from official Node.js image)
- **MySQL 8.0** database
- **VS Code extensions** for PHP, Vue.js, and TypeScript development

> **Note on Node.js Installation**: The container uses a multi-stage Docker build to copy Node.js from the official `node:20-bookworm-slim` image. This approach is more reliable than repository-based installation, especially in GitHub Codespaces and restricted network environments.

> **Note on XDebug**: XDebug has been removed from the default build due to PECL connectivity issues in certain network environments (e.g., GitHub Codespaces). If you need XDebug for debugging, you can install it manually after the container is built:
> ```bash
> sudo pecl install xdebug
> sudo docker-php-ext-enable xdebug
> # Configure XDebug
> echo "xdebug.mode=debug,develop,coverage" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
> echo "xdebug.start_with_request=yes" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
> echo "xdebug.client_host=host.docker.internal" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
> echo "xdebug.client_port=9003" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
> ```

### Development Configuration
```bash
.devcontainer/
├── devcontainer.json          # Main devcontainer config
├── compose.yml                # Multi-container setup with MySQL (Docker Compose v2)
├── Dockerfile                 # Combined PHP + Node.js container
├── setup-project.sh          # Mirrors integration-test.yml workflow
└── README.md                 # This file
```

> **Note**: This repository uses `compose.yml` (Docker Compose v2 naming convention) instead of the legacy `docker-compose.yml`. Both file names work with Docker Compose v2, but `compose.yml` is the recommended standard for new projects.

## Architecture Overview

The devcontainer setup mirrors the integration test workflow with enhanced flexibility for sprinkle development:

1. **Creating UserFrosting 6 project at `/workspace`** - The main working directory
2. **Cloning sprinkle-crud6 from GitHub to `/ssnukala/sprinkle-crud6`** - Editable clone for development
3. **Cloning sprinkle-c6admin from GitHub to `/ssnukala/sprinkle-c6admin`** - Editable clone for development (if available)
4. **Mounting sprinkle repository at `/repos/sprinkle-crud6`** - Read-only reference to current branch
5. **Configuring as local path repositories** in composer.json and package.json pointing to `/ssnukala/`
6. **Setting up the complete UserFrosting application** with all configurations (MyApp.php, main.ts, router/index.ts)

This approach allows you to:
- Work in a fully configured UserFrosting 6 instance at `/workspace`
- Edit sprinkle source code at `/ssnukala/sprinkle-crud6` and `/ssnukala/sprinkle-c6admin`
- Test changes immediately without rebuilding (local path dependencies)
- Pull latest changes from GitHub or make local modifications
- Have a reference copy at `/repos/sprinkle-crud6` (current branch you're working on)

**Key Difference from Previous Setup:**
- **OLD**: Workspace was the sprinkle repo, UserFrosting was created inside at `/workspace/userfrosting`
- **NEW**: Workspace IS the UserFrosting project at `/workspace`, sprinkle repo is mounted at `/repos/sprinkle-crud6`
- This matches the integration-test.yml workflow where UserFrosting is created first, then the sprinkle is added

## Quick Start

> **💡 Tip**: For detailed GitHub Codespaces instructions and troubleshooting, see [GITHUB_CODESPACES_GUIDE.md](GITHUB_CODESPACES_GUIDE.md).

### Prerequisites
- **VS Code** with "Dev Containers" extension
- **Docker Desktop** or **Docker Engine**

### Setup Process

1. **Open in VS Code Dev Container**
   - Open this repository in VS Code
   - When prompted, click "Reopen in Container"
   - Or use Command Palette: "Dev Containers: Reopen in Container"

2. **Automatic Setup** (mirrors integration-test.yml with development enhancements)
   - The container will automatically run `setup-project.sh`
   - **Clones sprinkle-crud6 from GitHub main branch to `/ssnukala/sprinkle-crud6`**
   - **Clones sprinkle-c6admin from GitHub main branch to `/ssnukala/sprinkle-c6admin` (if available)**
   - Creates UserFrosting 6 project at `/workspace`
   - Configures composer.json with local repositories pointing to `/ssnukala/`
   - Packages sprinkles from `/ssnukala/` as NPM packages and installs them
   - Modifies MyApp.php to include CRUD6 and C6Admin (if available)
   - Modifies router/index.ts to include routes from both sprinkles
   - Modifies main.ts to include both sprinkles
   - Creates groups.json schema
   - Sets up .env file
   - **Waits for MySQL to be ready (up to 120 seconds)**
   - **Runs database migrations**
   - **Seeds database with Account sprinkle data:**
     - DefaultGroups
     - DefaultPermissions
     - DefaultRoles
     - UpdatePermissions
   - **Seeds database with CRUD6 sprinkle data:**
     - DefaultRoles (crud6-admin role)
     - DefaultPermissions (6 CRUD6 permissions)
   - **Verifies database seeding (counts tables, groups, permissions, roles)**
   - **Creates admin user (username: admin, password: admin123)**
   - **Verifies admin user creation**
   - Builds frontend assets

3. **Start Development**
   ```bash
   # In container terminal - workspace is /workspace (UserFrosting project)
   php bakery serve                # Start UserFrosting server (port 8080)
   # In a new terminal
   php bakery assets:vite         # Start Vite dev server (port 5173)
   ```

## Development Workflow

### File Structure After Setup

```
/workspace/                          # UserFrosting 6 project (main working directory)
├── app/
│   ├── src/MyApp.php               # Configured with CRUD6::class and C6Admin::class
│   ├── assets/
│   │   ├── router/index.ts         # Configured with CRUD6Routes and C6AdminRoutes
│   │   └── main.ts                 # Configured with CRUD6Sprinkle and C6AdminSprinkle
│   ├── schema/crud6/
│   │   └── groups.json             # Example schema
│   └── .env                        # Database configuration
├── vendor/
│   └── ssnukala/
│       ├── sprinkle-crud6/         # Path dependency → /ssnukala/sprinkle-crud6
│       └── sprinkle-c6admin/       # Path dependency → /ssnukala/sprinkle-c6admin
├── node_modules/
│   └── @ssnukala/
│       ├── sprinkle-crud6/         # NPM package from /ssnukala/sprinkle-crud6
│       └── sprinkle-c6admin/       # NPM package from /ssnukala/sprinkle-c6admin
└── composer.json                   # Configured with local repositories

/ssnukala/                          # Cloned sprinkles for development
├── sprinkle-crud6/                 # Clone from GitHub main branch (editable)
│   ├── app/src/                    # Edit source code here
│   ├── composer.json
│   └── package.json
└── sprinkle-c6admin/               # Clone from GitHub main branch (editable, if available)
    ├── app/src/                    # Edit source code here
    ├── composer.json
    └── package.json

/repos/sprinkle-crud6/              # This repository (mounted as reference)
├── app/                            # CRUD6 Sprinkle source
├── .devcontainer/                  # DevContainer configuration
├── composer.json
└── package.json
```

### Working with Sprinkle Source Code

**Important:** The workspace folder is set to `/workspace` in the devcontainer, which is the UserFrosting 6 project.

The sprinkles are cloned from GitHub to `/ssnukala/` for development. Changes to files in `/ssnukala/sprinkle-crud6` or `/ssnukala/sprinkle-c6admin` are immediately available in the UserFrosting application via the local path dependencies.

**Development Workflow:**
1. **Edit sprinkle source**: Modify files in `/ssnukala/sprinkle-crud6/app/src/` or `/ssnukala/sprinkle-c6admin/app/src/`
2. **Changes are live**: The local path dependencies mean changes are immediately available
3. **Test in UserFrosting**: The running application at `/workspace` uses the updated code
4. **Git operations**: Work in `/ssnukala/sprinkle-crud6` or `/ssnukala/sprinkle-c6admin` to commit, push, pull changes
5. **Reference copy**: `/repos/sprinkle-crud6` contains the current branch you're working on (read-only)

**Note:** This structure gives you flexibility to:
- Pull latest changes from GitHub main branches
- Make and test local modifications
- Commit and push changes to GitHub
- Work on both sprinkles simultaneously in the same environment

### Available Commands

#### In UserFrosting project (`/workspace` - default working directory):
```bash
php bakery serve               # Start development server (port 8080)
php bakery assets:vite        # Start Vite dev server (port 5173)
php bakery migrate --force    # Run database migrations
php bakery bake               # Build frontend assets
php bakery route:list         # View all routes
php bakery clear:cache        # Clear cache

# Database seeding (already done during setup)
php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups --force
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force

# Create additional admin users
php bakery create:admin-user --username=test --password=test123 --email=test@example.com --firstName=Test --lastName=User
```

#### In Sprinkle source directories:
```bash
# For sprinkle-crud6
cd /ssnukala/sprinkle-crud6

# Development and testing
composer install               # Install dependencies
find app/src -name "*.php" -exec php -l {} \;  # Validate PHP syntax
vendor/bin/phpunit            # Run tests
vendor/bin/php-cs-fixer fix   # Format code
vendor/bin/phpstan analyse    # Static analysis

# Git operations
git status
git add .
git commit -m "Your message"
git push origin main

# For sprinkle-c6admin (if available)
cd /ssnukala/sprinkle-c6admin
# Same commands as above
```

### Port Mapping

| Service | Port | Description |
|---------|------|-------------|
| UserFrosting | 8080 | Main application server (http://localhost:8080) |
| Vite Dev Server | 5173 | Frontend development assets |
| MySQL | 3306 | Database server |

> **Note**: Port 9003 (XDebug) is not forwarded by default since XDebug is not pre-installed. If you install XDebug manually, you can add port forwarding in your local devcontainer configuration.

### VS Code Integration

The devcontainer includes these VS Code extensions:
- **PHP**: Intelephense, PHP CS Fixer, PHPStan
- **Vue.js**: Volar, TypeScript Vue Plugin
- **General**: Prettier, Tailwind CSS, Twig, GitHub Copilot

> **Note**: The XDebug extension has been removed from the default setup. If you install XDebug manually, you can add the `xdebug.php-debug` extension to your local VS Code settings.

### Database Configuration

MySQL is automatically configured with:
- Host: `mysql`
- Database: `userfrosting`
- Username: `userfrosting`
- Password: `userfrosting`
- Root password: `userfrosting`

Admin user created during setup:
- Username: `admin`
- Password: `admin123`
- Email: `admin@example.com`

**Database Initialization:**
- Migrations run automatically during setup
- Database seeded with default data:
  - Groups (from Account sprinkle)
  - Permissions (from Account + CRUD6 sprinkles)
  - Roles (from Account + CRUD6 sprinkles, including `crud6-admin`)
  - Admin user created
- Setup script verifies seeding was successful
- If MySQL is not ready, setup provides manual commands to run

## Integration Test Mirroring

The `setup-project.sh` script mirrors the steps from `.github/workflows/integration-test.yml`:

| Integration Test Step | DevContainer Implementation |
|----------------------|---------------------------|
| Checkout sprinkle-crud6 | Mounted at `/repos/sprinkle-crud6` |
| Setup PHP/Node | Included in Dockerfile |
| Create UserFrosting project | `composer create-project` at `/workspace` |
| Configure Composer | Local path repository to `/repos/sprinkle-crud6` |
| Install PHP dependencies | `composer install` in setup script |
| Package sprinkle-crud6 for NPM | `npm pack` from `/repos/sprinkle-crud6` |
| Install NPM dependencies | `npm install` with packaged sprinkle |
| Configure MyApp.php | `sed` commands to add CRUD6::class |
| Configure router/index.ts | `sed` commands to add CRUD6Routes |
| Configure main.ts | `sed` commands to add CRUD6Sprinkle |
| Create groups schema | Schema file created in setup script |
| Setup environment | .env file created with database config |
| Run migrations | `php bakery migrate --force` with error handling |
| Seed database | Account + CRUD6 seeds with error logging |
| Verify seeding | Check table counts, verify data exists |
| Create admin user | `php bakery create:admin-user` with verification |
| Build assets | `php bakery bake` |

## Testing CRUD6 Features

### Access the Application
1. Start the servers (if not already running):
   ```bash
   php bakery serve
   php bakery assets:vite  # in another terminal
   ```
2. Open browser to http://localhost:8080
3. Login with admin / admin123

### API Endpoints (require authentication)
- `GET /api/crud6/groups` - List groups with pagination/filtering
- `GET /api/crud6/groups/1` - Get single group
- `POST /api/crud6/groups` - Create group
- `PUT /api/crud6/groups/1` - Update group
- `DELETE /api/crud6/groups/1` - Delete group

### Frontend Routes
- `/crud6/groups` - Groups list page
- `/crud6/groups/1` - Group detail page

### Testing with curl
```bash
# Get groups list (will return 401 without authentication)
curl http://localhost:8080/api/crud6/groups

# After implementing authentication, use session cookies
curl -c cookies.txt -b cookies.txt http://localhost:8080/api/crud6/groups
```

## Schema Development

Create JSON schemas in `/workspace/app/schema/crud6/`:

```json
{
  "model": "products",
  "title": "Product Management",
  "table": "products",
  "primary_key": "id",
  "timestamps": true,
  "permissions": {
    "read": "uri_products",
    "create": "create_product",
    "update": "update_product_field",
    "delete": "delete_product"
  },
  "fields": {
    "id": { "type": "integer", "label": "ID", "readonly": true, "sortable": true },
    "name": { "type": "string", "label": "Name", "required": true, "sortable": true },
    "price": { "type": "decimal", "label": "Price", "required": true }
  }
}
```

## Troubleshooting

### Common Issues

1. **MySQL Not Ready During Setup**
   - The setup script waits up to 120 seconds for MySQL
   - If migrations fail, the script will show manual commands to run
   - To complete setup manually:
     ```bash
     php bakery migrate --force
     php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultGroups --force
     php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultPermissions --force
     php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\DefaultRoles --force
     php bakery seed UserFrosting\\Sprinkle\\Account\\Database\\Seeds\\UpdatePermissions --force
     php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultRoles --force
     php bakery seed UserFrosting\\Sprinkle\\CRUD6\\Database\\Seeds\\DefaultPermissions --force
     php bakery create:admin-user --username=admin --password=admin123 --email=admin@example.com --firstName=Admin --lastName=User
     ```

2. **Changes to Sprinkle Not Reflected**
   - Edit files in `/ssnukala/sprinkle-crud6/app/src/` or `/ssnukala/sprinkle-c6admin/app/src/`
   - Changes are automatically available via local path dependency
   - Run `composer dump-autoload` in `/workspace` if needed
   - For frontend changes, restart Vite: `php bakery assets:vite`

3. **Permission Issues**
   ```bash
   sudo chown -R vscode:vscode /workspace /ssnukala /repos/sprinkle-crud6
   ```

4. **Rebuild Setup**
   ```bash
   # Re-run setup script manually
   bash /repos/sprinkle-crud6/.devcontainer/setup-project.sh
   ```

### Rebuilding Container

If you need to rebuild the development container:

1. In VS Code: Command Palette → "Dev Containers: Rebuild Container"
2. Or manually:
   ```bash
   docker-compose down -v  # Remove volumes for fresh start
   docker-compose build --no-cache
   ```

### Logs and Debugging

```bash
# View container logs
docker-compose logs sprinkle-crud6
docker-compose logs mysql

# View UserFrosting logs
tail -f /workspace/app/logs/userfrosting.log

# Test database connection
mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SHOW TABLES;"
```

### Manual Validation

```bash
# Validate PHP syntax
find /ssnukala/sprinkle-crud6/app/src -name "*.php" -exec php -l {} \;
find /ssnukala/sprinkle-c6admin/app/src -name "*.php" -exec php -l {} \; 2>/dev/null || true

# Validate JSON schemas
for file in /workspace/app/schema/crud6/*.json; do
  php -r "echo json_decode(file_get_contents('$file')) ? '$file valid' : '$file invalid'; echo PHP_EOL;"
done

# Test UserFrosting
cd /workspace
php bakery debug:container
php bakery route:list
```

## Development Tips

### Hot Reload During Development
- PHP changes require server restart: `Ctrl+C` then `php bakery serve`
- Frontend changes auto-reload via Vite dev server
- Schema changes may require cache clear: `php bakery clear:cache`

### Debugging with XDebug (Optional)

XDebug is not pre-installed but can be added manually if needed:

1. Install XDebug in the container:
   ```bash
   sudo pecl install xdebug
   sudo docker-php-ext-enable xdebug
   ```

2. Configure XDebug:
   ```bash
   echo "xdebug.mode=debug,develop,coverage" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
   echo "xdebug.start_with_request=yes" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
   echo "xdebug.client_host=host.docker.internal" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
   echo "xdebug.client_port=9003" | sudo tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
   ```

3. Install the XDebug VS Code extension: `xdebug.php-debug`

4. Set breakpoints in VS Code and start listening for XDebug (F5 or Debug panel)

5. Add port 9003 forwarding if needed

### Running Tests
```bash
# CRUD6 sprinkle tests
cd /ssnukala/sprinkle-crud6
vendor/bin/phpunit

# C6Admin sprinkle tests (if available)
cd /ssnukala/sprinkle-c6admin
vendor/bin/phpunit

# UserFrosting tests
cd /workspace
vendor/bin/phpunit
```

### Working with Multiple Sprinkles
- Edit CRUD6 sprinkle: `/ssnukala/sprinkle-crud6` (cloned from GitHub main)
- Edit C6Admin sprinkle: `/ssnukala/sprinkle-c6admin` (cloned from GitHub main, if available)
- Both are installed as local path dependencies in UserFrosting via composer and npm
- Changes to sprinkle source files are immediately available in the UserFrosting application
- You can pull latest changes, make modifications, and push to GitHub
- Reference copy: `/repos/sprinkle-crud6` (current working branch)

## Contributing

When working on sprinkle development:

1. Make changes to source files in `/repos/sprinkle-crud6/app/src/`
2. Changes are immediately available in the UserFrosting application (local path dependency)
3. Add/update tests in `/repos/sprinkle-crud6/app/tests/`
4. Update schemas in `/repos/sprinkle-crud6/app/schema/`
5. Test changes in UserFrosting project at `/workspace`
6. Run validation commands before committing
7. Commit and push changes from `/repos/sprinkle-crud6`

The development container provides a complete environment that mirrors the integration test workflow, ensuring your local development matches the CI/CD pipeline.
