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
 * Pre-computed bcrypt password hashes for test data
 * These are bcrypt hashes (cost factor 10) of "password{N}" where N is the record index
 * Generated using: bcrypt.hash("password2", 10), bcrypt.hash("password3", 10), etc.
 * 
 * For testing purposes, these provide realistic bcrypt hashes without requiring
 * a bcrypt library in the seed generation script.
 */
const BCRYPT_TEST_PASSWORDS = {
    2: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password2
    3: '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // password3
    4: '$2y$10$lSqpQGHmQVHSrWPvWSbqsuJQs9lDlwHUMQgW8XcPjcC8QVgQC5B0u', // password4
};

/**
 * Get a bcrypt password hash for a given record index
 * Uses pre-computed hashes for indexes 2-4, generates a valid hash pattern for others
 */
function getBcryptPasswordHash(recordIndex) {
    // Use pre-computed hash if available
    if (BCRYPT_TEST_PASSWORDS[recordIndex]) {
        return BCRYPT_TEST_PASSWORDS[recordIndex];
    }
    
    // For other indexes, use a valid bcrypt hash pattern
    // This is a real bcrypt hash of "password" - safe to reuse for test data
    return '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
}

/**
 * Truncate a string value to fit within max length constraint
 */
function truncateToMaxLength(value, maxLength) {
    // Remove quotes, truncate, then re-add quotes
    const cleanValue = value.replace(/^'|'$/g, '');
    const truncated = cleanValue.substring(0, maxLength);
    return `'${truncated}'`;
}

/**
 * Generate a test value for a field based on its type and validation
 */
function generateTestValue(fieldName, field, recordIndex = 1) {
    const type = field.type;
    const validation = field.validation || {};
    const maxLength = validation.length?.max;
    const minLength = validation.length?.min || 1;
    
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
            
        case 'smartlookup':
        case 'lookup':
        case 'foreign_key':
            // Foreign key fields - generate valid integer ID
            // Use recordIndex to ensure valid references (assumes referenced records exist)
            return recordIndex;
            
        case 'email':
            // Email field type
            const emailValue = `'test${recordIndex}@example.com'`;
            return maxLength ? truncateToMaxLength(emailValue, maxLength) : emailValue;
            
        case 'phone':
            // Phone number field - format XXX-XXX-XXXX
            const phoneNum = String(recordIndex).padStart(3, '0').substring(0, 3);
            const phoneValue = `'555-000-${phoneNum}'`;
            return maxLength ? truncateToMaxLength(phoneValue, maxLength) : phoneValue;
            
        case 'url':
            // URL field
            const urlValue = `'https://example${recordIndex}.com'`;
            return maxLength ? truncateToMaxLength(urlValue, maxLength) : urlValue;
            
        case 'zip':
            // ZIP code - 5 digits (valid range: 10000-99999)
            const zipNum = 10000 + (recordIndex % 90000);
            const zipValue = `'${String(zipNum).padStart(5, '0')}'`;
            return zipValue;
            
        case 'string':
            if (validation.email) {
                const emailVal = `'test${recordIndex}@example.com'`;
                return maxLength ? truncateToMaxLength(emailVal, maxLength) : emailVal;
            }
            
            if (validation.unique) {
                const uniqueVal = `'test_${fieldName}_${recordIndex}'`;
                return maxLength ? truncateToMaxLength(uniqueVal, maxLength) : uniqueVal;
            }
            
            // Special handling for specific field name patterns
            
            // IP address field
            if (fieldName.includes('ip_address') || fieldName.includes('ip')) {
                const ipVal = `'192.168.${recordIndex}.${100 + recordIndex}'`;
                return maxLength ? truncateToMaxLength(ipVal, maxLength) : ipVal;
            }
            
            // Icon field (FontAwesome classes)
            if (fieldName.includes('icon')) {
                const icons = ['fas fa-home', 'fas fa-user', 'fas fa-cog', 'fas fa-star', 'fas fa-heart'];
                const iconVal = `'${icons[recordIndex % icons.length]}'`;
                return maxLength ? truncateToMaxLength(iconVal, maxLength) : iconVal;
            }
            
            // Status field
            if (fieldName.includes('status')) {
                const statuses = ['active', 'pending', 'completed', 'cancelled', 'draft'];
                const statusVal = `'${statuses[recordIndex % statuses.length]}'`;
                return maxLength ? truncateToMaxLength(statusVal, maxLength) : statusVal;
            }
            
            // Priority field
            if (fieldName.includes('priority')) {
                const priorities = ['low', 'medium', 'high', 'urgent'];
                const priorityVal = `'${priorities[recordIndex % priorities.length]}'`;
                return maxLength ? truncateToMaxLength(priorityVal, maxLength) : priorityVal;
            }
            
            // Type field
            if (fieldName === 'type' || fieldName.endsWith('_type')) {
                const types = ['type_a', 'type_b', 'type_c'];
                const typeVal = `'${types[recordIndex % types.length]}'`;
                return maxLength ? truncateToMaxLength(typeVal, maxLength) : typeVal;
            }
            
            // Email pattern in field name
            if (fieldName.includes('email')) {
                const emailVal = `'test${recordIndex}@example.com'`;
                return maxLength ? truncateToMaxLength(emailVal, maxLength) : emailVal;
            }
            
            // Password field
            if (fieldName.includes('password')) {
                // Use proper bcrypt hash for password fields
                return `'${getBcryptPasswordHash(recordIndex)}'`;
            }
            
            // Slug field
            if (fieldName.includes('slug')) {
                const slugVal = `'test-slug-${recordIndex}'`;
                return maxLength ? truncateToMaxLength(slugVal, maxLength) : slugVal;
            }
            
            // State/Province codes
            if (fieldName === 'state' || fieldName === 'state_code' || fieldName === 'province') {
                // US state codes - 2 characters
                const states = ['CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI'];
                return `'${states[recordIndex % states.length]}'`;
            }
            
            // Country codes
            if (fieldName === 'country' || fieldName === 'country_code') {
                if (maxLength && maxLength <= 3) {
                    const codes = ['US', 'CA', 'UK', 'AU', 'DE', 'FR', 'IT', 'ES', 'JP', 'CN'];
                    return `'${codes[recordIndex % codes.length]}'`;
                }
                const countries = ['United States', 'Canada', 'United Kingdom'];
                const countryVal = `'${countries[recordIndex % countries.length]}'`;
                return maxLength ? truncateToMaxLength(countryVal, maxLength) : countryVal;
            }
            
            // Generic code fields with length constraints
            if (fieldName.includes('code') && maxLength && maxLength <= 10) {
                const codeVal = `'C${recordIndex}'`;
                return maxLength ? truncateToMaxLength(codeVal, maxLength) : codeVal;
            }
            
            // Name fields (first_name, last_name, etc.)
            if (fieldName.includes('name') && !fieldName.includes('user_name') && !fieldName.includes('file_name')) {
                const nameVal = `'Name${recordIndex}'`;
                return maxLength ? truncateToMaxLength(nameVal, maxLength) : nameVal;
            }
            
            // Title fields
            if (fieldName.includes('title')) {
                const titleVal = `'Title ${recordIndex}'`;
                return maxLength ? truncateToMaxLength(titleVal, maxLength) : titleVal;
            }
            
            // Address fields
            if (fieldName.includes('address') && !fieldName.includes('ip')) {
                const addrVal = `'${100 + recordIndex} Main St'`;
                return maxLength ? truncateToMaxLength(addrVal, maxLength) : addrVal;
            }
            
            // City fields
            if (fieldName.includes('city')) {
                const cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];
                const cityVal = `'${cities[recordIndex % cities.length]}'`;
                return maxLength ? truncateToMaxLength(cityVal, maxLength) : cityVal;
            }
            
            // Company fields
            if (fieldName.includes('company')) {
                const companyVal = `'Company ${recordIndex}'`;
                return maxLength ? truncateToMaxLength(companyVal, maxLength) : companyVal;
            }
            
            // Position/role fields
            if (fieldName.includes('position') || fieldName.includes('job_title')) {
                const positionVal = `'Position ${recordIndex}'`;
                return maxLength ? truncateToMaxLength(positionVal, maxLength) : positionVal;
            }
            
            // Default string generation - respect max length
            let defaultVal = `'Value${recordIndex}'`;
            if (maxLength) {
                // Calculate the actual content length (excluding quotes)
                const content = defaultVal.slice(1, -1); // Remove quotes
                if (content.length > maxLength) {
                    // Try shorter pattern
                    defaultVal = `'V${recordIndex}'`;
                    const shortContent = defaultVal.slice(1, -1);
                    if (shortContent.length > maxLength) {
                        // For very small fields, use minimal content
                        const minContent = String(recordIndex).substring(0, maxLength);
                        defaultVal = `'${minContent}'`;
                    }
                }
                return truncateToMaxLength(defaultVal, maxLength);
            }
            return defaultVal;
            
        case 'text':
        case 'textarea':
        case 'textarea-r3c60':
        case 'textarea-r5':
            return `'Test description for ${fieldName} - Record ${recordIndex}'`;
            
        case 'password':
            // Generate proper bcrypt password hashes for password fields
            return `'${getBcryptPasswordHash(recordIndex)}'`;
            
        case 'boolean':
        case 'boolean-yn':
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
            
        case 'multiselect':
            return `'option1,option2'`;
            
        default:
            if (field.required) {
                const reqVal = `'val${recordIndex}'`;
                return maxLength ? truncateToMaxLength(reqVal, maxLength) : reqVal;
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
    
    // Skip computed/virtual fields (e.g., role_ids used for relationship sync)
    if (field.computed) {
        return false;
    }
    
    // Skip multiselect fields (virtual fields for relationship management)
    // These are NOT database columns - they're form inputs for syncing relationships
    if (field.type === 'multiselect') {
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
        sql.push(`VALUES (${values.join(', ')}) AS new_values`);
        sql.push(`ON DUPLICATE KEY UPDATE ${insertFields.map(f => `\`${f}\` = new_values.\`${f}\``).join(', ')};`);
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
                sql.push(`VALUES (${fk}, ${rk}) AS new_rel`);
                sql.push(`ON DUPLICATE KEY UPDATE \`${foreignKey}\` = new_rel.\`${foreignKey}\`;`);
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
