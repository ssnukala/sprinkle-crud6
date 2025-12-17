#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Generate Test Schemas and Translations Script
 * 
 * This script generates schema JSON files and locale translations
 * for CRUD6 testing using the SchemaBuilder helper.
 * 
 * Usage:
 *   php generate-test-schemas.php
 * 
 * Or make executable and run:
 *   chmod +x generate-test-schemas.php
 *   ./generate-test-schemas.php
 */

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

use UserFrosting\Sprinkle\CRUD6\Testing\GenerateSchemas;

// Generate schemas and translations
GenerateSchemas::generate();

echo "\nSchema files: app/schema/crud6/\n";
echo "Translations: app/locale/en_US/messages.php\n";
