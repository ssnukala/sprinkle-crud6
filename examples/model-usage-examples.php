<?php

/**
 * CRUD6 Generic Model Usage Examples
 * 
 * This file demonstrates how to use the new CRUD6Model to perform
 * database operations on any table without pre-defined model classes.
 */

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

// This file demonstrates CRUD6Model usage patterns
// Note: In actual usage, this would be loaded via UserFrosting's autoloader

/**
 * Example 1: Basic Model Configuration
 */
function example1_basicConfiguration()
{
    echo "=== Example 1: Basic Model Configuration ===\n";
    
    // Create a new generic model instance
    $model = new CRUD6Model();
    
    // Configure it for a specific table
    $model->setTable('users')
          ->setFillable(['user_name', 'email', 'first_name', 'last_name', 'is_active']);
    
    echo "Table: " . $model->getTable() . "\n";
    echo "Fillable: " . implode(', ', $model->getFillable()) . "\n";
    echo "\n";
}

/**
 * Example 2: Schema-Based Configuration
 */
function example2_schemaBasedConfiguration()
{
    echo "=== Example 2: Schema-Based Configuration ===\n";
    
    // Define a schema (this would normally come from JSON files)
    $userSchema = [
        'model' => 'users',
        'table' => 'users',
        'timestamps' => true,
        'soft_delete' => false,
        'fields' => [
            'id' => [
                'type' => 'integer',
                'auto_increment' => true,
                'readonly' => true
            ],
            'user_name' => [
                'type' => 'string',
                'required' => true
            ],
            'email' => [
                'type' => 'string',
                'required' => true
            ],
            'is_active' => [
                'type' => 'boolean',
                'default' => true
            ],
            'metadata' => [
                'type' => 'json'
            ],
            'created_at' => [
                'type' => 'datetime',
                'readonly' => true
            ],
            'updated_at' => [
                'type' => 'datetime',
                'readonly' => true
            ]
        ]
    ];
    
    // Create and configure model from schema
    $userModel = new CRUD6Model();
    $userModel->configureFromSchema($userSchema);
    
    echo "Model configured for table: " . $userModel->getTable() . "\n";
    echo "Timestamps enabled: " . ($userModel->timestamps ? 'Yes' : 'No') . "\n";
    echo "Fillable fields: " . implode(', ', $userModel->getFillable()) . "\n";
    echo "Model configured: " . ($userModel->getTable() !== 'CRUD6_NOT_SET' ? 'Yes' : 'No') . "\n";
    echo "\n";
}

/**
 * Example 3: Different Table Configurations
 */
function example3_differentTableConfigurations()
{
    echo "=== Example 3: Different Table Configurations ===\n";
    
    // Products table schema
    $productSchema = [
        'model' => 'products',
        'table' => 'products',
        'timestamps' => true,
        'soft_delete' => true, // Enable soft deletes
        'fields' => [
            'id' => ['type' => 'integer', 'auto_increment' => true],
            'name' => ['type' => 'string', 'required' => true],
            'price' => ['type' => 'decimal'],
            'is_active' => ['type' => 'boolean'],
            'metadata' => ['type' => 'json'],
            'deleted_at' => ['type' => 'datetime', 'nullable' => true]
        ]
    ];
    
    // Categories table schema  
    $categorySchema = [
        'model' => 'categories',
        'table' => 'categories',
        'timestamps' => false, // No timestamps
        'soft_delete' => false,
        'fields' => [
            'id' => ['type' => 'integer', 'auto_increment' => true],
            'name' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
            'description' => ['type' => 'text']
        ]
    ];
    
    // Configure product model
    $productModel = new CRUD6Model();
    $productModel->configureFromSchema($productSchema);
    
    // Configure category model
    $categoryModel = new CRUD6Model();
    $categoryModel->configureFromSchema($categorySchema);
    
    echo "Product Model:\n";
    echo "  Table: " . $productModel->getTable() . "\n";
    echo "  Timestamps: " . ($productModel->timestamps ? 'Yes' : 'No') . "\n";
    echo "  Soft Delete: " . (isset($productSchema['soft_delete']) && $productSchema['soft_delete'] ? 'Yes' : 'No') . "\n";
    echo "  Fillable: " . implode(', ', $productModel->getFillable()) . "\n";
    
    echo "\nCategory Model:\n";
    echo "  Table: " . $categoryModel->getTable() . "\n";
    echo "  Timestamps: " . ($categoryModel->timestamps ? 'Yes' : 'No') . "\n";
    echo "  Soft Delete: " . (isset($categorySchema['soft_delete']) && $categorySchema['soft_delete'] ? 'Yes' : 'No') . "\n";
    echo "  Fillable: " . implode(', ', $categoryModel->getFillable()) . "\n";
    echo "\n";
}

/**
 * Example 4: Using with SchemaService (Integration Example)
 */
function example4_schemaServiceIntegration()
{
    echo "=== Example 4: SchemaService Integration ===\n";
    
    echo "This example shows how the SchemaService can create\n";
    echo "configured model instances automatically:\n";
    echo "\n";
    echo "// Get configured model instance from SchemaService\n";
    echo "\$schemaService = new SchemaService(\$container);\n";
    echo "\$userModel = \$schemaService->getModelInstance('users');\n";
    echo "\n";
    echo "// The model is now ready to use with all configuration applied\n";
    echo "\$users = \$userModel->where('is_active', true)->get();\n";
    echo "\$newUser = \$userModel->create([\n";
    echo "    'user_name' => 'john_doe',\n";
    echo "    'email' => 'john@example.com',\n";
    echo "    'first_name' => 'John',\n";
    echo "    'last_name' => 'Doe'\n";
    echo "]);\n";
    echo "\n";
}

/**
 * Example 5: Eloquent ORM Operations
 */
function example5_eloquentOperations()
{
    echo "=== Example 5: Available Eloquent Operations ===\n";
    
    echo "Once configured, the CRUD6Model supports all standard Eloquent operations:\n";
    echo "\n";
    echo "// Create (if connected to database)\n";
    echo "\$model->create(['name' => 'New Record']);\n";
    echo "\n";
    echo "// Read\n";
    echo "\$record = \$model->find(1);\n";
    echo "\$records = \$model->where('active', true)->get();\n";
    echo "\n";
    echo "// Update\n";
    echo "\$record->update(['name' => 'Updated Name']);\n";
    echo "\n";
    echo "// Delete\n";
    echo "\$record->delete(); // Hard delete\n";
    echo "\$record->softDelete(); // Soft delete (if enabled)\n";
    echo "\n";
    echo "// Soft Delete Queries (if enabled)\n";
    echo "\$allRecords = \$model->withSoftDeleted()->get();\n";
    echo "\$deletedOnly = \$model->onlySoftDeleted()->get();\n";
    echo "\$activeOnly = \$model->withoutSoftDeleted()->get();\n";
    echo "\n";
}

// Run examples
if (php_sapi_name() === 'cli') {
    echo "CRUD6 Generic Model Usage Examples\n";
    echo "===================================\n\n";
    
    example1_basicConfiguration();
    example2_schemaBasedConfiguration();
    example3_differentTableConfigurations();
    example4_schemaServiceIntegration();
    example5_eloquentOperations();
    
    echo "Note: These examples demonstrate the model configuration.\n";
    echo "To use with an actual database, ensure your UserFrosting\n";
    echo "application is properly configured with database connections.\n";
}