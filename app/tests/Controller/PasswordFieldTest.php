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

use Illuminate\Database\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use UserFrosting\Config\Config;
use UserFrosting\Fortress\Transformer\RequestDataTransformer;
use UserFrosting\Fortress\Validator\ServerSideValidator;
use UserFrosting\I18n\Translator;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authenticate\Hasher;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Account\Log\UserActivityLogger;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction;
use UserFrosting\Sprinkle\CRUD6\Controller\EditAction;
use UserFrosting\Sprinkle\CRUD6\Controller\UpdateFieldAction;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

/**
 * Password Field Test
 *
 * Tests the password field type hashing functionality in CreateAction and EditAction.
 */
class PasswordFieldTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Test that password fields are properly hashed in CreateAction
     */
    public function testPasswordFieldHashingInCreateAction(): void
    {
        // Create a mock Hasher
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('hash')
            ->once()
            ->with('plaintext_password')
            ->andReturn('$2y$10$hashed_password_string');

        // Create the controller with mocked dependencies
        $controller = new CreateAction(
            Mockery::mock(AuthorizationManager::class),
            Mockery::mock(Authenticator::class),
            Mockery::mock(DebugLoggerInterface::class),
            Mockery::mock(SchemaService::class),
            Mockery::mock(Config::class),
            Mockery::mock(Translator::class),
            Mockery::mock(Connection::class),
            Mockery::mock(UserActivityLogger::class),
            Mockery::mock(RequestDataTransformer::class),
            Mockery::mock(ServerSideValidator::class),
            $hasher
        );

        // Schema with password field
        $schema = [
            'model' => 'users',
            'fields' => [
                'username' => [
                    'type' => 'string',
                ],
                'password' => [
                    'type' => 'password',
                ],
            ],
        ];

        // Test data with plain text password
        $data = [
            'username' => 'testuser',
            'password' => 'plaintext_password',
        ];

        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('hashPasswordFields');
        $method->setAccessible(true);

        // Call the method and get the result
        $hashedData = $method->invoke($controller, $schema, $data);

        // Assert that the password was hashed
        $this->assertArrayHasKey('password', $hashedData);
        $this->assertEquals('$2y$10$hashed_password_string', $hashedData['password']);
        $this->assertEquals('testuser', $hashedData['username']);
    }

    /**
     * Test that empty password fields are not hashed in CreateAction
     */
    public function testEmptyPasswordFieldNotHashedInCreateAction(): void
    {
        // Create a mock Hasher that should NOT be called
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldNotReceive('hash');

        // Create the controller with mocked dependencies
        $controller = new CreateAction(
            Mockery::mock(AuthorizationManager::class),
            Mockery::mock(Authenticator::class),
            Mockery::mock(DebugLoggerInterface::class),
            Mockery::mock(SchemaService::class),
            Mockery::mock(Config::class),
            Mockery::mock(Translator::class),
            Mockery::mock(Connection::class),
            Mockery::mock(UserActivityLogger::class),
            Mockery::mock(RequestDataTransformer::class),
            Mockery::mock(ServerSideValidator::class),
            $hasher
        );

        // Schema with password field
        $schema = [
            'model' => 'users',
            'fields' => [
                'username' => [
                    'type' => 'string',
                ],
                'password' => [
                    'type' => 'password',
                ],
            ],
        ];

        // Test data with empty password
        $data = [
            'username' => 'testuser',
            'password' => '',
        ];

        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('hashPasswordFields');
        $method->setAccessible(true);

        // Call the method and get the result
        $hashedData = $method->invoke($controller, $schema, $data);

        // Assert that the password was not modified
        $this->assertArrayHasKey('password', $hashedData);
        $this->assertEquals('', $hashedData['password']);
    }

    /**
     * Test that password fields are properly hashed in EditAction
     */
    public function testPasswordFieldHashingInEditAction(): void
    {
        // Create a mock Hasher
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('hash')
            ->once()
            ->with('new_password')
            ->andReturn('$2y$10$hashed_new_password');

        // Create the controller with mocked dependencies
        $controller = new EditAction(
            Mockery::mock(AuthorizationManager::class),
            Mockery::mock(Authenticator::class),
            Mockery::mock(DebugLoggerInterface::class),
            Mockery::mock(SchemaService::class),
            Mockery::mock(Config::class),
            Mockery::mock(Translator::class),
            Mockery::mock(Connection::class),
            Mockery::mock(UserActivityLogger::class),
            Mockery::mock(RequestDataTransformer::class),
            Mockery::mock(ServerSideValidator::class),
            $hasher
        );

        // Schema with password field
        $schema = [
            'model' => 'users',
            'fields' => [
                'username' => [
                    'type' => 'string',
                ],
                'password' => [
                    'type' => 'password',
                ],
            ],
        ];

        // Test data for password update
        $data = [
            'password' => 'new_password',
        ];

        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('hashPasswordFields');
        $method->setAccessible(true);

        // Call the method and get the result
        $hashedData = $method->invoke($controller, $schema, $data);

        // Assert that the password was hashed
        $this->assertArrayHasKey('password', $hashedData);
        $this->assertEquals('$2y$10$hashed_new_password', $hashedData['password']);
    }

    /**
     * Test that non-password fields are not affected
     */
    public function testNonPasswordFieldsNotAffected(): void
    {
        // Create a mock Hasher that should NOT be called
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldNotReceive('hash');

        // Create the controller with mocked dependencies
        $controller = new CreateAction(
            Mockery::mock(AuthorizationManager::class),
            Mockery::mock(Authenticator::class),
            Mockery::mock(DebugLoggerInterface::class),
            Mockery::mock(SchemaService::class),
            Mockery::mock(Config::class),
            Mockery::mock(Translator::class),
            Mockery::mock(Connection::class),
            Mockery::mock(UserActivityLogger::class),
            Mockery::mock(RequestDataTransformer::class),
            Mockery::mock(ServerSideValidator::class),
            $hasher
        );

        // Schema without password field
        $schema = [
            'model' => 'products',
            'fields' => [
                'name' => [
                    'type' => 'string',
                ],
                'price' => [
                    'type' => 'decimal',
                ],
            ],
        ];

        // Test data without password
        $data = [
            'name' => 'Product Name',
            'price' => '19.99',
        ];

        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('hashPasswordFields');
        $method->setAccessible(true);

        // Call the method and get the result
        $hashedData = $method->invoke($controller, $schema, $data);

        // Assert that data was not modified
        $this->assertEquals($data, $hashedData);
    }

    /**
     * Test that password fields are properly hashed in UpdateFieldAction
     * 
     * Note: UpdateFieldAction doesn't use hashPasswordFields() method.
     * It directly hashes password fields inline during field update.
     * This test verifies that the Hasher is injected and available for use.
     */
    public function testUpdateFieldActionHasHasher(): void
    {
        // Create a mock Hasher
        $hasher = Mockery::mock(Hasher::class);

        // Create the controller with mocked dependencies
        $controller = new UpdateFieldAction(
            Mockery::mock(AuthorizationManager::class),
            Mockery::mock(Authenticator::class),
            Mockery::mock(DebugLoggerInterface::class),
            Mockery::mock(SchemaService::class),
            Mockery::mock(Config::class),
            Mockery::mock(Translator::class),
            Mockery::mock(UserActivityLogger::class),
            Mockery::mock(Connection::class),
            $hasher
        );

        // Use reflection to verify the hasher property exists and is set
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('hasher');
        $property->setAccessible(true);
        
        // Assert that the hasher is the same instance we injected
        $this->assertSame($hasher, $property->getValue($controller));
    }
}

