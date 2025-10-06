<script setup lang="ts">
import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'

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

console.log('[DeleteModal] 🚀 Component setup - crud6.id:', props.crud6?.id, 'model:', props.model, 'hasSchema:', !!props.schema)
if (props.schema) {
    console.log('[DeleteModal] 📊 Schema provided for context - title:', props.schema?.title)
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
    console.log('[DeleteModal] 🗑️  Delete confirmed for crud6.slug:', props.crud6.slug)
    deleteRow(props.crud6.slug)
        .then(() => {
            console.log('[DeleteModal] ✅ Delete successful for:', props.crud6.slug)
            emits('deleted')
        })
        .catch((error) => {
            console.error('[DeleteModal] ❌ Delete failed for:', props.crud6.slug, 'error:', error)
        })
}
</script>

<template>
    <a :href="'#confirm-crud6-delete-' + props.crud6.id" v-bind="$attrs" uk-toggle>
        <slot><font-awesome-icon icon="trash" fixed-width /> {{ $t('CRUD6.DELETE') }}</slot>
    </a>

    <!-- This is the modal -->
    <UFModalConfirmation
        :id="'confirm-crud6-delete-' + props.crud6.id"
        title="CRUD6.DELETE"
        @confirmed="deleteConfirmed()"
        acceptLabel="CRUD6.DELETE_YES"
        acceptIcon="trash"
        :rejectIcon="null"
        :acceptSeverity="Severity.Danger">
        <template #prompt>
            <div v-html="$t('CRUD6.DELETE_CONFIRM', props.crud6)"></div>
        </template>
    </UFModalConfirmation>
</template> 
