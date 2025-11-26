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
 *
 * @author Alexander Weissman
 */
return [
    'CRUD6' => [
    // User model translations
    'USER' => [
        // Numeric keys for singular/plural (UserFrosting i18n pattern)
        1 => 'User',
        2 => 'Users',
        
        // Named keys for backwards compatibility
        'SINGULAR'  => 'User',
        'PLURAL'    => 'Users',
        
        // Page titles
        'PAGE'             => 'Users',
        'PAGE_DESCRIPTION' => 'A listing of users for your site. Provides management tools for editing and deleting users.',
        
        // User actions
        'DISABLE'         => 'Disable User',
        'DISABLE_CONFIRM' => 'Are you sure you want to disable user <strong>{{name}}</strong>?',
        'ENABLE'          => 'Enable User',
        'ENABLE_CONFIRM'  => 'Are you sure you want to enable user <strong>{{name}}</strong>?',
        
        // Admin-specific actions
        'ADMIN' => [
            'TOGGLE_ENABLED'        => 'Toggle Enabled',
            'TOGGLE_VERIFIED'       => 'Toggle Verified',
            'SEND_PASSWORD_RESET'   => 'Send Password Reset',
            'PASSWORD_RESET_CONFIRM' => 'Are you sure you want to send a password reset link to <strong>{{name}}</strong>?',
            'CHANGE_PASSWORD'       => 'Change Password',
            'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{name}}</strong>?',
        ],
    ],
    
    // Group model translations
    'GROUP' => [
        // Numeric keys for singular/plural
        1 => 'Group',
        2 => 'Groups',
        
        // Named keys for backwards compatibility
        'SINGULAR'  => 'Group',
        'PLURAL'    => 'Groups',
        
        // Page titles
        'PAGE'             => 'Groups',
        'PAGE_DESCRIPTION' => 'A listing of groups for your site. Provides management tools for editing and deleting groups.',
        
        // Related entities
        'USERS' => 'Users in this group',
    ],
    
    // Role model translations
    'ROLE' => [
        // Numeric keys for singular/plural
        1 => 'Role',
        2 => 'Roles',
        
        // Named keys for backwards compatibility
        'SINGULAR'  => 'Role',
        'PLURAL'    => 'Roles',
        
        // Page titles
        'PAGE'             => 'Roles',
        'PAGE_DESCRIPTION' => 'A listing of roles for your site. Provides management tools for editing and deleting roles.',
        
        // Related entities
        'PERMISSIONS' => 'Permissions assigned to this role',
        'USERS'       => 'Users with this role',
    ],
    
    // Permission model translations
    'PERMISSION' => [
        // Numeric keys for singular/plural
        1 => 'Permission',
        2 => 'Permissions',
        
        // Named keys for backwards compatibility
        'SINGULAR'  => 'Permission',
        'PLURAL'    => 'Permissions',
        
        // Page titles
        'PAGE'             => 'Permissions',
        'PAGE_DESCRIPTION' => 'A listing of permissions for your site. Provides management tools for editing and deleting permissions.',
        
        // Related entities
        'USERS' => 'Users with this permission',
    ],
    
    // Activity model translations
    'ACTIVITY' => [
        // Numeric keys for singular/plural
        1 => 'Activity',
        2 => 'Activities',
        
        // Named keys for backwards compatibility
        'SINGULAR'  => 'Activity',
        'PLURAL'    => 'Activities',
        
        // Page titles
        'PAGE'             => 'Activities',
        'PAGE_DESCRIPTION' => 'A listing of user activities for your site. Provides a log of user actions and events.',
    ],

];