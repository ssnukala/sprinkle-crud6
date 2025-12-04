<?php

/*
 * Example locale file showing proper nested translation usage
 * 
 * This file demonstrates how to use the {{&KEY}} syntax for nested translations
 * in confirmation messages and other translatable strings.
 * 
 * Place this in your sprinkle at: app/locale/en_US/messages.php
 */

return [
    'EXAMPLE' => [
        'USER' => [
            // Action labels
            'DISABLE' => 'Disable User',
            'ENABLE' => 'Enable User',
            'CHANGE_PASSWORD' => 'Change Password',
            'RESET_PASSWORD' => 'Reset Password',
            'DELETE_PERMANENT' => 'Delete Permanently',
            
            // Confirmation messages using nested translation syntax {{&KEY}}
            
            // Example 1: Using {{&ACTION.CANNOT_UNDO}} for standard warning
            'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',
            
            // Example 2: No nested translation (warning handled by modal_config.warning: "")
            'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Example 3: Input modal confirmation (no warning in message, handled separately)
            'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Example 4: Using custom warning key in message
            'PASSWORD_RESET_CONFIRM' => 'Send password reset link to <strong>{{email}}</strong>?<br/>{{&EXAMPLE.USER.PASSWORD_RESET_INFO}}',
            'PASSWORD_RESET_INFO' => 'The user will receive an email with instructions to reset their password.',
            
            // Example 5: Custom warning referenced via modal_config.warning
            'PASSWORD_RESET_WARNING' => 'A password reset email will be sent to the user immediately.',
            
            // Example 6: Permanent delete with custom warning
            'DELETE_PERMANENT_CONFIRM' => 'Are you sure you want to permanently delete <strong>{{first_name}} {{last_name}}</strong>?',
            'DELETE_PERMANENT_WARNING' => 'This action is permanent and cannot be reversed! The user and all associated data will be deleted.',
            
            // Field labels
            'ID' => 'User ID',
            'USERNAME' => 'Username',
            'FIRST_NAME' => 'First Name',
            'LAST_NAME' => 'Last Name',
            'EMAIL' => 'Email Address',
            'PASSWORD' => 'Password',
            'ENABLED' => 'Enabled',
        ],
    ],
    
    // Standard action messages (available in CRUD6 sprinkle)
    // These can be referenced using {{&ACTION.CANNOT_UNDO}}
    'ACTION' => [
        'CANNOT_UNDO' => 'This action cannot be undone.',
        'PERMANENT_WARNING' => 'This action is permanent and cannot be reversed.',
        'CONFIRM_ACTION' => 'Please confirm to proceed.',
    ],
    
    // Standard validation messages (used by ActionModal automatically)
    // These are automatically applied to input fields
    'VALIDATION' => [
        'ENTER_VALUE' => 'Enter value',
        'CONFIRM' => 'Confirm',
        'CONFIRM_PLACEHOLDER' => 'Confirm value',
        'MIN_LENGTH_HINT' => 'Minimum {{min}} characters',
        'MATCH_HINT' => 'Values must match',
        'FIELDS_MUST_MATCH' => 'Fields must match',
        'MIN_LENGTH' => 'Minimum {{min}} characters required',
    ],
];
