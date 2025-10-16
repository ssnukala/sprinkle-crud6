import { ref, computed } from 'vue'
import axios from 'axios'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'

export interface SchemaField {
    type: string
    label: string
    required?: boolean
    sortable?: boolean
    filterable?: boolean
    searchable?: boolean
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

export interface CRUD6Schema {
    model: string
    title: string
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
}

/**
 * Vue composable for loading and managing CRUD6 schemas.
 * 
 * Provides reactive access to schema data and methods to load schemas
 * from the API endpoints.
 */
export function useCRUD6Schema(modelName?: string) {
    const loading = ref(false)
    const error = ref<ApiErrorResponse | null>(null)
    const schema = ref<CRUD6Schema | null>(null)
    const currentModel = ref<string | null>(null)

    /**
     * Set schema directly without making an API call
     * Useful when schema is already available from parent component
     */
    function setSchema(schemaData: CRUD6Schema, model?: string): void {
        schema.value = schemaData
        if (model) {
            currentModel.value = model
        }
        error.value = null
    }

    /**
     * Load schema for a specific model
     * Skips API call if schema is already loaded for the same model
     */
    async function loadSchema(model: string, force: boolean = false): Promise<CRUD6Schema | null> {
        // Skip loading if schema is already loaded for the same model (unless forced)
        if (!force && currentModel.value === model && schema.value) {
            console.log('[useCRUD6Schema] Using cached schema - model:', model)
            return schema.value
        }

        console.log('[useCRUD6Schema] Loading schema from API - model:', model, 'force:', force)
        loading.value = true
        error.value = null

        try {
            const response = await axios.get<CRUD6Schema>(`/api/crud6/${model}/schema`)

            // Handle different response structures
            if (response.data.schema) {
                schema.value = response.data.schema
            } else if (response.data.fields) {
                schema.value = response.data
            } else {
                throw new Error('Invalid schema response')
            }
            
            currentModel.value = model
            console.log('[useCRUD6Schema] Schema loaded successfully - model:', model)
            return response.data
        } catch (err: any) {
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
     * Get filterable fields from schema
     */
    const filterableFields = computed(() => {
        if (!schema.value?.fields) return []
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.filterable)
            .map(([key]) => key)
    })

    /**
     * Get searchable fields from schema
     */
    const searchableFields = computed(() => {
        if (!schema.value?.fields) return []
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.searchable)
            .map(([key]) => key)
    })

    /**
     * Get table columns configuration for UFTable
     */
    const tableColumns = computed(() => {
        if (!schema.value?.fields) return []
        
        return Object.entries(schema.value.fields)
            .filter(([_, field]) => field.sortable || field.filterable || field.searchable)
            .map(([key, field]) => ({
                key,
                label: field.label || key,
                sortable: field.sortable || false,
                filterable: field.filterable || false,
                searchable: field.searchable || false,
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

    // Auto-load schema if model name is provided
    if (modelName) {
        console.log('[useCRUD6Schema] Auto-loading schema on init - modelName:', modelName)
        loadSchema(modelName)
    }

    return {
        schema,
        loading,
        error,
        currentModel,
        loadSchema,
        setSchema,
        sortableFields,
        filterableFields,
        searchableFields,
        tableColumns,
        defaultSort,
        hasPermission
    }
}