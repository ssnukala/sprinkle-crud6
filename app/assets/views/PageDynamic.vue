<script setup lang="ts">
import { computed, watch, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'

/**
 * PageDynamic Component
 * 
 * A dynamic wrapper that renders either PageRow or PageMasterDetail
 * based on the query parameter.
 * 
 * Priority order:
 * 1. Query parameter: ?v=md or ?view=master-detail → Use PageMasterDetail
 * 2. Query parameter: ?v=row or ?view=row → Use PageRow
 * 3. Default: Always use PageRow
 * 
 * Note: Schema loading is delegated to child components (PageRow/PageMasterDetail)
 * to avoid duplicate API calls. PageDynamic is just a routing wrapper.
 * 
 * URL Examples:
 * - /crud6/orders/123 → Use PageRow (default)
 * - /crud6/orders/123?v=md → Force PageMasterDetail view
 * - /crud6/orders/123?view=master-detail → Force PageMasterDetail view
 * - /crud6/orders/123?v=row → Force PageRow view
 * - /crud6/orders/123?view=row → Force PageRow view
 */

const route = useRoute()

// Determine which component to render based on query params only
// Schema-based render_mode will be handled by the child components
const componentToRender = computed(() => {
    // Priority 1: Check query parameter (v or view)
    // If query parameter exists, use it to determine view
    const viewParam = route.query.v || route.query.view
    if (viewParam) {
        if (viewParam === 'md' || viewParam === 'master-detail') {
            return 'master-detail'
        }
        if (viewParam === 'row' || viewParam === 'standard') {
            return 'row'
        }
    }
    
    // Priority 2: Default to PageRow
    // PageRow will check schema render_mode and handle it appropriately
    // This prevents PageDynamic from making unnecessary schema API calls
    return 'row'
})

// Dynamically import the appropriate component
const PageRow = defineAsyncComponent(() => import('./PageRow.vue'))
const PageMasterDetail = defineAsyncComponent(() => import('./PageMasterDetail.vue'))

// Watch for query parameter changes
watch(() => route.query, () => {
    console.log('[PageDynamic] Query params changed, render mode:', componentToRender.value)
}, { deep: true })
</script>

<template>
    <!-- Render PageMasterDetail when query param specifies master-detail mode -->
    <PageMasterDetail v-if="componentToRender === 'master-detail'" />
    
    <!-- Render PageRow for standard mode (default) -->
    <PageRow v-else-if="componentToRender === 'row'" />
</template>
