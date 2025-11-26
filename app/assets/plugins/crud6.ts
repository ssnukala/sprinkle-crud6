import type { App } from 'vue'
import axios from 'axios'
import { debugLog, debugError, initDebugMode } from '../utils/debug'

// Log when this module is loaded (before plugin install)
console.log('[CRUD6 Plugin] Module loaded')

/**
 * CRUD6 Sprinkle Plugin
 * 
 * Provides global initialization for CRUD6:
 * - Axios interceptors for CRUD6 API debugging
 * - Debug mode initialization from backend config
 * 
 * Note: Components and views are NOT registered globally to avoid eager loading.
 * Views are lazy-loaded via router, and components are imported locally where needed.
 * This prevents unnecessary module loading and YAML imports from other sprinkles.
 */
export default {
    install: (app: App) => {
        console.log('[CRUD6 Plugin] Install called')
        
        // Initialize debug mode from backend config
        // This runs asynchronously and updates debug mode when ready
        // The initDebugMode() promise is stored so subsequent calls can await it
        initDebugMode().then(() => {
            console.log('[CRUD6 Plugin] Debug mode initialization complete')
        }).catch(error => {
            console.error('[CRUD6 Plugin] Failed to initialize debug mode:', error)
        })
        
        // Add axios request interceptor for CRUD6 debugging
        axios.interceptors.request.use(
            (config) => {
                if (config.url?.includes('/api/crud6/')) {
                    debugLog('[CRUD6 Axios] ===== REQUEST START =====', {
                        method: config.method?.toUpperCase(),
                        url: config.url,
                        baseURL: config.baseURL,
                        fullURL: (config.baseURL || '') + (config.url || ''),
                        params: config.params,
                        data: config.data,
                        headers: config.headers,
                        timestamp: new Date().toISOString()
                    })
                }
                return config
            },
            (error) => {
                debugError('[CRUD6 Axios] Request error', error)
                return Promise.reject(error)
            }
        )

        // Add axios response interceptor for CRUD6 debugging
        axios.interceptors.response.use(
            (response) => {
                if (response.config.url?.includes('/api/crud6/')) {
                    debugLog('[CRUD6 Axios] ===== RESPONSE RECEIVED =====', {
                        method: response.config.method?.toUpperCase(),
                        url: response.config.url,
                        status: response.status,
                        statusText: response.statusText,
                        dataKeys: response.data ? Object.keys(response.data) : [],
                        dataPreview: typeof response.data === 'object' ? 
                            JSON.stringify(response.data).substring(0, 200) : 
                            String(response.data).substring(0, 200),
                        headers: response.headers,
                        timestamp: new Date().toISOString()
                    })
                }
                return response
            },
            (error) => {
                if (error.config?.url?.includes('/api/crud6/')) {
                    debugError('[CRUD6 Axios] ===== RESPONSE ERROR =====', {
                        method: error.config?.method?.toUpperCase(),
                        url: error.config?.url,
                        status: error.response?.status,
                        statusText: error.response?.statusText,
                        errorMessage: error.message,
                        responseData: error.response?.data,
                        timestamp: new Date().toISOString()
                    })
                }
                return Promise.reject(error)
            }
        )
    }
}
