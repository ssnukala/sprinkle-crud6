<script setup lang="ts">
import { watch, computed, onMounted } from 'vue'
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'

/**
 * Props - Optional CRUD6 object for editing, model for schema loading, and optional schema to avoid duplicate loads
 */
const props = defineProps<{ 
    crud6?: CRUD6Interface
    model?: string
    schema?: any
}>()

console.log('[Form] 🚀 Component setup - hasProvidedSchema:', !!props.schema, 'model:', props.model, 'isEdit:', !!props.crud6)
if (props.schema) {
    console.log('[Form] 📊 Schema provided by parent - title:', props.schema?.title, 'fieldCount:', Object.keys(props.schema?.fields || {}).length)
} else {
    console.log('[Form] ⚠️  No schema provided - will attempt to load via composable if model available')
}

/**
 * API - Use the CRUD6 edit API
 */
const { createRow, updateRow, r$, formData, apiLoading, resetForm, slugLocked } = useCRUD6Api()

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
const schema = computed(() => props.schema || composableSchema.value)

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
        console.log('[Form] 📊 Model prop changed to:', newModel, 'hasSchemaProps:', !!props.schema, 'hasLoadSchema:', !!loadSchema)
        // Only load schema if ALL conditions are met:
        // 1. We have a model
        // 2. We have a loadSchema function 
        // 3. NO schema was provided as a prop (PageRow should provide it)
        if (newModel && loadSchema && !props.schema) {
            console.log('[Form] 🔄 Loading schema for model (no schema prop provided):', newModel)
            console.log('[Form] 📍 Context - this indicates Form is used standalone without PageRow schema prop')
            const schemaPromise = loadSchema(newModel)
            if (schemaPromise && typeof schemaPromise.then === 'function') {
                schemaPromise.then(() => {
                    console.log('[Form] ✅ Schema loaded via composable for model:', newModel)
                }).catch((error) => {
                    console.error('[Form] ❌ Failed to load schema via composable:', error)
                })
            }
        } else if (props.schema) {
            console.log('[Form] ✅ Using provided schema prop - NO API call for model:', newModel)
            console.log('[Form] 📋 Provided schema from parent - title:', props.schema?.title, 'fields:', Object.keys(props.schema?.fields || {}))
            console.log('[Form] 🎯 This is the OPTIMIZED path - schema loaded once by PageRow and passed down')
        } else {
            console.log('[Form] ⚠️  No schema loading needed - missing model or loadSchema function')
            console.log('[Form] 📊 Conditions - model:', !!newModel, 'loadSchema:', !!loadSchema, 'hasSchemaProps:', !!props.schema)
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
    // Make sure validation is up to date
    const isValid = r$ ? await r$.$validate() : { valid: true }
    if (!isValid.valid) return

    const apiCall = props.crud6
        ? updateRow(props.crud6.slug, formData.value)
        : createRow(formData.value)
    apiCall
        .then(() => {
            emits('success')
            resetForm()
        })
        .catch(() => {})
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
                    
                    <!-- Text input -->
                    <input
                        v-if="['string', 'email', 'url'].includes(field.type) || !field.type"
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
