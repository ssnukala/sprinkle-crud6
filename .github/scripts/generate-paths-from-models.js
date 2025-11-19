#!/usr/bin/env node

/**
 * Generate integration-test-paths.json from model definitions
 * 
 * This script reads model definitions and path templates from integration-test-models.json
 * and generates a complete integration-test-paths.json file.
 * 
 * Usage: node generate-paths-from-models.js <models_config_file> [output_file]
 * Example: node generate-paths-from-models.js integration-test-models.json integration-test-paths.json
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length < 1) {
    console.error('Usage: node generate-paths-from-models.js <models_config_file> [output_file]');
    console.error('Example: node generate-paths-from-models.js integration-test-models.json integration-test-paths.json');
    process.exit(1);
}

const modelsConfigFile = args[0];
const outputFile = args[1] || 'integration-test-paths-generated.json';

console.log('==============================================');
console.log('Generate Integration Test Paths from Models');
console.log('==============================================');
console.log(`Models config: ${modelsConfigFile}`);
console.log(`Output file: ${outputFile}`);
console.log('');

// Load models configuration
let modelsConfig;
try {
    const configContent = fs.readFileSync(modelsConfigFile, 'utf8');
    modelsConfig = JSON.parse(configContent);
    console.log(`✅ Loaded models configuration`);
} catch (error) {
    console.error(`❌ Failed to load models configuration: ${error.message}`);
    process.exit(1);
}

/**
 * Replace template placeholders with actual values
 */
function replacePlaceholders(template, model) {
    let result;
    
    if (typeof template === 'string') {
        result = template
            .replace(/{model}/g, model.name)
            .replace(/{singular}/g, model.singular)
            .replace(/{api_prefix}/g, model.api_prefix)
            .replace(/{frontend_prefix}/g, model.frontend_prefix)
            .replace(/{test_id}/g, model.test_id)
            .replace(/{api_validation_keys}/g, JSON.stringify(model.api_validation_keys))
            .replace(/{list_validation_keys}/g, JSON.stringify(model.list_validation_keys));
        
        // If result is a JSON string, parse it
        if (result.startsWith('[') || result.startsWith('{')) {
            try {
                result = JSON.parse(result);
            } catch (e) {
                // Keep as string if not valid JSON
            }
        }
    } else if (Array.isArray(template)) {
        result = template.map(item => replacePlaceholders(item, model));
    } else if (typeof template === 'object' && template !== null) {
        result = {};
        for (const [key, value] of Object.entries(template)) {
            result[key] = replacePlaceholders(value, model);
        }
    } else {
        result = template;
    }
    
    return result;
}

/**
 * Generate paths for a single model
 */
function generatePathsForModel(model, templates) {
    const paths = {
        authenticated: { api: {}, frontend: {} },
        unauthenticated: { api: {}, frontend: {} }
    };
    
    // Authenticated API paths
    for (const [pathType, pathTemplate] of Object.entries(templates.authenticated.api)) {
        const pathName = `${model.name}_${pathType}`;
        paths.authenticated.api[pathName] = replacePlaceholders(pathTemplate, model);
    }
    
    // Authenticated frontend paths
    for (const [pathType, pathTemplate] of Object.entries(templates.authenticated.frontend)) {
        const pathName = `${model.name}_${pathType}`;
        paths.authenticated.frontend[pathName] = replacePlaceholders(pathTemplate, model);
    }
    
    // Unauthenticated API paths
    for (const [pathType, pathTemplate] of Object.entries(templates.unauthenticated.api)) {
        const pathName = `${model.name}_${pathType}`;
        paths.unauthenticated.api[pathName] = replacePlaceholders(pathTemplate, model);
    }
    
    // Unauthenticated frontend paths
    for (const [pathType, pathTemplate] of Object.entries(templates.unauthenticated.frontend)) {
        const pathName = `${model.name}_${pathType}`;
        paths.unauthenticated.frontend[pathName] = replacePlaceholders(pathTemplate, model);
    }
    
    return paths;
}

/**
 * Merge paths from multiple models
 */
function mergePaths(allPaths, modelPaths) {
    for (const authType of ['authenticated', 'unauthenticated']) {
        for (const pathCategory of ['api', 'frontend']) {
            Object.assign(
                allPaths.paths[authType][pathCategory],
                modelPaths[authType][pathCategory]
            );
        }
    }
}

// Initialize output structure
const output = {
    description: "Integration test paths configuration for CRUD6 sprinkle (auto-generated from models)",
    generated_from: modelsConfigFile,
    generated_at: new Date().toISOString(),
    paths: {
        authenticated: { api: {}, frontend: {} },
        unauthenticated: { api: {}, frontend: {} }
    },
    config: modelsConfig.config
};

// Generate paths for each enabled model
let modelCount = 0;
let pathCount = 0;

console.log('Generating paths for models:');
for (const [modelKey, model] of Object.entries(modelsConfig.models)) {
    if (!model.enabled) {
        console.log(`  ⊘ ${model.name} (disabled)`);
        continue;
    }
    
    const modelPaths = generatePathsForModel(model, modelsConfig.path_templates);
    mergePaths(output, modelPaths);
    
    const modelPathCount = 
        Object.keys(modelPaths.authenticated.api).length +
        Object.keys(modelPaths.authenticated.frontend).length +
        Object.keys(modelPaths.unauthenticated.api).length +
        Object.keys(modelPaths.unauthenticated.frontend).length;
    
    console.log(`  ✅ ${model.name} (${modelPathCount} paths)`);
    modelCount++;
    pathCount += modelPathCount;
}

console.log('');
console.log('Summary:');
console.log(`  Models processed: ${modelCount}`);
console.log(`  Total paths generated: ${pathCount}`);
console.log(`    Authenticated API: ${Object.keys(output.paths.authenticated.api).length}`);
console.log(`    Authenticated Frontend: ${Object.keys(output.paths.authenticated.frontend).length}`);
console.log(`    Unauthenticated API: ${Object.keys(output.paths.unauthenticated.api).length}`);
console.log(`    Unauthenticated Frontend: ${Object.keys(output.paths.unauthenticated.frontend).length}`);

// Save output
try {
    fs.writeFileSync(outputFile, JSON.stringify(output, null, 2), 'utf8');
    console.log('');
    console.log(`✅ Paths configuration saved to: ${outputFile}`);
    console.log(`   File size: ${(JSON.stringify(output).length / 1024).toFixed(2)} KB`);
} catch (error) {
    console.error(`❌ Failed to save output: ${error.message}`);
    process.exit(1);
}

console.log('');
console.log('==============================================');
console.log('✅ Path generation complete!');
console.log('==============================================');

process.exit(0);
