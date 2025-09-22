<template>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-table mr-1"></i>
                {{ schema?.title || 'CRUD6' }}
            </h3>
            <div class="card-tools">
                <button
                    v-if="hasPermission('create')"
                    type="button"
                    class="btn btn-primary btn-sm"
                    @click="createRecord"
                >
                    <i class="fas fa-plus"></i>
                    Create New
                </button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="!modelName" class="alert alert-warning">
                <h4>Model Not Specified</h4>
                <p>No model name provided in the route. Please navigate to a specific CRUD6 model.</p>
            </div>
            <div v-else-if="loading" class="text-center">
                <i class="fas fa-spinner fa-spin"></i>
                Loading...
            </div>
            <div v-else-if="error" class="alert alert-danger">
                <h4>{{ error.title }}</h4>
                <p>{{ error.description }}</p>
            </div>
            <UFTableCRUD6
                v-else-if="schema"
                :model="modelName"
                :schema="schema"
                @edit="editRecord"
                @delete="deleteRecord"
                @row-click="viewRecord"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UFTableCRUD6 from './UFTableCRUD6.vue'
import { useCRUD6Schema } from '../composables/useCRUD6Schema'
import type { CRUD6Interface } from '../interfaces'

const route = useRoute()
const router = useRouter()

// Get model name from route parameter
const modelName = computed(() => route.params.model as string)

// Use schema composable
const { schema, loading, error, loadSchema, hasPermission } = useCRUD6Schema()

// Load schema when component mounts or model changes
onMounted(async () => {
    if (modelName.value) {
        await loadSchema(modelName.value)
    } else {
        console.error('CRUD6 model name not provided in route parameters')
    }
})

// Event handlers
function createRecord() {
    router.push(`/crud6/${modelName.value}/create`)
}

function editRecord(row: CRUD6Interface) {
    router.push(`/crud6/${modelName.value}/${row.id}/edit`)
}

function viewRecord(row: CRUD6Interface) {
    router.push(`/crud6/${modelName.value}/${row.id}`)
}

function deleteRecord(row: CRUD6Interface) {
    // Handle delete - could show confirmation modal
    console.log('Delete record:', row)
}
</script>

<style scoped>
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
}

.mr-1 {
    margin-right: 0.25rem;
}
</style>