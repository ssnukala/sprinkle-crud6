#!/usr/bin/env node

/**
 * Test script for DDL generator
 * 
 * This script creates a minimal test schema and verifies that the DDL generator
 * produces valid SQL output.
 */

import fs from 'fs';
import { execSync } from 'child_process';
import path from 'path';

const testDir = '/tmp/ddl-test';
const schemaFile = path.join(testDir, 'test-schema.json');
const outputFile = path.join(testDir, 'test-output.sql');

console.log('═══════════════════════════════════════════════════════════════');
console.log('DDL Generator Test');
console.log('═══════════════════════════════════════════════════════════════');
console.log('');

// Create test directory
if (!fs.existsSync(testDir)) {
    fs.mkdirSync(testDir, { recursive: true });
}

// Create test schema with various field types
const testSchema = {
    "model": "test_table",
    "table": "test_table",
    "title": "Test Table",
    "fields": {
        "id": {
            "type": "integer",
            "auto_increment": true
        },
        "name": {
            "type": "string",
            "required": true,
            "validation": {
                "length": {
                    "max": 100
                }
            }
        },
        "email": {
            "type": "email",
            "required": true,
            "validation": {
                "unique": true
            }
        },
        "age": {
            "type": "integer",
            "required": false
        },
        "is_active": {
            "type": "boolean",
            "default": true
        },
        "birth_date": {
            "type": "date",
            "required": false
        },
        "created_at": {
            "type": "datetime"
        },
        "price": {
            "type": "decimal"
        },
        "description": {
            "type": "text"
        },
        "metadata": {
            "type": "json"
        }
    }
};

// Write test schema
fs.writeFileSync(schemaFile, JSON.stringify(testSchema, null, 2));
console.log('✅ Created test schema:', schemaFile);

// Run DDL generator
try {
    const scriptPath = '.github/testing-framework/scripts/generate-ddl-sql.js';
    const command = `node ${scriptPath} ${testDir} ${outputFile}`;
    
    console.log('');
    console.log('Running DDL generator...');
    console.log('Command:', command);
    console.log('');
    
    execSync(command, { stdio: 'inherit' });
    
    console.log('');
    console.log('✅ DDL generator completed successfully');
    console.log('');
    
    // Read and verify output
    const sql = fs.readFileSync(outputFile, 'utf8');
    
    console.log('Verifying generated SQL...');
    console.log('');
    
    const checks = [
        { name: 'CREATE TABLE statement', pattern: /CREATE TABLE IF NOT EXISTS test_table/ },
        { name: 'ID column with AUTO_INCREMENT', pattern: /id INT AUTO_INCREMENT/ },
        { name: 'Name VARCHAR column', pattern: /name VARCHAR\(100\) NOT NULL/ },
        { name: 'Email column with UNIQUE', pattern: /email VARCHAR\(255\) NOT NULL/ },
        { name: 'Email UNIQUE constraint', pattern: /UNIQUE KEY email_unique \(email\)/ },
        { name: 'Age INT column', pattern: /age INT NULL/ },
        { name: 'Boolean column', pattern: /is_active TINYINT\(1\)/ },
        { name: 'Boolean DEFAULT', pattern: /DEFAULT 1/ },
        { name: 'Date column', pattern: /birth_date DATE/ },
        { name: 'Timestamp column', pattern: /created_at TIMESTAMP/ },
        { name: 'Decimal column', pattern: /price DECIMAL\(10,2\)/ },
        { name: 'Text column', pattern: /description TEXT/ },
        { name: 'JSON column', pattern: /metadata JSON/ },
        { name: 'Primary key', pattern: /PRIMARY KEY \(id\)/ },
        { name: 'InnoDB engine', pattern: /ENGINE=InnoDB/ },
        { name: 'UTF8MB4 charset', pattern: /DEFAULT CHARSET=utf8mb4/ },
    ];
    
    let passed = 0;
    let failed = 0;
    
    for (const check of checks) {
        if (check.pattern.test(sql)) {
            console.log(`  ✅ ${check.name}`);
            passed++;
        } else {
            console.log(`  ❌ ${check.name}`);
            failed++;
        }
    }
    
    console.log('');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Test Results: ${passed} passed, ${failed} failed`);
    console.log('═══════════════════════════════════════════════════════════════');
    
    if (failed > 0) {
        console.log('');
        console.log('Generated SQL:');
        console.log('─'.repeat(60));
        console.log(sql);
        console.log('─'.repeat(60));
        process.exit(1);
    }
    
    console.log('');
    console.log('✅ All tests passed!');
    
    // Cleanup
    fs.rmSync(testDir, { recursive: true, force: true });
    
    process.exit(0);
    
} catch (error) {
    console.error('');
    console.error('❌ Test failed:', error.message);
    process.exit(1);
}
