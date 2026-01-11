<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud6
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/userfrosting/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Seeds;

use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\PermissionInterface;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;
use UserFrosting\Sprinkle\Core\Seeder\SeedInterface;

/**
 * Seeder for the default permissions.
 */
class DefaultPermissions implements SeedInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        // We require the default roles seed
        (new DefaultRoles())->run();

        // Get and save permissions
        $permissions = $this->getPermissions();
        $this->savePermissions($permissions);

        // Add default mappings to permissions
        $this->syncPermissionsRole($permissions);
    }

    /**
     * @return Permission[] Permissions to seed
     */
    protected function getPermissions(): array
    {
        $permissions = [
            // Legacy generic CRUD6 permissions (kept for backward compatibility)
            'create_crud6' => new Permission([
                'slug'        => 'create_crud6',
                'name'        => 'Create crud6',
                'conditions'  => 'always()',
                'description' => 'Create a new crud6.',
            ]),
            'delete_crud6' => new Permission([
                'slug'        => 'delete_crud6',
                'name'        => 'Delete crud6',
                'conditions'  => 'always()',
                'description' => 'Delete a crud6.',
            ]),
            'update_crud6_field' => new Permission([
                'slug'        => 'update_crud6_field',
                'name'        => 'Edit crud6',
                'conditions'  => 'always()',
                'description' => 'Edit basic properties of any crud6.',
            ]),
            'uri_crud6' => new Permission([
                'slug'        => 'uri_crud6',
                'name'        => 'View crud6',
                'conditions'  => 'always()',
                'description' => 'View the crud6 page of any crud6.',
            ]),
            'uri_crud6_list' => new Permission([
                'slug'        => 'uri_crud6_list',
                'name'        => 'crud6 management page',
                'conditions'  => 'always()',
                'description' => 'View a page containing a list of crud6s.',
            ]),
            'view_crud6_field' => new Permission([
                'slug'        => 'view_crud6_field',
                'name'        => 'View crud6',
                'conditions'  => 'always()',
                'description' => 'View certain properties of any crud6.',
            ]),
        ];
        
        // Add model-specific permissions for common models (users, groups, roles, permissions)
        $models = ['users', 'groups', 'roles', 'permissions'];
        $actions = ['read', 'create', 'edit', 'delete'];
        
        foreach ($models as $model) {
            foreach ($actions as $action) {
                $slug = "crud6.{$model}.{$action}";
                $permissions[$slug] = new Permission([
                    'slug'        => $slug,
                    'name'        => ucfirst($action) . ' ' . $model,
                    'conditions'  => 'always()',
                    'description' => ucfirst($action) . ' ' . $model . ' via CRUD6.',
                ]);
            }
        }
        
        // Dynamically load permissions from schema files
        $schemaPermissions = $this->loadPermissionsFromSchemas();
        foreach ($schemaPermissions as $slug => $permission) {
            // Don't overwrite existing permissions
            if (!isset($permissions[$slug])) {
                $permissions[$slug] = $permission;
            }
        }
        
        return $permissions;
    }
    
    /**
     * Load permissions from all schema files in app/schema/crud6/ directory.
     * 
     * Scans all JSON schema files and extracts permission definitions from
     * the "permissions" section of each schema. This ensures that any permission
     * referenced in a schema is available in the database for tests and runtime use.
     * 
     * @return Permission[] Array of Permission objects keyed by slug
     */
    protected function loadPermissionsFromSchemas(): array
    {
        $permissions = [];
        
        // Path to schema directory
        $schemaDir = __DIR__ . '/../../../schema/crud6';
        
        // Check if directory exists
        if (!is_dir($schemaDir)) {
            return $permissions;
        }
        
        // Get all JSON files in the schema directory
        $schemaFiles = glob($schemaDir . '/*.json');
        
        if ($schemaFiles === false) {
            return $permissions;
        }
        
        foreach ($schemaFiles as $schemaFile) {
            try {
                // Load and decode schema file
                $schemaContent = file_get_contents($schemaFile);
                if ($schemaContent === false) {
                    continue;
                }
                
                $schema = json_decode($schemaContent, true);
                if (!is_array($schema) || !isset($schema['permissions']) || !is_array($schema['permissions'])) {
                    continue;
                }
                
                $modelName = $schema['model'] ?? basename($schemaFile, '.json');
                
                // Extract each permission from the schema's permissions section
                foreach ($schema['permissions'] as $action => $permissionSlug) {
                    // Skip if permission already exists in our collection
                    if (isset($permissions[$permissionSlug])) {
                        continue;
                    }
                    
                    // Create a descriptive name based on action and model
                    $actionName = ucfirst($action);
                    $modelDisplayName = ucfirst($modelName);
                    
                    // Create permission object
                    $permissions[$permissionSlug] = new Permission([
                        'slug'        => $permissionSlug,
                        'name'        => "{$actionName} {$modelDisplayName}",
                        'conditions'  => 'always()',
                        'description' => "{$actionName} {$modelDisplayName} via CRUD6.",
                    ]);
                }
            } catch (\Exception $e) {
                // Skip files that can't be parsed
                continue;
            }
        }
        
        return $permissions;
    }

    /**
     * Save permissions.
     *
     * @param array<string, PermissionInterface> $permissions
     */
    protected function savePermissions(array &$permissions): void
    {
        /** @var PermissionInterface $permission */
        foreach ($permissions as $slug => $permission) {
            // Trying to find if the permission already exists
            $existingPermission = Permission::where([
                'slug'       => $permission->slug,
                'conditions' => $permission->conditions,
            ])->first();

            // Don't save if already exist, use existing permission reference
            // otherwise to re-sync permissions and roles
            if ($existingPermission == null) {
                $permission->save();
            } else {
                $permissions[$slug] = $existingPermission;
            }
        }
    }

    /**
     * Sync permissions with default roles.
     *
     * @param Permission[] $permissions
     */
    protected function syncPermissionsRole(array $permissions): void
    {
        /** @var Role|null */
        $roleSiteAdmin = Role::where('slug', 'site-admin')->first();
        if ($roleSiteAdmin !== null) {
            // Collect all permission IDs for site-admin
            $permissionIds = [];
            foreach ($permissions as $permission) {
                $permissionIds[] = $permission->id;
            }
            
            // Sync all CRUD6 permissions to site-admin role
            $roleSiteAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        /** @var Role|null */
        $rolecrud6Admin = Role::where('slug', 'crud6-admin')->first();
        if ($rolecrud6Admin !== null) {
            // Collect all permission IDs for crud6-admin
            $permissionIds = [];
            foreach ($permissions as $permission) {
                $permissionIds[] = $permission->id;
            }
            
            // Sync all CRUD6 permissions to crud6-admin role
            $rolecrud6Admin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
