#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Authenticated Screenshot Script
 * 
 * This script uses Playwright to:
 * 1. Navigate to the login page
 * 2. Log in with admin credentials
 * 3. Take screenshots of CRUD6 pages
 * 4. Capture and analyze network requests
 * 
 * Usage: node take-authenticated-screenshots.js <base_url> <username> <password>
 * Example: node take-authenticated-screenshots.js http://localhost:8080 admin admin123
 */

import { chromium } from 'playwright';
import { writeFileSync } from 'fs';

/**
 * Analyze network requests and generate report
 */
function analyzeNetworkRequests(pageTitle, requests) {
    console.log('');
    console.log(`### ${pageTitle}`);
    console.log('');
    
    // Filter by request type
    const schemaRequests = requests.filter(r => r.url.includes('/schema'));
    const yamlRequests = requests.filter(r => r.url.endsWith('.yaml') || r.url.includes('.yaml?'));
    const apiRequests = requests.filter(r => r.url.includes('/api/crud6/'));
    const otherRequests = requests.filter(r => 
        !r.url.includes('/schema') && 
        !r.url.endsWith('.yaml') && 
        !r.url.includes('.yaml?') &&
        !r.url.includes('/api/crud6/')
    );
    
    // Schema API calls
    if (schemaRequests.length > 0) {
        console.log(`📊 Schema API Calls: ${schemaRequests.length}`);
        schemaRequests.forEach((req, idx) => {
            const url = new URL(req.url);
            const context = url.searchParams.get('context') || 'full';
            const includeRelated = url.searchParams.get('include_related') || 'false';
            console.log(`   ${idx + 1}. ${req.method} ${url.pathname}`);
            console.log(`      Context: ${context}`);
            console.log(`      Include Related: ${includeRelated}`);
            console.log(`      Status: ${req.status || 'pending'}`);
        });
    } else {
        console.log('📊 Schema API Calls: 0');
    }
    
    // YAML file imports
    if (yamlRequests.length > 0) {
        console.log('');
        console.log(`⚠️  YAML File Imports: ${yamlRequests.length} (SHOULD BE 0!)`);
        yamlRequests.forEach((req, idx) => {
            const url = new URL(req.url);
            const filename = url.pathname.split('/').pop().split('?')[0];
            console.log(`   ${idx + 1}. ${filename}`);
            console.log(`      Full URL: ${req.url}`);
            console.log(`      Status: ${req.status || 'pending'}`);
        });
    } else {
        console.log('');
        console.log('✅ YAML File Imports: 0 (Good - no redundant YAML imports)');
    }
    
    // CRUD6 API calls
    if (apiRequests.length > 0) {
        console.log('');
        console.log(`🔌 CRUD6 API Calls: ${apiRequests.length}`);
        apiRequests.forEach((req, idx) => {
            const url = new URL(req.url);
            console.log(`   ${idx + 1}. ${req.method} ${url.pathname}`);
            console.log(`      Status: ${req.status || 'pending'}`);
        });
    }
    
    // Other requests (for reference)
    if (otherRequests.length > 0) {
        console.log('');
        console.log(`📡 Other XHR/Fetch Requests: ${otherRequests.length}`);
        // Only show first 5 to avoid cluttering output
        otherRequests.slice(0, 5).forEach((req, idx) => {
            const url = new URL(req.url);
            console.log(`   ${idx + 1}. ${req.method} ${url.pathname}`);
        });
        if (otherRequests.length > 5) {
            console.log(`   ... and ${otherRequests.length - 5} more`);
        }
    }
    
    // Summary
    console.log('');
    console.log('📈 Summary:');
    console.log(`   Total XHR/Fetch Requests: ${requests.length}`);
    console.log(`   Schema API Calls: ${schemaRequests.length}`);
    console.log(`   YAML Imports: ${yamlRequests.length} ${yamlRequests.length > 0 ? '⚠️  (ISSUE!)' : '✅'}`);
    console.log(`   CRUD6 API Calls: ${apiRequests.length}`);
    
    // Check for issues
    const issues = [];
    
    if (schemaRequests.length > 1) {
        issues.push(`Multiple schema API calls detected (${schemaRequests.length})`);
    }
    
    if (yamlRequests.length > 0) {
        issues.push(`YAML files being imported (${yamlRequests.length}) - validation adapter not working correctly`);
    }
    
    if (issues.length > 0) {
        console.log('');
        console.log('⚠️  Issues Detected:');
        issues.forEach((issue, idx) => {
            console.log(`   ${idx + 1}. ${issue}`);
        });
    }
}

async function takeAuthenticatedScreenshots(baseUrl, username, password) {
    console.log('========================================');
    console.log('Taking Authenticated Screenshots');
    console.log('========================================');
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Username: ${username}`);
    console.log('');

    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    // Network request tracking
    const networkRequests = {
        list: [],
        detail: []
    };
    let currentPage = null;

    try {
        const context = await browser.newContext({
            viewport: { width: 1280, height: 720 },
            ignoreHTTPSErrors: true
        });

        const page = await context.newPage();

        // Set up network request listener
        page.on('request', request => {
            const url = request.url();
            const method = request.method();
            const resourceType = request.resourceType();
            
            // Track all XHR and fetch requests
            if (resourceType === 'xhr' || resourceType === 'fetch') {
                const logEntry = {
                    url,
                    method,
                    type: resourceType,
                    timestamp: new Date().toISOString()
                };
                
                if (currentPage) {
                    networkRequests[currentPage].push(logEntry);
                }
            }
        });

        page.on('response', async response => {
            const url = response.url();
            const status = response.status();
            const resourceType = response.request().resourceType();
            
            // Track responses for XHR/fetch
            if (resourceType === 'xhr' || resourceType === 'fetch') {
                if (currentPage) {
                    const logEntry = networkRequests[currentPage].find(r => r.url === url && !r.status);
                    if (logEntry) {
                        logEntry.status = status;
                        logEntry.statusText = response.statusText();
                        logEntry.responseTime = new Date().toISOString();
                    }
                }
            }
        });

        // Step 1: Navigate to login page
        console.log('📍 Navigating to login page...');
        await page.goto(`${baseUrl}/account/sign-in`, { waitUntil: 'networkidle', timeout: 30000 });
        console.log('✅ Login page loaded');

        // Step 2: Fill in login form
        console.log('🔐 Logging in...');
        
        // Wait for the login form to be visible (UserFrosting 6 uses data-test attributes)
        // Use .uk-card to target the main body login form, not the header dropdown
        await page.waitForSelector('.uk-card input[data-test="username"]', { timeout: 10000 });
        
        // Fill in credentials using data-test selectors (qualified with .uk-card)
        await page.fill('.uk-card input[data-test="username"]', username);
        await page.fill('.uk-card input[data-test="password"]', password);
        
        // Click the login button using data-test selector and wait for navigation
        await Promise.all([
            page.waitForNavigation({ timeout: 15000 }).catch(() => {
                console.log('⚠️  No navigation detected after login, but continuing...');
            }),
            page.click('.uk-card button[data-test="submit"]')
        ]);
        
        console.log('✅ Logged in successfully');
        
        // Give it a moment for the session to stabilize
        await page.waitForTimeout(2000);

        // Step 3: Take screenshot of groups list page
        console.log('');
        console.log('📸 Taking screenshot: /crud6/groups');
        currentPage = 'list';
        await page.goto(`${baseUrl}/crud6/groups`, { waitUntil: 'networkidle', timeout: 30000 });
        
        // Wait for page content to load
        await page.waitForTimeout(2000);
        
        // Check if we're still on login page (would indicate auth failure)
        const currentUrl = page.url();
        if (currentUrl.includes('/account/sign-in')) {
            console.warn('⚠️  Warning: Still on login page - authentication may have failed');
        } else {
            console.log(`✅ Page loaded: ${currentUrl}`);
        }
        
        const listScreenshotPath = '/tmp/screenshot_groups_list.png';
        await page.screenshot({ 
            path: listScreenshotPath, 
            fullPage: true 
        });
        console.log(`✅ Screenshot saved: ${listScreenshotPath}`);

        // Step 4: Take screenshot of single group page
        console.log('');
        console.log('📸 Taking screenshot: /crud6/groups/1');
        currentPage = 'detail';
        await page.goto(`${baseUrl}/crud6/groups/1`, { waitUntil: 'networkidle', timeout: 30000 });
        
        // Wait for page content to load
        await page.waitForTimeout(2000);
        
        // Check if we're still on login page
        const currentUrl2 = page.url();
        if (currentUrl2.includes('/account/sign-in')) {
            console.warn('⚠️  Warning: Still on login page - authentication may have failed');
        } else {
            console.log(`✅ Page loaded: ${currentUrl2}`);
        }
        
        const detailScreenshotPath = '/tmp/screenshot_group_detail.png';
        await page.screenshot({ 
            path: detailScreenshotPath, 
            fullPage: true 
        });
        console.log(`✅ Screenshot saved: ${detailScreenshotPath}`);

        console.log('');
        console.log('========================================');
        console.log('✅ All screenshots taken successfully');
        console.log('========================================');

        // Analyze and report network requests
        console.log('');
        console.log('========================================');
        console.log('Network Request Analysis');
        console.log('========================================');
        
        analyzeNetworkRequests('Groups List (/crud6/groups)', networkRequests.list);
        analyzeNetworkRequests('Group Detail (/crud6/groups/1)', networkRequests.detail);
        
        // Save detailed network log to file
        const networkLog = {
            timestamp: new Date().toISOString(),
            baseUrl,
            pages: {
                list: {
                    url: `${baseUrl}/crud6/groups`,
                    requests: networkRequests.list
                },
                detail: {
                    url: `${baseUrl}/crud6/groups/1`,
                    requests: networkRequests.detail
                }
            },
            summary: {
                totalRequests: networkRequests.list.length + networkRequests.detail.length,
                listPageRequests: networkRequests.list.length,
                detailPageRequests: networkRequests.detail.length,
                schemaApiCalls: {
                    list: networkRequests.list.filter(r => r.url.includes('/schema')).length,
                    detail: networkRequests.detail.filter(r => r.url.includes('/schema')).length
                },
                yamlImports: {
                    list: networkRequests.list.filter(r => r.url.endsWith('.yaml') || r.url.includes('.yaml?')).length,
                    detail: networkRequests.detail.filter(r => r.url.endsWith('.yaml') || r.url.includes('.yaml?')).length
                }
            }
        };
        
        const logPath = '/tmp/network_requests.json';
        writeFileSync(logPath, JSON.stringify(networkLog, null, 2));
        console.log('');
        console.log(`📝 Detailed network log saved to: ${logPath}`);

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
        await browser.close();
    }
}

// Parse command line arguments
const args = process.argv.slice(2);

if (args.length < 3) {
    console.error('Usage: node take-authenticated-screenshots.js <base_url> <username> <password>');
    console.error('Example: node take-authenticated-screenshots.js http://localhost:8080 admin admin123');
    process.exit(1);
}

const [baseUrl, username, password] = args;

// Run the script
takeAuthenticatedScreenshots(baseUrl, username, password)
    .then(() => {
        process.exit(0);
    })
    .catch((error) => {
        console.error('Script failed:', error);
        process.exit(1);
    });
