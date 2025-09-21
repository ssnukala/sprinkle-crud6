<template>
    <div class="crud6-detail-page">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">
                        {{ isEditMode ? $t('CRUD6.EDIT') : $t('VIEW') }} {{ schema?.title || model }}
                    </h3>
                    <small v-if="recordId" class="text-muted">ID: {{ recordId }}</small>
                </div>
                <div class="card-tools">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="goBack"
                    >
                        <i class="fas fa-arrow-left"></i>
                        {{ $t('BACK') }}
                    </button>
                    <button
                        v-if="!isEditMode && hasEditPermission"
                        type="button"
                        class="btn btn-primary"
                        @click="toggleEditMode"
                    >
                        <i class="fas fa-edit"></i>
                        {{ $t('CRUD6.EDIT') }}
                    </button>
                    <button
                        v-if="isEditMode"
                        type="button"
                        class="btn btn-success"
                        @click="saveRecord"
                        :disabled="loading"
                    >
                        <i class="fas fa-save"></i>
                        {{ $t('SAVE') }}
                    </button>
                    <button
                        v-if="isEditMode"
                        type="button"
                        class="btn btn-secondary"
                        @click="cancelEdit"
                    >
                        <i class="fas fa-times"></i>
                        {{ $t('CANCEL') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Loading state -->
                <div v-if="loading" class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">{{ $t('LOADING') }}</span>
                    </div>
                </div>
                
                <!-- Error state -->
                <div v-else-if="error" class="alert alert-danger">
                    <h5>{{ error.title }}</h5>
                    <p>{{ error.description }}</p>
                </div>
                
                <!-- Form -->
                <form v-else-if="schema && record" @submit.prevent="saveRecord">
                    <div class="row">
                        <div
                            v-for="[fieldKey, field] in Object.entries(schema.fields)"
                            :key="fieldKey"
                            class="col-md-6 mb-3"
                        >
                            <label :for="fieldKey" class="form-label">
                                {{ field.label || fieldKey }}
                                <span v-if="field.required" class="text-danger">*</span>
                            </label>
                            
                            <!-- Read-only field or view mode -->
                            <div v-if="!isEditMode || field.readonly" class="form-control-plaintext">
                                {{ formatFieldValue(record[fieldKey], field) }}
                            </div>
                            
                            <!-- Editable fields -->
                            <template v-else>
                                <!-- Text input -->
                                <input
                                    v-if="field.type === 'string'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="form-control"
                                    :required="field.required"
                                />
                                
                                <!-- Number input -->
                                <input
                                    v-else-if="['integer', 'decimal', 'float'].includes(field.type)"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="number"
                                    class="form-control"
                                    :required="field.required"
                                    :step="field.type === 'integer' ? '1' : 'any'"
                                />
                                
                                <!-- Boolean checkbox -->
                                <div v-else-if="field.type === 'boolean'" class="form-check">
                                    <input
                                        :id="fieldKey"
                                        v-model="record[fieldKey]"
                                        type="checkbox"
                                        class="form-check-input"
                                    />
                                    <label :for="fieldKey" class="form-check-label">
                                        {{ field.label || fieldKey }}
                                    </label>
                                </div>
                                
                                <!-- Date input -->
                                <input
                                    v-else-if="field.type === 'date'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="date"
                                    class="form-control"
                                    :required="field.required"
                                />
                                
                                <!-- DateTime input -->
                                <input
                                    v-else-if="field.type === 'datetime'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="datetime-local"
                                    class="form-control"
                                    :required="field.required"
                                />
                                
                                <!-- Text area -->
                                <textarea
                                    v-else-if="field.type === 'text'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="form-control"
                                    rows="3"
                                    :required="field.required"
                                ></textarea>
                                
                                <!-- JSON field -->
                                <textarea
                                    v-else-if="field.type === 'json'"
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    class="form-control"
                                    rows="5"
                                    placeholder="Enter valid JSON"
                                ></textarea>
                                
                                <!-- Default text input -->
                                <input
                                    v-else
                                    :id="fieldKey"
                                    v-model="record[fieldKey]"
                                    type="text"
                                    class="form-control"
                                    :required="field.required"
                                />
                            </template>
                            
                            <small v-if="field.description" class="form-text text-muted">
                                {{ field.description }}
                            </small>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '../composables/useCRUD6Schema'
import { useCRUD6Api } from '../composables/useCRUD6Api'
import type { CRUD6Interface } from '../interfaces'

const route = useRoute()
const router = useRouter()

// Get model and ID from route parameters
const model = computed(() => route.params.model as string)
const recordId = computed(() => route.params.id as string)
const isCreateMode = computed(() => route.name === 'crud6-create')
const isEditMode = ref(isCreateMode.value)

// Use composables
const {
    schema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema,
    hasPermission
} = useCRUD6Schema()

const {
    fetchCRUD6,
    createCRUD6,
    updateCRUD6,
    apiLoading,
    apiError,
    formData,
    resetForm
} = useCRUD6Api()

// Reactive state
const record = ref<CRUD6Interface | null>(null)
const originalRecord = ref<CRUD6Interface | null>(null)

// Combined loading and error states
const loading = computed(() => schemaLoading.value || apiLoading.value)
const error = computed(() => schemaError.value || apiError.value)

// Permission checks
const hasEditPermission = computed(() => hasPermission('update'))

// Actions
function goBack() {
    router.push(`/crud6/${model.value}`)
}

function toggleEditMode() {
    isEditMode.value = true
    // Store original record for cancel functionality
    originalRecord.value = { ...record.value }
}

function cancelEdit() {
    if (originalRecord.value) {
        record.value = { ...originalRecord.value }
    }
    isEditMode.value = false
}

async function saveRecord() {
    if (!record.value) return

    try {
        if (isCreateMode.value) {
            await createCRUD6(record.value)
            router.push(`/crud6/${model.value}`)
        } else {
            await updateCRUD6(recordId.value, record.value)
            isEditMode.value = false
            originalRecord.value = { ...record.value }
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
            return value ? $t('YES') : $t('NO')
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
    if (model.value) {
        await loadSchema(model.value)
        
        if (!isCreateMode.value && recordId.value) {
            try {
                record.value = await fetchCRUD6(recordId.value)
                originalRecord.value = { ...record.value }
            } catch (error) {
                console.error('Failed to load record:', error)
            }
        } else if (isCreateMode.value) {
            // Initialize empty record for create mode
            record.value = {}
            resetForm()
        }
    }
})

// Watch for route changes
watch([model, recordId], async ([newModel, newId]) => {
    if (newModel) {
        await loadSchema(newModel)
        
        if (newId && !isCreateMode.value) {
            try {
                record.value = await fetchCRUD6(newId)
                originalRecord.value = { ...record.value }
            } catch (error) {
                console.error('Failed to load record:', error)
            }
        }
    }
})
</script>

<style scoped>
.crud6-detail-page {
    padding: 1rem;
}

.card-tools .btn {
    margin-left: 0.5rem;
}

.form-control-plaintext {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}
</style>

