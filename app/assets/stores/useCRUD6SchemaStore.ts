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
     */
    async function loadSchema(model: string, force: boolean = false): Promise<CRUD6Schema | null> {
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

        console.log('[useCRUD6SchemaStore] Loading schema from API - model:', model, 'force:', force)
        loadingStates.value[model] = true
        errorStates.value[model] = null

        try {
            const response = await axios.get<any>(`/api/crud6/${model}/schema`)

            // Handle different response structures
            let schemaData: CRUD6Schema
            if (response.data.schema) {
                // Response has nested schema property
                schemaData = response.data.schema as CRUD6Schema
            } else if (response.data.fields) {
                // Response is the schema itself
                schemaData = response.data as CRUD6Schema
            } else {
                throw new Error('Invalid schema response')
            }
            
            schemas.value[model] = schemaData
            console.log('[useCRUD6SchemaStore] Schema loaded successfully - model:', model)
            return schemaData
        } catch (err: any) {
            const error = err.response?.data || { 
                title: 'Schema Load Error',
                description: 'Failed to load schema for model: ' + model
            }
            errorStates.value[model] = error
            console.error('[useCRUD6SchemaStore] Schema load failed - model:', model, error)
            return null
        } finally {
            loadingStates.value[model] = false
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
