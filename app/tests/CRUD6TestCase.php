<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests;

use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Sprinkle\CRUD6\Testing\WithDatabaseSeeds;
use UserFrosting\Testing\TestCase;

/**
 * CRUD6 Test Case Base Class.
 * 
 * Base test case with CRUD6 as main sprinkle.
 * All CRUD6 tests should extend this class to ensure proper sprinkle loading.
 * 
 * This base class includes WithDatabaseSeeds trait to ensure tests that use
 * RefreshDatabase also get necessary seed data automatically.
 * 
 * Follows UserFrosting 6 testing patterns from sprinkle-admin and sprinkle-account.
 * 
 * @see \UserFrosting\Sprinkle\Admin\Tests\AdminTestCase
 * @see \UserFrosting\Sprinkle\Account\Tests\AccountTestCase
 */
class CRUD6TestCase extends TestCase
{
    use WithDatabaseSeeds;

    /**
     * @var string Main sprinkle class for CRUD6 tests
     */
    protected string $mainSprinkle = CRUD6::class;

    /**
     * Get the name of the test.
     * 
     * @return string Test name
     */
    public function getName(): string
    {
        return static::class . '::' . $this->name();
    }

    /**
     * Get JSON response data from a PSR-7 response.
     * 
     * Helper method to decode JSON response body into an associative array.
     * 
     * @param \Psr\Http\Message\ResponseInterface $response The HTTP response
     * 
     * @return array The decoded JSON data
     * 
     * @throws \JsonException If JSON decoding fails
     */
    protected function getJsonResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Verify database connection is working correctly.
     * 
     * This method can be called in tests to verify the database
     * connection is established with the correct database name.
     * 
     * @return array Database connection information
     */
    protected function verifyDatabaseConnection(): array
    {
        try {
            // Get database connection from container if available
            if (method_exists($this, 'ci') && $this->ci->has('db')) {
                $db = $this->ci->get('db');
                
                $connectionInfo = [
                    'connected' => true,
                    'database' => $db->getDatabaseName(),
                    'driver' => $db->getDriverName(),
                    'host' => $db->getConfig('host'),
                ];

                fwrite(STDERR, "\n[DB CONNECTION VERIFIED]\n");
                fwrite(STDERR, "  Database: {$connectionInfo['database']}\n");
                fwrite(STDERR, "  Driver: {$connectionInfo['driver']}\n");
                fwrite(STDERR, "  Host: {$connectionInfo['host']}\n\n");

                return $connectionInfo;
            }
        } catch (\Exception $e) {
            fwrite(STDERR, "\n[DB CONNECTION ERROR]\n");
            fwrite(STDERR, "  Error: " . $e->getMessage() . "\n\n");
            
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }

        return ['connected' => false, 'reason' => 'Database service not available'];
    }
}
