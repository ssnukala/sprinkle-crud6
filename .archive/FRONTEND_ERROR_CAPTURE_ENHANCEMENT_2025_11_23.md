# Frontend Error Capture Enhancement - November 23, 2025

## Issue
Integration test failing with login form not found. Need to capture frontend browser console errors to diagnose the root cause of the login page rendering issue.

## Problem Statement
From GitHub Actions run #19613929679:
- Login page loads but form elements cannot be found
- No backend errors detected
- Need to capture browser console errors to see frontend JavaScript failures
- Screenshots show empty or broken page

## Changes Made

### Enhanced Browser Error Capture

#### 1. All Console Messages
**Previous:** Only captured errors and warnings
**New:** Capture ALL console messages (info, log, debug, error, warning)
```javascript
page.on('console', msg => {
    const type = msg.type();
    const text = msg.text();
    // Log all console messages (not just errors/warnings)
    console.log(`   🖥️  Browser ${type}: ${text}`);
    // Store errors and warnings for later analysis
    if (type === 'error' || type === 'warning') {
        consoleErrors.push({ type, text, timestamp: Date.now() });
    }
});
```

#### 2. Uncaught JavaScript Exceptions
**Added:** Page error handler for uncaught exceptions
```javascript
page.on('pageerror', error => {
    console.log(`   ❌ Page Error (uncaught exception): ${error.message}`);
    console.log(`      Stack: ${error.stack}`);
    consoleErrors.push({ 
        type: 'pageerror', 
        text: error.message, 
        stack: error.stack,
        timestamp: Date.now() 
    });
});
```

#### 3. Failed Network Requests
**Added:** Request failure handler
```javascript
page.on('requestfailed', request => {
    const failure = request.failure();
    console.log(`   ⚠️  Request Failed: ${request.url()}`);
    console.log(`      Error: ${failure ? failure.errorText : 'Unknown error'}`);
});
```

### Enhanced Page State Analysis

#### 1. Vue.js Detection
**Added:** Check for Vue.js presence and version
```javascript
const pageState = await page.evaluate(() => {
    const state = {
        hasVue: typeof window.Vue !== 'undefined' || typeof window.__VUE__ !== 'undefined',
        hasVueRouter: typeof window.VueRouter !== 'undefined',
        vueVersion: window.Vue ? window.Vue.version : 'unknown',
        vueApps: []
    };

    // Try to detect Vue 3 apps
    if (window.__VUE_DEVTOOLS_GLOBAL_HOOK__) {
        const hook = window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
        if (hook.apps) {
            state.vueApps = hook.apps.map(app => ({
                version: app.version,
                config: app.config ? 'present' : 'missing'
            }));
        }
    }

    return state;
});
```

#### 2. Asset Loading Analysis
**Added:** Check for loaded scripts and stylesheets
```javascript
scripts: Array.from(document.querySelectorAll('script')).map(s => ({
    src: s.src,
    type: s.type,
    hasContent: s.innerHTML.length > 0
})),
stylesheets: Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map(l => l.href)
```

### Enhanced Login Form Debugging

#### 1. Detailed HTML Analysis
**Added:** When login form not found, analyze page structure
```javascript
const htmlAnalysis = await page.evaluate(() => {
    return {
        hasForm: document.querySelector('form') !== null,
        formCount: document.querySelectorAll('form').length,
        inputCount: document.querySelectorAll('input').length,
        inputs: Array.from(document.querySelectorAll('input')).map(input => ({
            type: input.type,
            name: input.name,
            id: input.id,
            dataTest: input.getAttribute('data-test'),
            placeholder: input.placeholder
        })),
        hasVueApp: document.querySelector('#app') !== null,
        vueAppHtml: document.querySelector('#app') ? document.querySelector('#app').innerHTML.substring(0, 200) : 'No #app element',
        bodyChildren: document.body.children.length,
        bodyHasContent: document.body.textContent.trim().length > 0
    };
});
```

#### 2. Input Field Discovery
**Added:** List all input fields found on page with details
- Shows type, name, id, data-test attributes
- Helps identify if fields exist but with different selectors
- Detects if page is completely empty

## Output Examples

### Successful Page Load
```
📍 Navigating to login page...
✅ Login page loaded
   Page Title: UserFrosting
   Page URL: http://localhost:8080/account/sign-in
📸 Early screenshot saved: /tmp/screenshot_login_page_initial.png
🔍 Checking page state...
   Page state:
      Vue detected: true
      Vue Router: true
      Vue apps: 1
      Body classes: uikit-theme
      Scripts loaded: 15
      Stylesheets loaded: 3
      Body HTML (first 500 chars): <div id="app">...
🔐 Logging in...
📸 Screenshot before selector search: /tmp/screenshot_before_login_selectors.png
   ✅ No browser console errors detected during page load
   Trying selector: .uk-card input[data-test="username"]
   ✅ Found username input with selector: .uk-card input[data-test="username"]
```

### Failed Page Load (Example)
```
📍 Navigating to login page...
✅ Login page loaded
   Page Title: UserFrosting
   Page URL: http://localhost:8080/account/sign-in
   🖥️  Browser error: Failed to load module script: Expected JavaScript module script...
   🖥️  Browser warning: Vue warn: Component <Login> mounted
   ⚠️  Request Failed: http://localhost:8080/assets/main.js
      Error: net::ERR_ABORTED
📸 Early screenshot saved: /tmp/screenshot_login_page_initial.png
🔍 Checking page state...
   Page state:
      Vue detected: false
      Vue Router: false
      Vue apps: 0
      Body classes: 
      Scripts loaded: 5
      Stylesheets loaded: 2
      Body HTML (first 500 chars): <div id="app"></div>
🔐 Logging in...
📸 Screenshot before selector search: /tmp/screenshot_before_login_selectors.png
   ⚠️  2 browser console errors/warnings detected:
      1. [error] Failed to load module script...
      2. [warning] Vue warn: Component <Login> mounted
   Trying selector: .uk-card input[data-test="username"]
   ⚠️  Selector .uk-card input[data-test="username"] not found, trying next...
   ...
❌ Could not find username input field after trying all selectors
   Debug HTML saved to /tmp/login_page_debug.html
   Debug screenshot saved to /tmp/login_page_debug.png

   HTML Analysis:
      Has form: false
      Form count: 0
      Input count: 0
      Has Vue app (#app): true
      Body children: 3
      Body has content: false
      ⚠️  No input fields found at all!
      Vue app content (first 200 chars): <div id="app"></div>
```

## What This Reveals

### Possible Issues to Detect

1. **Vite Build Failures**
   - Missing or failed JavaScript module loads
   - ES module syntax errors
   - Import resolution failures

2. **Vue Initialization Errors**
   - Vue app fails to mount
   - Component errors preventing render
   - Router configuration issues

3. **Asset Loading Problems**
   - CSS not loading
   - JavaScript bundles missing
   - Network errors fetching assets

4. **JavaScript Syntax Errors**
   - Uncaught exceptions during page load
   - Module import errors
   - TypeScript compilation issues

## Testing Impact

### Before Enhancement
- Silent failure - only "form not found"
- No insight into why form is missing
- Difficult to debug remotely

### After Enhancement
- Full console output captured
- JavaScript errors with stack traces
- Asset loading status
- Vue app state detection
- HTML structure analysis
- Network request failures

## Next Steps

1. **Run Integration Test**
   - Execute with enhanced logging
   - Review detailed console output
   - Analyze captured errors

2. **Identify Root Cause**
   - Vite build issue?
   - Vue component error?
   - Network/timing problem?
   - CSS/JavaScript loading failure?

3. **Fix Based on Findings**
   - If Vite: Check build configuration
   - If Vue: Check component code
   - If network: Check server/routing
   - If assets: Check asset pipeline

## Files Modified
- `.github/scripts/take-screenshots-with-tracking.js` - Enhanced error capture and debugging

## Related Documentation
- `.archive/CSRF_REMOVAL_FOR_TESTING_2025_11_23.md` - Previous debugging attempt
- `.github/workflows/integration-test.yml` - Integration test workflow
- Integration test configuration files

## Benefits

1. **Remote Debugging**
   - Can diagnose issues without local reproduction
   - CI logs contain all needed information
   - Stack traces for JavaScript errors

2. **Faster Resolution**
   - Pinpoint exact failure cause
   - No guesswork about frontend state
   - Clear indication of asset/Vue issues

3. **Better Documentation**
   - Future failures have detailed logs
   - Easy to compare working vs. broken states
   - Can track down regressions quickly
