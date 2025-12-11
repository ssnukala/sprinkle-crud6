# Custom Integration Test Scripts

This directory contains custom scripts that extend the integration testing framework for sprinkle-specific needs.

## Purpose

While the testing framework handles all standard integration testing automatically (infrastructure setup, database migrations, schema-driven SQL generation, API testing, frontend testing, screenshots), some sprinkles may need additional custom validation or setup steps.

## Usage

### 1. Create Your Custom Script

Create a Node.js script in this directory (e.g., `my-validation.js`):

```javascript
#!/usr/bin/env node

const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

async function main() {
  console.log('🔧 Running my custom validation...');
  
  try {
    // Example: Query database
    const { stdout } = await execPromise(
      `php bakery database:query "SELECT COUNT(*) as count FROM my_table"`
    );
    console.log('✅ Database query result:', stdout.trim());
    
    // Example: Validate API endpoint
    const { stdout: apiResult } = await execPromise(
      `curl -s http://localhost:8080/api/my-endpoint`
    );
    console.log('✅ API response:', apiResult.trim());
    
    // Add your validation logic here
    
    console.log('✅ All custom validations passed!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Custom validation failed:', error.message);
    process.exit(1);
  }
}

main();
```

### 2. Configure in integration-test-config.json

Add your script to the configuration:

```json
{
  "custom_steps": {
    "enabled": true,
    "scripts": [
      {
        "name": "My custom validation",
        "script": ".github/workflow/scripts/my-validation.js",
        "stage": "after_tests",
        "description": "Validates sprinkle-specific business logic"
      }
    ]
  }
}
```

### 3. Regenerate Workflow

```bash
node .github/testing-framework/scripts/generate-workflow.js \
  integration-test-config.json \
  .github/workflows/integration-test.yml
```

Your custom script will be automatically injected at the specified stage!

## Available Stages

- **`before_tests`**: After database seeds, before frontend build
  - Use for: Additional data setup, pre-test configuration
  
- **`after_tests`**: After API/frontend tests, before Playwright installation
  - Use for: Custom API validation, business logic checks
  
- **`before_screenshots`**: After Playwright install, before screenshot capture
  - Use for: UI state preparation, frontend setup
  
- **`after_screenshots`**: After screenshots, before artifact upload
  - Use for: Cleanup, final validation, reporting

## Example: custom-script.js

See `custom-script.js` in this directory for a complete example that demonstrates:
- Database queries via bakery CLI
- Result parsing and validation
- Error handling
- Multiple validation steps

## Tips

- **Use `cd userfrosting` in workflow**: Scripts run from userfrosting directory
- **Access bakery**: `php bakery <command>`
- **Access sprinkle files**: `../\${{ env.SPRINKLE_DIR }}/path/to/file`
- **Error handling**: Always use try/catch and exit codes
- **Logging**: Use console.log for output (visible in GitHub Actions)

## When to Use Custom Scripts

✅ **Good uses:**
- Sprinkle-specific business logic validation
- Custom API endpoint testing
- Specialized database queries
- Integration with external services
- Unique setup/teardown requirements

❌ **Don't use for:**
- Standard CRUD testing (framework handles this)
- Basic API testing (use integration-test-paths.json)
- Database migrations (framework handles this)
- Schema-driven SQL (framework auto-generates)
- Frontend route testing (framework handles this)

## Philosophy

The framework handles 95% of integration testing automatically. Custom scripts provide the 5% flexibility needed for sprinkle-specific requirements while maintaining the simplicity and consistency of JSON-driven configuration.

**Keep it simple. Keep it standard. Extend only when necessary.**
