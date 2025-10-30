<script setup lang="ts">
import { watch, computed, onMounted } from 'vue'
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import CRUD6AutoLookup from './AutoLookup.vue'

/**
 * Props - Optional CRUD6 object for editing, model for schema loading, and optional schema to avoid duplicate loads
 */
const props = defineProps<{ 
    crud6?: CRUD6Interface
    model?: string
    schema?: any
}>()

if (props.schema) {
} else {
}

/**
 * API - Use the CRUD6 edit API
 */
const { createRow, updateRow, r$, formData, apiLoading, resetForm, slugLocked } = useCRUD6Api(props.model)

/**
 * Schema - Use the CRUD6 schema composable for dynamic form generation or use provided schema
 * 
 * Schema source resolution strategy:
 * 1. If parent provides schema prop → use it (optimized, no API call)
 * 2. Otherwise → load via composable (standalone usage)
 * This allows Form to work both in optimized PageRow/PageList context
 * and as a standalone component.
 */
const {
    schema: composableSchema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema
} = useCRUD6Schema()

// Use provided schema or fallback to composable schema
// If the provided schema has contexts (multi-context), extract the form context
const schema = computed(() => {
    if (props.schema) {
        // Check if this is a multi-context schema response
        if (props.schema.contexts?.form) {
            // Extract form context and merge with base metadata
            return {
                ...props.schema,
                fields: props.schema.contexts.form.fields || props.schema.fields,
                ...props.schema.contexts.form
            }
        }
        // Single-context or legacy schema
        return props.schema
    }
    // Fallback to composable schema
    return composableSchema.value
})

/**
 * Computed properties for form rendering
 */
const editableFields = computed(() => {
    if (!schema.value?.fields) return {}
    return Object.fromEntries(
        Object.entries(schema.value.fields).filter(([key, field]) => field.editable !== false)
    )
})

const isLoading = computed(() => apiLoading.value || (!props.schema && schemaLoading.value))

/**
 * Watchers - Watch for changes in the crud6 prop and update formData
 * accordingly. Useful when the crud6 prop is updated from the parent component,
 * or the modal is reused.
 */
watch(
    () => props.crud6,
    (crud6) => {
        if (crud6 && schema.value?.fields) {
            // Dynamically populate formData based on schema fields
            Object.keys(schema.value.fields).forEach(fieldKey => {
                if (crud6[fieldKey] !== undefined) {
                    formData.value[fieldKey] = crud6[fieldKey]
                }
            })
        }
    },
    { immediate: true }
)

/**
 * Load schema when model prop changes (ONLY if no schema provided as prop)
 * When schema prop is provided, PageRow is the single source of truth for schema loading
 */
watch(
    () => props.model,
    (newModel) => {
        // Only load schema if ALL conditions are met:
        // 1. We have a model
        // 2. We have a loadSchema function 
        // 3. NO schema was provided as a prop (PageRow should provide it)
        if (newModel && loadSchema && !props.schema) {
            // Request 'form' context to get only editable fields with validation
            const schemaPromise = loadSchema(newModel, false, 'form')
            if (schemaPromise && typeof schemaPromise.then === 'function') {
                schemaPromise.then(() => {
                }).catch((error) => {
                    console.error('[Form] ❌ Failed to load schema via composable:', error)
                })
            }
        } else if (props.schema) {
        } else {
        }
    },
    { immediate: true }
)

// Schema loading is handled by the watcher above with immediate: true

/**
 * Emits
 */
const emits = defineEmits(['success'])

/**
 * Methods - Submit the form to the API and handle the response
 */
const submitForm = async () => {
    console.log('[Form] ===== FORM SUBMIT START =====', {
        model: props.model,
        hasCrud6: !!props.crud6,
        formData: formData.value,
    })

    // Make sure validation is up to date
    const isValid = r$ ? await r$.$validate() : { valid: true }
    
    console.log('[Form] Validation result', {
        model: props.model,
        isValid: isValid.valid,
        errors: r$ ? r$.$errors : null,
    })

    if (!isValid.valid) {
        console.warn('[Form] Validation failed, form not submitted', {
            model: props.model,
            errors: r$ ? r$.$errors : null,
        })
        return
    }

    // Use primary_key from schema, fallback to 'id'
    const primaryKey = schema.value?.primary_key || 'id'
    const recordId = props.crud6 ? props.crud6[primaryKey] : null

    console.log('[Form] Preparing API call', {
        model: props.model,
        primaryKey,
        recordId,
        operation: recordId ? 'UPDATE' : 'CREATE',
        formData: formData.value,
    })

    const apiCall = recordId
        ? updateRow(recordId, formData.value)
        : createRow(formData.value)
    
    apiCall
        .then(() => {
            console.log('[Form] ===== FORM SUBMIT SUCCESS =====', {
                model: props.model,
                operation: recordId ? 'UPDATE' : 'CREATE',
                recordId,
            })
            emits('success')
            resetForm()
        })
        .catch((error) => {
            console.error('[Form] ===== FORM SUBMIT FAILED =====', {
                model: props.model,
                operation: recordId ? 'UPDATE' : 'CREATE',
                recordId,
                error,
                formData: formData.value,
            })
        })
}

/**
 * Helper function to get field icon
 */
function getFieldIcon(field: any, fieldKey: string): string {
    if (field.icon) return field.icon
    
    // Default icons based on field type or name
    switch (field.type) {
        case 'email': return 'envelope'
        case 'password': return 'lock'
        case 'date': return 'calendar'
        case 'datetime': return 'clock'
        case 'boolean': return 'check-square'
        case 'number':
        case 'integer':
        case 'decimal': return 'hashtag'
        case 'text': return 'align-left'
        default:
            // Icon based on field name
            if (fieldKey.includes('name')) return 'pen-to-square'
            if (fieldKey.includes('slug')) return 'tag'
            if (fieldKey.includes('icon')) return 'icons'
            if (fieldKey.includes('description')) return 'align-left'
            return 'pen-to-square'
    }
}
</script>

<template>
    <!-- Loading state (only show if we don't have a provided schema) -->
    <div v-if="!props.schema && schemaLoading" class="uk-text-center uk-padding">
        <div uk-spinner></div>
        <p>{{ $t('LOADING') }}</p>
    </div>
    
    <!-- Error state (only show if we don't have a provided schema) -->
    <div v-else-if="!props.schema && schemaError" class="uk-alert-danger" uk-alert>
        <h4>{{ schemaError.title }}</h4>
        <p>{{ schemaError.description }}</p>
    </div>
    
    <!-- Dynamic form based on schema -->
    <form v-else-if="schema" v-on:submit.prevent="submitForm()">
        <fieldset class="uk-fieldset uk-form-stacked">
            <!-- Dynamic fields based on schema -->
            <div 
                v-for="[fieldKey, field] in Object.entries(editableFields)" 
                :key="fieldKey"
                class="uk-margin">
                
                <label class="uk-form-label" :for="fieldKey">
                    {{ field.label || fieldKey }}
                    <span v-if="field.required" class="uk-text-danger">*</span>
                </label>
                
                <span v-if="field.description" class="uk-text-meta">{{ field.description }}</span>
                
                <div class="uk-inline uk-width-1-1">
                    <!-- Field icon -->
                    <font-awesome-icon 
                        class="fa-form-icon" 
                        :icon="getFieldIcon(field, fieldKey)" 
                        fixed-width />
                    
                    <!-- Special handling for slug field with lock button -->
                    <button
                        v-if="fieldKey === 'slug'"
                        class="uk-button uk-button-default uk-form-button"
                        type="button"
                        :uk-tooltip="$t('OVERRIDE')"
                        @click="slugLocked = !slugLocked">
                        <font-awesome-icon fixed-width :icon="slugLocked ? 'lock' : 'lock-open'" />
                    </button>
                    
                    <!-- SmartLookup field -->
                    <CRUD6AutoLookup
                        v-if="field.type === 'smartlookup'"
                        :model="field.lookup_model || field.model"
                        :id-field="field.lookup_id || field.id || 'id'"
                        :display-field="field.lookup_desc || field.desc || 'name'"
                        :placeholder="field.placeholder"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]"
                    />
                    
                    <!-- Text input -->
                    <input
                        v-else-if="['string', 'email', 'url'].includes(field.type) || !field.type"
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        :type="field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="fieldKey === 'slug' ? slugLocked : field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Number input -->
                    <input
                        v-else-if="['number', 'integer', 'decimal', 'float'].includes(field.type)"
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="number"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :step="field.type === 'integer' ? '1' : 'any'"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Password input -->
                    <input
                        v-else-if="field.type === 'password'"
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="password"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Date input -->
                    <input
                        v-else-if="field.type === 'date'"
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="date"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- DateTime input -->
                    <input
                        v-else-if="field.type === 'datetime'"
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="datetime-local"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Textarea for text fields -->
                    <textarea
                        v-else-if="field.type === 'text'"
                        :id="fieldKey"
                        class="uk-textarea"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :rows="field.rows || 6"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Checkbox for boolean fields -->
                    <label v-else-if="field.type === 'boolean'" class="uk-form-label">
                        <input
                            :id="fieldKey"
                            class="uk-checkbox"
                            type="checkbox"
                            :data-test="fieldKey"
                            :disabled="field.readonly"
                            v-model="formData[fieldKey]" />
                        {{ field.label || fieldKey }}
                    </label>
                    
                    <!-- Default text input for unknown types -->
                    <input
                        v-else
                        :id="fieldKey"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="text"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="field.readonly"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Validation errors -->
                    <UFFormValidationError :errors="(r$ && r$.$errors && r$.$errors[fieldKey]) || []" />
                </div>
            </div>

            <!-- Form actions -->
            <div class="uk-text-right" uk-margin>
                <button class="uk-button uk-button-default uk-modal-close" type="button">
                    {{ $t('CANCEL') }}
                </button>
                <button
                    class="uk-button uk-button-primary"
                    :disabled="(r$ && r$.$error) || isLoading"
                    type="submit">
                    <div v-if="isLoading" uk-spinner="ratio: 0.5"></div>
                    {{ $t('SAVE') }}
                </button>
            </div>
        </fieldset>
    </form>
    
    <!-- Fallback for no schema -->
    <div v-else class="uk-alert-warning" uk-alert>
        <p>{{ $t('CRUD6.NO_SCHEMA') }}</p>
    </div>
</template>
