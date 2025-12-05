import { ref, computed } from 'vue'
import { useCRUD6SchemaStore } from '../stores/useCRUD6SchemaStore'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { debugLog, debugWarn, debugError } from '../utils/debug'

export interface SchemaField {
    type: string
    label: string
    required?: boolean
    sortable?: boolean
    filterable?: boolean
    editable?: boolean
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

/**
 * Custom action configuration for buttons and operations
 */
/**
 * Modal button configuration for schema-driven modals
 */
export interface ModalButtonConfig {
    /** Button label (translation key or text) */
    label: string
    /** Button icon (FontAwesome icon name) */
    icon?: string
    /** Button style (primary, secondary, danger, warning, default) */
    style?: 'primary' | 'secondary' | 'danger' | 'warning' | 'default'
    /** Button action type */
    action: 'confirm' | 'cancel' | 'submit' | 'close'
    /** Close modal after action */
    closeModal?: boolean
}

/**
 * Modal configuration for schema-driven modals
 */
export interface ModalConfig {
    /** Modal title (translation key or text) */
    title?: string
    /** Modal type determines content rendering */
    type?: 'confirm' | 'input' | 'form' | 'message'
    /** Fields to render in modal (for input/form types) */
    fields?: string[]
    /** Button combination preset or custom buttons */
    buttons?: 'yes_no' | 'save_cancel' | 'ok_cancel' | 'confirm_cancel' | ModalButtonConfig[]
    /** Warning message to display (translation key, defaults to 'WARNING_CANNOT_UNDONE' for confirm type) */
    warning?: string
}

export interface ActionConfig {
    /** Unique key for the action */
    key: string
    /** Display label for the button (can be auto-inferred from key or field) */
    label?: string
    /** Icon to display on the button (can be auto-inferred from action type or field type) */
    icon?: string
    /** 
     * Action type
     * - 'field_update': Update a field value (including password fields with requires_password_input)
     * - 'modal': Show a modal component
     * - 'route': Navigate to a route
     * - 'api_call': Make an API call
     * @deprecated 'password_update' is deprecated. Use 'field_update' with requires_password_input: true instead.
     */
    type: 'field_update' | 'modal' | 'route' | 'api_call' | 'password_update'
    /** Permission required to see/use this action (can be auto-inferred from model and action type) */
    permission?: string
    /** For field_update: field to update (can be auto-inferred from key pattern, e.g., "password_action" -> "password") */
    field?: string
    /** For field_update: value to set */
    value?: any
    /** For field_update: toggle boolean instead of setting specific value */
    toggle?: boolean
    /** For modal: modal component to show */
    modal?: string
    /** For route: route name to navigate to */
    route?: string
    /** For api_call: API endpoint to call (auto-generated if not specified: /api/crud6/{model}/{id}/a/{key}) */
    endpoint?: string
    /** For api_call: HTTP method (default: POST) */
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
    /** Button style class (can be auto-inferred from action pattern, e.g., "delete" -> "danger") */
    style?: string
    /** Confirmation message before executing action */
    confirm?: string
    /** Success message after action completes */
    success_message?: string
    /** 
     * Requires password input with confirmation modal before execution.
     * Use this for password field updates instead of the deprecated password_update type.
     */
    requires_password_input?: boolean
    /** 
     * @deprecated Use field instead. For password fields, use field: "password" with requires_password_input: true
     */
    password_field?: string
    /** Modal configuration for schema-driven modal rendering */
    modal_config?: ModalConfig
    /**
     * Conditional visibility based on record field values.
     * The action is visible only when all conditions are met.
     * 
     * @example
     * // Show "Enable User" only when flag_enabled is false
     * "visible_when": { "flag_enabled": false }
     * 
     * // Show "Archive" only when status is "active" and is_archived is false
     * "visible_when": { "status": "active", "is_archived": false }
     */
    visible_when?: Record<string, any>
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
    /** Single detail configuration (backward compatible) */
    detail?: DetailConfig
    /** Multiple detail configurations */
    details?: DetailConfig[]
    detail_editable?: DetailEditableConfig
    /** Custom actions for detail pages */
    actions?: ActionConfig[]
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
        details?: DetailConfig[]
        detail_editable?: DetailEditableConfig
        actions?: ActionConfig[]
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
 *
 * ## Features
 * - Automatic caching via Pinia store (prevents duplicate API calls)
 * - Context filtering (list, form, detail, meta)
 * - Related schema loading (batch loading of related model schemas)
 * - Computed properties for common schema operations
 *
 * ## Reactive State
 * - `schema` - The loaded schema object or null
 * - `loading` - Boolean indicating if schema is being loaded
 * - `error` - Error response from failed load, or null
 * - `currentModel` - Name of the currently loaded model
 *
 * ## Computed Properties
 * - `sortableFields` - Array of field names that are sortable
 * - `filterableFields` - Array of field names that are filterable
 * - `tableColumns` - Column configuration for data tables
 * - `defaultSort` - Default sort configuration from schema
 *
 * @param modelName - Optional model name for auto-loading
 * @returns Object with reactive state and schema methods
 *
 * @example
 * ```typescript
 * // Basic usage - load schema on mount
 * import { useCRUD6Schema } from '@/composables/useCRUD6Schema'
 * 
 * const { schema, loading, loadSchema, sortableFields } = useCRUD6Schema()
 * 
 * onMounted(async () => {
 *   await loadSchema('users')
 *   console.log('Fields:', Object.keys(schema.value?.fields || {}))
 *   console.log('Sortable:', sortableFields.value)
 * })
 * ```
 *
 * @example
 * ```typescript
 * // Load with specific context (reduces payload size)
 * const { loadSchema, schema } = useCRUD6Schema()
 * 
 * // Load only list fields (for table view)
 * await loadSchema('products', false, 'list')
 * 
 * // Load only form fields (for create/edit forms)
 * await loadSchema('products', false, 'form')
 * 
 * // Load full detail view with relationships
 * await loadSchema('orders', false, 'detail', true)
 * ```
 *
 * @example
 * ```typescript
 * // Use schema for table configuration
 * const { schema, tableColumns, defaultSort } = useCRUD6Schema()
 * 
 * await loadSchema('invoices', false, 'list')
 * 
 * // Use in UFTable component
 * // <UFTable
 * //   :columns="tableColumns"
 * //   :default-sort="defaultSort"
 * //   :data="rows"
 * // />
 * ```
 *
 * @example
 * ```typescript
 * // Set schema directly without API call (when parent provides it)
 * const { setSchema, schema } = useCRUD6Schema()
 * 
 * // Receive schema from parent component
 * props.schema && setSchema(props.schema, 'users', 'detail')
 * 
 * // Now schema.value is available without additional API call
 * ```
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
        debugLog('[useCRUD6Schema] ===== SET SCHEMA (NO API CALL) =====', {
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
     * @param includeRelated Whether to include related model schemas (default: false)
     */
    async function loadSchema(model: string, force: boolean = false, context?: string, includeRelated: boolean = false): Promise<CRUD6Schema | null> {
        debugLog('[useCRUD6Schema] ===== LOAD SCHEMA CALLED =====', {
            model,
            force,
            context: context || 'full',
            includeRelated,
            hasLocalCache: !!(currentModel.value === model && schema.value),
            currentModel: currentModel.value,
            timestamp: new Date().toISOString(),
            caller: new Error().stack?.split('\n')[2]?.trim()
        })
        
        // Check if already loaded in this instance and not forcing
        if (!force && currentModel.value === model && schema.value) {
            debugLog('[useCRUD6Schema] ✅ Using LOCAL cached schema - model:', model, 'context:', context || 'full')
            return schema.value
        }

        debugLog('[useCRUD6Schema] Delegating to STORE - model:', model, 'force:', force, 'context:', context || 'full', 'includeRelated:', includeRelated)
        loading.value = true
        error.value = null

        try {
            // Delegate to global store with context and includeRelated parameters
            const schemaData = await schemaStore.loadSchema(model, force, context, includeRelated)
            
            if (schemaData) {
                schema.value = schemaData
                currentModel.value = model
                debugLog('[useCRUD6Schema] ✅ Schema loaded and set - model:', model, 'context:', context || 'full', 'fieldCount:', Object.keys(schemaData.fields || {}).length)
                return schemaData
            } else {
                // Get error from store with context
                const storeError = schemaStore.getError(model, context)
                if (storeError) {
                    error.value = storeError
                }
                debugError('[useCRUD6Schema] ❌ Schema load failed - model:', model, 'context:', context || 'full', 'error:', storeError)
                return null
            }
        } catch (err: any) {
            debugError('[useCRUD6Schema] ❌ Schema load exception - model:', model, 'context:', context || 'full', 'error:', err)
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
                editable: field.editable !== false,
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