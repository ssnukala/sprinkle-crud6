/**
 * Shared CRUD6 API Client
 * 
 * A centralized axios instance configured for CRUD6 API endpoints.
 * Provides consistent configuration, error handling, and interceptors
 * for all CRUD6 API calls.
 * 
 * Features:
 * - Pre-configured base URL for CRUD6 endpoints
 * - Consistent JSON headers
 * - Response interceptor for centralized error handling
 * - Request interceptor for debugging (when enabled)
 * 
 * @example
 * ```typescript
 * import { crud6Api } from '@ssnukala/sprinkle-crud6/utils'
 * 
 * // Simple GET request
 * const response = await crud6Api.get('/users/1')
 * 
 * // POST request with data
 * await crud6Api.post('/users', { name: 'John', email: 'john@example.com' })
 * 
 * // Use with full URL path (base URL will be prepended)
 * await crud6Api.get('/products') // calls /api/crud6/products
 * ```
 */
import axios, { type AxiosInstance, type AxiosResponse, type AxiosError } from 'axios'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { debugLog, debugError } from './debug'

/**
 * HTTP status code for validation errors.
 * Validation errors (422) are handled by forms, not displayed as alerts.
 */
const VALIDATION_ERROR_STATUS = 422

/**
 * Create a shared axios instance for CRUD6 API calls.
 * 
 * Configuration:
 * - baseURL: '/api/crud6' - All requests are relative to this path
 * - Content-Type: 'application/json' - Default for all requests
 * - Accept: 'application/json' - Expect JSON responses
 */
export const crud6Api: AxiosInstance = axios.create({
    baseURL: '/api/crud6',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

/**
 * Request interceptor for debugging and request modification.
 * 
 * Logs outgoing requests when debug mode is enabled.
 * Can be extended to add authentication tokens or other headers.
 */
crud6Api.interceptors.request.use(
    (config) => {
        debugLog('[crud6Api] Request', {
            method: config.method?.toUpperCase(),
            url: config.url,
            baseURL: config.baseURL,
            data: config.data,
            params: config.params
        })
        return config
    },
    (error) => {
        debugError('[crud6Api] Request error', error)
        return Promise.reject(error)
    }
)

/**
 * Response interceptor for centralized error handling.
 * 
 * Successful responses are passed through unchanged.
 * Error responses trigger alerts using the UserFrosting alerts store.
 */
crud6Api.interceptors.response.use(
    (response: AxiosResponse) => {
        debugLog('[crud6Api] Response', {
            url: response.config.url,
            status: response.status,
            statusText: response.statusText,
            hasData: !!response.data
        })
        return response
    },
    (error: AxiosError<ApiErrorResponse>) => {
        debugError('[crud6Api] Response error', {
            url: error.config?.url,
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data
        })

        // Only show alert for API errors with message
        // Skip for validation errors (422) as they're handled by forms
        if (error.response?.data && error.response.status !== VALIDATION_ERROR_STATUS) {
            const errorData = error.response.data
            const alertsStore = useAlertsStore()
            
            // Check if error has a title or description to display
            if (errorData.title || errorData.description || errorData.message) {
                alertsStore.push({
                    title: errorData.title || 'Error',
                    description: errorData.description || errorData.message || 'An error occurred',
                    style: Severity.Danger
                })
            }
        }

        return Promise.reject(error)
    }
)

/**
 * Helper function to build CRUD6 API URLs.
 * 
 * @param model - The model name
 * @param id - Optional record ID
 * @param suffix - Optional URL suffix (e.g., 'schema', field name)
 * @returns The constructed URL path
 * 
 * @example
 * ```typescript
 * buildCrud6Url('users')           // '/users'
 * buildCrud6Url('users', '123')    // '/users/123'
 * buildCrud6Url('users', '123', 'name') // '/users/123/name'
 * buildCrud6Url('users', null, 'schema') // '/users/schema'
 * ```
 */
export function buildCrud6Url(model: string, id?: string | number | null, suffix?: string): string {
    let url = `/${model}`
    
    if (id !== undefined && id !== null) {
        url += `/${id}`
    }
    
    if (suffix) {
        url += `/${suffix}`
    }
    
    return url
}

/**
 * Type for CRUD6 API response with standard fields.
 */
export interface Crud6ApiResponse<T = any> {
    title?: string
    description?: string
    message?: string
    data?: T
    model?: string
    modelDisplayName?: string
    id?: string | number
}

/**
 * Type for paginated list response from Sprunje.
 */
export interface Crud6ListResponse<T = any> {
    count: number
    count_filtered: number
    rows: T[]
    listable?: string[]
}

export default crud6Api
