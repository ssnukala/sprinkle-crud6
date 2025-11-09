# Migration Guide: RegSevak (UF 4.6.7) to UserFrosting 6

## Overview

This guide provides a comprehensive roadmap for migrating the RegSevak registration application from UserFrosting 4.6.7 to UserFrosting 6 with the sprinkle-crud6 package.

## Migration Strategy

### Phased Approach

**Phase 1: Assessment and Planning** (1-2 weeks)
- Inventory all features and customizations
- Identify breaking changes
- Plan data migration strategy
- Set up development environment

**Phase 2: Infrastructure Migration** (2-3 weeks)
- Upgrade to PHP 8.1+
- Update dependencies
- Migrate database schema
- Set up UserFrosting 6 project

**Phase 3: Feature Migration** (4-6 weeks)
- Migrate models and schemas
- Update controllers to UF6 patterns
- Migrate views to Vue.js/Twig
- Update API endpoints

**Phase 4: Testing and Refinement** (2-3 weeks)
- Comprehensive testing
- Performance optimization
- Bug fixes
- User acceptance testing

**Phase 5: Deployment** (1 week)
- Production deployment
- Data migration
- User training
- Go-live support

## Prerequisites

### Current Environment (UF 4.6.7)
- PHP 7.x
- UserFrosting 4.6.7
- MySQL/PostgreSQL
- Apache/Nginx

### Target Environment (UF 6)
- PHP 8.1 or higher
- UserFrosting 6.0.4+
- MySQL/PostgreSQL (same)
- Apache/Nginx (same)
- Composer 2.x

## Breaking Changes from UF 4.6.7 to UF 6

### 1. PHP Version Requirement

**UF 4.6.7**: PHP 7.x
**UF 6**: PHP 8.1+

**Migration Steps**:
```bash
# Update PHP
sudo apt-get update
sudo apt-get install php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml

# Verify PHP version
php -v
```

### 2. Directory Structure Changes

**UF 4.6.7**:
```
app/
  sprinkles/
    RegSevak/
      src/
      templates/
      locale/
      config/
      assets/
```

**UF 6**:
```
app/
  src/
    RegSevak/         # Or your sprinkle name
      Controller/
      Database/
      ServicesProvider/
      Routes/
  templates/
  locale/
  config/
  assets/
    components/       # Vue components
    composables/
```

### 3. Namespace Changes

**UF 4.6.7**:
```php
namespace UserFrosting\Sprinkle\RegSevak\Controller;
```

**UF 6**:
```php
namespace UserFrosting\Sprinkle\RegSevak\Controller;
// Namespace structure is similar but controller patterns differ
```

### 4. Controller Pattern Changes

**UF 4.6.7** (Class with methods):
```php
class RegistrationController
{
    public function create($request, $response, $args)
    {
        // Logic here
    }
    
    public function list($request, $response, $args)
    {
        // Logic here
    }
}
```

**UF 6** (Action classes):
```php
class CreateAction
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Logic here
    }
}

class ListAction
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Logic here
    }
}
```

### 5. Dependency Injection Changes

**UF 4.6.7** (Pimple container):
```php
$this->ci[RegistrationService::class] = function ($c) {
    return new RegistrationService($c);
};
```

**UF 6** (PHP-DI):
```php
use DI\Autowire;

class RegSevakServicesProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            RegistrationService::class => autowire(),
        ];
    }
}
```

### 6. Middleware Changes

**UF 4.6.7** (Slim 3 middleware):
```php
$app->add(new AuthGuard($ci));
```

**UF 6** (PSR-15 middleware):
```php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthGuard implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        // Middleware logic
        return $handler->handle($request);
    }
}
```

## Migrating to sprinkle-crud6

### Advantages of Using sprinkle-crud6

1. **Schema-Driven Development**
   - Define models with JSON schemas
   - Automatic CRUD endpoints
   - Built-in validation

2. **Generic Model System**
   - No need for custom model classes
   - Dynamic configuration
   - Reduced boilerplate

3. **Vue.js Frontend**
   - Modern UI components
   - Better user experience
   - Reactive data handling

4. **RESTful API**
   - Standard endpoints
   - Consistent patterns
   - Easy integration

### Migration Steps

#### Step 1: Create JSON Schemas

Convert your RegSevak models to JSON schemas:

**Old (UF 4.6.7) - Eloquent Model**:
```php
class Registration extends Model
{
    protected $table = 'registrations';
    
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'status'
    ];
    
    protected $casts = [
        'user_id' => 'integer'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

**New (UF 6 with CRUD6) - JSON Schema**:
```json
{
  "model": "registrations",
  "table": "registrations",
  "title": "Registration Management",
  "primary_key": "id",
  "timestamps": true,
  "permissions": {
    "read": "uri_registrations",
    "create": "create_registration",
    "update": "update_registration",
    "delete": "delete_registration"
  },
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "auto_increment": true,
      "listable": true,
      "sortable": true
    },
    "user_id": {
      "type": "integer",
      "label": "User ID",
      "required": true,
      "listable": false
    },
    "name": {
      "type": "string",
      "label": "Name",
      "required": true,
      "listable": true,
      "sortable": true,
      "filterable": true,
      "validation": {
        "length": {
          "min": 3,
          "max": 100
        }
      }
    },
    "email": {
      "type": "string",
      "label": "Email",
      "required": true,
      "listable": true,
      "filterable": true,
      "validation": {
        "email": true
      }
    },
    "phone": {
      "type": "string",
      "label": "Phone",
      "required": true,
      "listable": true
    },
    "address": {
      "type": "text",
      "label": "Address",
      "listable": false,
      "viewable": true
    },
    "status": {
      "type": "string",
      "label": "Status",
      "default": "pending",
      "listable": true,
      "sortable": true,
      "filterable": true
    },
    "created_at": {
      "type": "datetime",
      "label": "Submitted",
      "listable": true,
      "sortable": true
    },
    "updated_at": {
      "type": "datetime",
      "label": "Last Updated",
      "listable": true,
      "sortable": true
    }
  }
}
```

Save as: `app/schema/crud6/registrations.json`

#### Step 2: Remove Custom Controllers (Optional)

With CRUD6, standard CRUD operations are handled automatically:

**Old Approach** - Custom controllers for each operation
**New Approach** - Use CRUD6 endpoints:

```
GET    /api/crud6/registrations          - List all
POST   /api/crud6/registrations          - Create
GET    /api/crud6/registrations/{id}     - View
PUT    /api/crud6/registrations/{id}     - Update
DELETE /api/crud6/registrations/{id}     - Delete
```

**Keep custom controllers for**:
- Complex business logic
- Custom workflows (approval, rejection)
- Integration endpoints
- Special reporting

#### Step 3: Update Frontend to Vue.js

**Old (UF 4.6.7) - jQuery/DataTables**:
```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations'
});
```

**New (UF 6 with CRUD6) - Vue Component**:
```vue
<template>
  <UFCRUD6ListPage model="registrations" />
</template>

<script setup lang="ts">
// Component automatically handles listing, filtering, pagination
</script>
```

Or for custom implementation:
```vue
<template>
  <div>
    <h1>{{ schema.title }}</h1>
    <UFTable
      :data-url="`/api/crud6/registrations`"
      :columns="columns"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables';

const { schema, loadSchema } = useCRUD6Schema();
const columns = ref([]);

onMounted(async () => {
  await loadSchema('registrations');
  // Extract columns from schema
  columns.value = Object.keys(schema.value.fields)
    .filter(key => schema.value.fields[key].listable)
    .map(key => ({
      data: key,
      title: schema.value.fields[key].label
    }));
});
</script>
```

#### Step 4: Migrate Custom Features

**Custom Approval Workflow**:

Since CRUD6 provides basic CRUD, implement approval as custom actions:

```php
// app/src/RegSevak/Controller/Registration/ApproveAction.php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

class ApproveAction
{
    public function __construct(
        protected SchemaService $schemaService,
        protected AlertStream $alerts,
        protected Authorizer $authorizer
    ) {}
    
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Get registration
        $model = $this->schemaService->getModelInstance('registrations');
        $registration = $model->find($args['id']);
        
        if (!$registration) {
            throw new NotFoundException();
        }
        
        // Check permission
        if (!$this->authorizer->checkAccess($currentUser, 'approve_registration')) {
            throw new ForbiddenException();
        }
        
        // Update status
        $registration->status = 'approved';
        $registration->approved_by = $currentUser->id;
        $registration->approved_at = now();
        $registration->save();
        
        // Send notification
        // ... notification logic
        
        $this->alerts->addMessageTranslated('success', 'REGISTRATION.APPROVED');
        
        return $response->withJson($registration, 200);
    }
}
```

Register custom route:
```php
// app/src/RegSevak/Routes/CustomRoutes.php
class CustomRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->group('/api/registrations', function (RouteCollectorProxy $group) {
            $group->post('/{id}/approve', ApproveAction::class)
                ->setName('api.registrations.approve');
                
            $group->post('/{id}/reject', RejectAction::class)
                ->setName('api.registrations.reject');
        })->add(AuthGuard::class);
    }
}
```

## Database Migration

### Step 1: Export Existing Data

```bash
# Export data from UF 4.6.7
mysqldump -u username -p database_name > regSevak_backup.sql

# Or export specific tables
mysqldump -u username -p database_name registrations users > data_export.sql
```

### Step 2: Schema Compatibility

Ensure database schema is compatible:

```sql
-- Add any missing columns for UF 6
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity_id INT;

-- Update any column types if needed
-- UF 6 might have different requirements
```

### Step 3: Import Data

```bash
# Import to UF 6 database
mysql -u username -p uf6_database < data_export.sql
```

## Configuration Migration

### Environment Variables

**UF 4.6.7** (`.env`):
```
DB_HOST=localhost
DB_NAME=regSevak
DB_USER=root
DB_PASSWORD=secret
```

**UF 6** (`.env`):
```
# Similar structure, verify any new requirements
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=regSevak_uf6
DB_USERNAME=root
DB_PASSWORD=secret
```

### Configuration Files

**UF 4.6.7**: `app/sprinkles/RegSevak/config/default.php`
**UF 6**: `app/config/default.php`

Structure is similar but verify service configurations.

## Testing Strategy

### Unit Tests

Update tests for UF 6 patterns:

```php
// Old (UF 4.6.7)
class RegistrationTest extends TestCase
{
    public function testCreateRegistration()
    {
        $response = $this->request('POST', '/api/registrations', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $this->assertEquals(201, $response->getStatusCode());
    }
}

// New (UF 6)
use UserFrosting\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function testCreateRegistration(): void
    {
        $response = $this->post('/api/crud6/registrations', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '1234567890'
        ]);
        
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'name', 'email']);
    }
}
```

### Integration Tests

Test complete workflows:

```php
public function testRegistrationWorkflow(): void
{
    // Create registration
    $response = $this->post('/api/crud6/registrations', $this->registrationData);
    $registrationId = $response->json('id');
    
    // Verify pending status
    $registration = $this->get("/api/crud6/registrations/{$registrationId}");
    $this->assertEquals('pending', $registration->json('status'));
    
    // Approve registration (custom endpoint)
    $this->post("/api/registrations/{$registrationId}/approve");
    
    // Verify approved status
    $registration = $this->get("/api/crud6/registrations/{$registrationId}");
    $this->assertEquals('approved', $registration->json('status'));
}
```

## Performance Considerations

### Optimization Strategies

1. **Caching**
   - Cache schema definitions
   - Cache permissions
   - Use query result caching

2. **Database Optimization**
   - Index frequently queried columns
   - Optimize Sprunje queries
   - Use eager loading

3. **Frontend Optimization**
   - Lazy load components
   - Optimize asset bundling
   - Use CDN for static assets

## Common Migration Issues

### Issue 1: Service Container Differences

**Problem**: Services registered differently

**Solution**:
```php
// Update service provider to use PHP-DI
public function register(): array
{
    return [
        MyService::class => \DI\autowire(MyService::class),
    ];
}
```

### Issue 2: Route Definition Changes

**Problem**: Route syntax changed

**Solution**:
```php
// Update to RouteDefinitionInterface
class MyRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->get('/my-route', MyAction::class);
    }
}
```

### Issue 3: Template Engine

**Problem**: Twig version differences

**Solution**: Review and update templates for Twig 3.x compatibility

## Post-Migration Checklist

- [ ] All features migrated and tested
- [ ] Database migrated successfully
- [ ] User accounts and roles working
- [ ] Permissions configured correctly
- [ ] Email notifications working
- [ ] Document uploads working
- [ ] Reports generating correctly
- [ ] Performance acceptable
- [ ] Security audit completed
- [ ] User training completed
- [ ] Documentation updated
- [ ] Backup strategy in place

## Rollback Plan

If migration fails:

1. **Restore database** from backup
2. **Revert to UF 4.6.7** code
3. **Verify functionality**
4. **Analyze issues**
5. **Plan fixes**
6. **Retry migration**

## Resources

### Documentation
- [UserFrosting 6 Documentation](https://learn.userfrosting.com/)
- [sprinkle-crud6 README](../../README.md)
- [Migration Guide](../../MIGRATION_FROM_THEME_CRUD6.md)

### Community Support
- [UserFrosting Chat](https://chat.userfrosting.com/)
- [GitHub Discussions](https://github.com/userfrosting/UserFrosting/discussions)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/userfrosting)

## Conclusion

Migrating RegSevak from UserFrosting 4.6.7 to UserFrosting 6 with sprinkle-crud6 provides:

✅ Modern PHP 8.1+ features
✅ Improved architecture and patterns
✅ Better separation of concerns
✅ Enhanced security
✅ Superior developer experience
✅ Future-proof foundation

The migration requires careful planning and execution, but the benefits of a modern, maintainable codebase make it worthwhile.

## Next Steps

1. Review all analysis documents
2. Create detailed migration plan
3. Set up development environment
4. Begin phase 1: Assessment
5. Execute migration phases
6. Deploy to production

## Related Documentation

- [01-overview.md](01-overview.md) - RegSevak overview
- [02-rsdashboard-flow.md](02-rsdashboard-flow.md) - Dashboard flow
- [03-datatables-integration.md](03-datatables-integration.md) - DataTables
- [04-crud-operations.md](04-crud-operations.md) - CRUD operations
- [05-user-flows.md](05-user-flows.md) - User workflows
- [06-admin-flows.md](06-admin-flows.md) - Admin workflows
- [07-key-features.md](07-key-features.md) - Feature analysis
