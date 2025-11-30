<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller\Traits;

use UserFrosting\Sprinkle\Account\Authenticate\Hasher;

/**
 * Provides password hashing functionality for CRUD6 controllers.
 * 
 * This trait consolidates password hashing logic that was previously duplicated
 * in CreateAction and EditAction. Controllers using this trait must have the
 * Hasher service injected.
 * 
 * Controllers using this trait must have:
 * - $hasher: Hasher - UserFrosting's password hashing service
 * 
 * And should implement (for logging):
 * - debugLog(string $message, array $context = []): void
 * 
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\CreateAction
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\EditAction
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\UpdateFieldAction
 */
trait HashesPasswords
{
    /**
     * Hash password fields in the data.
     * 
     * Iterates through schema fields and hashes any field with type 'password'
     * using UserFrosting's Hasher service before storing to database.
     * Only hashes non-empty password values to support optional password updates.
     * 
     * @param array $schema The schema configuration containing field definitions
     * @param array $data   The input data that may contain password fields
     * 
     * @return array The data with password fields hashed
     */
    protected function hashPasswordFields(array $schema, array $data): array
    {
        $fields = $schema['fields'] ?? [];
        
        foreach ($fields as $fieldName => $fieldConfig) {
            // Check if field is a password type and has a non-empty value in the data
            if (($fieldConfig['type'] ?? '') === 'password' && isset($data[$fieldName]) && !empty($data[$fieldName])) {
                // Hash the password using UserFrosting's Hasher service
                $data[$fieldName] = $this->hasher->hash($data[$fieldName]);
                
                // Log if debugLog method exists (optional dependency)
                if (method_exists($this, 'debugLog')) {
                    $this->debugLog("CRUD6 [HashesPasswords] Password field hashed", [
                        'field' => $fieldName,
                    ]);
                }
            }
        }
        
        return $data;
    }

    /**
     * Hash a single password value.
     * 
     * Convenience method for hashing a single password value without needing
     * the full schema context.
     * 
     * @param string $password The plain text password to hash
     * 
     * @return string The hashed password
     */
    protected function hashPassword(string $password): string
    {
        return $this->hasher->hash($password);
    }
}
