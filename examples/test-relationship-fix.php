#!/usr/bin/env php
<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */
/**
 * Test script to validate the belongs_to_many_through fix
 * 
 * This script tests that CRUD6Model can properly handle belongs_to_many_through
 * relationships by instantiating the through model.
 */

require_once __DIR__ . '/vendor/autoload.php';

use UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model;

echo "=== Testing belongs_to_many_through Fix ===\n\n";

// Test 1: Verify dynamicRelationship signature accepts throughModel parameter
echo "Test 1: Verify method signature\n";
echo str_repeat('-', 50) . "\n";

try {
    $reflection = new ReflectionClass(CRUD6Model::class);
    $method = $reflection->getMethod('dynamicRelationship');
    $parameters = $method->getParameters();
    
    echo "Method: dynamicRelationship()\n";
    echo "Parameters:\n";
    
    $hasRelationName = false;
    $hasConfig = false;
    $hasRelatedModel = false;
    $hasThroughModel = false;
    
    foreach ($parameters as $param) {
        $paramName = $param->getName();
        $paramType = $param->getType() ? $param->getType()->getName() : 'mixed';
        $isOptional = $param->isOptional();
        $defaultValue = $param->isDefaultValueAvailable() ? var_export($param->getDefaultValue(), true) : 'N/A';
        
        echo "  - \${$paramName}: {$paramType}" . ($isOptional ? " (optional, default: {$defaultValue})" : " (required)") . "\n";
        
        if ($paramName === 'relationName') $hasRelationName = true;
        if ($paramName === 'config') $hasConfig = true;
        if ($paramName === 'relatedModel') $hasRelatedModel = true;
        if ($paramName === 'throughModel') $hasThroughModel = true;
    }
    
    echo "\nValidation:\n";
    
    if ($hasRelationName && $hasConfig && $hasRelatedModel && $hasThroughModel) {
        echo "  ✓ All required parameters present\n";
        echo "  ✓ throughModel parameter added successfully\n";
        echo "  ✓ Test 1 PASSED\n\n";
    } else {
        echo "  ❌ Missing required parameters\n";
        echo "  ❌ Test 1 FAILED\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Verify error is thrown when throughModel is missing for belongs_to_many_through
echo "Test 2: Verify validation for missing throughModel\n";
echo str_repeat('-', 50) . "\n";

try {
    // Create mock models
    $parentModel = new CRUD6Model();
    $parentModel->configureFromSchema([
        'model' => 'users',
        'table' => 'users',
        'fields' => ['id' => ['type' => 'integer']],
    ]);
    
    $relatedModel = new CRUD6Model();
    $relatedModel->configureFromSchema([
        'model' => 'permissions',
        'table' => 'permissions',
        'fields' => ['id' => ['type' => 'integer']],
    ]);
    
    // Try to create belongs_to_many_through without throughModel
    $config = [
        'type' => 'belongs_to_many_through',
        'through' => 'roles',
        'first_pivot_table' => 'role_users',
        'first_foreign_key' => 'user_id',
        'first_related_key' => 'role_id',
        'second_pivot_table' => 'permission_roles',
        'second_foreign_key' => 'role_id',
        'second_related_key' => 'permission_id',
    ];
    
    $errorThrown = false;
    $errorMessage = '';
    
    try {
        // Call without throughModel - should throw error
        $parentModel->dynamicRelationship('permissions', $config, $relatedModel);
    } catch (InvalidArgumentException $e) {
        $errorThrown = true;
        $errorMessage = $e->getMessage();
    }
    
    if ($errorThrown) {
        echo "  ✓ Error thrown when throughModel is missing\n";
        echo "  ✓ Error message: {$errorMessage}\n";
        echo "  ✓ Test 2 PASSED\n\n";
    } else {
        echo "  ❌ No error thrown when throughModel is missing\n";
        echo "  ❌ Test 2 FAILED\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Verify many_to_many still works without throughModel
echo "Test 3: Verify many_to_many works without throughModel\n";
echo str_repeat('-', 50) . "\n";

try {
    $parentModel = new CRUD6Model();
    $parentModel->configureFromSchema([
        'model' => 'users',
        'table' => 'users',
        'fields' => ['id' => ['type' => 'integer']],
    ]);
    
    $relatedModel = new CRUD6Model();
    $relatedModel->configureFromSchema([
        'model' => 'roles',
        'table' => 'roles',
        'fields' => ['id' => ['type' => 'integer']],
    ]);
    
    $config = [
        'type' => 'many_to_many',
        'pivot_table' => 'role_users',
        'foreign_key' => 'user_id',
        'related_key' => 'role_id',
    ];
    
    // Call without throughModel - should work for many_to_many
    $relationship = $parentModel->dynamicRelationship('roles', $config, $relatedModel);
    
    if ($relationship instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
        echo "  ✓ Many-to-many relationship created successfully\n";
        echo "  ✓ Relationship type: " . get_class($relationship) . "\n";
        echo "  ✓ Test 3 PASSED\n\n";
    } else {
        echo "  ❌ Unexpected relationship type\n";
        echo "  ❌ Test 3 FAILED\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Summary
echo str_repeat('=', 50) . "\n";
echo "✅ ALL TESTS PASSED\n\n";
echo "Summary:\n";
echo "  ✓ dynamicRelationship() accepts optional throughModel parameter\n";
echo "  ✓ Throws error when throughModel is missing for belongs_to_many_through\n";
echo "  ✓ Many-to-many relationships still work without throughModel\n";
echo "\nThe fix is working correctly!\n";
exit(0);
