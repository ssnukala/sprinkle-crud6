<script setup lang="ts">
import { ref, watch, computed, onMounted, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'

/**
 * PageDynamic Component
 * 
 * A dynamic wrapper that renders either PageRow or PageMasterDetail
 * based on the query parameter or schema setting.
 * 
 * Priority order:
 * 1. Query parameter: ?v=md or ?view=master-detail → Use PageMasterDetail
 * 2. Query parameter: ?v=row or ?view=row → Use PageRow
 * 3. Schema render_mode setting (if query parameter doesn't exist)
 * 4. Default: Always use PageRow (if no query param and no schema setting)
 * 
 * URL Examples:
 * - /crud6/orders/123 → Use schema render_mode, default to PageRow
 * - /crud6/orders/123?v=md → Force PageMasterDetail view
 * - /crud6/orders/123?view=master-detail → Force PageMasterDetail view
 * - /crud6/orders/123?v=row → Force PageRow view
 * - /crud6/orders/123?view=row → Force PageRow view
 */

const route = useRoute()

// Get model from route parameters
const model = computed(() => route.params.model as string)

// Use composables for schema
const {
    schema,
    loading: schemaLoading,
    error: schemaError,
    loadSchema
} = useCRUD6Schema()

// Determine which component to render
const componentToRender = computed(() => {
    if (!schema.value) return 'loading'
    
    // Priority 1: Check query parameter (v or view)
    // If query parameter exists, use it (overrides everything)
    const viewParam = route.query.v || route.query.view
    if (viewParam) {
        if (viewParam === 'md' || viewParam === 'master-detail') {
            return 'master-detail'
        }
        if (viewParam === 'row' || viewParam === 'standard') {
            return 'row'
        }
    }
    
    // Priority 2: If NO query parameter, check schema render_mode setting
    if (!viewParam && schema.value.render_mode) {
        if (schema.value.render_mode === 'master-detail') {
            return 'master-detail'
        }
        if (schema.value.render_mode === 'row') {
            return 'row'
        }
    }
    
    // Priority 3: Default to PageRow (always)
    // - No query parameter exists
    // - No schema render_mode exists or is invalid
    return 'row'
})

// Dynamically import the appropriate component
const PageRow = defineAsyncComponent(() => import('./PageRow.vue'))
const PageMasterDetail = defineAsyncComponent(() => import('./PageMasterDetail.vue'))

// Load schema when model changes
let currentModel = ''
watch(model, async (newModel) => {
    if (newModel && loadSchema && newModel !== currentModel) {
        console.log('[PageDynamic] Loading schema for model:', newModel)
        currentModel = newModel
        const schemaPromise = loadSchema(newModel)
        if (schemaPromise && typeof schemaPromise.then === 'function') {
            await schemaPromise
            console.log('[PageDynamic] Schema loaded, render mode:', componentToRender.value)
        }
    }
}, { immediate: true })

// Watch for query parameter changes
watch(() => route.query, () => {
    console.log('[PageDynamic] Query params changed, render mode:', componentToRender.value)
}, { deep: true })

// Load schema on mount
onMounted(async () => {
    if (model.value && !schema.value) {
        await loadSchema(model.value)
    }
})
</script>

<template>
    <template v-if="schemaError">
        <UFErrorPage :errorCode="schemaError.status || 500" />
    </template>
    <template v-else-if="schemaLoading || !schema">
        <div class="uk-text-center uk-padding">
            <div uk-spinner></div>
            <p>{{ $t('LOADING') }}</p>
        </div>
    </template>
    <template v-else>
        <!-- Render PageMasterDetail when query param or schema specifies master-detail mode -->
        <PageMasterDetail v-if="componentToRender === 'master-detail'" />
        
        <!-- Render PageRow for standard mode -->
        <PageRow v-else-if="componentToRender === 'row'" />
    </template>
</template>
