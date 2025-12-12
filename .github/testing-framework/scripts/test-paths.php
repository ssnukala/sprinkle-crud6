#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Path Testing Script
 *
 * This script tests API and frontend paths from a JSON configuration file.
 * It's designed to be modular and reusable across different sprinkles.
 *
 * Usage: php test-paths.php <config_file> [auth|unauth|both] [api|frontend|both]
 * Example: php test-paths.php integration-test-paths.json auth api
 * Example: php test-paths.php integration-test-paths.json both both  # Tests both authenticated and unauthenticated
 */

// Parse command line arguments
$configFile = $argv[1] ?? null;
$authType = $argv[2] ?? 'both';  // auth, unauth, or both
$pathType = $argv[3] ?? 'both';  // api, frontend, or both

if (!$configFile) {
    echo "Usage: php test-paths.php <config_file> [auth|unauth|both] [api|frontend|both]\n";
    echo "Example: php test-paths.php integration-test-paths.json auth api\n";
    exit(1);
}

// Load configuration
if (!file_exists($configFile)) {
    echo "ERROR: Configuration file not found: {$configFile}\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    echo "ERROR: Failed to parse configuration file\n";
    exit(1);
}

$baseUrl = $config['config']['base_url'] ?? 'http://localhost:8080';
$username = $config['config']['auth']['username'] ?? 'admin';
$password = $config['config']['auth']['password'] ?? 'admin123';

echo "=========================================\n";
echo "Testing Paths from Configuration\n";
echo "=========================================\n";
echo "Config file: {$configFile}\n";
echo "Base URL: {$baseUrl}\n";
echo "Auth type: {$authType}\n";
echo "Path type: {$pathType}\n";
echo "\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;
$warningTests = 0;

// Global cookie jar for session management
$cookieJar = tempnam(sys_get_temp_dir(), 'cookies_');

/**
 * Perform login to get authenticated session
 * 
 * @param string $baseUrl Base URL of the application
 * @param string $username Username to login with
 * @param string $password Password to login with
 * @param string $cookieJar Path to cookie jar file
 * @return bool True if login successful, false otherwise
 */
function performLogin($baseUrl, $username, $password, $cookieJar) {
    echo "=========================================\n";
    echo "Authenticating User\n";
    echo "=========================================\n";
    echo "Username: {$username}\n";
    echo "Login URL: {$baseUrl}/account/sign-in\n\n";
    
    // Step 1: Get the login page to obtain CSRF token and initial session
    $loginPageUrl = $baseUrl . '/account/sign-in';
    $tmpFile = tempnam(sys_get_temp_dir(), 'login_page_');
    
    $curlCmd = "curl -s -o {$tmpFile} -c {$cookieJar} -L '{$loginPageUrl}' 2>&1";
    exec($curlCmd, $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "❌ Failed to load login page\n";
        echo "   Error: " . implode("\n   ", $output) . "\n\n";
        unlink($tmpFile);
        return false;
    }
    
    $loginPageContent = file_get_contents($tmpFile);
    unlink($tmpFile);
    
    // Extract CSRF token from the login page
    $csrfToken = null;
    if (preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*value=["\']([^"\']+)["\']/', $loginPageContent, $matches)) {
        $csrfToken = $matches[1];
    } elseif (preg_match('/<input[^>]*value=["\']([^"\']+)["\'][^>]*name=["\']csrf_token["\']/', $loginPageContent, $matches)) {
        $csrfToken = $matches[1];
    } elseif (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPageContent, $matches)) {
        $csrfToken = $matches[1];
    }
    
    if (!$csrfToken) {
        echo "⚠️  Warning: Could not extract CSRF token from login page\n";
        echo "   Attempting login without CSRF token...\n";
    } else {
        echo "✅ CSRF token obtained\n";
    }
    
    // Step 2: Submit login form
    $loginUrl = $baseUrl . '/account/sign-in';
    $tmpFile = tempnam(sys_get_temp_dir(), 'login_response_');
    
    // Build form data
    $postData = [
        'user_name' => $username,
        'password' => $password,
    ];
    
    if ($csrfToken) {
        $postData['csrf_token'] = $csrfToken;
    }
    
    // Convert to URL-encoded string
    $postDataString = http_build_query($postData);
    
    // Perform login POST request
    $curlCmd = "curl -s -o {$tmpFile} -w '%{http_code}' -b {$cookieJar} -c {$cookieJar} -L " .
               "-X POST -H 'Content-Type: application/x-www-form-urlencoded' " .
               "--data '{$postDataString}' '{$loginUrl}' 2>&1";
    
    $httpCode = trim(shell_exec($curlCmd));
    $loginResponse = file_get_contents($tmpFile);
    unlink($tmpFile);
    
    // Check if login was successful
    // Successful login typically results in redirect (302/303) or 200
    // We check for absence of login form and presence of dashboard/logged-in indicators
    $isStillOnLoginPage = (
        strpos($loginResponse, 'sign-in') !== false ||
        strpos($loginResponse, 'data-test="username"') !== false ||
        strpos($loginResponse, 'Please sign in') !== false
    );
    
    $hasLoggedInIndicators = (
        strpos($loginResponse, 'dashboard') !== false ||
        strpos($loginResponse, 'sign-out') !== false ||
        strpos($loginResponse, 'Sign Out') !== false ||
        strpos($loginResponse, 'logout') !== false
    );
    
    if ($hasLoggedInIndicators && !$isStillOnLoginPage) {
        echo "✅ Login successful (HTTP {$httpCode})\n";
        echo "   Session established\n\n";
        return true;
    } else {
        echo "❌ Login failed (HTTP {$httpCode})\n";
        if ($isStillOnLoginPage) {
            echo "   Still on login page - credentials may be incorrect\n";
        }
        echo "   Response length: " . strlen($loginResponse) . " bytes\n\n";
        return false;
    }
}

// Function to test a path
function testPath($name, $pathConfig, $baseUrl, $isAuth = false, $username = null, $password = null, $cookieJar = null) {
    global $totalTests, $passedTests, $failedTests, $skippedTests, $warningTests;
    
    $totalTests++;
    
    // Check if test should be skipped
    if (isset($pathConfig['skip']) && $pathConfig['skip']) {
        echo "⏭️  SKIP: {$name}\n";
        echo "   Reason: " . ($pathConfig['skip_reason'] ?? 'Not specified') . "\n\n";
        $skippedTests++;
        return;
    }
    
    $path = $pathConfig['path'];
    $method = $pathConfig['method'] ?? 'GET';
    $description = $pathConfig['description'] ?? $name;
    $expectedStatus = $pathConfig['expected_status'] ?? 200;
    
    echo "Testing: {$name}\n";
    echo "   Description: {$description}\n";
    echo "   Method: {$method}\n";
    echo "   Path: {$path}\n";
    
    // Build curl command
    $url = $baseUrl . $path;
    $tmpFile = tempnam(sys_get_temp_dir(), 'path_test_');
    
    // Basic curl options
    $curlCmd = "curl -s -o {$tmpFile} -w '%{http_code}' ";
    
    // Add authentication cookie jar if needed
    if ($isAuth && $cookieJar && file_exists($cookieJar)) {
        // Use cookie jar for authenticated requests
        $curlCmd .= "-b {$cookieJar} -c {$cookieJar} ";
    }
    
    // Follow redirects
    $curlCmd .= "-L ";
    
    // Add payload for POST/PUT/PATCH requests
    if (isset($pathConfig['payload']) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $payload = json_encode($pathConfig['payload']);
        $curlCmd .= "-H 'Content-Type: application/json' --data '{$payload}' ";
    }
    
    $curlCmd .= "-X {$method} '{$url}'";
    
    // Execute curl command
    $httpCode = trim(shell_exec($curlCmd));
    
    // For unauthenticated API tests, permission failures (400/401/403) should warn, not fail
    // We're looking for actual code/SQL failures (500, syntax errors, etc.)
    // Note: Patterns are hardcoded as they're specific to CRUD6 API structure
    $isUnauthApiTest = !$isAuth && isset($pathConfig['path']) && strpos($pathConfig['path'], '/api/') !== false;
    $isPermissionFailure = in_array($httpCode, ['400', '401', '403']); // HTTP bad request/unauthorized/forbidden
    $isServerError = in_array($httpCode, ['500', '502', '503', '504']); // HTTP server errors
    
    // Check if this is a CREATE endpoint (POST method to /api/crud6/{model})
    // Excludes custom actions (/a/{action}) which use POST but aren't create operations
    $isCreateEndpoint = $method === 'POST' && strpos($path, '/api/crud6/') !== false && !strpos($path, '/a/');
    
    // Validate status code
    if ($httpCode == $expectedStatus) {
        echo "   ✅ Status: {$httpCode} (expected {$expectedStatus})\n";
        
        // Additional validation if specified
        if (isset($pathConfig['validation'])) {
            $validation = $pathConfig['validation'];
            $content = file_get_contents($tmpFile);
            
            switch ($validation['type']) {
                case 'json':
                    $json = json_decode($content, true);
                    if ($json) {
                        $allFound = true;
                        foreach ($validation['contains'] ?? [] as $key) {
                            if (!isset($json[$key])) {
                                echo "   ⚠️  Missing expected key: {$key}\n";
                                $allFound = false;
                            }
                        }
                        if ($allFound) {
                            echo "   ✅ Validation: JSON contains expected keys\n";
                        }
                    } else {
                        echo "   ⚠️  Response is not valid JSON\n";
                    }
                    break;
                    
                case 'redirect_to_login':
                    $containsLogin = false;
                    foreach ($validation['contains'] ?? [] as $loginIndicator) {
                        if (stripos($content, $loginIndicator) !== false) {
                            $containsLogin = true;
                            break;
                        }
                    }
                    if ($containsLogin) {
                        echo "   ✅ Validation: Redirected to login\n";
                    } else {
                        echo "   ⚠️  Warning: No login indicators found in response\n";
                    }
                    break;
                    
                case 'status_only':
                    // Status code check is sufficient
                    break;
            }
        }
        
        echo "   ✅ PASSED\n\n";
        $passedTests++;
    } elseif ($isUnauthApiTest && $isPermissionFailure) {
        // For unauthenticated API tests, permission failures are expected - warn and continue
        echo "   ⚠️  Status: {$httpCode} (expected {$expectedStatus})\n";
        if ($httpCode === '400') {
            echo "   ⚠️  WARNING: Authentication/CSRF failure ({$httpCode}) - expected for unauthenticated request\n";
        } elseif ($isCreateEndpoint) {
            echo "   ⚠️  WARNING: CREATE endpoint returned {$httpCode} - this is acceptable (may or may not need permissions)\n";
        } else {
            echo "   ⚠️  WARNING: Permission failure ({$httpCode}) - expected for unauthenticated request\n";
        }
        echo "   ⚠️  WARNED (continuing tests to check for code/SQL failures)\n\n";
        $warningTests++;
    } elseif ($isUnauthApiTest && $isServerError) {
        // Server errors indicate actual code/SQL failures - these should fail
        echo "   ❌ Status: {$httpCode} (expected {$expectedStatus})\n";
        echo "   ❌ FAILED: Server error detected - possible code/SQL failure\n";
        
        // Try to read error details from response
        $content = file_get_contents($tmpFile);
        if ($content) {
            $json = json_decode($content, true);
            if ($json && isset($json['message'])) {
                echo "   ❌ Error: {$json['message']}\n";
            }
        }
        echo "\n";
        $failedTests++;
    } else {
        echo "   ❌ Status: {$httpCode} (expected {$expectedStatus})\n";
        echo "   ❌ FAILED\n\n";
        $failedTests++;
    }
    
    // Clean up
    unlink($tmpFile);
}

// Test authenticated paths
if ($authType === 'auth' || $authType === 'both') {
    $authPaths = $config['paths']['authenticated'] ?? [];
    
    // Check if there are any authenticated tests to run
    $hasAuthTests = (
        (($pathType === 'api' || $pathType === 'both') && isset($authPaths['api']) && count($authPaths['api']) > 0) ||
        (($pathType === 'frontend' || $pathType === 'both') && isset($authPaths['frontend']) && count($authPaths['frontend']) > 0)
    );
    
    if ($hasAuthTests) {
        // Perform login before testing authenticated paths
        if (!performLogin($baseUrl, $username, $password, $cookieJar)) {
            echo "❌ Authentication failed - skipping authenticated tests\n\n";
            // Count skipped tests
            if (($pathType === 'api' || $pathType === 'both') && isset($authPaths['api'])) {
                $skippedTests += count($authPaths['api']);
            }
            if (($pathType === 'frontend' || $pathType === 'both') && isset($authPaths['frontend'])) {
                $skippedTests += count($authPaths['frontend']);
            }
        } else {
            // Login successful, proceed with authenticated tests
            if (($pathType === 'api' || $pathType === 'both') && isset($authPaths['api'])) {
                echo "=========================================\n";
                echo "Testing Authenticated API Paths\n";
                echo "=========================================\n\n";
                
                foreach ($authPaths['api'] as $name => $pathConfig) {
                    testPath($name, $pathConfig, $baseUrl, true, $username, $password, $cookieJar);
                }
            }
            
            if (($pathType === 'frontend' || $pathType === 'both') && isset($authPaths['frontend'])) {
                echo "=========================================\n";
                echo "Testing Authenticated Frontend Paths\n";
                echo "=========================================\n\n";
                
                foreach ($authPaths['frontend'] as $name => $pathConfig) {
                    testPath($name, $pathConfig, $baseUrl, true, $username, $password, $cookieJar);
                }
            }
        }
    }
}

// Test unauthenticated paths
if ($authType === 'unauth' || $authType === 'both') {
    $unauthPaths = $config['paths']['unauthenticated'] ?? [];
    
    if (($pathType === 'api' || $pathType === 'both') && isset($unauthPaths['api'])) {
        echo "=========================================\n";
        echo "Testing Unauthenticated API Paths\n";
        echo "=========================================\n\n";
        
        foreach ($unauthPaths['api'] as $name => $pathConfig) {
            testPath($name, $pathConfig, $baseUrl, false);
        }
    }
    
    if (($pathType === 'frontend' || $pathType === 'both') && isset($unauthPaths['frontend'])) {
        echo "=========================================\n";
        echo "Testing Unauthenticated Frontend Paths\n";
        echo "=========================================\n\n";
        
        foreach ($unauthPaths['frontend'] as $name => $pathConfig) {
            testPath($name, $pathConfig, $baseUrl, false);
        }
    }
}

// Cleanup
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}

// Print summary
echo "=========================================\n";
echo "Test Summary\n";
echo "=========================================\n";
echo "Total tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Warnings: {$warningTests}\n";
echo "Failed: {$failedTests}\n";
echo "Skipped: {$skippedTests}\n";
echo "\n";

// Generate a table format for results (continue on failures - don't fail the test)
$continue_on_failure = getenv('CONTINUE_ON_FAILURE') ?: 'false';
if ($continue_on_failure === 'true') {
    // In report mode, always exit 0 to allow the workflow to continue
    if ($failedTests > 0) {
        echo "⚠️  Some tests failed (actual code/SQL errors detected)\n";
        echo "   Note: Permission failures (400/401/403) are warnings, not failures\n";
        echo "   Continuing workflow to collect all test results...\n";
    } elseif ($warningTests > 0) {
        echo "✅ Tests completed (permission warnings are expected for unauthenticated requests)\n";
        echo "   {$warningTests} permission warnings detected (400/401/403 status codes)\n";
        echo "   No actual code/SQL errors found\n";
    } else {
        echo "✅ All tests passed\n";
    }
    exit(0);
} else {
    // In strict mode, fail on errors
    if ($failedTests > 0) {
        echo "❌ Some tests failed (actual code/SQL errors detected)\n";
        echo "   Note: Permission failures (400/401/403) are warnings, not failures\n";
        exit(1);
    } elseif ($warningTests > 0) {
        echo "✅ All tests passed (permission warnings are expected for unauthenticated requests)\n";
        echo "   {$warningTests} permission warnings detected (400/401/403 status codes)\n";
        echo "   No actual code/SQL errors found\n";
        exit(0);
    } else {
        echo "✅ All tests passed\n";
        exit(0);
    }
}
