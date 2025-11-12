# Manual Pivot Table Management - Quick Guide

## Current Solution (Without Schema Actions)

Until the full relationship actions feature is implemented, you can manually manage pivot table entries by extending the CreateAction controller for specific models.

## Approach 1: Custom Controller Extension

Create a custom controller that extends CreateAction and adds pivot table management:

### 1. Create Custom User Create Action

```php
<?php

declare(strict_types=1);

namespace App\Controller\User;

use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction as BaseCreateAction;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Custom User Create Action with Default Role Assignment
 */
class UserCreateAction extends BaseCreateAction
{
    /**
     * Override handle method to add default role after user creation
     */
    protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
    {
        // Call parent method to create user
        $user = parent::handle($crudModel, $schema, $request);
        
        // Assign default role (role_id = 2 for 'User' role)
        $this->assignDefaultRole($user, 2);
        
        return $user;
    }
    
    /**
     * Assign a default role to the newly created user
     */
    protected function assignDefaultRole(CRUD6ModelInterface $user, int $roleId): void
    {
        $this->db->table('role_users')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->debugLog("Assigned default role to user", [
            'user_id' => $user->id,
            'role_id' => $roleId,
        ]);
    }
}
```

### 2. Register Custom Route

In your custom sprinkle's route definition, override the user create endpoint:

```php
<?php

namespace App\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use App\Controller\User\UserCreateAction;

class CustomUserRoutes
{
    public function register(App $app): void
    {
        $app->group('/api/crud6', function (RouteCollectorProxy $group) {
            // Override default user create endpoint
            $group->post('/users', UserCreateAction::class)
                ->setName('api.crud6.users.create');
        })->add(AuthGuard::class)->add(NoCache::class);
    }
}
```

## Approach 2: Event Listener (Future)

Once UserFrosting implements model events, you could use an event listener:

```php
<?php

namespace App\Listeners;

use UserFrosting\Sprinkle\Account\Database\Models\User;
use Illuminate\Database\Connection;

class AssignDefaultRoleListener
{
    public function __construct(protected Connection $db)
    {
    }
    
    public function handle(User $user): void
    {
        // Assign default role when user is created
        $this->db->table('role_users')->insert([
            'user_id' => $user->id,
            'role_id' => 2, // Default 'User' role
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

## Approach 3: Using Eloquent Relationship Methods

If you have access to the model instance, use Eloquent's built-in relationship methods:

```php
<?php

// In your custom create action or service
protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
{
    // Create user (existing code)
    $user = parent::handle($crudModel, $schema, $request);
    
    // Use Eloquent's attach method
    // This assumes the relationship is properly defined in the CRUD6Model
    $user->roles()->attach(2, [
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    return $user;
}
```

## Approach 4: Database Trigger (Not Recommended)

You could use a database trigger, but this is less maintainable:

```sql
-- MySQL trigger example
DELIMITER $$
CREATE TRIGGER assign_default_role_after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO role_users (user_id, role_id, created_at, updated_at)
    VALUES (NEW.id, 2, NOW(), NOW());
END$$
DELIMITER ;
```

**Note**: Database triggers are harder to maintain and test. Prefer application-level solutions.

## Recommended Approach

For now, use **Approach 1** (Custom Controller Extension) as it:

1. ✅ Maintains separation of concerns
2. ✅ Works with current CRUD6 architecture
3. ✅ Easy to test and debug
4. ✅ Clear and maintainable
5. ✅ Can be easily migrated to schema-based actions when implemented

## Example: Complete Implementation

### File: `app/src/Controller/User/UserCreateAction.php`

```php
<?php

declare(strict_types=1);

namespace App\Controller\User;

use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserCreateAction extends CreateAction
{
    /**
     * Default role ID to assign to new users
     */
    protected const DEFAULT_ROLE_ID = 2;
    
    protected function handle(CRUD6ModelInterface $crudModel, array $schema, Request $request): CRUD6ModelInterface
    {
        // Create user using parent implementation
        $user = parent::handle($crudModel, $schema, $request);
        
        // Assign default role in same transaction
        $this->assignDefaultRole($user);
        
        $this->debugLog("User created with default role assignment", [
            'user_id' => $user->id,
            'role_id' => self::DEFAULT_ROLE_ID,
        ]);
        
        return $user;
    }
    
    protected function assignDefaultRole(CRUD6ModelInterface $user): void
    {
        $this->db->table('role_users')->insert([
            'user_id' => $user->id,
            'role_id' => self::DEFAULT_ROLE_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

### File: `app/src/Routes/CustomUserRoutes.php`

```php
<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controller\User\UserCreateAction;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;

class CustomUserRoutes
{
    public function register(App $app): void
    {
        $app->group('/api/crud6', function (RouteCollectorProxy $group) {
            $group->post('/users', UserCreateAction::class)
                ->setName('api.crud6.users.create');
        })->add(AuthGuard::class)->add(NoCache::class);
    }
}
```

### File: `app/src/MyAppSprinkle.php`

```php
<?php

namespace App;

use App\Routes\CustomUserRoutes;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Theme\AdminLTE\AdminLTE;

class MyAppSprinkle implements SprinkleRecipe
{
    public function getRoutes(): array
    {
        return [
            CustomUserRoutes::class,
        ];
    }
    
    // ... other methods
}
```

## Testing

Create a test to verify default role assignment:

```php
<?php

namespace App\Tests\Integration\User;

use UserFrosting\Sprinkle\Account\Tests\AccountTestCase;

class UserCreateWithRoleTest extends AccountTestCase
{
    public function testUserCreatedWithDefaultRole(): void
    {
        // Arrange
        $userData = [
            'user_name' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'securePassword123',
        ];
        
        // Act
        $response = $this->post('/api/crud6/users', $userData);
        
        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        
        // Verify role was assigned
        $user = User::where('user_name', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles()->where('role_id', 2)->exists());
    }
}
```

## Migration to Schema-Based Actions

When the schema-based relationship actions feature is implemented, you can simply:

1. Remove the custom UserCreateAction
2. Add relationship actions to your schema:

```json
{
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "role_users",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "actions": {
        "on_create": {
          "attach": [{"related_id": 2}]
        }
      }
    }
  ]
}
```

3. Remove the custom route registration
4. The default CRUD6 routes will handle everything

This provides a clear upgrade path with minimal code changes.
