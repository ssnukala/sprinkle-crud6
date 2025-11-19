/**
 * Network Request Tracker for Frontend Integration Tests
 * 
 * This module provides functionality to track and analyze network requests made
 * from the frontend during integration tests. It helps identify redundant API calls,
 * duplicate requests, and performance issues.
 * 
 * Usage:
 * ```javascript
 * import NetworkRequestTracker from './NetworkRequestTracker';
 * 
 * const tracker = new NetworkRequestTracker();
 * tracker.startTracking();
 * 
 * // Make API calls...
 * await fetch('/api/crud6/users');
 * await fetch('/api/crud6/users'); // Duplicate!
 * 
 * const redundantCalls = tracker.getRedundantCalls();
 * console.log('Redundant calls:', redundantCalls);
 * 
 * tracker.stopTracking();
 * ```
 */

export class NetworkRequestTracker {
    constructor() {
        this.requests = [];
        this.tracking = false;
        this.originalFetch = null;
        this.originalXHROpen = null;
        this.originalXHRSend = null;
    }

    /**
     * Start tracking network requests
     */
    startTracking() {
        this.tracking = true;
        this.requests = [];
        this.interceptFetch();
        this.interceptXHR();
    }

    /**
     * Stop tracking network requests
     */
    stopTracking() {
        this.tracking = false;
        this.restoreFetch();
        this.restoreXHR();
    }

    /**
     * Track a network request
     * 
     * @param {string} url - The request URL
     * @param {string} method - The HTTP method
     * @param {Object} options - Additional request options
     */
    trackRequest(url, method, options = {}) {
        if (!this.tracking) {
            return;
        }

        const request = {
            url: this.normalizeUrl(url),
            method: method.toUpperCase(),
            timestamp: Date.now(),
            params: options.params || {},
            body: options.body,
            trace: this.getStackTrace(),
            key: this.generateRequestKey(url, method, options)
        };

        this.requests.push(request);
    }

    /**
     * Get all tracked requests
     * 
     * @returns {Array} Array of tracked requests
     */
    getRequests() {
        return this.requests;
    }

    /**
     * Get redundant requests (requests made more than once to the same endpoint)
     * 
     * @returns {Object} Object mapping request keys to arrays of redundant calls
     */
    getRedundantCalls() {
        const frequency = {};
        const redundant = {};

        // Count frequency of each request
        this.requests.forEach(req => {
            if (!frequency[req.key]) {
                frequency[req.key] = [];
            }
            frequency[req.key].push(req);
        });

        // Filter to only redundant calls (count > 1)
        Object.keys(frequency).forEach(key => {
            if (frequency[key].length > 1) {
                redundant[key] = {
                    count: frequency[key].length,
                    calls: frequency[key]
                };
            }
        });

        return redundant;
    }

    /**
     * Get schema API calls (calls to /api/crud6/{model}/schema)
     * 
     * @returns {Array} Array of schema API calls
     */
    getSchemaCalls() {
        return this.requests.filter(req => this.isSchemaCall(req.url));
    }

    /**
     * Get redundant schema calls
     * 
     * @returns {Object} Object mapping schema endpoints to redundant call data
     */
    getRedundantSchemaCalls() {
        const redundant = this.getRedundantCalls();
        const schemaRedundant = {};

        Object.keys(redundant).forEach(key => {
            const firstCall = redundant[key].calls[0];
            if (this.isSchemaCall(firstCall.url)) {
                schemaRedundant[key] = redundant[key];
            }
        });

        return schemaRedundant;
    }

    /**
     * Get CRUD6 API calls (any call to /api/crud6/*)
     * 
     * @returns {Array} Array of CRUD6 API calls
     */
    getCRUD6Calls() {
        return this.requests.filter(req => this.isCRUD6Call(req.url));
    }

    /**
     * Check if there are any redundant calls
     * 
     * @returns {boolean} True if redundant calls exist
     */
    hasRedundantCalls() {
        return Object.keys(this.getRedundantCalls()).length > 0;
    }

    /**
     * Get a summary of tracked requests
     * 
     * @returns {Object} Summary statistics
     */
    getSummary() {
        const redundantCalls = this.getRedundantCalls();
        
        return {
            total: this.requests.length,
            unique: this.requests.length - Object.values(redundantCalls).reduce((sum, r) => sum + r.count - 1, 0),
            redundant: Object.keys(redundantCalls).length,
            schemaCalls: this.getSchemaCalls().length,
            crud6Calls: this.getCRUD6Calls().length
        };
    }

    /**
     * Get a formatted report of redundant calls
     * 
     * @returns {string} Formatted report
     */
    getRedundantCallsReport() {
        const redundant = this.getRedundantCalls();
        
        if (Object.keys(redundant).length === 0) {
            return 'No redundant calls detected.';
        }
        
        let report = 'Redundant Network Requests Detected:\n';
        report += '='.repeat(80) + '\n\n';
        
        Object.keys(redundant).forEach(key => {
            const data = redundant[key];
            const firstCall = data.calls[0];
            
            report += `Endpoint: ${firstCall.method} ${firstCall.url}\n`;
            report += `Called ${data.count} times (should be 1):\n`;
            
            data.calls.forEach((call, idx) => {
                report += `  ${idx + 1}. Time: ${new Date(call.timestamp).toISOString()}\n`;
                if (call.trace) {
                    report += `     Trace: ${call.trace}\n`;
                }
            });
            
            report += '\n';
        });
        
        return report;
    }

    /**
     * Reset the tracker (clear all tracked requests)
     */
    reset() {
        this.requests = [];
    }

    // Private helper methods

    /**
     * Intercept fetch API calls
     */
    interceptFetch() {
        if (typeof window.fetch === 'undefined') {
            return;
        }

        this.originalFetch = window.fetch;
        const self = this;

        window.fetch = function(url, options = {}) {
            const method = options.method || 'GET';
            
            // Track the request
            self.trackRequest(url, method, {
                params: self.extractQueryParams(url),
                body: options.body
            });

            // Call original fetch
            return self.originalFetch.apply(this, arguments);
        };
    }

    /**
     * Restore original fetch API
     */
    restoreFetch() {
        if (this.originalFetch) {
            window.fetch = this.originalFetch;
            this.originalFetch = null;
        }
    }

    /**
     * Intercept XMLHttpRequest calls
     */
    interceptXHR() {
        if (typeof window.XMLHttpRequest === 'undefined') {
            return;
        }

        const self = this;
        this.originalXHROpen = XMLHttpRequest.prototype.open;
        this.originalXHRSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._trackingData = { method, url };
            return self.originalXHROpen.apply(this, [method, url, ...args]);
        };

        XMLHttpRequest.prototype.send = function(body) {
            if (this._trackingData) {
                self.trackRequest(this._trackingData.url, this._trackingData.method, {
                    params: self.extractQueryParams(this._trackingData.url),
                    body: body
                });
            }
            return self.originalXHRSend.apply(this, arguments);
        };
    }

    /**
     * Restore original XMLHttpRequest
     */
    restoreXHR() {
        if (this.originalXHROpen) {
            XMLHttpRequest.prototype.open = this.originalXHROpen;
            this.originalXHROpen = null;
        }
        if (this.originalXHRSend) {
            XMLHttpRequest.prototype.send = this.originalXHRSend;
            this.originalXHRSend = null;
        }
    }

    /**
     * Normalize URL (remove domain, keep path and query)
     * 
     * @param {string} url - The URL to normalize
     * @returns {string} Normalized URL
     */
    normalizeUrl(url) {
        try {
            const urlObj = new URL(url, window.location.origin);
            return urlObj.pathname + urlObj.search;
        } catch (e) {
            return url;
        }
    }

    /**
     * Extract query parameters from URL
     * 
     * @param {string} url - The URL to extract from
     * @returns {Object} Query parameters
     */
    extractQueryParams(url) {
        try {
            const urlObj = new URL(url, window.location.origin);
            const params = {};
            urlObj.searchParams.forEach((value, key) => {
                params[key] = value;
            });
            return params;
        } catch (e) {
            return {};
        }
    }

    /**
     * Generate a unique key for a request
     * 
     * @param {string} url - The request URL
     * @param {string} method - The HTTP method
     * @param {Object} options - Request options
     * @returns {string} Unique request key
     */
    generateRequestKey(url, method, options = {}) {
        const normalizedUrl = this.normalizeUrl(url);
        const parsedUrl = new URL(normalizedUrl, window.location.origin);
        const path = parsedUrl.pathname;

        // For schema calls, ignore query parameters
        if (this.isSchemaCall(path)) {
            return `${method.toUpperCase()}:${path}`;
        }

        // For other calls, include query parameters
        const params = this.extractQueryParams(normalizedUrl);
        const sortedParams = Object.keys(params).sort().map(k => `${k}=${params[k]}`).join('&');
        const queryString = sortedParams ? `?${sortedParams}` : '';

        return `${method.toUpperCase()}:${path}${queryString}`;
    }

    /**
     * Check if a URL is a schema API call
     * 
     * @param {string} url - The URL to check
     * @returns {boolean} True if schema call
     */
    isSchemaCall(url) {
        return /\/api\/crud6\/[^\/]+\/schema/.test(url);
    }

    /**
     * Check if a URL is a CRUD6 API call
     * 
     * @param {string} url - The URL to check
     * @returns {boolean} True if CRUD6 call
     */
    isCRUD6Call(url) {
        return url.startsWith('/api/crud6/');
    }

    /**
     * Get a simplified stack trace for debugging
     * 
     * @returns {string} Stack trace string
     */
    getStackTrace() {
        try {
            const stack = new Error().stack;
            if (!stack) return 'No stack trace available';
            
            const lines = stack.split('\n');
            // Skip first 3 lines (Error, this method, trackRequest)
            const relevantLines = lines.slice(3, 6);
            return relevantLines.map(line => line.trim()).join(' <- ');
        } catch (e) {
            return 'Stack trace unavailable';
        }
    }
}

export default NetworkRequestTracker;
