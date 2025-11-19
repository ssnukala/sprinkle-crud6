<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

/**
 * API Call Tracker for Integration Tests.
 * 
 * Tracks all API calls made during integration tests to detect redundant/duplicate calls.
 * This helps identify performance issues and unnecessary API requests.
 * 
 * Usage in tests:
 * ```php
 * $tracker = new ApiCallTracker();
 * $tracker->startTracking();
 * // ... make API calls ...
 * $redundantCalls = $tracker->getRedundantCalls();
 * $tracker->stopTracking();
 * ```
 */
class ApiCallTracker
{
    /**
     * @var array<int, array{uri: string, method: string, timestamp: float, trace: string}> Tracked API calls
     */
    private array $calls = [];

    /**
     * @var bool Whether tracking is currently active
     */
    private bool $tracking = false;

    /**
     * @var array<string, int> Call frequency map (uri+method => count)
     */
    private array $callFrequency = [];

    /**
     * Start tracking API calls.
     * 
     * @return void
     */
    public function startTracking(): void
    {
        $this->tracking = true;
        $this->calls = [];
        $this->callFrequency = [];
    }

    /**
     * Stop tracking API calls.
     * 
     * @return void
     */
    public function stopTracking(): void
    {
        $this->tracking = false;
    }

    /**
     * Track an API call.
     * 
     * @param string $uri    The request URI
     * @param string $method The HTTP method
     * @param array  $params Optional request parameters
     * 
     * @return void
     */
    public function trackCall(string $uri, string $method, array $params = []): void
    {
        if (!$this->tracking) {
            return;
        }

        // Generate a unique key for this call (uri + method)
        $key = $this->generateCallKey($uri, $method, $params);

        // Track the call
        $this->calls[] = [
            'uri' => $uri,
            'method' => $method,
            'params' => $params,
            'timestamp' => microtime(true),
            'trace' => $this->getCaller(),
            'key' => $key,
        ];

        // Update frequency map
        if (!isset($this->callFrequency[$key])) {
            $this->callFrequency[$key] = 0;
        }
        $this->callFrequency[$key]++;
    }

    /**
     * Get all tracked calls.
     * 
     * @return array<int, array{uri: string, method: string, timestamp: float, trace: string}> Array of tracked calls
     */
    public function getCalls(): array
    {
        return $this->calls;
    }

    /**
     * Get redundant calls (calls made more than once to the same endpoint).
     * 
     * @return array<string, array{count: int, calls: array}> Array of redundant calls grouped by endpoint
     */
    public function getRedundantCalls(): array
    {
        $redundant = [];

        foreach ($this->callFrequency as $key => $count) {
            if ($count > 1) {
                // Find all calls with this key
                $matchingCalls = array_filter($this->calls, fn($call) => $call['key'] === $key);
                
                $redundant[$key] = [
                    'count' => $count,
                    'calls' => array_values($matchingCalls),
                ];
            }
        }

        return $redundant;
    }

    /**
     * Get schema API calls (calls to /api/crud6/{model}/schema).
     * 
     * @return array<int, array{uri: string, method: string, timestamp: float}> Array of schema calls
     */
    public function getSchemaCalls(): array
    {
        return array_filter($this->calls, function ($call) {
            return $this->isSchemaCall($call['uri']);
        });
    }

    /**
     * Get redundant schema calls.
     * 
     * @return array<string, array{count: int, calls: array}> Array of redundant schema calls
     */
    public function getRedundantSchemaCalls(): array
    {
        $redundant = $this->getRedundantCalls();
        
        // Filter to only schema calls
        return array_filter($redundant, function ($key) use ($redundant) {
            // Extract URI from the first call in the group
            $firstCall = $redundant[$key]['calls'][0] ?? null;
            return $firstCall && $this->isSchemaCall($firstCall['uri']);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get CRUD6 API calls (any call to /api/crud6/*).
     * 
     * @return array<int, array{uri: string, method: string, timestamp: float}> Array of CRUD6 API calls
     */
    public function getCRUD6Calls(): array
    {
        return array_filter($this->calls, function ($call) {
            return $this->isCRUD6Call($call['uri']);
        });
    }

    /**
     * Check if there are any redundant calls.
     * 
     * @return bool True if redundant calls exist
     */
    public function hasRedundantCalls(): bool
    {
        return count($this->getRedundantCalls()) > 0;
    }

    /**
     * Get a summary of tracked calls.
     * 
     * @return array{total: int, unique: int, redundant: int, schema_calls: int, crud6_calls: int}
     */
    public function getSummary(): array
    {
        return [
            'total' => count($this->calls),
            'unique' => count($this->callFrequency),
            'redundant' => count($this->getRedundantCalls()),
            'schema_calls' => count($this->getSchemaCalls()),
            'crud6_calls' => count($this->getCRUD6Calls()),
        ];
    }

    /**
     * Generate a unique key for a call.
     * 
     * @param string $uri    The request URI
     * @param string $method The HTTP method
     * @param array  $params Request parameters
     * 
     * @return string Unique key for the call
     */
    private function generateCallKey(string $uri, string $method, array $params = []): string
    {
        // Normalize URI (remove query string, keep path only)
        $parsedUri = parse_url($uri);
        $path = $parsedUri['path'] ?? $uri;
        
        // For schema calls, group by model only (ignore query params)
        if ($this->isSchemaCall($path)) {
            return sprintf('%s:%s', strtoupper($method), $path);
        }
        
        // For other calls, include significant query params
        $queryString = '';
        if (!empty($params)) {
            ksort($params);
            $queryString = '?' . http_build_query($params);
        }
        
        return sprintf('%s:%s%s', strtoupper($method), $path, $queryString);
    }

    /**
     * Check if a URI is a schema API call.
     * 
     * @param string $uri The request URI
     * 
     * @return bool True if schema call
     */
    private function isSchemaCall(string $uri): bool
    {
        return (bool) preg_match('#/api/crud6/[^/]+/schema#', $uri);
    }

    /**
     * Check if a URI is a CRUD6 API call.
     * 
     * @param string $uri The request URI
     * 
     * @return bool True if CRUD6 API call
     */
    private function isCRUD6Call(string $uri): bool
    {
        return str_starts_with($uri, '/api/crud6/');
    }

    /**
     * Get caller information for debugging.
     * 
     * @return string Caller trace string
     */
    private function getCaller(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $callers = [];
        
        // Skip the first 2 entries (this method and trackCall)
        for ($i = 2; $i < min(count($trace), 5); $i++) {
            $frame = $trace[$i];
            $class = $frame['class'] ?? '';
            $function = $frame['function'] ?? '';
            $line = $trace[$i - 1]['line'] ?? '?';
            
            if ($class && $function) {
                $callers[] = sprintf("%s::%s():%s", basename($class), $function, $line);
            }
        }
        
        return implode(' <- ', $callers);
    }

    /**
     * Reset the tracker (clear all tracked calls).
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->calls = [];
        $this->callFrequency = [];
    }

    /**
     * Get a formatted report of redundant calls.
     * 
     * @return string Formatted report
     */
    public function getRedundantCallsReport(): string
    {
        $redundant = $this->getRedundantCalls();
        
        if (empty($redundant)) {
            return "No redundant calls detected.";
        }
        
        $report = "Redundant API Calls Detected:\n";
        $report .= str_repeat("=", 80) . "\n\n";
        
        foreach ($redundant as $key => $data) {
            $count = $data['count'];
            $calls = $data['calls'];
            $firstCall = $calls[0];
            
            $report .= sprintf("Endpoint: %s %s\n", $firstCall['method'], $firstCall['uri']);
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
}
