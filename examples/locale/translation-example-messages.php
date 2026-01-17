<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/*
 * Example locale file showing proper translation usage following UserFrosting 6 standards
 * 
 * This file demonstrates how to use specific field placeholders and WARNING_CANNOT_UNDONE
 * from UserFrosting 6 core for confirmation messages.
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
            
            // Confirmation messages using UF6 standards:
            // - Use specific field names as placeholders
            // - NO embedded warning messages
            // - Warnings handled by modal_config.warning
            
            // Example 1: Standard confirmation with default WARNING_CANNOT_UNDONE
            'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Example 2: Confirmation without warning (enabled via modal_config.warning: "")
            'ENABLE_CONFIRM' => 'Are you sure you want to enable <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Example 3: Input modal confirmation (no warning for input type by default)
            'PASSWORD_CHANGE_CONFIRM' => 'Are you sure you want to change the password for <strong>{{first_name}} {{last_name}} ({{user_name}})</strong>?',
            
            // Example 4: Informational message (not a warning)
            'PASSWORD_RESET_CONFIRM' => 'Send password reset link to <strong>{{email}}</strong>?',
            'PASSWORD_RESET_INFO' => 'The user will receive an email with instructions to reset their password.',
            
            // Example 5: Custom warning (set via modal_config.warning)
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
    
    // Note: WARNING_CANNOT_UNDONE is provided by UserFrosting 6 sprinkle-core
    // You do NOT need to define it in your sprinkle - it's available globally
    
    // Standard validation messages (used by ActionModal automatically)
    // These are defined in CRUD6 sprinkle
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
