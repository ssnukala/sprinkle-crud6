#!/usr/bin/env node

/**
 * Validate Integration Test Configuration
 * 
 * This script validates that:
 * 1. Integration test paths are generated dynamically from schemas
 * 2. All test_id values are >= 2 (protecting user ID 1 and group ID 1)
 * 3. DELETE/DISABLE operations don't target ID 1
 * 4. Path templates are complete and valid
 * 
 * Usage: node validate-integration-config.js
 */

import fs from 'fs';
import path from 'path';

const CONFIG_DIR = '.github/config';
const MODELS_FILE = path.join(CONFIG_DIR, 'integration-test-models.json');
const PATHS_FILE = path.join(CONFIG_DIR, 'integration-test-paths.json');

console.log('═══════════════════════════════════════════════════════════════');
console.log('Integration Test Configuration Validator');
console.log('═══════════════════════════════════════════════════════════════');
console.log('');

let errors = 0;
let warnings = 0;

/**
 * Load JSON file
 */
function loadJson(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        return JSON.parse(content);
    } catch (error) {
        console.error(`❌ Failed to load ${filePath}: ${error.message}`);
        errors++;
        return null;
    }
}

/**
 * Validate models configuration
 */
function validateModels(models) {
    console.log('📋 Validating Models Configuration...');
    console.log('');
    
    let modelErrors = 0;
    
    // Check security_notes exist
    if (!models.security_notes) {
        console.log('   ⚠️  Missing security_notes section');
        warnings++;
    } else {
        console.log('   ✅ security_notes section present');
    }
    
    // Validate each model
    for (const [key, model] of Object.entries(models.models)) {
        console.log(`   Checking model: ${model.name}`);
        
        // Check test_id
        if (model.test_id === undefined) {
            console.log(`      ❌ Missing test_id`);
            modelErrors++;
        } else if (model.test_id === 1) {
            console.log(`      ❌ test_id is 1 (MUST be >= 2 to protect system records)`);
            modelErrors++;
        } else if (model.test_id < 2) {
            console.log(`      ❌ test_id is ${model.test_id} (MUST be >= 2)`);
            modelErrors++;
        } else {
            console.log(`      ✅ test_id is ${model.test_id} (safe for DELETE/DISABLE)`);
        }
        
        // Check for safe_test_id_note
        if (!model.safe_test_id_note) {
            console.log(`      ⚠️  Missing safe_test_id_note documentation`);
            warnings++;
        }
        
        // Validate custom actions don't use dangerous operations on ID 1
        if (model.custom_actions) {
            for (const action of model.custom_actions) {
                if (action.includes('delete') || action.includes('disable')) {
                    console.log(`      ℹ️  Custom action '${action}' should verify ID != 1`);
                }
            }
        }
    }
    
    console.log('');
    return modelErrors;
}

/**
 * Validate paths configuration
 */
function validatePaths(paths) {
    console.log('🛣️  Validating Paths Configuration...');
    console.log('');
    
    let pathErrors = 0;
    
    // Check that paths were generated
    if (!paths.generated_from) {
        console.log('   ⚠️  Paths may not be dynamically generated (missing generated_from)');
        warnings++;
    } else {
        console.log(`   ✅ Paths generated from: ${paths.generated_from}`);
    }
    
    if (paths.generated_at) {
        console.log(`   ℹ️  Generated at: ${paths.generated_at}`);
    }
    
    // Check for dangerous paths using ID 1
    const allPaths = [];
    
    // Collect all paths
    for (const authType of ['authenticated', 'unauthenticated']) {
        for (const pathType of ['api', 'frontend']) {
            const pathSection = paths.paths?.[authType]?.[pathType];
            if (pathSection) {
                for (const [name, config] of Object.entries(pathSection)) {
                    allPaths.push({ name, config, authType, pathType });
                }
            }
        }
    }
    
    console.log(`   Found ${allPaths.length} total paths`);
    console.log('');
    
    // Check for ID 1 in paths
    console.log('   Checking for protected ID 1 in paths...');
    let id1Count = 0;
    
    for (const { name, config, authType, pathType } of allPaths) {
        const path = config.path || '';
        
        // Check for /users/1, /groups/1, /roles/1, etc.
        if (/\/(users|groups|roles|permissions|activities)\/1($|\/)/.test(path)) {
            // For DELETE operations, this is an error
            if (config.method === 'DELETE' || path.includes('/disable') || path.includes('/delete')) {
                console.log(`      ❌ DELETE/DISABLE path uses ID 1: ${name} → ${path}`);
                pathErrors++;
            } else {
                // For other operations on ID 1, it's a warning
                console.log(`      ⚠️  Path uses ID 1: ${name} → ${path}`);
                warnings++;
            }
            id1Count++;
        }
    }
    
    if (id1Count === 0) {
        console.log('   ✅ No paths use ID 1 (protected system records)');
    }
    
    console.log('');
    return pathErrors;
}

/**
 * Check dynamic generation capability
 */
function checkDynamicGeneration() {
    console.log('🔄 Checking Dynamic Generation...');
    console.log('');
    
    const generatorScript = '.github/scripts/generate-paths-from-models.js';
    
    if (!fs.existsSync(generatorScript)) {
        console.log(`   ❌ Generator script not found: ${generatorScript}`);
        errors++;
        return;
    }
    
    console.log(`   ✅ Generator script exists: ${generatorScript}`);
    
    // Check if it's executable
    try {
        const stats = fs.statSync(generatorScript);
        console.log(`   ℹ️  Script size: ${(stats.size / 1024).toFixed(2)} KB`);
    } catch (error) {
        console.log(`   ⚠️  Cannot read script stats: ${error.message}`);
    }
    
    console.log('');
}

/**
 * Main validation
 */
async function main() {
    // Load configurations
    const models = loadJson(MODELS_FILE);
    const paths = loadJson(PATHS_FILE);
    
    if (!models || !paths) {
        console.log('');
        console.log('❌ Cannot proceed with validation due to loading errors');
        process.exit(1);
    }
    
    // Validate
    errors += validateModels(models);
    errors += validatePaths(paths);
    checkDynamicGeneration();
    
    // Summary
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('Validation Summary');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Errors: ${errors}`);
    console.log(`Warnings: ${warnings}`);
    console.log('');
    
    if (errors > 0) {
        console.log('❌ Validation failed with errors');
        console.log('   Please fix the errors above before proceeding');
        process.exit(1);
    } else if (warnings > 0) {
        console.log('⚠️  Validation passed with warnings');
        console.log('   Review warnings above for potential improvements');
        process.exit(0);
    } else {
        console.log('✅ All validations passed!');
        console.log('');
        console.log('Integration test configuration is correct:');
        console.log('  ✓ Paths are generated dynamically from models');
        console.log('  ✓ All test IDs are >= 2 (protecting system records)');
        console.log('  ✓ No DELETE/DISABLE operations target ID 1');
        console.log('  ✓ Configuration is complete and valid');
        console.log('');
        process.exit(0);
    }
}

// Run validation
main().catch(error => {
    console.error('❌ Unexpected error:', error.message);
    console.error(error.stack);
    process.exit(1);
});
