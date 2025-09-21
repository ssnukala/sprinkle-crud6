<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>{{ modelName.charAt(0).toUpperCase() + modelName.slice(1) }} List</h3>
                        <button @click="createNew" class="btn btn-primary">
                            Create New
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="loading" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else-if="error" class="alert alert-danger">
                            {{ error.description || 'Error loading data' }}
                        </div>
                        <div v-else-if="crud6Rows.length > 0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th v-for="column in columns" :key="column.key">
                                                {{ column.label }}
                                            </th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in crud6Rows" :key="item.id || item.slug" 
                                            @click="goToDetail(item)" 
                                            style="cursor: pointer;">
                                            <td v-for="column in columns" :key="column.key">
                                                {{ formatValue(item[column.key]) }}
                                            </td>
                                            <td>
                                                <button @click.stop="goToDetail(item)" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="text-center">
                            <p>No {{ modelName }} records found.</p>
                            <button @click="createNew" class="btn btn-primary">
                                Create First {{ modelName.charAt(0).toUpperCase() + modelName.slice(1) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6sApi } from '../composables'

const route = useRoute()
const router = useRouter()
const { crud6Rows, error, loading, updateCRUD6s } = useCRUD6sApi()

const modelName = computed(() => route.params.model as string)

// Default columns - this should ideally be loaded from schema
const columns = ref([
    { key: 'id', label: 'ID' },
    { key: 'name', label: 'Name' },
    { key: 'created_at', label: 'Created' },
    { key: 'updated_at', label: 'Updated' }
])

function formatValue(value: any): string {
    if (value === null || value === undefined) return 'N/A'
    if (typeof value === 'object') return JSON.stringify(value)
    if (typeof value === 'string' && value.length > 50) {
        return value.substring(0, 50) + '...'
    }
    return String(value)
}

function goToDetail(item: any) {
    router.push({
        name: 'admin.crud6',
        params: { model: modelName.value, id: item.id || item.slug }
    })
}

function createNew() {
    // Navigate to create page - this could be implemented later
    console.log('Create new', modelName.value)
}

onMounted(() => {
    updateCRUD6s()
})
</script>
