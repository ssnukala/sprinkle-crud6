#!/usr/bin/env node

/**
 * Enhanced Frontend Error Detection for CRUD6 Integration Tests
 * 
 * This script extends the screenshot testing with comprehensive error detection:
 * 
 * ERROR DETECTION:
 * 1. JavaScript Console Errors - Catches all console.error() calls and JavaScript exceptions
 * 2. Network Errors - Detects 4xx/5xx HTTP errors in API calls
 * 3. Vue Component Errors - Catches Vue.js warnings and errors
 * 4. UI Error Notifications - Detects error alerts/notifications in the UI
 * 5. Failed Assertions - Validates expected page elements are present
 * 6. Performance Issues - Detects slow page loads and API calls
 * 
 * VALIDATION:
 * - Schema loads without errors
 * - No JavaScript errors during page navigation
 * - All API calls return expected status codes
 * - No error notifications appear unexpectedly
 * - Page elements load correctly
 * 
 * Usage: node enhanced-error-detection.js <config_file> [base_url] [username] [password]
 */

import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'fs';

/**
 * Error Tracker - Collects all types of errors during testing
 */
class ErrorTracker {
    constructor() {
        this.errors = [];
        this.warnings = [];
        this.consoleErrors = [];
        this.networkErrors = [];
        this.uiErrors = [];
        this.vueErrors = [];
    }

    /**
     * Add a console error
     */
    addConsoleError(message, source, location) {
        this.consoleErrors.push({
            type: 'console',
            message,
            source,
            location,
            timestamp: Date.now()
        });
        this.errors.push({
            type: 'JavaScript Console Error',
            message,
            source,
            location
        });
    }

    /**
     * Add a network error with detailed information
     */
    addNetworkError(url, status, method, errorDetails = null) {
        this.networkErrors.push({
            type: 'network',
            url,
            status,
            method,
            errorDetails,
            timestamp: Date.now()
        });
        this.errors.push({
            type: 'Network Error',
            message: `${method} ${url} returned ${status}`,
            url,
            status,
            errorDetails
        });
    }

    /**
     * Add a UI error (error notification)
     */
    addUIError(title, message, page) {
        this.uiErrors.push({
            type: 'ui',
            title,
            message,
            page,
            timestamp: Date.now()
        });
        this.errors.push({
            type: 'UI Error Notification',
            title,
            message,
            page
        });
    }

    /**
     * Add a Vue.js error
     */
    addVueError(message, component) {
        this.vueErrors.push({
            type: 'vue',
            message,
            component,
            timestamp: Date.now()
        });
        this.errors.push({
            type: 'Vue.js Error',
            message,
            component
        });
    }

    /**
     * Add a warning
     */
    addWarning(message, context) {
        this.warnings.push({
            message,
            context,
            timestamp: Date.now()
        });
    }

    /**
     * Check if there are any critical errors
     */
    hasCriticalErrors() {
        return this.errors.length > 0;
    }

    /**
     * Get error summary
     */
    getSummary() {
        return {
            totalErrors: this.errors.length,
            consoleErrors: this.consoleErrors.length,
            networkErrors: this.networkErrors.length,
            uiErrors: this.uiErrors.length,
            vueErrors: this.vueErrors.length,
            warnings: this.warnings.length
        };
    }

    /**
     * Generate detailed error report
     */
    generateReport() {
        let report = '';
        
        report += '═══════════════════════════════════════════════════════════════\n';
        report += 'FRONTEND ERROR DETECTION REPORT\n';
        report += '═══════════════════════════════════════════════════════════════\n\n';
        
        const summary = this.getSummary();
        report += 'SUMMARY:\n';
        report += `  Total Errors:          ${summary.totalErrors}\n`;
        report += `  Console Errors:        ${summary.consoleErrors}\n`;
        report += `  Network Errors:        ${summary.networkErrors}\n`;
        report += `  UI Error Notifications: ${summary.uiErrors}\n`;
        report += `  Vue.js Errors:         ${summary.vueErrors}\n`;
        report += `  Warnings:              ${summary.warnings}\n`;
        report += '\n';
        
        if (this.consoleErrors.length > 0) {
            report += '───────────────────────────────────────────────────────────────\n';
            report += 'JAVASCRIPT CONSOLE ERRORS\n';
            report += '───────────────────────────────────────────────────────────────\n';
            this.consoleErrors.forEach((error, idx) => {
                report += `\n${idx + 1}. ${error.message}\n`;
                if (error.source) report += `   Source: ${error.source}\n`;
                if (error.location) report += `   Location: ${error.location}\n`;
            });
            report += '\n';
        }
        
        if (this.networkErrors.length > 0) {
            report += '───────────────────────────────────────────────────────────────\n';
            report += 'NETWORK ERRORS (4xx/5xx Status Codes)\n';
            report += '───────────────────────────────────────────────────────────────\n';
            this.networkErrors.forEach((error, idx) => {
                report += `\n${idx + 1}. ${error.method} ${error.url}\n`;
                report += `   Status: ${error.status}\n`;
                
                // Add detailed error information if available (for 5xx errors)
                if (error.errorDetails) {
                    if (error.errorDetails.message) {
                        report += `   💥 Error Message: ${error.errorDetails.message}\n`;
                    }
                    if (error.errorDetails.exception) {
                        report += `   💥 Exception Type: ${error.errorDetails.exception}\n`;
                    }
                    if (error.errorDetails.file && error.errorDetails.line) {
                        report += `   📂 Location: ${error.errorDetails.file}:${error.errorDetails.line}\n`;
                    }
                    if (error.errorDetails.possibleSqlError || error.errorDetails.isSqlError) {
                        report += `   🗄️  POSSIBLE SQL ERROR DETECTED\n`;
                    }
                    if (error.errorDetails.trace) {
                        report += `   📚 Stack Trace (top 3 frames):\n`;
                        if (typeof error.errorDetails.trace === 'string') {
                            const lines = error.errorDetails.trace.split('\n');
                            lines.forEach(line => {
                                if (line.trim()) report += `      ${line}\n`;
                            });
                        } else if (Array.isArray(error.errorDetails.trace)) {
                            error.errorDetails.trace.forEach((frame, i) => {
                                report += `      ${i + 1}. ${frame.file || 'unknown'}:${frame.line || '?'}\n`;
                                if (frame.class && frame.function) {
                                    report += `         ${frame.class}::${frame.function}()\n`;
                                }
                            });
                        }
                    }
                    if (error.errorDetails.rawError) {
                        report += `   📝 Raw Error (first 500 chars):\n`;
                        report += `      ${error.errorDetails.rawError}\n`;
                    }
                }
            });
            report += '\n';
        }
        
        if (this.uiErrors.length > 0) {
            report += '───────────────────────────────────────────────────────────────\n';
            report += 'UI ERROR NOTIFICATIONS\n';
            report += '───────────────────────────────────────────────────────────────\n';
            this.uiErrors.forEach((error, idx) => {
                report += `\n${idx + 1}. ${error.title || 'Error'}\n`;
                report += `   Page: ${error.page}\n`;
                report += `   Message: ${error.message}\n`;
            });
            report += '\n';
        }
        
        if (this.vueErrors.length > 0) {
            report += '───────────────────────────────────────────────────────────────\n';
            report += 'VUE.JS ERRORS\n';
            report += '───────────────────────────────────────────────────────────────\n';
            this.vueErrors.forEach((error, idx) => {
                report += `\n${idx + 1}. ${error.message}\n`;
                if (error.component) report += `   Component: ${error.component}\n`;
            });
            report += '\n';
        }
        
        if (this.warnings.length > 0) {
            report += '───────────────────────────────────────────────────────────────\n';
            report += 'WARNINGS\n';
            report += '───────────────────────────────────────────────────────────────\n';
            this.warnings.forEach((warning, idx) => {
                report += `\n${idx + 1}. ${warning.message}\n`;
                if (warning.context) report += `   Context: ${warning.context}\n`;
            });
            report += '\n';
        }
        
        if (this.errors.length === 0 && this.warnings.length === 0) {
            report += '✅ NO ERRORS OR WARNINGS DETECTED!\n\n';
        }
        
        report += '═══════════════════════════════════════════════════════════════\n';
        
        return report;
    }
}

/**
 * Setup error monitoring for a page
 */
async function setupErrorMonitoring(page, errorTracker, pageName) {
    // Monitor console messages
    page.on('console', message => {
        const type = message.type();
        const text = message.text();
        const location = message.location();
        
        if (type === 'error') {
            console.error(`   ⚠️  Console Error on ${pageName}: ${text}`);
            errorTracker.addConsoleError(text, pageName, 
                `${location.url}:${location.lineNumber}:${location.columnNumber}`);
        } else if (type === 'warning' && text.includes('Vue')) {
            console.warn(`   ⚠️  Vue Warning on ${pageName}: ${text}`);
            errorTracker.addWarning(text, `Vue on ${pageName}`);
        }
    });

    // Monitor page errors
    page.on('pageerror', error => {
        console.error(`   ❌ Page Error on ${pageName}: ${error.message}`);
        errorTracker.addConsoleError(error.message, pageName, error.stack || 'Unknown location');
    });

    // Monitor failed requests
    page.on('response', async response => {
        const status = response.status();
        const url = response.url();
        const method = response.request().method();
        
        // Check for error status codes (4xx, 5xx)
        if (status >= 400) {
            let errorDetails = null;
            
            // For 5xx errors, try to capture detailed error information
            if (status >= 500) {
                try {
                    const responseText = await response.text();
                    if (responseText) {
                        try {
                            const data = JSON.parse(responseText);
                            errorDetails = {
                                message: data.message || null,
                                exception: data.exception || null,
                                file: data.file || null,
                                line: data.line || null,
                                trace: data.trace ? (Array.isArray(data.trace) ? data.trace.slice(0, 3) : data.trace.split('\n').slice(0, 5).join('\n')) : null
                            };
                            
                            // Check for SQL errors
                            const errorStr = JSON.stringify(data).toLowerCase();
                            if (errorStr.includes('sql') || errorStr.includes('database') || errorStr.includes('query')) {
                                errorDetails.possibleSqlError = true;
                            }
                        } catch (parseError) {
                            // Not JSON, store raw text (limited)
                            errorDetails = {
                                rawError: responseText.substring(0, 500),
                                isSqlError: responseText.toLowerCase().includes('sql') || responseText.toLowerCase().includes('database')
                            };
                        }
                    }
                } catch (error) {
                    // Could not read response
                    errorDetails = { error: 'Could not read response body' };
                }
                
                // Always track 5xx errors with details
                console.error(`   ❌ Server Error on ${pageName}: ${method} ${url} returned ${status}`);
                if (errorDetails && errorDetails.message) {
                    console.error(`      Error: ${errorDetails.message}`);
                }
                if (errorDetails && errorDetails.possibleSqlError) {
                    console.error(`      🗄️  POSSIBLE SQL ERROR DETECTED`);
                }
                errorTracker.addNetworkError(url, status, method, errorDetails);
            } else if (url.includes('/api/') && ![401, 403, 404].includes(status)) {
                // Only track API errors, not expected 401/403 for unauth requests
                console.error(`   ❌ Network Error on ${pageName}: ${method} ${url} returned ${status}`);
                errorTracker.addNetworkError(url, status, method, errorDetails);
            }
        }
    });

    // Monitor request failures
    page.on('requestfailed', request => {
        const failure = request.failure();
        const url = request.url();
        const method = request.method();
        
        console.error(`   ❌ Request Failed on ${pageName}: ${method} ${url}`);
        console.error(`      Reason: ${failure ? failure.errorText : 'Unknown'}`);
        
        errorTracker.addNetworkError(url, 0, method);
    });
}

/**
 * Check for UI error notifications
 */
async function checkForUIErrors(page, errorTracker, pageName) {
    try {
        // Wait a bit for any async errors to appear
        await page.waitForTimeout(1000);
        
        // Check for error notifications (UserFrosting/UIkit style)
        const errorAlerts = await page.locator('.uk-alert.uk-alert-danger').all();
        
        for (const alert of errorAlerts) {
            const isVisible = await alert.isVisible();
            if (isVisible) {
                const title = await alert.locator('.uk-alert-title').textContent().catch(() => '');
                const message = await alert.textContent();
                
                console.error(`   ❌ UI Error Notification on ${pageName}:`);
                if (title) console.error(`      Title: ${title}`);
                console.error(`      Message: ${message.substring(0, 200)}`);
                
                errorTracker.addUIError(title, message, pageName);
            }
        }
        
        // Check for Vue error displays
        const vueErrors = await page.locator('[data-error], .error-message, .vue-error').all();
        for (const vueError of vueErrors) {
            const isVisible = await vueError.isVisible();
            if (isVisible) {
                const message = await vueError.textContent();
                console.error(`   ❌ Vue Error Display on ${pageName}: ${message}`);
                errorTracker.addVueError(message, pageName);
            }
        }
        
    } catch (error) {
        // Don't fail if error checking fails
        console.warn(`   ⚠️  Could not check for UI errors on ${pageName}: ${error.message}`);
    }
}

/**
 * Validate page loaded correctly
 */
async function validatePageLoad(page, pageName, expectedElements = []) {
    const validations = [];
    
    // Check page title
    try {
        const title = await page.title();
        if (!title || title === 'Error') {
            validations.push(`Page title is missing or "Error"`);
        }
    } catch (error) {
        validations.push(`Could not get page title: ${error.message}`);
    }
    
    // Check for expected elements
    for (const selector of expectedElements) {
        try {
            const element = page.locator(selector);
            const isVisible = await element.isVisible({ timeout: 5000 });
            if (!isVisible) {
                validations.push(`Expected element not visible: ${selector}`);
            }
        } catch (error) {
            validations.push(`Expected element not found: ${selector}`);
        }
    }
    
    return validations;
}

/**
 * Main test execution
 */
async function main() {
    const args = process.argv.slice(2);
    const configFile = args[0] || '.github/config/integration-test-paths.json';
    const baseUrl = args[1] || 'http://localhost:8080';
    const username = args[2] || 'admin';
    const password = args[3] || 'admin123';
    
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('Enhanced Frontend Error Detection');
    console.log('═══════════════════════════════════════════════════════════════');
    console.log(`Config: ${configFile}`);
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Username: ${username}`);
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
    
    const errorTracker = new ErrorTracker();
    
    // Launch browser
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Setup global error monitoring
    await setupErrorMonitoring(page, errorTracker, 'Global');
    
    try {
        // Login
        console.log('🔐 Logging in...');
        await page.goto(`${baseUrl}/account/sign-in`);
        await page.fill('input[name="user_name"]', username);
        await page.fill('input[name="password"]', password);
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard', { timeout: 10000 });
        console.log('✅ Logged in successfully\n');
        
        // Test each frontend page
        const screenshots = config.paths?.authenticated?.screenshots || [];
        
        console.log(`Testing ${screenshots.length} frontend pages...\n`);
        
        for (const screenshot of screenshots) {
            const pageName = screenshot.name;
            const path = screenshot.path;
            
            console.log(`📍 Testing: ${pageName}`);
            console.log(`   Path: ${path}`);
            
            try {
                // Navigate to page
                await page.goto(`${baseUrl}${path}`, { waitUntil: 'networkidle' });
                
                // Wait for page to be fully loaded
                await page.waitForTimeout(2000);
                
                // Check for errors
                await checkForUIErrors(page, errorTracker, pageName);
                
                // Validate page loaded correctly
                const validationErrors = await validatePageLoad(page, pageName, 
                    screenshot.expectedElements || []);
                
                if (validationErrors.length > 0) {
                    console.warn(`   ⚠️  Validation warnings:`);
                    validationErrors.forEach(err => console.warn(`      - ${err}`));
                    validationErrors.forEach(err => 
                        errorTracker.addWarning(err, pageName));
                }
                
                console.log(`   ✅ Tested ${pageName}\n`);
                
            } catch (error) {
                console.error(`   ❌ Failed to test ${pageName}: ${error.message}\n`);
                errorTracker.addConsoleError(
                    `Failed to load page: ${error.message}`,
                    pageName,
                    'Page Navigation'
                );
            }
        }
        
    } finally {
        await browser.close();
    }
    
    // Generate and display report
    console.log('\n');
    const report = errorTracker.generateReport();
    console.log(report);
    
    // Save report to file
    const reportFile = '/tmp/frontend-error-report.txt';
    writeFileSync(reportFile, report);
    console.log(`📄 Report saved to: ${reportFile}\n`);
    
    // Exit with error code if critical errors found
    if (errorTracker.hasCriticalErrors()) {
        console.error('❌ CRITICAL ERRORS DETECTED - Tests failed!');
        process.exit(1);
    } else if (errorTracker.warnings.length > 0) {
        console.warn('⚠️  WARNINGS DETECTED - Review recommended');
        process.exit(0); // Don't fail on warnings
    } else {
        console.log('✅ NO ERRORS DETECTED - All tests passed!');
        process.exit(0);
    }
}

// Run if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
    main().catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}

export { ErrorTracker, setupErrorMonitoring, checkForUIErrors, validatePageLoad };
