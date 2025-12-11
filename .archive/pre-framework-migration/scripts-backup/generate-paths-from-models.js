#!/usr/bin/env node

/**
 * Generate integration-test-paths.json from model definitions
 * 
 * This script reads model definitions and path templates from integration-test-models.json
 * and generates a complete integration-test-paths.json file with flexible placeholder replacement.
 * 
 * Features:
 * - Automatically detects placeholders in templates ({field}, {actionKey}, {relation}, etc.)
 * - Reads JSON schemas to extract actual field names, actions, and relationships
 * - Generates specific test paths for each placeholder value
 * - Skips paths with unresolved placeholders
 * 
 * Usage: node generate-paths-from-models.js <models_config_file> [output_file] [schema_dir]
 * Example: node generate-paths-from-models.js integration-test-models.json integration-test-paths.json ../examples/schema
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length < 1) {
    console.error('Usage: node generate-paths-from-models.js <models_config_file> [output_file] [schema_dir]');
    console.error('Example: node generate-paths-from-models.js integration-test-models.json integration-test-paths.json ../examples/schema');
    process.exit(1);
}

const modelsConfigFile = args[0];
const outputFile = args[1] || 'integration-test-paths-generated.json';
const schemaDir = args[2] || path.join(__dirname, '../../examples/schema');

console.log('==============================================');
console.log('Generate Integration Test Paths from Models');
console.log('==============================================');
console.log(`Models config: ${modelsConfigFile}`);
console.log(`Output file: ${outputFile}`);
console.log(`Schema directory: ${schemaDir}`);
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
 * Load schema file for a model
 */
function loadSchema(modelName, schemaDirectory) {
    const schemaPath = path.join(schemaDirectory, `${modelName}.json`);
    
    try {
        if (!fs.existsSync(schemaPath)) {
            console.warn(`   ⚠️  Schema file not found: ${schemaPath}`);
            return null;
        }
        
        const schemaContent = fs.readFileSync(schemaPath, 'utf8');
        const schema = JSON.parse(schemaContent);
        return schema;
    } catch (error) {
        console.warn(`   ⚠️  Failed to load schema for ${modelName}: ${error.message}`);
        return null;
    }
}

/**
 * Extract editable fields from schema
 */
function extractEditableFields(schema) {
    if (!schema || !schema.fields) {
        return [];
    }
    
    const editableFields = [];
    
    for (const [fieldName, fieldConfig] of Object.entries(schema.fields)) {
        // Skip readonly and auto-increment fields
        if (fieldConfig.readonly || fieldConfig.auto_increment) {
            continue;
        }
        
        // Skip computed fields
        if (fieldConfig.computed) {
            continue;
        }
        
        // Only include fields that are shown in forms
        if (fieldConfig.show_in && fieldConfig.show_in.includes('form')) {
            editableFields.push(fieldName);
        }
    }
    
    return editableFields;
}

/**
 * Extract actions from schema
 */
function extractActions(schema) {
    if (!schema || !schema.actions) {
        return [];
    }
    
    return schema.actions.map(action => action.key || action.name);
}

/**
 * Extract relationships from schema
 */
function extractRelationships(schema) {
    if (!schema || !schema.relationships) {
        return [];
    }
    
    return schema.relationships.map(rel => rel.name);
}

/**
 * Detect placeholders in a string
 * Returns array of placeholder names found (e.g., ['field', 'actionKey'])
 */
function detectPlaceholders(str) {
    if (typeof str !== 'string') {
        return [];
    }
    
    const placeholderRegex = /\{([^}]+)\}/g;
    const placeholders = [];
    let match;
    
    while ((match = placeholderRegex.exec(str)) !== null) {
        const placeholder = match[1];
        // Only include dynamic placeholders (not model, test_id, etc.)
        if (['field', 'actionKey', 'relation'].includes(placeholder)) {
            if (!placeholders.includes(placeholder)) {
                placeholders.push(placeholder);
            }
        }
    }
    
    return placeholders;
}

/**
 * Get values for a specific placeholder type from schema/model
 */
function getPlaceholderValues(placeholder, model, schema) {
    switch (placeholder) {
        case 'field':
            const fields = extractEditableFields(schema);
            // Return first editable field or null if none available
            return fields.length > 0 ? [fields[0]] : [];
            
        case 'actionKey':
            const actions = extractActions(schema);
            return actions.length > 0 ? actions : [];
            
        case 'relation':
            // First try to get from schema
            const schemaRelations = extractRelationships(schema);
            if (schemaRelations.length > 0) {
                return schemaRelations;
            }
            // Fallback to model config relationships
            if (model.relationships && model.relationships.length > 0) {
                return model.relationships.map(rel => rel.name);
            }
            return [];
            
        default:
            return [];
    }
}

/**
 * Replace template placeholders with actual values
 * This function now handles both static and dynamic placeholders flexibly
 */
function replacePlaceholders(template, model, schema) {
    let result;
    
    if (typeof template === 'string') {
        // First replace static placeholders
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
        result = template.map(item => replacePlaceholders(item, model, schema));
    } else if (typeof template === 'object' && template !== null) {
        result = {};
        for (const [key, value] of Object.entries(template)) {
            result[key] = replacePlaceholders(value, model, schema);
        }
    } else {
        result = template;
    }
    
    return result;
}

/**
 * Generate appropriate payload for a path based on its type and placeholder values
 */
function generatePayload(pathConfig, model, placeholder, value) {
    // Only add payloads for methods that accept them
    if (!['POST', 'PUT'].includes(pathConfig.method)) {
        return pathConfig;
    }
    
    // Skip if payload already exists
    if (pathConfig.payload && Object.keys(pathConfig.payload).length > 0) {
        return pathConfig;
    }
    
    const result = { ...pathConfig };
    
    // Generate payload based on path type
    if (pathConfig.path.includes('/a/')) {
        // Custom action - usually no payload needed
        result.payload = {};
    } else if (placeholder === 'field') {
        // Field update - provide a test value
        result.payload = {
            [value]: generateFieldValue(value, model)
        };
    } else if (placeholder === 'relation' && pathConfig.method === 'POST') {
        // Relationship attach - provide related IDs
        result.payload = {
            [`${value}_ids`]: [1]
        };
    } else if (pathConfig.method === 'POST' && model.create_payload) {
        // Create operation - use model's create_payload
        result.payload = model.create_payload;
    } else if (pathConfig.method === 'PUT' && pathConfig.path.includes(`/${model.test_id}`)) {
        // Update operation - provide a subset of fields
        const updateFields = {};
        if (model.create_payload) {
            // Use first non-ID field from create_payload
            const fields = Object.keys(model.create_payload).filter(f => !f.includes('id'));
            if (fields.length > 0) {
                updateFields[fields[0]] = model.create_payload[fields[0]];
            }
        }
        result.payload = updateFields;
    }
    
    return result;
}

/**
 * Generate a test value for a field
 */
function generateFieldValue(fieldName, model) {
    // Check if field is in create_payload
    if (model.create_payload && model.create_payload[fieldName]) {
        return model.create_payload[fieldName];
    }
    
    // Generate based on field name patterns
    if (fieldName.includes('name') || fieldName === 'slug') {
        return `test_${fieldName}_${Date.now()}`;
    } else if (fieldName.includes('email')) {
        return `test_${Date.now()}@example.com`;
    } else if (fieldName.includes('flag_') || fieldName.includes('enabled') || fieldName.includes('verified')) {
        return true;
    } else if (fieldName.includes('_id')) {
        return 1;
    } else if (fieldName.includes('date') || fieldName.includes('_at')) {
        return new Date().toISOString();
    } else if (fieldName.includes('description') || fieldName.includes('conditions')) {
        return `Test ${fieldName}`;
    } else {
        return `test_value_${Date.now()}`;
    }
}

/**
 * Expand a path template with dynamic placeholders
 * Returns array of expanded paths, one for each placeholder value
 */
function expandPathTemplate(pathName, pathTemplate, model, schema) {
    // Check if template contains dynamic placeholders
    const pathStr = JSON.stringify(pathTemplate);
    const dynamicPlaceholders = detectPlaceholders(pathStr);
    
    if (dynamicPlaceholders.length === 0) {
        // No dynamic placeholders, just replace static ones
        const config = replacePlaceholders(pathTemplate, model, schema);
        // Add payload if needed
        const configWithPayload = generatePayload(config, model, null, null);
        return [{
            name: pathName,
            config: configWithPayload
        }];
    }
    
    // Get values for the first dynamic placeholder
    const placeholder = dynamicPlaceholders[0];
    const values = getPlaceholderValues(placeholder, model, schema);
    
    if (values.length === 0) {
        // No values available for this placeholder, skip this path
        console.log(`   ⏭️  Skipping ${pathName}: No ${placeholder} values available for ${model.name}`);
        return [];
    }
    
    // Expand template for each value
    const expandedPaths = [];
    
    for (const value of values) {
        // Create a copy of the template
        const templateCopy = JSON.parse(JSON.stringify(pathTemplate));
        
        // Replace this placeholder with the actual value
        const replacedTemplate = JSON.parse(
            JSON.stringify(templateCopy).replace(
                new RegExp(`\\{${placeholder}\\}`, 'g'),
                value
            )
        );
        
        // Create a unique name for this expanded path
        const expandedName = `${pathName}_${value}`;
        
        // Add note about which value was used
        if (!replacedTemplate.note) {
            replacedTemplate.note = `Using ${placeholder}='${value}'`;
        } else {
            replacedTemplate.note += ` (${placeholder}='${value}')`;
        }
        
        // Add payload if needed
        const configWithPayload = generatePayload(replacedTemplate, model, placeholder, value);
        
        // Recursively expand any remaining placeholders
        const furtherExpanded = expandPathTemplate(expandedName, configWithPayload, model, schema);
        expandedPaths.push(...furtherExpanded);
    }
    
    return expandedPaths;
}

/**
 * Generate paths for a single model
 */
function generatePathsForModel(model, templates, schema) {
    const paths = {
        authenticated: { api: {}, frontend: {} },
        unauthenticated: { api: {}, frontend: {} }
    };
    
    // Authenticated API paths
    for (const [pathType, pathTemplate] of Object.entries(templates.authenticated.api)) {
        const baseName = `${model.name}_${pathType}`;
        const expandedPaths = expandPathTemplate(baseName, pathTemplate, model, schema);
        
        for (const { name, config } of expandedPaths) {
            paths.authenticated.api[name] = config;
        }
    }
    
    // Authenticated frontend paths
    for (const [pathType, pathTemplate] of Object.entries(templates.authenticated.frontend)) {
        const baseName = `${model.name}_${pathType}`;
        const expandedPaths = expandPathTemplate(baseName, pathTemplate, model, schema);
        
        for (const { name, config } of expandedPaths) {
            paths.authenticated.frontend[name] = config;
        }
    }
    
    // Unauthenticated API paths
    for (const [pathType, pathTemplate] of Object.entries(templates.unauthenticated.api)) {
        const baseName = `${model.name}_${pathType}`;
        const expandedPaths = expandPathTemplate(baseName, pathTemplate, model, schema);
        
        for (const { name, config } of expandedPaths) {
            paths.unauthenticated.api[name] = config;
        }
    }
    
    // Unauthenticated frontend paths
    for (const [pathType, pathTemplate] of Object.entries(templates.unauthenticated.frontend)) {
        const baseName = `${model.name}_${pathType}`;
        const expandedPaths = expandPathTemplate(baseName, pathTemplate, model, schema);
        
        for (const { name, config } of expandedPaths) {
            paths.unauthenticated.frontend[name] = config;
        }
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
    
    // Load schema for this model
    const schema = loadSchema(model.name, schemaDir);
    if (schema) {
        console.log(`  📄 ${model.name}: Loaded schema`);
    } else {
        console.log(`  ⚠️  ${model.name}: No schema found, using model config only`);
    }
    
    const modelPaths = generatePathsForModel(model, modelsConfig.path_templates, schema);
    mergePaths(output, modelPaths);
    
    const modelPathCount = 
        Object.keys(modelPaths.authenticated.api).length +
        Object.keys(modelPaths.authenticated.frontend).length +
        Object.keys(modelPaths.unauthenticated.api).length +
        Object.keys(modelPaths.unauthenticated.frontend).length;
    
    console.log(`     ✅ Generated ${modelPathCount} paths`);
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
