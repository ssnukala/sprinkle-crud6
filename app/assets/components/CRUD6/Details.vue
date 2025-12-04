<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import { createTranslationHelper } from '../../utils/translation'
import { useCRUD6SchemaStore } from '@ssnukala/sprinkle-crud6/stores'
import type { DetailConfig } from '@ssnukala/sprinkle-crud6/composables'
import { debugLog } from '../../utils/debug'

const props = defineProps<{
    recordId: string
    parentModel: string
    detailConfig: DetailConfig
}>()

// Use the global schema store to check cache first
const schemaStore = useCRUD6SchemaStore()
const translator = useTranslator()
const t = createTranslationHelper(translator)

// Check if schema is already cached (from parent's include_related request)
const detailSchema = computed(() => {
    // Try to get from cache with 'list' context (default for related schemas)
    return schemaStore.getSchema(props.detailConfig.model, 'list')
})

// Track whether schema has been loaded
const schemaLoaded = ref(false)
const schemaLoading = ref(false)

// Load the detail model schema if not already cached
onMounted(async () => {
    if (!detailSchema.value) {
        debugLog('[Details] Schema not in cache, loading for model:', props.detailConfig.model)
        schemaLoading.value = true
        await schemaStore.loadSchema(props.detailConfig.model, false, 'list')
        schemaLoading.value = false
    } else {
        debugLog('[Details] Using cached schema for model:', props.detailConfig.model)
    }
    schemaLoaded.value = true
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
    <UFCardBox :title="t(detailTitle, {}, detailTitle)">
        <!-- Loading state -->
        <div v-if="schemaLoading" class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ t('LOADING', {}, 'Loading...') }}</p>
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
                    <template v-if="['boolean', 'boolean-tgl', 'boolean-toggle', 'boolean-yn'].includes(getFieldType(fieldKey))">
                        <UFLabel :severity="row[fieldKey] ? 'success' : 'danger'">
                            {{ row[fieldKey] ? t('ENABLED', {}, 'Enabled') : t('DISABLED', {}, 'Disabled') }}
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
