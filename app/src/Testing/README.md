# CRUD6 Testing Tools

This directory contains testing utilities for CRUD6 integration tests, specifically focused on detecting redundant API calls and performance issues.

## Available Tools

### ApiCallTracker

**Location:** `ApiCallTracker.php`

A service class that tracks API calls made during integration tests. It monitors all HTTP requests and identifies redundant calls to the same endpoints.

**Key Features:**
- Tracks all API calls with timestamps and call stack traces
- Detects redundant/duplicate calls to same endpoints
- Identifies schema API calls specifically (`/api/crud6/{model}/schema`)
- Filters CRUD6 API calls (`/api/crud6/*`)
- Generates detailed reports with caller information

**Basic Usage:**
```php
$tracker = new ApiCallTracker();
$tracker->startTracking();

// ... make API calls ...

$redundantCalls = $tracker->getRedundantCalls();
$summary = $tracker->getSummary();

$tracker->stopTracking();
```

### TracksApiCalls

**Location:** `TracksApiCalls.php`

A PHPUnit trait that integrates API call tracking into integration tests. Provides convenient methods and assertions for detecting redundant calls.

**Key Features:**
- Easy integration with existing test classes
- Wrapper methods for tracked requests
- Built-in assertions for common scenarios
- Automatic cleanup in tearDown

**Basic Usage:**
```php
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;

class MyIntegrationTest extends AdminTestCase
{
    use TracksApiCalls;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->startApiTracking();
    }
    
    public function tearDown(): void
    {
        $this->tearDownApiTracking();
        parent::tearDown();
    }
    
    public function testMyFeature(): void
    {
        // Make tracked requests
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        
        // Assert no redundant calls
        $this->assertNoRedundantApiCalls();
    }
}
```

## Integration Test Example

See `app/tests/Integration/RedundantApiCallsTest.php` for comprehensive examples of:

- Single call validation
- Complex workflow testing
- Redundant call detection
- Schema call tracking
- Multiple endpoint testing
- Summary statistics

## Available Assertions

### assertNoRedundantApiCalls(?string $message = null)

Asserts that no redundant API calls were made during the test. Fails with a detailed report if duplicates are found.

```php
$this->assertNoRedundantApiCalls('Should not make duplicate API calls');
```

### assertNoRedundantSchemaApiCalls(?string $message = null)

Asserts that no redundant schema API calls were made. Schema calls should typically happen only once per model.

```php
$this->assertNoRedundantSchemaApiCalls('Schema should only be loaded once');
```

### assertApiCallCount(string $uri, int $expectedCount, string $message = '')

Asserts that a specific endpoint was called exactly N times.

```php
$this->assertApiCallCount('/api/crud6/users', 1, 'Users endpoint should be called once');
```

## Getter Methods

### getTrackedApiCalls(): array

Returns all tracked API calls.

```php
$calls = $this->getTrackedApiCalls();
foreach ($calls as $call) {
    echo "{$call['method']} {$call['uri']}\n";
}
```

### getRedundantApiCalls(): array

Returns only redundant calls (called more than once).

```php
$redundant = $this->getRedundantApiCalls();
if (!empty($redundant)) {
    // Handle redundant calls
}
```

### getSchemaApiCalls(): array

Returns only schema API calls (`/api/crud6/{model}/schema`).

```php
$schemaCalls = $this->getSchemaApiCalls();
$this->assertLessThanOrEqual(1, count($schemaCalls));
```

### getApiCallSummary(): array

Returns summary statistics about tracked calls.

```php
$summary = $this->getApiCallSummary();
// Returns: [
//     'total' => 5,
//     'unique' => 4,
//     'redundant' => 1,
//     'schema_calls' => 1,
//     'crud6_calls' => 5
// ]
```

## Best Practices

### 1. Always Track in Integration Tests

```php
public function setUp(): void
{
    parent::setUp();
    $this->startApiTracking();
}
```

### 2. Use handleRequestWithTracking()

```php
// ❌ Bad - not tracked
$response = $this->handleRequest($request);

// ✅ Good - tracked
$response = $this->handleRequestWithTracking($request);
```

### 3. Assert at End of Tests

```php
public function testComplexFeature(): void
{
    // ... test code ...
    
    // Always check at the end
    $this->assertNoRedundantApiCalls();
}
```

### 4. Clean Up in tearDown()

```php
public function tearDown(): void
{
    $this->tearDownApiTracking();
    parent::tearDown();
}
```

## Common Patterns

### Testing a Complete User Workflow

```php
public function testUserWorkflow(): void
{
    $this->startApiTracking();
    
    // Step 1: List users
    $listRequest = $this->createJsonRequest('GET', '/api/crud6/users');
    $this->handleRequestWithTracking($listRequest);
    
    // Step 2: View specific user
    $viewRequest = $this->createJsonRequest('GET', '/api/crud6/users/1');
    $this->handleRequestWithTracking($viewRequest);
    
    // Step 3: Update user
    $updateRequest = $this->createJsonRequest('PUT', '/api/crud6/users/1', ['name' => 'New Name']);
    $this->handleRequestWithTracking($updateRequest);
    
    // Verify no redundant calls
    $summary = $this->getApiCallSummary();
    $this->assertEquals(3, $summary['total']);
    $this->assertEquals(0, $summary['redundant']);
    
    $this->assertNoRedundantApiCalls();
}
```

### Testing Schema Load Optimization

```php
public function testSchemaLoadedOnce(): void
{
    $this->startApiTracking();
    
    // Load schema (if endpoint exists)
    $schemaRequest = $this->createJsonRequest('GET', '/api/crud6/users/schema');
    $this->handleRequestWithTracking($schemaRequest);
    
    // Schema calls should be exactly 1
    $this->assertApiCallCount('/api/crud6/users/schema', 1);
    $this->assertNoRedundantSchemaApiCalls();
}
```

### Debugging Redundant Calls

```php
public function testWithDebugging(): void
{
    $this->startApiTracking();
    
    // ... make API calls ...
    
    // Get detailed information
    $redundant = $this->getRedundantApiCalls();
    if (!empty($redundant)) {
        $tracker = $this->getApiCallTracker();
        echo $tracker->getRedundantCallsReport();
    }
    
    $this->assertNoRedundantApiCalls();
}
```

## Troubleshooting

### False Positives

The tracker groups calls by URL + HTTP method + query parameters. Calls with different parameters are considered unique:

```php
// These are different calls (not redundant):
GET /api/crud6/users?page=1
GET /api/crud6/users?page=2
```

### Schema Detection

Schema calls must follow the pattern `/api/crud6/{model}/schema`:

```php
// ✅ Detected as schema call
GET /api/crud6/users/schema

// ❌ Not detected as schema call
GET /api/crud6/schema/users
```

## Related Files

- **Documentation:** `docs/REDUNDANT_API_CALL_TESTING.md`
- **Tests:** `app/tests/Integration/RedundantApiCallsTest.php`
- **Frontend Tracker:** `app/assets/js/testing/NetworkRequestTracker.js`

## Contributing

When adding new CRUD6 features:

1. Include integration tests with call tracking
2. Verify no redundant API calls are made
3. Ensure schema calls are minimal (once per model)
4. Document any intentional duplicate calls

## Support

For questions or issues with the testing tools, please:

1. Check the documentation: `docs/REDUNDANT_API_CALL_TESTING.md`
2. Review examples: `app/tests/Integration/RedundantApiCallsTest.php`
3. Open an issue on GitHub
