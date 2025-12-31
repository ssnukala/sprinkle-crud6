# GitHub Actions Workflow Example

Complete example GitHub Actions workflow using the UserFrosting 6 integration testing framework.

## Basic Workflow

Here's a minimal workflow that runs integration tests:

```yaml
name: Integration Test

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  integration-test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: userfrosting_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - name: Checkout sprinkle
        uses: actions/checkout@v4
        with:
          path: my-sprinkle
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.1"
          extensions: mbstring, xml, gd, pdo_mysql
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: "20"
      
      - name: Create UserFrosting project
        run: |
          composer create-project userfrosting/userfrosting userfrosting "^6.0-beta" --no-scripts
      
      - name: Configure for local sprinkle
        run: |
          cd userfrosting
          composer config repositories.local path ../my-sprinkle
          composer require your/sprinkle-package:@dev --no-update
          composer config minimum-stability beta
          composer config prefer-stable true
      
      - name: Install dependencies
        run: |
          cd userfrosting
          composer install --no-interaction
      
      - name: Setup environment
        run: |
          cd userfrosting
          cp app/.env.example app/.env
          sed -i 's/DB_CONNECTION=.*/DB_CONNECTION="mysql"/' app/.env
          sed -i 's/DB_HOST=.*/DB_HOST="127.0.0.1"/' app/.env
          sed -i 's/DB_NAME=.*/DB_NAME="userfrosting_test"/' app/.env
          sed -i 's/DB_USER=.*/DB_USER="root"/' app/.env
          sed -i 's/DB_PASSWORD=.*/DB_PASSWORD="root"/' app/.env
      
      - name: Run migrations
        run: |
          cd userfrosting
          php bakery migrate --force
      
      - name: Run seeds from configuration
        run: |
          cd userfrosting
          cp ../my-sprinkle/.github/config/integration-test-seeds.json .
          cp ../my-sprinkle/.github/scripts/run-seeds.php .
          php run-seeds.php integration-test-seeds.json
      
      - name: Validate seeds
        run: |
          cd userfrosting
          cp ../my-sprinkle/.github/scripts/check-seeds-modular.php .
          php check-seeds-modular.php integration-test-seeds.json
      
      - name: Test seed idempotency
        run: |
          cd userfrosting
          cp ../my-sprinkle/.github/scripts/test-seed-idempotency-modular.php .
          BEFORE=$(php test-seed-idempotency-modular.php integration-test-seeds.json | grep "BEFORE:")
          php run-seeds.php integration-test-seeds.json
          php test-seed-idempotency-modular.php integration-test-seeds.json after "$BEFORE"
      
      - name: Create admin user
        run: |
          cd userfrosting
          php bakery create:admin-user \
            --username=admin \
            --password=admin123 \
            --email=admin@example.com
      
      - name: Start PHP server
        run: |
          cd userfrosting
          php bakery serve &
          sleep 10
      
      - name: Test API paths
        run: |
          cd userfrosting
          cp ../my-sprinkle/.github/config/integration-test-paths.json .
          cp ../my-sprinkle/.github/scripts/test-paths.php .
          php test-paths.php integration-test-paths.json
```

## Complete Workflow with Screenshots

Full-featured workflow including frontend screenshots:

```yaml
name: Integration Test with Screenshots

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  integration-test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: userfrosting_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      # ... (previous steps same as basic workflow)
      
      - name: Install Playwright for screenshots
        run: |
          cd userfrosting
          npm install playwright
          npx playwright install chromium --with-deps
      
      - name: Build frontend assets
        run: |
          cd userfrosting
          php bakery bake
      
      - name: Start Vite dev server
        run: |
          cd userfrosting
          php bakery assets:vite &
          sleep 20
      
      - name: Take screenshots
        run: |
          cd userfrosting
          cp ../my-sprinkle/.github/scripts/take-screenshots-modular.js .
          node take-screenshots-modular.js integration-test-paths.json
      
      - name: Upload screenshots
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: integration-test-screenshots
          path: /tmp/screenshot_*.png
          retention-days: 30
```

## Workflow for Multiple PHP Versions

Test across multiple PHP versions:

```yaml
name: Integration Test Matrix

on:
  push:
    branches: [main, develop]

jobs:
  integration-test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: userfrosting_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - name: Checkout sprinkle
        uses: actions/checkout@v4
        with:
          path: my-sprinkle
      
      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, xml, gd, pdo_mysql
      
      # ... rest of workflow
```

## Adapting for Your Sprinkle

### 1. Replace Placeholders

Find and replace in the workflow:
- `my-sprinkle` → your sprinkle directory name
- `your/sprinkle-package` → your composer package name
- Adjust paths as needed for your setup

### 2. Customize Steps

Add or remove steps based on your needs:

**For NPM package sprinkle:**
```yaml
- name: Install NPM dependencies
  run: |
    cd userfrosting
    npm install ../my-sprinkle
```

**For additional setup:**
```yaml
- name: Custom setup
  run: |
    cd userfrosting
    # Your custom commands
```

### 3. Configure Artifacts

Upload additional artifacts as needed:

```yaml
- name: Upload logs
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: php-logs
    path: userfrosting/app/logs/*.log
```

## Advanced Configurations

### Caching

Speed up workflow with caching:

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: ~/.composer/cache
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}

- name: Cache NPM dependencies
  uses: actions/cache@v4
  with:
    path: ~/.npm
    key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
```

### Parallel Testing

Run different test types in parallel:

```yaml
jobs:
  test-seeds:
    runs-on: ubuntu-latest
    steps:
      # ... run seed tests only
  
  test-api:
    runs-on: ubuntu-latest
    steps:
      # ... run API tests only
  
  test-frontend:
    runs-on: ubuntu-latest
    steps:
      # ... run frontend tests only
```

### Conditional Execution

Run certain steps only on specific conditions:

```yaml
- name: Take screenshots
  if: github.event_name == 'pull_request'
  run: |
    # Only run on PRs
```

## Complete Real-World Example

See the CRUD6 sprinkle workflow for a complete, production-ready example:

https://github.com/ssnukala/sprinkle-crud6/blob/main/.github/workflows/integration-test.yml

This workflow includes:
- ✅ Database migrations
- ✅ Seed data creation and validation
- ✅ Idempotency testing
- ✅ API endpoint testing
- ✅ Frontend screenshot capture
- ✅ Error log collection
- ✅ Artifact uploads
- ✅ Detailed summary output

## Troubleshooting

### MySQL Connection Issues

If you see connection errors:

```yaml
services:
  mysql:
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=5  # Increase retries
```

### Timeout Issues

Increase wait times:

```yaml
- name: Start servers
  run: |
    php bakery serve &
    sleep 20  # Increase from 10 to 20 seconds
```

### Permission Issues

Ensure directories are writable:

```yaml
- name: Create storage directories
  run: |
    cd userfrosting
    mkdir -p app/storage/{sessions,cache,logs}
    chmod -R 777 app/storage
```

## Best Practices

1. **Always use `--force` for migrations** in CI
2. **Set health checks** for database services
3. **Upload logs as artifacts** for debugging
4. **Use caching** to speed up builds
5. **Add summary step** for clear test results
6. **Keep workflow DRY** - use scripts from config files
7. **Test idempotency** - verify seeds can run multiple times

## Next Steps

1. Copy workflow template to `.github/workflows/integration-test.yml`
2. Customize for your sprinkle
3. Commit and push
4. Monitor workflow run in GitHub Actions
5. Download artifacts to review screenshots

## Support

For issues with GitHub Actions integration:
- Check [GitHub Actions documentation](https://docs.github.com/actions)
- Review [CRUD6 workflow](https://github.com/ssnukala/sprinkle-crud6/blob/main/.github/workflows/integration-test.yml)
- Open an issue on the CRUD6 repository
