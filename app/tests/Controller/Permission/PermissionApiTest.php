<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller\Permission;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\User;
use UserFrosting\Sprinkle\Account\Testing\WithTestUser;
use UserFrosting\Sprinkle\CRUD6\Tests\AdminTestCase;
use UserFrosting\Sprinkle\Core\Testing\RefreshDatabase;

class PermissionApiTest extends AdminTestCase
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
        $request = $this->createJsonRequest('GET', '/api/permissions/p/1');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse('Login Required', $response, 'title');
        $this->assertResponseStatus(401, $response);
    }

    public function testPageForForbiddenException(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user);

        /** @var Permission */
        $permission = Permission::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/permissions/p/' . $permission->id);
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse('Access Denied', $response, 'title');
        $this->assertResponseStatus(403, $response);
    }

    public function testPageForNotFound(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_permissions']);

        // Create request with method and url and fetch response
        $request = $this->createJsonRequest('GET', '/api/permissions/p/99');
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertJsonResponse('Permission not found', $response, 'description');
        $this->assertResponseStatus(404, $response);
    }

    public function testPage(): void
    {
        /** @var User */
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['uri_permissions']);

        /** @var Permission */
        $permission = Permission::factory()->create();

        // Create request with method and url and fetch response
        $request = $this->createRequest('GET', '/api/permissions/p/' . $permission->id);
        $response = $this->handleRequest($request);

        // Assert response status & body
        $this->assertNotEmpty((string) $response->getBody());

        // Assert response status & body
        $this->assertJsonStructure([
            'id',
            'slug',
            'name',
            'conditions',
            'description',
            'created_at',
            'updated_at',
        ], $response);
    }
}
