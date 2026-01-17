<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Config\Config;

/**
 * Config endpoint action for CRUD6.
 * 
 * Returns public configuration settings for the frontend.
 * Currently exposes the debug_mode setting to allow frontend to sync with backend.
 * 
 * Route: GET /api/crud6/config
 */
class ConfigAction
{
    /**
     * Constructor for ConfigAction.
     * 
     * @param Config $config Configuration repository
     */
    public function __construct(
        protected Config $config,
    ) {
    }

    /**
     * Handle config request.
     * 
     * Returns public configuration settings for CRUD6.
     * 
     * @param ServerRequestInterface $request  HTTP request
     * @param ResponseInterface      $response HTTP response
     * 
     * @return ResponseInterface JSON response with config settings
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Get debug_mode from config (defaults to false if not set)
        $debugMode = $this->config->get('crud6.debug_mode', false);

        // Build response payload
        $payload = [
            'debug_mode' => $debugMode,
        ];

        // Return JSON response
        $response->getBody()->write((string) json_encode($payload));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
