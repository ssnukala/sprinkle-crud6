<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * PHPUnit Bootstrap File with Debug Logging
 * 
 * This bootstrap file is loaded before tests run and provides:
 * 1. Standard Composer autoloader
 * 2. Debug logging of database configuration
 * 3. Early detection of configuration issues
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Log PHPUnit bootstrap information
fwrite(STDERR, "\n" . str_repeat('=', 70) . "\n");
fwrite(STDERR, "PHPUNIT BOOTSTRAP - DATABASE CONFIGURATION CHECK\n");
fwrite(STDERR, str_repeat('=', 70) . "\n");
fwrite(STDERR, "Timestamp: " . date('Y-m-d H:i:s') . "\n");
fwrite(STDERR, "PHP Version: " . PHP_VERSION . "\n");
fwrite(STDERR, "PHPUnit Bootstrap File: " . __FILE__ . "\n");
fwrite(STDERR, str_repeat('-', 70) . "\n");

// Check and log database environment variables
$dbConfig = [
    'DB_DRIVER' => getenv('DB_DRIVER') ?: $_ENV['DB_DRIVER'] ?? 'NOT SET',
    'DB_HOST' => getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'NOT SET',
    'DB_PORT' => getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? 'NOT SET',
    'DB_NAME' => getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'NOT SET',
    'DB_USER' => getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'NOT SET',
    'DB_PASSWORD' => (getenv('DB_PASSWORD') !== false || isset($_ENV['DB_PASSWORD'])) ? '***SET***' : 'NOT SET',
    'UF_MODE' => getenv('UF_MODE') ?: $_ENV['UF_MODE'] ?? 'NOT SET',
];

fwrite(STDERR, "DATABASE CONFIGURATION:\n");
foreach ($dbConfig as $key => $value) {
    fwrite(STDERR, sprintf("  %-15s = %s\n", $key, $value));
}
fwrite(STDERR, str_repeat('-', 70) . "\n");

// Validate critical configuration
$errors = [];
if ($dbConfig['DB_NAME'] === 'NOT SET' || empty($dbConfig['DB_NAME'])) {
    $errors[] = 'DB_NAME is not set - this will cause "table_schema = \'\'" SQL errors';
}
if ($dbConfig['DB_HOST'] === 'NOT SET') {
    $errors[] = 'DB_HOST is not set - database connection will fail';
}
if ($dbConfig['DB_USER'] === 'NOT SET') {
    $errors[] = 'DB_USER is not set - database connection will fail';
}
if ($dbConfig['DB_DRIVER'] === 'NOT SET') {
    $errors[] = 'DB_DRIVER is not set - database connection will fail';
}

if (!empty($errors)) {
    fwrite(STDERR, "\n❌ CONFIGURATION ERRORS DETECTED:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, "  - {$error}\n");
    }
    fwrite(STDERR, "\nPlease check that phpunit.xml has a <php> section with <env> values.\n");
    fwrite(STDERR, "Example:\n");
    fwrite(STDERR, "  <php>\n");
    fwrite(STDERR, "    <env name=\"DB_NAME\" value=\"userfrosting_test\"/>\n");
    fwrite(STDERR, "    <env name=\"DB_HOST\" value=\"127.0.0.1\"/>\n");
    fwrite(STDERR, "    ...\n");
    fwrite(STDERR, "  </php>\n");
    fwrite(STDERR, str_repeat('=', 70) . "\n\n");
    
    // Exit with error to prevent running tests with bad configuration
    fwrite(STDERR, "CRITICAL: Cannot run tests with invalid database configuration.\n");
    fwrite(STDERR, "Fix phpunit.xml and try again.\n\n");
    exit(1);
} else {
    fwrite(STDERR, "\n✅ DATABASE CONFIGURATION VALID\n");
    fwrite(STDERR, "   All required environment variables are set.\n");
    fwrite(STDERR, "   Database: {$dbConfig['DB_NAME']}\n");
    fwrite(STDERR, "   Host: {$dbConfig['DB_HOST']}\n");
    fwrite(STDERR, "   Driver: {$dbConfig['DB_DRIVER']}\n");
}

fwrite(STDERR, str_repeat('=', 70) . "\n\n");
