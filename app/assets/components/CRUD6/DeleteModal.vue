<script setup lang="ts">
import { computed } from 'vue'
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'
import { debugLog, debugWarn, debugError } from '../../utils/debug'

/**
 * Variables and composables
 */
const { deleteRow } = useCRUD6Api()

/**
 * Props - The CRUD6 object to delete, optional model and schema for consistency
 */
const props = defineProps<{
    crud6: CRUD6Interface
    model?: string
    schema?: any
}>()

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

if (props.schema) {
}

/**
 * Emits - Define the deleted event. This event is emitted when the CRUD6 object is deleted
 * to notify the parent component to refresh the data.
 */
const emits = defineEmits(['deleted'])

/**
 * Methods - Submit the form to the API and handle the response.
 */
const deleteConfirmed = () => {
    deleteRow(recordId.value)
        .then(() => {
            emits('deleted')
        })
        .catch((error) => {
            debugError('[DeleteModal] ❌ Delete failed for:', recordId.value, 'error:', error)
        })
}
</script>

<template>
    <a :href="'#confirm-crud6-delete-' + recordId" v-bind="$attrs" uk-toggle data-test="btn-delete-modal">
        <slot><font-awesome-icon icon="trash" fixed-width /> {{ translate('CRUD6.DELETE', { model: modelLabel }, 'Delete') }}</slot>
    </a>

    <!-- This is the modal -->
    <UFModalConfirmation
        :id="'confirm-crud6-delete-' + recordId"
        :title="translate('CRUD6.DELETE', { model: modelLabel }, 'Delete')"
        @confirmed="deleteConfirmed()"
        :acceptLabel="translate('CRUD6.DELETE_YES', { model: modelLabel }, 'Yes, delete')"
        acceptIcon="trash"
        :rejectIcon="null"
        :acceptSeverity="Severity.Danger"
        data-test="modal-delete">
        <template #prompt>
            <div v-html="translate('CRUD6.DELETE_CONFIRM', { ...props.crud6, model: modelLabel }, 'Are you sure?')"></div>
        </template>
    </UFModalConfirmation>
</template> 
