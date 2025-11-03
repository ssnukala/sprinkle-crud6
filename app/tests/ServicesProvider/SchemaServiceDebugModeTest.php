<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\ServicesProvider;

use PHPUnit\Framework\TestCase;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * SchemaService Debug Mode Test
 *
 * Tests the SchemaService debug logging respects debug_mode configuration.
 */
class SchemaServiceDebugModeTest extends TestCase
{
    /**
     * Test SchemaService debugLog does not log when debug_mode is false
     */
    public function testDebugLogDoesNotLogWhenDebugModeDisabled(): void
    {
        // Create mock config with debug_mode = false
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(false);

        // Create mock logger that should NOT be called
        $logger = $this->createMock(DebugLoggerInterface::class);
        $logger->expects($this->never())
            ->method('debug');

        // Create SchemaService with logger
        $service = new class(
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            $logger
        ) extends SchemaService {
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog doesn't call logger when debug_mode is false
        $service->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test SchemaService debugLog logs when debug_mode is true
     */
    public function testDebugLogLogsWhenDebugModeEnabled(): void
    {
        // Create mock config with debug_mode = true
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(true);

        // Create mock logger that SHOULD be called
        $logger = $this->createMock(DebugLoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Test message', ['test' => 'data']);

        // Create SchemaService with logger
        $service = new class(
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            $logger
        ) extends SchemaService {
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog calls logger when debug_mode is true
        $service->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test SchemaService debugLog falls back to error_log when logger is null
     */
    public function testDebugLogFallsBackToErrorLogWhenLoggerIsNull(): void
    {
        // Create mock config with debug_mode = true
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(true);

        // Create SchemaService without logger (null)
        $service = new class(
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            null  // No logger
        ) extends SchemaService {
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Capture error_log output
        // Note: This is tricky to test in unit tests, so we just verify it doesn't throw an exception
        $this->expectNotToPerformAssertions();
        $service->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test SchemaService isDebugMode returns correct value
     */
    public function testIsDebugModeReturnsFalse(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(false);

        $service = new class(
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            null
        ) extends SchemaService {
            public function testIsDebugMode(): bool
            {
                return $this->isDebugMode();
            }
        };

        $this->assertFalse($service->testIsDebugMode());
    }

    /**
     * Test SchemaService isDebugMode returns true when enabled
     */
    public function testIsDebugModeReturnsTrue(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(true);

        $service = new class(
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            null
        ) extends SchemaService {
            public function testIsDebugMode(): bool
            {
                return $this->isDebugMode();
            }
        };

        $this->assertTrue($service->testIsDebugMode());
    }
}
