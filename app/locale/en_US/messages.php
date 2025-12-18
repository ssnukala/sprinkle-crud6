<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * US English message token translations for the 'crud6' sprinkle.
 * 
 * This file contains both:
 * 1. Base CRUD6 translations (manually maintained)
 * 2. Schema-specific model translations (auto-generated from schemas)
 * 
 * To regenerate schema translations: php scripts/generate-test-schemas.php
 * Note: Base translations are preserved during regeneration.
 *
 * @author Alexander Weissman
 */
return array (
  'CRUD6' => 
  array (
    '@TRANSLATION' => 'CRUD6',
    0 => 'CRUD6',
    1 => 'CRUD6 All Rows',
    'CREATE' => 
    array (
      '@TRANSLATION' => 'Create {{model}}',
      0 => 'Create {{model}}',
      'SUCCESS' => 'Successfully created {{model}}',
      'SUCCESS_TITLE' => 'Created!',
      'ERROR' => 'Failed to create {{model}}',
      'ERROR_TITLE' => 'Error Creating',
    ),
    'CREATION_SUCCESSFUL' => 'Successfully created {{model}} <strong>{{name}}</strong>',
    'DELETE' => 
    array (
      '@TRANSLATION' => 'Delete {{model}}',
      0 => 'Delete {{model}}',
      'SUCCESS' => 'Successfully deleted {{model}}',
      'SUCCESS_TITLE' => 'Deleted!',
      'ERROR' => 'Failed to delete {{model}}',
      'ERROR_TITLE' => 'Error Deleting',
    ),
    'DELETE_CONFIRM' => 'Are you sure you want to delete the row from {{model}} ?',
    'DELETE_DEFAULT' => 'You can\'t delete the {{model}} <strong>{{id}}</strong> because it is the default {{model}} for newly registered users.',
    'DELETE_YES' => 'Yes, delete {{model}}',
    'DELETION_SUCCESSFUL' => 'Successfully deleted {{model}} <strong>{{name}}</strong>',
    'EDIT' => 
    array (
      '@TRANSLATION' => 'Edit {{model}}',
      0 => 'Edit {{model}}',
      'SUCCESS' => 'Retrieved {{model}} for editing',
      'ERROR' => 'Failed to retrieve {{model}}',
    ),
    'EXCEPTION' => '{{model}} error',
    'ICON' => '{{model}} icon',
    'ICON_EXPLAIN' => 'Icon for {{model}} members',
    'INFO_PAGE' => 'View and edit {{model}} details.',
    'NAME' => '{{model}} name',
    'NAME_IN_USE' => 'A {{model}} named <strong>{{id}}</strong> already exist',
    'NAME_EXPLAIN' => 'Please enter a name for the {{model}}',
    'NONE' => 'No {{model}}',
    'NOT_EMPTY' => 'You can\'t do that because there are still users associated with the {{model}} <strong>{{id}}</strong>.',
    'NOT_FOUND' => '{{model}} not found',
    'PAGE' => '{{model}}',
    'PAGE_DESCRIPTION' => 'A listing of the {{model}} for your site.  Provides management tools for editing and deleting {{model}}.',
    'UPDATE' => 
    array (
      '@TRANSLATION' => 'Update {{model}}',
      0 => 'Details updated for {{model}} <strong>{{id}}</strong>',
      'SUCCESS' => 'Successfully updated {{model}}',
      'SUCCESS_TITLE' => 'Updated!',
      'ERROR' => 'Failed to update {{model}}',
      'ERROR_TITLE' => 'Error Updating',
    ),
    'UPDATE_FIELD_SUCCESSFUL' => 'Successfully updated {{field}} for {{model}}',
    'TOGGLE_CONFIRM' => 'Are you sure you want to toggle <strong>{{field}}</strong> for <strong>{{title}}</strong>?',
    'TOGGLE_SUCCESS' => 'Successfully toggled {{field}}',
    'RELATIONSHIP' => 
    array (
      '@TRANSLATION' => 'Relationships',
      'ATTACH_SUCCESS' => 'Successfully attached {{count}} {{relation}} to {{model}}',
      'DETACH_SUCCESS' => 'Successfully detached {{count}} {{relation}} from {{model}}',
    ),
    'ADMIN_PANEL' => 'CRUD6 Admin Panel',
    'VALIDATION' => 
    array (
      'ENTER_VALUE' => 'Enter value',
      'CONFIRM' => 'Confirm',
      'CONFIRM_PLACEHOLDER' => 'Confirm value',
      'MIN_LENGTH_HINT' => 'Minimum {{min}} characters',
      'MATCH_HINT' => 'Values must match',
      'FIELDS_MUST_MATCH' => 'Fields must match',
      'MIN_LENGTH' => 'Minimum {{min}} characters required',
    ),
    'ACTION' => 
    array (
      'SUCCESS' => 'Action completed successfully.',
      'SUCCESS_TITLE' => 'Success',
      'CANNOT_UNDO' => 'This action cannot be undone.',
    ),
    'API' => 
    array (
      'SUCCESS' => 'Retrieved {{model}} schema successfully',
    ),
    'USERS' => 
    array (
      1 => 'Users',
      2 => 'Userss',
      'PAGE' => 'Userss',
      'PAGE_DESCRIPTION' => 'A listing of users for your site',
      'ID' => 'Id',
      'USER_NAME' => 'User Name',
      'FIRST_NAME' => 'First Name',
      'LAST_NAME' => 'Last Name',
      'EMAIL' => 'Email',
      'PASSWORD' => 'Password',
      'FLAG_ENABLED' => 'Flag Enabled',
      'CREATED_AT' => 'Created At',
      'UPDATED_AT' => 'Updated At',
    ),
    'GROUPS' => 
    array (
      1 => 'Groups',
      2 => 'Groupss',
      'PAGE' => 'Groupss',
      'PAGE_DESCRIPTION' => 'A listing of groups for your site',
      'ID' => 'Id',
      'SLUG' => 'Slug',
      'NAME' => 'Name',
      'DESCRIPTION' => 'Description',
      'ICON' => 'Icon',
      'CREATED_AT' => 'Created At',
      'UPDATED_AT' => 'Updated At',
    ),
    'PRODUCTS' => 
    array (
      1 => 'Products',
      2 => 'Productss',
      'PAGE' => 'Productss',
      'PAGE_DESCRIPTION' => 'A listing of products for your site',
      'ID' => 'Id',
      'SKU' => 'Sku',
      'NAME' => 'Name',
      'DESCRIPTION' => 'Description',
      'PRICE' => 'Price',
      'QUANTITY' => 'Quantity',
      'ACTIVE' => 'Active',
      'CREATED_AT' => 'Created At',
      'UPDATED_AT' => 'Updated At',
    ),
    'ROLES' => 
    array (
      1 => 'Roles',
      2 => 'Roless',
      'PAGE' => 'Roless',
      'PAGE_DESCRIPTION' => 'A listing of roles for your site',
      'ID' => 'Id',
      'SLUG' => 'Slug',
      'NAME' => 'Name',
      'DESCRIPTION' => 'Description',
      'CREATED_AT' => 'Created At',
      'UPDATED_AT' => 'Updated At',
    ),
    'PERMISSIONS' => 
    array (
      1 => 'Permissions',
      2 => 'Permissionss',
      'PAGE' => 'Permissionss',
      'PAGE_DESCRIPTION' => 'A listing of permissions for your site',
      'ID' => 'Id',
      'SLUG' => 'Slug',
      'NAME' => 'Name',
      'DESCRIPTION' => 'Description',
      'CONDITIONS' => 'Conditions',
      'CREATED_AT' => 'Created At',
      'UPDATED_AT' => 'Updated At',
    ),
    'ACTIVITIES' => 
    array (
      1 => 'Activities',
      2 => 'Activitiess',
      'PAGE' => 'Activitiess',
      'PAGE_DESCRIPTION' => 'A listing of activities for your site',
      'ID' => 'Id',
      'USER_ID' => 'User Id',
      'TYPE' => 'Type',
      'OCCURRED_AT' => 'Occurred At',
      'IP_ADDRESS' => 'Ip Address',
      'DESCRIPTION' => 'Description',
    ),
  ),
);
