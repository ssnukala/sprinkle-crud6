#!/usr/bin/env node

/**
 * Generate DDL (CREATE TABLE) SQL from CRUD6 schema files
 * 
 * This script reads CRUD6 JSON schema files and generates CREATE TABLE statements
 * for creating database tables. The generated SQL includes:
 * - Column definitions based on field types
 * - Primary keys (auto_increment fields)
 * - Unique constraints
 * - Indexes for commonly filtered fields
 * - Timestamps columns
 * 
 * Usage: node generate-ddl-sql.js [schema_directory] [output_file]
 * Example: node generate-ddl-sql.js examples/schema app/sql/migrations/crud6-tables.sql
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);
const DEFAULT_SCHEMA_DIR = 'examples/schema';
const DEFAULT_OUTPUT_FILE = 'app/sql/migrations/crud6-tables.sql';

const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
const outputFile = args[1] || DEFAULT_OUTPUT_FILE;

console.log('═══════════════════════════════════════════════════════════════');
console.log('CRUD6 Schema-Driven DDL Generator');
console.log('═══════════════════════════════════════════════════════════════');
console.log(`Schema directory: ${schemaDir}`);
console.log(`Output file: ${outputFile}`);
console.log('');

/**
 * Map CRUD6 field types to SQL column types
 */
function mapFieldTypeToSQL(fieldName, field) {
    const type = field.type;
    const validation = field.validation || {};
    
    switch (type) {
        case 'integer':
            if (field.auto_increment) {
                return 'INT AUTO_INCREMENT';
            }
            return 'INT';
            
        case 'string':
            const maxLength = validation.length?.max || 255;
            return `VARCHAR(${maxLength})`;
            
        case 'text':
            return 'TEXT';
            
        case 'boolean':
            return 'TINYINT(1)';
            
        case 'boolean-yn':
            // Boolean with Yes/No representation
            return 'TINYINT(1)';
            
        case 'date':
            return 'DATE';
            
        case 'datetime':
            return 'TIMESTAMP';
            
        case 'decimal':
            // Default to DECIMAL(10,2) for prices and amounts
            return 'DECIMAL(10,2)';
            
        case 'float':
            return 'FLOAT';
            
        case 'json':
            return 'JSON';
            
        case 'email':
            // Email field - VARCHAR with validation
            return 'VARCHAR(255)';
            
        case 'password':
            // Password field - bcrypt hash is 60 chars, but give some room
            return 'VARCHAR(255)';
            
        case 'phone':
            // Phone number field
            return 'VARCHAR(20)';
            
        case 'url':
            // URL field
            return 'VARCHAR(2048)';
            
        case 'zip':
            // ZIP/postal code
            return 'VARCHAR(10)';
            
        case 'multiselect':
            // Multi-select field stored as comma-separated or JSON
            return 'TEXT';
            
        // Textarea variations
        case 'textarea':
        case 'textarea-r3c60':
        case 'textarea-r5':
            return 'TEXT';
            
        default:
            console.warn(`   ⚠️  Unknown field type: ${type} for field ${fieldName}, defaulting to VARCHAR(255)`);
            return 'VARCHAR(255)';
    }
}

/**
 * Generate CREATE TABLE statement for a schema
 */
function generateCreateTableSQL(schema) {
    const tableName = schema.table;
    const fields = schema.fields || {};
    
    const sql = [];
    const columns = [];
    const uniqueConstraints = [];
    const indexes = [];
    let primaryKey = null;
    
    // Process each field
    for (const [fieldName, field] of Object.entries(fields)) {
        // Skip computed/virtual fields (e.g., role_ids used for relationship sync)
        // These are form-only fields that don't belong in the database
        if (field.computed) {
            console.log(`   ⏭️  Skipping computed field: ${fieldName} (not a database column)`);
            continue;
        }
        
        const columnType = mapFieldTypeToSQL(fieldName, field);
        const parts = [`\`${fieldName}\``, columnType];
        
        // Handle NOT NULL - AUTO_INCREMENT fields are always NOT NULL
        if (field.auto_increment) {
            parts.push('NOT NULL');
        } else if (field.required || field.validation?.required) {
            parts.push('NOT NULL');
        } else {
            parts.push('NULL');
        }
        
        // Handle DEFAULT values
        // NOTE: MySQL does not allow default values for TEXT, BLOB, JSON, or GEOMETRY columns
        const typesWithoutDefaults = ['TEXT', 'JSON', 'BLOB', 'MEDIUMTEXT', 'LONGTEXT', 'TINYTEXT', 'GEOMETRY'];
        // Extract base type (e.g., "INT AUTO_INCREMENT" -> "INT")
        const baseType = columnType.split(' ')[0].toUpperCase();
        const hasInvalidDefaultType = typesWithoutDefaults.includes(baseType);
        
        if (field.default !== undefined && !hasInvalidDefaultType) {
            if (typeof field.default === 'string') {
                parts.push(`DEFAULT '${field.default}'`);
            } else if (typeof field.default === 'boolean') {
                parts.push(`DEFAULT ${field.default ? 1 : 0}`);
            } else {
                parts.push(`DEFAULT ${field.default}`);
            }
        } else if (hasInvalidDefaultType && field.default !== undefined) {
            // Skip default value for TEXT/JSON/BLOB/GEOMETRY types and log a warning
            console.warn(`   ⚠️  Skipping default value for ${fieldName} (${columnType}): MySQL does not support defaults for TEXT/JSON/BLOB/GEOMETRY columns`);
        }
        
        // Track primary key
        if (field.auto_increment) {
            primaryKey = fieldName;
        }
        
        // Track unique constraints
        if (field.validation?.unique) {
            uniqueConstraints.push(fieldName);
        }
        
        // Track indexes for filterable/sortable fields
        if ((field.filterable || field.sortable) && !field.auto_increment && field.type !== 'text' && field.type !== 'json') {
            indexes.push(fieldName);
        }
        
        columns.push('  ' + parts.join(' '));
    }
    
    // Add primary key
    if (primaryKey) {
        columns.push(`  PRIMARY KEY (\`${primaryKey}\`)`);
    }
    
    // Add unique constraints
    for (const uniqueField of uniqueConstraints) {
        columns.push(`  UNIQUE KEY \`${uniqueField}_unique\` (\`${uniqueField}\`)`);
    }
    
    // Add indexes (limit to most important ones to avoid too many indexes)
    const maxIndexes = 5;
    for (const indexField of indexes.slice(0, maxIndexes)) {
        if (!uniqueConstraints.includes(indexField)) {
            columns.push(`  KEY \`${indexField}_idx\` (\`${indexField}\`)`);
        }
    }
    
    // Build CREATE TABLE statement
    sql.push(`CREATE TABLE IF NOT EXISTS \`${tableName}\` (`);
    sql.push(columns.join(',\n'));
    sql.push(') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
    
    return sql.join('\n');
}

/**
 * Generate CREATE TABLE statements for relationship pivot tables
 */
function generatePivotTableSQL(schema, processedPivotTables) {
    const relationships = schema.relationships || [];
    const sql = [];
    
    for (const rel of relationships) {
        if (rel.type === 'many_to_many' && rel.pivot_table) {
            const pivotTable = rel.pivot_table;
            
            // Skip if we've already processed this pivot table
            if (processedPivotTables.has(pivotTable)) {
                continue;
            }
            
            const foreignKey = rel.foreign_key || `${schema.model.slice(0, -1)}_id`;
            const relatedKey = rel.related_key || `${rel.name.slice(0, -1)}_id`;
            
            sql.push(`-- Pivot table for ${schema.model} <-> ${rel.name} relationship`);
            sql.push(`CREATE TABLE IF NOT EXISTS \`${pivotTable}\` (`);
            sql.push(`  \`${foreignKey}\` INT NOT NULL,`);
            sql.push(`  \`${relatedKey}\` INT NOT NULL,`);
            sql.push(`  PRIMARY KEY (\`${foreignKey}\`, \`${relatedKey}\`),`);
            sql.push(`  KEY \`${foreignKey}_idx\` (\`${foreignKey}\`),`);
            sql.push(`  KEY \`${relatedKey}_idx\` (\`${relatedKey}\`)`);
            sql.push(') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
            sql.push('');
            
            // Mark this pivot table as processed
            processedPivotTables.add(pivotTable);
        }
    }
    
    return sql.join('\n');
}

/**
 * Load and process all schemas
 */
function processSchemas() {
    console.log('📂 Loading schemas...');
    
    if (!fs.existsSync(schemaDir)) {
        console.error(`❌ Schema directory not found: ${schemaDir}`);
        process.exit(1);
    }
    
    const files = fs.readdirSync(schemaDir);
    const jsonFiles = files.filter(f => f.endsWith('.json'));
    
    console.log(`   Found ${jsonFiles.length} schema files`);
    console.log('');
    
    const allSQL = [];
    
    // SQL file header
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('-- CRUD6 DDL - CREATE TABLE Statements');
    allSQL.push('-- Generated from JSON schemas');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('--');
    allSQL.push('-- This file creates all tables needed for CRUD6 test schemas.');
    allSQL.push('-- Run this BEFORE seeding data with INSERT statements.');
    allSQL.push('--');
    allSQL.push('-- EXECUTION ORDER:');
    allSQL.push('-- 1. Run UserFrosting migrations (php bakery migrate)');
    allSQL.push('-- 2. Run this DDL file (CREATE TABLE statements)');
    allSQL.push('-- 3. Create admin user (php bakery create:admin-user)');
    allSQL.push('-- 4. Run seed data (INSERT statements)');
    allSQL.push('--');
    allSQL.push(`-- Generated: ${new Date().toISOString()}`);
    allSQL.push(`-- Source: Schema files in ${schemaDir}/`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('');
    allSQL.push('-- Disable foreign key checks during table creation');
    allSQL.push('SET FOREIGN_KEY_CHECKS=0;');
    allSQL.push('');
    
    let processedCount = 0;
    const processedTables = new Set();
    const processedPivotTables = new Set();
    
    // Process each schema
    for (const file of jsonFiles) {
        try {
            const filePath = path.join(schemaDir, file);
            const content = fs.readFileSync(filePath, 'utf8');
            const schema = JSON.parse(content);
            
            const tableName = schema.table;
            
            // Skip if we've already processed this table (multiple schemas may use same table)
            if (processedTables.has(tableName)) {
                console.log(`   ⏭️  Skipping duplicate table: ${tableName} (from ${file})`);
                continue;
            }
            
            console.log(`   ✅ Processing: ${file} (table: ${tableName})`);
            processedTables.add(tableName);
            
            // Generate CREATE TABLE SQL
            allSQL.push(`-- ${'-'.repeat(60)}`);
            allSQL.push(`-- Table: ${tableName}`);
            allSQL.push(`-- Schema: ${file}`);
            allSQL.push(`-- ${'-'.repeat(60)}`);
            allSQL.push('');
            allSQL.push(generateCreateTableSQL(schema));
            allSQL.push('');
            
            // Generate pivot table SQL if any
            const pivotSQL = generatePivotTableSQL(schema, processedPivotTables);
            if (pivotSQL) {
                allSQL.push(pivotSQL);
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
    allSQL.push(`-- Successfully generated ${processedTables.size} table definitions`);
    allSQL.push(`-- and ${processedPivotTables.size} pivot tables`);
    allSQL.push(`-- from ${processedCount} schema files`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    
    console.log('');
    console.log(`✅ Processed ${processedCount} schemas, generated ${processedTables.size} unique tables and ${processedPivotTables.size} pivot tables`);
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
    fs.writeFileSync(outputFile, sql);
    
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('✅ DDL SQL file generated successfully');
    console.log(`📄 Output: ${outputFile}`);
    console.log('═══════════════════════════════════════════════════════════════');
    
    process.exit(0);
} catch (error) {
    console.error('');
    console.error('═══════════════════════════════════════════════════════════════');
    console.error('❌ Error generating DDL SQL');
    console.error('═══════════════════════════════════════════════════════════════');
    console.error(error);
    process.exit(1);
}
