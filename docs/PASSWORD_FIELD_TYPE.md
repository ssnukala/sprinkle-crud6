# Password Field Type

## Overview

CRUD6 now supports a dedicated `password` field type that automatically hashes passwords using UserFrosting 6's built-in password hashing mechanism (bcrypt) before storing them in the database.

## Features

- **Automatic Password Hashing**: Password fields are automatically hashed using UserFrosting's `Hasher` service
- **Security by Default**: Password fields are not listable or viewable by default to prevent exposure
- **Support Across All Actions**: Password hashing works in CreateAction, EditAction, and UpdateFieldAction
- **Empty Password Handling**: Empty or null passwords are not hashed, allowing for optional password updates
- **Validation**: Password fields support standard validation rules (e.g., minimum length)

## Schema Configuration

To use the password field type, set the field type to `password` in your schema:

```json
{
  "model": "users",
  "fields": {
    "password": {
      "type": "password",
      "label": "Password",
      "required": false,
      "listable": false,
      "viewable": false,
      "editable": true,
      "validation": {
        "length": {
          "min": 8,
          "max": 255
        }
      }
    }
  }
}
```

### Field Configuration Properties

- **type**: Must be set to `"password"` to enable automatic hashing
- **listable**: Should be `false` to prevent passwords from appearing in list views (default: false)
- **viewable**: Should be `false` to prevent passwords from appearing in detail views (default: false)
- **editable**: Set to `true` to allow password updates via edit forms
- **required**: Set to `true` for create operations if password is mandatory, or `false` to allow optional updates
- **validation**: Standard validation rules apply (e.g., minimum/maximum length)

## How It Works

### Create Action

When creating a new record with a password field:

1. User submits form data with plain-text password
2. CreateAction validates the data
3. Before database insertion, `hashPasswordFields()` method is called
4. Password fields are identified by their `type: "password"` in the schema
5. Non-empty passwords are hashed using `$this->hasher->hash($password)`
6. Hashed password is stored in the database

### Edit Action

When updating an existing record:

1. User submits form data with new plain-text password (or no password to keep existing)
2. EditAction validates the data
3. Before database update, `hashPasswordFields()` method is called
4. Only non-empty password fields are hashed
5. Empty password fields are ignored, keeping the existing hashed password unchanged

### UpdateField Action

When updating a single password field:

1. User submits new plain-text password value
2. UpdateFieldAction validates the data
3. Field type is checked - if it's a password type and value is not empty
4. Password is hashed using `$this->hasher->hash($password)`
5. Hashed password replaces the plain-text value before saving

## Implementation Details

### Base Controller

The `Base` controller provides a `hashPasswordFields()` hook method that child controllers can override:

```php
protected function hashPasswordFields(array $schema, array $data): array
{
    // Base implementation does nothing - child controllers with Hasher service
    // should override this method to hash password fields
    return $data;
}
```

This method is called by `prepareInsertData()` and `prepareUpdateData()` before database operations.

### CreateAction & EditAction

Both actions inject the `Hasher` service and override `hashPasswordFields()`:

```php
public function __construct(
    // ... other dependencies ...
    protected Hasher $hasher,
) {
    parent::__construct(...);
}

protected function hashPasswordFields(array $schema, array $data): array
{
    $fields = $schema['fields'] ?? [];
    
    foreach ($fields as $fieldName => $fieldConfig) {
        if (($fieldConfig['type'] ?? '') === 'password' && 
            isset($data[$fieldName]) && 
            !empty($data[$fieldName])) {
            $data[$fieldName] = $this->hasher->hash($data[$fieldName]);
        }
    }
    
    return $data;
}
```

### UpdateFieldAction

UpdateFieldAction hashes passwords inline during the field update:

```php
$fieldConfig = $crudSchema['fields'][$fieldName] ?? [];
if (($fieldConfig['type'] ?? '') === 'password' && !empty($newValue)) {
    $newValue = $this->hasher->hash($newValue);
}
$crudModel->{$fieldName} = $newValue;
```

## Security Considerations

1. **Never Expose Password Hashes**: Set `listable: false` and `viewable: false` for password fields
2. **Validation**: Always set minimum password length requirements
3. **Required vs Optional**: 
   - For create operations, consider making password `required: true`
   - For update operations, keep `required: false` to allow updates without changing password
4. **Empty Password Handling**: Empty passwords are not hashed, maintaining existing passwords on updates

## Examples

### User Schema with Password

```json
{
  "model": "users",
  "title": "User Management",
  "fields": {
    "id": {
      "type": "integer",
      "auto_increment": true
    },
    "username": {
      "type": "string",
      "required": true,
      "validation": {
        "unique": true
      }
    },
    "email": {
      "type": "string",
      "required": true,
      "validation": {
        "email": true,
        "unique": true
      }
    },
    "password": {
      "type": "password",
      "label": "Password",
      "required": false,
      "listable": false,
      "viewable": false,
      "editable": true,
      "validation": {
        "length": {
          "min": 8,
          "max": 255
        }
      }
    }
  }
}
```

### Creating a User with Password

**API Request:**
```json
POST /api/crud6/users
{
  "username": "johndoe",
  "email": "john@example.com",
  "password": "MySecurePassword123"
}
```

**Database Result:**
```
username: johndoe
email: john@example.com
password: $2y$10$xGHJ8... (bcrypt hash)
```

### Updating a Password

**API Request:**
```json
PUT /api/crud6/users/123
{
  "password": "NewSecurePassword456"
}
```

The password will be hashed and stored. Other fields remain unchanged.

### Updating Without Changing Password

**API Request:**
```json
PUT /api/crud6/users/123
{
  "email": "newemail@example.com"
}
```

The existing password hash remains unchanged because no password field was submitted.

## Testing

The password field feature includes comprehensive unit tests in `app/tests/Controller/PasswordFieldTest.php`:

- Password hashing in CreateAction
- Password hashing in EditAction
- Empty password handling (no hashing)
- Non-password fields are unaffected
- Hasher service injection in UpdateFieldAction

Run tests with:
```bash
vendor/bin/phpunit app/tests/Controller/PasswordFieldTest.php
```

## Migration from String Type

If you have existing schemas using `type: "string"` for passwords, update them to `type: "password"`:

**Before:**
```json
"password": {
  "type": "string",
  "editable": false
}
```

**After:**
```json
"password": {
  "type": "password",
  "editable": true,
  "listable": false,
  "viewable": false,
  "validation": {
    "length": {
      "min": 8
    }
  }
}
```

## See Also

- [Schema API Quick Reference](SCHEMA_API_QUICK_REFERENCE.md)
- [Field Template Feature](FIELD_TEMPLATE_FEATURE.md)
- [Integration Testing](INTEGRATION_TESTING.md)
