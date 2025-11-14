<script setup lang="ts">
import { computed } from 'vue'
import UIkit from 'uikit'
import CRUD6Form from './Form.vue'

/**
 * Props - Model for schema loading and optional schema to avoid duplicate loads.
 */
const props = defineProps<{
    model?: string
    schema?: any
}>()

if (props.schema) {
} else {
}

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
 * Emits - Define the saved event. This event is emitted when the form is saved
 * to notify the parent component to refresh the data.
 */
const emits = defineEmits(['saved'])

/**
 * Methods - Submit the form to the API and handle the response.
 */
const formSuccess = () => {
    emits('saved')
    UIkit.modal('#modal-crud6-create').hide()
}

</script>

<template>
    <a v-bind="$attrs" :uk-toggle="'target: #modal-crud6-create'" data-test="btn-create-modal">
        <slot><font-awesome-icon icon="plus" fixed-width /> {{ $t('CRUD6.CREATE', { model: modelLabel }) }}</slot>
    </a>

    <!-- This is the modal -->
    <UFModal id="modal-crud6-create" closable data-test="modal-create">
        <template #header>{{ $t('CRUD6.CREATE', { model: modelLabel }) }}</template>
        <template #default>
            <CRUD6Form :model="props.model" :schema="props.schema" @success="formSuccess()" />
        </template>
    </UFModal>
</template>
