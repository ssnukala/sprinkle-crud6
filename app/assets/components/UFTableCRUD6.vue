<template>
    <div class="table-container">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.key">
                        {{ column.label }}
                    </th>
                    <th v-if="tableOptions.rowActions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in data" :key="row.id" @click="handleRowClick(row)">
                    <td v-for="column in columns" :key="column.key">
                        {{ formatValue(row[column.key], column.type) }}
                    </td>
                    <td v-if="tableOptions.rowActions">
                        <div class="btn-group" role="group">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                @click.stop="editRow(row)"
                                title="Edit"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                @click.stop="deleteRow(row)"
                                title="Delete"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import type { CRUD6Interface } from '../interfaces'

interface Props {
    model: string
    schema?: any
    readonly?: boolean
}

interface Emits {
    (e: 'edit', row: CRUD6Interface): void
    (e: 'delete', row: CRUD6Interface): void
    (e: 'row-click', row: CRUD6Interface): void
}

const props = withDefaults(defineProps<Props>(), {
    readonly: false
})

const emit = defineEmits<Emits>()

const data = ref<CRUD6Interface[]>([])
const loading = ref(false)

const tableState = ref({
    currentPage: 1,
    pageSize: 10,
    sorts: {} as Record<string, string>,
    filters: {} as Record<string, any>
})

const apiUrl = computed(() => `/api/crud6/${props.model}`)

const columns = computed(() => {
    if (!props.schema?.fields) return []
    
    return Object.entries(props.schema.fields)
        .filter(([_, field]: [string, any]) => field.sortable || field.filterable || field.searchable)
        .map(([key, field]: [string, any]) => ({
            key,
            label: field.label || key,
            sortable: field.sortable || false,
            filterable: field.filterable || false,
            searchable: field.searchable || false,
            type: field.type || 'string',
            ...(field.readonly && { readonly: true })
        }))
})

const tableOptions = computed(() => ({
    pagination: true,
    sorting: true,
    filtering: true,
    searching: true,
    rowActions: !props.readonly,
    responsive: true
}))

function handleRowClick(row: CRUD6Interface) {
    emit('row-click', row)
}

function editRow(row: CRUD6Interface) {
    emit('edit', row)
}

function deleteRow(row: CRUD6Interface) {
    emit('delete', row)
}

function formatValue(value: any, type: string): string {
    if (value === null || value === undefined) return ''
    
    switch (type) {
        case 'boolean':
            return value ? 'Yes' : 'No'
        case 'date':
        case 'datetime':
            return new Date(value).toLocaleDateString()
        case 'json':
            return JSON.stringify(value)
        default:
            return String(value)
    }
}

// Load data when component mounts
onMounted(async () => {
    loading.value = true
    try {
        const response = await fetch(apiUrl.value)
        const result = await response.json()
        data.value = result.rows || []
    } catch (error) {
        console.error('Failed to load data:', error)
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.table-container {
    overflow-x: auto;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    border-top: none;
    font-weight: 600;
}

.table tbody tr:hover {
    cursor: pointer;
}

.btn-group .btn {
    margin-right: 0.25rem;
}
</style>