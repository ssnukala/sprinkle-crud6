<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud6
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * US English message token translations for the 'crud6' sprinkle.
 * Model-specific translations for CRUD6 testing.
 * 
 * These translations are copied from sprinkle-c6admin for testing purposes.
 * Schema files in examples/schema use these translation keys.
 *
 * @author Alexander Weissman
 * @author Srinivas Nukala
 */
return [
    'CRUD6' => [
        // Activity model translations
        'ACTIVITY' => [
            1 => 'Activity',
            2 => 'Activities',
            'LAST'             => 'Last Activity',
            'LATEST'           => 'Latest Activities',
            'PAGE'             => 'Activities',
            'PAGE_DESCRIPTION' => 'A listing of user activities',
            'TIME'             => 'Activity Time',

            // Field labels from activities.json
            'ID'          => 'Activity ID',
            'IP_ADDRESS'  => 'IP Address',
            'USER_ID'     => 'User ID',
            'TYPE'        => 'Activity Type',
            'OCCURRED_AT' => 'Occurred At',
            'DESCRIPTION' => 'Description',
        ],

        // Group model translations
        'GROUP' => [
            1 => 'Group',
            2 => 'Groups',

            'CREATE'              => 'Create group',
            'CREATION_SUCCESSFUL' => 'Successfully created group <strong>{{name}}</strong>',
            'DELETE'              => 'Delete group',
            'DELETE_CONFIRM'      => 'Are you sure you want to delete the group <strong>{{name}}</strong>?',
            'DELETE_DEFAULT'      => "You can't delete the group <strong>{{name}}</strong> because it is the default group for newly registered users.",
            'DELETE_YES'          => 'Yes, delete group',
            'DELETION_SUCCESSFUL' => 'Successfully deleted group <strong>{{name}}</strong>',
            'EDIT'                => 'Edit group',
            'EXCEPTION'           => 'Group error',
            'ICON'                => 'Group Icon',
            'ICON_EXPLAIN'        => 'Icon for group members',
            'INFO_PAGE'           => 'View and edit group details.',
            'NAME'                => 'Group Name',
            'NAME_IN_USE'         => 'A group named <strong>{{name}}</strong> already exists',
            'NAME_EXPLAIN'        => 'Please enter a name for the group',
            'NONE'                => 'No group',
            'NOT_EMPTY'           => "You can't do that because there are still users associated with the group <strong>{{name}}</strong>.",
            'NOT_FOUND'           => 'Group not found',
            'PAGE'                => 'Groups',
            'PAGE_DESCRIPTION'    => 'A listing of the groups for your site. Provides management tools for editing and deleting groups.',
            'UPDATE'              => 'Details updated for group <strong>{{name}}</strong>',
            'USERS'               => 'Users in this group',

            // Field labels from groups.json
            'ID'          => 'Group ID',
            'SLUG'        => 'Group Slug',
            'DESCRIPTION' => 'Description',
            'ICON_LABEL'  => 'Icon',
            'CREATED_AT'  => 'Created At',
            'UPDATED_AT'  => 'Updated At',
        ],

        // Permission model translations
        'PERMISSION' => [
            1 => 'Permission',
            2 => 'Permissions',

            'ASSIGN'            => [
                '@TRANSLATION' => 'Assign permissions',
                'EXPLAIN'      => 'The selected permissions will be assigned to the role.',
            ],
            'HOOK_CONDITION'    => 'Hook/Conditions',
            'ID'                => 'Permission ID',
            'INFO_PAGE'         => 'View and edit permission details.',
            'NOT_FOUND'         => 'Permission not found',
            'PAGE'              => 'Permissions',
            'PAGE_DESCRIPTION'  => 'A listing of the permissions for your site. Provides management tools for editing and deleting permissions.',
            'UPDATE'            => 'Update permissions',
            'USERS'             => 'Users with this permission',
            'VIA_ROLES'         => 'Has permission via roles',

            // Field labels from permissions.json
            'SLUG'        => 'Permission Slug',
            'NAME'        => 'Permission Name',
            'CONDITIONS'  => 'Conditions',
            'DESCRIPTION' => 'Description',
            'ROLES'       => 'Roles',
            'ROLE_IDS'    => 'Roles',
            'CREATED_AT'  => 'Created At',
            'UPDATED_AT'  => 'Updated At',
        ],

        // Role model translations
        'ROLE' => [
            1 => 'Role',
            2 => 'Roles',

            'CREATE'              => 'Create role',
            'CREATION_SUCCESSFUL' => 'Successfully created role <strong>{{name}}</strong>',
            'DELETE'              => 'Delete role',
            'DELETE_CONFIRM'      => 'Are you sure you want to delete the role <strong>{{name}}</strong>?',
            'DELETE_DEFAULT'      => "You can't delete the role <strong>{{name}}</strong> because it is a default role for newly registered users.",
            'DELETE_YES'          => 'Yes, delete role',
            'DELETION_SUCCESSFUL' => 'Successfully deleted role <strong>{{name}}</strong>',
            'EDIT'                => 'Edit role',
            'EXCEPTION'           => 'Role error',
            'HAS_USERS'           => "You can't do that because there are still users who have the role <strong>{{name}}</strong>.",
            'INFO_PAGE'           => 'View and edit role details.',
            'MANAGE'              => 'Manage Roles',
            'MANAGE_EXPLAIN'      => 'The selected roles will be assigned to the user.',
            'NAME'                => 'Name',
            'NAME_EXPLAIN'        => 'Please enter a name for the role',
            'NAME_IN_USE'         => 'A role named <strong>{{name}}</strong> already exists',
            'NOT_FOUND'           => 'Role not found',
            'PAGE'                => 'Roles',
            'PAGE_DESCRIPTION'    => 'A listing of the roles for your site. Provides management tools for editing and deleting roles.',
            'PERMISSIONS'         => 'Role permissions',
            'PERMISSIONS_UPDATED' => 'Permissions updated for role <strong>{{name}}</strong>',
            'UPDATE'              => 'Update Roles',
            'UPDATED'             => 'Details updated for role <strong>{{name}}</strong>',
            'USERS'               => 'Users with this role',

            // Field labels from roles.json
            'ID'              => 'Role ID',
            'SLUG'            => 'Role Slug',
            'DESCRIPTION'     => 'Description',
            'PERMISSION_IDS'  => 'Permissions',
            'CREATED_AT'      => 'Created At',
            'UPDATED_AT'      => 'Updated At',
        ],

        // User model translations
        'USER' => [
            1 => 'User',
            2 => 'Users',

            // Action button labels - these match the schema action label keys
            'TOGGLE_ENABLED'           => 'Toggle Enabled',
            'TOGGLE_VERIFIED'          => 'Toggle Verified',
            'RESET_PASSWORD'           => 'Send Password Reset',
            'CHANGE_PASSWORD'          => "Change User's Password",
            'DISABLE_USER'             => 'Disable User',
            'ENABLE_USER'              => 'Enable User',
            
            // Confirmation messages - these match the schema action confirm keys
            'DISABLE_CONFIRM'          => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            'ENABLE_CONFIRM'           => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Success messages
            'PASSWORD_RESET_SUCCESS'   => "<strong>{{first_name}} {{last_name}}</strong>'s password has been reset.",
            'TOGGLE_ENABLED_SUCCESS'   => 'User status updated successfully',
            'TOGGLE_VERIFIED_SUCCESS'  => 'User verification status updated successfully',
            
            // Admin-specific actions (nested under ADMIN for confirm messages in schema)
            'ADMIN' => [
                'PASSWORD_RESET_CONFIRM'   => 'Are you sure you want to send a password reset link to <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
                'PASSWORD_CHANGE_CONFIRM'  => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            ],
            
            // Legacy action labels (deprecated - use direct keys above)
            'ACTIVATE'                 => 'Activate user',
            'ACTIVATE_CONFIRM'         => 'Are you sure you want to activate <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            'CREATE'                   => 'Create user',
            'CREATED'                  => 'User <strong>{{user_name}}</strong> has been successfully created',
            'DELETE'                   => 'Delete user',
            'DELETE_CONFIRM'           => 'Are you sure you want to delete the user <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            'DELETED'                  => 'User deleted',
            'DISABLE'                  => 'Disable user',
            'EDIT'                     => 'Edit user',
            'ENABLE'                   => 'Enable user',
            'INFO_PAGE'                => 'View and edit user details.',
            'LATEST'                   => 'Latest Users',
            'PAGE'                     => 'Users',
            'PAGE_DESCRIPTION'         => 'A listing of the users for your site. Provides management tools including editing, activation and enabling/disabling accounts.',
            'VIEW_ALL'                 => 'View all users',

            // Field labels from users.json
            'ID'          => 'User ID',
            'USERNAME'    => 'Username',
            'FIRST_NAME'  => 'First Name',
            'LAST_NAME'   => 'Last Name',
            'EMAIL'       => 'Email Address',
            'LOCALE'      => 'Locale',
            'GROUP'       => 'Group',
            'VERIFIED'    => 'Verified',
            'ENABLED'     => 'Enabled',
            'ROLES'       => 'Roles',
            'PASSWORD'    => 'Password',
            'DELETED_AT'  => 'Deleted At',
            'CREATED_AT'  => 'Created At',
            'UPDATED_AT'  => 'Updated At',
            'ROLE_IDS'    => 'Roles',
        ],
    ],
];
