<script setup lang="ts">
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
 * Methods - Submit the form to the API and handle the response.
 */
const formSuccess = () => {
    console.log('[EditModal] ✅ Form submitted successfully for crud6.id:', props.crud6.id)
    emits('saved')
    UIkit.modal('#modal-crud6-edit-' + props.crud6.id).hide()
}

// Debug logging for prop analysis  
console.log('[EditModal] 🚀 Component setup - crud6.id:', props.crud6?.id, 'model:', props.model, 'hasSchema:', !!props.schema)
console.log('[EditModal] 📊 Schema details passed to Form - title:', props.schema?.title, 'fields:', Object.keys(props.schema?.fields || {}))
</script>

<template>
    <a :href="'#modal-crud6-edit-' + props.crud6.id" v-bind="$attrs" uk-toggle>
        <slot> <font-awesome-icon icon="pen-to-square" fixed-width /> {{ $t('CRUD6.EDIT') }} </slot>
    </a>

    <!-- This is the modal -->
    <UFModal :id="'modal-crud6-edit-' + props.crud6.id" closable>
        <template #header> {{ $t('CRUD6.EDIT') }} </template>
        <template #default>
            <CRUD6Form :crud6="props.crud6" :model="props.model" :schema="props.schema" @success="formSuccess()" />
        </template>
    </UFModal>
</template>
