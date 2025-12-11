#!/usr/bin/env node

/**
 * Generate integration-test-paths.json directly from CRUD6 schema files
 * 
 * This script reads JSON schema files and automatically generates integration test paths
 * without needing an intermediary configuration file. It extracts all necessary information
 * directly from the schema definitions.
 * 
 * Features:
 * - Reads schema files directly from examples/schema/ or app/schema/crud6/
 * - Automatically detects models, relationships, and custom actions
 * - Generates authenticated and unauthenticated API/frontend paths
 * - Respects security constraints (test_id starts from 2)
 * - Creates comprehensive test coverage from schema metadata
 * 
 * Usage: node generate-paths-from-schema.js [schema_directory] [output_file]
 * Example: node generate-paths-from-schema.js examples/schema .github/config/integration-test-paths.json
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);
const DEFAULT_SCHEMA_DIR = 'examples/schema';
const DEFAULT_OUTPUT_FILE = '.github/config/integration-test-paths.json';

const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
const outputFile = args[1] || DEFAULT_OUTPUT_FILE;

console.log('═══════════════════════════════════════════════════════════════');
console.log('Generate Integration Test Paths from Schema Files');
console.log('═══════════════════════════════════════════════════════════════');
console.log(`Schema directory: ${schemaDir}`);
console.log(`Output file: ${outputFile}`);
console.log('');

/**
 * Load all schema files from directory
 */
function loadSchemas(directory) {
    const schemas = [];
    const files = fs.readdirSync(directory);
    const jsonFiles = files.filter(f => f.endsWith('.json'));
    
    console.log(`📂 Found ${jsonFiles.length} schema files`);
    console.log('');
    
    for (const file of jsonFiles) {
        try {
            const filePath = path.join(directory, file);
            const content = fs.readFileSync(filePath, 'utf8');
            const schema = JSON.parse(content);
            
            // Skip if not a valid CRUD6 schema
            if (!schema.model || !schema.table) {
                console.log(`   ⊘ Skipping ${file} (not a CRUD6 schema)`);
                continue;
            }
            
            schemas.push({
                filename: file,
                model: schema.model,
                schema: schema
            });
            
            console.log(`   ✅ Loaded: ${file} (model: ${schema.model})`);
        } catch (error) {
            console.error(`   ❌ Failed to load ${file}: ${error.message}`);
        }
    }
    
    console.log('');
    console.log(`✅ Loaded ${schemas.length} valid CRUD6 schemas`);
    console.log('');
    
    return schemas;
}

/**
 * Extract model metadata from schema
 */
function extractModelMetadata(schema) {
    const metadata = {
        name: schema.model,
        singular: schema.singular_title || schema.model.replace(/s$/, ''), // Remove trailing 's' as fallback
        table: schema.table,
        primary_key: schema.primary_key || 'id',
        api_prefix: '/api/crud6',
        frontend_prefix: '/crud6',
        test_id: 2, // Always use 2 to protect system records
        relationships: [],
        custom_actions: [],
        field_toggles: [],
        details: [],
        permissions: schema.permissions || {}
    };
    
    // Extract relationships - these create nested endpoints
    if (schema.relationships) {
        metadata.relationships = schema.relationships.map(rel => ({
            name: rel.name,
            type: rel.type,
            pivot_table: rel.pivot_table,
            nested_endpoint: true
        }));
    }
    
    // Extract custom actions from top-level "actions" array
    if (schema.actions && Array.isArray(schema.actions)) {
        metadata.custom_actions = schema.actions.map(action => ({
            key: action.key,
            type: action.type,
            method: action.method || 'POST',
            field: action.field,
            permission: action.permission
        }));
    }
    
    // Extract details (nested models shown on detail page)
    if (schema.details && Array.isArray(schema.details)) {
        metadata.details = schema.details.map(detail => ({
            model: detail.model,
            foreign_key: detail.foreign_key,
            list_fields: detail.list_fields || []
        }));
    }
    
    // Extract field toggles (boolean fields that can be toggled)
    if (schema.fields) {
        const fields = Array.isArray(schema.fields) 
            ? schema.fields 
            : Object.entries(schema.fields).map(([name, field]) => ({name, ...field}));
        
        for (const field of fields) {
            const fieldName = field.name || Object.keys(field)[0];
            const fieldDef = field.name ? field : field[fieldName];
            
            if (fieldDef && fieldDef.type === 'boolean' && !fieldDef.readonly) {
                metadata.field_toggles.push(fieldName);
            }
        }
    }
    
    // Determine validation keys from schema fields
    metadata.api_validation_keys = [metadata.primary_key];
    metadata.list_validation_keys = ['rows'];
    
    // Add key fields to validation
    if (schema.title_field) {
        metadata.api_validation_keys.push(schema.title_field);
    }
    
    // Add common fields
    const commonFields = ['name', 'slug', 'email', 'user_name'];
    if (schema.fields) {
        const fields = Array.isArray(schema.fields) ? schema.fields : Object.keys(schema.fields);
        for (const field of fields) {
            const fieldName = typeof field === 'string' ? field : field.name;
            if (commonFields.includes(fieldName) && !metadata.api_validation_keys.includes(fieldName)) {
                metadata.api_validation_keys.push(fieldName);
            }
        }
    }
    
    return metadata;
}

/**
 * Generate paths for a single model
 */
function generateModelPaths(metadata) {
    const paths = {
        authenticated: { api: {}, frontend: {} },
        unauthenticated: { api: {}, frontend: {} }
    };
    
    const { name, singular, api_prefix, frontend_prefix, test_id, relationships, custom_actions, field_toggles, details } = metadata;
    
    // Authenticated API paths
    paths.authenticated.api[`${name}_schema`] = {
        method: 'GET',
        path: `${api_prefix}/${name}/schema`,
        description: `Get ${name} schema definition`,
        expected_status: 200,
        validation: {
            type: 'json',
            contains: ['model', 'fields']
        }
    };
    
    paths.authenticated.api[`${name}_list`] = {
        method: 'GET',
        path: `${api_prefix}/${name}`,
        description: `Get list of ${name} via CRUD6 API`,
        expected_status: 200,
        validation: {
            type: 'json',
            contains: metadata.list_validation_keys
        }
    };
    
    paths.authenticated.api[`${name}_create`] = {
        method: 'POST',
        path: `${api_prefix}/${name}`,
        description: `Create new ${singular} via CRUD6 API`,
        expected_status: 200,
        validation: {
            type: 'json',
            contains: ['data', 'id']
        },
        requires_permission: `create_${singular}`,
        note: 'Requires payload with model-specific fields'
    };
    
    paths.authenticated.api[`${name}_single`] = {
        method: 'GET',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Get single ${singular} by ID via CRUD6 API`,
        expected_status: 200,
        validation: {
            type: 'json',
            contains: metadata.api_validation_keys
        }
    };
    
    paths.authenticated.api[`${name}_update`] = {
        method: 'PUT',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Update ${singular} via CRUD6 API`,
        expected_status: 200,
        validation: {
            type: 'json'
        },
        requires_permission: `update_${singular}_field`,
        note: 'Requires payload with fields to update'
    };
    
    paths.authenticated.api[`${name}_update_field`] = {
        method: 'PUT',
        path: `${api_prefix}/${name}/${test_id}/{field}`,
        description: `Update single field for ${singular}`,
        expected_status: 200,
        validation: {
            type: 'json'
        },
        requires_permission: `update_${singular}_field`,
        note: 'Field name and value depend on model schema'
    };
    
    // Custom actions - generate specific test for each action defined in schema
    if (custom_actions && custom_actions.length > 0) {
        for (const action of custom_actions) {
            paths.authenticated.api[`${name}_action_${action.key}`] = {
                method: action.method || 'POST',
                path: `${api_prefix}/${name}/${test_id}/a/${action.key}`,
                description: `Execute ${action.key} action on ${singular}`,
                expected_status: 200,
                validation: {
                    type: 'status_any',
                    acceptable_statuses: [200, 400, 403, 404]
                },
                requires_permission: action.permission,
                note: `Custom action: ${action.type}${action.field ? ` (field: ${action.field})` : ''}`
            };
        }
    }
    
    // Relationship endpoints - generate specific test for each relationship
    if (relationships && relationships.length > 0) {
        for (const rel of relationships) {
            // GET nested relationship (list related items)
            paths.authenticated.api[`${name}_nested_${rel.name}`] = {
                method: 'GET',
                path: `${api_prefix}/${name}/${test_id}/${rel.name}`,
                description: `Get ${rel.name} for ${singular} (nested endpoint)`,
                expected_status: 200,
                validation: {
                    type: 'json',
                    contains: ['rows']
                },
                note: `Relationship: ${rel.type}`
            };
            
            // POST attach (for many-to-many)
            if (rel.type === 'many_to_many' || rel.type === 'belongs_to_many') {
                paths.authenticated.api[`${name}_attach_${rel.name}`] = {
                    method: 'POST',
                    path: `${api_prefix}/${name}/${test_id}/${rel.name}`,
                    description: `Attach ${rel.name} to ${singular}`,
                    expected_status: 200,
                    validation: {
                        type: 'status_any',
                        acceptable_statuses: [200, 400, 403]
                    },
                    note: `Many-to-many relationship${rel.pivot_table ? ` via ${rel.pivot_table}` : ''}`
                };
                
                // DELETE detach (for many-to-many)
                paths.authenticated.api[`${name}_detach_${rel.name}`] = {
                    method: 'DELETE',
                    path: `${api_prefix}/${name}/${test_id}/${rel.name}`,
                    description: `Detach ${rel.name} from ${singular}`,
                    expected_status: 200,
                    validation: {
                        type: 'status_any',
                        acceptable_statuses: [200, 400, 403]
                    },
                    note: `Many-to-many relationship${rel.pivot_table ? ` via ${rel.pivot_table}` : ''}`
                };
            }
        }
    }
    
    // Details endpoints - test nested model views
    if (details && details.length > 0) {
        for (const detail of details) {
            paths.authenticated.api[`${name}_detail_${detail.model}`] = {
                method: 'GET',
                path: `${api_prefix}/${name}/${test_id}/${detail.model}`,
                description: `Get ${detail.model} details for ${singular}`,
                expected_status: 200,
                validation: {
                    type: 'json'
                },
                note: `Detail model${detail.foreign_key ? ` via ${detail.foreign_key}` : ''}`
            };
        }
    }
    
    // Field toggle endpoints - test each boolean field that can be toggled
    if (field_toggles && field_toggles.length > 0) {
        for (const field of field_toggles) {
            paths.authenticated.api[`${name}_toggle_${field}`] = {
                method: 'PUT',
                path: `${api_prefix}/${name}/${test_id}/${field}`,
                description: `Toggle ${field} for ${singular}`,
                expected_status: 200,
                validation: {
                    type: 'json'
                },
                note: `Boolean field toggle`
            };
        }
    }
    
    paths.authenticated.api[`${name}_delete`] = {
        method: 'DELETE',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Delete ${singular} via CRUD6 API`,
        expected_status: 200,
        validation: {
            type: 'json'
        },
        requires_permission: `delete_${singular}`
    };
    
    // Authenticated Frontend paths
    paths.authenticated.frontend[`${name}_list`] = {
        path: `${frontend_prefix}/${name}`,
        description: `${name} list page`,
        screenshot: true,
        screenshot_name: `${name}_list`
    };
    
    paths.authenticated.frontend[`${name}_detail`] = {
        path: `${frontend_prefix}/${name}/${test_id}`,
        description: `Single ${singular} detail page`,
        screenshot: true,
        screenshot_name: `${singular}_detail`
    };
    
    // Unauthenticated API paths (should return 401)
    paths.unauthenticated.api[`${name}_schema`] = {
        method: 'GET',
        path: `${api_prefix}/${name}/schema`,
        description: `Attempt to access ${name} schema without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    paths.unauthenticated.api[`${name}_list`] = {
        method: 'GET',
        path: `${api_prefix}/${name}`,
        description: `Attempt to access ${name} list without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    paths.unauthenticated.api[`${name}_create`] = {
        method: 'POST',
        path: `${api_prefix}/${name}`,
        description: `Attempt to create ${singular} without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    paths.unauthenticated.api[`${name}_single`] = {
        method: 'GET',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Attempt to access single ${singular} without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    paths.unauthenticated.api[`${name}_update`] = {
        method: 'PUT',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Attempt to update ${singular} without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    paths.unauthenticated.api[`${name}_delete`] = {
        method: 'DELETE',
        path: `${api_prefix}/${name}/${test_id}`,
        description: `Attempt to delete ${singular} without authentication`,
        expected_status: 401,
        validation: {
            type: 'status_only'
        }
    };
    
    // Unauthenticated Frontend paths (should redirect to login)
    paths.unauthenticated.frontend[`${name}_list`] = {
        path: `${frontend_prefix}/${name}`,
        description: `Attempt to access ${name} list page without authentication (should redirect to login)`,
        expected_status: 200,
        validation: {
            type: 'redirect_to_login',
            contains: ['/account/sign-in', 'login']
        }
    };
    
    paths.unauthenticated.frontend[`${name}_detail`] = {
        path: `${frontend_prefix}/${name}/${test_id}`,
        description: `Attempt to access ${singular} detail page without authentication (should redirect to login)`,
        expected_status: 200,
        validation: {
            type: 'redirect_to_login',
            contains: ['/account/sign-in', 'login']
        }
    };
    
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

/**
 * Main execution
 */
try {
    // Load schemas
    const schemas = loadSchemas(schemaDir);
    
    if (schemas.length === 0) {
        console.error('❌ No valid CRUD6 schemas found');
        process.exit(1);
    }
    
    // Initialize output structure
    const output = {
        description: 'Integration test paths configuration for CRUD6 sprinkle (auto-generated from schema files)',
        generated_from: schemaDir,
        generated_at: new Date().toISOString(),
        schema_count: schemas.length,
        security_notes: {
            reserved_ids: {
                user_id_1: 'Reserved for system admin user - NEVER use in DELETE/DISABLE tests',
                group_id_1: 'Reserved for admin group - NEVER use in DELETE/DISABLE tests'
            },
            test_data_range: 'All test IDs start from 2 to avoid conflicts with system records',
            safe_operations: 'DELETE and DISABLE operations should only target IDs >= 2'
        },
        paths: {
            authenticated: { api: {}, frontend: {} },
            unauthenticated: { api: {}, frontend: {} }
        },
        config: {
            base_url: 'http://localhost:8080',
            auth: {
                username: 'admin',
                password: 'admin123'
            },
            timeout: {
                api: 10,
                frontend: 30
            },
            security: {
                csrf: {
                    enabled: true,
                    note: 'CSRF protection is enforced by UserFrosting 6\'s CsrfGuardMiddleware',
                    applies_to: ['POST', 'PUT', 'DELETE']
                },
                authentication: {
                    enabled: true,
                    note: 'All endpoints require authentication via AuthGuard middleware',
                    applies_to: 'all_endpoints'
                },
                permissions: {
                    enabled: true,
                    note: 'Operations require specific permissions as documented in schema definitions'
                }
            }
        }
    };
    
    // Generate paths for each schema
    let pathCount = 0;
    const modelNames = [];
    
    console.log('Generating paths for schemas:');
    for (const { filename, model, schema } of schemas) {
        const metadata = extractModelMetadata(schema);
        const modelPaths = generateModelPaths(metadata);
        mergePaths(output, modelPaths);
        
        const modelPathCount = 
            Object.keys(modelPaths.authenticated.api).length +
            Object.keys(modelPaths.authenticated.frontend).length +
            Object.keys(modelPaths.unauthenticated.api).length +
            Object.keys(modelPaths.unauthenticated.frontend).length;
        
        console.log(`  ✅ ${model} (${modelPathCount} paths)`);
        modelNames.push(model);
        pathCount += modelPathCount;
    }
    
    // Add models list to output
    output.models = modelNames;
    
    console.log('');
    console.log('Summary:');
    console.log(`  Schemas processed: ${schemas.length}`);
    console.log(`  Models: ${modelNames.join(', ')}`);
    console.log(`  Total paths generated: ${pathCount}`);
    console.log(`    Authenticated API: ${Object.keys(output.paths.authenticated.api).length}`);
    console.log(`    Authenticated Frontend: ${Object.keys(output.paths.authenticated.frontend).length}`);
    console.log(`    Unauthenticated API: ${Object.keys(output.paths.unauthenticated.api).length}`);
    console.log(`    Unauthenticated Frontend: ${Object.keys(output.paths.unauthenticated.frontend).length}`);
    
    // Ensure output directory exists
    const outputDir = path.dirname(outputFile);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }
    
    // Save output
    fs.writeFileSync(outputFile, JSON.stringify(output, null, 2), 'utf8');
    
    console.log('');
    console.log(`✅ Paths configuration saved to: ${outputFile}`);
    console.log(`   File size: ${(JSON.stringify(output).length / 1024).toFixed(2)} KB`);
    console.log('');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('✅ Path generation complete!');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('');
    console.log('ℹ️  Paths are now fully schema-driven:');
    console.log(`   - Read directly from: ${schemaDir}`);
    console.log('   - No intermediary configuration file needed');
    console.log('   - All test IDs automatically set to 2 (protecting system records)');
    console.log('   - Relationships and actions extracted from schema');
    console.log('');
    
    process.exit(0);
} catch (error) {
    console.error(`\n❌ Error: ${error.message}`);
    console.error(error.stack);
    process.exit(1);
}
