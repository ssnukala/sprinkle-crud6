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
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaNormalizer;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaCache;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaTranslator;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaActionManager;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * SchemaService Debug Mode Test
 *
 * Tests the SchemaService debug logging respects debug_mode configuration.
 */
class SchemaServiceDebugModeTest extends TestCase
{
    /**
     * Create mock dependencies for SchemaService
     */
    private function createMockDependencies(Config $config, ?DebugLoggerInterface $logger): array
    {
        return [
            $this->createMock(ResourceLocatorInterface::class),
            $config,
            $logger,
            $this->createMock(Translator::class),
            $this->createMock(SchemaLoader::class),
            $this->createMock(SchemaValidator::class),
            $this->createMock(SchemaNormalizer::class),
            $this->createMock(SchemaCache::class),
            $this->createMock(SchemaFilter::class),
            $this->createMock(SchemaTranslator::class),
            $this->createMock(SchemaActionManager::class),
        ];
    }
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

        // Create SchemaService with all required dependencies
        $deps = $this->createMockDependencies($config, $logger);
        $service = new class(...$deps) extends SchemaService {
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

        // Create SchemaService with all required dependencies
        $deps = $this->createMockDependencies($config, $logger);
        $service = new class(...$deps) extends SchemaService {
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog calls logger when debug_mode is true
        $service->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test SchemaService debugLog handles logger properly when debug mode is enabled
     * 
     * Note: SchemaService requires a valid DebugLoggerInterface - null is not allowed.
     * This test verifies the logger is called when debug mode is enabled.
     */
    public function testDebugLogCallsLoggerWhenDebugModeEnabled(): void
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

        // Create SchemaService with all required dependencies
        $deps = $this->createMockDependencies($config, $logger);
        $service = new class(...$deps) extends SchemaService {
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog calls logger when debug_mode is true
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

        // Create mock logger (required, cannot be null)
        $logger = $this->createMock(DebugLoggerInterface::class);

        $deps = $this->createMockDependencies($config, $logger);
        $service = new class(...$deps) extends SchemaService {
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

        // Create mock logger (required, cannot be null)
        $logger = $this->createMock(DebugLoggerInterface::class);

        $deps = $this->createMockDependencies($config, $logger);
        $service = new class(...$deps) extends SchemaService {
            public function testIsDebugMode(): bool
            {
                return $this->isDebugMode();
            }
        };

        $this->assertTrue($service->testIsDebugMode());
    }
}
