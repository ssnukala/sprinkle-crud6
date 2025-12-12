#!/usr/bin/env node

/**
 * Example Custom Script for Integration Testing
 * 
 * This is a template custom script that demonstrates how to add
 * sprinkle-specific testing steps to the integration test workflow.
 * 
 * You can customize this script to:
 * - Run custom database queries
 * - Validate sprinkle-specific business logic
 * - Setup/teardown test data
 * - Call external APIs
 * - Anything Node.js can do!
 * 
 * Usage in integration-test-config.json:
 * {
 *   "custom_steps": {
 *     "enabled": true,
 *     "scripts": [
 *       {
 *         "name": "Custom validation",
 *         "script": ".github/custom-scripts/scripts/custom-script.js",
 *         "stage": "after_tests"
 *       }
 *     ]
 *   }
 * }
 */

const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

async function main() {
  console.log('🔧 Running custom script...');
  
  try {
    // Example 1: Query database to validate data
    console.log('\n📊 Example 1: Validating database records...');
    const { stdout: userCount } = await execPromise(
      `php bakery database:query "SELECT COUNT(*) as count FROM users"`
    );
    console.log('✅ User count query result:', userCount.trim());
    
    // Example 2: Check specific business logic
    console.log('\n🔍 Example 2: Checking CRUD6 models...');
    const { stdout: modelCheck } = await execPromise(
      `php bakery database:query "SELECT model, COUNT(*) as count FROM crud6_models GROUP BY model"`
    );
    console.log('✅ CRUD6 models:', modelCheck.trim());
    
    // Example 3: Validate permissions
    console.log('\n🔐 Example 3: Validating permissions...');
    const { stdout: permCount } = await execPromise(
      `php bakery database:query "SELECT COUNT(*) as count FROM permissions"`
    );
    console.log('✅ Permission count:', permCount.trim());
    
    // Example 4: Custom validation logic
    console.log('\n✨ Example 4: Custom business logic validation...');
    
    // Parse user count to validate threshold
    const countMatch = userCount.match(/count: (\d+)/);
    if (countMatch) {
      const count = parseInt(countMatch[1], 10);
      if (count < 1) {
        throw new Error('❌ Validation failed: Expected at least 1 user in database');
      }
      console.log(`✅ Validation passed: Found ${count} users`);
    }
    
    console.log('\n✅ All custom validations passed!');
    process.exit(0);
    
  } catch (error) {
    console.error('\n❌ Custom script failed:', error.message);
    process.exit(1);
  }
}

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = { main };
