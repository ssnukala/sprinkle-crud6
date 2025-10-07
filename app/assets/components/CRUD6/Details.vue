<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { DetailConfig } from '@ssnukala/sprinkle-crud6/composables'

const props = defineProps<{
    recordId: string
    parentModel: string
    detailConfig: DetailConfig
}>()

// Load schema for the detail model to get field information
const { schema: detailSchema, loading: schemaLoading, loadSchema } = useCRUD6Schema()

// Track whether schema has been loaded
const schemaLoaded = ref(false)

// Load the detail model schema when component mounts
onMounted(async () => {
    console.log('[Details] 🚀 Component mounted - loading schema for:', props.detailConfig.model)
    console.log('[Details] 📊 Detail config:', {
        model: props.detailConfig.model,
        parentModel: props.parentModel,
        recordId: props.recordId,
        list_fields: props.detailConfig.list_fields
    })
    try {
        await loadSchema(props.detailConfig.model)
        schemaLoaded.value = true
        console.log('[Details] ✅ Schema loaded successfully')
        console.log('[Details] 📝 dataUrl will be:', dataUrl.value)
        if (detailSchema.value) {
            console.log('[Details] 📋 Schema fields:', Object.keys(detailSchema.value.fields || {}))
        } else {
            console.warn('[Details] ⚠️  Schema loaded but detailSchema.value is null/undefined')
        }
    } catch (error) {
        console.error('[Details] ❌ Failed to load schema:', error)
    }
})

// Build the data URL based on the detail configuration
const dataUrl = computed(() => {
    return `/api/crud6/${props.parentModel}/${props.recordId}/${props.detailConfig.model}`
})

// Get the title for the detail section
const detailTitle = computed(() => {
    // Try to use the title from config first (may be a translation key)
    if (props.detailConfig.title) {
        return props.detailConfig.title
    }
    // Use schema title if available
    if (detailSchema.value?.title) {
        return detailSchema.value.title
    }
    // Capitalize and pluralize model name as fallback
    const modelName = props.detailConfig.model
    return modelName.charAt(0).toUpperCase() + modelName.slice(1)
})

// Get field labels from the detail model schema
const getFieldLabel = (fieldKey: string): string => {
    if (detailSchema.value?.fields?.[fieldKey]?.label) {
        return detailSchema.value.fields[fieldKey].label
    }
    // Fallback to field key
    return fieldKey.charAt(0).toUpperCase() + fieldKey.slice(1).replace(/_/g, ' ')
}

// Get field type from the detail model schema
const getFieldType = (fieldKey: string): string => {
    return detailSchema.value?.fields?.[fieldKey]?.type || 'string'
}
</script>

<template>
    <UFCardBox :title="$t(detailTitle)">
        <!-- Loading state -->
        <div v-if="schemaLoading" class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ $t('LOADING') }}</p>
        </div>
        
        <!-- Table with data -->
        <UFSprunjeTable
            v-else
            :dataUrl="dataUrl"
            searchColumn="name"
            hideFilters>
            <template #header>
                <UFSprunjeHeader 
                    v-for="fieldKey in detailConfig.list_fields" 
                    :key="fieldKey"
                    :sort="fieldKey">
                    {{ getFieldLabel(fieldKey) }}
                </UFSprunjeHeader>
            </template>

            <template #body="{ row }">
                <UFSprunjeColumn v-for="fieldKey in detailConfig.list_fields" :key="fieldKey">
                    <template v-if="getFieldType(fieldKey) === 'boolean'">
                        <UFLabel :severity="row[fieldKey] ? 'success' : 'danger'">
                            {{ row[fieldKey] ? $t('ENABLED') : $t('DISABLED') }}
                        </UFLabel>
                    </template>
                    <template v-else-if="getFieldType(fieldKey) === 'date'">
                        {{ row[fieldKey] ? new Date(row[fieldKey]).toLocaleDateString() : '' }}
                    </template>
                    <template v-else-if="getFieldType(fieldKey) === 'datetime'">
                        {{ row[fieldKey] ? new Date(row[fieldKey]).toLocaleString() : '' }}
                    </template>
                    <template v-else>
                        {{ row[fieldKey] }}
                    </template>
                </UFSprunjeColumn>
            </template>
        </UFSprunjeTable>
    </UFCardBox>
</template>
