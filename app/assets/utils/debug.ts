/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * CRUD6 Debug Utility
 * 
 * Provides conditional debug logging for frontend code based on configuration.
 * Matches the backend debug_mode functionality.
 * 
 * The debug mode is fetched from backend config on each page load.
 * Value is cached in memory until the page is refreshed.
 */

/**
 * Debug mode configuration - cached in memory after fetching from backend
 * Defaults to false until initialized
 */
let debugMode = false;

/**
 * Track if initialization has been attempted
 */
let initialized = false;

/**
 * Initialize debug mode from backend configuration
 * 
 * Fetches debug_mode from /api/crud6/config on page load.
 * Value is cached in memory until the page is refreshed.
 * Should be called once on page load.
 */
export async function initDebugMode(): Promise<void> {
    if (initialized) {
        return; // Already initialized this page load
    }
    
    try {
        const response = await fetch('/api/crud6/config');
        if (response.ok) {
            const config = await response.json();
            debugMode = config.debug_mode === true;
        }
    } catch (error) {
        // Silently fail - debug mode will remain false
    }
    
    initialized = true;
}

/**
 * Set debug mode manually (for testing)
 */
export function setDebugMode(enabled: boolean): void {
    debugMode = enabled;
    initialized = true;
}

/**
 * Get current debug mode status
 */
export function isDebugMode(): boolean {
    return debugMode;
}

/**
 * Conditional debug log - only logs when debug mode is enabled
 */
export function debugLog(message: string, ...args: any[]): void {
    if (debugMode) {
        console.log(message, ...args);
    }
}

/**
 * Conditional debug warn - only logs when debug mode is enabled
 */
export function debugWarn(message: string, ...args: any[]): void {
    if (debugMode) {
        console.warn(message, ...args);
    }
}

/**
 * Conditional debug error - only logs when debug mode is enabled
 */
export function debugError(message: string, ...args: any[]): void {
    if (debugMode) {
        console.error(message, ...args);
    }
}

/**
 * Always log critical errors (bypasses debug mode)
 */
export function logError(message: string, ...args: any[]): void {
    console.error(message, ...args);
}

export default {
    initDebugMode,
    setDebugMode,
    isDebugMode,
    debugLog,
    debugWarn,
    debugError,
    logError,
};
