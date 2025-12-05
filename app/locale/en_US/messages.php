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
        '@TRANSLATION' => 'CRUD6',
        1 => 'CRUD6',
        2 => 'CRUD6 All Rows',

        'CREATE' => [
            '@TRANSLATION'  => 'Create {{model}}',
            0 => 'Create {{model}}',
            'SUCCESS'       => 'Successfully created {{model}}',
            'SUCCESS_TITLE' => 'Created!',
            'ERROR'         => 'Failed to create {{model}}',
            'ERROR_TITLE'   => 'Error Creating',
        ],
        'CREATION_SUCCESSFUL' => 'Successfully created {{model}} <strong>{{name}}</strong>',
        'DELETE' => [
            '@TRANSLATION'  => 'Delete {{model}}',
            0 => 'Delete {{model}}',
            'SUCCESS'       => 'Successfully deleted {{model}}',
            'SUCCESS_TITLE' => 'Deleted!',
            'ERROR'         => 'Failed to delete {{model}}',
            'ERROR_TITLE'   => 'Error Deleting',
        ],
        'DELETE_CONFIRM'      => 'Are you sure you want to delete the row from {{model}} ?',
        'DELETE_DEFAULT'      => "You can't delete the {{model}} <strong>{{id}}</strong> because it is the default {{model}} for newly registered users.",
        'DELETE_YES'          => 'Yes, delete {{model}}',
        'DELETION_SUCCESSFUL' => 'Successfully deleted {{model}} <strong>{{name}}</strong>',
        'EDIT' => [
            '@TRANSLATION' => 'Edit {{model}}',
            0 => 'Edit {{model}}',
            'SUCCESS' => 'Retrieved {{model}} for editing',
            'ERROR'   => 'Failed to retrieve {{model}}',
        ],
        'EXCEPTION'           => '{{model}} error',
        'ICON'                => '{{model}} icon',
        'ICON_EXPLAIN'        => 'Icon for {{model}} members',
        'INFO_PAGE'           => 'View and edit {{model}} details.',
        'NAME'                => '{{model}} name',
        'NAME_IN_USE'         => 'A {{model}} named <strong>{{id}}</strong> already exist',
        'NAME_EXPLAIN'        => 'Please enter a name for the {{model}}',
        'NONE'                => 'No {{model}}',
        'NOT_EMPTY'           => "You can't do that because there are still users associated with the {{model}} <strong>{{id}}</strong>.",
        'NOT_FOUND'           => '{{model}} not found',
        'PAGE'                => '{{model}}',
        'PAGE_DESCRIPTION'    => 'A listing of the {{model}} for your site.  Provides management tools for editing and deleting {{model}}.',
        'UPDATE' => [
            '@TRANSLATION'  => 'Update {{model}}',
            0 => 'Details updated for {{model}} <strong>{{id}}</strong>',
            'SUCCESS'       => 'Successfully updated {{model}}',
            'SUCCESS_TITLE' => 'Updated!',
            'ERROR'         => 'Failed to update {{model}}',
            'ERROR_TITLE'   => 'Error Updating',
        ],
        'UPDATE_FIELD_SUCCESSFUL' => 'Successfully updated {{field}} for {{model}}',
        'TOGGLE_CONFIRM' => 'Are you sure you want to toggle <strong>{{field}}</strong> for <strong>{{title}}</strong>?',
        'TOGGLE_SUCCESS' => 'Successfully toggled {{field}}',
        'RELATIONSHIP' => [
            '@TRANSLATION'  => 'Relationships',
            'ATTACH_SUCCESS' => 'Successfully attached {{count}} {{relation}} to {{model}}',
            'DETACH_SUCCESS' => 'Successfully detached {{count}} {{relation}} from {{model}}',
        ],

        // Panel/Breadcrumb translations (nested under CRUD6 to match dot notation)
        'ADMIN_PANEL' => 'CRUD6 Admin Panel',

        // Validation translations used in forms and modals (nested under CRUD6 for proper scoping)
        'VALIDATION' => [
            'ENTER_VALUE'         => 'Enter value',
            'CONFIRM'             => 'Confirm',
            'CONFIRM_PLACEHOLDER' => 'Confirm value',
            'MIN_LENGTH_HINT'     => 'Minimum {{min}} characters',
            'MATCH_HINT'          => 'Values must match',
            'FIELDS_MUST_MATCH'   => 'Fields must match',
            'MIN_LENGTH'          => 'Minimum {{min}} characters required',
        ],

    ],

    // Panel/Breadcrumb translations (flat keys for backward compatibility)
    'CRUD6_PANEL'               => 'CRUD6 Management',
    'C6ADMIN_PANEL'             => 'CRUD6 Admin Panel',
    'CRUD6_ADMIN_PANEL'         => 'CRUD6 Admin Panel',  // Fallback for CRUD6.ADMIN_PANEL
];
