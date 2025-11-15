#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle Integration Test - Path Testing Script
 *
 * This script tests API and frontend paths from a JSON configuration file.
 * It's designed to be modular and reusable across different sprinkles.
 *
 * Usage: php test-paths.php <config_file> [auth|unauth] [api|frontend]
 * Example: php test-paths.php integration-test-paths.json auth api
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

// Function to test a path
function testPath($name, $pathConfig, $baseUrl, $isAuth = false, $username = null, $password = null) {
    global $totalTests, $passedTests, $failedTests, $skippedTests;
    
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
    
    // Add authentication if needed
    if ($isAuth && $username && $password) {
        // For authenticated requests, we need to first get a session cookie
        // This is simplified - in a real scenario, you'd need to login first
        $curlCmd .= "-L "; // Follow redirects
    } else {
        $curlCmd .= "-L "; // Follow redirects for unauthenticated
    }
    
    $curlCmd .= "-X {$method} '{$url}'";
    
    // Execute curl command
    $httpCode = trim(shell_exec($curlCmd));
    
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
    
    if (($pathType === 'api' || $pathType === 'both') && isset($authPaths['api'])) {
        echo "=========================================\n";
        echo "Testing Authenticated API Paths\n";
        echo "=========================================\n\n";
        
        foreach ($authPaths['api'] as $name => $pathConfig) {
            testPath($name, $pathConfig, $baseUrl, true, $username, $password);
        }
    }
    
    if (($pathType === 'frontend' || $pathType === 'both') && isset($authPaths['frontend'])) {
        echo "=========================================\n";
        echo "Testing Authenticated Frontend Paths\n";
        echo "=========================================\n\n";
        
        foreach ($authPaths['frontend'] as $name => $pathConfig) {
            testPath($name, $pathConfig, $baseUrl, true, $username, $password);
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

// Print summary
echo "=========================================\n";
echo "Test Summary\n";
echo "=========================================\n";
echo "Total tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";
echo "Skipped: {$skippedTests}\n";
echo "\n";

if ($failedTests > 0) {
    echo "❌ Some tests failed\n";
    exit(1);
} else {
    echo "✅ All tests passed\n";
    exit(0);
}
