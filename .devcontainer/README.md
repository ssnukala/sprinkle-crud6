# Development Container Setup for CRUD6 Sprinkle

This repository includes a complete development container setup for CRUD6 sprinkle development with UserFrosting 6.

## What's Included

### Full Stack Development Environment
- **PHP 8.2** with UserFrosting 6 beta
- **Node.js 20** for frontend development
- **MySQL 8.0** database
- **XDebug** for PHP debugging
- **VS Code extensions** for PHP, Vue.js, and TypeScript development

### Development Configuration
```bash
.devcontainer/
├── devcontainer.json          # Main devcontainer config
├── docker-compose.yml         # Multi-container setup with MySQL
├── Dockerfile                 # Combined PHP + Node.js container
├── setup-project.sh          # Setup script for CRUD6 development
└── README.md                 # This file
```

## Quick Start

### Prerequisites
- **VS Code** with "Dev Containers" extension
- **Docker Desktop** or **Docker Engine**

### Setup Process

1. **Open in VS Code Dev Container**
   - Open this repository in VS Code
   - When prompted, click "Reopen in Container"
   - Or use Command Palette: "Dev Containers: Reopen in Container"

2. **Automatic Setup**
   - The container will automatically run `setup-project.sh`
   - Creates a UserFrosting 6 project at `/workspace/userfrosting`
   - Installs CRUD6 sprinkle as a local dependency
   - Sets up database connection with MySQL
   - Configures development environment

3. **Start Development**
   ```bash
   # In container terminal
   cd /workspace/userfrosting
   php bakery migrate              # Run database migrations
   composer serve                  # Start UserFrosting server (port 8080)
   ```

## Development Workflow

### CRUD6 Sprinkle Development

The setup automatically configures UserFrosting to use the CRUD6 sprinkle as a local development dependency:

```json
// composer.json in UserFrosting project
{
  "repositories": {
    "crud6-sprinkle": {
      "type": "path",
      "url": "/workspace"
    }
  },
  "require": {
    "ssnukala/sprinkle-crud6": "*"
  }
}
```

### Available Commands

#### In CRUD6 Sprinkle (workspace root `/workspace`):
```bash
# Development and testing
composer install               # Install dependencies (if needed)
find app/src -name "*.php" -exec php -l {} \;  # Validate PHP syntax
vendor/bin/phpunit            # Run tests (requires full setup)
vendor/bin/php-cs-fixer fix   # Format code
vendor/bin/phpstan analyse    # Static analysis
```

#### In UserFrosting project (`/workspace/userfrosting`):
```bash
composer install              # Install PHP dependencies
composer serve               # Start development server
php bakery migrate           # Run database migrations
php bakery bake              # Build assets and clear cache
npm install                  # Install Node.js dependencies
```

### Port Mapping

| Service | Port | Description |
|---------|------|-------------|
| UserFrosting | 8080 | Main application server |
| Vite Dev Server | 5173 | Frontend development (if using theme) |
| MySQL | 3306 | Database server |
| XDebug | 9003 | PHP debugging |

### VS Code Integration

The devcontainer includes these VS Code extensions:
- **PHP**: Intelephense, XDebug, PHP CS Fixer, PHPStan
- **Vue.js**: Volar, TypeScript Vue Plugin (for theme development)
- **General**: Prettier, Tailwind CSS, Twig, GitHub Copilot

### Database Configuration

MySQL is automatically configured with:
- Host: `mysql`
- Database: `userfrosting`
- Username: `userfrosting`
- Password: `userfrosting`

The development `.env` file is automatically created in the UserFrosting project.

## File Structure After Setup

```
/workspace/
├── app/                       # CRUD6 Sprinkle source (this repository)
│   ├── src/
│   ├── schema/
│   ├── tests/
│   └── assets/
├── composer.json             # CRUD6 sprinkle composer config
├── userfrosting/             # UserFrosting 6 project
│   ├── app/
│   │   ├── sprinkles/
│   │   │   └── crud6 -> /workspace (symlink)
│   │   └── .env
│   ├── vendor/
│   │   └── ssnukala/
│   │       └── sprinkle-crud6 -> /workspace (path dependency)
│   ├── composer.json
│   └── package.json
└── examples/                 # CRUD6 usage examples
    ├── products.json
    └── README.md
```

## Integration with UserFrosting

### Sprinkle Registration

Add the CRUD6 sprinkle to your UserFrosting application:

```php
// In your main sprinkle class (e.g., app/src/MyApp.php)
use UserFrosting\Sprinkle\CRUD6\CRUD6;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class,      // Add this line
        // ... your other sprinkles
    ];
}
```

### Schema Development

Create JSON schemas in `/workspace/app/schema/crud6/`:

```json
{
  "model": "products",
  "table": "products",
  "fields": {
    "name": {
      "type": "string",
      "required": true,
      "validation": {
        "length": { "min": 2, "max": 255 }
      }
    }
  }
}
```

### API Endpoints

The CRUD6 sprinkle automatically provides RESTful API endpoints:
- `GET /api/crud6/{model}` - List records with pagination/filtering
- `GET /api/crud6/{model}/{id}` - Get single record
- `POST /api/crud6/{model}` - Create record
- `PUT /api/crud6/{model}/{id}` - Update record
- `DELETE /api/crud6/{model}/{id}` - Delete record

## Troubleshooting

### Common Issues

1. **Permission Issues**
   ```bash
   sudo chown -R vscode:vscode /workspace
   ```

2. **Composer Install Fails**
   ```bash
   # Try with extended timeout
   cd /workspace
   composer install --no-interaction --timeout=600
   ```

3. **Database Connection Issues**
   - Wait for MySQL container to fully start (30-60 seconds)
   - Check that database credentials match in UserFrosting `.env`

4. **XDebug Not Working**
   - Ensure XDebug client is configured for port 9003
   - Check that `host.docker.internal` resolves correctly

### Rebuilding Container

If you need to rebuild the development container:

1. In VS Code: Command Palette → "Dev Containers: Rebuild Container"
2. Or manually: `docker-compose down && docker-compose build --no-cache`

### Logs and Debugging

```bash
# View container logs
docker-compose logs sprinkle-crud6
docker-compose logs mysql

# Connect to running container
docker-compose exec sprinkle-crud6 bash

# View XDebug logs
tail -f /tmp/xdebug.log

# Check UserFrosting logs
tail -f /workspace/userfrosting/app/logs/userfrosting.log
```

### Manual Validation

```bash
# Validate PHP syntax
find /workspace/app/src -name "*.php" -exec php -l {} \;

# Validate JSON schemas
for file in /workspace/app/schema/crud6/*.json; do
  php -r "echo json_decode(file_get_contents('$file')) ? '$file valid' : '$file invalid'; echo PHP_EOL;"
done

# Test UserFrosting connection
cd /workspace/userfrosting
php bakery debug:container
```

## Development Tips

### Working with Schemas
- JSON schemas are validated on container startup
- Schema files support hot-reload during development
- Use the examples in `/workspace/examples/` as templates

### Testing CRUD6 Features
- Create test schemas in `app/schema/crud6/`
- Use the UserFrosting web interface to test API endpoints
- Check browser developer tools for API requests/responses

### Integration with Theme CRUD6
This sprinkle is designed to work with [theme-crud6](https://github.com/ssnukala/theme-crud6):
- The theme provides Vue.js components for CRUD interfaces
- Both repositories use compatible devcontainer setups
- Can be developed together in a full-stack environment

## Contributing

When working on CRUD6 sprinkle development:

1. Make changes to source files in `/workspace/app/src/`
2. Add/update tests in `/workspace/app/tests/`
3. Update schemas in `/workspace/app/schema/crud6/`
4. Test changes in the UserFrosting project at `/workspace/userfrosting/`
5. Run validation commands before committing

The development container provides a complete environment for contributing to the CRUD6 sprinkle while maintaining integration with UserFrosting 6.