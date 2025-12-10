#!/usr/bin/env node

/**
 * Generate CREATE TABLE SQL from CRUD6 schema files
 * 
 * This script reads CRUD6 JSON schema files and generates SQL CREATE TABLE statements
 * for tables that may not exist in the database. It analyzes field types, validations,
 * and relationships to create proper table structures.
 * 
 * Features:
 * - Generates CREATE TABLE IF NOT EXISTS statements
 * - Maps CRUD6 field types to MySQL/PostgreSQL column types
 * - Creates proper indexes for foreign keys and unique constraints
 * - Handles primary keys, auto-increment, and default values
 * - Creates pivot tables for many-to-many relationships
 * - Adds foreign key constraints where appropriate
 * 
 * Usage: node generate-tables-from-schema.js [schema_directory] [output_file] [db_type]
 * Example: node generate-tables-from-schema.js examples/schema app/sql/migrations/schema-tables.sql mysql
 */

import fs from 'fs';
import path from 'path';

// Parse command line arguments
const args = process.argv.slice(2);
const DEFAULT_SCHEMA_DIR = 'examples/schema';
const DEFAULT_OUTPUT_FILE = 'app/sql/migrations/schema-tables.sql';
const DEFAULT_DB_TYPE = 'mysql';

const schemaDir = args[0] || DEFAULT_SCHEMA_DIR;
const outputFile = args[1] || DEFAULT_OUTPUT_FILE;
const dbType = (args[2] || DEFAULT_DB_TYPE).toLowerCase();

console.log('═══════════════════════════════════════════════════════════════');
console.log('Generate CREATE TABLE SQL from Schema Files');
console.log('═══════════════════════════════════════════════════════════════');
console.log(`Schema directory: ${schemaDir}`);
console.log(`Output file: ${outputFile}`);
console.log(`Database type: ${dbType}`);
console.log('');

/**
 * Map CRUD6 field types to SQL column types
 */
function mapFieldTypeToSQL(field, dbType) {
    const fieldType = field.type;
    const validation = field.validation || {};
    
    switch (fieldType) {
        case 'integer':
            if (field.auto_increment) {
                return dbType === 'postgresql' ? 'SERIAL' : 'INT AUTO_INCREMENT';
            }
            return 'INT';
            
        case 'string':
            const maxLength = validation.length?.max || 255;
            return `VARCHAR(${maxLength})`;
            
        case 'text':
            return 'TEXT';
            
        case 'boolean':
            return dbType === 'postgresql' ? 'BOOLEAN' : 'TINYINT(1)';
            
        case 'date':
            return 'DATE';
            
        case 'datetime':
        case 'timestamp':
            return dbType === 'postgresql' ? 'TIMESTAMP' : 'DATETIME';
            
        case 'decimal':
        case 'money':
            const precision = field.precision || 10;
            const scale = field.scale || 2;
            return `DECIMAL(${precision},${scale})`;
            
        case 'float':
            return 'FLOAT';
            
        case 'json':
            return 'JSON';
            
        case 'uuid':
            return dbType === 'postgresql' ? 'UUID' : 'CHAR(36)';
            
        default:
            return 'VARCHAR(255)';
    }
}

/**
 * Check if field should be nullable
 */
function isNullable(field) {
    if (field.required === true) return false;
    if (field.validation?.required === true) return false;
    if (field.auto_increment) return false;
    return true;
}

/**
 * Get default value for field
 */
function getDefaultValue(field, dbType) {
    if (field.default !== undefined) {
        if (typeof field.default === 'string') {
            // Handle special keywords
            if (field.default === 'CURRENT_TIMESTAMP' || field.default === 'now()') {
                return dbType === 'postgresql' ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP';
            }
            return `'${field.default}'`;
        }
        if (typeof field.default === 'boolean') {
            return field.default ? '1' : '0';
        }
        return field.default;
    }
    return null;
}

/**
 * Generate CREATE TABLE statement for a model
 */
function generateCreateTableSQL(schema, dbType) {
    const tableName = schema.table || schema.model;
    const primaryKey = schema.primary_key || 'id';
    const fields = schema.fields || {};
    
    const sql = [];
    sql.push(`-- Table: ${tableName}`);
    sql.push(`-- Model: ${schema.model}`);
    sql.push(`CREATE TABLE IF NOT EXISTS \`${tableName}\` (`);
    
    const columns = [];
    const indexes = [];
    const uniqueConstraints = [];
    
    // Process fields
    const fieldList = Array.isArray(fields) 
        ? fields 
        : Object.entries(fields).map(([name, field]) => ({name, ...field}));
    
    for (const field of fieldList) {
        const fieldName = field.name || Object.keys(field).find(k => k !== 'name');
        if (!fieldName) continue;
        
        const fieldDef = field.name ? field : field[fieldName];
        if (!fieldDef || !fieldDef.type) continue;
        
        const columnType = mapFieldTypeToSQL(fieldDef, dbType);
        const nullable = isNullable(fieldDef);
        const defaultValue = getDefaultValue(fieldDef, dbType);
        
        let columnDef = `  \`${fieldName}\` ${columnType}`;
        
        if (!nullable) {
            columnDef += ' NOT NULL';
        }
        
        if (defaultValue !== null) {
            columnDef += ` DEFAULT ${defaultValue}`;
        }
        
        // Check if this is the primary key
        if (fieldName === primaryKey) {
            columnDef += ' PRIMARY KEY';
        }
        
        columns.push(columnDef);
        
        // Add unique constraint
        if (fieldDef.validation?.unique && fieldName !== primaryKey) {
            uniqueConstraints.push(fieldName);
        }
        
        // Add index for foreign keys (fields ending with _id)
        if (fieldName.endsWith('_id') && fieldName !== primaryKey) {
            indexes.push(fieldName);
        }
    }
    
    // Add timestamp fields if not present
    const fieldNames = fieldList.map(f => f.name || Object.keys(f).find(k => k !== 'name'));
    if (!fieldNames.includes('created_at')) {
        const timestampType = dbType === 'postgresql' ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME DEFAULT CURRENT_TIMESTAMP';
        columns.push(`  \`created_at\` ${timestampType}`);
    }
    if (!fieldNames.includes('updated_at')) {
        const timestampType = dbType === 'postgresql' 
            ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' 
            : 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        columns.push(`  \`updated_at\` ${timestampType}`);
    }
    
    sql.push(columns.join(',\n'));
    sql.push(`)${dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''};`);
    sql.push('');
    
    // Add indexes
    for (const indexField of indexes) {
        sql.push(`CREATE INDEX IF NOT EXISTS \`idx_${tableName}_${indexField}\` ON \`${tableName}\`(\`${indexField}\`);`);
    }
    
    // Add unique constraints
    for (const uniqueField of uniqueConstraints) {
        sql.push(`CREATE UNIQUE INDEX IF NOT EXISTS \`uniq_${tableName}_${uniqueField}\` ON \`${tableName}\`(\`${uniqueField}\`);`);
    }
    
    if (indexes.length > 0 || uniqueConstraints.length > 0) {
        sql.push('');
    }
    
    return sql.join('\n');
}

/**
 * Generate pivot tables for many-to-many relationships
 */
function generatePivotTablesSQL(schemas, dbType) {
    const pivotTables = new Set();
    const sql = [];
    
    for (const { schema } of schemas) {
        if (!schema.relationships) continue;
        
        for (const rel of schema.relationships) {
            if ((rel.type === 'many_to_many' || rel.type === 'belongs_to_many') && rel.pivot_table) {
                // Skip if already processed
                if (pivotTables.has(rel.pivot_table)) continue;
                pivotTables.add(rel.pivot_table);
                
                const foreignKey = rel.foreign_key || `${schema.model.slice(0, -1)}_id`;
                const relatedKey = rel.related_key || `${rel.name.slice(0, -1)}_id`;
                
                sql.push(`-- Pivot Table: ${rel.pivot_table}`);
                sql.push(`-- Relationship: ${schema.model} <-> ${rel.name}`);
                sql.push(`CREATE TABLE IF NOT EXISTS \`${rel.pivot_table}\` (`);
                sql.push(`  \`${foreignKey}\` INT NOT NULL,`);
                sql.push(`  \`${relatedKey}\` INT NOT NULL,`);
                
                // Add created_at for tracking
                const timestampType = dbType === 'postgresql' ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME DEFAULT CURRENT_TIMESTAMP';
                sql.push(`  \`created_at\` ${timestampType},`);
                
                // Composite primary key
                sql.push(`  PRIMARY KEY (\`${foreignKey}\`, \`${relatedKey}\`)`);
                sql.push(`)${dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''};`);
                sql.push('');
                
                // Add foreign key indexes
                sql.push(`CREATE INDEX IF NOT EXISTS \`idx_${rel.pivot_table}_${foreignKey}\` ON \`${rel.pivot_table}\`(\`${foreignKey}\`);`);
                sql.push(`CREATE INDEX IF NOT EXISTS \`idx_${rel.pivot_table}_${relatedKey}\` ON \`${rel.pivot_table}\`(\`${relatedKey}\`);`);
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
    
    const schemas = [];
    
    for (const file of jsonFiles) {
        try {
            const filePath = path.join(schemaDir, file);
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
            
            console.log(`   ✅ Loaded: ${file} (model: ${schema.model}, table: ${schema.table})`);
        } catch (error) {
            console.error(`   ❌ Failed to load ${file}: ${error.message}`);
        }
    }
    
    console.log('');
    console.log(`✅ Loaded ${schemas.length} valid schemas`);
    console.log('');
    
    const allSQL = [];
    
    // SQL file header
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('-- CRUD6 Table Creation from Schemas');
    allSQL.push('-- Auto-generated from JSON schema files');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('--');
    allSQL.push('-- This file creates tables for CRUD6 models if they don\'t exist.');
    allSQL.push('-- It should be run AFTER core UserFrosting migrations.');
    allSQL.push('--');
    allSQL.push(`-- Generated: ${new Date().toISOString()}`);
    allSQL.push(`-- Database type: ${dbType}`);
    allSQL.push(`-- Source: ${schemaDir}`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push('');
    allSQL.push('SET FOREIGN_KEY_CHECKS=0;');
    allSQL.push('');
    
    // Generate CREATE TABLE for each schema
    console.log('Generating CREATE TABLE statements:');
    for (const { filename, model, schema } of schemas) {
        console.log(`  ✅ ${model}`);
        
        allSQL.push('-- ' + '-'.repeat(60));
        allSQL.push(`-- Schema: ${filename}`);
        allSQL.push('-- ' + '-'.repeat(60));
        allSQL.push('');
        allSQL.push(generateCreateTableSQL(schema, dbType));
        allSQL.push('');
    }
    
    // Generate pivot tables
    console.log('');
    console.log('Generating pivot tables:');
    const pivotSQL = generatePivotTablesSQL(schemas, dbType);
    if (pivotSQL) {
        allSQL.push('-- ' + '-'.repeat(60));
        allSQL.push('-- Pivot Tables for Many-to-Many Relationships');
        allSQL.push('-- ' + '-'.repeat(60));
        allSQL.push('');
        allSQL.push(pivotSQL);
        console.log('  ✅ Pivot tables generated');
    } else {
        console.log('  ℹ️  No pivot tables needed');
    }
    
    // SQL file footer
    allSQL.push('SET FOREIGN_KEY_CHECKS=1;');
    allSQL.push('');
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    allSQL.push(`-- Successfully generated ${schemas.length} table definitions`);
    allSQL.push('-- ═══════════════════════════════════════════════════════════════');
    
    console.log('');
    console.log(`✅ Generated ${schemas.length} table definitions`);
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
    console.log('✅ Table creation SQL generated successfully!');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Output file: ${outputFile}`);
    console.log(`File size: ${(sql.length / 1024).toFixed(2)} KB`);
    console.log('');
    console.log('Usage:');
    console.log(`  mysql -u user -p database < ${outputFile}`);
    console.log('  or integrate into UserFrosting migrations');
    console.log('');
    console.log('⚠️  IMPORTANT:');
    console.log('   - Run this AFTER UserFrosting core migrations');
    console.log('   - Tables use CREATE IF NOT EXISTS (safe to re-run)');
    console.log('   - Review generated SQL before applying to production');
    console.log('');
    
    process.exit(0);
} catch (error) {
    console.error('❌ Error:', error.message);
    console.error(error.stack);
    process.exit(1);
}
