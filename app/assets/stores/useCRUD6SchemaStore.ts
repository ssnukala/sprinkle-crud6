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
 * 
 * Caching Strategy:
 * - Schemas are cached by model name AND context
 * - Cache key format: `${model}:${context || 'full'}`
 * - Different contexts of same model are cached separately
 * - This prevents duplicate API calls while supporting context filtering
 */
export const useCRUD6SchemaStore = defineStore('crud6-schemas', () => {
    // Map of cache key (model:context) to schema data
    const schemas = ref<Record<string, CRUD6Schema>>({})
    
    // Map of cache key to loading state
    const loadingStates = ref<Record<string, boolean>>({})
    
    // Map of cache key to error state
    const errorStates = ref<Record<string, ApiErrorResponse | null>>({})

    /**
     * Generate cache key for model and context
     */
    function getCacheKey(model: string, context?: string): string {
        return `${model}:${context || 'full'}`
    }

    /**
     * Check if a schema is already loaded for a model+context
     */
    function hasSchema(model: string, context?: string): boolean {
        const key = getCacheKey(model, context)
        return !!schemas.value[key]
    }

    /**
     * Get schema for a model+context (may be undefined if not loaded)
     */
    function getSchema(model: string, context?: string): CRUD6Schema | undefined {
        const key = getCacheKey(model, context)
        return schemas.value[key]
    }

    /**
     * Check if a schema is currently loading
     */
    function isLoading(model: string, context?: string): boolean {
        const key = getCacheKey(model, context)
        return loadingStates.value[key] || false
    }

    /**
     * Get error for a model+context (if any)
     */
    function getError(model: string, context?: string): ApiErrorResponse | null {
        const key = getCacheKey(model, context)
        return errorStates.value[key] || null
    }

    /**
     * Load schema for a specific model
     * Returns cached schema if available, otherwise fetches from API
     * 
     * @param model Model name to load schema for
     * @param force Force reload even if cached
     * @param context Optional context for filtering ('list', 'form', 'detail', 'meta', or comma-separated for multiple)
     */
    async function loadSchema(model: string, force: boolean = false, context?: string): Promise<CRUD6Schema | null> {
        const cacheKey = getCacheKey(model, context)
        
        console.log('[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====', {
            model,
            force,
            context: context || 'full',
            cacheKey,
            hasCache: hasSchema(model, context),
            isCurrentlyLoading: isLoading(model, context),
            timestamp: new Date().toISOString(),
            caller: new Error().stack?.split('\n')[2]?.trim()
        })
        
        // Return cached schema if available and not forcing reload
        if (!force && hasSchema(model, context)) {
            console.log('[useCRUD6SchemaStore] ✅ Using CACHED schema - cacheKey:', cacheKey, '(NO API CALL)')
            return schemas.value[cacheKey] || null
        }

        // Return existing promise if already loading
        if (isLoading(model, context)) {
            console.log('[useCRUD6SchemaStore] ⏳ Schema already loading, waiting for completion - cacheKey:', cacheKey)
            // Wait for the loading to complete by polling
            return new Promise((resolve) => {
                const checkInterval = setInterval(() => {
                    if (!isLoading(model, context)) {
                        clearInterval(checkInterval)
                        console.log('[useCRUD6SchemaStore] ✅ Wait complete, schema loaded - cacheKey:', cacheKey)
                        resolve(schemas.value[cacheKey] || null)
                    }
                }, 100)
            })
        }

        console.log('[useCRUD6SchemaStore] 🌐 MAKING API CALL to load schema - cacheKey:', cacheKey, 'force:', force, 'context:', context || 'full')
        loadingStates.value[cacheKey] = true
        errorStates.value[cacheKey] = null

        try {
            // Build URL with optional context parameter
            let url = `/api/crud6/${model}/schema`
            if (context) {
                url += `?context=${encodeURIComponent(context)}`
            }
            
            console.log('[useCRUD6SchemaStore] 📤 HTTP GET REQUEST', {
                url,
                method: 'GET',
                cacheKey,
                timestamp: new Date().toISOString(),
                requestNumber: Object.keys(loadingStates.value).filter(k => loadingStates.value[k]).length
            })
            
            const response = await axios.get<any>(url)
            
            console.log('[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED', {
                url,
                status: response.status,
                statusText: response.statusText,
                hasData: !!response.data,
                dataKeys: response.data ? Object.keys(response.data) : [],
                cacheKey,
                timestamp: new Date().toISOString()
            })

            // Handle different response structures
            let schemaData: CRUD6Schema
            if (response.data.schema) {
                // Response has nested schema property
                schemaData = response.data.schema as CRUD6Schema
                console.log('[useCRUD6SchemaStore] ✅ Schema found in response.data.schema', {
                    model: schemaData.model,
                    fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
                })
                
                // If schema has contexts (multi-context response), cache each context separately
                if (schemaData.contexts) {
                    console.log('[useCRUD6SchemaStore] 📦 Multi-context schema detected, caching contexts separately', {
                        contexts: Object.keys(schemaData.contexts)
                    })
                    const baseSchema = { ...schemaData }
                    delete baseSchema.contexts
                    
                    // Cache each context separately for future single-context requests
                    for (const [ctxName, ctxData] of Object.entries(schemaData.contexts)) {
                        const ctxCacheKey = getCacheKey(model, ctxName)
                        const ctxSchema = { ...baseSchema, ...ctxData }
                        schemas.value[ctxCacheKey] = ctxSchema as CRUD6Schema
                        console.log('[useCRUD6SchemaStore] ✅ Cached context separately', {
                            context: ctxName,
                            cacheKey: ctxCacheKey,
                            fieldCount: ctxData.fields ? Object.keys(ctxData.fields).length : 0
                        })
                    }
                }
            } else if (response.data.fields) {
                // Response is the schema itself
                schemaData = response.data as CRUD6Schema
                console.log('[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)', {
                    model: schemaData.model,
                    fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
                })
            } else {
                console.error('[useCRUD6SchemaStore] ❌ Invalid schema response structure', {
                    dataKeys: Object.keys(response.data),
                    data: response.data
                })
                throw new Error('Invalid schema response')
            }
            
            schemas.value[cacheKey] = schemaData
            console.log('[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully', {
                model,
                context: context || 'full',
                cacheKey,
                schemaKeys: Object.keys(schemaData),
                fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0,
                hasContexts: !!schemaData.contexts,
                timestamp: new Date().toISOString()
            })
            return schemaData
        } catch (err: any) {
            console.error('[useCRUD6SchemaStore] ❌ Schema load ERROR', {
                model,
                context: context || 'full',
                cacheKey,
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
            errorStates.value[cacheKey] = error
            return null
        } finally {
            loadingStates.value[cacheKey] = false
            console.log('[useCRUD6SchemaStore] Loading state cleared for cacheKey:', cacheKey)
        }
    }

    /**
     * Set schema directly without making an API call
     * Useful when schema is already available from another source
     */
    function setSchema(model: string, schemaData: CRUD6Schema, context?: string): void {
        const cacheKey = getCacheKey(model, context)
        
        console.log('[useCRUD6SchemaStore] ===== SET SCHEMA DIRECTLY (NO API CALL) =====', {
            model,
            context: context || 'full',
            cacheKey,
            fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0,
            timestamp: new Date().toISOString(),
            source: 'setSchema() - schema passed from parent/prop'
        })
        
        schemas.value[cacheKey] = schemaData
        errorStates.value[cacheKey] = null
        
        console.log('[useCRUD6SchemaStore] ✅ Schema cached directly - cacheKey:', cacheKey)
    }

    /**
     * Clear cached schema for a model+context
     */
    function clearSchema(model: string, context?: string): void {
        const cacheKey = getCacheKey(model, context)
        delete schemas.value[cacheKey]
        delete loadingStates.value[cacheKey]
        delete errorStates.value[cacheKey]
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
