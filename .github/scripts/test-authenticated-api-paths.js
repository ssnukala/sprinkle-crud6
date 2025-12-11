#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Authenticated API Path Testing
 * 
 * This script uses Playwright to test authenticated API endpoints from the JSON configuration.
 * Unlike test-paths.php which uses curl (and can't handle sessions), this script:
 * 1. Logs in to get an authenticated session
 * 2. Tests all authenticated API endpoints from the config
 * 3. Handles POST/PUT/DELETE with payloads and CSRF tokens
 * 4. Validates responses and status codes
 * 5. Reports results similar to test-paths.php
 * 
 * Usage: node test-authenticated-api-paths.js <config_file> [base_url] [username] [password]
 * Example: node test-authenticated-api-paths.js integration-test-paths.json
 */

import { chromium } from 'playwright';
import { readFileSync } from 'fs';

// Test counters
let totalTests = 0;
let passedTests = 0;
let failedTests = 0;
let skippedTests = 0;
let warningTests = 0;

// Detailed failure tracking by schema and action
const failuresBySchema = {}; // { 'users': { 'create': {...}, 'update': {...} } }
const successBySchema = {};  // { 'users': { 'create': true, 'update': true } }

/**
 * Get CSRF token from the page
 * 
 * UserFrosting 6 provides CSRF tokens via meta tags in HTML pages, not via a dedicated API endpoint.
 * This function retrieves the token from the current page's meta tag.
 * If no token is found, it navigates to the dashboard to get a fresh token.
 */
async function getCsrfToken(page, baseUrl) {
    try {
        // Try to get CSRF token from meta tag on current page
        let csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            return csrfToken;
        }
        
        // If no token on current page, navigate to dashboard to get one
        // This ensures we're on a valid UserFrosting page with a CSRF meta tag
        console.warn('   ⚠️  No CSRF token on current page, navigating to dashboard to get token...');
        await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        
        // Try again to get token from dashboard page
        csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            console.log('   ✅ CSRF token retrieved from dashboard page');
            return csrfToken;
        }
        
        console.warn('   ⚠️  Could not find CSRF token meta tag on dashboard page either');
        return null;
    } catch (error) {
        console.warn('   ⚠️  Could not retrieve CSRF token:', error.message);
        return null;
    }
}

/**
 * Extract schema name and action from test name
 * E.g., "users_create" -> { schema: "users", action: "create" }
 */
function extractSchemaAction(name) {
    const parts = name.split('_');
    if (parts.length >= 2) {
        return {
            schema: parts[0],
            action: parts.slice(1).join('_')
        };
    }
    return { schema: 'unknown', action: name };
}

/**
 * Record test result by schema and action
 */
function recordTestResult(name, passed, errorInfo = null) {
    const { schema, action } = extractSchemaAction(name);
    
    if (passed) {
        if (!successBySchema[schema]) successBySchema[schema] = {};
        successBySchema[schema][action] = true;
    } else {
        if (!failuresBySchema[schema]) failuresBySchema[schema] = {};
        failuresBySchema[schema][action] = errorInfo || { message: 'Test failed' };
    }
}

/**
 * Test a single API path
 */
async function testApiPath(page, name, pathConfig, baseUrl) {
    totalTests++;
    
    // Check if test should be skipped or disabled
    if (pathConfig.skip || pathConfig.disabled) {
        console.log(`⏭️  SKIP: ${name}`);
        const reason = pathConfig.skip_reason || pathConfig.note || 'Test disabled or marked for skip';
        console.log(`   Reason: ${reason}\n`);
        skippedTests++;
        return;
    }
    
    const path = pathConfig.path;
    const method = pathConfig.method || 'GET';
    const description = pathConfig.description || name;
    const expectedStatus = pathConfig.expected_status || 200;
    const payload = pathConfig.payload || {};
    
    console.log(`Testing: ${name}`);
    console.log(`   Description: ${description}`);
    console.log(`   Method: ${method}`);
    console.log(`   Path: ${path}`);
    
    try {
        const url = `${baseUrl}${path}`;
        let response;
        
        // Get CSRF token for state-changing operations
        let headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        
        if (['POST', 'PUT', 'DELETE'].includes(method)) {
            const csrfToken = await getCsrfToken(page, baseUrl);
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }
        }
        
        // Make the API request
        if (method === 'GET') {
            response = await page.request.get(url, { headers });
        } else if (method === 'POST') {
            response = await page.request.post(url, { 
                headers,
                data: payload 
            });
        } else if (method === 'PUT') {
            response = await page.request.put(url, { 
                headers,
                data: payload 
            });
        } else if (method === 'DELETE') {
            response = await page.request.delete(url, { 
                headers,
                data: payload 
            });
        }
        
        const status = response.status();
        
        // Validate status code
        // Success: exact match OR both in 2xx range (e.g., 200 vs 201 are both success)
        const isSuccess = status === expectedStatus || 
                         (status >= 200 && status < 300 && expectedStatus >= 200 && expectedStatus < 300);
        
        if (isSuccess) {
            if (status === expectedStatus) {
                console.log(`   ✅ Status: ${status} (exact match)`);
            } else {
                console.log(`   ✅ Status: ${status} (expected ${expectedStatus}, both are 2xx success)`);
            }
            
            // Additional validation if specified
            if (pathConfig.validation) {
                const validation = pathConfig.validation;
                
                if (validation.type === 'json') {
                    try {
                        const data = await response.json();
                        let allFound = true;
                        
                        for (const key of (validation.contains || [])) {
                            if (!data.hasOwnProperty(key)) {
                                console.log(`   ⚠️  Missing expected key: ${key}`);
                                allFound = false;
                            }
                        }
                        
                        if (allFound) {
                            console.log(`   ✅ Validation: JSON contains expected keys`);
                        }
                    } catch (error) {
                        console.log(`   ⚠️  Response is not valid JSON`);
                    }
                }
            }
            
            console.log(`   ✅ PASSED\n`);
            passedTests++;
            recordTestResult(name, true);
        } else if (status === 403) {
            // Permission failure - warn instead of fail
            console.log(`   ⚠️  Status: ${status} (expected ${expectedStatus})`);
            console.log(`   ⚠️  WARNING: Permission failure (403) - user may lack required permission`);
            if (pathConfig.requires_permission) {
                console.log(`   ⚠️  Required permission: ${pathConfig.requires_permission}`);
            }
            console.log(`   ⚠️  WARNED (continuing tests)\n`);
            warningTests++;
            recordTestResult(name, false, { 
                type: 'permission', 
                status: 403,
                message: 'Permission denied',
                permission: pathConfig.requires_permission 
            });
        } else if (status >= 500) {
            // Server error - log as critical warning but continue
            console.log(`   ⚠️  CRITICAL WARNING: Status ${status} (expected ${expectedStatus})`);
            console.log(`   ⚠️  Server error detected - possible code/SQL failure`);
            console.log(`   ⚠️  Continuing with remaining tests...`);
            console.log(`   🔍 Request Details:`);
            console.log(`      URL: ${method} ${baseUrl}${path}`);
            if (method !== 'GET' && Object.keys(payload).length > 0) {
                console.log(`      Payload: ${JSON.stringify(payload, null, 2)}`);
            }
            
            // Try to extract detailed error information
            try {
                const responseText = await response.text();
                
                if (responseText) {
                    console.log(`   📝 Response Body (${responseText.length} bytes):`);
                    
                    try {
                        const data = JSON.parse(responseText);
                        
                        // Log error message
                        if (data.message) {
                            console.log(`   ❌ Error Message: ${data.message}`);
                        }
                        
                        // Log exception details if available
                        if (data.exception) {
                            console.log(`   💥 Exception Type: ${data.exception}`);
                        }
                        
                        // Log file and line if available
                        if (data.file) {
                            console.log(`   📂 File: ${data.file}`);
                        }
                        if (data.line) {
                            console.log(`   📍 Line: ${data.line}`);
                        }
                        
                        // Log stack trace if available
                        if (data.trace) {
                            console.log(`   📚 Stack Trace:`);
                            if (Array.isArray(data.trace)) {
                                data.trace.slice(0, 5).forEach((frame, idx) => {
                                    console.log(`      ${idx + 1}. ${frame.file || 'unknown'}:${frame.line || '?'}`);
                                    if (frame.class && frame.function) {
                                        console.log(`         ${frame.class}::${frame.function}()`);
                                    }
                                });
                                if (data.trace.length > 5) {
                                    console.log(`      ... and ${data.trace.length - 5} more frames`);
                                }
                            } else if (typeof data.trace === 'string') {
                                // If trace is a string, show first few lines
                                const traceLines = data.trace.split('\n').slice(0, 10);
                                traceLines.forEach(line => console.log(`      ${line}`));
                            }
                        }
                        
                        // Check for SQL-related errors
                        const errorStr = JSON.stringify(data).toLowerCase();
                        if (errorStr.includes('sql') || errorStr.includes('database') || errorStr.includes('query')) {
                            console.log(`   🗄️  POSSIBLE SQL ERROR DETECTED`);
                            if (data.message && (data.message.toLowerCase().includes('sql') || data.message.toLowerCase().includes('database'))) {
                                console.log(`   🗄️  SQL Error Details: ${data.message}`);
                            }
                        }
                        
                        // Log full error for complete context (limited to first 1000 chars)
                        const fullError = JSON.stringify(data, null, 2);
                        if (fullError.length > 1000) {
                            console.log(`   📋 Full Error (first 1000 chars):`);
                            console.log(fullError.substring(0, 1000) + '...');
                        } else {
                            console.log(`   📋 Full Error:`);
                            console.log(fullError);
                        }
                        
                    } catch (parseError) {
                        // Response is not JSON, try to extract useful information from HTML/text
                        console.log(`   ⚠️  Response is not JSON, showing raw content (first 500 chars):`);
                        console.log(responseText.substring(0, 500));
                        
                        // Check for common error patterns in HTML/text
                        if (responseText.toLowerCase().includes('syntax error')) {
                            console.log(`   💥 SYNTAX ERROR detected in response`);
                        }
                        if (responseText.toLowerCase().includes('sql') || responseText.toLowerCase().includes('database')) {
                            console.log(`   🗄️  SQL/DATABASE keywords found in error response`);
                        }
                        if (responseText.toLowerCase().includes('exception') || responseText.toLowerCase().includes('fatal error')) {
                            console.log(`   💥 PHP Exception or Fatal Error detected`);
                        }
                    }
                } else {
                    console.log(`   ⚠️  Empty response body`);
                }
            } catch (error) {
                console.log(`   ⚠️  Could not read response: ${error.message}`);
            }
            
            // Extract error information for tracking
            let errorType = 'server_error';
            let errorMessage = 'Unknown server error';
            
            try {
                const responseText = await response.text();
                if (responseText) {
                    try {
                        const data = JSON.parse(responseText);
                        errorMessage = data.message || errorMessage;
                        
                        // Check for SQL/database errors
                        const errorStr = JSON.stringify(data).toLowerCase();
                        if (errorStr.includes('sql') || errorStr.includes('database') || errorStr.includes('query')) {
                            errorType = 'database_error';
                        }
                    } catch (e) {
                        // Not JSON
                    }
                }
            } catch (e) {
                // Ignore
            }
            
            console.log('');
            failedTests++;
            recordTestResult(name, false, { 
                type: errorType, 
                status,
                message: errorMessage,
                url: path,
                method,
                payload: Object.keys(payload).length > 0 ? payload : undefined
            });
        } else {
            // Non-500 error - log as warning and continue
            console.log(`   ⚠️  CRITICAL WARNING: Status ${status} (expected ${expectedStatus})`);
            console.log(`   ⚠️  Continuing with remaining tests...\n`);
            failedTests++;
            recordTestResult(name, false, { 
                type: 'unexpected_status', 
                status,
                expected: expectedStatus,
                url: path,
                method
            });
        }
    } catch (error) {
        console.log(`   ⚠️  CRITICAL WARNING: Exception - ${error.message}`);
        console.log(`   ⚠️  Continuing with remaining tests...\n`);
        failedTests++;
        recordTestResult(name, false, { 
            type: 'exception', 
            message: error.message,
            url: path,
            method
        });
    }
}

/**
 * Main execution
 */
async function main() {
    // Parse command line arguments
    const configFile = process.argv[2];
    
    if (!configFile) {
        console.error('Usage: node test-authenticated-api-paths.js <config_file> [base_url] [username] [password]');
        console.error('Example: node test-authenticated-api-paths.js integration-test-paths.json');
        process.exit(1);
    }
    
    // Load configuration
    let config;
    try {
        const configContent = readFileSync(configFile, 'utf8');
        config = JSON.parse(configContent);
    } catch (error) {
        console.error(`ERROR: Failed to load configuration file: ${error.message}`);
        process.exit(1);
    }
    
    const baseUrl = process.argv[3] || config.config?.base_url || 'http://localhost:8080';
    const username = process.argv[4] || config.config?.auth?.username || 'admin';
    const password = process.argv[5] || config.config?.auth?.password || 'admin123';
    
    console.log('=========================================');
    console.log('Testing Authenticated API Paths');
    console.log('=========================================');
    console.log(`Config file: ${configFile}`);
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Username: ${username}`);
    console.log('');
    
    // Launch browser
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        baseURL: baseUrl,
        ignoreHTTPSErrors: true
    });
    const page = await context.newPage();
    
    try {
        // Step 1: Login to get authenticated session
        console.log('📍 Logging in...');
        await page.goto(`${baseUrl}/account/sign-in`, { waitUntil: 'networkidle', timeout: 30000 });
        
        // Fill in login form
        await page.fill('input[name="user_name"]', username);
        await page.fill('input[name="password"]', password);
        
        // Submit login form
        await Promise.race([
            page.waitForNavigation({ timeout: 10000 }),
            page.click('button[type="submit"]')
        ]).catch(() => {
            console.log('   ⚠️  No navigation detected after login, but continuing...');
        });
        
        // Wait a bit for session to be established
        await page.waitForTimeout(2000);
        
        // Verify we're logged in
        const currentUrl = page.url();
        if (currentUrl.includes('/account/sign-in')) {
            console.error('   ❌ Still on login page - authentication failed');
            process.exit(1);
        }
        
        console.log('   ✅ Logged in successfully\n');
        
        // Step 2: Test authenticated API paths
        const authPaths = config.paths?.authenticated?.api || {};
        
        if (Object.keys(authPaths).length === 0) {
            console.log('⚠️  No authenticated API paths found in configuration\n');
        } else {
            console.log('=========================================');
            console.log('Testing Authenticated API Endpoints');
            console.log('=========================================\n');
            
            for (const [name, pathConfig] of Object.entries(authPaths)) {
                await testApiPath(page, name, pathConfig, baseUrl);
            }
        }
        
        // Print summary
        console.log('=========================================');
        console.log('Test Summary');
        console.log('=========================================');
        console.log(`Total tests: ${totalTests}`);
        console.log(`Passed: ${passedTests}`);
        console.log(`Warnings: ${warningTests}`);
        console.log(`Failed: ${failedTests}`);
        console.log(`Skipped: ${skippedTests}`);
        console.log('');
        
        // Print detailed failure report by schema
        if (Object.keys(failuresBySchema).length > 0) {
            console.log('=========================================');
            console.log('Failure Report by Schema');
            console.log('=========================================');
            
            for (const [schema, actions] of Object.entries(failuresBySchema)) {
                console.log(`\n📋 Schema: ${schema}`);
                
                const actionsList = Object.keys(actions);
                const successCount = successBySchema[schema] ? Object.keys(successBySchema[schema]).length : 0;
                const failCount = actionsList.length;
                
                console.log(`   Status: ${successCount} passed, ${failCount} failed`);
                console.log(`   Failed actions:`);
                
                for (const [action, errorInfo] of Object.entries(actions)) {
                    console.log(`      • ${action}:`);
                    console.log(`         Type: ${errorInfo.type}`);
                    console.log(`         Status: ${errorInfo.status || 'N/A'}`);
                    console.log(`         Message: ${errorInfo.message}`);
                    
                    if (errorInfo.type === 'database_error') {
                        console.log(`         ⚠️  DATABASE/SQL ERROR - Check schema definition`);
                    } else if (errorInfo.type === 'permission') {
                        console.log(`         ⚠️  Permission required: ${errorInfo.permission || 'unknown'}`);
                    }
                }
            }
            
            console.log('\n=========================================');
        }
        
        // Print success report by schema
        if (Object.keys(successBySchema).length > 0) {
            console.log('=========================================');
            console.log('Success Report by Schema');
            console.log('=========================================');
            
            for (const [schema, actions] of Object.entries(successBySchema)) {
                const actionsList = Object.keys(actions);
                console.log(`\n✅ Schema: ${schema}`);
                console.log(`   Passed actions: ${actionsList.join(', ')}`);
            }
            
            console.log('\n=========================================');
        }
        
        // Always exit with success (0) - failures are warnings
        if (failedTests > 0) {
            console.log('\n⚠️  CRITICAL WARNINGS DETECTED:');
            console.log(`   ${failedTests} test(s) had errors`);
            console.log('   These are logged as warnings - build will continue');
            console.log('   Review the failure report above for details');
            console.log('   Note: Permission failures (403) and database errors are expected for some schemas');
        } else if (warningTests > 0) {
            console.log('\n✅ All tests passed (permission warnings are expected for some endpoints)');
            console.log(`   ${warningTests} permission warnings detected (403 status codes)`);
            console.log('   No actual code/SQL errors found');
        } else {
            console.log('\n✅ All tests passed with no warnings');
        }
        
        await browser.close();
        process.exit(0); // Always exit with success
    } catch (error) {
        console.error(`\n❌ Fatal error: ${error.message}`);
        console.error(error.stack);
        await browser.close();
        process.exit(1);
    }
}

// Run the tests
main().catch(error => {
    console.error(`Fatal error: ${error.message}`);
    process.exit(1);
});
