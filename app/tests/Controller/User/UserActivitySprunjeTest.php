<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller\User;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class UserActivitySprunjeTest extends AdminTestCase
{
    use RefreshDatabase;
    use WithTestUser;
    use MockeryPHPUnitIntegration;

    /**
     * Setup test database for controller tests
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    public function testPageForGuestUser(): void
    {
        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/users/u/foo/activities');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    public function testPageForNotUser(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/users/u/foo/activities');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse([
            'title'       => 'Account Not Found',
            'description' => 'This account does not exist. It may have been deleted.',
            'status'      => 404,
        ], $response);
        $this->assertResponseStatus(404, $response);
    }

    public function testPageForForbiddenException(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/users/u/' . $user->user_name . '/activities');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    public function testPage(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['view_user_activities']);

        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/api/users/u/' . $user->user_name . '/activities');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertResponseStatus(200, $response);
        $this->assertNotEmpty((string) $response->getBody());
    }
}
