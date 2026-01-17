#!/usr/bin/env php
<?php
/**
 * Validation Script for AutoLookup Component
 * 
 * This script validates that the AutoLookup component and related files
 * are properly structured and integrated.
 */

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

echo "=== AutoLookup Component Validation ===\n\n";

$errors = [];
$warnings = [];
$passed = 0;

// Check 1: AutoLookup.vue exists
echo "Checking AutoLookup.vue exists... ";
if (file_exists('app/assets/components/CRUD6/AutoLookup.vue')) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup.vue component file not found";
}

// Check 2: Component is exported in CRUD6/index.ts
echo "Checking CRUD6/index.ts exports AutoLookup... ";
$crud6IndexContent = file_get_contents('app/assets/components/CRUD6/index.ts');
if (strpos($crud6IndexContent, 'CRUD6AutoLookup') !== false && 
    strpos($crud6IndexContent, './AutoLookup.vue') !== false) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup not properly exported from CRUD6/index.ts";
}

// Check 3: Component is exported in components/index.ts
echo "Checking components/index.ts exports AutoLookup... ";
$componentsIndexContent = file_get_contents('app/assets/components/index.ts');
if (strpos($componentsIndexContent, 'CRUD6AutoLookup') !== false) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup not properly exported from components/index.ts";
}

// Check 4: Documentation exists
echo "Checking AutoLookup documentation exists... ";
if (file_exists('docs/AutoLookup.md')) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup.md documentation not found";
}

// Check 5: Example files exist
echo "Checking example files exist... ";
$exampleFiles = [
    'examples/AutoLookupExamples.vue',
    'examples/ProductCategoryPageWithAutoLookup.vue'
];
$allExamplesExist = true;
foreach ($exampleFiles as $file) {
    if (!file_exists($file)) {
        $allExamplesExist = false;
        $warnings[] = "Example file not found: $file";
    }
}
if ($allExamplesExist) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "⚠ WARN\n";
}

// Check 6: README mentions AutoLookup
echo "Checking README.md mentions AutoLookup... ";
$readmeContent = file_get_contents('README.md');
if (strpos($readmeContent, 'AutoLookup') !== false || 
    strpos($readmeContent, 'auto-complete') !== false) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "README.md does not mention AutoLookup component";
}

// Check 7: Component structure validation (basic Vue syntax)
echo "Checking AutoLookup.vue structure... ";
$autoLookupContent = file_get_contents('app/assets/components/CRUD6/AutoLookup.vue');
$hasScript = strpos($autoLookupContent, '<script setup') !== false;
$hasTemplate = strpos($autoLookupContent, '<template>') !== false;
$hasStyle = strpos($autoLookupContent, '<style') !== false;
$hasProps = strpos($autoLookupContent, 'defineProps') !== false;
$hasEmits = strpos($autoLookupContent, 'defineEmits') !== false;

if ($hasScript && $hasTemplate && $hasProps && $hasEmits) {
    echo "✓ PASS\n";
    $passed++;
    if (!$hasStyle) {
        $warnings[] = "AutoLookup.vue has no <style> section (optional)";
    }
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup.vue structure incomplete";
    if (!$hasScript) $errors[] = "  - Missing <script setup>";
    if (!$hasTemplate) $errors[] = "  - Missing <template>";
    if (!$hasProps) $errors[] = "  - Missing defineProps";
    if (!$hasEmits) $errors[] = "  - Missing defineEmits";
}

// Check 8: Component uses CRUD6 API patterns
echo "Checking AutoLookup uses CRUD6 API patterns... ";
if (strpos($autoLookupContent, '/api/crud6/') !== false && 
    strpos($autoLookupContent, 'axios') !== false) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup does not use CRUD6 API patterns";
}

// Check 9: Component implements required features
echo "Checking AutoLookup implements required features... ";
$hasSearch = strpos($autoLookupContent, 'search') !== false;
$hasModelProp = strpos($autoLookupContent, 'model:') !== false || 
                strpos($autoLookupContent, 'model') !== false;
$hasKeyboard = strpos($autoLookupContent, 'keydown') !== false || 
               strpos($autoLookupContent, 'ArrowDown') !== false;
$hasDebounce = strpos($autoLookupContent, 'debounce') !== false;

if ($hasSearch && $hasModelProp && $hasKeyboard && $hasDebounce) {
    echo "✓ PASS\n";
    $passed++;
} else {
    echo "✗ FAIL\n";
    $errors[] = "AutoLookup missing required features";
    if (!$hasSearch) $errors[] = "  - Missing search functionality";
    if (!$hasModelProp) $errors[] = "  - Missing model prop";
    if (!$hasKeyboard) $errors[] = "  - Missing keyboard navigation";
    if (!$hasDebounce) $errors[] = "  - Missing debounce functionality";
}

// Summary
echo "\n=== Validation Summary ===\n";
echo "Tests passed: $passed\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n\n";

if (count($errors) > 0) {
    echo "ERRORS:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

if (count($errors) === 0) {
    echo "✓ All validation checks passed!\n";
    exit(0);
} else {
    echo "✗ Validation failed with " . count($errors) . " error(s)\n";
    exit(1);
}
