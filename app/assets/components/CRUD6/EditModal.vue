<script setup lang="ts">
import { computed } from 'vue'
import UIkit from 'uikit'
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
 * Methods - Submit the form to the API and handle the response.
 */
const formSuccess = () => {
    console.log('[EditModal] ✅ Form submitted successfully for record:', recordId.value)
    emits('saved')
    UIkit.modal('#modal-crud6-edit-' + recordId.value).hide()
}

// Debug logging for prop analysis  
console.log('[EditModal] 🚀 Component setup - recordId:', recordId.value, 'model:', props.model, 'hasSchema:', !!props.schema)
console.log('[EditModal] 📊 Schema details passed to Form - title:', props.schema?.title, 'fields:', Object.keys(props.schema?.fields || {}))
</script>

<template>
    <a :href="'#modal-crud6-edit-' + recordId" v-bind="$attrs" uk-toggle>
        <slot> <font-awesome-icon icon="pen-to-square" fixed-width /> {{ $t('CRUD6.EDIT', { model: modelLabel }) }} </slot>
    </a>

    <!-- This is the modal -->
    <UFModal :id="'modal-crud6-edit-' + recordId" closable>
        <template #header> {{ $t('CRUD6.EDIT', { model: modelLabel }) }} </template>
        <template #default>
            <CRUD6Form :crud6="props.crud6" :model="props.model" :schema="props.schema" @success="formSuccess()" />
        </template>
    </UFModal>
</template>
