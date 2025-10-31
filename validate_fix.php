<?php
declare(strict_types=1);

// Load the SchemaService
require_once 'app/src/ServicesProvider/SchemaService.php';

// Mock the ResourceLocatorInterface
class MockResourceLocator implements \UserFrosting\UniformResourceLocator\ResourceLocatorInterface {
    public function getResources(...$args) { return []; }
    public function getResource(...$args) { return null; }
    public function findResource(...$args) { return null; }
    public function findResources(...$args) { return []; }
    public function listResources(...$args) { return []; }
    public function getScheme(...$args) { return null; }
    public function getSchemes(...$args) { return []; }
    public function addStream(...$args) { return $this; }
    public function removeStream(...$args) { return $this; }
    public function isStream(...$args) { return false; }
    public function getStream(...$args) { return null; }
    public function getStreams(...$args) { return []; }
    public function removeLocation(...$args) { return $this; }
    public function getLocation(...$args) { return null; }
    public function getLocations(...$args) { return []; }
    public function addPath(...$args) { return $this; }
    public function getPaths(...$args) { return []; }
    public function normalizePath(...$args) { return ''; }
    public function __invoke(...$args) { return null; }
}

// Create SchemaService instance
$locator = new MockResourceLocator();
$schemaService = new \UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService($locator);

// Test schema with details and actions (matching the c6admin users.json schema)
$testSchema = [
    'model' => 'users',
    'title' => 'Users',
    'singular_title' => 'User',
    'table' => 'users',
    'primary_key' => 'id',
    'permissions' => [
        'read' => 'uri_users',
        'update' => 'update_user_field',
    ],
    'fields' => [
        'id' => ['type' => 'integer', 'label' => 'ID', 'viewable' => true],
        'user_name' => ['type' => 'string', 'label' => 'Username', 'viewable' => true],
        'email' => ['type' => 'string', 'label' => 'Email', 'viewable' => true],
    ],
    'details' => [
        [
            'model' => 'activities',
            'foreign_key' => 'user_id',
            'list_fields' => ['occurred_at', 'type', 'description'],
            'title' => 'ACTIVITY.2',
        ],
        [
            'model' => 'roles',
            'foreign_key' => 'user_id',
            'list_fields' => ['name', 'slug', 'description'],
            'title' => 'ROLE.2',
        ],
    ],
    'actions' => [
        [
            'key' => 'toggle_enabled',
            'label' => 'USER.ADMIN.TOGGLE_ENABLED',
            'icon' => 'power-off',
            'type' => 'field_update',
            'field' => 'flag_enabled',
        ],
        [
            'key' => 'reset_password',
            'label' => 'USER.ADMIN.PASSWORD_RESET',
            'icon' => 'envelope',
            'type' => 'api_call',
        ],
    ],
    'relationships' => [
        [
            'name' => 'roles',
            'type' => 'many_to_many',
            'pivot_table' => 'role_user',
        ],
    ],
];

// Filter for detail context
$filteredSchema = $schemaService->filterSchemaForContext($testSchema, 'detail');

echo "✅ Schema Filtering Test for Detail Context\n";
echo "==========================================\n\n";

// Check if details array is included
if (isset($filteredSchema['details'])) {
    echo "✅ PASS: 'details' array is included in detail context\n";
    echo "   - Found " . count($filteredSchema['details']) . " detail configurations\n";
    foreach ($filteredSchema['details'] as $idx => $detail) {
        echo "   - Detail " . ($idx + 1) . ": " . $detail['model'] . "\n";
    }
} else {
    echo "❌ FAIL: 'details' array is NOT included in detail context\n";
}

echo "\n";

// Check if actions array is included
if (isset($filteredSchema['actions'])) {
    echo "✅ PASS: 'actions' array is included in detail context\n";
    echo "   - Found " . count($filteredSchema['actions']) . " action configurations\n";
    foreach ($filteredSchema['actions'] as $idx => $action) {
        echo "   - Action " . ($idx + 1) . ": " . $action['key'] . " (" . $action['type'] . ")\n";
    }
} else {
    echo "❌ FAIL: 'actions' array is NOT included in detail context\n";
}

echo "\n";

// Check if relationships array is included
if (isset($filteredSchema['relationships'])) {
    echo "✅ PASS: 'relationships' array is included in detail context\n";
    echo "   - Found " . count($filteredSchema['relationships']) . " relationship configurations\n";
} else {
    echo "❌ FAIL: 'relationships' array is NOT included in detail context\n";
}

echo "\n";

// Check if fields are still included
if (isset($filteredSchema['fields']) && count($filteredSchema['fields']) > 0) {
    echo "✅ PASS: Fields are included in detail context\n";
    echo "   - Found " . count($filteredSchema['fields']) . " viewable fields\n";
} else {
    echo "❌ FAIL: Fields are NOT included properly\n";
}

echo "\n";

// Overall result
if (isset($filteredSchema['details']) && isset($filteredSchema['actions']) && isset($filteredSchema['relationships']) && isset($filteredSchema['fields'])) {
    echo "✅✅✅ ALL TESTS PASSED! ✅✅✅\n";
    echo "\nThe fix correctly includes:\n";
    echo "  ✓ details (plural) for multiple relationship tables\n";
    echo "  ✓ actions for custom action buttons\n";
    echo "  ✓ relationships for data fetching\n";
    echo "  ✓ fields for displaying user information\n";
    exit(0);
} else {
    echo "❌❌❌ SOME TESTS FAILED ❌❌❌\n";
    exit(1);
}
