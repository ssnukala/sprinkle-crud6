<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useMasterDetail, useCRUD6Schema, useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import type { DetailRecord, DetailEditableConfig } from '@ssnukala/sprinkle-crud6/composables'
import DetailGrid from './DetailGrid.vue'
import { debugLog, debugWarn, debugError } from '../../utils/debug'

/**
 * MasterDetailForm Component
 * 
 * A comprehensive form component for managing master-detail relationships.
 * Allows creating/editing a master record along with its associated detail records
 * in a single form with save operation.
 * 
 * Use Cases:
 * 1. Order + OrderDetails (one-to-many)
 * 2. Product + Categories via pivot table (many-to-many)
 */

/**
 * Generate a unique ID for this form instance to avoid duplicate IDs when multiple forms exist on the same page
 */
let instanceCounter = 0
const formInstanceId = `master-detail-form-${++instanceCounter}-${Date.now()}`

/**
 * Helper function to generate unique field ID
 * Prevents duplicate IDs when multiple forms exist on the same page
 */
function getFieldId(fieldKey: string): string {
    return `${formInstanceId}-${fieldKey}`
}

const props = defineProps<{
    model: string
    recordId?: string | number
    detailConfig: DetailEditableConfig
}>()

const emit = defineEmits<{
    'saved': []
    'cancelled': []
}>()

const router = useRouter()

// Load master schema
const {
    schema: masterSchema,
    loading: masterSchemaLoading,
    error: masterSchemaError,
    loadSchema: loadMasterSchema,
} = useCRUD6Schema()

// Load detail schema
const {
    schema: detailSchema,
    loading: detailSchemaLoading,
    error: detailSchemaError,
    loadSchema: loadDetailSchema,
} = useCRUD6Schema()

// API for master record operations
const {
    fetchRow,
    formData: masterFormData,
    apiLoading: masterApiLoading,
    apiError: masterApiError,
} = useCRUD6Api(props.model)

// Master-detail composable
const {
    saveMasterWithDetails,
    loadDetails,
    apiLoading: saveLoading,
    apiError: saveError,
} = useMasterDetail(
    props.model,
    props.detailConfig.model,
    props.detailConfig.foreign_key
)

// Detail records
const detailRecords = ref<DetailRecord[]>([])

// Combined loading state
const isLoading = computed(() => 
    masterSchemaLoading.value || 
    detailSchemaLoading.value || 
    masterApiLoading.value || 
    saveLoading.value
)

// Combined error state
const error = computed(() => 
    masterSchemaError.value || 
    detailSchemaError.value || 
    masterApiError.value || 
    saveError.value
)

// Mode detection
const isEditMode = computed(() => !!props.recordId)

// Get editable master fields (exclude readonly, auto_increment, timestamps)
const editableMasterFields = computed(() => {
    if (!masterSchema.value?.fields) return []
    
    return Object.entries(masterSchema.value.fields)
        .filter(([key, field]) => {
            // Exclude readonly, auto_increment, and timestamp fields
            if (field.readonly || field.auto_increment) return false
            if (key === 'created_at' || key === 'updated_at' || key === 'deleted_at') return false
            return true
        })
        .map(([key, field]) => ({ key, ...field }))
})

// Load schemas on mount
onMounted(async () => {
    debugLog('[MasterDetailForm] Component mounted', {
        model: props.model,
        recordId: props.recordId,
        detailConfig: props.detailConfig,
    })

    // Load master schema
    await loadMasterSchema(props.model)

    // Load detail schema
    if (props.detailConfig.model) {
        await loadDetailSchema(props.detailConfig.model)
    }

    // If editing, load master record and details
    if (props.recordId && fetchRow) {
        try {
            const masterRecord = await fetchRow(props.recordId.toString())
            
            // Populate master form data
            if (masterRecord && masterSchema.value?.fields) {
                Object.keys(masterSchema.value.fields).forEach(fieldKey => {
                    if (masterRecord[fieldKey] !== undefined) {
                        masterFormData.value[fieldKey] = masterRecord[fieldKey]
                    }
                })
            }

            // Load detail records
            const details = await loadDetails(props.recordId)
            detailRecords.value = details.map(detail => ({
                ...detail,
                _action: 'update' as const, // Mark existing records for potential update
            }))

            debugLog('[MasterDetailForm] Record loaded', {
                masterRecord,
                detailCount: detailRecords.value.length,
            })
        } catch (error) {
            debugError('[MasterDetailForm] Failed to load record', error)
        }
    }
})

// Submit form
async function submitForm() {
    debugLog('[MasterDetailForm] Submit form', {
        recordId: props.recordId,
        masterData: masterFormData.value,
        detailCount: detailRecords.value.length,
    })

    try {
        const response = await saveMasterWithDetails(
            props.recordId || null,
            masterFormData.value,
            detailRecords.value
        )

        debugLog('[MasterDetailForm] Save successful', response)
        emit('saved')

        // Navigate to list page
        router.push(`/crud6/${props.model}`)
    } catch (error) {
        debugError('[MasterDetailForm] Save failed', error)
    }
}

// Cancel form
function cancelForm() {
    debugLog('[MasterDetailForm] Cancel form')
    emit('cancelled')
    router.push(`/crud6/${props.model}`)
}

// Get field type for rendering
function getFieldType(field: any): string {
    return field.type || 'string'
}

// Get field label
function getFieldLabel(field: any): string {
    return field.label || field.key.charAt(0).toUpperCase() + field.key.slice(1).replace(/_/g, ' ')
}
</script>

<template>
    <div class="master-detail-form">
        <!-- Loading state -->
        <div v-if="isLoading && !masterSchema" class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ $t('LOADING') }}</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="uk-alert-danger" uk-alert>
            <p>{{ error.message || 'An error occurred while loading the form' }}</p>
        </div>

        <!-- Form -->
        <form v-else @submit.prevent="submitForm" class="uk-form-stacked">
            <!-- Master Record Section -->
            <UFCardBox :title="isEditMode ? `Edit ${masterSchema?.singular_title || model}` : `Create ${masterSchema?.singular_title || model}`">
                <div class="uk-grid-small" uk-grid>
                    <div 
                        v-for="field in editableMasterFields" 
                        :key="field.key"
                        :class="field.type === 'text' ? 'uk-width-1-1' : 'uk-width-1-2@s'">
                        <label class="uk-form-label" :for="getFieldId(field.key)">
                            {{ getFieldLabel(field) }}
                            <span v-if="field.required" class="uk-text-danger">*</span>
                        </label>
                        <div class="uk-form-controls">
                            <!-- Text input -->
                            <input
                                v-if="getFieldType(field) === 'string'"
                                :id="getFieldId(field.key)"
                                v-model="masterFormData[field.key]"
                                type="text"
                                class="uk-input"
                                :required="field.required"
                                :readonly="field.readonly"
                            />

                            <!-- Textarea -->
                            <textarea
                                v-else-if="getFieldType(field) === 'text'"
                                :id="getFieldId(field.key)"
                                v-model="masterFormData[field.key]"
                                class="uk-textarea"
                                rows="3"
                                :required="field.required"
                                :readonly="field.readonly"
                            ></textarea>

                            <!-- Number input -->
                            <input
                                v-else-if="getFieldType(field) === 'integer' || getFieldType(field) === 'decimal' || getFieldType(field) === 'float'"
                                :id="getFieldId(field.key)"
                                v-model.number="masterFormData[field.key]"
                                type="number"
                                class="uk-input"
                                :step="getFieldType(field) === 'integer' ? '1' : '0.01'"
                                :required="field.required"
                                :readonly="field.readonly"
                            />

                            <!-- Checkbox -->
                            <label v-else-if="getFieldType(field) === 'boolean'" class="uk-form-label">
                                <input
                                    :id="getFieldId(field.key)"
                                    v-model="masterFormData[field.key]"
                                    type="checkbox"
                                    class="uk-checkbox"
                                    :disabled="field.readonly"
                                />
                                {{ getFieldLabel(field) }}
                            </label>

                            <!-- Date input -->
                            <input
                                v-else-if="getFieldType(field) === 'date'"
                                :id="getFieldId(field.key)"
                                v-model="masterFormData[field.key]"
                                type="date"
                                class="uk-input"
                                :required="field.required"
                                :readonly="field.readonly"
                            />

                            <!-- Datetime input -->
                            <input
                                v-else-if="getFieldType(field) === 'datetime'"
                                :id="getFieldId(field.key)"
                                v-model="masterFormData[field.key]"
                                type="datetime-local"
                                class="uk-input"
                                :required="field.required"
                                :readonly="field.readonly"
                            />

                            <!-- Default text input -->
                            <input
                                v-else
                                :id="getFieldId(field.key)"
                                v-model="masterFormData[field.key]"
                                type="text"
                                class="uk-input"
                                :required="field.required"
                                :readonly="field.readonly"
                            />
                        </div>
                    </div>
                </div>
            </UFCardBox>

            <!-- Detail Records Section -->
            <UFCardBox 
                :title="detailConfig.title || `${detailSchema?.title || detailConfig.model}`"
                class="uk-margin-top">
                <DetailGrid
                    v-if="detailSchema"
                    v-model="detailRecords"
                    :detail-schema="detailSchema"
                    :fields="detailConfig.fields"
                    :allow-add="detailConfig.allow_add !== false"
                    :allow-edit="detailConfig.allow_edit !== false"
                    :allow-delete="detailConfig.allow_delete !== false"
                    :disabled="isLoading"
                />
            </UFCardBox>

            <!-- Form Actions -->
            <div class="uk-margin-top uk-text-right">
                <button 
                    type="button" 
                    class="uk-button uk-button-default uk-margin-small-right"
                    @click="cancelForm"
                    :disabled="isLoading">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="uk-button uk-button-primary"
                    :disabled="isLoading">
                    <span v-if="isLoading" uk-spinner="ratio: 0.5"></span>
                    {{ isEditMode ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
.master-detail-form {
    padding: 1rem;
}

.uk-form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.uk-text-danger {
    color: #f0506e;
}
</style>
