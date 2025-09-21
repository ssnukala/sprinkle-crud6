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
                        {{ $t('CRUD6.CREATE') }}
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
                        <span class="sr-only">{{ $t('LOADING') }}</span>
                    </div>
                </div>
                
                <!-- Error state -->
                <div v-else-if="schemaError" class="alert alert-danger">
                    <h5>{{ schemaError.title }}</h5>
                    <p>{{ schemaError.description }}</p>
                </div>
                
                <!-- Table -->
                <UFTable
                    v-else-if="schema"
                    :api-url="`/api/crud6/${model}`"
                    :table-state="tableState"
                    :columns="tableColumns"
                    :table-options="tableOptions"
                    @row-click="viewRecord"
                    @edit="editRecord"
                    @delete="deleteRecord"
                />

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '../composables/useCRUD6Schema'
import { UFTable } from '@userfrosting/sprinkle-admin'
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
    hasPermission,
    tableColumns,
    defaultSort
} = useCRUD6Schema()

// UFTable state
const tableState = ref({
    currentPage: 1,
    pageSize: 10,
    sorts: defaultSort.value || {},
    filters: {}
})

// UFTable options
const tableOptions = computed(() => ({
    pagination: true,
    sorting: true,
    filtering: true,
    searching: true,
    rowActions: hasEditPermission.value,
    responsive: true
}))

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

