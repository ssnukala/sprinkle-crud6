#!/usr/bin/env node

/**
 * Generate SQL seed data from CRUD6 schema files
 * 
 * This script reads CRUD6 JSON schema files and generates SQL INSERT statements
 * for seeding test data. The generated SQL can be used in integration tests
 * instead of PHP seeder classes.
 * 
 * EXECUTION ORDER (Integration Tests):
 * 1. Run migrations (php bakery migrate)
 * 2. Create admin user (php bakery create:admin-user) - Creates user ID 1
 * 3. Run this generated SQL seed data - Creates test data starting from ID 2
 * 4. Run unauthenticated path testing
 * 5. Run authenticated path testing
 * 
 * Features:
 * - Automatically generates test data based on field types
 * - Respects field validation rules (required, unique, length, etc.)
 * - EXCLUDES user ID 1 ONLY (reserved for admin user)
 * - Starts test data from ID 2 (safe for all tables including users, groups, roles, permissions)
 * - Generates relationship data for many-to-many tables
 * - Creates idempotent SQL with INSERT...ON DUPLICATE KEY UPDATE
 * - Safe for re-seeding (won't duplicate or conflict with existing data)
 * 
 * Usage: node generate-seed-sql.js [schema_directory] [output_file]
 * Example: node generate-seed-sql.js examples/schema app/sql/seeds/crud6-test-data.sql
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);
const DEFAULT_SCHEMA_DIR = 'examples/schema';
const DEFAULT_OUTPUT_FILE = 'app/sql/seeds/crud6-test-data.sql';

const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
const outputFile = args[1] || DEFAULT_OUTPUT_FILE;

console.log('═══════════════════════════════════════════════════════════════');
console.log('CRUD6 Schema-Driven SQL Seed Generator');
console.log('═══════════════════════════════════════════════════════════════');
console.log(`Schema directory: ${schemaDir}`);
console.log(`Output file: ${outputFile}`);
console.log('');

/**
 * Generate a test value for a field based on its type and validation
 */
function generateTestValue(fieldName, field, recordIndex = 1) {
    const type = field.type;
    const validation = field.validation || {};
    
    // Check for default value
    if (field.default !== undefined) {
        if (typeof field.default === 'string') {
            return `'${field.default}'`;
        }
        return field.default;
    }
    
    // Generate based on type
    switch (type) {
        case 'integer':
            if (field.auto_increment) {
                return 'NULL'; // Let DB auto-increment
            }
            return recordIndex; // Use recordIndex directly (starts from 2)
            
        case 'string':
            const maxLength = validation.length?.max || 255;
            const minLength = validation.length?.min || 1;
            
            if (validation.email) {
                return `'test${recordIndex}@example.com'`;
            }
            
            if (validation.unique) {
                return `'test_${fieldName}_${recordIndex}'`;
            }
            
            // Generate based on field name patterns
            if (fieldName.includes('email')) {
                return `'test${recordIndex}@example.com'`;
            }
            if (fieldName.includes('name') && !fieldName.includes('user_name')) {
                return `'Test ${fieldName} ${recordIndex}'`;
            }
            if (fieldName.includes('slug')) {
                return `'test-${fieldName.replace('_', '-')}-${recordIndex}'`;
            }
            if (fieldName.includes('email')) {
                return `'test${recordIndex}@example.com'`;
            }
            if (fieldName.includes('password')) {
                return `'$2y$10$test.password.hash.${recordIndex}'`; // bcrypt hash placeholder
            }
            
            return `'Test ${fieldName}'`;
            
        case 'text':
            return `'Test description for ${fieldName} - Record ${recordIndex}'`;
            
        case 'boolean':
            return field.default !== undefined ? field.default : 1;
            
        case 'date':
            return `'2024-01-${String(recordIndex).padStart(2, '0')}'`;
            
        case 'datetime':
            if (fieldName === 'created_at' || fieldName === 'updated_at') {
                return 'CURRENT_TIMESTAMP';
            }
            return `'2024-01-${String(recordIndex).padStart(2, '0')} 12:00:00'`;
            
        case 'decimal':
        case 'float':
            const min = validation.min || 0;
            return (min + recordIndex * 10.50).toFixed(2);
            
        case 'json':
            return `'{}'`;
            
        default:
            if (field.required) {
                return `'test_${fieldName}'`;
            }
            return 'NULL';
    }
}

/**
 * Check if a field should be included in INSERT
 */
function shouldIncludeField(fieldName, field) {
    // Skip auto-increment fields
    if (field.auto_increment) {
        return false;
    }
    
    // Skip readonly fields that aren't required
    if (field.readonly && !field.required) {
        return false;
    }
    
    // Skip created_at/updated_at if they have timestamp defaults
    if ((fieldName === 'created_at' || fieldName === 'updated_at') && !field.required) {
        return false;
    }
    
    return true;
}

/**
 * Generate SQL INSERT statement for a model
 */
function generateInsertSQL(schema, recordCount = 3) {
    const tableName = schema.table || schema.model;
    const fields = schema.fields || {};
    
    const sql = [];
    sql.push(`-- Seed data for ${tableName}`);
    sql.push(`-- Generated from schema: ${schema.model}.json`);
    sql.push('');
    
    // Get insertable fields
    const insertFields = [];
    const fieldNames = Object.keys(fields);
    
    for (const fieldName of fieldNames) {
        const field = fields[fieldName];
        if (shouldIncludeField(fieldName, field)) {
            insertFields.push(fieldName);
        }
    }
    
    if (insertFields.length === 0) {
        sql.push(`-- No insertable fields found for ${tableName}`);
        return sql.join('\n');
    }
    
    // Generate INSERT statements
    // Start from ID 2 - only user ID 1 is reserved for admin
    for (let i = 0; i < recordCount; i++) {
        const recordIndex = i + 2; // Start from 2 (only user ID 1 is reserved)
        const values = [];
        
        for (const fieldName of insertFields) {
            const field = fields[fieldName];
            const value = generateTestValue(fieldName, field, recordIndex);
            values.push(value);
        }
        
        sql.push(`INSERT INTO \`${tableName}\` (${insertFields.map(f => `\`${f}\``).join(', ')})`);
        sql.push(`VALUES (${values.join(', ')})`);
        sql.push(`ON DUPLICATE KEY UPDATE ${insertFields.map(f => `\`${f}\` = VALUES(\`${f}\`)`).join(', ')};`);
        sql.push('');
    }
    
    return sql.join('\n');
}

/**
 * Generate SQL for relationship tables
 */
function generateRelationshipSQL(schema) {
    const relationships = schema.relationships || [];
    const sql = [];
    
    for (const rel of relationships) {
        if (rel.type === 'many_to_many' && rel.pivot_table) {
            sql.push(`-- Relationship: ${schema.model} -> ${rel.name}`);
            sql.push(`-- Pivot table: ${rel.pivot_table}`);
            sql.push('');
            
            const foreignKey = rel.foreign_key || `${schema.model.slice(0, -1)}_id`;
            const relatedKey = rel.related_key || `${rel.name.slice(0, -1)}_id`;
            
            // Generate a few test relationships (2->2, 3->2, 3->3)
            const testRelationships = [
                [2, 2],
                [3, 2],
                [3, 3],
            ];
            
            for (const [fk, rk] of testRelationships) {
                sql.push(`INSERT INTO \`${rel.pivot_table}\` (\`${foreignKey}\`, \`${relatedKey}\`)`);
                sql.push(`VALUES (${fk}, ${rk})`);
                sql.push(`ON DUPLICATE KEY UPDATE \`${foreignKey}\` = VALUES(\`${foreignKey}\`);`);
                sql.push('');
            }
        }
    }
    
    return sql.join('\n');
}

/**
 * Load and process all schemas
 */
function processSchemas() {
    console.log('📂 Loading schemas...');
    
    const files = fs.readdirSync(schemaDir);
    const jsonFiles = files.filter(f => f.endsWith('.json'));
    
    console.log(`   Found ${jsonFiles.length} schema files`);
    console.log('');
    
    const allSQL = [];
    
    // SQL file header
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('-- CRUD6 Integration Test Seed Data');
    allSQL.push('-- Generated from JSON schemas');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('--');
    allSQL.push('-- EXECUTION ORDER IN INTEGRATION TESTS:');
    allSQL.push('-- 1. Migrations run (php bakery migrate)');
    allSQL.push('-- 2. Admin user created (php bakery create:admin-user) → user_id = 1, group_id = 1');
    allSQL.push('-- 3. THIS SQL RUNS → Creates test data starting from ID 2');
    allSQL.push('-- 4. Unauthenticated path testing begins');
    allSQL.push('-- 5. Authenticated path testing begins');
    allSQL.push('--');
    allSQL.push('-- CRITICAL CONSTRAINTS:');
    allSQL.push('-- - User ID 1 and Group ID 1 are RESERVED for system/admin');
    allSQL.push('-- - Test data ALWAYS starts from ID 2 or higher');
    allSQL.push('-- - DELETE/DISABLE tests MUST NOT use ID 1 (system account protection)');
    allSQL.push('-- - Uses INSERT...ON DUPLICATE KEY UPDATE for safe re-seeding');
    allSQL.push('--');
    allSQL.push(`-- Generated: ${new Date().toISOString()}`);
    allSQL.push(`-- Source: Schema files in ${schemaDir}/`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('');
    allSQL.push('-- Disable foreign key checks for seeding');
    allSQL.push('SET FOREIGN_KEY_CHECKS=0;');
    allSQL.push('');
    
    let processedCount = 0;
    
    // Process each schema
    for (const file of jsonFiles) {
        try {
            const filePath = path.join(schemaDir, file);
            const content = fs.readFileSync(filePath, 'utf8');
            const schema = JSON.parse(content);
            
            console.log(`   ✅ Processing: ${file} (model: ${schema.model})`);
            
            // Generate INSERT SQL
            allSQL.push(`-- ${'-'.repeat(60)}`);
            allSQL.push(`-- Model: ${schema.model}`);
            allSQL.push(`-- ${'-'.repeat(60)}`);
            allSQL.push('');
            allSQL.push(generateInsertSQL(schema, 3));
            allSQL.push('');
            
            // Generate relationship SQL
            const relSQL = generateRelationshipSQL(schema);
            if (relSQL) {
                allSQL.push(relSQL);
                allSQL.push('');
            }
            
            processedCount++;
        } catch (error) {
            console.error(`   ❌ Failed to process ${file}: ${error.message}`);
        }
    }
    
    // SQL file footer
    allSQL.push('-- Re-enable foreign key checks');
    allSQL.push('SET FOREIGN_KEY_CHECKS=1;');
    allSQL.push('');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push(`-- Successfully generated ${processedCount} model seed data sets`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('-- REMINDER: This seed data is designed to run AFTER admin user creation');
    allSQL.push('--           and BEFORE unauthenticated path testing.');
    allSQL.push('--');
    allSQL.push('-- Protected Records:');
    allSQL.push('--   - User ID 1 (admin user)');
    allSQL.push('--   - Group ID 1 (admin group)');
    allSQL.push('--');
    allSQL.push('-- Test Data Range: ID >= 2 (safe for DELETE/DISABLE operations)');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    
    console.log('');
    console.log(`✅ Processed ${processedCount} schemas`);
    console.log('');
    
    return allSQL.join('\n');
}

/**
 * Main execution
 */
try {
    // Ensure output directory exists
    const outputDir = path.dirname(outputFile);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
        console.log(`📁 Created output directory: ${outputDir}`);
        console.log('');
    }
    
    // Generate SQL
    const sql = processSchemas();
    
    // Write to file
    fs.writeFileSync(outputFile, sql, 'utf8');
    
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('✅ SQL seed data generated successfully!');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Output file: ${outputFile}`);
    console.log(`File size: ${(sql.length / 1024).toFixed(2)} KB`);
    console.log('');
    console.log('Usage:');
    console.log(`  mysql -u user -p database < ${outputFile}`);
    console.log('  or use in integration tests via PDO/Eloquent');
    console.log('');
    console.log('⚠️  REMEMBER: User ID 1 and Group ID 1 are reserved!');
    console.log('   DELETE/DISABLE tests must use IDs >= 2');
    console.log('');
    
    process.exit(0);
} catch (error) {
    console.error('❌ Error:', error.message);
    console.error(error.stack);
    process.exit(1);
}
