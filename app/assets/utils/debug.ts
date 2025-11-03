/**
 * CRUD6 Debug Utility
 * 
 * Provides conditional debug logging for frontend code based on configuration.
 * Matches the backend debug_mode functionality.
 */

/**
 * Debug mode configuration
 * 
 * Set this to true to enable frontend debug logging.
 * Should match backend crud6.debug_mode configuration.
 */
let debugMode = false;

/**
 * Initialize debug mode from configuration
 * 
 * This should be called early in the application lifecycle,
 * ideally from a config service or environment variable.
 * 
 * @param enabled - Whether debug mode is enabled
 */
export function setDebugMode(enabled: boolean): void {
    debugMode = enabled;
}

/**
 * Get current debug mode status
 * 
 * @returns True if debug mode is enabled
 */
export function isDebugMode(): boolean {
    return debugMode;
}

/**
 * Conditional debug log
 * 
 * Logs to console only when debug mode is enabled.
 * Replaces direct console.log calls throughout the codebase.
 * 
 * @param message - Debug message
 * @param args - Additional arguments to log
 */
export function debugLog(message: string, ...args: any[]): void {
    if (debugMode) {
        console.log(message, ...args);
    }
}

/**
 * Conditional debug warn
 * 
 * Logs warning to console only when debug mode is enabled.
 * 
 * @param message - Warning message
 * @param args - Additional arguments to log
 */
export function debugWarn(message: string, ...args: any[]): void {
    if (debugMode) {
        console.warn(message, ...args);
    }
}

/**
 * Conditional debug error
 * 
 * Logs error to console only when debug mode is enabled.
 * Note: Critical errors should use console.error directly.
 * 
 * @param message - Error message
 * @param args - Additional arguments to log
 */
export function debugError(message: string, ...args: any[]): void {
    if (debugMode) {
        console.error(message, ...args);
    }
}

/**
 * Always log critical errors
 * 
 * This bypasses debug mode and always logs to console.error.
 * Use for errors that should always be visible regardless of debug mode.
 * 
 * @param message - Error message
 * @param args - Additional arguments to log
 */
export function logError(message: string, ...args: any[]): void {
    console.error(message, ...args);
}

export default {
    setDebugMode,
    isDebugMode,
    debugLog,
    debugWarn,
    debugError,
    logError,
};
