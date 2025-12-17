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
 * This is a standalone version that doesn't require composer autoload
 * to work in CI environments.
 * 
 * Usage:
 *   php scripts/generate-test-schemas.php
 * 
 * Or make executable and run:
 *   chmod +x scripts/generate-test-schemas.php
 *   ./scripts/generate-test-schemas.php
 */

// Load helper classes directly
require_once __DIR__ . '/SchemaBuilder.php';
require_once __DIR__ . '/GenerateSchemas.php';

// Generate schemas and translations
GenerateSchemas::generate();
