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
        1 => 'CRUD6',
        2 => 'CRUD6 All Rows',

        'CREATE' => [
            0 => 'Create {{model}}',
            'SUCCESS'       => 'Successfully created {{model}}',
            'SUCCESS_TITLE' => 'Created!',
            'ERROR'         => 'Failed to create {{model}}',
            'ERROR_TITLE'   => 'Error Creating',
        ],
        'CREATION_SUCCESSFUL' => 'Successfully created {{model}} <strong>{{name}}</strong>',
        'DELETE' => [
            0 => 'Delete {{model}}',
            'SUCCESS'       => 'Successfully deleted {{model}}',
            'SUCCESS_TITLE' => 'Deleted!',
            'ERROR'         => 'Failed to delete {{model}}',
            'ERROR_TITLE'   => 'Error Deleting',
        ],
        'DELETE_CONFIRM'      => 'Are you sure you want to delete the {{model}} <strong>{{name}}</strong>?',
        'DELETE_DEFAULT'      => "You can't delete the {{model}} <strong>{{name}}</strong> because it is the default {{model}} for newly registered users.",
        'DELETE_YES'          => 'Yes, delete {{model}}',
        'DELETION_SUCCESSFUL' => 'Successfully deleted {{model}} <strong>{{name}}</strong>',
        'EDIT' => [
            0 => 'Edit {{model}}',
            'SUCCESS' => 'Retrieved {{model}} for editing',
            'ERROR'   => 'Failed to retrieve {{model}}',
        ],
        'EXCEPTION'           => '{{model}} error',
        'ICON'                => '{{model}} icon',
        'ICON_EXPLAIN'        => 'Icon for {{model}} members',
        'INFO_PAGE'           => 'View and edit {{model}} details.',
        'NAME'                => '{{model}} name',
        'NAME_IN_USE'         => 'A {{model}} named <strong>{{name}}</strong> already exist',
        'NAME_EXPLAIN'        => 'Please enter a name for the {{model}}',
        'NONE'                => 'No {{model}}',
        'NOT_EMPTY'           => "You can't do that because there are still users associated with the {{model}} <strong>{{name}}</strong>.",
        'NOT_FOUND'           => '{{model}} not found',
        'PAGE'                => '{{model}}',
        'PAGE_DESCRIPTION'    => 'A listing of the {{model}} for your site.  Provides management tools for editing and deleting {{model}}.',
        'UPDATE' => [
            0 => 'Details updated for {{model}} <strong>{{name}}</strong>',
            'SUCCESS'       => 'Successfully updated {{model}}',
            'SUCCESS_TITLE' => 'Updated!',
            'ERROR'         => 'Failed to update {{model}}',
            'ERROR_TITLE'   => 'Error Updating',
        ],
        'UPDATE_FIELD_SUCCESSFUL' => 'Successfully updated {{field}} for {{model}}',
        'RELATIONSHIP' => [
            'ATTACH_SUCCESS' => 'Successfully attached {{count}} {{relation}} to {{model}}',
            'DETACH_SUCCESS' => 'Successfully detached {{count}} {{relation}} from {{model}}',
        ],
        'USERS'               => 'Users in this {{model}}',
        
        // Custom Actions
        'ACTION' => [
            'SUCCESS'            => '{{action}} completed successfully',
            'TOGGLE_ENABLED'     => 'Toggle Enabled',
            'TOGGLE_VERIFIED'    => 'Toggle Verified',
            'CHANGE_PASSWORD'    => 'Change Password',
            'RESET_PASSWORD'     => 'Reset Password',
            'DISABLE_USER'       => 'Disable User',
            'ENABLE_USER'        => 'Enable User',
        ],
    ],
    'CRUD6_PANEL'               => 'CRUD6 Management',
    
    // Model names - singular forms
    'USER' => [
        'SINGULAR'  => 'User',
        'PLURAL'    => 'Users',
    ],
    'GROUP' => [
        'SINGULAR'  => 'Group',
        'PLURAL'    => 'Groups',
    ],
    'ROLE' => [
        'SINGULAR'  => 'Role',
        'PLURAL'    => 'Roles',
    ],
    'PERMISSION' => [
        'SINGULAR'  => 'Permission',
        'PLURAL'    => 'Permissions',
    ],
    'ACTIVITY' => [
        'SINGULAR'  => 'Activity',
        'PLURAL'    => 'Activities',
    ],

];
