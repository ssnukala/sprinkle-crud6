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

The devcontainer setup mirrors the integration test workflow by:

1. **Creating UserFrosting 6 project at `/workspace`** - The main working directory
2. **Mounting sprinkle-crud6 repository at `/repos/sprinkle-crud6`** - The source repository (read-only development)
3. **Configuring as a local path repository** in composer.json and package.json
4. **Setting up the complete UserFrosting application** with all configurations (MyApp.php, main.ts, router/index.ts)

This approach allows you to:
- Work in a fully configured UserFrosting 6 instance at `/workspace`
- Reference the sprinkle source code at `/repos/sprinkle-crud6`
- Develop and test in a complete environment that matches the integration test workflow
- Make changes to sprinkle code and see them immediately in the running application

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

2. **Automatic Setup** (mirrors integration-test.yml)
   - The container will automatically run `setup-project.sh`
   - Creates UserFrosting 6 project at `/workspace`
   - Configures composer.json with local repository pointing to `/repos/sprinkle-crud6`
   - Packages sprinkle-crud6 as NPM package and installs it
   - Modifies MyApp.php to include CRUD6
   - Modifies router/index.ts to include CRUD6 routes
   - Modifies main.ts to include CRUD6 sprinkle
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
│   ├── src/MyApp.php               # Configured with CRUD6::class
│   ├── assets/
│   │   ├── router/index.ts         # Configured with CRUD6Routes
│   │   └── main.ts                 # Configured with CRUD6Sprinkle
│   ├── schema/crud6/
│   │   └── groups.json             # Example schema
│   └── .env                        # Database configuration
├── vendor/
│   └── ssnukala/
│       └── sprinkle-crud6/         # Path dependency → /repos/sprinkle-crud6
├── node_modules/
│   └── @ssnukala/
│       └── sprinkle-crud6/         # NPM package from local path
└── composer.json                   # Configured with local repository

/repos/sprinkle-crud6/              # This repository (mounted as read-only reference)
├── app/                            # CRUD6 Sprinkle source
├── .devcontainer/                  # DevContainer configuration
├── composer.json
└── package.json
```

### Working with Sprinkle Source Code

**Important:** The workspace folder is set to `/workspace` in the devcontainer, which is the UserFrosting 6 project.

The sprinkle source repository is mounted at `/repos/sprinkle-crud6`. Changes to files in `/repos/sprinkle-crud6` are immediately available in the UserFrosting application via the local path dependency.

**Development Workflow:**
1. **Edit sprinkle source**: Modify files in `/repos/sprinkle-crud6/app/src/`
2. **Changes are live**: The local path dependency means changes are immediately available
3. **Test in UserFrosting**: The running application at `/workspace` uses the updated code
4. **Commit from sprinkle repo**: Navigate to `/repos/sprinkle-crud6` to commit changes

**Note:** This structure matches the integration-test.yml workflow where the UserFrosting project is the primary working directory.

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

#### In Sprinkle source directory (`/repos/sprinkle-crud6`):
```bash
cd /repos/sprinkle-crud6

# Development and testing
composer install               # Install dependencies
find app/src -name "*.php" -exec php -l {} \;  # Validate PHP syntax
vendor/bin/phpunit            # Run tests
vendor/bin/php-cs-fixer fix   # Format code
vendor/bin/phpstan analyse    # Static analysis
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
   - Edit files in `/repos/sprinkle-crud6/app/src/`
   - Changes are automatically available via local path dependency
   - Run `composer dump-autoload` in `/workspace` if needed

3. **Permission Issues**
   ```bash
   sudo chown -R vscode:vscode /workspace /repos/sprinkle-crud6
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
find /repos/sprinkle-crud6/app/src -name "*.php" -exec php -l {} \;

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
cd /repos/sprinkle-crud6
vendor/bin/phpunit

# UserFrosting tests
cd /workspace
vendor/bin/phpunit
```

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
