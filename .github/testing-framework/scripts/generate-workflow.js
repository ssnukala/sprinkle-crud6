#!/usr/bin/env node

/**
 * Configuration-Driven Workflow Generator
 * 
 * This script reads integration-test-config.json and generates a complete
 * GitHub Actions workflow file automatically.
 * 
 * Usage:
 *   node generate-workflow.js [config.json] [output.yml]
 *   node generate-workflow.js integration-test-config.json .github/workflows/integration-test.yml
 */

const fs = require('fs');
const path = require('path');

// Parse command line arguments
const configFile = process.argv[2] || 'integration-test-config.json';
const outputFile = process.argv[3] || '.github/workflows/integration-test.yml';

// Read configuration
console.log(`Reading configuration from: ${configFile}`);
const config = JSON.parse(fs.readFileSync(configFile, 'utf8'));

// Validate required fields
if (!config.sprinkle || !config.sprinkle.name || !config.sprinkle.composer_package) {
  console.error('ERROR: Missing required sprinkle configuration (name, composer_package)');
  process.exit(1);
}

// Generate workflow YAML
const workflow = generateWorkflow(config);

// Ensure output directory exists
const outputDir = path.dirname(outputFile);
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

// Write workflow file
fs.writeFileSync(outputFile, workflow);
console.log(`✅ Generated workflow: ${outputFile}`);
console.log(`   Sprinkle: ${config.sprinkle.name}`);
console.log(`   Pattern: ${config.routes.pattern}`);
console.log(`   Schema path: ${config.schemas.path || 'app/schema/crud6 (default)'}`);

function generateCustomSteps(customSteps, stage) {
  if (!customSteps?.enabled || !customSteps?.scripts) {
    return '';
  }
  
  const stageScripts = customSteps.scripts.filter(s => s.stage === stage);
  if (stageScripts.length === 0) {
    return '';
  }
  
  return stageScripts.map(script => `
      - name: ${script.name}
        run: |
          cd userfrosting
          node ../\${{ env.SPRINKLE_DIR }}/${script.script}
`).join('');
}

function generateWorkflow(config) {
  const s = config.sprinkle;
  const routes = config.routes;
  const schemas = config.schemas;
  const testing = config.testing;
  const vite = config.vite;
  const customSteps = config.custom_steps;
  
  // Extract versions with defaults
  const mysqlVersion = testing?.mysql_version || '8.0';
  
  return `name: ${s.name} Integration Test

# AUTO-GENERATED from integration-test-config.json
# To regenerate: node .github/testing-framework/scripts/generate-workflow.js

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:

env:
  SPRINKLE_DIR: ${s.name}
  COMPOSER_PACKAGE: ${s.composer_package}
  NPM_PACKAGE: "${s.npm_package || ''}"
  SCHEMA_PATH: "${schemas.path || ''}"
  FRAMEWORK_REPO: ssnukala/sprinkle-crud6
  FRAMEWORK_BRANCH: ${config.framework?.version || 'main'}
  UF_VERSION: "${testing?.userfrosting_version || '^6.0-beta'}"
  PHP_VERSION: "${testing?.php_version || '8.1'}"
  NODE_VERSION: "${testing?.node_version || '20'}"

jobs:
  integration-test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:${mysqlVersion}
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
          path: \${{ env.SPRINKLE_DIR }}
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: \${{ env.PHP_VERSION }}
          extensions: mbstring, xml, gd, pdo_mysql
          coverage: none
      
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: \${{ env.NODE_VERSION }}
      
      - name: Install testing framework
        run: |
          cd \${{ env.SPRINKLE_DIR }}
          
          if [ -d ".github/crud6-framework" ]; then
            echo "✅ Using local framework"
          else
            echo "📦 Installing framework..."
            git clone --depth 1 --branch \${{ env.FRAMEWORK_BRANCH }} \\
              https://github.com/\${{ env.FRAMEWORK_REPO }}.git /tmp/crud6-repo
            mkdir -p .github/crud6-framework
            cp -r /tmp/crud6-repo/.github/testing-framework/* .github/crud6-framework/
            chmod +x .github/crud6-framework/scripts/*.php
            echo "✅ Framework installed"
          fi
          
          mkdir -p .github/config
          if [ -d "/tmp/crud6-repo/.github/config" ]; then
            cp /tmp/crud6-repo/.github/config/integration-test-*.json .github/config/ 2>/dev/null || true
          fi
      
      - name: Create UserFrosting project
        run: |
          composer create-project userfrosting/userfrosting userfrosting "\${{ env.UF_VERSION }}" \\
            --no-scripts --no-install --ignore-platform-reqs
          
          cd userfrosting/app
          mkdir -p storage/sessions storage/cache storage/logs logs cache sessions
          chmod -R 777 storage sessions logs cache
          touch logs/userfrosting.log
      
      - name: Configure Composer dependencies
        run: |
          cd userfrosting
          
          if [ -d "/tmp/crud6-repo" ]; then
            composer config repositories.crud6 path /tmp/crud6-repo
            composer require ssnukala/sprinkle-crud6:@dev --no-update
          fi
          
          composer config repositories.local path ../\${{ env.SPRINKLE_DIR }}
          composer require \${{ env.COMPOSER_PACKAGE }}:@dev --no-update
          
          composer config minimum-stability beta
          composer config prefer-stable true
          
          composer install --no-interaction --prefer-dist
      
      - name: Configure NPM dependencies
        run: |
          cd userfrosting
          npm update
          
          if [ -d "/tmp/crud6-repo" ]; then
            cd /tmp/crud6-repo
            npm pack
            mv ssnukala-sprinkle-crud6-*.tgz "\$GITHUB_WORKSPACE/userfrosting/"
            cd "\$GITHUB_WORKSPACE/userfrosting"
            npm install ./ssnukala-sprinkle-crud6-*.tgz
          fi
          
          cd "\$GITHUB_WORKSPACE/\${{ env.SPRINKLE_DIR }}"
          npm pack 2>/dev/null || true
          if ls *.tgz 1> /dev/null 2>&1; then
            mv *.tgz "\$GITHUB_WORKSPACE/userfrosting/"
            cd "\$GITHUB_WORKSPACE/userfrosting"
            npm install ./*.tgz 2>/dev/null || echo "NPM package optional"
          fi
      
      - name: Configure MyApp.php
        run: |
          cd userfrosting
          
          sed -i '/use UserFrosting\\\\Sprinkle\\\\Core\\\\Core;/a use UserFrosting\\\\Sprinkle\\\\CRUD6\\\\CRUD6;' app/src/MyApp.php
          sed -i '/Admin::class,/a \\            CRUD6::class,' app/src/MyApp.php
          
          echo ""
          echo "✅ MyApp.php configured"
          echo "Updated app/src/MyApp.php:"
          cat app/src/MyApp.php
      
      - name: Configure main.ts
        run: |
          cd userfrosting
          
          sed -i "/import AdminSprinkle from '@userfrosting\\\\/sprinkle-admin'/a import CRUD6Sprinkle from '@ssnukala\\\\/sprinkle-crud6'" app/assets/main.ts
          sed -i "/app.use(AdminSprinkle)/a app.use(CRUD6Sprinkle)" app/assets/main.ts
          
          echo ""
          echo "✅ main.ts configured"
          echo "Updated app/assets/main.ts:"
          cat app/assets/main.ts
      
${generateRouteConfiguration(routes, s)}
      
${generateViteConfiguration(vite)}
      
      - name: Setup environment
        run: |
          cd userfrosting
          cp app/.env.example app/.env
          sed -i 's/DB_CONNECTION=.*/DB_CONNECTION="mysql"/' app/.env
          sed -i 's/DB_HOST=.*/DB_HOST="127.0.0.1"/' app/.env
          sed -i 's/DB_PORT=.*/DB_PORT="3306"/' app/.env
          sed -i 's/DB_NAME=.*/DB_NAME="userfrosting_test"/' app/.env
          sed -i 's/DB_USER=.*/DB_USER="root"/' app/.env
          sed -i 's/DB_PASSWORD=.*/DB_PASSWORD="root"/' app/.env
          echo "BAKERY_CONFIRM_SENSITIVE_COMMAND=false" >> app/.env
          echo "TEST_SESSION_HANDLER=database" >> app/.env
      
      - name: Run migrations
        run: |
          cd userfrosting
          php bakery migrate --force
      
      - name: Generate and create tables from schemas
        run: |
          cd userfrosting
          
          SCHEMA_DIR="../\${{ env.SPRINKLE_DIR }}/\${{ env.SCHEMA_PATH }}"
          if [ -z "\${{ env.SCHEMA_PATH }}" ]; then
            SCHEMA_DIR="../\${{ env.SPRINKLE_DIR }}/app/schema/crud6"
          fi
          
          if [ ! -d "\$SCHEMA_DIR" ]; then
            echo "❌ ERROR: Schema directory not found: \$SCHEMA_DIR"
            exit 1
          fi
          
          echo "✅ Using schemas from: \$SCHEMA_DIR"
          
          # Generate DDL (CREATE TABLE statements) from schemas
          node ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/generate-ddl-sql.js \\
            "\$SCHEMA_DIR" tables.sql
          
          # Create tables from DDL
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/load-seed-sql.php \\
            tables.sql
          
          echo "✅ Tables created from schemas"
      
      - name: Generate and load SQL seed data
        run: |
          cd userfrosting
          
          SCHEMA_DIR="../\${{ env.SPRINKLE_DIR }}/\${{ env.SCHEMA_PATH }}"
          if [ -z "\${{ env.SCHEMA_PATH }}" ]; then
            SCHEMA_DIR="../\${{ env.SPRINKLE_DIR }}/app/schema/crud6"
          fi
          
          # Generate seed data
          node ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/generate-seed-sql.js \\
            "\$SCHEMA_DIR" seed-data.sql
          
          # Load seed data
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/load-seed-sql.php \\
            seed-data.sql
          
          echo "✅ Schema-driven SQL loaded"
${config.custom_test_data?.enabled ? `
      - name: Load custom test data
        run: |
          cd userfrosting
          CUSTOM_SQL="../\${{ env.SPRINKLE_DIR }}/${config.custom_test_data.path}"
          if [ -f "\$CUSTOM_SQL" ]; then
            echo "📝 Loading custom test data"
            php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/load-seed-sql.php "\$CUSTOM_SQL"
            echo "✅ Custom test data loaded"
          fi
` : ''}
      - name: Run PHP seeds
        run: |
          cd userfrosting
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/run-seeds.php \\
            ../\${{ env.SPRINKLE_DIR }}/.github/config/integration-test-seeds.json
      
      - name: Validate seed data
        run: |
          cd userfrosting
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/check-seeds-modular.php \\
            ../\${{ env.SPRINKLE_DIR }}/.github/config/integration-test-seeds.json
      
      - name: Test seed idempotency
        run: |
          cd userfrosting
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/test-seed-idempotency-modular.php \\
            ../\${{ env.SPRINKLE_DIR }}/.github/config/integration-test-seeds.json
${generateCustomSteps(customSteps, 'before_tests')}
      - name: Build frontend assets
        run: |
          cd userfrosting
          npm run uf-bundle
      
      - name: Test API and frontend paths
        run: |
          cd userfrosting
          php ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/test-paths.php \\
            ../\${{ env.SPRINKLE_DIR }}/.github/config/integration-test-paths.json
${generateCustomSteps(customSteps, 'after_tests')}
      - name: Install Playwright
        run: |
          cd userfrosting
          npx playwright install chromium
${generateCustomSteps(customSteps, 'before_screenshots')}
      - name: Capture screenshots
        run: |
          cd userfrosting
          node ../\${{ env.SPRINKLE_DIR }}/.github/crud6-framework/scripts/take-screenshots-modular.js \\
            ../\${{ env.SPRINKLE_DIR }}/.github/config/integration-test-paths.json \\
            screenshots
${generateCustomSteps(customSteps, 'after_screenshots')}
      - name: Upload screenshots
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: integration-test-screenshots
          path: userfrosting/screenshots/
          retention-days: 7
      
      - name: Upload logs
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: integration-test-logs
          path: userfrosting/app/logs/
          retention-days: 7
`;
}

function generateRouteConfiguration(routes, sprinkle) {
  const pattern = routes.pattern || 'simple';
  const importModule = routes.import.module;
  const importName = routes.import.name;
  
  let step = `      - name: Configure routes (${pattern} pattern)
        run: |
          cd userfrosting
          
`;
  
  if (pattern === 'simple') {
    step += `          # Simple array spread pattern
          sed -i "/import AdminRoutes from '@userfrosting\\\\/sprinkle-admin\\\\/routes'/a import ${importName} from '${importModule}'" app/assets/router/index.ts
          sed -i "/\\\\.\\\\.\\\\.AccountRoutes,/a \\\\            ...${importName}," app/assets/router/index.ts
`;
  } else if (pattern === 'factory') {
    const layoutComponent = routes.factory?.layout_component || 'Layout';
    step += `          # Factory function pattern
          sed -i "/import AdminRoutes from '@userfrosting\\\\/sprinkle-admin\\\\/routes'/a import { ${importName} } from '${importModule}'" app/assets/router/index.ts
          sed -i "/import Layout from '@userfrosting\\\\/theme-adminlte\\\\/layouts\\\\/Layout.vue'/a const ${sprinkle.name}Routes = ${importName}({ layoutComponent: ${layoutComponent} });" app/assets/router/index.ts
          sed -i "/\\\\.\\\\.\\\\.AccountRoutes,/a \\\\            ...${sprinkle.name}Routes," app/assets/router/index.ts
`;
  } else if (pattern === 'custom' && routes.custom_setup?.enabled) {
    step += `          # Custom route setup
`;
    routes.custom_setup.commands.forEach(cmd => {
      step += `          ${cmd}\n`;
    });
  }
  
  // Add cat command to display the configured router file
  step += `
          echo ""
          echo "✅ Routes configured (${pattern} pattern)"
          echo "Updated app/assets/router/index.ts:"
          cat app/assets/router/index.ts
`;
  
  return step;
}

function generateViteConfiguration(vite) {
  const deps = vite?.optimize_deps || ['limax', 'lodash.deburr'];
  const depsStr = deps.map(d => `'${d}'`).join(', ');
  
  return `      - name: Configure vite.config.ts
        run: |
          cd userfrosting
          
          if grep -q "optimizeDeps:" vite.config.ts; then
            if grep -q "include:" vite.config.ts; then
              sed -i "/include: \\\\[/a \\\\            ${depsStr.replace(/'/g, "\\'")}," vite.config.ts
            else
              sed -i "/optimizeDeps: {/a \\\\        include: [${depsStr.replace(/'/g, "\\'")}]," vite.config.ts
            fi
          else
            sed -i "/plugins: \\\\[/,/\\\\],/a \\\\    optimizeDeps: {\\\\n        include: [${depsStr.replace(/'/g, "\\'")}]\\\\n    }," vite.config.ts
          fi
          
          echo ""
          echo "✅ Vite configuration updated"
          echo "Updated vite.config.ts:"
          cat vite.config.ts
`;
}

module.exports = { generateWorkflow };
