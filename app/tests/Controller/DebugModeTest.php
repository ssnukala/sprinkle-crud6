<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\Base;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Debug Mode Test
 *
 * Tests the debug_mode configuration and debugLog() helper functionality.
 */
class DebugModeTest extends TestCase
{
    /**
     * Test debugLog does not log when debug_mode is false
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

        // Create controller instance
        $controller = new class(
            $this->createMock(AuthorizationManager::class),
            $this->createMock(Authenticator::class),
            $logger,
            $this->createMock(SchemaService::class),
            $config
        ) extends Base {
            public function __invoke(
                array $crudSchema,
                CRUD6ModelInterface $crudModel,
                ServerRequestInterface $request,
                ResponseInterface $response
            ): ResponseInterface {
                // Call debugLog - should not trigger logger
                $this->debugLog("Test message", ['test' => 'data']);
                return $response;
            }

            // Expose protected method for testing
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog doesn't call logger when debug_mode is false
        $controller->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test debugLog logs when debug_mode is true
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

        // Create controller instance
        $controller = new class(
            $this->createMock(AuthorizationManager::class),
            $this->createMock(Authenticator::class),
            $logger,
            $this->createMock(SchemaService::class),
            $config
        ) extends Base {
            public function __invoke(
                array $crudSchema,
                CRUD6ModelInterface $crudModel,
                ServerRequestInterface $request,
                ResponseInterface $response
            ): ResponseInterface {
                return $response;
            }

            // Expose protected method for testing
            public function testDebugLog(string $message, array $context = []): void
            {
                $this->debugLog($message, $context);
            }
        };

        // Test that debugLog calls logger when debug_mode is true
        $controller->testDebugLog("Test message", ['test' => 'data']);
    }

    /**
     * Test isDebugMode returns correct value
     */
    public function testIsDebugModeReturnsFalse(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(false);

        $controller = new class(
            $this->createMock(AuthorizationManager::class),
            $this->createMock(Authenticator::class),
            $this->createMock(DebugLoggerInterface::class),
            $this->createMock(SchemaService::class),
            $config
        ) extends Base {
            public function __invoke(
                array $crudSchema,
                CRUD6ModelInterface $crudModel,
                ServerRequestInterface $request,
                ResponseInterface $response
            ): ResponseInterface {
                return $response;
            }

            public function testIsDebugMode(): bool
            {
                return $this->isDebugMode();
            }
        };

        $this->assertFalse($controller->testIsDebugMode());
    }

    /**
     * Test isDebugMode returns correct value when enabled
     */
    public function testIsDebugModeReturnsTrue(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('crud6.debug_mode', false)
            ->willReturn(true);

        $controller = new class(
            $this->createMock(AuthorizationManager::class),
            $this->createMock(Authenticator::class),
            $this->createMock(DebugLoggerInterface::class),
            $this->createMock(SchemaService::class),
            $config
        ) extends Base {
            public function __invoke(
                array $crudSchema,
                CRUD6ModelInterface $crudModel,
                ServerRequestInterface $request,
                ResponseInterface $response
            ): ResponseInterface {
                return $response;
            }

            public function testIsDebugMode(): bool
            {
                return $this->isDebugMode();
            }
        };

        $this->assertTrue($controller->testIsDebugMode());
    }
}
