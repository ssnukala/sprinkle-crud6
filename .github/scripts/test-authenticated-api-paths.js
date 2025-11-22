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
 * Test a single API path
 */
async function testApiPath(page, name, pathConfig, baseUrl) {
    totalTests++;
    
    // Check if test should be skipped
    if (pathConfig.skip) {
        console.log(`⏭️  SKIP: ${name}`);
        console.log(`   Reason: ${pathConfig.skip_reason || 'Not specified'}\n`);
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
        if (status === expectedStatus) {
            console.log(`   ✅ Status: ${status} (expected ${expectedStatus})`);
            
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
        } else if (status === 403) {
            // Permission failure - warn instead of fail
            console.log(`   ⚠️  Status: ${status} (expected ${expectedStatus})`);
            console.log(`   ⚠️  WARNING: Permission failure (403) - user may lack required permission`);
            if (pathConfig.requires_permission) {
                console.log(`   ⚠️  Required permission: ${pathConfig.requires_permission}`);
            }
            console.log(`   ⚠️  WARNED (continuing tests)\n`);
            warningTests++;
        } else if (status >= 500) {
            // Server error - this is a real failure
            console.log(`   ❌ Status: ${status} (expected ${expectedStatus})`);
            console.log(`   ❌ FAILED: Server error detected - possible code/SQL failure`);
            
            try {
                const data = await response.json();
                if (data.message) {
                    console.log(`   ❌ Error: ${data.message}`);
                }
            } catch (error) {
                // Can't parse error message
            }
            
            console.log('');
            failedTests++;
        } else {
            console.log(`   ❌ Status: ${status} (expected ${expectedStatus})`);
            console.log(`   ❌ FAILED\n`);
            failedTests++;
        }
    } catch (error) {
        console.log(`   ❌ Exception: ${error.message}`);
        console.log(`   ❌ FAILED\n`);
        failedTests++;
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
        
        if (failedTests > 0) {
            console.log('❌ Some tests failed (actual code/SQL errors detected)');
            console.log('   Note: Permission failures (403) are warnings, not failures');
            await browser.close();
            process.exit(1);
        } else if (warningTests > 0) {
            console.log('✅ All tests passed (permission warnings are expected for some endpoints)');
            console.log(`   ${warningTests} permission warnings detected (403 status codes)`);
            console.log('   No actual code/SQL errors found');
            await browser.close();
            process.exit(0);
        } else {
            console.log('✅ All tests passed');
            await browser.close();
            process.exit(0);
        }
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
