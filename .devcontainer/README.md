# Development Container Setup for CRUD6 Sprinkle

This repository includes a complete development container setup that mirrors the `integration-test.yml` workflow to provide a full UserFrosting 6 development environment with the CRUD6 sprinkle.

## What's Included

### Full Stack Development Environment
- **PHP 8.2** with UserFrosting 6 beta
- **Node.js 20** for frontend development
- **MySQL 8.0** database
- **VS Code extensions** for PHP, Vue.js, and TypeScript development

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

1. **Copying sprinkle-crud6 to `/ssnukala/sprinkle-crud6`** - Main CRUD6 sprinkle source
2. **Cloning sprinkle-c6admin to `/ssnukala/sprinkle-c6admin`** (if repository exists)
3. **Creating UserFrosting 6 project at `/workspace/userfrosting`**
4. **Configuring both as local path repositories** in composer.json
5. **Setting up the complete UserFrosting application** with all configurations

This approach allows you to:
- Work on CRUD6 sprinkle source code in `/workspace` (mounted from repository)
- Have the sprinkle available at `/ssnukala/sprinkle-crud6` for UserFrosting integration
- Develop and test in a fully configured UserFrosting 6 instance
- Make changes to sprinkle code and see them immediately in the running application

## Quick Start

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
   - Copies sprinkle-crud6 to `/ssnukala/sprinkle-crud6`
   - Clones sprinkle-c6admin to `/ssnukala/sprinkle-c6admin` (if available)
   - Creates UserFrosting 6 project at `/workspace/userfrosting`
   - Configures composer.json with local repositories
   - Packages and installs NPM packages
   - Modifies MyApp.php, router/index.ts, and main.ts
   - Creates groups.json schema
   - Sets up .env file
   - Runs database migrations
   - Seeds database (Account + CRUD6 sprinkles)
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
├── app/                            # CRUD6 Sprinkle source (edit here)
├── composer.json
├── package.json
└── .devcontainer/

/ssnukala/                          # Local sprinkle repository copies
├── sprinkle-crud6/                 # Copy of CRUD6 sprinkle
└── sprinkle-c6admin/              # Clone of C6Admin sprinkle (if exists)

/workspace/userfrosting/            # Full UserFrosting 6 installation
├── app/
│   ├── src/MyApp.php              # Configured with CRUD6::class
│   ├── assets/
│   │   ├── router/index.ts        # Configured with CRUD6Routes
│   │   └── main.ts                # Configured with CRUD6Sprinkle
│   ├── schema/crud6/
│   │   └── groups.json            # Example schema
│   └── .env                       # Database configuration
├── vendor/
│   └── ssnukala/
│       ├── sprinkle-crud6/        # Path dependency → /ssnukala/sprinkle-crud6
│       └── sprinkle-c6admin/      # Path dependency → /ssnukala/sprinkle-c6admin
├── node_modules/
│   └── @ssnukala/
│       ├── sprinkle-crud6/        # NPM package
│       └── sprinkle-c6admin/      # NPM package
└── composer.json                  # Configured with local repositories
```

### Working with CRUD6 Source Code

**Important:** The workspace folder is set to `/workspace/userfrosting` in the devcontainer, so VS Code opens directly to the UserFrosting project.

To edit CRUD6 sprinkle source code:
1. The original `/workspace` contains the repository source
2. It's copied to `/ssnukala/sprinkle-crud6` during setup
3. Changes in `/workspace` need to be re-copied or you can edit in `/ssnukala/sprinkle-crud6`

**Recommended approach:** Edit files in `/workspace` (repository), then re-run setup or manually copy:
```bash
# After making changes to /workspace source
cp -r /workspace/app /ssnukala/sprinkle-crud6/
```

### Available Commands

#### In UserFrosting project (`/workspace/userfrosting` - default workspace):
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

#### In CRUD6 Sprinkle source (navigate to `/workspace` or `/ssnukala/sprinkle-crud6`):
```bash
cd /workspace  # or cd /ssnukala/sprinkle-crud6

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

## Integration Test Mirroring

The `setup-project.sh` script mirrors the steps from `.github/workflows/integration-test.yml`:

| Integration Test Step | DevContainer Implementation |
|----------------------|---------------------------|
| Checkout sprinkle-crud6 | Repository mounted at `/workspace` |
| Setup PHP/Node | Included in Dockerfile |
| Create UserFrosting project | `composer create-project` in setup script |
| Configure Composer | Local path repositories in `/ssnukala` |
| Install PHP dependencies | `composer install` in setup script |
| Package sprinkle for NPM | `npm pack` in setup script |
| Install NPM dependencies | `npm install` with local packages |
| Configure MyApp.php | `sed` commands to add CRUD6::class |
| Configure router/index.ts | `sed` commands to add CRUD6Routes |
| Configure main.ts | `sed` commands to add CRUD6Sprinkle |
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
- Edit CRUD6 sprinkle: `/workspace` or `/ssnukala/sprinkle-crud6`
- Edit C6Admin sprinkle: `/ssnukala/sprinkle-c6admin` (if cloned)
- Both are installed as local path dependencies in UserFrosting

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

# UserFrosting tests
cd /workspace/userfrosting
vendor/bin/phpunit
```

## Integration with sprinkle-c6admin

If the `ssnukala/sprinkle-c6admin` repository exists and is cloned during setup:
- It will be added to composer.json as a local repository
- NPM package will be installed from local path
- You can develop both sprinkles simultaneously in the same environment

## Contributing

When working on CRUD6 sprinkle development:

1. Make changes to source files in `/workspace/app/src/` (repository)
2. Copy changes to `/ssnukala/sprinkle-crud6/` for testing
3. Add/update tests in `/workspace/app/tests/`
4. Update schemas in `/workspace/app/schema/crud6/`
5. Test changes in UserFrosting project at `/workspace/userfrosting/`
6. Run validation commands before committing
7. Commit changes from `/workspace` (repository root)

The development container provides a complete environment that mirrors the integration test workflow, ensuring your local development matches the CI/CD pipeline.