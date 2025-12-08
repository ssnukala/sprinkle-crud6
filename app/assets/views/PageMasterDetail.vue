<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Api, useCRUD6Schema, useMasterDetail, useCRUD6Breadcrumbs } from '@ssnukala/sprinkle-crud6/composables'
import type { DetailRecord, DetailEditableConfig } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Response, CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import CRUD6Info from '../components/CRUD6/Info.vue'
import CRUD6Details from '../components/CRUD6/Details.vue'
import CRUD6DetailGrid from '../components/CRUD6/DetailGrid.vue'
import CRUD6AutoLookup from '../components/CRUD6/AutoLookup.vue'
import { debugLog, debugWarn, debugError } from '../utils/debug'
import { getLookupConfig } from '../composables/useCRUD6FieldRenderer'

/**
 * PageMasterDetail Component
 * 
 * Extends PageRow with master-detail features:
 * - Supports master record with related detail records
 * - Provides inline detail editing with DetailGrid
 * - Supports "smartlookup" field type for autolookup functionality
 * - Saves master and details together in a single transaction
 * 
 * Usage:
 * Route should include detailConfig in meta or component props
 */

/**
 * Variables and composables
 */
const route = useRoute()
const router = useRouter()
const page = usePageMeta()
const { setDetailBreadcrumbs, updateBreadcrumbs } = useCRUD6Breadcrumbs()

// Get model and ID from route parameters
const model = computed(() => route.params.model as string)
const recordId = computed(() => route.params.id as string)
const isCreateMode = computed(() => route.name === 'crud6-create' || route.name === 'crud6-master-detail-create')
const isEditMode = ref(isCreateMode.value)

// Use composables for schema and API
const {
    schema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema,
    hasPermission
} = useCRUD6Schema()

// Load detail schema for detail_editable configuration
const {
    schema: detailSchema,
    loading: detailSchemaLoading,
    loadSchema: loadDetailSchema
} = useCRUD6Schema()

const {
    fetchRow,
    createRow,
    updateRow,
    apiLoading,
    apiError,
    formData,
    resetForm,
    recordBreadcrumb  // Get pre-computed breadcrumb from API
} = useCRUD6Api()

// Master-detail composable - will be initialized when schema is loaded
let masterDetailComposable: ReturnType<typeof useMasterDetail> | null = null

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

// Detail records for master-detail editing
const detailRecords = ref<DetailRecord[]>([])

// Combined loading and error states
const loading = computed(() => schemaLoading.value || detailSchemaLoading.value || apiLoading.value)
const error = computed(() => schemaError.value || detailSchemaError.value || apiError.value)

// Permission checks
const hasCreatePermission = computed(() => hasPermission('create'))
const hasViewPermission = computed(() => hasPermission('view'))

// Model label for page titles - prioritize singular_title over title
const modelLabel = computed(() => {
    if (schema.value?.singular_title) {
        return schema.value.singular_title
    }
    // Capitalize first letter of model name as fallback
    return model.value ? model.value.charAt(0).toUpperCase() + model.value.slice(1) : 'Record'
})

// Check if this is a master-detail enabled schema
const hasMasterDetail = computed(() => {
    return schema.value?.detail_editable !== undefined
})

// Get the detail configuration
const detailConfig = computed(() => {
    return schema.value?.detail_editable as DetailEditableConfig | undefined
})

// Computed property for detail configurations (supports both single and multiple)
const detailConfigs = computed(() => {
    if (!schema.value) return []
    
    // If schema has 'details' array (new format), use it
    if (schema.value.details && Array.isArray(schema.value.details)) {
        return schema.value.details
    }
    
    // If schema has single 'detail' object (legacy format), convert to array
    if (schema.value.detail) {
        return [schema.value.detail]
    }
    
    return []
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
                
                // Use pre-computed breadcrumb from API response
                // This eliminates the need to calculate the display name from schema
                const recordName = recordBreadcrumb.value || recordId.value
                
                page.title = `${recordName} - ${modelLabel.value}`
                
                // Update breadcrumbs with model title and record name
                const listPath = `/crud6/${model.value}`
                await setDetailBreadcrumbs(modelLabel.value, recordName, listPath)
            }).catch((error) => {
                debugError('Failed to fetch CRUD6 row:', error)
            })
        }
    }
}

// Load detail records for editing
async function loadDetailRecords() {
    if (!hasMasterDetail.value || !recordId.value || !masterDetailComposable) {
        return
    }
    
    try {
        const details = await masterDetailComposable.loadDetails(recordId.value)
        detailRecords.value = details.map(detail => ({
            ...detail,
            _action: 'update' as const
        }))
        debugLog('[PageMasterDetail] Detail records loaded', { count: detailRecords.value.length })
    } catch (error) {
        debugError('[PageMasterDetail] Failed to load details:', error)
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
    
    // Reload detail records if in master-detail mode
    if (hasMasterDetail.value && recordId.value) {
        loadDetailRecords()
    }
}

async function saveRecord() {
    if (!record.value) return

    try {
        if (hasMasterDetail.value && masterDetailComposable) {
            // Save master and details together
            debugLog('[PageMasterDetail] Saving master with details', {
                recordId: recordId.value,
                record: record.value,
                detailCount: detailRecords.value.length
            })
            
            await masterDetailComposable.saveMasterWithDetails(
                isCreateMode.value ? null : recordId.value,
                record.value,
                detailRecords.value
            )
            
            // Navigate back to list
            router.push(`/crud6/${model.value}`)
        } else {
            // Standard save for non-master-detail records
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
        CRUD6Row.value = createInitialRecord(schema.value?.fields)
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
            if (hasMasterDetail.value) {
                loadDetailRecords()
            }
        }
    },
    { immediate: false }
)

// Watch for schema changes to update initial record structure
watch(
    () => schema.value,
    async (newSchema) => {
        if (newSchema?.fields && isCreateMode.value) {
            // Update the initial record structure when schema loads in create mode
            CRUD6Row.value = createInitialRecord(newSchema.fields)
        }
        
        // Initialize master-detail composable if needed
        if (newSchema?.detail_editable && !masterDetailComposable) {
            const detailConfig = newSchema.detail_editable
            masterDetailComposable = useMasterDetail(
                model.value,
                detailConfig.model,
                detailConfig.foreign_key
            )
            
            // Load detail schema
            await loadDetailSchema(detailConfig.model)
            
            // Load detail records if editing
            if (!isCreateMode.value && recordId.value) {
                loadDetailRecords()
            }
        }
    }
)

// Load schema when model changes - single source of truth for schema loading
let currentModel = ''
watch(model, async (newModel) => {
    if (newModel && loadSchema && newModel !== currentModel) {
        debugLog('[PageMasterDetail] Schema loading triggered - model:', newModel, 'currentModel:', currentModel)
        
        // Set initial page title immediately for breadcrumbs
        const initialTitle = newModel.charAt(0).toUpperCase() + newModel.slice(1)
        page.title = isCreateMode.value ? `Create ${initialTitle}` : initialTitle
        
        // Update breadcrumbs with initial title (replace {{model}} placeholder)
        await updateBreadcrumbs(initialTitle)
        
        currentModel = newModel
        // Request all contexts needed by master-detail page in one consolidated API call
        // This prevents child components from making separate schema calls
        // Include related schemas to eliminate separate requests for detail models
        const schemaPromise = loadSchema(newModel, false, 'list,detail,form', true)
        if (schemaPromise && typeof schemaPromise.then === 'function') {
            await schemaPromise
            debugLog('[PageMasterDetail] Schema loaded successfully for model:', newModel)
            
            // Update page title and description
            if (schema.value) {
                const schemaTitle = schema.value.title || modelLabel.value
                
                if (isCreateMode.value) {
                    page.title = `Create ${modelLabel.value}`
                    page.description = schema.value.description || `Create a new ${modelLabel.value}`
                    
                    // Update breadcrumbs for create mode
                    await updateBreadcrumbs(schemaTitle)
                } else if (recordId.value) {
                    // Set title to schema title for breadcrumbs, will be updated with record name after fetch
                    page.title = schemaTitle
                    page.description = schema.value.description || `View and edit ${modelLabel.value} details.`
                    
                    // Update breadcrumbs - record breadcrumb will be added after fetch()
                    await updateBreadcrumbs(schemaTitle)
                }
            }
        }
    }
}, { immediate: true })

// Watch for recordId changes to fetch data
watch(recordId, (newId) => {
    if (newId && !isCreateMode.value) {
        fetch()
        if (hasMasterDetail.value) {
            loadDetailRecords()
        }
    }
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
    <template v-else-if="loading && !schema">
        <div class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ $t('LOADING') }}</p>
        </div>
    </template>
    <template v-else>
        <!-- Master-Detail edit/create mode -->
        <div v-if="isEditMode && schema && hasMasterDetail" class="uk-container">
            <div class="uk-card uk-card-default">
                <div class="uk-card-header">
                    <div class="uk-flex uk-flex-between uk-flex-middle">
                        <div>
                            <h3 class="uk-card-title uk-margin-remove">
                                {{ isCreateMode ? $t('CRUD6.CREATE', { model: schema?.title || model }) : $t('CRUD6.EDIT', { model: schema?.title || model }) }}
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
                    <!-- Master Record Form -->
                    <form v-if="schema && record" @submit.prevent="saveRecord" class="uk-form-stacked">
                        <div class="uk-grid-small" uk-grid>
                            <div
                                v-for="[fieldKey, field] in Object.entries(schema.fields)"
                                :key="fieldKey"
                                :class="field.width || (field.type === 'text' ? 'uk-width-1-1' : 'uk-width-1-2')"
                                v-if="field.editable !== false">
                                
                                <label :for="fieldKey" class="uk-form-label">
                                    {{ field.label || fieldKey }}
                                    <span v-if="field.required" class="uk-text-danger">*</span>
                                </label>
                                
                                <!-- SmartLookup field -->
                                <CRUD6AutoLookup
                                    v-if="field.type === 'smartlookup'"
                                    v-bind="getLookupAttributes(field)"
                                    :id-field="field.lookup_id || field.id || 'id'"
                                    :display-field="field.lookup_desc || field.desc || 'name'"
                                    v-model="record[fieldKey]"
                                />
                                
                                <!-- Text input -->
                                <input
                                    v-else-if="field.type === 'string' || !field.type"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="uk-input"
                                />
                                
                                <!-- Number input -->
                                <input
                                    v-else-if="['integer', 'decimal', 'float'].includes(field.type)"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="number"
                                    class="uk-input"
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
                                />
                                
                                <!-- DateTime input -->
                                <input
                                    v-else-if="field.type === 'datetime'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="datetime-local"
                                    class="uk-input"
                                />
                                
                                <!-- Text area -->
                                <textarea
                                    v-else-if="field.type === 'text'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="uk-textarea"
                                    :rows="field.rows || 3"
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
                                />
                                
                                <small v-if="field.description" class="uk-text-muted">
                                    {{ field.description }}
                                </small>
                            </div>
                        </div>
                    </form>

                    <!-- Detail Records Section -->
                    <div v-if="detailConfig && detailSchema" class="uk-margin-top">
                        <h4>{{ detailConfig.title || detailSchema.title }}</h4>
                        <CRUD6DetailGrid
                            v-model="detailRecords"
                            :detail-schema="detailSchema"
                            :fields="detailConfig.fields"
                            :allow-add="detailConfig.allow_add !== false"
                            :allow-edit="detailConfig.allow_edit !== false"
                            :allow-delete="detailConfig.allow_delete !== false"
                            :disabled="loading"
                        />
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Standard edit/create mode (without master-detail) -->
        <div v-else-if="isEditMode && schema" class="uk-container">
            <div class="uk-card uk-card-default">
                <div class="uk-card-header">
                    <div class="uk-flex uk-flex-between uk-flex-middle">
                        <div>
                            <h3 class="uk-card-title uk-margin-remove">
                                {{ isCreateMode ? $t('CRUD6.CREATE', { model: schema?.title || model }) : $t('CRUD6.EDIT', { model: schema?.title || model }) }}
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
                    <form v-if="schema && record" @submit.prevent="saveRecord" class="uk-form-stacked">
                        <div class="uk-grid-small" uk-grid>
                            <div
                                v-for="[fieldKey, field] in Object.entries(schema.fields)"
                                :key="fieldKey"
                                :class="field.width || (field.type === 'text' ? 'uk-width-1-1' : 'uk-width-1-2')"
                                v-if="field.editable !== false">
                                
                                <label :for="fieldKey" class="uk-form-label">
                                    {{ field.label || fieldKey }}
                                    <span v-if="field.required" class="uk-text-danger">*</span>
                                </label>
                                
                                <!-- SmartLookup field -->
                                <CRUD6AutoLookup
                                    v-if="field.type === 'smartlookup'"
                                    v-bind="getLookupAttributes(field)"
                                    :id-field="field.lookup_id || field.id || 'id'"
                                    :display-field="field.lookup_desc || field.desc || 'name'"
                                    v-model="record[fieldKey]"
                                />
                                
                                <!-- Text input -->
                                <input
                                    v-else-if="field.type === 'string' || !field.type"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="uk-input"
                                />
                                
                                <!-- Number input -->
                                <input
                                    v-else-if="['integer', 'decimal', 'float'].includes(field.type)"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="number"
                                    class="uk-input"
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
                                />
                                
                                <!-- DateTime input -->
                                <input
                                    v-else-if="field.type === 'datetime'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="datetime-local"
                                    class="uk-input"
                                />
                                
                                <!-- Text area -->
                                <textarea
                                    v-else-if="field.type === 'text'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="uk-textarea"
                                    :rows="field.rows || 3"
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
                <CRUD6Info :crud6="CRUD6Row" :schema="schema" @crud6Updated="fetch()" />
            </div>
            <div class="uk-width-2-3" v-if="detailConfigs.length > 0 && $checkAccess('view_crud6_field')">
                <!-- Render multiple detail sections -->
                <CRUD6Details 
                    v-for="(detailConfiguration, index) in detailConfigs"
                    :key="`detail-${index}-${detailConfiguration.model}`"
                    :recordId="recordId" 
                    :parentModel="model" 
                    :detailConfig="detailConfiguration"
                    class="uk-margin-bottom"
                />
            </div>
        </div>
    </template>
</template>
