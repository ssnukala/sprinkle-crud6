<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Debug Mode Integration Test
 *
 * Documents how to use the debug_mode configuration option.
 */
class DebugModeIntegrationTest extends TestCase
{
    /**
     * Test that debug_mode defaults to false
     */
    public function testDebugModeDefaultsToFalse(): void
    {
        // Load the actual config file
        $configFile = __DIR__ . '/../../../config/default.php';
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            
            $this->assertIsArray($config);
            $this->assertArrayHasKey('crud6', $config);
            $this->assertArrayHasKey('debug_mode', $config['crud6']);
            $this->assertIsBool($config['crud6']['debug_mode']);
            $this->assertFalse($config['crud6']['debug_mode'], 
                'debug_mode should default to false for production safety');
        } else {
            $this->markTestSkipped('Config file not found');
        }
    }
}
