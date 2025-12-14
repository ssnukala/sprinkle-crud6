#!/usr/bin/env node

/**
 * Generate integration-test-paths.json from CRUD6 schema files
 * 
 * This script dynamically generates the complete integration test paths configuration
 * by reading schema JSON files and using path templates from integration-test-models.json.
 * 
 * Features:
 * - Reads all schema files from specified directory
 * - Extracts model information (name, relationships, actions, fields)
 * - Applies path templates to generate all CRUD operation paths
 * - Generates payloads from schema validation rules
 * - Creates both authenticated and unauthenticated test paths
 * 
 * Usage: node generate-integration-test-paths.js [schema_directory] [output_file] [templates_file]
 * Example: node generate-integration-test-paths.js examples/schema .github/config/integration-test-paths.json
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);
const DEFAULT_SCHEMA_DIR = 'examples/schema';
const DEFAULT_OUTPUT_FILE = '.github/config/integration-test-paths.json';
const DEFAULT_TEMPLATES_FILE = '.github/config/integration-test-models.json';

const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
const outputFile = args[1] || DEFAULT_OUTPUT_FILE;
const templatesFile = args[2] || DEFAULT_TEMPLATES_FILE;

console.log('═══════════════════════════════════════════════════════════════');
console.log('CRUD6 Integration Test Paths Generator');
console.log('═══════════════════════════════════════════════════════════════');
console.log(`Schema directory: ${schemaDir}`);
console.log(`Output file: ${outputFile}`);
console.log(`Templates file: ${templatesFile}`);
console.log('');

// Constants for this CRUD6 sprinkle
const API_PREFIX = '/api/crud6';
const FRONTEND_PREFIX = '/crud6';
const TEST_ID = 100; // Use ID 100+ for test data

/**
 * Load path templates from integration-test-models.json
 */
function loadTemplates() {
    if (!fs.existsSync(templatesFile)) {
        console.log(`⚠️  Templates file not found: ${templatesFile}`);
        console.log('   Using default templates...');
        return getDefaultTemplates();
    }
    
    const content = fs.readFileSync(templatesFile, 'utf8');
    const config = JSON.parse(content);
    return config.path_templates || getDefaultTemplates();
}

/**
 * Default templates if file not found
 */
function getDefaultTemplates() {
    return {
        authenticated: {
            api: {
                schema: {
                    method: "GET",
                    path: "{api_prefix}/{model}/schema",
                    description: "Get {model} schema definition",
                    expected_status: 200,
                    validation: { type: "json", contains: ["model", "fields"] }
                },
                list: {
                    method: "GET",
                    path: "{api_prefix}/{model}",
                    description: "Get list of {model} via CRUD6 API",
                    expected_status: 200,
                    validation: { type: "json", contains: ["rows"] }
                },
                create: {
                    method: "POST",
                    path: "{api_prefix}/{model}",
                    description: "Create new {singular} via CRUD6 API",
                    expected_status: 200,
                    acceptable_statuses: [200, 201],
                    validation: { type: "json", contains: ["data", "id"] },
                    requires_permission: "create_{singular}",
                    note: "Requires payload with model-specific fields. HTTP 201 Created is also accepted."
                },
                single: {
                    method: "GET",
                    path: "{api_prefix}/{model}/{test_id}",
                    description: "Get single {singular} by ID via CRUD6 API",
                    expected_status: 200,
                    validation: { type: "json", contains: ["id"] }
                },
                update: {
                    method: "PUT",
                    path: "{api_prefix}/{model}/{test_id}",
                    description: "Update {singular} via CRUD6 API",
                    expected_status: 200,
                    validation: { type: "json" },
                    requires_permission: "update_{singular}_field",
                    note: "Requires payload with fields to update"
                },
                delete: {
                    method: "DELETE",
                    path: "{api_prefix}/{model}/{test_id}",
                    description: "Delete {singular} via CRUD6 API",
                    expected_status: 200,
                    validation: { type: "json" },
                    requires_permission: "delete_{singular}"
                }
            }
        }
    };
}

/**
 * Extract model information from schema
 */
function extractModelInfo(schema) {
    const modelName = schema.model;
    const tableName = schema.table || modelName;
    const titleField = schema.title_field || 'name';
    const fields = schema.fields || {};
    const relationships = schema.relationships || [];
    const actions = schema.actions || [];
    
    // Determine singular form (simple approach: remove trailing 's')
    let singular = modelName;
    if (modelName.endsWith('ies')) {
        singular = modelName.slice(0, -3) + 'y';
    } else if (modelName.endsWith('s') && !modelName.endsWith('ss')) {
        singular = modelName.slice(0, -1);
    }
    
    // Find unique fields for validation
    const uniqueFields = [];
    const apiValidationKeys = ['id'];
    for (const [fieldName, field] of Object.entries(fields)) {
        if (field.validation?.unique) {
            uniqueFields.push(fieldName);
            if (apiValidationKeys.length < 3) {
                apiValidationKeys.push(fieldName);
            }
        }
    }
    
    // Extract field toggles (boolean fields that can be toggled)
    const fieldToggles = [];
    for (const [fieldName, field] of Object.entries(fields)) {
        if (field.type === 'boolean' && !field.readonly) {
            fieldToggles.push(fieldName);
        }
    }
    
    // Extract custom action keys
    const customActions = actions.map(action => action.key);
    
    return {
        name: modelName,
        singular,
        table: tableName,
        title_field: titleField,
        api_validation_keys: apiValidationKeys,
        relationships,
        custom_actions: customActions,
        field_toggles: fieldToggles,
        fields
    };
}

/**
 * Replace placeholders in template string
 */
function replacePlaceholders(template, replacements) {
    let result = template;
    for (const [key, value] of Object.entries(replacements)) {
        const regex = new RegExp(`\\{${key}\\}`, 'g');
        result = result.replace(regex, value);
    }
    return result;
}

/**
 * Generate paths for a single model
 * 
 * @param {object} modelInfo - Model information extracted from schema
 * @param {object} templates - Path templates from integration-test-models.json
 * @param {object} testPayloads - Generated test payloads for create/update operations
 * @returns {{api: object, frontend: object}} Object containing both API and frontend paths
 */
function generateModelPaths(modelInfo, templates, testPayloads) {
    const apiPaths = {};
    const frontendPaths = {};
    const modelName = modelInfo.name;
    const singular = modelInfo.singular;
    
    const replacements = {
        api_prefix: API_PREFIX,
        frontend_prefix: FRONTEND_PREFIX,
        model: modelName,
        singular: singular,
        test_id: TEST_ID
    };
    
    // ========================================
    // Generate API paths
    // ========================================
    
    // Generate basic CRUD paths
    for (const [templateKey, template] of Object.entries(templates.authenticated.api)) {
        if (templateKey === 'custom_action' || templateKey === 'relationship_attach' || 
            templateKey === 'relationship_detach' || templateKey === 'nested_relationship' ||
            templateKey === 'update_field') {
            continue; // Handle these separately
        }
        
        const pathKey = `${modelName}_${templateKey}`;
        const pathConfig = JSON.parse(JSON.stringify(template)); // Deep clone
        
        // Replace placeholders in path
        pathConfig.path = replacePlaceholders(pathConfig.path, replacements);
        
        // Replace placeholders in description
        if (pathConfig.description) {
            pathConfig.description = replacePlaceholders(pathConfig.description, replacements);
        }
        
        // Replace placeholders in note
        if (pathConfig.note) {
            pathConfig.note = replacePlaceholders(pathConfig.note, replacements);
        }
        
        // Replace placeholders in requires_permission
        if (pathConfig.requires_permission) {
            pathConfig.requires_permission = replacePlaceholders(pathConfig.requires_permission, replacements);
        }
        
        // Add payload for create and update operations
        if (templateKey === 'create' && testPayloads.create_payloads?.[0]) {
            pathConfig.payload = testPayloads.create_payloads[0].payload;
        } else if (templateKey === 'update' && testPayloads.update_payloads?.[0]) {
            pathConfig.payload = testPayloads.update_payloads[0].payload;
        }
        
        apiPaths[pathKey] = pathConfig;
    }
    
    // Generate update_field paths for unique fields
    if (templates.authenticated.api.update_field) {
        for (const [fieldName, field] of Object.entries(modelInfo.fields)) {
            if (field.validation?.unique && !field.readonly && !field.auto_increment) {
                const pathKey = `${modelName}_update_field_${fieldName}`;
                const pathConfig = JSON.parse(JSON.stringify(templates.authenticated.api.update_field));
                
                const fieldReplacements = { ...replacements, field: fieldName };
                pathConfig.path = replacePlaceholders(pathConfig.path, fieldReplacements);
                pathConfig.description = replacePlaceholders(pathConfig.description, fieldReplacements);
                pathConfig.note = replacePlaceholders(pathConfig.note, fieldReplacements);
                
                if (pathConfig.requires_permission) {
                    pathConfig.requires_permission = replacePlaceholders(pathConfig.requires_permission, replacements);
                }
                
                // Add payload with just this field
                if (testPayloads.update_payloads?.[0]?.payload[fieldName]) {
                    pathConfig.payload = {
                        [fieldName]: testPayloads.update_payloads[0].payload[fieldName]
                    };
                }
                
                apiPaths[pathKey] = pathConfig;
            }
        }
    }
    
    // Generate custom action paths
    if (templates.authenticated.api.custom_action && modelInfo.custom_actions.length > 0) {
        for (const actionKey of modelInfo.custom_actions) {
            const pathKey = `${modelName}_custom_action_${actionKey}`;
            const pathConfig = JSON.parse(JSON.stringify(templates.authenticated.api.custom_action));
            
            const actionReplacements = { ...replacements, actionKey };
            pathConfig.path = replacePlaceholders(pathConfig.path, actionReplacements);
            pathConfig.description = replacePlaceholders(pathConfig.description, actionReplacements);
            pathConfig.note = replacePlaceholders(pathConfig.note, actionReplacements);
            
            pathConfig.payload = {};
            apiPaths[pathKey] = pathConfig;
        }
    }
    
    // Generate relationship paths
    for (const relationship of modelInfo.relationships) {
        const relationName = relationship.name;
        const relationType = relationship.type;
        
        // Only generate attach/detach for many_to_many
        if (relationType === 'many_to_many') {
            // Attach
            if (templates.authenticated.api.relationship_attach) {
                const pathKey = `${modelName}_relationship_attach_${relationName}`;
                const pathConfig = JSON.parse(JSON.stringify(templates.authenticated.api.relationship_attach));
                
                const relReplacements = { ...replacements, relation: relationName };
                pathConfig.path = replacePlaceholders(pathConfig.path, relReplacements);
                pathConfig.description = replacePlaceholders(pathConfig.description, relReplacements);
                pathConfig.note = replacePlaceholders(pathConfig.note, relReplacements);
                
                // Add relationship payload
                if (testPayloads.relationship_payloads?.[relationName]?.attach) {
                    pathConfig.payload = testPayloads.relationship_payloads[relationName].attach;
                }
                
                apiPaths[pathKey] = pathConfig;
            }
            
            // Detach
            if (templates.authenticated.api.relationship_detach) {
                const pathKey = `${modelName}_relationship_detach_${relationName}`;
                const pathConfig = JSON.parse(JSON.stringify(templates.authenticated.api.relationship_detach));
                
                const relReplacements = { ...replacements, relation: relationName };
                pathConfig.path = replacePlaceholders(pathConfig.path, relReplacements);
                pathConfig.description = replacePlaceholders(pathConfig.description, relReplacements);
                pathConfig.note = replacePlaceholders(pathConfig.note, relReplacements);
                
                // Detach usually has no payload or empty ids array
                apiPaths[pathKey] = pathConfig;
            }
        }
        
        // Generate nested relationship GET endpoint for all relationship types
        if (templates.authenticated.api.nested_relationship) {
            const pathKey = `${modelName}_nested_relationship_${relationName}`;
            const pathConfig = JSON.parse(JSON.stringify(templates.authenticated.api.nested_relationship));
            
            const relReplacements = { ...replacements, relation: relationName };
            pathConfig.path = replacePlaceholders(pathConfig.path, relReplacements);
            pathConfig.description = replacePlaceholders(pathConfig.description, relReplacements);
            pathConfig.note = replacePlaceholders(pathConfig.note, relReplacements);
            
            apiPaths[pathKey] = pathConfig;
        }
    }
    
    // ========================================
    // Generate frontend paths
    // ========================================
    
    // If frontend templates are not defined or empty, frontendPaths will remain empty
    // This is normal for API-only models or when templates are not configured
    if (templates.authenticated.frontend) {
        for (const [templateKey, template] of Object.entries(templates.authenticated.frontend)) {
            const pathKey = `${modelName}_${templateKey}`;
            // Deep clone template to avoid mutation
            // Note: Using JSON.parse(JSON.stringify()) is simple and sufficient for JSON-serializable data
            const pathConfig = JSON.parse(JSON.stringify(template));
            
            // Replace placeholders in path
            pathConfig.path = replacePlaceholders(pathConfig.path, replacements);
            
            // Replace placeholders in description
            if (pathConfig.description) {
                pathConfig.description = replacePlaceholders(pathConfig.description, replacements);
            }
            
            // Replace placeholders in screenshot_name
            if (pathConfig.screenshot_name) {
                pathConfig.screenshot_name = replacePlaceholders(pathConfig.screenshot_name, replacements);
            }
            
            frontendPaths[pathKey] = pathConfig;
        }
    }
    
    return { api: apiPaths, frontend: frontendPaths };
}

/**
 * Generate test payloads from schema (reuse logic from generate-seed-sql.js)
 */
function generateTestPayloads(schema) {
    const modelName = schema.model;
    const fields = schema.fields || {};
    const relationships = schema.relationships || [];
    
    const testData = {
        create_payloads: [],
        update_payloads: [],
        relationship_payloads: {}
    };
    
    // Generate create payload for ID 100
    const createPayload = {};
    for (const [fieldName, field] of Object.entries(fields)) {
        if (field.auto_increment || field.readonly || field.computed) {
            continue;
        }
        
        const showIn = field.show_in || [];
        if (showIn.length > 0 && !showIn.includes('form') && !showIn.includes('create')) {
            continue;
        }
        
        const value = generateFieldValue(fieldName, field, 100);
        if (value !== null) {
            createPayload[fieldName] = value;
        }
    }
    
    testData.create_payloads.push({ id: 100, payload: createPayload });
    
    // Generate update payload (subset of fields)
    const updatePayload = {};
    let fieldCount = 0;
    for (const [fieldName, field] of Object.entries(fields)) {
        if (field.auto_increment || field.readonly || field.computed || 
            fieldName === 'id' || fieldName === 'created_at' || fieldName === 'updated_at') {
            continue;
        }
        
        if (fieldCount < 2) {
            const value = generateFieldValue(fieldName, field, 100);
            if (value !== null) {
                updatePayload[fieldName] = value;
                fieldCount++;
            }
        }
    }
    
    if (Object.keys(updatePayload).length > 0) {
        testData.update_payloads.push({ id: 100, payload: updatePayload });
    }
    
    // Generate relationship payloads
    for (const rel of relationships) {
        if (rel.type === 'many_to_many') {
            testData.relationship_payloads[rel.name] = {
                attach: { ids: [100, 101] },
                detach: { ids: [101] }
            };
        }
    }
    
    return testData;
}

/**
 * Generate field value based on type and validation
 */
function generateFieldValue(fieldName, field, recordId) {
    const type = field.type;
    const validation = field.validation || {};
    const maxLength = validation.length?.max;
    
    if (field.default !== undefined && field.default !== null) {
        return field.default;
    }
    
    switch (type) {
        case 'integer':
        case 'foreign_key':
        case 'lookup':
            return 1;
            
        case 'email':
            return `test${recordId}@example.com`;
            
        case 'string':
            if (validation.email) {
                return `test${recordId}@example.com`;
            }
            if (validation.unique) {
                const value = `test_${fieldName}_${recordId}`;
                return maxLength ? value.substring(0, maxLength) : value;
            }
            return `Name${recordId}`;
            
        case 'text':
            return `Test description for ${fieldName} - Record ${recordId}`;
            
        case 'boolean':
            return true;
            
        case 'password':
            return '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            
        default:
            return null;
    }
}

/**
 * Main generation function
 */
function generateIntegrationTestPaths() {
    console.log('📂 Loading schemas...');
    
    // Load templates
    const templates = loadTemplates();
    
    // Load all schema files
    const files = fs.readdirSync(schemaDir);
    const jsonFiles = files.filter(f => f.endsWith('.json'));
    
    console.log(`   Found ${jsonFiles.length} schema files`);
    console.log('');
    
    const allPaths = {
        description: "Integration test paths configuration for CRUD6 sprinkle (auto-generated from schemas)",
        generated_from: "Schema JSON files",
        generated_at: new Date().toISOString(),
        note: "All test data IDs use range 100+ (IDs 1-99 reserved for PHP seed data)",
        config: {
            base_url: "http://localhost:8080",
            auth: {
                username: "admin",
                password: "admin123"
            },
            api_patterns: {
                main_api: API_PREFIX + "/",
                schema_api: API_PREFIX + "/{model}/schema"
            }
        },
        paths: {
            authenticated: {
                api: {},
                frontend: {}
            },
            unauthenticated: {
                api: {},
                frontend: {}
            }
        }
    };
    
    // Process each schema
    for (const file of jsonFiles) {
        try {
            const filePath = path.join(schemaDir, file);
            const content = fs.readFileSync(filePath, 'utf8');
            const schema = JSON.parse(content);
            
            const modelInfo = extractModelInfo(schema);
            const testPayloads = generateTestPayloads(schema);
            
            console.log(`   ✅ Processing: ${file} (model: ${modelInfo.name})`);
            
            // Generate authenticated paths
            const modelPaths = generateModelPaths(modelInfo, templates, testPayloads);
            Object.assign(allPaths.paths.authenticated.api, modelPaths.api);
            Object.assign(allPaths.paths.authenticated.frontend, modelPaths.frontend);
            
        } catch (error) {
            console.error(`   ❌ Failed to process ${file}: ${error.message}`);
        }
    }
    
    console.log('');
    console.log(`✅ Generated ${Object.keys(allPaths.paths.authenticated.api).length} authenticated API paths`);
    console.log(`✅ Generated ${Object.keys(allPaths.paths.authenticated.frontend).length} authenticated frontend paths`);
    console.log('');
    
    return allPaths;
}

// Main execution
try {
    // Ensure output directory exists
    const outputDir = path.dirname(outputFile);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
        console.log(`📁 Created output directory: ${outputDir}`);
        console.log('');
    }
    
    // Generate paths
    const paths = generateIntegrationTestPaths();
    
    // Write to file
    fs.writeFileSync(outputFile, JSON.stringify(paths, null, 2), 'utf8');
    
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('✅ Integration test paths generated successfully!');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Output file: ${outputFile}`);
    console.log(`File size: ${(JSON.stringify(paths).length / 1024).toFixed(2)} KB`);
    console.log('');
    console.log('Summary:');
    console.log(`  - Models processed: ${Object.keys(paths.paths.authenticated.api).filter(k => k.endsWith('_schema')).length}`);
    console.log(`  - Total API paths: ${Object.keys(paths.paths.authenticated.api).length}`);
    console.log(`  - Total frontend paths: ${Object.keys(paths.paths.authenticated.frontend).length}`);
    console.log(`  - Frontend paths with screenshots: ${Object.values(paths.paths.authenticated.frontend).filter(p => p.screenshot).length}`);
    console.log('');
    
    process.exit(0);
} catch (error) {
    console.error('❌ Error:', error.message);
    console.error(error.stack);
    process.exit(1);
}
