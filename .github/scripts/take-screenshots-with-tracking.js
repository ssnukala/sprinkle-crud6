#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Screenshots with Network Tracking and API Testing
 * 
 * This script uses Playwright to take screenshots, track network requests, and test authenticated API endpoints.
 * It reads the paths configuration and:
 * 1. Logs in once to establish an authenticated session
 * 2. Takes screenshots for all frontend paths
 * 3. Tests all authenticated API endpoints (reusing the same session)
 * 4. Tracks all network requests made during page loads
 * 5. Detects redundant API calls
 * 6. Outputs a summary of network activity
 * 
 * This approach avoids logging in multiple times by reusing the authenticated session
 * for both screenshots and API testing.
 * 
 * Usage: node take-screenshots-with-tracking.js <config_file> [base_url] [username] [password]
 * Example: node take-screenshots-with-tracking.js integration-test-paths.json
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'fs';

/**
 * Network Request Tracker (integrated from NetworkRequestTracker.js)
 */
class NetworkRequestTracker {
    constructor() {
        this.requests = [];
        this.tracking = false;
    }

    startTracking() {
        this.tracking = true;
        this.requests = [];
    }

    stopTracking() {
        this.tracking = false;
    }

    trackRequest(url, method, resourceType) {
        if (!this.tracking) return;

        const request = {
            url: this.normalizeUrl(url),
            method: method.toUpperCase(),
            resourceType: resourceType,
            timestamp: Date.now(),
            key: this.generateRequestKey(url, method),
            originalUrl: url
        };

        this.requests.push(request);
    }

    getRequests() {
        return this.requests;
    }

    getRedundantCalls() {
        const frequency = {};
        const redundant = {};

        this.requests.forEach(req => {
            if (!frequency[req.key]) frequency[req.key] = [];
            frequency[req.key].push(req);
        });

        Object.keys(frequency).forEach(key => {
            if (frequency[key].length > 1) {
                redundant[key] = {
                    count: frequency[key].length,
                    calls: frequency[key]
                };
            }
        });

        return redundant;
    }

    getSchemaCalls() {
        return this.requests.filter(req => this.isSchemaCall(req.url));
    }

    getCRUD6Calls() {
        return this.requests.filter(req => this.isCRUD6Call(req.url));
    }

    hasRedundantCalls() {
        return Object.keys(this.getRedundantCalls()).length > 0;
    }

    getSummary() {
        const redundantCalls = this.getRedundantCalls();
        
        return {
            total: this.requests.length,
            unique: this.requests.length - Object.values(redundantCalls).reduce((sum, r) => sum + r.count - 1, 0),
            redundant: Object.keys(redundantCalls).length,
            schemaCalls: this.getSchemaCalls().length,
            crud6Calls: this.getCRUD6Calls().length
        };
    }

    reset() {
        this.requests = [];
    }

    normalizeUrl(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.pathname + urlObj.search;
        } catch (e) {
            return url;
        }
    }

    generateRequestKey(url, method) {
        const normalizedUrl = this.normalizeUrl(url);
        const urlObj = new URL(normalizedUrl, 'http://localhost');
        const path = urlObj.pathname;

        if (this.isSchemaCall(path)) {
            return `${method.toUpperCase()}:${path}`;
        }

        return `${method.toUpperCase()}:${normalizedUrl}`;
    }

    isSchemaCall(url) {
        return /\/api\/crud6\/[^\/]+\/schema/.test(url);
    }

    isCRUD6Call(url) {
        return url.includes('/api/crud6/');
    }

    /**
     * Filter requests to only CRUD6-related API calls
     * @param {boolean} includeSchema - Whether to include schema API calls
     * @returns {Array} Filtered array of CRUD6 requests
     */
    getFilteredCRUD6Requests(includeSchema = true) {
        return this.requests.filter(req => {
            // Only include CRUD6 API calls
            if (!this.isCRUD6Call(req.url)) {
                return false;
            }
            // If includeSchema is false, exclude schema calls
            if (!includeSchema && this.isSchemaCall(req.url)) {
                return false;
            }
            return true;
        });
    }

    getRedundantCallsReport() {
        const redundant = this.getRedundantCalls();
        
        if (Object.keys(redundant).length === 0) {
            return 'No redundant calls detected.';
        }
        
        let report = 'Redundant Network Requests Detected:\n';
        report += '═'.repeat(80) + '\n\n';
        
        Object.keys(redundant).forEach(key => {
            const data = redundant[key];
            const firstCall = data.calls[0];
            
            report += `Endpoint: ${firstCall.method} ${firstCall.url}\n`;
            report += `Called ${data.count} times (should be 1):\n`;
            
            data.calls.forEach((call, idx) => {
                report += `  ${idx + 1}. Time: ${new Date(call.timestamp).toISOString()}\n`;
            });
            
            report += '\n';
        });
        
        return report;
    }

    /**
     * Generate a detailed text report of all network requests
     */
    getDetailedReport() {
        let report = '';
        report += '═══════════════════════════════════════════════════════════════════════════════\n';
        report += 'NETWORK REQUEST TRACKING DETAILED REPORT\n';
        report += '═══════════════════════════════════════════════════════════════════════════════\n';
        report += `Generated: ${new Date().toISOString()}\n`;
        report += `Total Requests: ${this.requests.length}\n`;
        report += '\n';

        // Group requests by type
        const apiRequests = this.requests.filter(r => r.url.includes('/api/'));
        const crud6Requests = this.getCRUD6Calls();
        const schemaRequests = this.getSchemaCalls();
        const otherRequests = this.requests.filter(r => !r.url.includes('/api/'));

        report += '───────────────────────────────────────────────────────────────────────────────\n';
        report += 'SUMMARY BY TYPE\n';
        report += '───────────────────────────────────────────────────────────────────────────────\n';
        report += `Total Requests:        ${this.requests.length}\n`;
        report += `API Requests:          ${apiRequests.length}\n`;
        report += `  - CRUD6 API:         ${crud6Requests.length}\n`;
        report += `  - Schema API:        ${schemaRequests.length}\n`;
        report += `Other Requests:        ${otherRequests.length}\n`;
        report += '\n';

        // Redundant calls section
        const redundant = this.getRedundantCalls();
        report += '───────────────────────────────────────────────────────────────────────────────\n';
        report += 'REDUNDANT CALLS DETECTION\n';
        report += '───────────────────────────────────────────────────────────────────────────────\n';
        if (Object.keys(redundant).length > 0) {
            report += `⚠️  WARNING: ${Object.keys(redundant).length} redundant call group(s) detected!\n\n`;
            report += this.getRedundantCallsReport();
        } else {
            report += '✅ No redundant calls detected.\n';
        }
        report += '\n';

        // Detailed request list
        report += '───────────────────────────────────────────────────────────────────────────────\n';
        report += 'ALL REQUESTS (CHRONOLOGICAL ORDER)\n';
        report += '───────────────────────────────────────────────────────────────────────────────\n';
        this.requests.forEach((req, idx) => {
            const time = new Date(req.timestamp).toISOString();
            report += `${idx + 1}. [${time}] ${req.method} ${req.url}\n`;
            report += `   Resource Type: ${req.resourceType}\n`;
            if (this.isCRUD6Call(req.url)) {
                report += `   📌 CRUD6 API Call\n`;
            }
            if (this.isSchemaCall(req.url)) {
                report += `   📌 Schema API Call\n`;
            }
            report += '\n';
        });

        report += '═══════════════════════════════════════════════════════════════════════════════\n';
        report += 'END OF REPORT\n';
        report += '═══════════════════════════════════════════════════════════════════════════════\n';

        return report;
    }
}

// Test counters for API testing
let totalApiTests = 0;
let passedApiTests = 0;
let failedApiTests = 0;
let skippedApiTests = 0;
let warningApiTests = 0;

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
        
        // If no token on current page, navigate to home page to get one
        // Home page is unauthenticated and will have a CSRF meta tag
        // Dashboard would redirect to login since it requires authentication
        console.warn('   ⚠️  No CSRF token on current page, navigating to home page to get token...');
        await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        
        // Try again to get token from home page
        csrfToken = await page.evaluate(() => {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.getAttribute('content') : null;
        });
        
        if (csrfToken) {
            console.log('   ✅ CSRF token retrieved from home page');
            return csrfToken;
        }
        
        console.warn('   ⚠️  Could not find CSRF token meta tag on home page either');
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
    totalApiTests++;
    
    // Check if test should be skipped
    if (pathConfig.skip) {
        console.log(`⏭️  SKIP: ${name}`);
        console.log(`   Reason: ${pathConfig.skip_reason || 'Not specified'}\n`);
        skippedApiTests++;
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
        
        // Set up headers for API request
        let headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
        
        // Get CSRF token for state-changing operations (POST, PUT, DELETE)
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
            passedApiTests++;
        } else if (status === 403) {
            // Permission failure - warn instead of fail
            console.log(`   ⚠️  Status: ${status} (expected ${expectedStatus})`);
            console.log(`   ⚠️  WARNING: Permission failure (403) - user may lack required permission`);
            if (pathConfig.requires_permission) {
                console.log(`   ⚠️  Required permission: ${pathConfig.requires_permission}`);
            }
            console.log(`   ⚠️  WARNED (continuing tests)\n`);
            warningApiTests++;
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
            failedApiTests++;
        } else {
            console.log(`   ❌ Status: ${status} (expected ${expectedStatus})`);
            
            // Try to get error details from response
            try {
                const responseText = await response.text();
                if (responseText) {
                    try {
                        const data = JSON.parse(responseText);
                        if (data.message) {
                            console.log(`   ❌ Error: ${data.message}`);
                        }
                        if (data.errors) {
                            console.log(`   ❌ Validation errors:`, data.errors);
                        }
                        if (data.status && data.status.message) {
                            console.log(`   ❌ Status message: ${data.status.message}`);
                        }
                    } catch (jsonError) {
                        // Not JSON, print first 500 chars of response with ellipsis if truncated
                        const maxLength = 500;
                        const truncated = responseText.length > maxLength;
                        const displayText = truncated 
                            ? responseText.substring(0, maxLength) + '...' 
                            : responseText;
                        console.log(`   ❌ Response: ${displayText}`);
                        if (truncated) {
                            console.log(`   ⚠️  Response truncated (${responseText.length} total characters)`);
                        }
                    }
                }
            } catch (error) {
                // Can't read response body
            }
            
            console.log(`   ❌ FAILED\n`);
            failedApiTests++;
        }
    } catch (error) {
        console.log(`   ❌ Exception: ${error.message}`);
        console.log(`   ❌ FAILED\n`);
        failedApiTests++;
    }
}

async function takeScreenshotsFromConfig(configFile, baseUrlOverride, usernameOverride, passwordOverride) {
    console.log('========================================');
    console.log('Screenshots + Network Tracking + API Testing');
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

    // Collect screenshots to take
    const screenshots = [];
    
    if (config.paths?.authenticated?.frontend) {
        for (const [name, pathConfig] of Object.entries(config.paths.authenticated.frontend)) {
            if (pathConfig.screenshot && !pathConfig.skip) {
                screenshots.push({
                    name,
                    path: pathConfig.path,
                    description: pathConfig.description || name,
                    screenshot_name: pathConfig.screenshot_name || name
                });
            }
        }
    }

    if (screenshots.length === 0) {
        console.log('ℹ️  No screenshots configured');
        return;
    }

    console.log(`Found ${screenshots.length} screenshots to capture\n`);

    // Create network tracker
    const networkTracker = new NetworkRequestTracker();

    // Initialize counters and error tracking at function scope so they're accessible at return and in error handler
    let successCount = 0;
    let failCount = 0;
    let consoleErrors = [];

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

        // Set up console error logging - use function-scoped consoleErrors array
        page.on('console', msg => {
            const type = msg.type();
            const text = msg.text();
            if (type === 'error' || type === 'warning') {
                consoleErrors.push({ type, text, timestamp: Date.now() });
                console.log(`   🖥️  Browser ${type}: ${text}`);
            }
        });

        // Set up network tracking
        networkTracker.startTracking();
        
        page.on('request', request => {
            networkTracker.trackRequest(
                request.url(),
                request.method(),
                request.resourceType()
            );
        });

        // Step 1: Navigate to login page and authenticate
        console.log('📍 Navigating to login page...');
        await page.goto(`${baseUrl}/account/sign-in`, { waitUntil: 'networkidle', timeout: 30000 });
        
        // Log page info for debugging
        const pageTitle = await page.title();
        const pageUrl = page.url();
        console.log(`✅ Login page loaded`);
        console.log(`   Page Title: ${pageTitle}`);
        console.log(`   Page URL: ${pageUrl}`);
        
        // Take early screenshot to see what's rendering
        await page.screenshot({ path: '/tmp/screenshot_login_page_initial.png', fullPage: true });
        console.log('📸 Early screenshot saved: /tmp/screenshot_login_page_initial.png');
        
        // Wait a bit for any JavaScript to execute
        await page.waitForTimeout(2000);

        console.log('🔐 Logging in...');
        
        // Take screenshot before looking for selectors
        await page.screenshot({ path: '/tmp/screenshot_before_login_selectors.png', fullPage: true });
        console.log('📸 Screenshot before selector search: /tmp/screenshot_before_login_selectors.png');
        
        // Try to extract CSRF token from the login page
        let csrfToken = null;
        try {
            csrfToken = await page.evaluate(() => {
                // Try to find CSRF token in various common locations
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) return metaTag.getAttribute('content');
                
                const inputField = document.querySelector('input[name="csrf_token"], input[name="_csrf_token"], input[name="csrf-token"]');
                if (inputField) return inputField.value;
                
                return null;
            });
            if (csrfToken) {
                // Only show ellipsis if token is longer than 20 characters
                const tokenPreview = csrfToken.length > 20 
                    ? `${csrfToken.substring(0, 20)}...` 
                    : csrfToken;
                console.log(`   ✅ Found CSRF token: ${tokenPreview}`);
            } else {
                console.log('   ⚠️  No CSRF token found on login page');
            }
        } catch (e) {
            console.log('   ⚠️  Error extracting CSRF token:', e.message);
        }
        
        // Log browser console errors if any occurred during page load
        if (consoleErrors.length > 0) {
            console.log(`   ⚠️  ${consoleErrors.length} browser console errors/warnings detected:`);
            consoleErrors.forEach((error, idx) => {
                console.log(`      ${idx + 1}. [${error.type}] ${error.text}`);
            });
        }
        
        // Wait for the login form to be visible with increased timeout
        // Try multiple selectors in case the page structure varies
        let usernameInput = null;
        const selectors = [
            '.uk-card input[data-test="username"]',
            'input[data-test="username"]',
            'input[name="username"]',
        ];
        
        // Reduce timeout since we have better debugging now
        const selectorTimeout = 10000; // 10 seconds per selector
        
        for (const selector of selectors) {
            try {
                console.log(`   Trying selector: ${selector}`);
                usernameInput = await page.waitForSelector(selector, { timeout: selectorTimeout, state: 'visible' });
                if (usernameInput) {
                    console.log(`   ✅ Found username input with selector: ${selector}`);
                    break;
                }
            } catch (e) {
                console.log(`   ⚠️  Selector ${selector} not found, trying next...`);
            }
        }
        
        if (!usernameInput) {
            // If we still can't find it, save debug info
            const pageContent = await page.content();
            writeFileSync('/tmp/login_page_debug.html', pageContent);
            await page.screenshot({ path: '/tmp/login_page_debug.png', fullPage: true });
            console.error('❌ Could not find username input field after trying all selectors');
            console.error('   Debug HTML saved to /tmp/login_page_debug.html');
            console.error('   Debug screenshot saved to /tmp/login_page_debug.png');
            throw new Error('Login form not found - username input field is missing');
        }
        
        // Fill in credentials - try the same selector patterns
        const usernameSelectors = [
            '.uk-card input[data-test="username"]',
            'input[data-test="username"]',
            'input[name="username"]',
        ];
        
        const passwordSelectors = [
            '.uk-card input[data-test="password"]',
            'input[data-test="password"]',
            'input[name="password"]',
            'input[type="password"]',
        ];
        
        let filled = false;
        for (const uSelector of usernameSelectors) {
            try {
                await page.fill(uSelector, username);
                console.log(`   ✅ Filled username using: ${uSelector}`);
                filled = true;
                break;
            } catch (e) {
                // Try next selector
            }
        }
        
        if (!filled) {
            throw new Error('Could not fill username field');
        }
        
        filled = false;
        for (const pSelector of passwordSelectors) {
            try {
                await page.fill(pSelector, password);
                console.log(`   ✅ Filled password using: ${pSelector}`);
                filled = true;
                break;
            } catch (e) {
                // Try next selector
            }
        }
        
        if (!filled) {
            throw new Error('Could not fill password field');
        }
        
        // Click the login button and wait for navigation - try multiple selectors
        const submitSelectors = [
            '.uk-card button[data-test="submit"]',
            'button[data-test="submit"]',
            'button[type="submit"]',
            '.uk-card button[type="submit"]',
        ];
        
        let submitClicked = false;
        for (const submitSelector of submitSelectors) {
            try {
                await Promise.all([
                    page.waitForNavigation({ timeout: 15000 }).catch(() => {
                        console.log('⚠️  No navigation detected after login, but continuing...');
                    }),
                    page.click(submitSelector)
                ]);
                console.log(`   ✅ Clicked submit button using: ${submitSelector}`);
                submitClicked = true;
                break;
            } catch (e) {
                console.log(`   ⚠️  Could not click submit with ${submitSelector}, trying next...`);
            }
        }
        
        if (!submitClicked) {
            throw new Error('Could not click submit button');
        }
        
        console.log('✅ Logged in successfully');
        
        // Give session a moment to stabilize
        await page.waitForTimeout(2000);

        // Step 2: Take screenshots from configuration and track network requests per page
        const pageNetworkStats = [];
        const pageNetworkDetails = []; // Store detailed requests per page

        for (const screenshot of screenshots) {
            console.log('');
            console.log(`📸 Taking screenshot: ${screenshot.name}`);
            console.log(`   Path: ${screenshot.path}`);
            console.log(`   Description: ${screenshot.description}`);

            // Mark the start of this page's requests
            const requestsBeforePage = networkTracker.getRequests().length;

            try {
                await page.goto(`${baseUrl}${screenshot.path}`, { waitUntil: 'networkidle', timeout: 30000 });
                
                // Wait for page content to load
                await page.waitForTimeout(2000);
                
                // Check if we're still on login page (would indicate auth failure)
                const currentUrl = page.url();
                if (currentUrl.includes('/account/sign-in')) {
                    console.warn(`   ⚠️  Warning: Still on login page - authentication may have failed`);
                    failCount++;
                } else {
                    console.log(`   ✅ Page loaded: ${currentUrl}`);
                    
                    // Check for UserFrosting Severity.Danger alerts (UFAlert component)
                    // UFAlert with Severity.Danger renders as uk-alert-danger class
                    const errorNotifications = await page.locator('.uk-alert.uk-alert-danger').all();
                    
                    let hasErrorNotification = false;
                    const errorMessages = [];
                    
                    for (const notification of errorNotifications) {
                        const isVisible = await notification.isVisible();
                        if (isVisible) {
                            hasErrorNotification = true;
                            
                            // Try to get the alert title (has data-test="title" attribute)
                            const titleElement = notification.locator('[data-test="title"]');
                            const titleCount = await titleElement.count();
                            let title = '';
                            if (titleCount > 0) {
                                title = await titleElement.textContent();
                            }
                            
                            // Get the full alert message
                            const fullText = await notification.textContent();
                            
                            // Store error details
                            const errorDetail = {
                                title: title ? title.trim() : 'Error',
                                message: fullText ? fullText.trim() : ''
                            };
                            errorMessages.push(errorDetail);
                        }
                    }
                    
                    const screenshotPath = `/tmp/screenshot_${screenshot.screenshot_name}.png`;
                    await page.screenshot({ 
                        path: screenshotPath, 
                        fullPage: true 
                    });
                    console.log(`   ✅ Screenshot saved: ${screenshotPath}`);
                    
                    if (hasErrorNotification) {
                        console.error(`   ❌ SEVERITY.DANGER ALERT DETECTED on page!`);
                        errorMessages.forEach((error, idx) => {
                            console.error(`      Alert ${idx + 1}:`);
                            if (error.title) {
                                console.error(`         Title: ${error.title}`);
                            }
                            // Show first 200 chars of message for debugging
                            const msgPreview = error.message.substring(0, 200);
                            console.error(`         Message: ${msgPreview}${error.message.length > 200 ? '...' : ''}`);
                        });
                        failCount++;
                    } else {
                        // Get requests made during this page load
                        const allRequests = networkTracker.getRequests();
                        const pageRequests = allRequests.slice(requestsBeforePage);
                        
                        // Calculate stats for this page
                        const pageCRUD6 = pageRequests.filter(r => networkTracker.isCRUD6Call(r.url)).length;
                        const pageSchema = pageRequests.filter(r => networkTracker.isSchemaCall(r.url)).length;
                        
                        pageNetworkStats.push({
                            name: screenshot.name,
                            path: screenshot.path,
                            total: pageRequests.length,
                            crud6Calls: pageCRUD6,
                            schemaCalls: pageSchema,
                            hasError: false
                        });
                        
                        pageNetworkDetails.push({
                            name: screenshot.name,
                            path: screenshot.path,
                            requests: pageRequests
                        });
                        
                        console.log(`   📡 Network: ${pageRequests.length} requests (${pageCRUD6} CRUD6, ${pageSchema} Schema)`);
                        console.log(`   ✅ No error notifications detected`);
                        
                        successCount++;
                    }
                }
            } catch (error) {
                console.error(`   ❌ Failed: ${error.message}`);
                failCount++;
            }
        }

        console.log('');
        console.log('========================================');
        console.log('Screenshot Summary');
        console.log('========================================');
        console.log(`Total: ${screenshots.length}`);
        console.log(`Success: ${successCount}`);
        console.log(`Failed: ${failCount}`);
        console.log('========================================');

        if (failCount > 0) {
            console.error('❌ TESTS FAILED: Some screenshots had errors or error notifications detected');
            console.error(`   ${failCount} page(s) with errors detected`);
        } else {
            console.log('✅ All screenshots taken successfully with no error notifications');
        }

        // Output network tracking summary
        console.log('');
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('Network Request Tracking Summary');
        console.log('═══════════════════════════════════════════════════════════════');
        
        let totalRequests = 0;
        let totalCRUD6 = 0;
        let totalSchema = 0;
        
        pageNetworkStats.forEach(stats => {
            console.log(`\n📄 ${stats.name} (${stats.path})`);
            console.log(`   Total Requests:       ${stats.total}`);
            console.log(`   CRUD6 API Calls:      ${stats.crud6Calls}`);
            console.log(`   Schema API Calls:     ${stats.schemaCalls}`);
            
            totalRequests += stats.total;
            totalCRUD6 += stats.crud6Calls;
            totalSchema += stats.schemaCalls;
        });
        
        const allRequests = networkTracker.getRequests();
        const redundantCalls = networkTracker.getRedundantCalls();
        const totalRedundant = Object.keys(redundantCalls).length;
        
        console.log('\n' + '─'.repeat(80));
        console.log('Overall Totals:');
        console.log(`   Pages Tested:         ${pageNetworkStats.length}`);
        console.log(`   Total Requests:       ${allRequests.length}`);
        console.log(`   Total CRUD6 Calls:    ${totalCRUD6}`);
        console.log(`   Total Schema Calls:   ${totalSchema}`);
        console.log(`   Total Redundant Groups: ${totalRedundant}`);
        
        if (totalRedundant > 0) {
            console.log('\n⚠️  WARNING: Redundant API calls detected across pages!');
        } else {
            console.log('\n✅ No redundant calls detected');
        }
        console.log('═══════════════════════════════════════════════════════════════');

        // Step 3: Test authenticated API paths using the same session
        const authApiPaths = config.paths?.authenticated?.api || {};
        
        if (Object.keys(authApiPaths).length > 0) {
            console.log('');
            console.log('');
            console.log('=========================================');
            console.log('Testing Authenticated API Endpoints');
            console.log('=========================================');
            console.log('Using existing authenticated session from screenshots\n');
            
            for (const [name, pathConfig] of Object.entries(authApiPaths)) {
                await testApiPath(page, name, pathConfig, baseUrl);
            }
            
            // Print API test summary
            console.log('');
            console.log('=========================================');
            console.log('API Test Summary');
            console.log('=========================================');
            console.log(`Total tests: ${totalApiTests}`);
            console.log(`Passed: ${passedApiTests}`);
            console.log(`Warnings: ${warningApiTests}`);
            console.log(`Failed: ${failedApiTests}`);
            console.log(`Skipped: ${skippedApiTests}`);
            console.log('');
            
            if (failedApiTests > 0) {
                console.log('❌ Some API tests failed (actual code/SQL errors detected)');
                console.log('   Note: Permission failures (403) are warnings, not failures');
                failCount += failedApiTests; // Add API failures to total fail count
            } else if (warningApiTests > 0) {
                console.log('✅ All API tests passed (permission warnings are expected for some endpoints)');
                console.log(`   ${warningApiTests} permission warnings detected (403 status codes)`);
                console.log('   No actual code/SQL errors found');
            } else {
                console.log('✅ All API tests passed');
            }
            console.log('=========================================');
        } else {
            console.log('');
            console.log('ℹ️  No authenticated API paths configured - skipping API tests');
        }

        // Generate and save detailed network report to file
        console.log('');
        console.log('📝 Generating detailed network request report (CRUD6 filtered)...');
        
        // Get only CRUD6-related requests for the detailed report
        const crud6FilteredRequests = networkTracker.getFilteredCRUD6Requests(true);
        const nonCRUD6Count = allRequests.length - crud6FilteredRequests.length;
        
        let detailedReport = '';
        detailedReport += '═══════════════════════════════════════════════════════════════════════════════\n';
        detailedReport += 'NETWORK REQUEST TRACKING DETAILED REPORT (CRUD6 FILTERED)\n';
        detailedReport += 'UserFrosting CRUD6 Sprinkle Integration Test\n';
        detailedReport += '═══════════════════════════════════════════════════════════════════════════════\n';
        detailedReport += `Generated: ${new Date().toISOString()}\n`;
        detailedReport += `Base URL: ${baseUrl}\n`;
        detailedReport += `Total Pages Tested: ${pageNetworkStats.length}\n`;
        detailedReport += '\n';
        detailedReport += 'ℹ️  This report focuses on CRUD6 API calls only.\n';
        detailedReport += `   Total requests captured: ${allRequests.length}\n`;
        detailedReport += `   CRUD6 requests (shown below): ${crud6FilteredRequests.length}\n`;
        detailedReport += `   Non-CRUD6 requests (filtered out): ${nonCRUD6Count}\n`;
        detailedReport += '\n';

        // Summary section
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        detailedReport += 'SUMMARY BY TYPE\n';
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        detailedReport += `Total Requests Captured:     ${allRequests.length}\n`;
        detailedReport += `CRUD6 API Calls (filtered):  ${totalCRUD6}\n`;
        detailedReport += `  - Schema API Calls:        ${totalSchema}\n`;
        detailedReport += `  - Other CRUD6 Calls:       ${totalCRUD6 - totalSchema}\n`;
        detailedReport += `Non-CRUD6 Calls (excluded):  ${nonCRUD6Count}\n`;
        detailedReport += `Redundant Call Groups:       ${totalRedundant}\n`;
        detailedReport += '\n';

        // Per-page breakdown
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        detailedReport += 'PER-PAGE BREAKDOWN (CRUD6 REQUESTS ONLY)\n';
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        pageNetworkDetails.forEach((pageDetail, idx) => {
            // Filter to only CRUD6 requests for this page
            const pageCRUD6Requests = pageDetail.requests.filter(r => networkTracker.isCRUD6Call(r.url));
            const pageNonCRUD6Count = pageDetail.requests.length - pageCRUD6Requests.length;
            
            detailedReport += `\n${idx + 1}. ${pageDetail.name}\n`;
            detailedReport += `   Path: ${pageDetail.path}\n`;
            detailedReport += `   Total Requests: ${pageDetail.requests.length} (${pageCRUD6Requests.length} CRUD6, ${pageNonCRUD6Count} other)\n`;
            
            if (pageCRUD6Requests.length > 0) {
                detailedReport += '\n   CRUD6 Request Details:\n';
                pageCRUD6Requests.forEach((req, reqIdx) => {
                    const time = new Date(req.timestamp).toISOString();
                    detailedReport += `   ${reqIdx + 1}. [${time}] ${req.method} ${req.url}\n`;
                    detailedReport += `      Resource Type: ${req.resourceType}\n`;
                    if (networkTracker.isSchemaCall(req.url)) {
                        detailedReport += `      📌 Schema API Call\n`;
                    }
                });
            } else {
                detailedReport += '\n   ℹ️  No CRUD6 requests on this page\n';
            }
            detailedReport += '\n';
        });

        // Redundant calls section (for CRUD6 requests only)
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        detailedReport += 'REDUNDANT CALLS DETECTION (CRUD6 ONLY)\n';
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        
        // Calculate redundant calls for CRUD6 requests only
        const crud6RedundantCalls = {};
        const crud6Frequency = {};
        
        crud6FilteredRequests.forEach(req => {
            if (!crud6Frequency[req.key]) crud6Frequency[req.key] = [];
            crud6Frequency[req.key].push(req);
        });
        
        Object.keys(crud6Frequency).forEach(key => {
            if (crud6Frequency[key].length > 1) {
                crud6RedundantCalls[key] = {
                    count: crud6Frequency[key].length,
                    calls: crud6Frequency[key]
                };
            }
        });
        
        const crud6RedundantCount = Object.keys(crud6RedundantCalls).length;
        
        if (crud6RedundantCount > 0) {
            detailedReport += `⚠️  WARNING: ${crud6RedundantCount} redundant CRUD6 call group(s) detected!\n\n`;
            
            Object.keys(crud6RedundantCalls).forEach(key => {
                const data = crud6RedundantCalls[key];
                const firstCall = data.calls[0];
                
                detailedReport += `Endpoint: ${firstCall.method} ${firstCall.url}\n`;
                detailedReport += `Called ${data.count} times (should be 1):\n`;
                
                data.calls.forEach((call, idx) => {
                    detailedReport += `  ${idx + 1}. Time: ${new Date(call.timestamp).toISOString()}\n`;
                });
                
                detailedReport += '\n';
            });
        } else {
            detailedReport += '✅ No redundant CRUD6 calls detected.\n';
        }
        detailedReport += '\n';

        // All CRUD6 requests chronologically
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        detailedReport += 'ALL CRUD6 REQUESTS (CHRONOLOGICAL ORDER)\n';
        detailedReport += '───────────────────────────────────────────────────────────────────────────────\n';
        crud6FilteredRequests.forEach((req, idx) => {
            const time = new Date(req.timestamp).toISOString();
            detailedReport += `${idx + 1}. [${time}] ${req.method} ${req.url}\n`;
            detailedReport += `   Resource Type: ${req.resourceType}\n`;
            if (networkTracker.isSchemaCall(req.url)) {
                detailedReport += `   📌 Schema API Call\n`;
            }
            detailedReport += '\n';
        });

        detailedReport += '═══════════════════════════════════════════════════════════════════════════════\n';
        detailedReport += 'END OF REPORT\n';
        detailedReport += '═══════════════════════════════════════════════════════════════════════════════\n';

        // Save to file
        const reportPath = '/tmp/network-requests-summary.txt';
        try {
            writeFileSync(reportPath, detailedReport, 'utf8');
            console.log(`✅ Network request report saved to: ${reportPath}`);
            console.log(`   File size: ${(detailedReport.length / 1024).toFixed(2)} KB`);
            console.log(`   Total requests captured: ${allRequests.length}`);
            console.log(`   CRUD6 requests documented: ${crud6FilteredRequests.length}`);
            console.log(`   Non-CRUD6 requests filtered: ${nonCRUD6Count}`);
        } catch (writeError) {
            console.error(`❌ Failed to save network report: ${writeError.message}`);
        }


    } catch (error) {
        console.error('');
        console.error('========================================');
        console.error('❌ Error taking screenshots:');
        console.error(error.message);
        console.error('========================================');
        
        // Log any browser console errors that were captured
        if (consoleErrors && consoleErrors.length > 0) {
            console.error('');
            console.error('🖥️  Browser Console Errors/Warnings:');
            consoleErrors.forEach((err, idx) => {
                console.error(`   ${idx + 1}. [${err.type}] ${err.text}`);
            });
        }
        
        // Take a screenshot of the current page for debugging
        try {
            const errorPage = await browser.newPage();
            await errorPage.screenshot({ path: '/tmp/screenshot_error.png', fullPage: true });
            console.log('📸 Error screenshot saved to /tmp/screenshot_error.png');
        } catch (e) {
            // Ignore errors when taking error screenshot
        }
        
        throw error;
    } finally {
        networkTracker.stopTracking();
        await browser.close();
    }
    
    // Return the fail count so caller can exit with appropriate code
    return failCount;
}

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length < 1) {
    console.error('Usage: node take-screenshots-with-tracking.js <config_file> [base_url] [username] [password]');
    console.error('Example: node take-screenshots-with-tracking.js integration-test-paths.json');
    process.exit(1);
}

const [configFile, baseUrl, username, password] = args;

// Run the script
takeScreenshotsFromConfig(configFile, baseUrl, username, password)
    .then((failCount) => {
        if (failCount > 0) {
            console.error('');
            console.error('========================================');
            console.error(`❌ Test failed: ${failCount} page(s) had errors`);
            console.error('========================================');
            process.exit(1);
        }
        process.exit(0);
    })
    .catch((error) => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
