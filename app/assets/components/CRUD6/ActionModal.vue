<script setup lang="ts">
import { ref, computed } from 'vue'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { ActionConfig, ModalButtonConfig, ModalConfig, SchemaField } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Unified Action Modal for CRUD6
 * 
 * This component provides a schema-driven modal that can render:
 * - Confirmation dialogs (message only with confirm/cancel)
 * - Input forms (single or multiple fields with validation)
 * - Full CRUD forms (using CRUD6Form component)
 * 
 * Button combinations are configurable via schema:
 * - Presets: 'yes_no', 'save_cancel', 'ok_cancel', 'confirm_cancel'
 * - Custom: Array of ModalButtonConfig objects
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
    /** Schema fields configuration for input rendering */
    schemaFields?: Record<string, SchemaField>
    /** Model name for display */
    model?: string
}>()

/**
 * Emits
 */
const emits = defineEmits<{
    /** Emitted when user confirms the action */
    confirmed: [data?: Record<string, any>]
    /** Emitted when user cancels the action */
    cancelled: []
}>()

/**
 * State for input fields
 */
const fieldValues = ref<Record<string, any>>({})
const confirmValues = ref<Record<string, any>>({})
const error = ref('')

/**
 * Computed - Modal ID for UIKit toggle
 */
const modalId = computed(() => {
    return `action-modal-${props.action.key}`
})

/**
 * Computed - Modal configuration with defaults
 */
const modalConfig = computed((): ModalConfig => {
    const config = props.action.modal_config || {}
    return {
        type: config.type || (props.action.confirm ? 'confirm' : 'input'),
        title: config.title || props.action.label,
        fields: config.fields || (props.action.field ? [props.action.field] : []),
        buttons: config.buttons || 'confirm_cancel'
    }
})

/**
 * Computed - Translated confirmation/prompt message with HTML support
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
 * Computed - Modal title
 */
const modalTitle = computed(() => {
    if (modalConfig.value.title) {
        return translator.translate(modalConfig.value.title, props.record)
    }
    return actionLabel.value
})

/**
 * Computed - Fields to render in modal
 */
const fieldsToRender = computed(() => {
    if (!modalConfig.value.fields || !props.schemaFields) return []
    return modalConfig.value.fields
        .map(fieldKey => ({
            key: fieldKey,
            config: props.schemaFields?.[fieldKey]
        }))
        .filter(f => f.config)
})

/**
 * Computed - Resolve button configuration from preset or custom
 */
const resolvedButtons = computed((): ModalButtonConfig[] => {
    const buttons = modalConfig.value.buttons
    
    // If already an array of custom buttons, use as-is
    if (Array.isArray(buttons)) {
        return buttons
    }
    
    // Resolve preset button combinations
    switch (buttons) {
        case 'yes_no':
            return [
                { label: 'NO', icon: 'xmark', style: 'default', action: 'cancel', closeModal: true },
                { label: 'YES', icon: 'check', style: 'primary', action: 'confirm', closeModal: true }
            ]
        case 'save_cancel':
            return [
                { label: 'CANCEL', icon: 'xmark', style: 'default', action: 'cancel', closeModal: true },
                { label: 'SAVE', icon: 'floppy-disk', style: 'primary', action: 'submit', closeModal: true }
            ]
        case 'ok_cancel':
            return [
                { label: 'CANCEL', icon: 'xmark', style: 'default', action: 'cancel', closeModal: true },
                { label: 'OK', icon: 'check', style: 'primary', action: 'confirm', closeModal: true }
            ]
        case 'confirm_cancel':
        default:
            return [
                { label: 'CANCEL', style: 'default', action: 'cancel', closeModal: true },
                { 
                    label: props.action.label || 'CONFIRM', 
                    icon: props.action.icon, 
                    style: (props.action.style as any) || 'primary', 
                    action: 'confirm', 
                    closeModal: true 
                }
            ]
    }
})

/**
 * Computed - Accept button severity based on action style
 */
const getButtonSeverity = (style?: string) => {
    switch (style) {
        case 'danger':
            return Severity.Danger
        case 'warning':
            return Severity.Warning
        case 'primary':
            return Severity.Success
        default:
            return Severity.Info
    }
}

/**
 * Computed - Get button class based on style
 */
const getButtonClass = (style?: string) => {
    switch (style) {
        case 'danger':
            return 'uk-button-danger'
        case 'warning':
            return 'uk-button-warning'
        case 'primary':
            return 'uk-button-primary'
        case 'secondary':
            return 'uk-button-secondary'
        default:
            return 'uk-button-default'
    }
}

/**
 * Get input type for a field
 */
const getInputType = (fieldConfig?: SchemaField) => {
    if (!fieldConfig) return 'text'
    switch (fieldConfig.type) {
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
}

/**
 * Check if field requires match validation
 */
const requiresMatch = (fieldConfig?: SchemaField) => {
    return fieldConfig?.validation?.match === true
}

/**
 * Get minimum length for field
 */
const getMinLength = (fieldConfig?: SchemaField) => {
    return fieldConfig?.validation?.length?.min || 0
}

/**
 * Get field label
 */
const getFieldLabel = (fieldKey: string, fieldConfig?: SchemaField) => {
    if (fieldConfig?.label) {
        return translator.translate(fieldConfig.label)
    }
    return fieldKey
}

/**
 * Validate all fields
 */
const isFormValid = computed(() => {
    for (const field of fieldsToRender.value) {
        const value = fieldValues.value[field.key]
        const config = field.config
        
        // Check required
        if (!value) return false
        
        // Check min length
        const minLen = getMinLength(config)
        if (minLen && String(value).length < minLen) return false
        
        // Check match
        if (requiresMatch(config) && value !== confirmValues.value[field.key]) return false
    }
    return true
})

/**
 * Handle button click
 */
function handleButtonClick(button: ModalButtonConfig) {
    switch (button.action) {
        case 'confirm':
        case 'submit':
            handleConfirmed()
            break
        case 'cancel':
        case 'close':
            handleCancelled()
            break
    }
}

/**
 * Handle confirmation
 */
function handleConfirmed() {
    // Validate fields if any
    for (const field of fieldsToRender.value) {
        const value = fieldValues.value[field.key]
        const config = field.config
        
        // Check match validation
        if (requiresMatch(config) && value !== confirmValues.value[field.key]) {
            error.value = translator.translate('VALIDATION.FIELDS_MUST_MATCH') || 'Fields must match'
            return
        }
        
        // Check min length
        const minLen = getMinLength(config)
        if (minLen && String(value || '').length < minLen) {
            error.value = translator.translate('VALIDATION.MIN_LENGTH', { min: minLen }) || `Minimum ${minLen} characters required`
            return
        }
    }
    
    error.value = ''
    
    // Emit with field data if any fields exist
    if (fieldsToRender.value.length > 0) {
        // Build data object, mapping password fields to 'password' key for backward compatibility
        const data: Record<string, any> = {}
        for (const field of fieldsToRender.value) {
            const dataKey = field.config?.type === 'password' ? 'password' : field.key
            data[dataKey] = fieldValues.value[field.key]
        }
        emits('confirmed', data)
    } else {
        emits('confirmed')
    }
    
    // Reset form
    resetForm()
}

/**
 * Handle cancellation
 */
function handleCancelled() {
    error.value = ''
    resetForm()
    emits('cancelled')
}

/**
 * Reset form state
 */
function resetForm() {
    fieldValues.value = {}
    confirmValues.value = {}
    error.value = ''
}
</script>

<template>
    <!-- Trigger link styled as button for UIKit modal toggle -->
    <slot name="trigger" :modal-id="modalId">
        <a
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
        </a>
    </slot>

    <!-- Unified Modal -->
    <div :id="modalId" uk-modal :data-test="`modal-action-${action.key}`">
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close @click="resetForm"></button>
            
            <!-- Modal Header -->
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">{{ modalTitle }}</h2>
            </div>
            
            <!-- Modal Body -->
            <div class="uk-modal-body">
                <!-- Confirmation message (for confirm type or when confirm prop exists) -->
                <div v-if="promptMessage" class="uk-text-center uk-margin-bottom">
                    <p>
                        <font-awesome-icon 
                            icon="triangle-exclamation" 
                            class="uk-text-warning fa-4x" />
                    </p>
                    <div v-html="promptMessage"></div>
                    <div v-if="modalConfig.type === 'confirm'" class="uk-text-meta">
                        {{ $t('ACTION.CANNOT_UNDO') || 'This action cannot be undone.' }}
                    </div>
                </div>
                
                <!-- Input fields (for input type) -->
                <template v-if="fieldsToRender.length > 0">
                    <div v-for="field in fieldsToRender" :key="field.key" class="uk-margin">
                        <label class="uk-form-label" :for="`field-${action.key}-${field.key}`">
                            {{ getFieldLabel(field.key, field.config) }}
                        </label>
                        <div class="uk-form-controls">
                            <input
                                :id="`field-${action.key}-${field.key}`"
                                v-model="fieldValues[field.key]"
                                :type="getInputType(field.config)"
                                class="uk-input"
                                :placeholder="$t('VALIDATION.ENTER_VALUE') || `Enter ${getFieldLabel(field.key, field.config).toLowerCase()}`"
                                :autocomplete="getInputType(field.config) === 'password' ? 'new-password' : 'off'"
                                required
                                :minlength="getMinLength(field.config) || undefined" />
                        </div>
                        
                        <!-- Confirm field for match validation -->
                        <div v-if="requiresMatch(field.config)" class="uk-margin-small-top">
                            <label class="uk-form-label" :for="`confirm-${action.key}-${field.key}`">
                                {{ $t('VALIDATION.CONFIRM') || 'Confirm' }} {{ getFieldLabel(field.key, field.config) }}
                            </label>
                            <div class="uk-form-controls">
                                <input
                                    :id="`confirm-${action.key}-${field.key}`"
                                    v-model="confirmValues[field.key]"
                                    :type="getInputType(field.config)"
                                    class="uk-input"
                                    :placeholder="$t('VALIDATION.CONFIRM_PLACEHOLDER') || `Confirm ${getFieldLabel(field.key, field.config).toLowerCase()}`"
                                    :autocomplete="getInputType(field.config) === 'password' ? 'new-password' : 'off'"
                                    required />
                            </div>
                        </div>
                    </div>
                </template>
                
                <!-- Error message -->
                <div v-if="error" class="uk-alert-danger" uk-alert>
                    <p>{{ error }}</p>
                </div>
                
                <!-- Validation hints -->
                <div v-if="fieldsToRender.some(f => getMinLength(f.config) || requiresMatch(f.config))" 
                     class="uk-text-small uk-text-muted">
                    <ul class="uk-list">
                        <template v-for="field in fieldsToRender" :key="`hint-${field.key}`">
                            <li v-if="getMinLength(field.config)" 
                                :class="{ 'uk-text-success': (fieldValues[field.key] || '').length >= getMinLength(field.config) }">
                                {{ $t('VALIDATION.MIN_LENGTH_HINT', { min: getMinLength(field.config) }) || `Minimum ${getMinLength(field.config)} characters` }}
                            </li>
                            <li v-if="requiresMatch(field.config)" 
                                :class="{ 'uk-text-success': fieldValues[field.key] && confirmValues[field.key] && fieldValues[field.key] === confirmValues[field.key] }">
                                {{ $t('VALIDATION.MATCH_HINT') || 'Values must match' }}
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
            
            <!-- Modal Footer with schema-driven buttons -->
            <div class="uk-modal-footer uk-text-right">
                <button
                    v-for="(button, index) in resolvedButtons"
                    :key="index"
                    class="uk-button uk-margin-small-left"
                    :class="[
                        getButtonClass(button.style),
                        { 'uk-modal-close': button.closeModal }
                    ]"
                    type="button"
                    :disabled="(button.action === 'confirm' || button.action === 'submit') && fieldsToRender.length > 0 && !isFormValid"
                    @click="handleButtonClick(button)"
                    :data-test="`btn-${button.action}-${action.key}`">
                    <font-awesome-icon v-if="button.icon" :icon="button.icon" fixed-width />
                    {{ translator.translate(button.label) }}
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
