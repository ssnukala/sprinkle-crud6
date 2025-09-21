<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>{{ $t('CRUD6.DETAIL', { model: modelName }) }}</h3>
                        <div>
                            <button @click="goBack" class="btn btn-secondary">
                                {{ $t('CRUD6.BACK') }}
                            </button>
                            <button @click="editMode = !editMode" class="btn btn-primary ms-2">
                                {{ editMode ? $t('CRUD6.CANCEL') : $t('CRUD6.EDIT') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="apiLoading" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else-if="apiError" class="alert alert-danger">
                            {{ apiError.description || 'Error loading data' }}
                        </div>
                        <div v-else-if="data">
                            <form v-if="editMode" @submit.prevent="saveData">
                                <div v-for="(value, key) in data" :key="key" class="mb-3">
                                    <label :for="key" class="form-label">{{ formatLabel(key) }}</label>
                                    <input 
                                        v-if="!isReadOnly(key)"
                                        :id="key"
                                        v-model="editData[key]"
                                        :type="getInputType(key)"
                                        class="form-control"
                                    />
                                    <input 
                                        v-else
                                        :id="key"
                                        :value="value"
                                        type="text"
                                        class="form-control"
                                        readonly
                                    />
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success" :disabled="apiLoading">
                                        {{ apiLoading ? 'Saving...' : $t('CRUD6.SAVE') }}
                                    </button>
                                    <button type="button" @click="resetForm" class="btn btn-secondary">
                                        {{ $t('CRUD6.RESET') }}
                                    </button>
                                </div>
                            </form>
                            <div v-else>
                                <div v-for="(value, key) in data" :key="key" class="row mb-2">
                                    <div class="col-3 fw-bold">{{ formatLabel(key) }}:</div>
                                    <div class="col-9">{{ formatValue(value) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Api } from '../composables'

const route = useRoute()
const router = useRouter()
const { fetchCRUD6, updateCRUD6, apiLoading, apiError, resetForm } = useCRUD6Api()

const modelName = computed(() => route.params.model as string)
const slug = computed(() => route.params.slug as string)

const editMode = ref(false)
const data = ref<any>(null)
const editData = reactive<any>({})

const readOnlyFields = ['id', 'created_at', 'updated_at', 'slug']

function isReadOnly(key: string): boolean {
    return readOnlyFields.includes(key)
}

function formatLabel(key: string): string {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function formatValue(value: any): string {
    if (value === null || value === undefined) return 'N/A'
    if (typeof value === 'object') return JSON.stringify(value)
    return String(value)
}

function getInputType(key: string): string {
    if (key.includes('email')) return 'email'
    if (key.includes('password')) return 'password'
    if (key.includes('date')) return 'date'
    if (key.includes('time')) return 'datetime-local'
    return 'text'
}

function goBack() {
    router.push({
        name: 'admin.crud6.list',
        params: { model: modelName.value }
    })
}

async function loadData() {
    try {
        data.value = await fetchCRUD6(slug.value)
        // Copy data for editing
        Object.assign(editData, data.value)
    } catch (error) {
        console.error('Error loading data:', error)
    }
}

async function saveData() {
    try {
        await updateCRUD6(slug.value, editData)
        editMode.value = false
        await loadData() // Reload data
    } catch (error) {
        console.error('Error saving data:', error)
    }
}

onMounted(() => {
    loadData()
})
</script>
