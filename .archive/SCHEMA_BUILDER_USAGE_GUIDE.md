# Schema Builder Usage Guide

The `SchemaBuilder` helper class provides a fluent API for programmatically creating CRUD6 schemas in tests. This ensures schemas stay in sync with code changes and makes test setup more maintainable.

## Why Use SchemaBuilder?

1. **Type Safety**: IDE autocompletion and type hints help prevent errors
2. **Maintainability**: Schema changes are code-based, easier to track in version control
3. **Consistency**: Enforces schema structure and conventions
4. **Testability**: Easy to create variations for different test scenarios
5. **Documentation**: Self-documenting - method names clearly indicate field types and options

## Basic Usage

### Simple Schema Creation

```php
use UserFrosting\Sprinkle\CRUD6\Testing\SchemaBuilder;

// Create a basic schema
$schema = SchemaBuilder::create('products', 'products')
    ->setTitleField('name')
    ->addStringField('name', required: true, listable: true)
    ->addStringField('sku', required: true, unique: true, listable: true)
    ->addCustomField('price', [
        'type' => 'decimal',
        'label' => 'CRUD6.PRODUCTS.PRICE',
        'required' => true,
        'listable' => true,
    ])
    ->build();
```

### Using Pre-built Helpers

```php
// Quick schemas for common models
$userSchema = SchemaBuilder::userSchema();
$groupSchema = SchemaBuilder::groupSchema();
$productSchema = SchemaBuilder::productSchema();
```

## Field Types

### String Fields

```php
$schema = SchemaBuilder::create('users', 'users')
    ->addStringField(
        name: 'user_name',
        required: true,
        sortable: true,
        filterable: true,
        listable: true,
        maxLength: 100,
        unique: true
    )
    ->build();
```

### Integer Fields

```php
$schema = SchemaBuilder::create('products', 'products')
    ->addIntegerField('id', autoIncrement: true, readonly: true)
    ->addIntegerField('quantity', required: true, listable: true)
    ->build();
```

### Boolean Fields

```php
$schema = SchemaBuilder::create('users', 'users')
    ->addBooleanField('flag_enabled', listable: true, default: true)
    ->build();
```

### Date/DateTime Fields

```php
$schema = SchemaBuilder::create('events', 'events')
    ->addDateField('event_date', required: true, listable: true)
    ->addDateTimeField('created_at', readonly: true, format: 'Y-m-d H:i:s')
    ->build();
```

### Email Fields

```php
$schema = SchemaBuilder::create('contacts', 'contacts')
    ->addEmailField('email', required: true, unique: true, listable: true)
    ->build();
```

### Password Fields

```php
$schema = SchemaBuilder::create('users', 'users')
    ->addPasswordField('password', required: true, minLength: 12)
    ->build();
```

### Text Fields (Multiline)

```php
$schema = SchemaBuilder::create('posts', 'posts')
    ->addTextField('content', required: true, filterable: true)
    ->build();
```

### JSON Fields

```php
$schema = SchemaBuilder::create('settings', 'settings')
    ->addJsonField('config', required: true)
    ->build();
```

### Custom Fields

For field types not covered by helper methods:

```php
$schema = SchemaBuilder::create('products', 'products')
    ->addCustomField('price', [
        'type' => 'decimal',
        'label' => 'CRUD6.PRODUCTS.PRICE',
        'required' => true,
        'sortable' => true,
        'filterable' => true,
        'listable' => true,
        'validation' => [
            'required' => true,
            'numeric' => true,
            'min' => 0,
            'max' => 999999.99,
        ],
        'precision' => 2,
    ])
    ->build();
```

## Configuration Options

### Primary Key

```php
$schema = SchemaBuilder::create('orders', 'orders')
    ->setPrimaryKey('order_id')  // Default is 'id'
    ->build();
```

### Title Field

```php
$schema = SchemaBuilder::create('products', 'products')
    ->setTitleField('name')  // Field used to display record in UI
    ->build();
```

### Default Sort

```php
$schema = SchemaBuilder::create('posts', 'posts')
    ->setDefaultSort('created_at', 'desc')
    ->build();
```

### Database Connection

```php
$schema = SchemaBuilder::create('analytics', 'page_views')
    ->setConnection('analytics_db')
    ->build();
```

### Permissions

```php
$schema = SchemaBuilder::create('users', 'users')
    ->addPermissions([
        'read' => 'uri_users',
        'create' => 'create_user',
        'update' => 'update_user_field',
        'delete' => 'delete_user',
    ])
    ->build();
```

### Relationships

```php
$schema = SchemaBuilder::create('groups', 'groups')
    ->addDetail(
        model: 'users',
        foreignKey: 'group_id',
        listFields: ['user_name', 'email', 'first_name', 'last_name'],
        title: 'CRUD6.GROUP.USERS'
    )
    ->build();
```

## Real-World Test Examples

### Test with Dynamic Schema

```php
class CreateProductTest extends CRUD6TestCase
{
    use RefreshDatabase;
    use WithTestUser;

    public function testCreateProduct(): void
    {
        // Create schema programmatically
        $schema = SchemaBuilder::create('products', 'products')
            ->setTitleField('name')
            ->addPermissions(['create' => 'create_product'])
            ->addStringField('sku', required: true, unique: true)
            ->addStringField('name', required: true, listable: true)
            ->addCustomField('price', [
                'type' => 'decimal',
                'required' => true,
                'validation' => ['numeric' => true, 'min' => 0],
            ])
            ->build();

        // Use schema in test...
        $user = User::factory()->create();
        $this->actAsUser($user, permissions: ['create_product']);

        $request = $this->createJsonRequest('POST', '/api/crud6/products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'price' => 99.99,
        ]);

        $response = $this->handleRequest($request);
        $this->assertResponseStatus(201, $response);
    }
}
```

### Test with Schema Variations

```php
public function testRequiredFieldValidation(): void
{
    $schema = SchemaBuilder::create('users', 'users')
        ->addStringField('user_name', required: true)
        ->addEmailField('email', required: true)
        ->build();

    // Test that missing required fields are rejected
    $request = $this->createJsonRequest('POST', '/api/crud6/users', [
        'user_name' => 'testuser',
        // Missing email
    ]);

    $response = $this->handleRequest($request);
    $this->assertResponseStatus(400, $response);
}

public function testOptionalFieldValidation(): void
{
    $schema = SchemaBuilder::create('users', 'users')
        ->addStringField('user_name', required: true)
        ->addStringField('bio', required: false)  // Optional
        ->build();

    // Test that optional fields can be omitted
    $request = $this->createJsonRequest('POST', '/api/crud6/users', [
        'user_name' => 'testuser',
        // Bio is optional, can be omitted
    ]);

    $response = $this->handleRequest($request);
    $this->assertResponseStatus(201, $response);
}
```

### Test with Multiple Schemas

```php
public function testCrossModelRelationship(): void
{
    // Create related schemas
    $userSchema = SchemaBuilder::userSchema();
    $groupSchema = SchemaBuilder::groupSchema()
        ->addDetail('users', 'group_id', ['user_name', 'email'])
        ->build();

    // Test that relationships work correctly
    // ... test code ...
}
```

## Exporting to JSON

If you need to save the schema as a JSON file:

```php
$schema = SchemaBuilder::create('products', 'products')
    ->addStringField('name', required: true)
    ->build();

// Get as JSON string
$json = SchemaBuilder::create('products', 'products')
    ->addStringField('name', required: true)
    ->toJson();

// Save to file
file_put_contents('app/schema/crud6/products.json', $json);
```

## Best Practices

### 1. Use Pre-built Helpers When Possible

```php
// Good - uses helper
$schema = SchemaBuilder::userSchema();

// Also good - customizes helper
$schema = SchemaBuilder::create('users', 'users')
    ->addStringField('user_name', required: true, listable: true)
    ->addEmailField('email', required: true, unique: true, listable: true)
    ->build();
```

### 2. Keep Test Schemas Minimal

Only include fields needed for the specific test:

```php
// Good - minimal schema for permission test
public function testCreatePermission(): void
{
    $schema = SchemaBuilder::create('products', 'products')
        ->addPermissions(['create' => 'create_product'])
        ->addStringField('name', required: true)
        ->build();
    
    // ... test permission check ...
}

// Bad - includes unnecessary fields
public function testCreatePermission(): void
{
    $schema = SchemaBuilder::productSchema(); // Too much for this test
    // ... test permission check ...
}
```

### 3. Document Complex Schemas

```php
// Create order schema with items relationship
// Tests order creation with line items
$orderSchema = SchemaBuilder::create('orders', 'orders')
    ->setPrimaryKey('order_id')
    ->addStringField('order_number', required: true, unique: true)
    ->addCustomField('total', ['type' => 'decimal', 'required' => true])
    ->addDetail('order_items', 'order_id', ['product_name', 'quantity'])
    ->build();
```

### 4. Reuse Schema Builders in setUp()

```php
class ProductTestCase extends CRUD6TestCase
{
    protected array $productSchema;

    public function setUp(): void
    {
        parent::setUp();
        
        // Build once, use in all tests
        $this->productSchema = SchemaBuilder::productSchema();
    }

    public function testCreateProduct(): void
    {
        // Use pre-built schema
        // ... test code using $this->productSchema ...
    }
}
```

### 5. Test Schema Variations

```php
public function testWithMinimalPermissions(): void
{
    $schema = SchemaBuilder::userSchema();
    $schema['permissions'] = ['read' => 'uri_users']; // Only read
    // ... test ...
}

public function testWithFullPermissions(): void
{
    $schema = SchemaBuilder::userSchema();
    // Uses default permissions (all CRUD operations)
    // ... test ...
}
```

## Migration from JSON Files

### Before (JSON file)

```json
{
  "model": "products",
  "table": "products",
  "fields": {
    "name": {
      "type": "string",
      "required": true,
      "listable": true
    }
  }
}
```

### After (SchemaBuilder)

```php
$schema = SchemaBuilder::create('products', 'products')
    ->addStringField('name', required: true, listable: true)
    ->build();
```

### Benefits

1. **Type checking**: IDE catches typos and errors
2. **Easier refactoring**: Find all usages, rename safely
3. **Version control**: Changes are code diffs, not JSON diffs
4. **Testable**: Can unit test schema generation itself
5. **Dynamic**: Can create variations easily for different test scenarios

## Common Patterns

### Pattern: Permission Testing

```php
$noPermissionSchema = SchemaBuilder::create('test', 'test')
    ->addPermissions(['read' => 'impossible_permission'])
    ->build();

$withPermissionSchema = SchemaBuilder::create('test', 'test')
    ->addPermissions(['read' => 'uri_test'])
    ->build();
```

### Pattern: Validation Testing

```php
// Test required field validation
$schema = SchemaBuilder::create('test', 'test')
    ->addStringField('required_field', required: true)
    ->addStringField('optional_field', required: false)
    ->build();

// Test unique constraint
$schema = SchemaBuilder::create('test', 'test')
    ->addStringField('unique_field', unique: true)
    ->build();

// Test email validation
$schema = SchemaBuilder::create('test', 'test')
    ->addEmailField('email', required: true)
    ->build();
```

### Pattern: Relationship Testing

```php
// Parent with children
$parentSchema = SchemaBuilder::create('orders', 'orders')
    ->addStringField('order_number', required: true)
    ->addDetail('order_items', 'order_id', ['product_name', 'quantity'])
    ->build();

// Child references parent
$childSchema = SchemaBuilder::create('order_items', 'order_items')
    ->addIntegerField('order_id', required: true)
    ->addStringField('product_name', required: true)
    ->build();
```

## Troubleshooting

### Schema Not Recognized

Ensure you're passing the schema to the correct service or test method:

```php
// In tests, schemas may need to be registered with SchemaService
$schemaService = $this->ci->get(SchemaService::class);
// Or use SchemaBuilder directly in test setup
```

### Field Not Appearing in List

Check the `show_in` configuration:

```php
// Field visible in list
->addStringField('name', listable: true)

// Field only in form/detail
->addStringField('internal_notes', listable: false)
```

### Validation Not Working

Ensure validation rules are set correctly:

```php
// With validation
->addEmailField('email', required: true, unique: true)

// Custom validation via addCustomField
->addCustomField('age', [
    'type' => 'integer',
    'validation' => [
        'required' => true,
        'integer' => true,
        'min' => 0,
        'max' => 150,
    ],
])
```

## Next Steps

1. **Start Simple**: Begin with pre-built helpers (userSchema, groupSchema, productSchema)
2. **Customize**: Use builder methods to add/modify fields as needed
3. **Expand**: Create your own domain-specific schema builders
4. **Test**: Write tests that validate schema generation
5. **Refactor**: Convert existing JSON schemas to builder-based schemas gradually

For more examples, see:
- `app/tests/Testing/SchemaBuilderTest.php` - Unit tests showing all features
- `app/tests/Testing/SchemaBuilder.php` - Full API documentation in comments
