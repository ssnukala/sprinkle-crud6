import { ref, computed } from 'vue'
import axios from 'axios'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'

interface SchemaField {
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

interface CRUD6Schema {
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

    /**
     * Load schema for a specific model
     */
    async function loadSchema(model: string): Promise<CRUD6Schema | null> {
        loading.value = true
        error.value = null

        try {
            const response = await axios.get<CRUD6Schema>(`/api/crud6/${model}/schema`)
            schema.value = response.data.schema
            //console.log('Loaded schema for model:', model, response.data)
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
        loadSchema(modelName)
    }

    return {
        schema,
        loading,
        error,
        loadSchema,
        sortableFields,
        filterableFields,
        searchableFields,
        tableColumns,
        defaultSort,
        hasPermission
    }
}