<template>
    <div class="crud6-list-page">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    {{ schema?.title || `${model} Management` }}
                </h3>
                <div class="card-tools">
                    <button
                        v-if="hasCreatePermission"
                        type="button"
                        class="btn btn-primary"
                        @click="createNew"
                    >
                        <i class="fas fa-plus"></i>
                        {{ $t('CREATE_NEW') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p v-if="schema?.description" class="text-muted">
                    {{ schema.description }}
                </p>
                
                <!-- Loading state -->
                <div v-if="schemaLoading" class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                
                <!-- Error state -->
                <div v-else-if="schemaError" class="alert alert-danger">
                    <h5>{{ schemaError.title }}</h5>
                    <p>{{ schemaError.description }}</p>
                </div>
                
                <!-- Table -->
                <UFTableCRUD6
                    v-else-if="schema"
                    :model="model"
                    :schema="schema"
                    :readonly="!hasEditPermission"
                    @edit="editRecord"
                    @delete="deleteRecord"
                    @row-click="viewRecord"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '../composables/useCRUD6Schema'
import UFTableCRUD6 from '../components/UFTableCRUD6.vue'
import type { CRUD6Interface } from '../interfaces'

const route = useRoute()
const router = useRouter()

// Get model from route parameter
const model = computed(() => route.params.model as string)

// Use schema composable
const {
    schema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema,
    hasPermission
} = useCRUD6Schema()

// Permission checks
const hasCreatePermission = computed(() => hasPermission('create'))
const hasEditPermission = computed(() => hasPermission('update'))
const hasDeletePermission = computed(() => hasPermission('delete'))

// Actions
function createNew() {
    router.push(`/crud6/${model.value}/create`)
}

function editRecord(record: CRUD6Interface) {
    const id = record[schema.value?.primary_key || 'id']
    router.push(`/crud6/${model.value}/${id}/edit`)
}

function viewRecord(record: CRUD6Interface) {
    const id = record[schema.value?.primary_key || 'id']
    router.push(`/crud6/${model.value}/${id}`)
}

function deleteRecord(record: CRUD6Interface) {
    // TODO: Implement delete confirmation modal
    console.log('Delete record:', record)
}

// Load schema when component mounts or model changes
onMounted(() => {
    if (model.value) {
        loadSchema(model.value)
    }
})
</script>

<style scoped>
.crud6-list-page {
    padding: 1rem;
}

.card-tools .btn {
    margin-left: 0.5rem;
}
</style>
