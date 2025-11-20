<script setup lang="ts">
import { ref, computed } from 'vue'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { ActionConfig } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Generic Confirmation Modal for CRUD6 Custom Actions
 * 
 * This component provides a UIKit modal dialog for confirming custom actions
 * defined in CRUD6 schemas. It replaces the native browser confirm() dialog
 * with a better UX that supports HTML rendering and custom styling.
 * 
 * Based on sprinkle-admin's modal patterns.
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
    /** Emitted when user confirms the action */
    confirmed: []
    /** Emitted when user cancels the action */
    cancelled: []
}>()

/**
 * Computed - Modal ID for UIKit toggle
 */
const modalId = computed(() => {
    return `confirm-action-${props.action.key}`
})

/**
 * Computed - Translated confirmation message with HTML support
 */
const confirmMessage = computed(() => {
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
 * Methods - Handle confirmation
 */
function handleConfirmed() {
    emits('confirmed')
}

function handleCancelled() {
    emits('cancelled')
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

    <!-- Confirmation Modal -->
    <UFModalConfirmation
        :id="modalId"
        :title="actionLabel"
        @confirmed="handleConfirmed"
        @cancelled="handleCancelled"
        :acceptLabel="actionLabel"
        :acceptIcon="action.icon || null"
        :rejectIcon="null"
        :acceptSeverity="acceptSeverity"
        :data-test="`modal-confirm-${action.key}`">
        <template #prompt>
            <!-- Use v-html to render HTML in confirmation message -->
            <div v-html="confirmMessage"></div>
        </template>
    </UFModalConfirmation>
</template>
