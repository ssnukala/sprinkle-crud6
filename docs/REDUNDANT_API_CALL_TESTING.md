# Redundant API Call Detection in Integration Tests

This document describes the enhanced integration testing capabilities for detecting and flagging redundant API calls in CRUD6.

## Overview

The CRUD6 sprinkle now includes comprehensive tools for tracking and detecting redundant API calls during integration tests. This helps identify:

- **Duplicate schema API calls** to `/api/crud6/{model}/schema`
- **Redundant CRUD6 API calls** to any `/api/crud6/*` endpoint
- **Duplicate frontend network requests** from JavaScript code
- **Performance issues** caused by unnecessary API requests

## Backend API Call Tracking

### Setup

Integration tests can use the `TracksApiCalls` trait to enable API call tracking:

```php
use UserFrosting\Sprinkle\CRUD6\Testing\TracksApiCalls;

class MyIntegrationTest extends AdminTestCase
{
    use TracksApiCalls;
    
    public function testMyFeature(): void
    {
        // Start tracking
        $this->startApiTracking();
        
        // Make API calls using handleRequestWithTracking()
        $request = $this->createJsonRequest('GET', '/api/crud6/users');
        $response = $this->handleRequestWithTracking($request);
        
        // Assert no redundant calls
        $this->assertNoRedundantApiCalls();
    }
}
```

### Available Methods

#### Tracking Control

- `startApiTracking()` - Begin tracking API calls
- `stopApiTracking()` - Stop tracking API calls
- `tearDownApiTracking()` - Cleanup (call in tearDown())

#### Making Tracked Requests

- `handleRequestWithTracking(ServerRequestInterface $request)` - Handle request and track the API call

#### Getting Tracked Data

- `getTrackedApiCalls()` - Get all tracked calls
- `getRedundantApiCalls()` - Get calls made more than once
- `getSchemaApiCalls()` - Get schema API calls only
- `getRedundantSchemaApiCalls()` - Get redundant schema calls
- `getApiCallSummary()` - Get summary statistics

#### Assertions

- `assertNoRedundantApiCalls(?string $message = null)` - Assert no redundant calls exist
- `assertNoRedundantSchemaApiCalls(?string $message = null)` - Assert no redundant schema calls
- `assertApiCallCount(string $uri, int $expectedCount, string $message = '')` - Assert specific call count

### Example: Detecting Redundant Calls

```php
public function testDetectsRedundantCalls(): void
{
    $user = User::factory()->create();
    $this->actAsUser($user, permissions: ['uri_crud6']);
    
    $group = Group::factory()->create();
    
    $this->startApiTracking();
    
    // Make the same call twice
    $uri = '/api/crud6/groups/' . $group->id;
    
    $request1 = $this->createJsonRequest('GET', $uri);
    $this->handleRequestWithTracking($request1);
    
    $request2 = $this->createJsonRequest('GET', $uri);
    $this->handleRequestWithTracking($request2);
    
    // This will fail because we made 2 calls to the same endpoint
    $this->assertNoRedundantApiCalls();
}
```

### Example: Complex Workflow Validation

```php
public function testComplexWorkflowNoRedundantCalls(): void
{
    $user = User::factory()->create();
    $this->actAsUser($user, permissions: ['uri_crud6']);
    
    $group = Group::factory()->create();
    
    $this->startApiTracking();
    
    // Step 1: Load groups list
    $request1 = $this->createJsonRequest('GET', '/api/crud6/groups');
    $this->handleRequestWithTracking($request1);
    
    // Step 2: Load individual group
    $request2 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id);
    $this->handleRequestWithTracking($request2);
    
    // Step 3: Load related users
    $request3 = $this->createJsonRequest('GET', '/api/crud6/groups/' . $group->id . '/users');
    $this->handleRequestWithTracking($request3);
    
    // Verify no redundant calls in this workflow
    $summary = $this->getApiCallSummary();
    $this->assertEquals(3, $summary['total']);
    $this->assertEquals(3, $summary['unique']);
    $this->assertEquals(0, $summary['redundant']);
    
    $this->assertNoRedundantApiCalls();
}
```

## Frontend Network Request Tracking

### Setup (JavaScript)

For frontend integration tests, use the `NetworkRequestTracker`:

```javascript
import NetworkRequestTracker from './NetworkRequestTracker';

describe('My Component Tests', () => {
    let tracker;
    
    beforeEach(() => {
        tracker = new NetworkRequestTracker();
        tracker.startTracking();
    });
    
    afterEach(() => {
        tracker.stopTracking();
    });
    
    it('should not make redundant API calls', async () => {
        // Your test code that makes API calls
        await fetch('/api/crud6/users');
        await fetch('/api/crud6/users/1');
        
        // Check for redundant calls
        const redundant = tracker.getRedundantCalls();
        expect(Object.keys(redundant).length).toBe(0);
    });
});
```

### Available Methods

#### Tracking Control

- `startTracking()` - Begin tracking network requests
- `stopTracking()` - Stop tracking network requests
- `reset()` - Clear all tracked requests

#### Getting Tracked Data

- `getRequests()` - Get all tracked requests
- `getRedundantCalls()` - Get requests made more than once
- `getSchemaCalls()` - Get schema API calls only
- `getRedundantSchemaCalls()` - Get redundant schema calls
- `getCRUD6Calls()` - Get all CRUD6 API calls
- `getSummary()` - Get summary statistics

#### Utility Methods

- `hasRedundantCalls()` - Check if redundant calls exist
- `getRedundantCallsReport()` - Get formatted report

### Example: Frontend Component Test

```javascript
import NetworkRequestTracker from './testing/NetworkRequestTracker';
import { mount } from '@vue/test-utils';
import UserListComponent from './UserListComponent.vue';

describe('UserListComponent', () => {
    let tracker;
    
    beforeEach(() => {
        tracker = new NetworkRequestTracker();
        tracker.startTracking();
    });
    
    afterEach(() => {
        tracker.stopTracking();
    });
    
    it('should load users without redundant API calls', async () => {
        // Mount component
        const wrapper = mount(UserListComponent);
        
        // Wait for component to load data
        await wrapper.vm.$nextTick();
        await new Promise(resolve => setTimeout(resolve, 100));
        
        // Check for redundant calls
        const summary = tracker.getSummary();
        expect(summary.redundant).toBe(0);
        
        // Generate report if redundant calls found
        if (tracker.hasRedundantCalls()) {
            console.log(tracker.getRedundantCallsReport());
            throw new Error('Redundant API calls detected');
        }
    });
    
    it('should not reload schema on every interaction', async () => {
        const wrapper = mount(UserListComponent);
        
        // Simulate multiple interactions
        await wrapper.find('button.refresh').trigger('click');
        await wrapper.find('button.filter').trigger('click');
        
        // Schema should only be loaded once
        const schemaCalls = tracker.getSchemaCalls();
        expect(schemaCalls.length).toBeLessThanOrEqual(1);
    });
});
```

## Integration Test Examples

### Running the Tests

```bash
# Run all integration tests
vendor/bin/phpunit app/tests/Integration

# Run specific redundant call detection test
vendor/bin/phpunit app/tests/Integration/RedundantApiCallsTest.php

# Run with verbose output
vendor/bin/phpunit --verbose app/tests/Integration/RedundantApiCallsTest.php
```

### Example Output

When redundant calls are detected, you'll see detailed output:

```
Redundant API Calls Detected:
================================================================================

Endpoint: GET /api/crud6/groups/1
Called 2 times (should be 1):
  1. Time: 1699564231.4567, Trace: handleRequestWithTracking() <- testDetectsRedundantCalls()
  2. Time: 1699564231.4789, Trace: handleRequestWithTracking() <- testDetectsRedundantCalls()
```

## Best Practices

### 1. Always Use Tracking in Integration Tests

```php
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
```

### 2. Track All Requests

Use `handleRequestWithTracking()` instead of `handleRequest()`:

```php
// ❌ Bad - not tracked
$response = $this->handleRequest($request);

// ✅ Good - tracked
$response = $this->handleRequestWithTracking($request);
```

### 3. Assert No Redundant Calls at End of Tests

```php
public function testMyFeature(): void
{
    // ... test code ...
    
    // Always check for redundant calls
    $this->assertNoRedundantApiCalls();
}
```

### 4. Use Specific Assertions for Critical Paths

```php
// Assert specific endpoints are called exactly once
$this->assertApiCallCount('/api/crud6/users/schema', 1);
$this->assertApiCallCount('/api/crud6/users/1', 1);
```

### 5. Monitor Summary Statistics

```php
$summary = $this->getApiCallSummary();
$this->assertLessThan(10, $summary['total'], 'Should not make more than 10 API calls');
$this->assertEquals(0, $summary['redundant'], 'Should have no redundant calls');
```

## Troubleshooting

### Issue: False Positives for Different Query Parameters

The tracker considers requests with different query parameters as unique calls. If you're seeing false positives:

```php
// These are considered different calls:
GET /api/crud6/users?page=1
GET /api/crud6/users?page=2
```

### Issue: Schema Calls Not Detected

Ensure the schema endpoint follows the pattern `/api/crud6/{model}/schema`:

```php
// ✅ Will be detected as schema call
GET /api/crud6/users/schema

// ❌ Will not be detected
GET /api/crud6/schema/users
```

### Issue: Frontend Tracker Not Working

Make sure to:
1. Import and instantiate the tracker
2. Call `startTracking()` before making requests
3. The tracker intercepts both `fetch()` and `XMLHttpRequest`

## API Reference

### ApiCallTracker Class

Full API documentation is available in the source code:
- `app/src/Testing/ApiCallTracker.php`

### TracksApiCalls Trait

Full API documentation is available in the source code:
- `app/src/Testing/TracksApiCalls.php`

### NetworkRequestTracker Class

Full API documentation is available in the source code:
- `app/assets/js/testing/NetworkRequestTracker.js`

## Contributing

When adding new CRUD6 features, always include integration tests that verify:
1. No redundant API calls are made
2. Schema calls are minimal (ideally once per model)
3. Complex workflows don't duplicate requests

See `app/tests/Integration/RedundantApiCallsTest.php` for examples.
