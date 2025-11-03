/**
 * CRUD6 Debug Utility
 * 
 * Provides conditional debug logging for frontend code based on configuration.
 * Matches the backend debug_mode functionality.
 * 
 * The debug mode is automatically initialized from:
 * 1. sessionStorage (if previously fetched this session)
 * 2. Backend config via /api/crud6/config endpoint (fetched once per session)
 * 3. Environment variable VITE_DEBUG_MODE as fallback
 */

/**
 * Session storage key for debug mode
 */
const DEBUG_MODE_SESSION_KEY = 'crud6_debug_mode';

/**
 * Debug mode configuration
 * 
 * Set this to true to enable frontend debug logging.
 * Should match backend crud6.debug_mode configuration.
 * 
 * Initializes from sessionStorage if available, otherwise from environment variable.
 * The backend config is fetched once per session and stored.
 */
let debugMode = getDebugModeFromSession();

/**
 * Get debug mode from sessionStorage
 * Falls back to environment variable if not found
 */
function getDebugModeFromSession(): boolean {
    try {
        const stored = sessionStorage.getItem(DEBUG_MODE_SESSION_KEY);
        if (stored !== null) {
            return stored === 'true';
        }
    } catch (error) {
        // sessionStorage not available or disabled
        console.warn('[CRUD6 Debug] sessionStorage not available:', error);
    }
    
    // Fallback to environment variable
    return import.meta.env.VITE_DEBUG_MODE === 'true';
}

/**
 * Save debug mode to sessionStorage
 */
function saveDebugModeToSession(enabled: boolean): void {
    try {
        sessionStorage.setItem(DEBUG_MODE_SESSION_KEY, String(enabled));
    } catch (error) {
        // sessionStorage not available or disabled
        console.warn('[CRUD6 Debug] Failed to save to sessionStorage:', error);
    }
}

/**
 * Track if debug mode has been initialized from backend config
 */
let initialized = false;

/**
 * Initialize debug mode from backend configuration
 * 
 * This fetches the debug_mode setting from the backend API
 * and synchronizes the frontend debug mode with it.
 * 
 * The setting is cached in sessionStorage to avoid repeated API calls.
 * The API is only called once per browser session.
 * 
 * This should be called early in the application lifecycle,
 * ideally from the main plugin initialization.
 * 
 * Falls back to environment variable if API call fails.
 */
export async function initDebugMode(): Promise<void> {
    if (initialized) {
        return; // Already initialized
    }

    // Check if we already have the value in sessionStorage
    try {
        const stored = sessionStorage.getItem(DEBUG_MODE_SESSION_KEY);
        if (stored !== null) {
            debugMode = stored === 'true';
            initialized = true;
            
            // Log initialization (always show this, regardless of debug mode)
            console.log('[CRUD6 Debug] Initialized from sessionStorage:', {
                debug_mode: debugMode,
                source: 'session-storage'
            });
            return;
        }
    } catch (error) {
        // sessionStorage not available, continue to API fetch
    }

    // Not in sessionStorage, fetch from backend API (only once per session)
    try {
        const response = await fetch('/api/crud6/config');
        if (response.ok) {
            const config = await response.json();
            debugMode = config.debug_mode === true;
            initialized = true;
            
            // Save to sessionStorage for future use
            saveDebugModeToSession(debugMode);
            
            // Log initialization (always show this, regardless of debug mode)
            console.log('[CRUD6 Debug] Initialized from backend config:', {
                debug_mode: debugMode,
                source: 'backend-api',
                cached_to: 'session-storage'
            });
        } else {
            // Failed to fetch, use env variable
            debugMode = import.meta.env.VITE_DEBUG_MODE === 'true';
            console.warn('[CRUD6 Debug] Failed to fetch backend config, using environment variable:', {
                debug_mode: debugMode,
                source: 'env-variable',
                status: response.status
            });
            initialized = true;
            
            // Save to sessionStorage to avoid repeated failed API calls
            saveDebugModeToSession(debugMode);
        }
    } catch (error) {
        // Error fetching config, use env variable
        debugMode = import.meta.env.VITE_DEBUG_MODE === 'true';
        console.warn('[CRUD6 Debug] Error fetching backend config, using environment variable:', {
            debug_mode: debugMode,
            source: 'env-variable',
            error: error instanceof Error ? error.message : String(error)
        });
        initialized = true;
        
        // Save to sessionStorage to avoid repeated failed API calls
        saveDebugModeToSession(debugMode);
    }
}

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
    initialized = true;
    
    // Save to sessionStorage
    saveDebugModeToSession(enabled);
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
    initDebugMode,
    setDebugMode,
    isDebugMode,
    debugLog,
    debugWarn,
    debugError,
    logError,
};
