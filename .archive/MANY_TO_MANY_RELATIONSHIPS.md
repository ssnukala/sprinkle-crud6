# Many-to-Many Relationship Management in CRUD6

## Overview

CRUD6 now supports managing many-to-many relationships through pivot tables. This enables attaching and detaching related records, similar to UserFrosting's admin sprinkle functionality for managing user roles and role permissions.

## Features

### Backend API

Two new endpoints for managing many-to-many relationships:

```
POST   /api/crud6/{model}/{id}/{relation}   - Attach relationships
DELETE /api/crud6/{model}/{id}/{relation}   - Detach relationships
```

### Frontend Composable

New `useCRUD6Relationships` composable provides:

```typescript
const { attachRelationships, detachRelationships, apiLoading, apiError } = useCRUD6Relationships()

// Attach
await attachRelationships('users', '5', 'roles', [1, 2, 3])

// Detach
await detachRelationships('users', '5', 'roles', [2])
```

## Schema Configuration

Define many-to-many relationships in your schema:

```json
{
  "model": "users",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "user_roles",
      "foreign_key": "user_id",
      "related_key": "role_id",
      "title": "USER.ROLES"
    }
  ]
}
```

### Schema Properties

- **name**: The relationship name (used in URL)
- **type**: Must be "many_to_many"
- **pivot_table**: The junction/pivot table name
- **foreign_key**: Column in pivot table referencing parent model (defaults to `{model}_id`)
- **related_key**: Column in pivot table referencing related model (defaults to `{relation}_id`)
- **title**: Display title for the relationship (optional, supports i18n keys)

## API Usage

### Attach Relationships

**Request:**
```http
POST /api/crud6/users/5/roles
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "title": "Success",
  "description": "Successfully attached 3 roles to User"
}
```

**Behavior:**
- Inserts records into pivot table
- Uses `insertOrIgnore` to avoid duplicates
- Sets `created_at` and `updated_at` timestamps
- Logs activity

### Detach Relationships

**Request:**
```http
DELETE /api/crud6/users/5/roles
Content-Type: application/json

{
  "ids": [2]
}
```

**Response:**
```json
{
  "title": "Success", 
  "description": "Successfully detached 1 roles from User"
}
```

**Behavior:**
- Deletes records from pivot table
- Only removes specified IDs
- Logs activity

## Frontend Implementation

### Using the Composable

```typescript
import { useCRUD6Relationships } from '@ssnukala/sprinkle-crud6/composables'

const { attachRelationships, detachRelationships, apiLoading } = useCRUD6Relationships()

// Attach multiple roles to a user
async function addRoles(userId: string, roleIds: number[]) {
  try {
    await attachRelationships('users', userId, 'roles', roleIds)
    // Success - alert shown automatically
  } catch (error) {
    // Error - alert shown automatically
  }
}

// Remove a role from a user
async function removeRole(userId: string, roleId: number) {
  await detachRelationships('users', userId, 'roles', [roleId])
}
```

### Example Modal Component

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useCRUD6Relationships } from '@ssnukala/sprinkle-crud6/composables'

const props = defineProps<{
  userId: string
  currentRoleIds: number[]
}>()

const emit = defineEmits(['saved'])

const { attachRelationships, detachRelationships, apiLoading } = useCRUD6Relationships()
const selectedRoles = ref<number[]>([...props.currentRoleIds])

async function save() {
  const current = new Set(props.currentRoleIds)
  const selected = new Set(selectedRoles.value)
  
  // Determine which to attach and detach
  const toAttach = [...selected].filter(id => !current.has(id))
  const toDetach = [...current].filter(id => !selected.has(id))
  
  // Attach new relationships
  if (toAttach.length > 0) {
    await attachRelationships('users', props.userId, 'roles', toAttach)
  }
  
  // Detach removed relationships
  if (toDetach.length > 0) {
    await detachRelationships('users', props.userId, 'roles', toDetach)
  }
  
  emit('saved')
}
</script>

<template>
  <UFModal>
    <!-- Multi-select for roles -->
    <select v-model="selectedRoles" multiple>
      <option v-for="role in allRoles" :key="role.id" :value="role.id">
        {{ role.name }}
      </option>
    </select>
    
    <button @click="save" :disabled="apiLoading">
      Save
    </button>
  </UFModal>
</template>
```

## Security

### Permission Checks

The `RelationshipAction` checks the parent model's update permission:

```php
$updatePermission = $schema['permissions']['update'] ?? null;
if ($updatePermission && !$this->authorizer->checkAccess($currentUser, $updatePermission)) {
    throw new ForbiddenException();
}
```

To manage relationships, users must have permission to update the parent model.

### Activity Logging

All attach/detach operations are logged:

```php
$this->userActivityLogger->info(
    "User {$currentUser->user_name} attached roles for users 5.",
    [
        'type'     => 'relationship_attached',
        'model'    => 'users',
        'id'       => '5',
        'relation' => 'roles',
        'count'    => 3,
    ]
);
```

## Examples

### User Roles

**Schema (users.json):**
```json
{
  "model": "users",
  "relationships": [
    {
      "name": "roles",
      "type": "many_to_many",
      "pivot_table": "user_roles",
      "foreign_key": "user_id",
      "related_key": "role_id"
    }
  ]
}
```

**Usage:**
```typescript
// Add admin and moderator roles to user
await attachRelationships('users', '5', 'roles', [1, 2])

// Remove moderator role
await detachRelationships('users', '5', 'roles', [2])
```

### Role Permissions

**Schema (roles.json):**
```json
{
  "model": "roles",
  "relationships": [
    {
      "name": "permissions",
      "type": "many_to_many",
      "pivot_table": "role_permissions",
      "foreign_key": "role_id",
      "related_key": "permission_id"
    }
  ]
}
```

**Usage:**
```typescript
// Grant permissions to a role
await attachRelationships('roles', 'admin', 'permissions', [10, 11, 12])

// Revoke a permission
await detachRelationships('roles', 'admin', 'permissions', [12])
```

## Database Schema

### Pivot Table Structure

```sql
CREATE TABLE user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

**Notes:**
- Composite primary key prevents duplicates
- Foreign keys with CASCADE delete clean up relationships
- Timestamps are optional but recommended
- `insertOrIgnore` prevents duplicate key errors

## Compatibility with Admin Sprinkle

This feature implements the same pattern used in sprinkle-admin:

- **User Roles Management**: `UserManageRolesAction`
- **Role Permissions Management**: `RoleManagePermissionsAction`

The CRUD6 implementation provides the same functionality but in a generic, reusable way for any model pair.

## Benefits

1. **Generic**: Works with any many-to-many relationship
2. **Schema-Driven**: Configuration in JSON, not code
3. **Type-Safe**: TypeScript support with proper types
4. **Secure**: Permission checks and activity logging
5. **Transactional**: All operations wrapped in database transactions
6. **Error Handling**: Proper error messages and validation

## Migration from Admin Sprinkle

**Old (Admin Sprinkle):**
```php
// UserManageRolesAction - hardcoded for users/roles
class UserManageRolesAction {
    // ... specific to users and roles
}
```

**New (CRUD6):**
```php
// RelationshipAction - works for any model pair
class RelationshipAction {
    // ... reads config from schema
}
```

**Old Frontend:**
```typescript
// useUserRolesApi - specific composable
const { addRoles, removeRoles } = useUserRolesApi()
```

**New Frontend:**
```typescript
// useCRUD6Relationships - generic composable
const { attachRelationships, detachRelationships } = useCRUD6Relationships()
attachRelationships('users', id, 'roles', roleIds)
```

This approach is more flexible and requires less code duplication.
