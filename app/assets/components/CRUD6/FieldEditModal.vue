<script setup lang="ts">
import { ref, computed } from 'vue'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { ActionConfig } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Generic Field Edit Modal for CRUD6 Custom Actions
 * 
 * This component provides a UIKit modal dialog for field update actions
 * that require user input. The edit form automatically adapts based on:
 * - Field type (password, text, number, email, date, etc.) - determines input type and masking
 * - Validation rules (match, length, etc.) - determines if confirmation field is shown
 * 
 * Replaces the need for separate field-type-specific modals (e.g., PasswordInputModal).
 */

const translator = useTranslator()

/**
 * Props
 */
const props = defineProps<{
    /** The action configuration from schema */
    action: ActionConfig
    /** The record data for variable interpolation in messages */
    record?: any
    /** The field configuration from schema */
    fieldConfig?: any
    /** Model name for display */
    model?: string
}>()

/**
 * Emits
 */
const emits = defineEmits<{
    /** Emitted when user confirms with field data */
    confirmed: [{ [key: string]: any }]
    /** Emitted when user cancels the action */
    cancelled: []
}>()

/**
 * State
 */
const fieldValue = ref('')
const confirmValue = ref('')
const error = ref('')

/**
 * Computed - Modal ID for UIKit toggle
 */
const modalId = computed(() => {
    return `field-input-${props.action.key}`
})

/**
 * Computed - Translated prompt message with HTML support
 */
const promptMessage = computed(() => {
    if (!props.action.confirm) return ''
    return translator.translate(props.action.confirm, props.record)
})

/**
 * Computed - Action label (button text)
 */
const actionLabel = computed(() => {
    return translator.translate(props.action.label)
})

/**
 * Computed - Field label
 */
const fieldLabel = computed(() => {
    if (props.fieldConfig?.label) {
        return translator.translate(props.fieldConfig.label)
    }
    return props.action.field || 'Value'
})

/**
 * Computed - Input type based on field type
 */
const inputType = computed(() => {
    const fieldType = props.fieldConfig?.type || 'text'
    // Map CRUD6 field types to HTML input types
    switch (fieldType) {
        case 'password':
            return 'password'
        case 'integer':
        case 'number':
            return 'number'
        case 'email':
            return 'email'
        case 'date':
            return 'date'
        case 'datetime':
            return 'datetime-local'
        default:
            return 'text'
    }
})

/**
 * Computed - Should show matching confirmation field
 */
const requiresMatch = computed(() => {
    return props.fieldConfig?.validation?.match === true
})

/**
 * Computed - Minimum length validation
 */
const minLength = computed(() => {
    return props.fieldConfig?.validation?.length?.min || 0
})

/**
 * Computed - Accept button severity based on action style
 */
const acceptSeverity = computed(() => {
    switch (props.action.style) {
        case 'danger':
            return Severity.Danger
        case 'warning':
            return Severity.Warning
        case 'primary':
            return Severity.Success
        default:
            return Severity.Info
    }
})

/**
 * Computed - Field validation
 */
const isFieldValid = computed(() => {
    if (!fieldValue.value) {
        return false
    }
    if (minLength.value && fieldValue.value.length < minLength.value) {
        return false
    }
    if (requiresMatch.value && fieldValue.value !== confirmValue.value) {
        return false
    }
    return true
})

/**
 * Methods - Handle confirmation
 */
function handleConfirmed() {
    // Validate fields match if required
    if (requiresMatch.value && fieldValue.value !== confirmValue.value) {
        error.value = translator.translate('VALIDATION.FIELDS_MUST_MATCH') || 'Fields must match'
        return
    }
    
    // Validate minimum length
    if (minLength.value && fieldValue.value.length < minLength.value) {
        error.value = translator.translate('VALIDATION.MIN_LENGTH', { min: minLength.value }) || `Minimum ${minLength.value} characters required`
        return
    }
    
    error.value = ''
    
    // Emit with the field name as key
    const fieldName = props.action.field || 'value'
    // For password fields, use 'password' key for backward compatibility
    const dataKey = props.fieldConfig?.type === 'password' ? 'password' : fieldName
    
    emits('confirmed', { [dataKey]: fieldValue.value })
    
    // Reset form
    fieldValue.value = ''
    confirmValue.value = ''
}

function handleCancelled() {
    error.value = ''
    fieldValue.value = ''
    confirmValue.value = ''
    emits('cancelled')
}

/**
 * Watch for modal close to reset form
 */
function resetForm() {
    fieldValue.value = ''
    confirmValue.value = ''
    error.value = ''
}
</script>

<template>
    <!-- Trigger button with slot for custom content -->
    <slot name="trigger" :modal-id="modalId">
        <button
            :href="`#${modalId}`"
            uk-toggle
            :data-test="`btn-action-${action.key}`"
            :class="[
                'uk-width-1-1',
                'uk-margin-small-bottom',
                'uk-button',
                'uk-button-small',
                action.style ? `uk-button-${action.style}` : 'uk-button-default'
            ]">
            <font-awesome-icon v-if="action.icon" :icon="action.icon" fixed-width />
            {{ actionLabel }}
        </button>
    </slot>

    <!-- Field Input Modal -->
    <div :id="modalId" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close @click="resetForm"></button>
            
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">{{ actionLabel }}</h2>
            </div>
            
            <div class="uk-modal-body">
                <!-- Prompt message with HTML support -->
                <div v-if="promptMessage" v-html="promptMessage" class="uk-margin-bottom"></div>
                
                <!-- Field input -->
                <div class="uk-margin">
                    <label class="uk-form-label" :for="`field-input-${action.key}`">
                        {{ fieldLabel }}
                    </label>
                    <div class="uk-form-controls">
                        <input
                            :id="`field-input-${action.key}`"
                            v-model="fieldValue"
                            :type="inputType"
                            class="uk-input"
                            :placeholder="$t('VALIDATION.ENTER_VALUE') || `Enter ${fieldLabel.toLowerCase()}`"
                            :autocomplete="inputType === 'password' ? 'new-password' : 'off'"
                            required
                            :minlength="minLength || undefined" />
                    </div>
                </div>
                
                <!-- Confirm field (shown only when validation.match is true) -->
                <div v-if="requiresMatch" class="uk-margin">
                    <label class="uk-form-label" :for="`confirm-field-input-${action.key}`">
                        {{ $t('VALIDATION.CONFIRM') || 'Confirm' }} {{ fieldLabel }}
                    </label>
                    <div class="uk-form-controls">
                        <input
                            :id="`confirm-field-input-${action.key}`"
                            v-model="confirmValue"
                            :type="inputType"
                            class="uk-input"
                            :placeholder="$t('VALIDATION.CONFIRM_PLACEHOLDER') || `Confirm ${fieldLabel.toLowerCase()}`"
                            :autocomplete="inputType === 'password' ? 'new-password' : 'off'"
                            required
                            :minlength="minLength || undefined" />
                    </div>
                </div>
                
                <!-- Error message -->
                <div v-if="error" class="uk-alert-danger" uk-alert>
                    <p>{{ error }}</p>
                </div>
                
                <!-- Validation hints -->
                <div v-if="minLength || requiresMatch" class="uk-text-small uk-text-muted">
                    <ul class="uk-list">
                        <li v-if="minLength" :class="{ 'uk-text-success': fieldValue.length >= minLength }">
                            {{ $t('VALIDATION.MIN_LENGTH_HINT', { min: minLength }) || `Minimum ${minLength} characters` }}
                        </li>
                        <li v-if="requiresMatch" :class="{ 'uk-text-success': fieldValue && confirmValue && fieldValue === confirmValue }">
                            {{ $t('VALIDATION.MATCH_HINT') || 'Values must match' }}
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="uk-modal-footer uk-text-right">
                <button
                    class="uk-button uk-button-default uk-modal-close"
                    type="button"
                    @click="handleCancelled">
                    {{ $t('CANCEL') || 'Cancel' }}
                </button>
                <button
                    class="uk-button"
                    :class="{
                        'uk-button-danger': acceptSeverity === Severity.Danger,
                        'uk-button-warning': acceptSeverity === Severity.Warning,
                        'uk-button-primary': acceptSeverity === Severity.Success,
                        'uk-button-secondary': acceptSeverity === Severity.Info
                    }"
                    type="button"
                    :disabled="!isFieldValid"
                    @click="handleConfirmed"
                    :data-test="`btn-confirm-${action.key}`">
                    <font-awesome-icon v-if="action.icon" :icon="action.icon" fixed-width />
                    {{ actionLabel }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.uk-list li {
    margin-bottom: 0.25rem;
}

.uk-list li.uk-text-success::before {
    content: '✓ ';
    color: #32d296;
}
</style>
