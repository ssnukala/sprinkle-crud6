<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6Info from '../../components/Pages/CRUD6/Base/Info.vue'
import CRUD6Users from '../../components/Pages/CRUD6/Base/Users.vue'
import type { CRUD6Response, CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'

/**
 * Variables and composables
 */
const route = useRoute()
const router = useRouter()
const page = usePageMeta()

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

// Permission checks
const hasCreatePermission = computed(() => hasPermission('create'))
const hasViewPermission = computed(() => hasPermission('view'))

/**
 * Methods - Fetch record
 */
function fetch() {
    if (recordId.value && fetchRow) {
        const fetchPromise = fetchRow(recordId.value)
        if (fetchPromise && typeof fetchPromise.then === 'function') {
            fetchPromise.then((fetchedRow) => {
                CRUD6Row.value = fetchedRow.data
                record.value = fetchedRow.data
                originalRecord.value = { ...fetchedRow.data }
                page.title = CRUD6Row.value.name
            }).catch((error) => {
                console.error('Failed to fetch CRUD6 row:', error)
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
        console.error('Save failed:', error)
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
        default:
            return String(value)
    }
}

// Load data when component mounts
onMounted(async () => {
    console.log('[PageRow] 🚀 Component mounted - route:', route.path, 'model:', model.value, 'recordId:', recordId.value)
    console.log('[PageRow] 📋 Mount state - isCreateMode:', isCreateMode.value, 'hasSchema:', !!schema.value)
    
    // Schema loading is handled by the route watcher with immediate: true
    // No need to load schema here to avoid duplicate calls
    console.log('[PageRow] 📝 Schema loading delegated to model watcher to avoid duplicate API calls')
    
    if (!isCreateMode.value && recordId.value) {
        console.log('[PageRow] 📖 Fetching record data for ID:', recordId.value)
        fetch()
    } else if (isCreateMode.value) {
        console.log('[PageRow] ➕ Initialize create mode with schema-based record structure')
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
        }
    },
    { immediate: false }
)

// Watch for schema changes to update initial record structure
watch(
    () => schema.value,
    (newSchema) => {
        console.log('[PageRow] 📊 Schema value changed - hasFields:', !!newSchema?.fields, 'isCreateMode:', isCreateMode.value)
        if (newSchema?.fields && isCreateMode.value) {
            console.log('[PageRow] 🔄 Updating initial record structure for create mode with schema fields:', Object.keys(newSchema.fields))
            // Update the initial record structure when schema loads in create mode
            CRUD6Row.value = createInitialRecord(newSchema.fields)
        } else if (newSchema?.fields) {
            console.log('[PageRow] 📝 Schema available but not in create mode - no record structure update needed')
        } else {
            console.log('[PageRow] ⚠️  Schema change but no fields available yet')
        }
    }
)

// Load schema when model changes - single source of truth for schema loading
let currentModel = ''
watch(model, async (newModel) => {
    console.log('[PageRow] Schema loading watcher triggered - model:', newModel, 'currentModel:', currentModel, 'route:', route.path)
    if (newModel && loadSchema && newModel !== currentModel) {
        try {
            console.log('[PageRow] 🔄 Starting schema API call for model:', newModel, 'at route:', route.path)
            console.log('[PageRow] 📍 Schema loading context - recordId:', recordId.value, 'isCreateMode:', isCreateMode.value)
            currentModel = newModel
            const schemaPromise = loadSchema(newModel)
            if (schemaPromise && typeof schemaPromise.then === 'function') {
                await schemaPromise
                console.log('[PageRow] ✅ Schema loaded successfully for model:', newModel, 'with fields:', Object.keys(schema.value?.fields || {}))
                console.log('[PageRow] 📊 Schema details - title:', schema.value?.title, 'field count:', Object.keys(schema.value?.fields || {}).length)
            }
        } catch (error) {
            console.error('[PageRow] ❌ Failed to load schema for model:', newModel, 'error:', error)
        }
    } else {
        console.log('[PageRow] ⏭️  Skipping schema load - conditions not met:', {
            hasModel: !!newModel,
            hasLoadSchema: !!loadSchema,
            modelChanged: newModel !== currentModel,
            model: newModel,
            currentModel: currentModel
        })
    }
}, { immediate: true })

// Watch for recordId changes to fetch data
watch(recordId, (newId) => {
    if (newId && !isCreateMode.value) {
        fetch()
    }
}, { immediate: true })
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
        <div v-if="isEditMode && schema" class="uk-container">
            <div class="uk-card uk-card-default">
                <div class="uk-card-header">
                    <div class="uk-flex uk-flex-between uk-flex-middle">
                        <div>
                            <h3 class="uk-card-title uk-margin-remove">
                                {{ isCreateMode ? $t('CREATE') : $t('EDIT') }} {{ schema?.title || model }}
                            </h3>
                            <small v-if="recordId" class="uk-text-muted">ID: {{ recordId }}</small>
                        </div>
                        <div>
                            <button
                                type="button"
                                class="uk-button uk-button-default"
                                @click="goBack">
                                <font-awesome-icon icon="arrow-left" /> Back
                            </button>
                            <button
                                type="button"
                                class="uk-button uk-button-primary"
                                @click="saveRecord"
                                :disabled="loading">
                                <font-awesome-icon icon="save" /> Save
                            </button>
                            <button
                                v-if="!isCreateMode"
                                type="button"
                                class="uk-button uk-button-secondary"
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
                                :class="field.width || 'uk-width-1-2'"
                                v-if="field.editable !== false">
                                
                                <label :for="fieldKey" class="uk-form-label">
                                    {{ field.label || fieldKey }}
                                    <span v-if="field.required" class="uk-text-danger">*</span>
                                </label>
                                
                                <!-- Text input -->
                                <input
                                    v-if="field.type === 'string' || !field.type"
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
                <CRUD6Info :crud6="CRUD6Row" :schema="schema" @crud6Updated="fetch()" />
            </div>
            <div class="uk-width-2-3" v-if="$checkAccess('view_crud6_field')">
                <CRUD6Users :slug="$route.params.id" />
            </div>
        </div>
    </template>
</template>
