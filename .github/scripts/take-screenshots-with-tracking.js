#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Screenshots with Network Tracking
 * 
 * This script uses Playwright to take screenshots and track network requests.
 * It reads the paths configuration and:
 * 1. Takes screenshots for all frontend paths
 * 2. Tracks all network requests made during page loads
 * 3. Detects redundant API calls
 * 4. Outputs a summary of network activity
 * 
 * Usage: node take-screenshots-with-tracking.js <config_file> [base_url] [username] [password]
 * Example: node take-screenshots-with-tracking.js integration-test-paths.json
 */

import { chromium } from 'playwright';
import { readFileSync } from 'fs';

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
            key: this.generateRequestKey(url, method)
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
}

async function takeScreenshotsFromConfig(configFile, baseUrlOverride, usernameOverride, passwordOverride) {
    console.log('========================================');
    console.log('Taking Screenshots with Network Tracking');
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

        // Step 2: Take screenshots from configuration and track network requests per page
        let successCount = 0;
        let failCount = 0;
        const pageNetworkStats = [];

        for (const screenshot of screenshots) {
            console.log('');
            console.log(`📸 Taking screenshot: ${screenshot.name}`);
            console.log(`   Path: ${screenshot.path}`);
            console.log(`   Description: ${screenshot.description}`);

            // Reset tracker for this page
            networkTracker.reset();
            networkTracker.startTracking();

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
                    
                    const screenshotPath = `/tmp/screenshot_${screenshot.screenshot_name}.png`;
                    await page.screenshot({ 
                        path: screenshotPath, 
                        fullPage: true 
                    });
                    console.log(`   ✅ Screenshot saved: ${screenshotPath}`);
                    
                    // Get network stats for this page
                    const summary = networkTracker.getSummary();
                    pageNetworkStats.push({
                        name: screenshot.name,
                        path: screenshot.path,
                        ...summary
                    });
                    
                    console.log(`   📡 Network: ${summary.total} requests (${summary.crud6Calls} CRUD6, ${summary.redundant} redundant groups)`);
                    
                    successCount++;
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
            console.log('⚠️  Some screenshots failed');
        } else {
            console.log('✅ All screenshots taken successfully');
        }

        // Output network tracking summary
        console.log('');
        console.log('═══════════════════════════════════════════════════════════════');
        console.log('Network Request Tracking Summary');
        console.log('═══════════════════════════════════════════════════════════════');
        
        let totalRequests = 0;
        let totalCRUD6 = 0;
        let totalRedundant = 0;
        let totalSchema = 0;
        
        pageNetworkStats.forEach(stats => {
            console.log(`\n📄 ${stats.name} (${stats.path})`);
            console.log(`   Total Requests:       ${stats.total}`);
            console.log(`   CRUD6 API Calls:      ${stats.crud6Calls}`);
            console.log(`   Schema API Calls:     ${stats.schemaCalls}`);
            console.log(`   Redundant Call Groups: ${stats.redundant}`);
            
            if (stats.redundant > 0) {
                console.log(`   ⚠️  WARNING: Redundant calls detected!`);
            }
            
            totalRequests += stats.total;
            totalCRUD6 += stats.crud6Calls;
            totalRedundant += stats.redundant;
            totalSchema += stats.schemaCalls;
        });
        
        console.log('\n' + '─'.repeat(80));
        console.log('Overall Totals:');
        console.log(`   Pages Tested:         ${pageNetworkStats.length}`);
        console.log(`   Total Requests:       ${totalRequests}`);
        console.log(`   Total CRUD6 Calls:    ${totalCRUD6}`);
        console.log(`   Total Schema Calls:   ${totalSchema}`);
        console.log(`   Total Redundant Groups: ${totalRedundant}`);
        
        if (totalRedundant > 0) {
            console.log('\n⚠️  WARNING: Redundant API calls detected across pages!');
        } else {
            console.log('\n✅ No redundant calls detected');
        }
        console.log('═══════════════════════════════════════════════════════════════');

    } catch (error) {
        console.error('');
        console.error('========================================');
        console.error('❌ Error taking screenshots:');
        console.error(error.message);
        console.error('========================================');
        
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
    .then(() => {
        process.exit(0);
    })
    .catch((error) => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
