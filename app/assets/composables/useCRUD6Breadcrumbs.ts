import { useRoute } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'

/**
 * Breadcrumb interface matching UserFrosting 6 sprinkle-core pattern
 * @see usePageMeta in @userfrosting/sprinkle-core/stores
 */
interface Breadcrumb {
    label: string
    to: string
}

/**
 * Composable for managing CRUD6 breadcrumbs
 * 
 * This composable extends UserFrosting 6's usePageMeta store to handle
 * dynamic breadcrumbs for CRUD6 routes. Since CRUD6 routes use dynamic 
 * model names, the route meta.title contains a placeholder '{{model}}' 
 * that cannot be substituted at route registration time.
 * 
 * This composable updates the breadcrumbs array in usePageMeta after
 * the schema is loaded, replacing placeholders with actual model titles.
 * 
 * @example
 * ```typescript
 * import { useCRUD6Breadcrumbs } from '@ssnukala/sprinkle-crud6/composables'
 * 
 * const { setListBreadcrumb, setDetailBreadcrumbs } = useCRUD6Breadcrumbs()
 * 
 * // For list pages - after loading schema
 * setListBreadcrumb(schema.value.title) // e.g., "Users"
 * 
 * // For detail pages - after loading schema and record
 * setDetailBreadcrumbs(schema.value.title, recordName, listPath)
 * // e.g., setDetailBreadcrumbs("Users", "John Doe", "/crud6/users")
 * ```
 * 
 * @returns Object with breadcrumb management functions
 */
export function useCRUD6Breadcrumbs() {
    const route = useRoute()
    const page = usePageMeta()

    /**
     * Update breadcrumbs to replace {{model}} placeholders with actual titles
     * 
     * This function scans existing breadcrumbs for the {{model}} placeholder
     * (which comes from CRUD6.PAGE translation) and replaces it with the
     * actual model title from the schema.
     * 
     * @param title - The title to display in breadcrumb (e.g., "Users", "Products")
     * @param path - Optional path for this breadcrumb entry (defaults to current route path)
     */
    function updateBreadcrumbs(title: string, path?: string): void {
        if (!title) return

        const currentPath = path || route.path
        const existingCrumbs: Breadcrumb[] = [...page.breadcrumbs]
        
        // Find and replace any breadcrumb with {{model}} placeholder
        let updated = false
        const updatedCrumbs: Breadcrumb[] = existingCrumbs.map((crumb: Breadcrumb) => {
            // Check if this crumb has the {{model}} placeholder
            if (crumb.label === '{{model}}' || crumb.label.includes('{{model}}')) {
                updated = true
                return { label: title, to: crumb.to }
            }
            // Check if this crumb matches our current path and needs updating
            if (crumb.to === currentPath && crumb.label !== title) {
                updated = true
                return { label: title, to: crumb.to }
            }
            return crumb
        })

        if (updated) {
            page.breadcrumbs = updatedCrumbs
        } else {
            // If no existing crumb was updated, check if we need to add one
            // This handles cases where the route didn't have a title at all
            const hasCurrentPath = existingCrumbs.some((crumb: Breadcrumb) => crumb.to === currentPath)
            if (!hasCurrentPath) {
                page.breadcrumbs = [
                    ...existingCrumbs,
                    { label: title, to: currentPath }
                ]
            }
        }
    }

    /**
     * Add a child breadcrumb for detail/record views
     * 
     * This adds a breadcrumb entry for the specific record being viewed.
     * It should be called after the record data is loaded.
     * 
     * @param recordTitle - The title of the record (e.g., "John Doe", "Product ABC")
     * @param path - Optional path for this breadcrumb entry (defaults to current route path)
     */
    function addRecordBreadcrumb(recordTitle: string, path?: string): void {
        if (!recordTitle) return

        const currentPath = path || route.path
        const existingCrumbs: Breadcrumb[] = [...page.breadcrumbs]
        
        // Check if we already have this path in breadcrumbs
        const existingIndex = existingCrumbs.findIndex((crumb: Breadcrumb) => crumb.to === currentPath)
        
        if (existingIndex >= 0) {
            // Update existing breadcrumb label
            existingCrumbs[existingIndex] = { 
                label: recordTitle,
                to: existingCrumbs[existingIndex].to
            }
            page.breadcrumbs = existingCrumbs
        } else {
            // Add new breadcrumb
            page.breadcrumbs = [
                ...existingCrumbs,
                { label: recordTitle, to: currentPath }
            ]
        }
    }

    /**
     * Set breadcrumbs for a list page
     * 
     * Replaces the {{model}} placeholder with the model title from schema.
     * Call this after the schema is loaded in list views.
     * 
     * @param modelTitle - The title from schema (e.g., "Users", "Products") 
     */
    function setListBreadcrumb(modelTitle: string): void {
        updateBreadcrumbs(modelTitle)
    }

    /**
     * Set breadcrumbs for a detail/row page
     * 
     * Updates both the model breadcrumb (replacing {{model}} placeholder)
     * and adds the record-specific breadcrumb. Call this after both
     * the schema and record data are loaded.
     * 
     * @param modelTitle - The title from schema (e.g., "Users")
     * @param recordTitle - The title of the specific record (e.g., "John Doe")
     * @param listPath - Optional path to the list page for the model breadcrumb
     */
    function setDetailBreadcrumbs(modelTitle: string, recordTitle: string, listPath?: string): void {
        // First, update the model breadcrumb (replace {{model}} placeholder)
        if (listPath) {
            updateBreadcrumbs(modelTitle, listPath)
        } else {
            // Try to find and update any {{model}} placeholder
            updateBreadcrumbs(modelTitle)
        }
        
        // Then add the record breadcrumb
        if (recordTitle) {
            addRecordBreadcrumb(recordTitle)
        }
    }

    return {
        updateBreadcrumbs,
        addRecordBreadcrumb,
        setListBreadcrumb,
        setDetailBreadcrumbs
    }
}

