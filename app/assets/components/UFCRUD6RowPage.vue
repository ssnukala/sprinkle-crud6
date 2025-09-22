<template>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-edit mr-1"></i>
                {{ schema?.title || 'CRUD6' }} - {{ isCreate ? 'Create' : 'Edit' }}
            </h3>
            <div class="card-tools">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    @click="goBack"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back to List
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="loading" class="text-center">
                <i class="fas fa-spinner fa-spin"></i>
                Loading...
            </div>
            <div v-else-if="error" class="alert alert-danger">
                <h4>{{ error.title }}</h4>
                <p>{{ error.description }}</p>
            </div>
            <form v-else-if="schema" @submit.prevent="saveRecord">
                <div class="row">
                    <div 
                        v-for="(field, fieldName) in editableFields" 
                        :key="fieldName"
                        class="col-md-6 mb-3"
                    >
                        <label :for="fieldName" class="form-label">
                            {{ field.label }}
                            <span v-if="field.required" class="text-danger">*</span>
                        </label>
                        
                        <!-- Text inputs -->
                        <input
                            v-if="field.type === 'string'"
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            type="text"
                            class="form-control"
                            :required="field.required"
                            :readonly="field.readonly"
                        />
                        
                        <!-- Textarea -->
                        <textarea
                            v-else-if="field.type === 'text'"
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            class="form-control"
                            rows="3"
                            :required="field.required"
                            :readonly="field.readonly"
                        ></textarea>
                        
                        <!-- Number inputs -->
                        <input
                            v-else-if="field.type === 'integer' || field.type === 'float' || field.type === 'decimal'"
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            type="number"
                            class="form-control"
                            :required="field.required"
                            :readonly="field.readonly"
                        />
                        
                        <!-- Date inputs -->
                        <input
                            v-else-if="field.type === 'date'"
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            type="date"
                            class="form-control"
                            :required="field.required"
                            :readonly="field.readonly"
                        />
                        
                        <!-- Datetime inputs -->
                        <input
                            v-else-if="field.type === 'datetime'"
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            type="datetime-local"
                            class="form-control"
                            :required="field.required"
                            :readonly="field.readonly"
                        />
                        
                        <!-- Boolean inputs -->
                        <div v-else-if="field.type === 'boolean'" class="form-check">
                            <input
                                :id="fieldName"
                                v-model="formData[fieldName]"
                                type="checkbox"
                                class="form-check-input"
                                :disabled="field.readonly"
                            />
                            <label :for="fieldName" class="form-check-label">
                                {{ field.label }}
                            </label>
                        </div>
                        
                        <!-- Default fallback -->
                        <input
                            v-else
                            :id="fieldName"
                            v-model="formData[fieldName]"
                            type="text"
                            class="form-control"
                            :required="field.required"
                            :readonly="field.readonly"
                        />
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-secondary me-2" @click="goBack">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="btn btn-primary"
                        :disabled="saving"
                    >
                        <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                        {{ isCreate ? 'Create' : 'Update' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '../composables/useCRUD6Schema'
import { useCRUD6Api } from '../composables/useCRUD6Api'

const route = useRoute()
const router = useRouter()

// Get model name and ID from route parameters
const modelName = computed(() => route.params.model as string)
const recordId = computed(() => route.params.id as string)
const isCreate = computed(() => !recordId.value || recordId.value === 'create')

// Use composables
const { schema, loading, error, loadSchema } = useCRUD6Schema()
const { fetchCRUD6, updateCRUD6, createCRUD6, apiLoading, apiError } = useCRUD6Api(modelName.value)

// Store the current row data
const crud6Row = ref<any>(null)

// Form state
const formData = reactive<Record<string, any>>({})
const saving = ref(false)

// Get editable fields from schema
const editableFields = computed(() => {
    if (!schema.value?.fields) return {}
    
    const fields: Record<string, any> = {}
    for (const [key, field] of Object.entries(schema.value.fields)) {
        // Skip auto-increment fields for create, and include non-readonly fields
        if (isCreate.value && field.auto_increment) continue
        if (!field.readonly || !isCreate.value) {
            fields[key] = field
        }
    }
    return fields
})

// Load data when component mounts
onMounted(async () => {
    if (modelName.value) {
        await loadSchema(modelName.value)
        
        if (!isCreate.value && recordId.value) {
            // Load existing record for editing
            try {
                crud6Row.value = await fetchCRUD6(recordId.value)
                if (crud6Row.value) {
                    // Populate form data
                    Object.assign(formData, crud6Row.value)
                }
            } catch (err) {
                console.error('Failed to load record:', err)
            }
        } else {
            // Initialize form data for new record
            if (schema.value?.fields) {
                for (const [key, field] of Object.entries(schema.value.fields)) {
                    if (field.default !== undefined) {
                        formData[key] = field.default
                    }
                }
            }
        }
    }
})

async function saveRecord() {
    saving.value = true
    
    try {
        if (isCreate.value) {
            await createCRUD6(formData)
        } else {
            await updateCRUD6(recordId.value, formData)
        }
        
        // Navigate back to list on success
        router.push(`/crud6/${modelName.value}`)
    } catch (err) {
        console.error('Save failed:', err)
    } finally {
        saving.value = false
    }
}

function goBack() {
    router.push(`/crud6/${modelName.value}`)
}
</script>

<style scoped>
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
}

.mr-1 {
    margin-right: 0.25rem;
}

.me-2 {
    margin-right: 0.5rem;
}

.text-end {
    text-align: end;
}
</style>