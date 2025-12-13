#!/usr/bin/env node

/**
 * UserFrosting CRUD6 Sprinkle Integration Test - Admin Login Script
 * 
 * This script uses Playwright to:
 * 1. Navigate to the login page
 * 2. Log in with admin credentials
 * 3. Verify successful authentication
 * 4. Save the authenticated browser state for subsequent tests
 * 
 * Usage: node login-admin.js <base_url> <username> <password> [state_file]
 * Example: node login-admin.js http://localhost:8080 admin admin123 /tmp/admin-auth-state.json
 */

import { chromium } from 'playwright';
import { writeFileSync } from 'fs';

async function loginAdmin(baseUrl, username, password, stateFile = '/tmp/admin-auth-state.json') {
    console.log('========================================');
    console.log('Admin Login - Establishing Authenticated Session');
    console.log('========================================');
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Username: ${username}`);
    console.log(`State file: ${stateFile}`);
    console.log('');

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

        // Step 1: Navigate to login page
        console.log('📍 Navigating to login page...');
        await page.goto(`${baseUrl}/account/sign-in`, { waitUntil: 'networkidle', timeout: 30000 });
        console.log('✅ Login page loaded');

        // Take a screenshot of the login page for diagnostics
        try {
            const loginPageScreenshotPath = '/tmp/login-page-before-attempt.png';
            await page.screenshot({ path: loginPageScreenshotPath, fullPage: true });
            console.log(`📸 Login page screenshot saved: ${loginPageScreenshotPath}`);
        } catch (screenshotError) {
            console.log('⚠️  Could not save login page screenshot');
        }

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

        // Step 3: Verify authentication by navigating to a protected page
        console.log('🔍 Verifying authentication...');
        console.log('📍 Navigating to protected page to verify login...');
        
        // Try to access a protected page (dashboard or admin page)
        await page.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle', timeout: 30000 }).catch(async () => {
            // If dashboard doesn't exist, try admin page
            await page.goto(`${baseUrl}/admin`, { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {
                console.log('⚠️  Protected pages not accessible, will check current URL');
            });
        });
        
        // Wait for page content to load
        await page.waitForTimeout(2000);
        
        // Check if we're still on login page (would indicate auth failure)
        const currentUrl = page.url();
        if (currentUrl.includes('/account/sign-in')) {
            console.error('❌ Authentication failed: Still on login page after protected page access attempt');
            console.error('   Current URL:', currentUrl);
            
            // Take screenshot for debugging
            try {
                const errorScreenshotPath = '/tmp/login-error-screenshot.png';
                await page.screenshot({ path: errorScreenshotPath, fullPage: true });
                console.error(`📸 Error screenshot saved: ${errorScreenshotPath}`);
            } catch (screenshotError) {
                console.error('⚠️  Could not save error screenshot');
            }
            
            // Check for error messages on the page (case-insensitive)
            const pageContent = await page.content();
            const pageContentLower = pageContent.toLowerCase();
            if (pageContentLower.includes('invalid username or password') || 
                pageContentLower.includes('error') || 
                pageContentLower.includes('failed') ||
                pageContentLower.includes('incorrect')) {
                console.error('⚠️  Login form may contain error messages');
                console.error('   Check the error screenshot for details');
            }
            
            await browser.close();
            process.exit(1);
        }
        
        console.log('✅ Authentication verified');
        console.log(`   Current URL: ${currentUrl}`);

        // Step 4: Save browser state (cookies, localStorage, etc.)
        console.log('💾 Saving authenticated browser state...');
        const storageState = await context.storageState();
        writeFileSync(stateFile, JSON.stringify(storageState, null, 2));
        console.log(`✅ Browser state saved to: ${stateFile}`);
        
        // Log cookies for debugging
        const cookies = storageState.cookies;
        console.log(`✅ Saved ${cookies.length} cookie(s):`);
        cookies.forEach(cookie => {
            console.log(`   - ${cookie.name}: ${cookie.value.substring(0, 20)}...`);
        });

        await browser.close();
        
        console.log('');
        console.log('========================================');
        console.log('✅ Admin login successful');
        console.log('========================================');
        console.log('Authenticated session is ready for testing');
        console.log('');

        process.exit(0);

    } catch (error) {
        console.error('❌ Login failed with error:', error.message);
        if (error.stack) {
            console.error('Stack trace:', error.stack);
        }
        await browser.close();
        process.exit(1);
    }
}

// Parse command line arguments
const baseUrl = process.argv[2] || 'http://localhost:8080';
const username = process.argv[3] || 'admin';
const password = process.argv[4] || 'admin123';
const stateFile = process.argv[5] || '/tmp/admin-auth-state.json';

// Validate arguments (need at least baseUrl, username, password)
// process.argv[0] = node, process.argv[1] = script, process.argv[2] = baseUrl, process.argv[3] = username, process.argv[4] = password
if (process.argv.length < 5) {
    console.log('Usage: node login-admin.js <base_url> <username> <password> [state_file]');
    console.log('Example: node login-admin.js http://localhost:8080 admin admin123');
    process.exit(1);
}

// Run login
loginAdmin(baseUrl, username, password, stateFile);
