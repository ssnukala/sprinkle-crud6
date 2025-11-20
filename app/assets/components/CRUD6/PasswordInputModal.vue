<script setup lang="ts">
import { ref, computed } from 'vue'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { ActionConfig } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Password Input Modal for CRUD6 Custom Actions
 * 
 * This component provides a UIKit modal dialog for password input actions
 * (like reset password or change password). It shows password and confirm password
 * fields with validation to ensure they match.
 * 
 * Configurable via schema with `requires_password_input: true` or for any
 * action with type 'password_update'.
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
    /** Model name for display */
    model?: string
}>()

/**
 * Emits
 */
const emits = defineEmits<{
    /** Emitted when user confirms with password data */
    confirmed: [{ password: string }]
    /** Emitted when user cancels the action */
    cancelled: []
}>()

/**
 * State
 */
const password = ref('')
const confirmPassword = ref('')
const error = ref('')

/**
 * Computed - Modal ID for UIKit toggle
 */
const modalId = computed(() => {
    return `password-input-${props.action.key}`
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
 * Computed - Password validation
 */
const isPasswordValid = computed(() => {
    if (!password.value) {
        return false
    }
    if (password.value.length < 8) {
        return false
    }
    if (password.value !== confirmPassword.value) {
        return false
    }
    return true
})

/**
 * Methods - Handle confirmation
 */
function handleConfirmed() {
    // Validate passwords match
    if (password.value !== confirmPassword.value) {
        error.value = translator.translate('PASSWORD.PASSWORDS_MUST_MATCH') || 'Passwords must match'
        return
    }
    
    // Validate password length
    if (password.value.length < 8) {
        error.value = translator.translate('PASSWORD.MIN_LENGTH', { min: 8 }) || 'Password must be at least 8 characters'
        return
    }
    
    error.value = ''
    emits('confirmed', { password: password.value })
    
    // Reset form
    password.value = ''
    confirmPassword.value = ''
}

function handleCancelled() {
    error.value = ''
    password.value = ''
    confirmPassword.value = ''
    emits('cancelled')
}

/**
 * Watch for modal close to reset form
 */
function resetForm() {
    password.value = ''
    confirmPassword.value = ''
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

    <!-- Password Input Modal -->
    <div :id="modalId" uk-modal>
        <div class="uk-modal-dialog">
            <button class="uk-modal-close-default" type="button" uk-close @click="resetForm"></button>
            
            <div class="uk-modal-header">
                <h2 class="uk-modal-title">{{ actionLabel }}</h2>
            </div>
            
            <div class="uk-modal-body">
                <!-- Prompt message with HTML support -->
                <div v-if="promptMessage" v-html="promptMessage" class="uk-margin-bottom"></div>
                
                <!-- Password input -->
                <div class="uk-margin">
                    <label class="uk-form-label" for="password-input">
                        {{ $t('PASSWORD.NEW') || 'New Password' }}
                    </label>
                    <div class="uk-form-controls">
                        <input
                            id="password-input"
                            v-model="password"
                            type="password"
                            class="uk-input"
                            :placeholder="$t('PASSWORD.ENTER_NEW') || 'Enter new password'"
                            autocomplete="new-password"
                            required
                            minlength="8" />
                    </div>
                </div>
                
                <!-- Confirm password input -->
                <div class="uk-margin">
                    <label class="uk-form-label" for="confirm-password-input">
                        {{ $t('PASSWORD.CONFIRM') || 'Confirm Password' }}
                    </label>
                    <div class="uk-form-controls">
                        <input
                            id="confirm-password-input"
                            v-model="confirmPassword"
                            type="password"
                            class="uk-input"
                            :placeholder="$t('PASSWORD.CONFIRM_PLACEHOLDER') || 'Confirm new password'"
                            autocomplete="new-password"
                            required
                            minlength="8" />
                    </div>
                </div>
                
                <!-- Error message -->
                <div v-if="error" class="uk-alert-danger" uk-alert>
                    <p>{{ error }}</p>
                </div>
                
                <!-- Password validation hints -->
                <div class="uk-text-small uk-text-muted">
                    <ul class="uk-list">
                        <li :class="{ 'uk-text-success': password.length >= 8 }">
                            {{ $t('PASSWORD.MIN_LENGTH_HINT', { min: 8 }) || 'Minimum 8 characters' }}
                        </li>
                        <li :class="{ 'uk-text-success': password && confirmPassword && password === confirmPassword }">
                            {{ $t('PASSWORD.MATCH_HINT') || 'Passwords must match' }}
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
                    :disabled="!isPasswordValid"
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
