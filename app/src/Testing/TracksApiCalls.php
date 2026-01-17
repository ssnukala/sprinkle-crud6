<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait for tracking API calls in integration tests.
 * 
 * This trait provides functionality to track and analyze API calls made during tests,
 * helping identify redundant calls and performance issues.
 * 
 * Usage in test class:
 * ```php
 * class MyIntegrationTest extends CRUD6TestCase
 * {
 *     use TracksApiCalls;
 * 
 *     public function testSomething(): void
 *     {
 *         $this->startApiTracking();
 *         
 *         // Make API calls...
 *         $request = $this->createJsonRequest('GET', '/api/crud6/users');
 *         $response = $this->handleRequestWithTracking($request);
 *         
 *         // Check for redundant calls
 *         $this->assertNoRedundantApiCalls();
 *     }
 * }
 * ```
 */
trait TracksApiCalls
{
    /**
     * @var ApiCallTracker|null The API call tracker instance
     */
    private ?ApiCallTracker $apiCallTracker = null;

    /**
     * Start tracking API calls.
     * 
     * @return void
     */
    protected function startApiTracking(): void
    {
        $this->apiCallTracker = new ApiCallTracker();
        $this->apiCallTracker->startTracking();
    }

    /**
     * Stop tracking API calls.
     * 
     * @return void
     */
    protected function stopApiTracking(): void
    {
        if ($this->apiCallTracker !== null) {
            $this->apiCallTracker->stopTracking();
        }
    }

    /**
     * Handle a request and track the API call.
     * 
     * This method wraps the standard handleRequest() to also track the call.
     * 
     * @param ServerRequestInterface $request The request to handle
     * 
     * @return ResponseInterface The response
     */
    protected function handleRequestWithTracking(ServerRequestInterface $request): ResponseInterface
    {
        // Track the call
        if ($this->apiCallTracker !== null) {
            $uri = (string) $request->getUri();
            $method = $request->getMethod();
            $params = $request->getQueryParams();
            
            $this->apiCallTracker->trackCall($uri, $method, $params);
        }

        // Handle the request using the parent test case method
        return $this->handleRequest($request);
    }

    /**
     * Get the API call tracker.
     * 
     * @return ApiCallTracker|null The tracker instance
     */
    protected function getApiCallTracker(): ?ApiCallTracker
    {
        return $this->apiCallTracker;
    }

    /**
     * Get all tracked API calls.
     * 
     * @return array<int, array{uri: string, method: string, timestamp: float}> Array of calls
     */
    protected function getTrackedApiCalls(): array
    {
        return $this->apiCallTracker?->getCalls() ?? [];
    }

    /**
     * Get redundant API calls.
     * 
     * @return array<string, array{count: int, calls: array}> Array of redundant calls
     */
    protected function getRedundantApiCalls(): array
    {
        return $this->apiCallTracker?->getRedundantCalls() ?? [];
    }

    /**
     * Get schema API calls.
     * 
     * @return array<int, array{uri: string, method: string, timestamp: float}> Array of schema calls
     */
    protected function getSchemaApiCalls(): array
    {
        return $this->apiCallTracker?->getSchemaCalls() ?? [];
    }

    /**
     * Get redundant schema API calls.
     * 
     * @return array<string, array{count: int, calls: array}> Array of redundant schema calls
     */
    protected function getRedundantSchemaApiCalls(): array
    {
        return $this->apiCallTracker?->getRedundantSchemaCalls() ?? [];
    }

    /**
     * Get API call summary.
     * 
     * @return array{total: int, unique: int, redundant: int, schema_calls: int, crud6_calls: int}
     */
    protected function getApiCallSummary(): array
    {
        return $this->apiCallTracker?->getSummary() ?? [
            'total' => 0,
            'unique' => 0,
            'redundant' => 0,
            'schema_calls' => 0,
            'crud6_calls' => 0,
        ];
    }

    /**
     * Assert that there are no redundant API calls.
     * 
     * @param string|null $message Optional custom assertion message
     * 
     * @return void
     */
    protected function assertNoRedundantApiCalls(?string $message = null): void
    {
        $redundantCalls = $this->getRedundantApiCalls();
        
        if (!empty($redundantCalls)) {
            $report = $this->apiCallTracker?->getRedundantCallsReport() ?? 'Redundant calls detected';
            $message = $message ?? "No redundant API calls should be made during this test.\n\n" . $report;
        } else {
            $message = $message ?? "No redundant API calls detected";
        }

        $this->assertEmpty($redundantCalls, $message);
    }

    /**
     * Assert that there are no redundant schema API calls.
     * 
     * @param string|null $message Optional custom assertion message
     * 
     * @return void
     */
    protected function assertNoRedundantSchemaApiCalls(?string $message = null): void
    {
        $redundantSchemaCalls = $this->getRedundantSchemaApiCalls();
        
        if (!empty($redundantSchemaCalls)) {
            $report = $this->buildSchemaCallsReport($redundantSchemaCalls);
            $message = $message ?? "No redundant schema API calls should be made during this test.\n\n" . $report;
        } else {
            $message = $message ?? "No redundant schema API calls detected";
        }

        $this->assertEmpty($redundantSchemaCalls, $message);
    }

    /**
     * Assert specific number of API calls to an endpoint.
     * 
     * @param string $uri           The URI to check
     * @param int    $expectedCount Expected number of calls
     * @param string $message       Optional custom assertion message
     * 
     * @return void
     */
    protected function assertApiCallCount(string $uri, int $expectedCount, string $message = ''): void
    {
        $calls = $this->getTrackedApiCalls();
        $matchingCalls = array_filter($calls, fn($call) => $call['uri'] === $uri);
        $actualCount = count($matchingCalls);

        if ($message === '') {
            $message = sprintf(
                "Expected %d call(s) to %s, but found %d",
                $expectedCount,
                $uri,
                $actualCount
            );
        }

        $this->assertEquals($expectedCount, $actualCount, $message);
    }

    /**
     * Build a report for schema calls.
     * 
     * @param array<string, array{count: int, calls: array}> $redundantSchemaCalls
     * 
     * @return string The formatted report
     */
    private function buildSchemaCallsReport(array $redundantSchemaCalls): string
    {
        $report = "Redundant Schema API Calls Detected:\n";
        $report .= str_repeat("=", 80) . "\n\n";
        
        foreach ($redundantSchemaCalls as $key => $data) {
            $count = $data['count'];
            $calls = $data['calls'];
            $firstCall = $calls[0];
            
            $report .= sprintf("Schema Endpoint: %s %s\n", $firstCall['method'], $firstCall['uri']);
            $report .= sprintf("Called %d times (should be 1):\n", $count);
            
            foreach ($calls as $idx => $call) {
                $report .= sprintf(
                    "  %d. Time: %.4f, Trace: %s\n",
                    $idx + 1,
                    $call['timestamp'],
                    $call['trace']
                );
            }
            
            $report .= "\n";
        }
        
        return $report;
    }

    /**
     * Reset API tracking - clears all tracked calls and starts fresh.
     * 
     * This is useful when you want to track calls for a specific portion of a test
     * without including setup calls.
     * 
     * @return void
     */
    protected function resetApiTracking(): void
    {
        if ($this->apiCallTracker !== null) {
            $this->apiCallTracker->stopTracking();
        }
        $this->apiCallTracker = new ApiCallTracker();
        $this->apiCallTracker->startTracking();
    }

    /**
     * Cleanup after tests - stop tracking if still active.
     * 
     * @return void
     */
    protected function tearDownApiTracking(): void
    {
        $this->stopApiTracking();
        $this->apiCallTracker = null;
    }
}
