<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Testing;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\CRUD6\Testing\ApiCallTracker;

/**
 * Unit tests for ApiCallTracker class.
 *
 * These tests verify the core tracking functionality without requiring
 * a full UserFrosting application setup.
 */
class ApiCallTrackerTest extends TestCase
{
    /**
     * Test that tracker starts in stopped state
     */
    public function testInitialState(): void
    {
        $tracker = new ApiCallTracker();
        
        // Track a call before starting
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        // Should not be tracked
        $calls = $tracker->getCalls();
        $this->assertEmpty($calls, 'Calls should not be tracked before startTracking() is called');
    }

    /**
     * Test basic tracking functionality
     */
    public function testBasicTracking(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        $calls = $tracker->getCalls();
        $this->assertCount(1, $calls);
        $this->assertEquals('GET', $calls[0]['method']);
        $this->assertEquals('/api/crud6/users', $calls[0]['uri']);
    }

    /**
     * Test that stopping tracking prevents new calls from being tracked
     */
    public function testStopTracking(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->stopTracking();
        $tracker->trackCall('/api/crud6/groups', 'GET');
        
        $calls = $tracker->getCalls();
        $this->assertCount(1, $calls, 'Should only track call made before stopTracking()');
        $this->assertEquals('/api/crud6/users', $calls[0]['uri']);
    }

    /**
     * Test detection of redundant calls
     */
    public function testDetectRedundantCalls(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        // Make the same call twice
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        $redundant = $tracker->getRedundantCalls();
        $this->assertNotEmpty($redundant, 'Should detect redundant calls');
        $this->assertCount(1, $redundant, 'Should have one redundant call group');
        
        $firstKey = array_key_first($redundant);
        $this->assertEquals(2, $redundant[$firstKey]['count']);
    }

    /**
     * Test that different endpoints are not considered redundant
     */
    public function testDifferentEndpointsNotRedundant(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/groups', 'GET');
        
        $redundant = $tracker->getRedundantCalls();
        $this->assertEmpty($redundant, 'Different endpoints should not be redundant');
    }

    /**
     * Test schema call detection
     */
    public function testSchemaCallDetection(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users/schema', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        $schemaCalls = $tracker->getSchemaCalls();
        $this->assertCount(1, $schemaCalls, 'Should detect schema call');
        $this->assertStringContainsString('schema', $schemaCalls[0]['uri']);
    }

    /**
     * Test CRUD6 call detection
     */
    public function testCRUD6CallDetection(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/other/endpoint', 'GET');
        
        $crud6Calls = $tracker->getCRUD6Calls();
        $this->assertCount(1, $crud6Calls, 'Should detect CRUD6 calls only');
        $this->assertEquals('/api/crud6/users', $crud6Calls[0]['uri']);
    }

    /**
     * Test summary statistics
     */
    public function testSummary(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET'); // Duplicate
        $tracker->trackCall('/api/crud6/groups', 'GET');
        
        $summary = $tracker->getSummary();
        
        $this->assertEquals(3, $summary['total'], 'Total should be 3');
        $this->assertEquals(2, $summary['unique'], 'Unique should be 2');
        $this->assertEquals(1, $summary['redundant'], 'Redundant groups should be 1');
        $this->assertEquals(3, $summary['crud6_calls'], 'All are CRUD6 calls');
    }

    /**
     * Test reset functionality
     */
    public function testReset(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $this->assertCount(1, $tracker->getCalls());
        
        $tracker->reset();
        $this->assertCount(0, $tracker->getCalls(), 'Calls should be cleared after reset');
    }

    /**
     * Test hasRedundantCalls method
     */
    public function testHasRedundantCalls(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $this->assertFalse($tracker->hasRedundantCalls(), 'Should not have redundant calls');
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $this->assertTrue($tracker->hasRedundantCalls(), 'Should have redundant calls');
    }

    /**
     * Test redundant schema calls detection
     */
    public function testRedundantSchemaCalls(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users/schema', 'GET');
        $tracker->trackCall('/api/crud6/users/schema', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        $redundantSchema = $tracker->getRedundantSchemaCalls();
        
        $this->assertCount(1, $redundantSchema, 'Should detect redundant schema calls');
        
        // Verify it's the schema call
        $firstKey = array_key_first($redundantSchema);
        $this->assertStringContainsString('schema', $redundantSchema[$firstKey]['calls'][0]['uri']);
    }

    /**
     * Test that query parameters are considered in uniqueness
     */
    public function testQueryParametersInUniqueness(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        // Different query params = different calls
        $tracker->trackCall('/api/crud6/users?page=1', 'GET', ['page' => '1']);
        $tracker->trackCall('/api/crud6/users?page=2', 'GET', ['page' => '2']);
        
        $redundant = $tracker->getRedundantCalls();
        $this->assertEmpty($redundant, 'Different query params should not be redundant');
    }

    /**
     * Test that schema calls ignore query parameters
     */
    public function testSchemaCallsIgnoreQueryParams(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        // Schema calls with different query params should be considered redundant
        $tracker->trackCall('/api/crud6/users/schema?v=1', 'GET', ['v' => '1']);
        $tracker->trackCall('/api/crud6/users/schema?v=2', 'GET', ['v' => '2']);
        
        $redundantSchema = $tracker->getRedundantSchemaCalls();
        $this->assertNotEmpty($redundantSchema, 'Schema calls should ignore query params');
    }

    /**
     * Test report generation
     */
    public function testReportGeneration(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        $report = $tracker->getRedundantCallsReport();
        
        $this->assertStringContainsString('Redundant API Calls Detected', $report);
        $this->assertStringContainsString('/api/crud6/users', $report);
        $this->assertStringContainsString('Called 2 times', $report);
    }

    /**
     * Test report when no redundant calls exist
     */
    public function testReportNoRedundantCalls(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/groups', 'GET');
        
        $report = $tracker->getRedundantCallsReport();
        
        $this->assertStringContainsString('No redundant calls detected', $report);
    }

    /**
     * Test HTTP method is considered in uniqueness
     */
    public function testHttpMethodInUniqueness(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        // Same URL, different methods = different calls
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'POST');
        
        $redundant = $tracker->getRedundantCalls();
        $this->assertEmpty($redundant, 'Different HTTP methods should not be redundant');
    }

    /**
     * Test multiple redundant call groups
     */
    public function testMultipleRedundantGroups(): void
    {
        $tracker = new ApiCallTracker();
        $tracker->startTracking();
        
        // First redundant group
        $tracker->trackCall('/api/crud6/users', 'GET');
        $tracker->trackCall('/api/crud6/users', 'GET');
        
        // Second redundant group
        $tracker->trackCall('/api/crud6/groups', 'GET');
        $tracker->trackCall('/api/crud6/groups', 'GET');
        
        $redundant = $tracker->getRedundantCalls();
        $this->assertCount(2, $redundant, 'Should detect 2 redundant call groups');
    }
}
