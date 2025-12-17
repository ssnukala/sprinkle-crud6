<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use UserFrosting\Sprinkle\CRUD6\Tests\CRUD6TestCase;

/**
 * Test ConfigAction controller.
 * 
 * Verifies that the /api/crud6/config endpoint returns the correct
 * debug_mode configuration from the backend.
 */
class ConfigActionTest extends CRUD6TestCase
{
    /**
     * Test that config endpoint returns debug_mode from configuration.
     */
    public function testConfigEndpointReturnsDebugMode(): void
    {
        // Create request to config endpoint
        $request = $this->createJsonRequest('GET', '/api/crud6/config');
        
        // Execute request
        $response = $this->handleRequest($request);
        
        // Assert successful response
        $this->assertResponseStatus(200, $response);
        
        // Get response data
        $data = $this->getJsonResponse($response);
        
        // Assert debug_mode key exists
        $this->assertArrayHasKey('debug_mode', $data);
        
        // Assert debug_mode is boolean
        $this->assertIsBool($data['debug_mode']);
        
        // In default config, debug_mode should be false
        $this->assertFalse($data['debug_mode']);
    }
    
    /**
     * Test that config endpoint returns true when debug_mode is enabled.
     */
    public function testConfigEndpointReturnsDebugModeWhenEnabled(): void
    {
        // Set debug_mode to true in config
        $this->ci->get('config')->set('crud6.debug_mode', true);
        
        // Create request to config endpoint
        $request = $this->createJsonRequest('GET', '/api/crud6/config');
        
        // Execute request
        $response = $this->handleRequest($request);
        
        // Assert successful response
        $this->assertResponseStatus(200, $response);
        
        // Get response data
        $data = $this->getJsonResponse($response);
        
        // Assert debug_mode is true
        $this->assertArrayHasKey('debug_mode', $data);
        $this->assertTrue($data['debug_mode']);
    }
    
    /**
     * Test that config endpoint is accessible without authentication.
     */
    public function testConfigEndpointIsPublic(): void
    {
        // Create request without authentication
        $request = $this->createJsonRequest('GET', '/api/crud6/config');
        
        // Execute request (should succeed even without auth)
        $response = $this->handleRequest($request);
        
        // Assert successful response (not 401 or 403)
        $this->assertResponseStatus(200, $response);
    }
}
