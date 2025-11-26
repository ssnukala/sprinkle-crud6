/**
 * CRUD6 Debug Utility
 * 
 * Provides conditional debug logging for frontend code based on configuration.
 * Matches the backend debug_mode functionality.
 * 
 * The debug mode is fetched from backend config on page load and stored in sessionStorage.
 */

/**
 * Session storage key for debug mode
 */
const DEBUG_MODE_KEY = 'crud6_debug_mode';

/**
 * Debug mode configuration - read from sessionStorage on module load
 */
let debugMode = sessionStorage.getItem(DEBUG_MODE_KEY) === 'true';

/**
 * Initialize debug mode from backend configuration
 * 
 * Fetches debug_mode from /api/crud6/config and stores in sessionStorage.
 * Should be called on page load.
 */
export async function initDebugMode(): Promise<void> {
    try {
        const response = await fetch('/api/crud6/config');
        if (response.ok) {
            const config = await response.json();
            debugMode = config.debug_mode === true;
            sessionStorage.setItem(DEBUG_MODE_KEY, String(debugMode));
            console.log('[CRUD6 Debug] Initialized:', { debug_mode: debugMode });
        }
    } catch (error) {
        console.warn('[CRUD6 Debug] Failed to fetch config:', error);
    }
}

/**
 * Set debug mode manually
 */
export function setDebugMode(enabled: boolean): void {
    debugMode = enabled;
    sessionStorage.setItem(DEBUG_MODE_KEY, String(enabled));
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
