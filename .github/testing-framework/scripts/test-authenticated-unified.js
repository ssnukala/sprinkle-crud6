#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Unified Authenticated Testing
 * 
 * This script performs login and then immediately tests authenticated API endpoints
 * and frontend pages in a single session. This approach is more reliable than
 * separating login from testing because the session remains active throughout.
 * 
 * Usage: node test-authenticated-unified.js <config_file> [base_url] [username] [password]
 * Example: node test-authenticated-unified.js integration-test-paths.json
 * Example: node test-authenticated-unified.js integration-test-paths.json http://localhost:8080 admin admin123
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'fs';

async function testAuthenticatedUnified(configFile, baseUrlOverride, usernameOverride, passwordOverride) {
    console.log('========================================');
    console.log('Unified Authenticated Testing');
    console.log('Login + API + Frontend Tests in One Session');
    console.log('========================================');
    console.log(`Config file: ${configFile}`);
    console.log('');

    // Load configuration
    let config;
    try {
        const configContent = readFileSync(configFile, 'utf8');
        config = JSON.parse(configContent);
    } catch (error) {
        console.error(`❌ Failed to load configuration: ${error.message}`);
        process.exit(1);
    }

    // Get credentials from config or command line
    const baseUrl = baseUrlOverride || config.config?.base_url || 'http://localhost:8080';
    const username = usernameOverride || config.config?.auth?.username || 'admin';
    const password = passwordOverride || config.config?.auth?.password || 'admin123';

    console.log(`Base URL: ${baseUrl}`);
    console.log(`Username: ${username}`);
    console.log('');

    // Collect API paths to test
    const apiPaths = [];
    if (config.paths?.authenticated?.api) {
        for (const [name, pathConfig] of Object.entries(config.paths.authenticated.api)) {
            if (!pathConfig.skip) {
                apiPaths.push({
                    name,
                    path: pathConfig.path,
                    method: pathConfig.method || 'GET',
                    description: pathConfig.description || name,
                    expected_status: pathConfig.expected_status || 200,
                    payload: pathConfig.payload || {}
                });
            }
        }
    }

    // Collect frontend paths to test
    const frontendPaths = [];
    if (config.paths?.authenticated?.frontend) {
        for (const [name, pathConfig] of Object.entries(config.paths.authenticated.frontend)) {
            if (!pathConfig.skip) {
                frontendPaths.push({
                    name,
                    path: pathConfig.path,
                    description: pathConfig.description || name,
                    expected_status: pathConfig.expected_status || 200,
                    screenshot: pathConfig.screenshot || false,
                    screenshot_name: pathConfig.screenshot_name || name
                });
            }
        }
    }

    console.log(`Found ${apiPaths.length} API paths to test`);
    console.log(`Found ${frontendPaths.length} frontend paths to test`);
    console.log('');

    // Launch browser
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    let page; // Declare page outside try block for error handling

    try {
        const context = await browser.newContext({
            viewport: { width: 1280, height: 720 },
            ignoreHTTPSErrors: true
        });

        page = await context.newPage();

        // ========================================
        // STEP 1: Login
        // ========================================
        console.log('========================================');
        console.log('STEP 1: Login');
        console.log('========================================');
        console.log('📍 Navigating to login page...');
        await page.goto(`${baseUrl}/account/sign-in`, { waitUntil: 'networkidle', timeout: 30000 });
        console.log('✅ Login page loaded');

        console.log('🔐 Logging in...');
        
        // Wait for the login form to be visible
        await page.waitForSelector('.uk-card input[data-test="username"]', { timeout: 10000 });
        
        // Fill in credentials
        await page.fill('.uk-card input[data-test="username"]', username);
        await page.fill('.uk-card input[data-test="password"]', password);
        
        // Click the login button and wait for navigation
        await Promise.all([
            page.waitForNavigation({ timeout: 15000 }).catch(() => {
                console.log('⚠️  No navigation detected after login, but continuing...');
            }),
            page.click('.uk-card button[data-test="submit"]')
        ]);
        
        console.log('✅ Logged in successfully');
        
        // Give session a moment to stabilize
        await page.waitForTimeout(2000);

        // Verify authentication by checking we're not on login page
        const currentUrl = page.url();
        if (currentUrl.includes('/account/sign-in')) {
            console.error('❌ Login failed: Still on login page');
            await page.screenshot({ path: '/tmp/login-failed.png', fullPage: true });
            console.error('📸 Login failure screenshot saved to /tmp/login-failed.png');
            process.exit(1);
        }
        console.log(`✅ Authentication verified: ${currentUrl}`);
        console.log('');

        // ========================================
        // STEP 2: Test Authenticated API Endpoints
        // ========================================
        console.log('========================================');
        console.log('STEP 2: Test Authenticated API Endpoints');
        console.log('========================================');
        
        let apiPassedTests = 0;
        let apiFailedTests = 0;
        const apiLogEntries = []; // Collect API call logs for artifact

        /**
         * Get CSRF token from the page
         * UserFrosting 6 provides CSRF tokens via meta tags in HTML pages
         */
        async function getCsrfToken() {
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
                console.log('   ⚠️  No CSRF token on current page, navigating to dashboard...');
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
                
                console.log('   ⚠️  Could not find CSRF token meta tag');
                return null;
            } catch (error) {
                console.log('   ⚠️  Could not retrieve CSRF token:', error.message);
                return null;
            }
        }

        for (const apiPath of apiPaths) {
            console.log('');
            console.log(`🔍 Testing API: ${apiPath.name}`);
            console.log(`   Method: ${apiPath.method}`);
            console.log(`   Path: ${apiPath.path}`);
            console.log(`   Description: ${apiPath.description}`);
            console.log(`   Expected status: ${apiPath.expected_status}`);

            try {
                const url = `${baseUrl}${apiPath.path}`;
                const method = apiPath.method;
                const payload = apiPath.payload || {};
                
                // Build headers
                let headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                };
                
                // Get CSRF token for state-changing operations
                let csrfToken = null;
                if (['POST', 'PUT', 'DELETE'].includes(method)) {
                    csrfToken = await getCsrfToken();
                    if (csrfToken) {
                        headers['X-CSRF-Token'] = csrfToken;
                    }
                }
                
                // Log payload if present
                if (Object.keys(payload).length > 0) {
                    console.log(`   Payload: ${JSON.stringify(payload)}`);
                }
                
                // Record request timestamp
                const requestTime = new Date().toISOString();
                
                // Make the API request with appropriate method and payload
                let response;
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
                } else {
                    // Fallback to fetch for other methods
                    response = await page.request.fetch(url, {
                        method: method,
                        headers: headers,
                        data: Object.keys(payload).length > 0 ? payload : undefined
                    });
                }
                
                const status = response.status();
                const responseTime = new Date().toISOString();
                console.log(`   Response status: ${status}`);

                // Get response body for logging
                let responseBody = null;
                let responseBodyText = '';
                try {
                    responseBodyText = await response.text();
                    // Try to parse as JSON
                    try {
                        responseBody = JSON.parse(responseBodyText);
                    } catch (e) {
                        // Not JSON, keep as text
                        responseBody = responseBodyText;
                    }
                } catch (e) {
                    responseBody = `[Could not read response body: ${e.message}]`;
                }

                // Create log entry for this API call
                const logEntry = {
                    test_name: apiPath.name,
                    timestamp: requestTime,
                    request: {
                        method: method,
                        url: url,
                        path: apiPath.path,
                        headers: csrfToken ? {
                            ...headers,
                            'X-CSRF-Token': '[REDACTED]'
                        } : headers,
                        payload: payload
                    },
                    response: {
                        status: status,
                        timestamp: responseTime,
                        headers: Object.fromEntries(response.headers().entries()),
                        body: responseBody
                    },
                    expected_status: apiPath.expected_status,
                    result: status === apiPath.expected_status ? 'PASSED' : 'FAILED',
                    description: apiPath.description
                };
                
                apiLogEntries.push(logEntry);

                if (status === apiPath.expected_status) {
                    console.log(`   ✅ PASSED`);
                    apiPassedTests++;
                } else {
                    console.log(`   ❌ FAILED: Expected ${apiPath.expected_status}, got ${status}`);
                    
                    // Display response body for debugging (truncated)
                    if (typeof responseBody === 'string') {
                        console.log(`   Response body (first 500 chars): ${responseBody.substring(0, 500)}`);
                    } else {
                        console.log(`   Response body (first 500 chars): ${JSON.stringify(responseBody).substring(0, 500)}`);
                    }
                    
                    apiFailedTests++;
                }
            } catch (error) {
                console.error(`   ❌ FAILED: ${error.message}`);
                
                // Log error as well
                const errorLogEntry = {
                    test_name: apiPath.name,
                    timestamp: new Date().toISOString(),
                    request: {
                        method: apiPath.method,
                        url: `${baseUrl}${apiPath.path}`,
                        path: apiPath.path,
                        payload: apiPath.payload || {}
                    },
                    error: error.message,
                    result: 'ERROR',
                    description: apiPath.description
                };
                apiLogEntries.push(errorLogEntry);
                
                apiFailedTests++;
            }
        }

        console.log('');
        console.log('API Tests Summary:');
        console.log(`  Total: ${apiPaths.length}`);
        console.log(`  ✅ Passed: ${apiPassedTests}`);
        console.log(`  ❌ Failed: ${apiFailedTests}`);
        console.log('');
        
        // Write API log to file for artifact upload
        if (apiLogEntries.length > 0) {
            try {
                const apiLogFile = '/tmp/api-test-log.json';
                const apiLogData = {
                    test_run: {
                        timestamp: new Date().toISOString(),
                        base_url: baseUrl,
                        username: username,
                        total_tests: apiPaths.length,
                        passed: apiPassedTests,
                        failed: apiFailedTests
                    },
                    api_calls: apiLogEntries
                };
                writeFileSync(apiLogFile, JSON.stringify(apiLogData, null, 2), 'utf8');
                console.log(`📝 API test log written to: ${apiLogFile}`);
                console.log(`   Log contains ${apiLogEntries.length} API call(s)`);
                console.log('');
            } catch (error) {
                console.error(`⚠️  Failed to write API log file: ${error.message}`);
            }
        }

        // ========================================
        // STEP 3: Test Authenticated Frontend Pages
        // ========================================
        console.log('========================================');
        console.log('STEP 3: Test Authenticated Frontend Pages');
        console.log('========================================');
        
        let frontendPassedTests = 0;
        let frontendFailedTests = 0;
        let screenshotCount = 0;

        for (const frontendPath of frontendPaths) {
            console.log('');
            console.log(`🔍 Testing Frontend: ${frontendPath.name}`);
            console.log(`   Path: ${frontendPath.path}`);
            console.log(`   Description: ${frontendPath.description}`);
            console.log(`   Expected status: ${frontendPath.expected_status}`);

            try {
                const response = await page.goto(`${baseUrl}${frontendPath.path}`, { 
                    waitUntil: 'networkidle', 
                    timeout: 30000 
                });
                
                // Wait for page content to load
                await page.waitForTimeout(2000);
                
                const status = response.status();
                const pageUrl = page.url();
                
                console.log(`   Response status: ${status}`);
                console.log(`   Page URL: ${pageUrl}`);

                // Check if we got redirected to login (auth failure)
                if (pageUrl.includes('/account/sign-in')) {
                    console.log(`   ❌ FAILED: Redirected to login page - authentication lost`);
                    frontendFailedTests++;
                    continue;
                }

                if (status === frontendPath.expected_status) {
                    console.log(`   ✅ PASSED`);
                    frontendPassedTests++;
                    
                    // Take screenshot if requested
                    if (frontendPath.screenshot) {
                        const screenshotPath = `/tmp/screenshot_${frontendPath.screenshot_name}.png`;
                        await page.screenshot({ 
                            path: screenshotPath, 
                            fullPage: true 
                        });
                        console.log(`   📸 Screenshot saved: ${screenshotPath}`);
                        screenshotCount++;
                    }
                } else {
                    console.log(`   ❌ FAILED: Expected ${frontendPath.expected_status}, got ${status}`);
                    frontendFailedTests++;
                }
            } catch (error) {
                console.error(`   ❌ FAILED: ${error.message}`);
                frontendFailedTests++;
            }
        }

        console.log('');
        console.log('Frontend Tests Summary:');
        console.log(`  Total: ${frontendPaths.length}`);
        console.log(`  ✅ Passed: ${frontendPassedTests}`);
        console.log(`  ❌ Failed: ${frontendFailedTests}`);
        console.log(`  📸 Screenshots: ${screenshotCount}`);
        console.log('');

        // ========================================
        // Overall Summary
        // ========================================
        console.log('========================================');
        console.log('OVERALL TEST SUMMARY');
        console.log('========================================');
        console.log(`API Tests: ${apiPassedTests}/${apiPaths.length} passed`);
        console.log(`Frontend Tests: ${frontendPassedTests}/${frontendPaths.length} passed`);
        console.log(`Total: ${apiPassedTests + frontendPassedTests}/${apiPaths.length + frontendPaths.length} passed`);
        console.log('========================================');

        const totalFailed = apiFailedTests + frontendFailedTests;
        if (totalFailed > 0) {
            console.log(`❌ ${totalFailed} test(s) failed`);
            process.exit(1);
        } else {
            console.log('✅ All tests passed');
            process.exit(0);
        }

    } catch (error) {
        console.error('');
        console.error('========================================');
        console.error('❌ Test execution error:');
        console.error(error.message);
        console.error('========================================');
        
        // Take a screenshot for debugging if page was successfully created
        if (page) {
            try {
                await page.screenshot({ path: '/tmp/test-error.png', fullPage: true });
                console.log('📸 Error screenshot saved to /tmp/test-error.png');
            } catch (e) {
                console.log('⚠️  Could not capture error screenshot');
            }
        }
        
        process.exit(1);
    } finally {
        await browser.close();
    }
}

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length < 1) {
    console.error('Usage: node test-authenticated-unified.js <config_file> [base_url] [username] [password]');
    console.error('Example: node test-authenticated-unified.js integration-test-paths.json');
    console.error('Example: node test-authenticated-unified.js integration-test-paths.json http://localhost:8080 admin admin123');
    process.exit(1);
}

const [configFile, baseUrl, username, password] = args;

// Run the unified test
testAuthenticatedUnified(configFile, baseUrl, username, password)
    .catch((error) => {
        console.error('Script failed:', error);
        process.exit(1);
    });
