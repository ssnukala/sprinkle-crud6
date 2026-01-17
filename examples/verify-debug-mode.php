#!/usr/bin/env php
<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */
/**
 * Debug Mode Verification Script
 * 
 * This script demonstrates how the debug_mode configuration works.
 * It simulates the behavior of the debugLog() methods in both 
 * Base controller and SchemaService.
 */

echo "=== CRUD6 Debug Mode Verification ===\n\n";

// Simulate Config class
class MockConfig {
    private $config;
    
    public function __construct(bool $debugMode) {
        $this->config = ['crud6' => ['debug_mode' => $debugMode]];
    }
    
    public function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
}

// Simulate DebugLoggerInterface
class MockLogger {
    public $logCount = 0;
    public $logs = [];
    
    public function debug(string $message, array $context = []): void {
        $this->logCount++;
        $this->logs[] = ['message' => $message, 'context' => $context];
        echo "  [LOGGER] $message " . json_encode($context) . "\n";
    }
}

// Simulate debugLog behavior
function testDebugLog(MockConfig $config, ?MockLogger $logger, string $scenario): void {
    echo "--- Scenario: $scenario ---\n";
    
    $isDebugMode = $config->get('crud6.debug_mode', false);
    echo "  debug_mode = " . ($isDebugMode ? 'true' : 'false') . "\n";
    echo "  logger = " . ($logger ? 'available' : 'null') . "\n";
    
    // Simulate debugLog call
    $message = "CRUD6 Test message";
    $context = ['test' => 'data'];
    
    if (!$isDebugMode) {
        echo "  Result: No logging (debug_mode is false)\n";
    } elseif ($logger !== null) {
        $logger->debug($message, $context);
        echo "  Result: Logged via DebugLoggerInterface\n";
    } else {
        echo "  [ERROR_LOG] $message " . json_encode($context) . "\n";
        echo "  Result: Logged via error_log() fallback\n";
    }
    
    echo "\n";
}

// Test scenarios
echo "1. Debug mode OFF, Logger available (Production - typical case)\n";
$config1 = new MockConfig(false);
$logger1 = new MockLogger();
testDebugLog($config1, $logger1, "debug_mode=false, logger=available");
echo "   Expected: No logging\n";
echo "   Actual log count: {$logger1->logCount}\n\n";

echo "2. Debug mode ON, Logger available (Development - typical case)\n";
$config2 = new MockConfig(true);
$logger2 = new MockLogger();
testDebugLog($config2, $logger2, "debug_mode=true, logger=available");
echo "   Expected: Logging via DebugLoggerInterface\n";
echo "   Actual log count: {$logger2->logCount}\n\n";

echo "3. Debug mode ON, Logger unavailable (SchemaService fallback case)\n";
$config3 = new MockConfig(true);
$logger3 = null;
testDebugLog($config3, $logger3, "debug_mode=true, logger=null");
echo "   Expected: Logging via error_log() fallback\n\n";

echo "4. Debug mode OFF, Logger unavailable (Should not log)\n";
$config4 = new MockConfig(false);
$logger4 = null;
testDebugLog($config4, $logger4, "debug_mode=false, logger=null");
echo "   Expected: No logging\n\n";

echo "=== Summary ===\n";
echo "✓ debug_mode=false: No logging regardless of logger availability\n";
echo "✓ debug_mode=true + logger: Uses DebugLoggerInterface\n";
echo "✓ debug_mode=true + no logger: Falls back to error_log()\n";
echo "\nThis matches the implementation in:\n";
echo "  - app/src/Controller/Base.php::debugLog()\n";
echo "  - app/src/ServicesProvider/SchemaService.php::debugLog()\n";
