# SchemaBuilder Quick Reference Card

## Basic Usage

```php
use UserFrosting\Sprinkle\CRUD6\Testing\SchemaBuilder;

$schema = SchemaBuilder::create('model_name', 'table_name')
    ->addStringField('name', required: true, listable: true)
    ->build();
```

## Pre-built Helpers

```php
SchemaBuilder::userSchema()     // Complete user schema
SchemaBuilder::groupSchema()    // Complete group schema
SchemaBuilder::productSchema()  // Complete product schema
```

## Field Types

| Method | Usage |
|--------|-------|
| `addStringField()` | Text input (default max 255 chars) |
| `addIntegerField()` | Numeric input (whole numbers) |
| `addBooleanField()` | Checkbox/toggle |
| `addDateTimeField()` | Date and time picker |
| `addDateField()` | Date picker only |
| `addEmailField()` | Email with validation |
| `addPasswordField()` | Password input (hidden) |
| `addTextField()` | Multiline text area |
| `addJsonField()` | JSON data storage |
| `addCustomField()` | Full control over config |

## Common Parameters

All field methods support:
- `required: bool` - Field is mandatory
- `sortable: bool` - Enable sorting in lists
- `filterable: bool` - Enable search/filtering
- `listable: bool` - Show in list views

## Field-Specific Parameters

```php
// String
->addStringField('name', maxLength: 100, unique: true)

// Integer
->addIntegerField('id', autoIncrement: true, readonly: true)

// Boolean
->addBooleanField('active', default: true)

// DateTime
->addDateTimeField('created_at', readonly: true, format: 'Y-m-d H:i:s')

// Email
->addEmailField('email', unique: true)

// Password
->addPasswordField('password', minLength: 12)
```

## Configuration Methods

```php
->setPrimaryKey('id')                           // Default: 'id'
->setTitleField('name')                         // Display field
->setDefaultSort('created_at', 'desc')          // Default sorting
->setConnection('analytics_db')                 // Database connection
->addPermissions(['read' => 'uri_model'])       // CRUD permissions
->addDetail('related_model', 'foreign_key', [...]) // Relationships
```

## Build & Export

```php
$schema = $builder->build();      // Get array
$json = $builder->toJson();       // Get JSON string
```

## Complete Example

```php
$schema = SchemaBuilder::create('orders', 'orders')
    ->setPrimaryKey('order_id')
    ->setTitleField('order_number')
    ->setDefaultSort('created_at', 'desc')
    ->addPermissions([
        'read' => 'view_orders',
        'create' => 'create_order',
        'update' => 'update_order',
        'delete' => 'delete_order',
    ])
    ->addIntegerField('order_id', autoIncrement: true, readonly: true)
    ->addStringField('order_number', required: true, unique: true, listable: true)
    ->addDateTimeField('order_date', required: true, listable: true)
    ->addCustomField('total', [
        'type' => 'decimal',
        'label' => 'CRUD6.ORDERS.TOTAL',
        'required' => true,
        'listable' => true,
        'validation' => ['numeric' => true, 'min' => 0],
    ])
    ->addStringField('status', listable: true)
    ->addTextField('notes')
    ->addDetail('order_items', 'order_id', ['product_name', 'quantity', 'price'])
    ->build();
```

## Testing Pattern

```php
class MyTest extends CRUD6TestCase
{
    public function testCreate(): void
    {
        $schema = SchemaBuilder::create('test', 'test')
            ->addStringField('name', required: true)
            ->addPermissions(['create' => 'create_test'])
            ->build();
        
        // Use $schema in test...
    }
}
```

## Validation Examples

```php
// Required field
->addStringField('username', required: true)

// Unique constraint
->addStringField('email', unique: true)

// Length constraint
->addStringField('code', maxLength: 10)

// Email validation
->addEmailField('contact_email', required: true)

// Password minimum length
->addPasswordField('password', minLength: 12)

// Numeric validation via custom field
->addCustomField('age', [
    'type' => 'integer',
    'validation' => ['integer' => true, 'min' => 0, 'max' => 150],
])
```

## Show In Options

Controls where fields appear:

```php
// List view only
->addStringField('code', listable: true)
// Generates: 'show_in' => ['list', 'form', 'detail']

// Form and detail only (not in list)
->addTextField('notes', listable: false)
// Generates: 'show_in' => ['form', 'detail']

// Detail only (readonly fields)
->addDateTimeField('created_at', readonly: true, listable: false)
// Generates: 'show_in' => ['detail']
```

## Permission Patterns

```php
// Read only
->addPermissions(['read' => 'uri_model'])

// Full CRUD
->addPermissions([
    'read' => 'uri_model',
    'create' => 'create_model',
    'update' => 'update_model_field',
    'delete' => 'delete_model',
])

// Custom permission names
->addPermissions(['read' => 'view_sensitive_data'])
```

## Relationships

```php
// One-to-many: Group has many Users
SchemaBuilder::create('groups', 'groups')
    ->addDetail(
        model: 'users',
        foreignKey: 'group_id',
        listFields: ['user_name', 'email'],
        title: 'CRUD6.GROUP.USERS'
    )
    ->build();

// Many-to-many: Role has many Permissions
SchemaBuilder::create('roles', 'roles')
    ->addDetail(
        model: 'permissions',
        foreignKey: 'role_id',
        listFields: ['slug', 'name']
    )
    ->build();
```

## Tips

✅ **DO:**
- Use pre-built helpers when possible
- Keep test schemas minimal (only needed fields)
- Document complex schemas with comments
- Reuse schema builders in `setUp()`

❌ **DON'T:**
- Create overly complex schemas for simple tests
- Forget to call `->build()` at the end
- Mix SchemaBuilder with manual array construction

## See Also

- Full documentation: `.archive/SCHEMA_BUILDER_USAGE_GUIDE.md`
- Unit tests: `app/tests/Testing/SchemaBuilderTest.php`
- Source code: `app/tests/Testing/SchemaBuilder.php`
