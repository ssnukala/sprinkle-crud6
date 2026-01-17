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
 * Manual Validation Script
 * 
 * This script validates the schema optimization and field_template features.
 */

echo "=== CRUD6 Schema Optimization & Field Template Validation ===\n\n";

// Test 1: Validate all JSON schemas
echo "Test 1: Validating JSON Schema Files\n";
echo "--------------------------------------\n";

$schemaFiles = [
    'examples/products.json',
    'examples/categories.json',
    'examples/analytics.json',
    'examples/field-template-example.json',
    'examples/products-template-file.json',
    'examples/products-vue-template.json',
    'app/schema/crud6/users.json',
    'app/schema/crud6/groups.json',
    'app/schema/crud6/db1/users.json',
];

$allValid = true;
foreach ($schemaFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "✗ MISSING: $file\n";
        $allValid = false;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $json = json_decode($content, true);
    
    if ($json === null) {
        echo "✗ INVALID JSON: $file - " . json_last_error_msg() . "\n";
        $allValid = false;
    } else {
        echo "✓ VALID: $file\n";
    }
}

echo "\n";

// Test 2: Check for removal of default values
echo "Test 2: Checking Default Values Removal\n";
echo "----------------------------------------\n";

$testSchemas = [
    'examples/products.json',
    'examples/categories.json',
    'app/schema/crud6/users.json',
];

foreach ($testSchemas as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $content = file_get_contents($fullPath);
    $json = json_decode($content, true);
    
    $hasDefaults = [];
    if (isset($json['primary_key'])) {
        $hasDefaults[] = 'primary_key';
    }
    if (isset($json['timestamps'])) {
        $hasDefaults[] = 'timestamps';
    }
    if (isset($json['soft_delete'])) {
        $hasDefaults[] = 'soft_delete';
    }
    
    if (empty($hasDefaults)) {
        echo "✓ OPTIMIZED: $file (no default values present)\n";
    } else {
        echo "⚠ HAS DEFAULTS: $file (" . implode(', ', $hasDefaults) . ")\n";
        echo "  Note: This is OK if values differ from defaults\n";
    }
}

echo "\n";

// Test 3: Validate field_template presence
echo "Test 3: Checking Field Template Feature\n";
echo "----------------------------------------\n";

$templatesFound = [];
foreach (['examples/products.json', 'examples/categories.json', 'examples/field-template-example.json'] as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $content = file_get_contents($fullPath);
    $json = json_decode($content, true);
    
    if (isset($json['fields'])) {
        foreach ($json['fields'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['field_template'])) {
                $templatesFound[] = "$file -> $fieldName";
                echo "✓ FOUND: $file -> $fieldName\n";
                
                // Validate template has placeholders
                $template = $fieldConfig['field_template'];
                if (preg_match('/\{\{(\w+)\}\}/', $template)) {
                    echo "  ✓ Contains valid {{placeholder}} syntax\n";
                } else {
                    echo "  ⚠ No placeholders found\n";
                }
            }
        }
    }
}

if (empty($templatesFound)) {
    echo "✗ NO TEMPLATES FOUND\n";
} else {
    echo "\n✓ Found " . count($templatesFound) . " field template(s)\n";
}

echo "\n";

// Test 3b: Check Template Files
echo "Test 3b: Checking Template Files\n";
echo "---------------------------------\n";

$templateFiles = [
    'app/assets/templates/crud6/product-card.html',
    'app/assets/templates/crud6/category-info.html',
    'app/assets/templates/crud6/ProductCard.vue',
    'app/assets/templates/crud6/CategoryInfo.vue',
];

$allTemplatesExist = true;
foreach ($templateFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "✗ MISSING: $file\n";
        $allTemplatesExist = false;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    if (empty($content)) {
        echo "✗ EMPTY: $file\n";
        $allTemplatesExist = false;
    } else {
        echo "✓ EXISTS: $file\n";
        
        // Validate template content based on file type
        if (strpos($file, '.html') !== false) {
            // HTML template - check for placeholders
            if (preg_match('/\{\{(\w+)\}\}/', $content)) {
                echo "  ✓ Contains valid {{placeholder}} syntax\n";
            } else {
                echo "  ⚠ No placeholders found\n";
            }
        } elseif (strpos($file, '.vue') !== false) {
            // Vue component - check for required structure
            if (strpos($content, '<template>') !== false) {
                echo "  ✓ Contains <template> section\n";
            } else {
                echo "  ✗ Missing <template> section\n";
                $allTemplatesExist = false;
            }
            if (strpos($content, 'rowData') !== false) {
                echo "  ✓ Uses rowData prop\n";
            } else {
                echo "  ⚠ No rowData prop found\n";
            }
        }
    }
}

if ($allTemplatesExist) {
    echo "\n✓ All template files exist and are valid\n";
}

echo "\n";

// Test 4: Verify SchemaService changes
echo "Test 4: Verifying SchemaService.php Changes\n";
echo "--------------------------------------------\n";

$schemaServicePath = __DIR__ . '/app/src/ServicesProvider/SchemaService.php';
if (!file_exists($schemaServicePath)) {
    echo "✗ SchemaService.php not found\n";
} else {
    $content = file_get_contents($schemaServicePath);
    
    $checks = [
        'applyDefaults method' => strpos($content, 'function applyDefaults') !== false,
        'primary_key default' => strpos($content, "['primary_key'] ?? 'id'") !== false,
        'timestamps default' => strpos($content, "['timestamps'] ?? true") !== false,
        'soft_delete default' => strpos($content, "['soft_delete'] ?? false") !== false,
        'applyDefaults call' => strpos($content, '$schema = $this->applyDefaults($schema)') !== false,
    ];
    
    foreach ($checks as $check => $passed) {
        if ($passed) {
            echo "✓ $check\n";
        } else {
            echo "✗ $check\n";
            $allValid = false;
        }
    }
}

echo "\n";

// Test 5: Verify Vue component changes
echo "Test 5: Verifying PageList.vue Changes\n";
echo "---------------------------------------\n";

$pageListPath = __DIR__ . '/app/assets/views/PageList.vue';
if (!file_exists($pageListPath)) {
    echo "✗ PageList.vue not found\n";
} else {
    $content = file_get_contents($pageListPath);
    
    $checks = [
        'field_template check' => strpos($content, 'field.field_template') !== false,
        'renderFieldTemplate function' => strpos($content, 'function renderFieldTemplate') !== false,
        'v-html rendering' => strpos($content, 'v-html="renderFieldTemplate') !== false,
        'placeholder regex' => strpos($content, '/\\{\\{(\\w+)\\}\\}/g') !== false,
        'template file glob import' => strpos($content, "import.meta.glob") !== false,
        'Vue component glob import' => strpos($content, "vueTemplateFiles") !== false,
        'file extension check' => strpos($content, "endsWith('.html')") !== false,
        'Vue component check' => strpos($content, "isVueTemplate") !== false,
        'dynamic component rendering' => strpos($content, "<component") !== false,
        'template file loading' => strpos($content, "templateFiles[templatePath]") !== false,
    ];
    
    foreach ($checks as $check => $passed) {
        if ($passed) {
            echo "✓ $check\n";
        } else {
            echo "✗ $check\n";
            $allValid = false;
        }
    }
}

echo "\n";

// Test 6: Check documentation
echo "Test 6: Checking Documentation\n";
echo "-------------------------------\n";

$docs = [
    'README.md' => [
        'field_template',
        'Schema Defaults',
        'primary_key',
        'External HTML Template',
        'Vue Component Template Example',
        'template file',
    ],
    'docs/FIELD_TEMPLATE_FEATURE.md' => [
        'field_template',
        '{{field_name}}',
        'placeholder',
        'External HTML Template',
        'products-template-file.json',
        'Vue Component Templates',
        'ProductCard.vue',
        'rowData',
    ],
];

foreach ($docs as $docFile => $searchTerms) {
    $docPath = __DIR__ . '/' . $docFile;
    if (!file_exists($docPath)) {
        echo "✗ MISSING: $docFile\n";
        $allValid = false;
        continue;
    }
    
    $content = file_get_contents($docPath);
    $foundAll = true;
    foreach ($searchTerms as $term) {
        if (strpos($content, $term) === false) {
            echo "✗ MISSING TERM '$term' in $docFile\n";
            $foundAll = false;
            $allValid = false;
        }
    }
    
    if ($foundAll) {
        echo "✓ $docFile (all required terms present)\n";
    }
}

echo "\n";

// Final Summary
echo "=== VALIDATION SUMMARY ===\n";
if ($allValid) {
    echo "✓ ALL TESTS PASSED\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    exit(1);
}
