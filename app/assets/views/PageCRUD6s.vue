<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3>{{ $t('CRUD6.LIST', { model: modelName }) }}</h3>
                    </div>
                    <div class="card-body">
                        <UFTable
                            :sprunje="sprunje"
                            :columns="columns"
                            :loading="loading"
                            :error="error"
                            @row-click="goToDetail"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { UFTable } from '@userfrosting/sprinkle-core/components'
import { useCRUD6sApi } from '../composables'

const route = useRoute()
const router = useRouter()
const { crud6Rows, error, loading, updateCRUD6s } = useCRUD6sApi()

const modelName = computed(() => route.params.model as string)

// Default columns - this should ideally be loaded from schema
const columns = ref([
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
    { key: 'updated_at', label: 'Updated', sortable: true }
])

// For UFTable compatibility, create a sprunje-like object
const sprunje = computed(() => ({
    data: crud6Rows.value,
    loading: loading.value,
    error: error.value
}))

function goToDetail(item: any) {
    router.push({
        name: 'admin.crud6',
        params: { model: modelName.value, slug: item.id || item.slug }
    })
}

onMounted(() => {
    updateCRUD6s()
})
</script>
