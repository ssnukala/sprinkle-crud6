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
import { readFileSync } from 'fs';

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
                    expected_status: pathConfig.expected_status || 200
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

    try {
        const context = await browser.newContext({
            viewport: { width: 1280, height: 720 },
            ignoreHTTPSErrors: true
        });

        const page = await context.newPage();

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

        for (const apiPath of apiPaths) {
            console.log('');
            console.log(`🔍 Testing API: ${apiPath.name}`);
            console.log(`   Method: ${apiPath.method}`);
            console.log(`   Path: ${apiPath.path}`);
            console.log(`   Description: ${apiPath.description}`);
            console.log(`   Expected status: ${apiPath.expected_status}`);

            try {
                const response = await page.request.fetch(`${baseUrl}${apiPath.path}`, {
                    method: apiPath.method
                });
                
                const status = response.status();
                console.log(`   Response status: ${status}`);

                if (status === apiPath.expected_status) {
                    console.log(`   ✅ PASSED`);
                    apiPassedTests++;
                } else {
                    console.log(`   ❌ FAILED: Expected ${apiPath.expected_status}, got ${status}`);
                    
                    // Try to get response body for debugging
                    try {
                        const body = await response.text();
                        console.log(`   Response body (first 200 chars): ${body.substring(0, 200)}`);
                    } catch (e) {
                        console.log(`   Could not read response body`);
                    }
                    
                    apiFailedTests++;
                }
            } catch (error) {
                console.error(`   ❌ FAILED: ${error.message}`);
                apiFailedTests++;
            }
        }

        console.log('');
        console.log('API Tests Summary:');
        console.log(`  Total: ${apiPaths.length}`);
        console.log(`  ✅ Passed: ${apiPassedTests}`);
        console.log(`  ❌ Failed: ${apiFailedTests}`);
        console.log('');

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
        
        // Take a screenshot of the current page for debugging
        try {
            const page = await browser.newPage();
            await page.screenshot({ path: '/tmp/test-error.png', fullPage: true });
            console.log('📸 Error screenshot saved to /tmp/test-error.png');
        } catch (e) {
            // Ignore errors when taking error screenshot
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
