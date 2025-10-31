import { ref, computed } from 'vue'
import { useCRUD6SchemaStore } from '../stores/useCRUD6SchemaStore'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'

export interface SchemaField {
    type: string
    label: string
    required?: boolean
    sortable?: boolean
    filterable?: boolean
    readonly?: boolean
    filter_type?: string
    validation?: any
    [key: string]: any
}

export interface DetailConfig {
    model: string
    foreign_key: string
    list_fields: string[]
    title?: string
}

export interface DetailEditableConfig {
    model: string
    foreign_key: string
    fields: string[]
    title?: string
    allow_add?: boolean
    allow_edit?: boolean
    allow_delete?: boolean
}

export interface CRUD6Schema {
    model: string
    title: string
    singular_title?: string
    description?: string
    table: string
    primary_key: string
    timestamps?: boolean
    soft_delete?: boolean
    permissions?: {
        read?: string
        create?: string
        update?: string
        delete?: string
    }
    default_sort?: Record<string, 'asc' | 'desc'>
    fields: Record<string, SchemaField>
    detail?: DetailConfig
    detail_editable?: DetailEditableConfig
    /** 
     * Render mode for the detail page
     * - 'row': Use PageRow component (default)
     * - 'master-detail': Use PageMasterDetail component
     * If not specified, defaults to 'master-detail' when detail_editable is present, otherwise 'row'
     */
    render_mode?: 'row' | 'master-detail'
    /**
     * Multi-context schema data (when multiple contexts are requested)
     * Each key is a context name ('list', 'form', 'detail', etc.)
     * Each value contains the context-specific schema data
     */
    contexts?: Record<string, {
        fields?: Record<string, SchemaField>
        default_sort?: Record<string, 'asc' | 'desc'>
        detail?: DetailConfig
        detail_editable?: DetailEditableConfig
        render_mode?: 'row' | 'master-detail'
        title_field?: string
    }>
}

/**
 * Vue composable for loading and managing CRUD6 schemas.
 * 
 * Provides reactive access to schema data and methods to load schemas
 * from the API endpoints. Uses global Pinia store for caching to prevent
 * duplicate API calls across different component instances.
 */
export function useCRUD6Schema(modelName?: string) {
    // Use global schema store for centralized caching
    const schemaStore = useCRUD6SchemaStore()
    
    const loading = ref(false)
    const error = ref<ApiErrorResponse | null>(null)
    const schema = ref<CRUD6Schema | null>(null)
    const currentModel = ref<string | null>(null)

    /**
     * Set schema directly without making an API call
     * Useful when schema is already available from parent component
     */
    function setSchema(schemaData: CRUD6Schema, model?: string, context?: string): void {
        console.log('[useCRUD6Schema] ===== SET SCHEMA (NO API CALL) =====', {
            model: model || 'unknown',
            context: context || 'none',
            hasSchemaData: !!schemaData,
            fieldCount: schemaData?.fields ? Object.keys(schemaData.fields).length : 0,
            timestamp: new Date().toISOString(),
            source: 'setSchema() called directly'
        })
        
        schema.value = schemaData
        if (model) {
            currentModel.value = model
            // Also update the global store
            schemaStore.setSchema(model, schemaData, context)
        }
        error.value = null
    }

    /**
     * Load schema for a specific model
     * Uses global store for caching to prevent duplicate API calls
     * 
     * @param model Model name to load
     * @param force Force reload even if cached
     * @param context Optional context for filtering ('list', 'form', 'detail', 'meta')
     */
    async function loadSchema(model: string, force: boolean = false, context?: string): Promise<CRUD6Schema | null> {
        console.log('[useCRUD6Schema] ===== LOAD SCHEMA CALLED =====', {
            model,
            force,
            context: context || 'full',
            hasLocalCache: !!(currentModel.value === model && schema.value),
            currentModel: currentModel.value,
            timestamp: new Date().toISOString(),
            caller: new Error().stack?.split('\n')[2]?.trim()
        })
        
        // Check if already loaded in this instance and not forcing
        if (!force && currentModel.value === model && schema.value) {
            console.log('[useCRUD6Schema] ✅ Using LOCAL cached schema - model:', model, 'context:', context || 'full')
            return schema.value
        }

        console.log('[useCRUD6Schema] Delegating to STORE - model:', model, 'force:', force, 'context:', context || 'full')
        loading.value = true
        error.value = null

        try {
            // Delegate to global store with context parameter
            const schemaData = await schemaStore.loadSchema(model, force, context)
            
            if (schemaData) {
                schema.value = schemaData
                currentModel.value = model
                console.log('[useCRUD6Schema] ✅ Schema loaded and set - model:', model, 'context:', context || 'full', 'fieldCount:', Object.keys(schemaData.fields || {}).length)
                return schemaData
            } else {
                // Get error from store with context
                const storeError = schemaStore.getError(model, context)
                if (storeError) {
                    error.value = storeError
                }
                console.error('[useCRUD6Schema] ❌ Schema load failed - model:', model, 'context:', context || 'full', 'error:', storeError)
                return null
            }
        } catch (err: any) {
            console.error('[useCRUD6Schema] ❌ Schema load exception - model:', model, 'context:', context || 'full', 'error:', err)
            error.value = err.response?.data || { 
                title: 'Schema Load Error',
                description: 'Failed to load schema for model: ' + model
            }
            return null
        } finally {
            loading.value = false
        }
    }

    /**
     * Get sortable fields from schema
     */
    const sortableFields = computed(() => {
        if (!schema.value?.fields) return []
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.sortable)
            .map(([key]) => key)
    })

    /**
     * Get filterable fields from schema.
     * These fields are used for global text search.
     */
    const filterableFields = computed(() => {
        if (!schema.value?.fields) return []
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.filterable)
            .map(([key]) => key)
    })

    /**
     * Get table columns configuration for UFTable.
     * Returns columns that are either sortable or filterable.
     */
    const tableColumns = computed(() => {
        if (!schema.value?.fields) return []
        
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.sortable || field.filterable)
            .map(([key, field]) => ({
                key,
                label: field.label || key,
                sortable: field.sortable || false,
                filterable: field.filterable || false,
                type: field.type || 'string',
                readonly: field.readonly || false,
                filterType: field.filter_type || 'equals'
            }))
    })

    /**
     * Get default sort configuration
     */
    const defaultSort = computed(() => {
        return schema.value?.default_sort || {}
    })

    /**
     * Check if user has permission for an action
     */
    function hasPermission(_action: 'read' | 'create' | 'update' | 'delete'): boolean {
        // This would typically check against the current user's permissions
        // For now, we'll return true - this should be implemented based on 
        // UserFrosting's authorization system
        return true
    }

    // NOTE: Auto-load removed to prevent duplicate schema API calls.
    // Components must explicitly call loadSchema() when needed.
    // The global store caching will prevent actual duplicate API calls,
    // but we shouldn't trigger loads unless explicitly requested.
    // See: .archive/DEBUG_DUPLICATE_SCHEMA_CALLS.md for details.

    return {
        schema,
        loading,
        error,
        currentModel,
        loadSchema,
        setSchema,
        sortableFields,
        filterableFields,
        tableColumns,
        defaultSort,
        hasPermission
    }
}