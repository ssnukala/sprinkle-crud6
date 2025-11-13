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
├── docker-compose.yml         # Multi-container setup with MySQL
├── Dockerfile                 # Combined PHP + Node.js container
├── setup-project.sh          # Mirrors integration-test.yml workflow
└── README.md                 # This file
```

## Architecture Overview

The devcontainer setup mirrors the integration test workflow by:

1. **Cloning sprinkle-crud6 from main branch to `/ssnukala/sprinkle-crud6`** - Main CRUD6 sprinkle source from GitHub
2. **Cloning sprinkle-c6admin from main branch to `/ssnukala/sprinkle-c6admin`** - C6Admin sprinkle source from GitHub (if repository exists)
3. **Creating UserFrosting 6 project at `/workspace/userfrosting`**
4. **Configuring both as local path repositories** in composer.json and package.json
5. **Setting up the complete UserFrosting application** with all configurations (MyApp.php, main.ts, router/index.ts)

This approach allows you to:
- Work on CRUD6 sprinkle source code cloned from the main branch at `/ssnukala/sprinkle-crud6`
- Work on C6Admin sprinkle source code cloned from the main branch at `/ssnukala/sprinkle-c6admin` (if available)
- Have the sprinkle available at `/ssnukala/sprinkle-crud6` for UserFrosting integration
- Develop and test in a fully configured UserFrosting 6 instance
- Make changes to sprinkle code and see them immediately in the running application

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
   - Clones sprinkle-crud6 from main branch to `/ssnukala/sprinkle-crud6`
   - Clones sprinkle-c6admin from main branch to `/ssnukala/sprinkle-c6admin` (if available)
   - Creates UserFrosting 6 project at `/workspace/userfrosting`
   - Configures composer.json with local repositories
   - Configures package.json with local sprinkle references
   - Modifies MyApp.php to include CRUD6 (and C6Admin if available)
   - Modifies router/index.ts to include routes from both sprinkles
   - Modifies main.ts to include both sprinkles
   - Creates groups.json schema
   - Sets up .env file
   - Runs database migrations
   - Seeds database (Account + CRUD6 + C6Admin sprinkles)
   - Creates admin user (username: admin, password: admin123)

3. **Start Development**
   ```bash
   # In container terminal - workspace opens to /workspace/userfrosting
   php bakery serve                # Start UserFrosting server (port 8080)
   # In a new terminal
   php bakery assets:vite         # Start Vite dev server (port 5173)
   ```

## Development Workflow

### File Structure After Setup

```
/workspace/                          # Repository mounted here
├── app/                            # CRUD6 Sprinkle source (from this repo)
├── composer.json
├── package.json
└── .devcontainer/

/ssnukala/                          # Local sprinkle clones from GitHub main branches
├── sprinkle-crud6/                 # Clone of CRUD6 sprinkle from main branch
└── sprinkle-c6admin/              # Clone of C6Admin sprinkle from main branch (if exists)

/workspace/userfrosting/            # Full UserFrosting 6 installation
├── app/
│   ├── src/MyApp.php              # Configured with CRUD6::class and C6Admin::class
│   ├── assets/
│   │   ├── router/index.ts        # Configured with CRUD6Routes and C6AdminRoutes
│   │   └── main.ts                # Configured with CRUD6Sprinkle and C6AdminSprinkle
│   ├── schema/crud6/
│   │   └── groups.json            # Example schema
│   └── .env                       # Database configuration
├── vendor/
│   └── ssnukala/
│       ├── sprinkle-crud6/        # Path dependency → /ssnukala/sprinkle-crud6
│       └── sprinkle-c6admin/      # Path dependency → /ssnukala/sprinkle-c6admin
├── node_modules/
│   └── @ssnukala/
│       ├── sprinkle-crud6/        # NPM package from local path
│       └── sprinkle-c6admin/      # NPM package from local path
└── composer.json                  # Configured with local repositories
```

### Working with Sprinkle Source Code

**Important:** The workspace folder is set to `/workspace` in the devcontainer, which is the root of the mounted repository. After the setup script runs, the UserFrosting project will be available at `/workspace/userfrosting`.

To edit sprinkle source code:
1. **sprinkle-crud6**: Edit files in `/ssnukala/sprinkle-crud6` (cloned from main branch)
2. **sprinkle-c6admin**: Edit files in `/ssnukala/sprinkle-c6admin` (cloned from main branch)
3. Both are automatically linked to UserFrosting via composer and npm local path dependencies
4. Changes are immediately available in the UserFrosting application

**Note:** The repositories are cloned fresh from GitHub main branches during setup, ensuring you have the latest versions.

### Available Commands

#### In UserFrosting project (`/workspace/userfrosting` - created during setup):
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

## Integration Test Mirroring

The `setup-project.sh` script mirrors the steps from `.github/workflows/integration-test.yml`:

| Integration Test Step | DevContainer Implementation |
|----------------------|---------------------------|
| Checkout sprinkle-crud6 | Clone from main branch to `/ssnukala/sprinkle-crud6` |
| Checkout sprinkle-c6admin | Clone from main branch to `/ssnukala/sprinkle-c6admin` |
| Setup PHP/Node | Included in Dockerfile |
| Create UserFrosting project | `composer create-project` in setup script |
| Configure Composer | Local path repositories in `/ssnukala` |
| Install PHP dependencies | `composer install` in setup script |
| Configure package.json | Local path references to both sprinkles |
| Install NPM dependencies | `npm install` with local packages |
| Configure MyApp.php | `sed` commands to add CRUD6::class and C6Admin::class |
| Configure router/index.ts | `sed` commands to add routes from both sprinkles |
| Configure main.ts | `sed` commands to add both sprinkles |
| Create groups schema | Schema file created in setup script |
| Setup environment | .env file created with database config |
| Run migrations | `php bakery migrate --force` |
| Seed database | Account + CRUD6 seeds run automatically |
| Create admin user | `php bakery create:admin-user` |

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

Create JSON schemas in `/workspace/userfrosting/app/schema/crud6/`:

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
   - The setup script waits up to 60 seconds for MySQL
   - If migrations fail, manually run: `cd /workspace/userfrosting && php bakery migrate --force`

2. **Changes Not Reflected**
   - Changes in `/workspace` need to be copied to `/ssnukala/sprinkle-crud6`
   - Or edit directly in `/ssnukala/sprinkle-crud6` during development
   - Run `composer dump-autoload` in UserFrosting project

3. **Permission Issues**
   ```bash
   sudo chown -R vscode:vscode /workspace /ssnukala
   ```

4. **Rebuild Setup**
   ```bash
   # Re-run setup script manually
   bash /workspace/.devcontainer/setup-project.sh
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
tail -f /workspace/userfrosting/app/logs/userfrosting.log

# View XDebug logs
tail -f /tmp/xdebug.log

# Test database connection
mysql -h mysql -u userfrosting -puserfrosting userfrosting -e "SHOW TABLES;"
```

### Manual Validation

```bash
# Validate PHP syntax
find /ssnukala/sprinkle-crud6/app/src -name "*.php" -exec php -l {} \;

# Validate JSON schemas
for file in /workspace/userfrosting/app/schema/crud6/*.json; do
  php -r "echo json_decode(file_get_contents('$file')) ? '$file valid' : '$file invalid'; echo PHP_EOL;"
done

# Test UserFrosting
cd /workspace/userfrosting
php bakery debug:container
php bakery route:list
```

## Development Tips

### Working with Multiple Sprinkles
- Edit CRUD6 sprinkle: `/ssnukala/sprinkle-crud6` (cloned from main branch)
- Edit C6Admin sprinkle: `/ssnukala/sprinkle-c6admin` (cloned from main branch, if available)
- Both are installed as local path dependencies in UserFrosting via composer and npm
- Changes to sprinkle source files are immediately available in the UserFrosting application

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
cd /workspace/userfrosting
vendor/bin/phpunit
```

## Integration with sprinkle-c6admin

If the `ssnukala/sprinkle-c6admin` repository exists and is cloned during setup:
- It will be cloned from the main branch to `/ssnukala/sprinkle-c6admin`
- It will be added to composer.json and package.json as a local path dependency
- MyApp.php, main.ts, and router/index.ts will be automatically configured to include C6Admin
- You can develop both sprinkles simultaneously in the same environment

## Contributing

When working on sprinkle development:

1. Make changes to source files in `/ssnukala/sprinkle-crud6/app/src/` (or `/ssnukala/sprinkle-c6admin/`)
2. Changes are immediately available in the UserFrosting application (local path dependencies)
3. Add/update tests in the respective sprinkle's `app/tests/` directory
4. Update schemas in the respective sprinkle's `app/schema/` directory
5. Test changes in UserFrosting project at `/workspace/userfrosting/`
6. Run validation commands before committing
7. Commit and push changes from the respective sprinkle directories

The development container provides a complete environment that mirrors the integration test workflow, ensuring your local development matches the CI/CD pipeline. Both sprinkles are cloned fresh from their main branches, ensuring you're always working with the latest code.