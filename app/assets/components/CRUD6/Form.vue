<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { watch, computed, onMounted } from 'vue'
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import CRUD6AutoLookup from './AutoLookup.vue'
import GoogleAddress from './GoogleAddress.vue'
import CRUD6ToggleSwitch from './ToggleSwitch.vue'
import { debugLog, debugWarn, debugError } from '../../utils/debug'
import { parseTextareaConfig, getInputType, getInputPattern, isBooleanType, getBooleanUIType, isAddressType, getAutocompleteAttribute } from '../../utils/fieldTypes'
import { getLookupConfig } from '../../composables/useCRUD6FieldRenderer'

/**
 * Generate a unique ID for this form instance to avoid duplicate IDs when multiple forms exist on the same page
 */
let instanceCounter = 0
const formInstanceId = `form-${++instanceCounter}-${Date.now()}`

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
 * Computed property for form layout configuration
 * Returns the appropriate UIKit grid class based on schema configuration
 * Defaults to 2-column layout for better space utilization
 */
const formLayoutClass = computed(() => {
    const layout = schema.value?.form_layout || '2-column'
    
    switch (layout) {
        case '1-column':
            return 'uk-child-width-1-1'
        case '3-column':
            return 'uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@m'
        case '2-column':
        default:
            return 'uk-child-width-1-1 uk-child-width-1-2@s'
    }
})

/**
 * Helper function to check if a field is disabled/non-editable
 * @param field - The field configuration object
 * @returns true if field should be disabled
 */
const isFieldDisabled = (field: any): boolean => {
    return field.editable === false
}

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
                    debugError('[Form] ❌ Failed to load schema via composable:', error)
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
 * Handle address selection from Google Places
 * Automatically populate related address fields
 */
const handleAddressSelected = (addressData: any) => {
    debugLog('[Form] Address selected from Google Places', addressData)
    
    const { mappedData } = addressData
    
    // Update formData with geocoded address components
    if (mappedData) {
        Object.keys(mappedData).forEach((fieldKey) => {
            if (formData.value) {
                formData.value[fieldKey] = mappedData[fieldKey]
                debugLog('[Form] Updated address field', {
                    field: fieldKey,
                    value: mappedData[fieldKey]
                })
            }
        })
    }
}

/**
 * Methods - Submit the form to the API and handle the response
 */
const submitForm = async () => {
    debugLog('[Form] ===== FORM SUBMIT START =====', {
        model: props.model,
        hasCrud6: !!props.crud6,
        formData: formData.value,
    })

    // Make sure validation is up to date
    const isValid = r$ ? await r$.$validate() : { valid: true }
    
    debugLog('[Form] Validation result', {
        model: props.model,
        isValid: isValid.valid,
        errors: r$ ? r$.$errors : null,
    })

    if (!isValid.valid) {
        debugWarn('[Form] Validation failed, form not submitted', {
            model: props.model,
            errors: r$ ? r$.$errors : null,
        })
        return
    }

    // Use primary_key from schema, fallback to 'id'
    const primaryKey = schema.value?.primary_key || 'id'
    const recordId = props.crud6 ? props.crud6[primaryKey] : null

    debugLog('[Form] Preparing API call', {
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
            debugLog('[Form] ===== FORM SUBMIT SUCCESS =====', {
                model: props.model,
                operation: recordId ? 'UPDATE' : 'CREATE',
                recordId,
            })
            emits('success')
            resetForm()
        })
        .catch((error) => {
            debugError('[Form] ===== FORM SUBMIT FAILED =====', {
                model: props.model,
                operation: recordId ? 'UPDATE' : 'CREATE',
                recordId,
                error,
                formData: formData.value,
            })
        })
}

/**
 * Helper function to generate unique field ID
 * Prevents duplicate IDs when multiple forms exist on the same page
 */
function getFieldId(fieldKey: string): string {
    return `${formInstanceId}-${fieldKey}`
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
        required: field.required,
        disabled: isFieldDisabled(field)
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
            <!-- Dynamic fields grid based on schema layout configuration -->
            <div class="uk-grid-small" :class="formLayoutClass" uk-grid>
                <!-- Dynamic fields based on schema -->
                <div 
                    v-for="[fieldKey, field] in Object.entries(editableFields)" 
                    :key="fieldKey"
                    class="uk-margin">
                
                    <label class="uk-form-label" :for="getFieldId(fieldKey)">
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
                        data-test="btn-toggle-slug-lock"
                        :uk-tooltip="$t('OVERRIDE')"
                        @click="slugLocked = !slugLocked">
                        <font-awesome-icon fixed-width :icon="slugLocked ? 'lock' : 'lock-open'" />
                    </button>
                    
                    <!-- SmartLookup field -->
                    <CRUD6AutoLookup
                        v-if="field.type === 'smartlookup'"
                        v-bind="getLookupAttributes(field)"
                        v-model="formData[fieldKey]"
                    />
                    
                    <!-- Google Address field with autocomplete and geocoding -->
                    <GoogleAddress
                        v-else-if="isAddressType(field.type)"
                        :field-key="fieldKey"
                        :placeholder="field.placeholder || field.label || 'Enter address'"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        :address-fields="field.address_fields"
                        v-model="formData[fieldKey]"
                        @address-selected="handleAddressSelected"
                    />
                    
                    <!-- Text input (including email, url, phone, zip) -->
                    <input
                        v-else-if="['string', 'email', 'url', 'phone', 'zip'].includes(field.type) || !field.type"
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        :type="getInputType(field.type || 'string')"
                        :pattern="getInputPattern(field.type, field.validation)"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="fieldKey === 'slug' ? slugLocked : isFieldDisabled(field)"
                        :autocomplete="getAutocompleteAttribute(fieldKey, field.type)"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Number input -->
                    <input
                        v-else-if="['number', 'integer', 'decimal', 'float'].includes(field.type)"
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="number"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :step="field.type === 'integer' ? '1' : 'any'"
                        :disabled="isFieldDisabled(field)"
                        autocomplete="off"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Password input -->
                    <input
                        v-else-if="field.type === 'password'"
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="password"
                        autocomplete="new-password"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Date input -->
                    <input
                        v-else-if="field.type === 'date'"
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="date"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        :autocomplete="getAutocompleteAttribute(fieldKey, field.type)"
                        v-model="formData[fieldKey]" />
                    
                    <!-- DateTime input -->
                    <input
                        v-else-if="field.type === 'datetime'"
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="datetime-local"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        autocomplete="off"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Textarea for text fields (supports text, textarea, textarea-rXcY formats) -->
                    <textarea
                        v-else-if="field.type === 'text' || field.type === 'textarea' || field.type?.startsWith('textarea-') || field.type?.startsWith('text-')"
                        :id="getFieldId(fieldKey)"
                        class="uk-textarea"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :rows="parseTextareaConfig(field.type).rows"
                        :cols="parseTextareaConfig(field.type).cols"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Boolean fields - Toggle switch, Checkbox, or Yes/No select -->
                    <template v-else-if="isBooleanType(field.type)">
                        <!-- Yes/No Select Dropdown (boolean-yn) -->
                        <select
                            v-if="getBooleanUIType(field.type) === 'select'"
                            :id="getFieldId(fieldKey)"
                            class="uk-select"
                            :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                            :data-test="fieldKey"
                            :disabled="isFieldDisabled(field)"
                            :required="field.required"
                            v-model="formData[fieldKey]">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                        
                        <!-- Toggle Switch (boolean-tgl, boolean-toggle) -->
                        <CRUD6ToggleSwitch
                            v-else-if="getBooleanUIType(field.type) === 'toggle'"
                            :id="getFieldId(fieldKey)"
                            :data-test="fieldKey"
                            :disabled="isFieldDisabled(field)"
                            v-model="formData[fieldKey]" />
                        
                        <!-- Standard Checkbox (boolean) -->
                        <label v-else class="uk-form-label">
                            <input
                                :id="getFieldId(fieldKey)"
                                class="uk-checkbox"
                                type="checkbox"
                                :data-test="fieldKey"
                                :disabled="isFieldDisabled(field)"
                                v-model="formData[fieldKey]" />
                            {{ field.label || fieldKey }}
                        </label>
                    </template>
                    
                    <!-- Default text input for unknown types -->
                    <input
                        v-else
                        :id="getFieldId(fieldKey)"
                        class="uk-input"
                        :class="{ 'uk-form-danger': r$[fieldKey]?.$error }"
                        type="text"
                        :placeholder="field.placeholder || field.label || fieldKey"
                        :aria-label="field.label || fieldKey"
                        :data-test="fieldKey"
                        :required="field.required"
                        :disabled="isFieldDisabled(field)"
                        :autocomplete="getAutocompleteAttribute(fieldKey, field.type)"
                        v-model="formData[fieldKey]" />
                    
                    <!-- Validation errors -->
                    <UFFormValidationError :errors="(r$ && r$.$errors && r$.$errors[fieldKey]) || []" />
                </div>
            </div>
            </div>

            <!-- Form actions -->
            <div class="uk-text-right" uk-margin>
                <button class="uk-button uk-button-default uk-modal-close" type="button" data-test="btn-cancel">
                    {{ $t('CANCEL') }}
                </button>
                <button
                    class="uk-button uk-button-primary"
                    :disabled="(r$ && r$.$error) || isLoading"
                    type="submit"
                    data-test="btn-submit">
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
