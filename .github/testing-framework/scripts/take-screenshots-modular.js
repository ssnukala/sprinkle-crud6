#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Modular Screenshot Script
 * 
 * This script uses Playwright to take screenshots based on a JSON configuration.
 * It reads the paths configuration and takes screenshots for all frontend paths
 * that have the "screenshot" flag set to true.
 * 
 * Usage: node take-screenshots-modular.js <config_file> [base_url] [username] [password]
 * Example: node take-screenshots-modular.js integration-test-paths.json
 * Example: node take-screenshots-modular.js integration-test-paths.json http://localhost:8080 admin admin123
 */

import { chromium } from 'playwright';
import { readFileSync } from 'fs';

async function takeScreenshotsFromConfig(configFile, baseUrlOverride, usernameOverride, passwordOverride) {
    console.log('========================================');
    console.log('Taking Screenshots from Configuration');
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

        // Step 2: Take screenshots from configuration
        let successCount = 0;
        let failCount = 0;

        for (const screenshot of screenshots) {
            console.log('');
            console.log(`📸 Taking screenshot: ${screenshot.name}`);
            console.log(`   Path: ${screenshot.path}`);
            console.log(`   Description: ${screenshot.description}`);

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

if (args.length < 1) {
    console.error('Usage: node take-screenshots-modular.js <config_file> [base_url] [username] [password]');
    console.error('Example: node take-screenshots-modular.js integration-test-paths.json');
    console.error('Example: node take-screenshots-modular.js integration-test-paths.json http://localhost:8080 admin admin123');
    process.exit(1);
}

const [configFile, baseUrl, username, password] = args;

// Run the script
takeScreenshotsFromConfig(configFile, baseUrl, username, password)
    .then(() => {
        process.exit(0);
    })
    .catch((error) => {
        console.error('Script failed:', error);
        process.exit(1);
    });
