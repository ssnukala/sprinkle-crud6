import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { CRUD6Schema } from '../composables/useCRUD6Schema'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { debugLog, debugError } from '../utils/debug'

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
     * Check if a broader context (containing the requested context) is currently loading
     * OR if we should wait because the requested context is broader than what's loading
     * 
     * For example:
     * - If "detail,form" is loading and we request "form" → wait (subset)
     * - If "form" is loading and we request "detail,form" → DON'T wait, make new call (superset)
     */
    function isRelatedContextLoading(model: string, context?: string): string | null {
        if (!context) return null
        
        // Get all loading states for this model
        const requestedContexts = context.split(',').map(c => c.trim())
        
        for (const [loadingKey, loading] of Object.entries(loadingStates.value)) {
            if (!loading) continue
            
            // Check if this loading key is for the same model
            const [loadingModel, loadingContext] = loadingKey.split(':')
            if (loadingModel !== model) continue
            if (!loadingContext || loadingContext === 'full') continue
            
            // Check if the loading context contains all requested contexts (broader or equal)
            const loadingContexts = loadingContext.split(',').map(c => c.trim())
            const containsAll = requestedContexts.every(rc => loadingContexts.includes(rc))
            
            if (containsAll) {
                debugLog('[useCRUD6SchemaStore] ⏳ Related context loading (broader or equal):', {
                    requested: context,
                    loading: loadingContext,
                    loadingKey,
                    message: 'Will wait for broader context to complete'
                })
                return loadingKey
            }
        }
        
        return null
    }

    /**
     * Find a cached superset context for the requested context
     * 
     * For example:
     * - If "list,detail,form" is cached and we request "list,form" → return "list,detail,form"
     * - If "form" is cached and we request "list,form" → return null (not a superset)
     */
    function findCachedSupersetContext(model: string, context?: string): string | null {
        if (!context) return null
        
        const requestedContexts = context.split(',').map(c => c.trim())
        
        for (const cacheKey of Object.keys(schemas.value)) {
            const [cachedModel, cachedContext] = cacheKey.split(':')
            if (cachedModel !== model) continue
            if (!cachedContext || cachedContext === 'full') continue
            
            // Check if the cached context contains all requested contexts (is a superset)
            const cachedContexts = cachedContext.split(',').map(c => c.trim())
            const containsAll = requestedContexts.every(rc => cachedContexts.includes(rc))
            
            if (containsAll) {
                debugLog('[useCRUD6SchemaStore] 🔍 Found cached superset context:', {
                    requested: context,
                    cached: cachedContext,
                    cacheKey,
                    message: 'Will use cached superset instead of making API call'
                })
                return cacheKey
            }
        }
        
        return null
    }

    /**
     * Load schema for a specific model
     * Returns cached schema if available, otherwise fetches from API
     * 
     * @param model Model name to load schema for
     * @param force Force reload even if cached
     * @param context Optional context for filtering ('list', 'form', 'detail', 'meta', or comma-separated for multiple)
     * @param includeRelated Whether to include related model schemas in the response (default: false)
     */
    async function loadSchema(model: string, force: boolean = false, context?: string, includeRelated: boolean = false): Promise<CRUD6Schema | null> {
        const cacheKey = getCacheKey(model, context)
        
        debugLog('[useCRUD6SchemaStore] ===== LOAD SCHEMA CALLED =====', {
            model,
            force,
            context: context || 'full',
            includeRelated,
            cacheKey,
            hasCache: hasSchema(model, context),
            isCurrentlyLoading: isLoading(model, context),
            timestamp: new Date().toISOString(),
            caller: new Error().stack?.split('\n')[2]?.trim()
        })
        
        // Return cached schema if available and not forcing reload
        if (!force && hasSchema(model, context)) {
            debugLog('[useCRUD6SchemaStore] ✅ Using CACHED schema - cacheKey:', cacheKey, '(NO API CALL)')
            return schemas.value[cacheKey] || null
        }

        // Return existing promise if already loading
        if (isLoading(model, context)) {
            debugLog('[useCRUD6SchemaStore] ⏳ Schema already loading, waiting for completion - cacheKey:', cacheKey)
            // Wait for the loading to complete by polling
            return new Promise((resolve) => {
                const checkInterval = setInterval(() => {
                    if (!isLoading(model, context)) {
                        clearInterval(checkInterval)
                        debugLog('[useCRUD6SchemaStore] ✅ Wait complete, schema loaded - cacheKey:', cacheKey)
                        resolve(schemas.value[cacheKey] || null)
                    }
                }, 100)
            })
        }

        // Check if a broader context is loading that would include this context
        const relatedLoadingKey = isRelatedContextLoading(model, context)
        if (relatedLoadingKey) {
            debugLog('[useCRUD6SchemaStore] ⏳ Waiting for related context to finish loading - relatedKey:', relatedLoadingKey)
            // Wait for the related context to complete, then check cache again
            return new Promise((resolve) => {
                const checkInterval = setInterval(() => {
                    if (!loadingStates.value[relatedLoadingKey]) {
                        clearInterval(checkInterval)
                        debugLog('[useCRUD6SchemaStore] ✅ Related context loaded, checking cache - cacheKey:', cacheKey)
                        // After the broader context loads, our specific context should be cached
                        resolve(schemas.value[cacheKey] || null)
                    }
                }, 100)
            })
        }

        // Check if a superset context is already cached (e.g., "list,detail,form" cached when "list,form" requested)
        // This avoids making an API call when the data is already available in a broader cached context
        if (!force && context) {
            const supersetCacheKey = findCachedSupersetContext(model, context)
            if (supersetCacheKey) {
                debugLog('[useCRUD6SchemaStore] ✅ Using cached SUPERSET schema - supersetKey:', supersetCacheKey, '(NO API CALL)')
                // Return the superset schema - it contains all the data we need
                // Also cache this for the exact context key for future lookups
                const supersetSchema = schemas.value[supersetCacheKey]
                if (supersetSchema) {
                    schemas.value[cacheKey] = supersetSchema
                    return supersetSchema
                }
            }
        }

        debugLog('[useCRUD6SchemaStore] 🌐 MAKING API CALL to load schema - cacheKey:', cacheKey, 'force:', force, 'context:', context || 'full')
        loadingStates.value[cacheKey] = true
        errorStates.value[cacheKey] = null

        try {
            // Build URL with optional context and include_related parameters
            let url = `/api/crud6/${model}/schema`
            const params = new URLSearchParams()
            
            if (context) {
                params.append('context', context)
            }
            
            if (includeRelated) {
                params.append('include_related', 'true')
            }
            
            if (params.toString()) {
                url += `?${params.toString()}`
            }
            
            debugLog('[useCRUD6SchemaStore] 📤 HTTP GET REQUEST', {
                url,
                method: 'GET',
                cacheKey,
                timestamp: new Date().toISOString(),
                requestNumber: Object.keys(loadingStates.value).filter(k => loadingStates.value[k]).length
            })
            
            const response = await axios.get<any>(url)
            
            debugLog('[useCRUD6SchemaStore] 📥 HTTP RESPONSE RECEIVED', {
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
                debugLog('[useCRUD6SchemaStore] ✅ Schema found in response.data.schema', {
                    model: schemaData.model,
                    fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
                })
                
                // If schema has contexts (multi-context response), cache each context separately
                if (schemaData.contexts) {
                    debugLog('[useCRUD6SchemaStore] 📦 Multi-context schema detected, caching contexts separately', {
                        contexts: Object.keys(schemaData.contexts)
                    })
                    const baseSchema = { ...schemaData }
                    delete baseSchema.contexts
                    
                    // Cache each context separately for future single-context requests
                    for (const [ctxName, ctxData] of Object.entries(schemaData.contexts)) {
                        const ctxCacheKey = getCacheKey(model, ctxName)
                        const ctxSchema = { ...baseSchema, ...ctxData }
                        schemas.value[ctxCacheKey] = ctxSchema as CRUD6Schema
                        debugLog('[useCRUD6SchemaStore] ✅ Cached context separately', {
                            context: ctxName,
                            cacheKey: ctxCacheKey,
                            fieldCount: ctxData.fields ? Object.keys(ctxData.fields).length : 0
                        })
                    }
                }
                
                // If schema has related_schemas, cache each related model separately
                if (schemaData.related_schemas) {
                    debugLog('[useCRUD6SchemaStore] 📦 Related schemas detected, caching separately', {
                        relatedModels: Object.keys(schemaData.related_schemas)
                    })
                    
                    for (const [relatedModel, relatedSchemaData] of Object.entries(schemaData.related_schemas)) {
                        // Cache the related schema with 'list' context by default
                        const relatedCacheKey = getCacheKey(relatedModel, 'list')
                        schemas.value[relatedCacheKey] = relatedSchemaData as CRUD6Schema
                        debugLog('[useCRUD6SchemaStore] ✅ Cached related schema', {
                            model: relatedModel,
                            cacheKey: relatedCacheKey,
                            fieldCount: relatedSchemaData.fields ? Object.keys(relatedSchemaData.fields).length : 0
                        })
                    }
                }
            } else if (response.data.fields) {
                // Response is the schema itself
                schemaData = response.data as CRUD6Schema
                debugLog('[useCRUD6SchemaStore] ✅ Schema found in response.data (direct)', {
                    model: schemaData.model,
                    fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0
                })
            } else {
                debugError('[useCRUD6SchemaStore] ❌ Invalid schema response structure', {
                    dataKeys: Object.keys(response.data),
                    data: response.data
                })
                throw new Error('Invalid schema response')
            }
            
            schemas.value[cacheKey] = schemaData
            debugLog('[useCRUD6SchemaStore] ✅ Schema loaded and CACHED successfully', {
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
            debugError('[useCRUD6SchemaStore] ❌ Schema load ERROR', {
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
            debugLog('[useCRUD6SchemaStore] Loading state cleared for cacheKey:', cacheKey)
        }
    }

    /**
     * Set schema directly without making an API call
     * Useful when schema is already available from another source
     */
    function setSchema(model: string, schemaData: CRUD6Schema, context?: string): void {
        const cacheKey = getCacheKey(model, context)
        
        debugLog('[useCRUD6SchemaStore] ===== SET SCHEMA DIRECTLY (NO API CALL) =====', {
            model,
            context: context || 'full',
            cacheKey,
            fieldCount: schemaData.fields ? Object.keys(schemaData.fields).length : 0,
            timestamp: new Date().toISOString(),
            source: 'setSchema() - schema passed from parent/prop'
        })
        
        schemas.value[cacheKey] = schemaData
        errorStates.value[cacheKey] = null
        
        debugLog('[useCRUD6SchemaStore] ✅ Schema cached directly - cacheKey:', cacheKey)
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
