<script setup lang="ts">
import { computed } from 'vue'
import UIkit from 'uikit'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import CRUD6Form from './Form.vue'

/**
 * Props - The CRUD6 object to edit, model for schema loading, and optional schema to avoid duplicate loads.
 */
const props = defineProps<{
    crud6: CRUD6Interface
    model?: string
    schema?: any
}>()

const translator = useTranslator()

/**
 * Emits - Define the saved event. This event is emitted when the form is saved
 * to notify the parent component to refresh the data.
 */
const emits = defineEmits(['saved'])

/**
 * Computed - Get the record ID using the schema's primary key
 */
const recordId = computed(() => {
    const primaryKey = props.schema?.primary_key || 'id'
    return props.crud6[primaryKey]
})

/**
 * Computed - Get the model label for button text
 * Priority: singular_title > model name (capitalized)
 */
const modelLabel = computed(() => {
    if (props.schema?.singular_title) {
        return props.schema.singular_title
    }
    // Capitalize first letter of model name as fallback
    return props.model ? props.model.charAt(0).toUpperCase() + props.model.slice(1) : 'Record'
})

/**
 * Translate helper for template use
 */
function t(key: string, params?: Record<string, any>, fallback?: string): string {
    const translated = translator.translate(key, params)
    return (translated === key && fallback) ? fallback : translated
}

/**
 * Methods - Submit the form to the API and handle the response.
 */
const formSuccess = () => {
    emits('saved')
    UIkit.modal('#modal-crud6-edit-' + recordId.value).hide()
}

// Debug logging for prop analysis  
</script>

<template>
    <a :href="'#modal-crud6-edit-' + recordId" v-bind="$attrs" uk-toggle data-test="btn-edit-modal">
        <slot> <font-awesome-icon icon="pen-to-square" fixed-width /> {{ t('CRUD6.EDIT', { model: modelLabel }) }} </slot>
    </a>

    <!-- This is the modal -->
    <UFModal :id="'modal-crud6-edit-' + recordId" closable data-test="modal-edit">
        <template #header> {{ t('CRUD6.EDIT', { model: modelLabel }) }} </template>
        <template #default>
            <CRUD6Form :crud6="props.crud6" :model="props.model" :schema="props.schema" @success="formSuccess()" />
        </template>
    </UFModal>
</template>
