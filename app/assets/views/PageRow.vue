<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta, useTranslator } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Api, useCRUD6Schema, useCRUD6Breadcrumbs } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6Info from '../components/CRUD6/Info.vue'
import CRUD6Details from '../components/CRUD6/Details.vue'
import CRUD6AutoLookup from '../components/CRUD6/AutoLookup.vue'
import type { CRUD6Response, CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import { debugLog, debugWarn, debugError } from '../utils/debug'
import { getLookupConfig } from '../composables/useCRUD6FieldRenderer'

/**
 * Variables and composables
 */
const route = useRoute()
const router = useRouter()
const page = usePageMeta()
const translator = useTranslator()
const { setDetailBreadcrumbs, updateBreadcrumbs } = useCRUD6Breadcrumbs()

// Get model and ID from route parameters
const model = computed(() => route.params.model as string)
const recordId = computed(() => route.params.id as string)
const isCreateMode = computed(() => route.name === 'crud6-create')
const isEditMode = ref(isCreateMode.value)

// Use composables for schema and API
const {
    schema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema,
    hasPermission
} = useCRUD6Schema()

// Pre-load schema before initializing useCRUD6Api to prevent duplicate API calls
// This ensures the schema is loaded/loading before useCRUD6Api tries to load it for validation
// Request all contexts needed by detail page in one consolidated API call
// Include related schemas to eliminate separate requests for detail models
if (model.value && loadSchema) {
    debugLog('[PageRow] Pre-loading schema before useCRUD6Api initialization - model:', model.value)
    loadSchema(model.value, false, 'list,detail,form', true).catch(err => {
        debugError('[PageRow] Schema pre-load failed:', err)
    })
}

const {
    fetchRows,
    fetchRow,
    createRow,
    updateRow,
    apiLoading,
    apiError,
    formData,
    resetForm
} = useCRUD6Api()

// Helper function to create initial object based on schema
function createInitialRecord(schemaFields?: any): CRUD6Response {
    const defaultRecord: CRUD6Response = {
        id: 0,
        name: '',
        slug: '',
        description: '',
        icon: '',
        created_at: '',
        updated_at: '',
        deleted_at: null,
        users_count: 0
    }

    if (!schemaFields) {
        return defaultRecord
    }

    // Create dynamic structure based on schema fields
    const dynamicRecord: any = {}
    
    Object.entries(schemaFields).forEach(([fieldKey, field]: [string, any]) => {
        switch (field.type) {
            case 'boolean':
                dynamicRecord[fieldKey] = field.default ?? false
                break
            case 'integer':
            case 'decimal':
            case 'float':
            case 'number':
                dynamicRecord[fieldKey] = field.default ?? 0
                break
            case 'date':
            case 'datetime':
                dynamicRecord[fieldKey] = field.default ?? ''
                break
            case 'json':
                dynamicRecord[fieldKey] = field.default ?? null
                break
            case 'smartlookup':
                dynamicRecord[fieldKey] = field.default ?? null
                break
            case 'string':
            case 'email':
            case 'url':  
            case 'password':
            case 'text':
            default:
                dynamicRecord[fieldKey] = field.default ?? ''
                break
        }
    })

    // Merge with default structure to ensure required fields exist
    return { ...defaultRecord, ...dynamicRecord }
}

// Use the schema to set the initial response structure
const CRUD6Row = ref<CRUD6Response>(createInitialRecord())

// Reactive state for record management
const record = ref<CRUD6Interface | null>(null)
const originalRecord = ref<CRUD6Interface | null>(null)

// Combined loading and error states
const loading = computed(() => schemaLoading.value || apiLoading.value)
const error = computed(() => schemaError.value || apiError.value)

// Flattened schema - handles multi-context responses
// When schema has 'contexts' property (multi-context response), merge detail context data to root
const flattenedSchema = computed(() => {
    if (!schema.value) return null
    
    // If schema has contexts property (multi-context response from 'list,detail,form' request)
    if (schema.value.contexts) {
        debugLog('[PageRow] Multi-context schema detected, flattening...')
        
        // Start with base schema properties
        const flattened: Record<string, any> = {
            model: schema.value.model,
            title: schema.value.title,
            singular_title: schema.value.singular_title,
            description: schema.value.description,
            primary_key: schema.value.primary_key,
            permissions: schema.value.permissions,
            // Preserve contexts for child components that need access to all fields
            // (e.g., Info.vue needs form context fields for action modals like password change)
            contexts: schema.value.contexts,
        }
        
        // Merge 'detail' context data if present (for detail view display)
        if (schema.value.contexts.detail) {
            Object.assign(flattened, schema.value.contexts.detail)
            debugLog('[PageRow] Merged detail context - hasDetail:', !!flattened.detail, 'hasDetails:', !!flattened.details)
        }
        
        // If we're in edit/create mode, also check for 'form' context
        // Form context provides editable fields which may have additional properties
        if (isEditMode.value && schema.value.contexts.form) {
            // Merge form fields, but don't overwrite detail fields
            if (!flattened.fields && schema.value.contexts.form.fields) {
                flattened.fields = schema.value.contexts.form.fields
            }
        }
        
        return flattened
    }
    
    // Single-context or full schema - use as-is
    return schema.value
})

// Computed property for detail configurations (supports both single and multiple)
const detailConfigs = computed(() => {
    if (!flattenedSchema.value) return []
    
    // If schema has 'details' array (new format), use it
    if (flattenedSchema.value.details && Array.isArray(flattenedSchema.value.details)) {
        return flattenedSchema.value.details
    }
    
    // If schema has single 'detail' object (legacy format), convert to array
    if (flattenedSchema.value.detail) {
        return [flattenedSchema.value.detail]
    }
    
    return []
})

// Permission checks
const hasCreatePermission = computed(() => hasPermission('create'))
const hasViewPermission = computed(() => hasPermission('view'))

// Model label for page titles - prioritize singular_title over title
// Support translation keys (e.g., "USER.SINGULAR") or plain text
const modelLabel = computed(() => {
    if (flattenedSchema.value?.singular_title) {
        // Try to translate - if key doesn't exist, returns the original value
        return translator.translate(flattenedSchema.value.singular_title)
    }
    // Capitalize first letter of model name as fallback
    return model.value ? model.value.charAt(0).toUpperCase() + model.value.slice(1) : 'Record'
})

// Model title for breadcrumbs - use plural title (e.g., "Users" for list page link)
// This is used in breadcrumb trails when linking back to the list page
const modelTitle = computed(() => {
    if (flattenedSchema.value?.title) {
        // Try to translate - if key doesn't exist, returns the original value
        return translator.translate(flattenedSchema.value.title)
    }
    // Fallback to modelLabel if title is not available
    return modelLabel.value
})

/**
 * Methods - Fetch record
 */
async function fetch() {
    if (recordId.value && fetchRow) {
        const fetchPromise = fetchRow(recordId.value)
        if (fetchPromise && typeof fetchPromise.then === 'function') {
            fetchPromise.then(async (fetchedRow) => {
                CRUD6Row.value = fetchedRow
                record.value = fetchedRow
                originalRecord.value = { ...fetchedRow }
                
                // Update page title with record name if available
                // Use title_field from schema, or fall back to ID
                const titleField = flattenedSchema.value?.title_field
                let recordName = titleField ? (fetchedRow[titleField] || recordId.value) : recordId.value
                
                debugLog('[PageRow.fetch] Record fetched:', {
                    recordId: recordId.value,
                    titleField,
                    recordName,
                    availableFields: Object.keys(fetchedRow).slice(0, 10), // Limit to first 10 for performance
                    modelLabel: modelLabel.value
                })
                
                // Wait for schema to be available before setting breadcrumbs
                // This prevents setting breadcrumbs with incomplete model title
                // Use a simple polling mechanism to ensure schema is loaded
                console.log('[BREADCRUMB DEBUG] Starting schema wait', {
                    hasFlattenedSchema: !!flattenedSchema.value,
                    hasTitle: !!flattenedSchema.value?.title,
                    title: flattenedSchema.value?.title,
                    modelTitleValue: modelTitle.value,
                    recordName,
                    recordId: recordId.value
                })
                
                let retries = 0
                const maxRetries = 20 // Max 2 seconds (20 * 100ms)
                while (!flattenedSchema.value?.title && retries < maxRetries) {
                    await new Promise(resolve => setTimeout(resolve, 100))
                    retries++
                    if (retries % 5 === 0) {
                        console.log(`[BREADCRUMB DEBUG] Waiting for schema... retry ${retries}/${maxRetries}`)
                    }
                }
                
                console.log('[BREADCRUMB DEBUG] Schema wait complete', {
                    retries,
                    hasFlattenedSchema: !!flattenedSchema.value,
                    hasTitle: !!flattenedSchema.value?.title,
                    title: flattenedSchema.value?.title,
                    modelTitleValue: modelTitle.value,
                    recordName,
                    recordId: recordId.value
                })
                
                // Update breadcrumbs with model title and record name
                // Don't set page.title here as it will cause usePageMeta to add a breadcrumb automatically
                // setDetailBreadcrumbs will handle the breadcrumb trail correctly
                // Use modelTitle (plural) for the list page breadcrumb link
                const listPath = `/crud6/${model.value}`
                
                console.log('[BREADCRUMB DEBUG] Calling setDetailBreadcrumbs', {
                    modelTitle: modelTitle.value,
                    recordName,
                    listPath,
                    currentBreadcrumbs: page.breadcrumbs.map(b => ({ label: b.label, to: b.to }))
                })
                
                await setDetailBreadcrumbs(modelTitle.value, recordName, listPath)
                
                console.log('[BREADCRUMB DEBUG] After setDetailBreadcrumbs', {
                    breadcrumbs: page.breadcrumbs.map(b => ({ label: b.label, to: b.to }))
                })
                
                // DON'T set page.title here as it causes usePageMeta to automatically add a breadcrumb
                // The record name is already in the breadcrumb trail
                // page.title = recordName
                
                console.log('[BREADCRUMB DEBUG] Skipped setting page.title to avoid auto-breadcrumb')
                
                debugLog('[PageRow.fetch] Breadcrumbs updated with record name')
            }).catch((error) => {
                debugError('Failed to fetch CRUD6 row:', error)
            })
        }
    }
}

// Actions for form management
function goBack() {
    router.push(`/crud6/${model.value}`)
}

function cancelEdit() {
    if (originalRecord.value) {
        record.value = { ...originalRecord.value }
        CRUD6Row.value = { ...originalRecord.value } as CRUD6Response
    }
    isEditMode.value = false
}

async function saveRecord() {
    if (!record.value) return

    try {
        if (isCreateMode.value) {
            await createRow(record.value)
            router.push(`/crud6/${model.value}`)
        } else {
            await updateRow(recordId.value, record.value)
            isEditMode.value = false
            originalRecord.value = { ...record.value }
            CRUD6Row.value = { ...record.value } as CRUD6Response
            // Refresh the data
            fetch()
        }
    } catch (error) {
        debugError('Save failed:', error)
    }
}

// Utility function to format field values for display
function formatFieldValue(value: any, field: any): string {
    if (value === null || value === undefined) return ''
    
    switch (field.type) {
        case 'boolean':
            return value ? 'Yes' : 'No'
        case 'date':
        case 'datetime':
            return new Date(value).toLocaleDateString()
        case 'json':
            return JSON.stringify(value, null, 2)
        case 'smartlookup':
            return String(value)
        default:
            return String(value)
    }
}

// Load data when component mounts
onMounted(async () => {
    // Schema loading is handled by the model watcher with immediate: true
    // Record fetching is handled by the recordId watcher with immediate: true
    // No need to load schema or fetch record here to avoid duplicate calls
    
    if (isCreateMode.value) {
        // Initialize empty record for create mode using schema
        record.value = {}
        CRUD6Row.value = createInitialRecord(flattenedSchema.value?.fields)
        resetForm()
    }
})

/**
 * Watcher - Update page on id change
 */
watch(
    () => route.params.id,
    () => {
        if (!isCreateMode.value) {
            fetch()
        }
    },
    { immediate: false }
)

// Watch for schema changes to update initial record structure
watch(
    () => flattenedSchema.value,
    (newSchema) => {
        if (newSchema?.fields && isCreateMode.value) {
            // Update the initial record structure when schema loads in create mode
            CRUD6Row.value = createInitialRecord(newSchema.fields)
        }
    }
)

// Load schema when model changes - single source of truth for schema loading
let currentModel = ''
watch(model, async (newModel) => {
    console.log('[BREADCRUMB DEBUG - model watcher] START', { newModel, currentModel, loadSchema: !!loadSchema })
    
    if (newModel && loadSchema && newModel !== currentModel) {
        debugLog('[PageRow] Schema loading triggered - model:', newModel, 'currentModel:', currentModel)
        
        currentModel = newModel
        // Request all contexts needed by detail page in one consolidated API call
        // This prevents child components (Info, EditModal) from making separate schema calls
        // Include related schemas to eliminate separate requests for detail models (activities, roles, permissions)
        console.log('[BREADCRUMB DEBUG - model watcher] Loading schema for:', newModel)
        const schemaPromise = loadSchema(newModel, false, 'list,detail,form', true)
        if (schemaPromise && typeof schemaPromise.then === 'function') {
            await schemaPromise
            debugLog('[PageRow] Schema loaded successfully for model:', newModel)
            console.log('[BREADCRUMB DEBUG - model watcher] Schema loaded', {
                hasFlattenedSchema: !!flattenedSchema.value,
                title: flattenedSchema.value?.title,
                singular_title: flattenedSchema.value?.singular_title
            })
            
            // Update page title and description with translation support
            if (flattenedSchema.value) {
                const schemaTitle = flattenedSchema.value.title 
                    ? translator.translate(flattenedSchema.value.title) 
                    : modelLabel.value
                    
                if (isCreateMode.value) {
                    page.title = translator.translate('CRUD6.CREATE', { model: modelLabel.value })
                    page.description = flattenedSchema.value.description 
                        ? translator.translate(flattenedSchema.value.description) 
                        : translator.translate('CRUD6.CREATE.SUCCESS', { model: modelLabel.value })
                    
                    // Update breadcrumbs for create mode
                    await updateBreadcrumbs(schemaTitle)
                } else if (recordId.value) {
                    // Set page description
                    page.description = flattenedSchema.value.description 
                        ? translator.translate(flattenedSchema.value.description) 
                        : translator.translate('CRUD6.INFO_PAGE', { model: modelLabel.value })
                    
                    // Don't set breadcrumbs here - let fetch() handle it after loading the record
                    // This ensures breadcrumbs are only set once with complete data
                    // The breadcrumb will show "UserFrosting / Admin Panel / Users / user01" after fetch completes
                    console.log('[BREADCRUMB DEBUG - model watcher] Not setting breadcrumbs here, waiting for fetch()')
                    
                    // Clear page.title to prevent auto-breadcrumb generation by usePageMeta
                    // It will be updated with the record name after fetch() completes
                    page.title = ''
                    
                    // Note: Breadcrumbs will be set by setDetailBreadcrumbs() after fetch() completes
                }
            }
        }
    }
    console.log('[BREADCRUMB DEBUG - model watcher] END')
}, { immediate: true })

// Watch for recordId changes to fetch data
watch(recordId, (newId) => {
    console.log('[BREADCRUMB DEBUG - recordId watcher] START', { newId, isCreateMode: isCreateMode.value })
    if (newId && !isCreateMode.value) {
        console.log('[BREADCRUMB DEBUG - recordId watcher] Calling fetch()')
        fetch()
    }
    console.log('[BREADCRUMB DEBUG - recordId watcher] END')
}, { immediate: true })

/**
 * Helper function to get lookup attributes for AutoLookup component
 * Uses centralized getLookupConfig from composable
 */
function getLookupAttributes(field: any) {
    const lookupConfig = getLookupConfig(field)
    return {
        model: lookupConfig.model,
        'id-field': lookupConfig.idField,
        'display-field': lookupConfig.displayField,
        placeholder: field.placeholder,
        required: field.required
    }
}
</script>

<template>
    <template v-if="error">
        <UFErrorPage :errorCode="error.status || 500" />
    </template>
    <template v-else-if="loading">
        <div class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ $t('LOADING') }}</p>
        </div>
    </template>
    <template v-else>
        <!-- Schema-driven edit/create mode -->
        <div v-if="isEditMode && flattenedSchema" class="uk-container">
            <div class="uk-card uk-card-default">
                <div class="uk-card-header">
                    <div class="uk-flex uk-flex-between uk-flex-middle">
                        <div>
                            <h3 class="uk-card-title uk-margin-remove">
                                {{ isCreateMode ? $t('CRUD6.CREATE', { model: flattenedSchema?.title || model }) : $t('CRUD6.EDIT', { model: flattenedSchema?.title || model }) }}
                            </h3>
                            <small v-if="recordId" class="uk-text-muted">ID: {{ recordId }}</small>
                        </div>
                        <div>
                            <button
                                type="button"
                                class="uk-button uk-button-default"
                                data-test="btn-back"
                                @click="goBack">
                                <font-awesome-icon icon="arrow-left" /> Back
                            </button>
                            <button
                                type="button"
                                class="uk-button uk-button-primary"
                                data-test="btn-save"
                                @click="saveRecord"
                                :disabled="loading">
                                <font-awesome-icon icon="save" /> Save
                            </button>
                            <button
                                v-if="!isCreateMode"
                                type="button"
                                class="uk-button uk-button-secondary"
                                data-test="btn-cancel"
                                @click="cancelEdit">
                                <font-awesome-icon icon="times" /> Cancel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="uk-card-body">
                    <!-- Dynamic Form based on schema -->
                    <form v-if="flattenedSchema && record" @submit.prevent="saveRecord" class="uk-form-stacked">
                        <div class="uk-grid-small" uk-grid>
                            <div
                                v-for="[fieldKey, field] in Object.entries(flattenedSchema.fields)"
                                :key="fieldKey"
                                :class="field.width || 'uk-width-1-2'"
                                v-if="field.editable !== false">
                                
                                <label :for="fieldKey" class="uk-form-label">
                                    {{ field.label || fieldKey }}
                                    <span v-if="field.required" class="uk-text-danger">*</span>
                                </label>
                                
                                <!-- SmartLookup field -->
                                <CRUD6AutoLookup
                                    v-if="field.type === 'smartlookup'"
                                    v-bind="getLookupAttributes(field)"
                                    v-model="record[fieldKey]"
                                />
                                
                                <!-- Text input -->
                                <input
                                    v-else-if="field.type === 'string' || !field.type"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="uk-input"
                                    :required="field.required"
                                    :placeholder="field.placeholder"
                                />
                                
                                <!-- Number input -->
                                <input
                                    v-else-if="['integer', 'decimal', 'float'].includes(field.type)"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="number"
                                    class="uk-input"
                                    :required="field.required"
                                    :step="field.type === 'integer' ? '1' : 'any'"
                                />
                                
                                <!-- Boolean checkbox -->
                                <label v-else-if="field.type === 'boolean'" class="uk-form-label">
                                    <input
                                        :id="fieldKey"
                                        v-model="record[fieldKey]"
                                        type="checkbox"
                                        class="uk-checkbox"
                                    />
                                    {{ field.label || fieldKey }}
                                </label>
                                
                                <!-- Date input -->
                                <input
                                    v-else-if="field.type === 'date'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="date"
                                    class="uk-input"
                                    :required="field.required"
                                />
                                
                                <!-- DateTime input -->
                                <input
                                    v-else-if="field.type === 'datetime'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="datetime-local"
                                    class="uk-input"
                                    :required="field.required"
                                />
                                
                                <!-- Text area -->
                                <textarea
                                    v-else-if="field.type === 'text'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="uk-textarea"
                                    :rows="field.rows || 3"
                                    :required="field.required"
                                    :placeholder="field.placeholder"
                                ></textarea>
                                
                                <!-- JSON field -->
                                <textarea
                                    v-else-if="field.type === 'json'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="uk-textarea"
                                    :rows="field.rows || 5"
                                    placeholder="Enter valid JSON"
                                ></textarea>
                                
                                <!-- Default text input -->
                                <input
                                    v-else
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="uk-input"
                                    :required="field.required"
                                />
                                
                                <small v-if="field.description" class="uk-text-muted">
                                    {{ field.description }}
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Default view mode with existing components -->
        <div v-else class="uk-child-width-expand" uk-grid>
            <div>
                <CRUD6Info :crud6="CRUD6Row" :schema="flattenedSchema" @crud6Updated="fetch()" />
            </div>
            <div class="uk-width-2-3" v-if="detailConfigs.length > 0 && $checkAccess('view_crud6_field')">
                <!-- Render multiple detail sections -->
                <CRUD6Details 
                    v-for="(detailConfig, index) in detailConfigs"
                    :key="`detail-${index}-${detailConfig.model}`"
                    :recordId="recordId" 
                    :parentModel="model" 
                    :detailConfig="detailConfig"
                    class="uk-margin-bottom"
                />
            </div>
        </div>
    </template>
</template>
