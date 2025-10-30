import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { CRUD6Schema } from '../composables/useCRUD6Schema'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'

/**
 * Global Pinia store for CRUD6 schema management.
 * 
 * Provides centralized schema caching across all components to eliminate
 * duplicate API calls for the same model schema.
 */
export const useCRUD6SchemaStore = defineStore('crud6-schemas', () => {
    // Map of model name to schema data
    const schemas = ref<Record<string, CRUD6Schema>>({})
    
    // Map of model name to loading state
    const loadingStates = ref<Record<string, boolean>>({})
    
    // Map of model name to error state
    const errorStates = ref<Record<string, ApiErrorResponse | null>>({})

    /**
     * Check if a schema is already loaded for a model
     */
    function hasSchema(model: string): boolean {
        return !!schemas.value[model]
    }

    /**
     * Get schema for a model (may be undefined if not loaded)
     */
    function getSchema(model: string): CRUD6Schema | undefined {
        return schemas.value[model]
    }

    /**
     * Check if a schema is currently loading
     */
    function isLoading(model: string): boolean {
        return loadingStates.value[model] || false
    }

    /**
     * Get error for a model (if any)
     */
    function getError(model: string): ApiErrorResponse | null {
        return errorStates.value[model] || null
    }

    /**
     * Load schema for a specific model
     * Returns cached schema if available, otherwise fetches from API
     * 
     * @param model Model name to load schema for
     * @param force Force reload even if cached
     * @param context Optional context for filtering ('list', 'form', 'detail', 'meta')
     */
    async function loadSchema(model: string, force: boolean = false, context?: string): Promise<CRUD6Schema | null> {
        console.log('[useCRUD6SchemaStore] loadSchema called', {
            model,
            force,
            context,
            hasCache: hasSchema(model),
            isCurrentlyLoading: isLoading(model),
            timestamp: new Date().toISOString()
        })
        
        // Return cached schema if available and not forcing reload
        if (!force && hasSchema(model)) {
            console.log('[useCRUD6SchemaStore] Using cached schema - model:', model)
            return schemas.value[model] || null
        }

        // Return existing promise if already loading
        if (isLoading(model)) {
            console.log('[useCRUD6SchemaStore] Schema already loading - model:', model)
            // Wait for the loading to complete by polling
            return new Promise((resolve) => {
                const checkInterval = setInterval(() => {
                    if (!isLoading(model)) {
                        clearInterval(checkInterval)
                        resolve(schemas.value[model] || null)
                    }
                }, 100)
            })
        }

        console.log('[useCRUD6SchemaStore] Loading schema from API - model:', model, 'force:', force, 'context:', context)
        loadingStates.value[model] = true
        errorStates.value[model] = null

        try {
            // Build URL with optional context parameter
            let url = `/api/crud6/${model}/schema`
            if (context) {
                url += `?context=${encodeURIComponent(context)}`
            }
            
            console.log('[useCRUD6SchemaStore] Making API request', {
                url,
                method: 'GET',
                timestamp: new Date().toISOString()
            })
            
            const response = await axios.get<any>(url)
            
            console.log('[useCRUD6SchemaStore] API response received', {
                status: response.status,
                statusText: response.statusText,
                hasData: !!response.data,
                dataKeys: response.data ? Object.keys(response.data) : [],
                timestamp: new Date().toISOString()
            })

            // Handle different response structures
            let schemaData: CRUD6Schema
            if (response.data.schema) {
                // Response has nested schema property
                schemaData = response.data.schema as CRUD6Schema
                console.log('[useCRUD6SchemaStore] Schema found in response.data.schema')
            } else if (response.data.fields) {
                // Response is the schema itself
                schemaData = response.data as CRUD6Schema
                console.log('[useCRUD6SchemaStore] Schema found in response.data (direct)')
            } else {
                console.error('[useCRUD6SchemaStore] Invalid schema response structure', {
                    dataKeys: Object.keys(response.data),
                    data: response.data
                })
                throw new Error('Invalid schema response')
            }
            
            schemas.value[model] = schemaData
            console.log('[useCRUD6SchemaStore] Schema loaded successfully', {
                model,
                schemaKeys: Object.keys(schemaData),
                fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0,
                timestamp: new Date().toISOString()
            })
            return schemaData
        } catch (err: any) {
            console.error('[useCRUD6SchemaStore] Schema load error', {
                model,
                errorType: err.constructor.name,
                message: err.message,
                status: err.response?.status,
                statusText: err.response?.statusText,
                responseData: err.response?.data,
                timestamp: new Date().toISOString()
            })
            
            const error = err.response?.data || { 
                title: 'Schema Load Error',
                description: 'Failed to load schema for model: ' + model
            }
            errorStates.value[model] = error
            return null
        } finally {
            loadingStates.value[model] = false
            console.log('[useCRUD6SchemaStore] Loading state cleared for model:', model)
        }
    }

    /**
     * Set schema directly without making an API call
     * Useful when schema is already available from another source
     */
    function setSchema(model: string, schemaData: CRUD6Schema): void {
        schemas.value[model] = schemaData
        errorStates.value[model] = null
        console.log('[useCRUD6SchemaStore] Schema set directly - model:', model)
    }

    /**
     * Clear cached schema for a model
     */
    function clearSchema(model: string): void {
        delete schemas.value[model]
        delete loadingStates.value[model]
        delete errorStates.value[model]
    }

    /**
     * Clear all cached schemas
     */
    function clearAllSchemas(): void {
        schemas.value = {}
        loadingStates.value = {}
        errorStates.value = {}
    }

    return {
        // State
        schemas: computed(() => schemas.value),
        
        // Getters
        hasSchema,
        getSchema,
        isLoading,
        getError,
        
        // Actions
        loadSchema,
        setSchema,
        clearSchema,
        clearAllSchemas
    }
})
