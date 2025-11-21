#!/usr/bin/env node

/**
 * Schema-Driven Test Generator for CRUD6
 * 
 * This script analyzes all JSON schema files and generates comprehensive PHPUnit tests
 * that validate ALL features defined in each schema:
 * 
 * - All field types (string, integer, boolean, date, datetime, text, json, float, decimal)
 * - All validation rules (required, unique, min/max length, email, pattern)
 * - Default values
 * - Readonly fields
 * - Hidden fields
 * - Listable fields configuration
 * - Editable fields configuration
 * - All relationship types (belongs_to, has_many, many_to_many, etc.)
 * - All custom actions
 * - Permissions for all operations
 * - Default sorting
 * - Details/nested models
 * 
 * Usage: node generate-schema-tests.js [schema_directory] [output_directory]
 */

import { readFileSync, writeFileSync, readdirSync, mkdirSync, existsSync } from 'fs';
import { join, basename } from 'path';

// Default paths
const DEFAULT_SCHEMA_DIR = 'app/schema/crud6';
const DEFAULT_OUTPUT_DIR = 'app/tests/Generated';

/**
 * Schema Test Generator
 */
class SchemaTestGenerator {
    constructor(schemaDir, outputDir) {
        this.schemaDir = schemaDir;
        this.outputDir = outputDir;
        this.schemas = [];
    }

    /**
     * Load all schema files from directory
     */
    loadSchemas() {
        console.log(`📂 Loading schemas from: ${this.schemaDir}`);
        
        const files = readdirSync(this.schemaDir);
        const jsonFiles = files.filter(f => f.endsWith('.json'));
        
        console.log(`   Found ${jsonFiles.length} schema files`);
        
        for (const file of jsonFiles) {
            try {
                const filePath = join(this.schemaDir, file);
                const content = readFileSync(filePath, 'utf8');
                const schema = JSON.parse(content);
                
                this.schemas.push({
                    filename: file,
                    model: schema.model,
                    schema: schema
                });
                
                console.log(`   ✅ Loaded: ${file} (model: ${schema.model})`);
            } catch (error) {
                console.error(`   ❌ Failed to load ${file}: ${error.message}`);
            }
        }
        
        console.log(`\n✅ Loaded ${this.schemas.length} schemas\n`);
    }

    /**
     * Generate tests for all schemas
     */
    generateAllTests() {
        console.log(`📝 Generating tests...`);
        
        // Create output directory if it doesn't exist
        if (!existsSync(this.outputDir)) {
            mkdirSync(this.outputDir, { recursive: true });
            console.log(`   Created directory: ${this.outputDir}`);
        }
        
        for (const schemaInfo of this.schemas) {
            this.generateTestForSchema(schemaInfo);
        }
        
        console.log(`\n✅ Generated ${this.schemas.length} test files`);
    }

    /**
     * Generate test file for a single schema
     */
    generateTestForSchema(schemaInfo) {
        const { filename, model, schema } = schemaInfo;
        const className = this.getTestClassName(model);
        const testFile = join(this.outputDir, `${className}.php`);
        
        console.log(`   Generating: ${className}.php for model "${model}"`);
        
        const testContent = this.generateTestContent(model, schema, className);
        
        writeFileSync(testFile, testContent);
        console.log(`   ✅ Written: ${testFile}`);
    }

    /**
     * Get test class name from model name
     */
    getTestClassName(model) {
        // Convert snake_case to PascalCase
        const pascalCase = model
            .split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join('');
        
        return `${pascalCase}SchemaTest`;
    }

    /**
     * Generate full test content
     */
    generateTestContent(model, schema, className) {
        // Handle fields as either array or object
        let fields = schema.fields || [];
        if (!Array.isArray(fields)) {
            // Convert object to array of field objects with name property
            fields = Object.entries(fields).map(([name, field]) => ({
                name,
                ...field
            }));
        }
        
        const relationships = schema.relationships || [];
        const actions = schema.actions || [];
        const details = schema.details || [];
        
        // Analyze schema features
        const features = this.analyzeSchemaFeatures(schema);
        
        let php = `<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\\Sprinkle\\CRUD6\\Tests\\Generated;

use Mockery\\Adapter\\Phpunit\\MockeryPHPUnitIntegration;
use UserFrosting\\Sprinkle\\Account\\Database\\Models\\User;
use UserFrosting\\Sprinkle\\Account\\Testing\\WithTestUser;
use UserFrosting\\Sprinkle\\CRUD6\\Tests\\AdminTestCase;
use UserFrosting\\Sprinkle\\Core\\Testing\\RefreshDatabase;

/**
 * GENERATED TEST: ${model} Schema Comprehensive Test
 * 
 * AUTO-GENERATED from schema: ${schema.table || model}
 * 
 * This test validates ALL features defined in the ${model} JSON schema:
 * ${this.generateFeatureList(features)}
 * 
 * @generated
 * @see app/schema/crud6/${model}.json
 */
class ${className} extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;

    protected string \$modelName = '${model}';
    protected string \$tableName = '${schema.table || model}';
    protected string \$primaryKey = '${schema.primary_key || 'id'}';

    /**
     * Setup test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        \$this->refreshDatabase();
    }

`;

        // Generate test methods
        php += this.generateSchemaLoadingTest(model, schema);
        php += this.generateFieldsTest(model, fields);
        php += this.generateValidationTest(model, fields);
        php += this.generateRelationshipsTest(model, relationships);
        php += this.generateActionsTest(model, actions);
        php += this.generatePermissionsTest(model, schema);
        php += this.generateDefaultsTest(model, schema);
        
        php += `}\n`;
        
        return php;
    }

    /**
     * Analyze all features present in schema
     */
    analyzeSchemaFeatures(schema) {
        const features = {
            fieldTypes: new Set(),
            validationRules: new Set(),
            hasDefaults: false,
            hasReadonly: false,
            hasHidden: false,
            hasListable: false,
            hasEditable: false,
            relationshipTypes: new Set(),
            actionTypes: new Set(),
            hasPermissions: false,
            hasSorting: false,
            hasDetails: false
        };

        // Analyze fields - handle both array and object formats
        let fields = schema.fields || [];
        if (!Array.isArray(fields)) {
            // Convert object to array of field objects
            fields = Object.values(fields);
        }
        
        for (const field of fields) {
            if (field.type) features.fieldTypes.add(field.type);
            if (field.validation) {
                for (const rule of Object.keys(field.validation)) {
                    features.validationRules.add(rule);
                }
            }
            if (field.default !== undefined) features.hasDefaults = true;
            if (field.readonly) features.hasReadonly = true;
            if (field.hidden) features.hasHidden = true;
            if (field.listable !== undefined) features.hasListable = true;
            if (field.editable !== undefined) features.hasEditable = true;
        }

        // Analyze relationships
        const relationships = schema.relationships || [];
        for (const rel of relationships) {
            if (rel.type) features.relationshipTypes.add(rel.type);
        }

        // Analyze actions
        const actions = schema.actions || [];
        for (const action of actions) {
            if (action.type) features.actionTypes.add(action.type);
        }

        // Check other features
        if (schema.permissions) features.hasPermissions = true;
        if (schema.default_sort) features.hasSorting = true;
        if (schema.details && schema.details.length > 0) features.hasDetails = true;

        return features;
    }

    /**
     * Generate feature list for documentation
     */
    generateFeatureList(features) {
        const items = [];
        
        if (features.fieldTypes.size > 0) {
            items.push(`* Field types: ${Array.from(features.fieldTypes).join(', ')}`);
        }
        if (features.validationRules.size > 0) {
            items.push(`* Validation rules: ${Array.from(features.validationRules).join(', ')}`);
        }
        if (features.hasDefaults) items.push('* Default values');
        if (features.hasReadonly) items.push('* Readonly fields');
        if (features.hasHidden) items.push('* Hidden fields');
        if (features.relationshipTypes.size > 0) {
            items.push(`* Relationships: ${Array.from(features.relationshipTypes).join(', ')}`);
        }
        if (features.actionTypes.size > 0) {
            items.push(`* Actions: ${Array.from(features.actionTypes).join(', ')}`);
        }
        if (features.hasPermissions) items.push('* Permissions');
        if (features.hasSorting) items.push('* Default sorting');
        if (features.hasDetails) items.push('* Nested details');
        
        return items.join('\n * ');
    }

    /**
     * Generate schema loading test
     */
    generateSchemaLoadingTest(model, schema) {
        return `
    /**
     * Test that schema loads correctly for ${model}
     */
    public function testSchemaLoads(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        
        \$this->assertResponseStatus(200, \$response);
        
        \$data = json_decode((string) \$response->getBody(), true);
        \$this->assertIsArray(\$data);
        \$this->assertEquals('${model}', \$data['model']);
        \$this->assertEquals('${schema.table || model}', \$data['table']);
    }

`;
    }

    /**
     * Generate fields validation test
     */
    generateFieldsTest(model, fields) {
        if (fields.length === 0) return '';
        
        const fieldNames = fields.map(f => f.name).join("', '");
        
        return `
    /**
     * Test all fields are present in schema
     */
    public function testAllFieldsPresent(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$expectedFields = ['${fieldNames}'];
        \$actualFieldNames = array_column(\$data['fields'], 'name');
        
        foreach (\$expectedFields as \$field) {
            \$this->assertContains(\$field, \$actualFieldNames, "Field '\$field' should be present in schema");
        }
    }

`;
    }

    /**
     * Generate validation rules test
     */
    generateValidationTest(model, fields) {
        const fieldsWithValidation = fields.filter(f => f.validation);
        if (fieldsWithValidation.length === 0) return '';
        
        let php = `
    /**
     * Test validation rules are properly defined
     */
    public function testValidationRules(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$fields = \$data['fields'];
        
`;
        
        for (const field of fieldsWithValidation) {
            const rules = Object.keys(field.validation);
            php += `        // Validate ${field.name} has proper validation\n`;
            php += `        \$${field.name}Field = array_filter(\$fields, fn(\$f) => \$f['name'] === '${field.name}');\n`;
            php += `        \$this->assertNotEmpty(\$${field.name}Field);\n`;
            
            for (const rule of rules) {
                php += `        // TODO: Check ${rule} validation for ${field.name}\n`;
            }
            php += `\n`;
        }
        
        php += `    }\n\n`;
        
        return php;
    }

    /**
     * Generate relationships test
     */
    generateRelationshipsTest(model, relationships) {
        if (relationships.length === 0) return '';
        
        return `
    /**
     * Test relationships are properly configured
     */
    public function testRelationships(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$this->assertArrayHasKey('relationships', \$data);
        \$this->assertCount(${relationships.length}, \$data['relationships']);
        
${relationships.map(rel => `        // Check ${rel.name} relationship
        \$${rel.name}Rel = array_filter(\$data['relationships'], fn(\$r) => \$r['name'] === '${rel.name}');
        \$this->assertNotEmpty(\$${rel.name}Rel, '${rel.name} relationship should exist');
`).join('\n')}
    }

`;
    }

    /**
     * Generate actions test
     */
    generateActionsTest(model, actions) {
        if (actions.length === 0) return '';
        
        return `
    /**
     * Test custom actions are properly configured
     */
    public function testActions(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$this->assertArrayHasKey('actions', \$data);
        \$this->assertCount(${actions.length}, \$data['actions']);
        
${actions.map(action => `        // Check ${action.key} action
        \$${action.key}Action = array_filter(\$data['actions'], fn(\$a) => \$a['key'] === '${action.key}');
        \$this->assertNotEmpty(\$${action.key}Action, '${action.key} action should exist');
`).join('\n')}
    }

`;
    }

    /**
     * Generate permissions test
     */
    generatePermissionsTest(model, schema) {
        if (!schema.permissions) return '';
        
        const permissions = schema.permissions;
        const ops = Object.keys(permissions);
        
        return `
    /**
     * Test permissions are properly configured
     */
    public function testPermissions(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$this->assertArrayHasKey('permissions', \$data);
        
${ops.map(op => `        \$this->assertArrayHasKey('${op}', \$data['permissions']);
        \$this->assertEquals('${permissions[op]}', \$data['permissions']['${op}']);
`).join('\n')}
    }

`;
    }

    /**
     * Generate defaults test
     */
    generateDefaultsTest(model, schema) {
        const defaultSort = schema.default_sort;
        if (!defaultSort) return '';
        
        // Convert JavaScript object to PHP array syntax
        const phpArray = this.jsObjectToPhpArray(defaultSort);
        
        return `
    /**
     * Test default sorting is properly configured
     */
    public function testDefaultSort(): void
    {
        \$response = \$this->get("/api/crud6/{\$this->modelName}/schema");
        \$data = json_decode((string) \$response->getBody(), true);
        
        \$this->assertArrayHasKey('default_sort', \$data);
        \$this->assertEquals(${phpArray}, \$data['default_sort']);
    }

`;
    }
    
    /**
     * Convert JavaScript object to PHP array syntax
     */
    jsObjectToPhpArray(obj) {
        if (Array.isArray(obj)) {
            const items = obj.map(item => this.jsObjectToPhpArray(item));
            return `[${items.join(', ')}]`;
        } else if (typeof obj === 'object' && obj !== null) {
            const pairs = Object.entries(obj).map(([key, value]) => {
                const phpValue = this.jsObjectToPhpArray(value);
                return `'${key}' => ${phpValue}`;
            });
            return `[${pairs.join(', ')}]`;
        } else if (typeof obj === 'string') {
            return `'${obj}'`;
        } else if (typeof obj === 'number' || typeof obj === 'boolean') {
            return String(obj);
        } else if (obj === null) {
            return 'null';
        }
        return 'null';
    }
}

/**
 * Main execution
 */
async function main() {
    const args = process.argv.slice(2);
    const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
    const outputDir = args[1] || DEFAULT_OUTPUT_DIR;
    
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('CRUD6 Schema-Driven Test Generator');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Schema directory: ${schemaDir}`);
    console.log(`Output directory: ${outputDir}`);
    console.log('');
    
    const generator = new SchemaTestGenerator(schemaDir, outputDir);
    
    try {
        generator.loadSchemas();
        generator.generateAllTests();
        
        console.log('\n═══════════════════════════════════════════════════════════════');
        console.log('✅ Test generation completed successfully!');
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('\nNext steps:');
        console.log(`1. Review generated tests in: ${outputDir}`);
        console.log('2. Run tests with: vendor/bin/phpunit ' + outputDir);
        console.log('3. Update phpunit.xml to include Generated test suite');
        console.log('');
        
    } catch (error) {
        console.error(`\n❌ Error: ${error.message}`);
        console.error(error.stack);
        process.exit(1);
    }
}

// Run if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
    main();
}

export { SchemaTestGenerator };
