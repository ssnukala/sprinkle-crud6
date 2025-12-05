import { nextTick, getCurrentInstance } from 'vue'
import { useRoute } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { debugLog, debugWarn } from '../utils/debug'

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
 * Uses nextTick to ensure updates happen after usePageMeta's route watcher
 * has finished refreshing the breadcrumbs.
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
    
    // Access UserFrosting's global $t() function via getCurrentInstance()
    // This gives access to the same translation system used in Vue templates
    const instance = getCurrentInstance()
    const $t = instance?.appContext.config.globalProperties.$t
    
    if (!$t) {
        debugWarn('[useCRUD6Breadcrumbs] Warning: $t() translation function not available')
    }

    /**
     * Translate a breadcrumb label if it's a translation key
     * 
     * Detects translation keys by checking if the string is in SCREAMING_SNAKE_CASE
     * format and contains dots (e.g., "C6ADMIN_PANEL", "CRUD6.PAGE").
     * Falls back to the original value if translation doesn't exist.
     * 
     * Uses UserFrosting's global $t() function to access the same translations
     * available in Vue templates.
     * 
     * @param label - The label to potentially translate
     * @returns Translated label or original value
     */
    function translateLabel(label: string): string {
        // Return label unchanged if $t() is not available
        if (!$t) {
            debugWarn('[useCRUD6Breadcrumbs.translateLabel] $t() not available, returning label unchanged:', label)
            return label
        }
        
        // Check if label looks like a translation key (uppercase with dots/underscores)
        // Examples: "C6ADMIN_PANEL", "CRUD6.PAGE", "USER.MANAGEMENT"
        if (/^[A-Z][A-Z0-9_.]+$/.test(label)) {
            debugLog('[useCRUD6Breadcrumbs.translateLabel] Attempting to translate:', label)
            
            // Try direct translation first using UserFrosting's $t()
            const translated = $t(label)
            debugLog('[useCRUD6Breadcrumbs.translateLabel] Translation result:', { 
                original: label, 
                translated, 
                isDifferent: translated !== label,
                translatedType: typeof translated 
            })
            
            // Check if translation was successful
            if (translated && typeof translated === 'string' && translated !== label && translated.trim() !== '') {
                debugLog('[useCRUD6Breadcrumbs.translateLabel] Using translation:', { original: label, translated })
                return translated
            }
            
            // Try fallback translations for common key patterns
            // e.g., if "CRUD6.ADMIN_PANEL" doesn't translate, try "CRUD6_ADMIN_PANEL"
            if (label.includes('.')) {
                const fallbackKey = label.replace(/\./g, '_')
                debugLog('[useCRUD6Breadcrumbs.translateLabel] Trying fallback key:', fallbackKey)
                const fallbackTranslated = $t(fallbackKey)
                debugLog('[useCRUD6Breadcrumbs.translateLabel] Fallback result:', {
                    fallbackKey,
                    translated: fallbackTranslated,
                    isDifferent: fallbackTranslated !== fallbackKey,
                    type: typeof fallbackTranslated
                })
                if (fallbackTranslated && typeof fallbackTranslated === 'string' && fallbackTranslated !== fallbackKey && fallbackTranslated.trim() !== '') {
                    debugLog('[useCRUD6Breadcrumbs.translateLabel] Using fallback translation:', { original: label, fallback: fallbackKey, translated: fallbackTranslated })
                    return fallbackTranslated
                }
            }
            
            // Try alternative key patterns as last resort
            // For C6ADMIN_PANEL, try CRUD6_PANEL, ADMIN_PANEL, etc.
            const alternativeKeys = []
            
            // If key starts with C6, try CRUD6 variant
            if (label.startsWith('C6')) {
                alternativeKeys.push(label.replace(/^C6/, 'CRUD6'))
            }
            
            // If key has ADMIN_PANEL, try just that
            if (label.includes('ADMIN_PANEL')) {
                alternativeKeys.push('ADMIN_PANEL')
            }
            
            // Try each alternative
            for (const altKey of alternativeKeys) {
                debugLog('[useCRUD6Breadcrumbs.translateLabel] Trying alternative key:', altKey)
                const altTranslated = $t(altKey)
                if (altTranslated && typeof altTranslated === 'string' && altTranslated !== altKey && altTranslated.trim() !== '') {
                    debugLog('[useCRUD6Breadcrumbs.translateLabel] Using alternative translation:', { 
                        original: label, 
                        alternative: altKey, 
                        translated: altTranslated 
                    })
                    return altTranslated
                }
            }
            
            debugLog('[useCRUD6Breadcrumbs.translateLabel] No valid translation found for:', label)
            debugLog('[useCRUD6Breadcrumbs.translateLabel] ⚠️ Translation not found - using original label')
        }
        return label
    }

    /**
     * Update breadcrumbs to replace {{model}} placeholders with actual titles
     * 
     * This function scans existing breadcrumbs for the {{model}} placeholder
     * (which comes from CRUD6.PAGE translation) and replaces ALL occurrences
     * with the actual model title from the schema. It also removes duplicate
     * breadcrumbs that have the same label to keep the trail clean.
     * 
     * Additionally, it translates any translation keys found in breadcrumb labels.
     * 
     * Uses nextTick to ensure the update happens after usePageMeta's refresh.
     * 
     * @param title - The title to display in breadcrumb (e.g., "Users", "Products")
     * @param path - Optional path for this breadcrumb entry (defaults to current route path)
     */
    async function updateBreadcrumbs(title: string, path?: string): Promise<void> {
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Called with:', { title, path })
        
        if (!title) {
            debugWarn('[useCRUD6Breadcrumbs.updateBreadcrumbs] No title provided, skipping')
            return
        }

        // Wait for next tick to ensure usePageMeta has finished its refresh
        await nextTick()
        
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] After nextTick')

        const currentPath = path || route.path
        const existingCrumbs: Breadcrumb[] = [...page.breadcrumbs]
        
        // Log detailed breadcrumb information
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Existing breadcrumbs:', existingCrumbs)
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Breadcrumb labels:', existingCrumbs.map(c => c.label))
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Current path:', currentPath)
        
        // Replace breadcrumbs with CRUD6.PAGE translation key or {{model}} placeholder
        // Also handle breadcrumbs that point to route patterns like '/crud6/:model'
        // Additionally, translate any translation keys in breadcrumb labels
        let updated = false
        const updatedCrumbs: Breadcrumb[] = existingCrumbs.map((crumb: Breadcrumb) => {
            debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Checking crumb:', { label: crumb.label, to: crumb.to })
            debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Check results:', {
                'isCRUD6PAGE': crumb.label === 'CRUD6.PAGE',
                'isPlaceholder': crumb.label === '{{model}}',
                'includesPlaceholder': crumb.label.includes('{{model}}'),
                'isRoutePattern': crumb.to.includes(':model')
            })
            
            // Check if this crumb has the CRUD6.PAGE translation key (untranslated)
            // OR the {{model}} placeholder (translated)
            if (crumb.label === 'CRUD6.PAGE' || crumb.label === '{{model}}' || crumb.label.includes('{{model}}')) {
                debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Found CRUD6.PAGE or {{model}} placeholder in breadcrumb:', crumb)
                updated = true
                return { label: title, to: currentPath }
            }
            // Check if this crumb points to a route pattern (e.g., '/crud6/:model')
            // This handles cases where the translation already happened but the path is still a pattern
            if (crumb.to.includes(':model')) {
                debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Found route pattern breadcrumb:', crumb)
                updated = true
                return { label: title, to: currentPath }
            }
            // Check if this crumb matches our current path and needs updating
            if (crumb.to === currentPath && crumb.label !== title) {
                debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Updating breadcrumb for current path:', crumb)
                updated = true
                return { label: title, to: crumb.to }
            }
            
            // Translate the label if it's a translation key
            const translatedLabel = translateLabel(crumb.label)
            if (translatedLabel !== crumb.label) {
                updated = true
                return { label: translatedLabel, to: crumb.to }
            }
            
            return crumb
        })

        if (updated) {
            debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Breadcrumbs updated, applying deduplication')
            
            // Remove duplicate consecutive breadcrumbs with the same label
            // This handles cases where parent and child routes both have {{model}}
            const deduplicatedCrumbs: Breadcrumb[] = []
            for (let i = 0; i < updatedCrumbs.length; i++) {
                const crumb = updatedCrumbs[i]
                const prevCrumb = deduplicatedCrumbs[deduplicatedCrumbs.length - 1]
                
                // Skip if this crumb has the same label as the previous one
                if (prevCrumb && prevCrumb.label === crumb.label) {
                    debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Skipping duplicate breadcrumb:', crumb)
                    continue
                }
                deduplicatedCrumbs.push(crumb)
            }
            
            debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Final breadcrumbs after deduplication:', deduplicatedCrumbs)
            page.breadcrumbs = deduplicatedCrumbs
        } else {
            debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] No CRUD6.PAGE or {{model}} placeholder found, checking if we need to add breadcrumb')
            
            // If no existing crumb was updated, check if we need to add one
            // This handles cases where the route didn't have a title at all
            const hasCurrentPath = existingCrumbs.some((crumb: Breadcrumb) => crumb.to === currentPath)
            if (!hasCurrentPath) {
                debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Adding new breadcrumb for current path')
                page.breadcrumbs = [
                    ...existingCrumbs,
                    { label: title, to: currentPath }
                ]
            } else {
                debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Current path already exists in breadcrumbs, no changes made')
            }
        }
        
        debugLog('[useCRUD6Breadcrumbs.updateBreadcrumbs] Completed. Final page.breadcrumbs:', page.breadcrumbs)
    }

    /**
     * Add a child breadcrumb for detail/record views
     * 
     * This adds a breadcrumb entry for the specific record being viewed.
     * It should be called after the record data is loaded.
     * 
     * Uses nextTick to ensure the update happens after usePageMeta's refresh.
     * 
     * @param recordTitle - The title of the record (e.g., "John Doe", "Product ABC")
     * @param path - Optional path for this breadcrumb entry (defaults to current route path)
     */
    async function addRecordBreadcrumb(recordTitle: string, path?: string): Promise<void> {
        debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Called with:', { recordTitle, path })
        
        if (!recordTitle) {
            debugWarn('[useCRUD6Breadcrumbs.addRecordBreadcrumb] No recordTitle provided, skipping')
            return
        }

        // Wait for next tick to ensure usePageMeta has finished its refresh
        await nextTick()
        
        debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] After nextTick')

        const currentPath = path || route.path
        const existingCrumbs: Breadcrumb[] = [...page.breadcrumbs]
        
        debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Existing breadcrumbs:', existingCrumbs)
        debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Current path:', currentPath)
        
        // Check if we already have this path in breadcrumbs
        const existingIndex = existingCrumbs.findIndex((crumb: Breadcrumb) => crumb.to === currentPath)
        
        if (existingIndex >= 0) {
            debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Updating existing breadcrumb at index', existingIndex)
            // Update existing breadcrumb label
            existingCrumbs[existingIndex] = { 
                label: recordTitle,
                to: existingCrumbs[existingIndex].to
            }
            page.breadcrumbs = existingCrumbs
        } else {
            debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Adding new breadcrumb')
            // Add new breadcrumb
            page.breadcrumbs = [
                ...existingCrumbs,
                { label: recordTitle, to: currentPath }
            ]
        }
        
        debugLog('[useCRUD6Breadcrumbs.addRecordBreadcrumb] Completed. Final page.breadcrumbs:', page.breadcrumbs)
    }

    /**
     * Set breadcrumbs for a list page
     * 
     * Replaces the {{model}} placeholder with the model title from schema.
     * Call this after the schema is loaded in list views.
     * 
     * @param modelTitle - The title from schema (e.g., "Users", "Products") 
     */
    async function setListBreadcrumb(modelTitle: string): Promise<void> {
        debugLog('[useCRUD6Breadcrumbs.setListBreadcrumb] Called with modelTitle:', modelTitle)
        await updateBreadcrumbs(modelTitle)
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
    async function setDetailBreadcrumbs(modelTitle: string, recordTitle: string, listPath?: string): Promise<void> {
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] START', { modelTitle, recordTitle, listPath })
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Called with:', { modelTitle, recordTitle, listPath })
        
        // Wait for next tick to ensure usePageMeta has finished its refresh
        await nextTick()
        
        const currentPath = route.path
        const existingCrumbs: Breadcrumb[] = [...page.breadcrumbs]
        
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] BEFORE PROCESSING', {
            existingCrumbs: existingCrumbs.map(c => ({ label: c.label, to: c.to })),
            currentPath,
            listPath
        })
        
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Existing breadcrumbs:', existingCrumbs)
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Current path:', currentPath)
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] List path:', listPath)
        
        // Replace CRUD6.PAGE or {{model}} placeholders and update paths
        const updatedCrumbs: Breadcrumb[] = []
        let foundModelCrumb = false
        let foundRecordCrumb = false
        
        for (const crumb of existingCrumbs) {
            console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Processing crumb', {
                label: crumb.label,
                to: crumb.to,
                checks: {
                    isPlaceholder: crumb.label === 'CRUD6.PAGE' || crumb.label === '{{model}}' || crumb.label.includes('{{model}}') || crumb.to.includes(':model'),
                    isListPath: listPath && crumb.to === listPath,
                    isCurrentPath: crumb.to === currentPath
                }
            })
            
            // Check if this is the model placeholder that needs replacement
            if (crumb.label === 'CRUD6.PAGE' || crumb.label === '{{model}}' || crumb.label.includes('{{model}}') || crumb.to.includes(':model')) {
                debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Found model placeholder/pattern breadcrumb:', crumb)
                console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Replacing placeholder with model title')
                // Replace with model title pointing to list path
                updatedCrumbs.push({ label: modelTitle, to: listPath || `/crud6/${route.params.model}` })
                foundModelCrumb = true
            }
            // Check if this breadcrumb points to the list path (existing model breadcrumb)
            // This handles cases where setDetailBreadcrumbs is called multiple times
            // and the placeholder was already replaced with the model title
            else if (listPath && crumb.to === listPath) {
                debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Found existing model breadcrumb by path:', crumb)
                console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Updating existing model breadcrumb')
                // Update with current model title (in case it changed, e.g., from plural to singular)
                updatedCrumbs.push({ label: modelTitle, to: listPath })
                foundModelCrumb = true
            }
            // Check if this is already the current path (detail page)
            else if (crumb.to === currentPath) {
                debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Found existing current path breadcrumb:', crumb)
                console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Found current path crumb', { hasRecordTitle: !!recordTitle })
                // Update it with record title (only if recordTitle is provided)
                if (recordTitle) {
                    console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Updating current path with recordTitle')
                    updatedCrumbs.push({ label: recordTitle, to: currentPath })
                    foundRecordCrumb = true
                } else {
                    // If no recordTitle provided, skip this breadcrumb (it will be added later when record is loaded)
                    debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Skipping current path breadcrumb (no recordTitle yet)')
                    console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Skipping current path (no recordTitle)')
                }
            }
            // Keep other breadcrumbs, but translate if needed
            else {
                const translatedLabel = translateLabel(crumb.label)
                console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Keeping breadcrumb', { original: crumb.label, translated: translatedLabel })
                updatedCrumbs.push({ label: translatedLabel, to: crumb.to })
            }
        }
        
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] After loop', {
            foundModelCrumb,
            foundRecordCrumb,
            updatedCrumbs: updatedCrumbs.map(c => ({ label: c.label, to: c.to }))
        })
        
        // If we didn't find a model breadcrumb, add one
        if (!foundModelCrumb && listPath) {
            debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Adding model breadcrumb')
            console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Adding new model breadcrumb')
            updatedCrumbs.push({ label: modelTitle, to: listPath })
        }
        
        // If we didn't find the record breadcrumb, add it
        if (!foundRecordCrumb && recordTitle) {
            debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Adding record breadcrumb')
            console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Adding new record breadcrumb')
            updatedCrumbs.push({ label: recordTitle, to: currentPath })
        }
        
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Before deduplication', {
            updatedCrumbs: updatedCrumbs.map(c => ({ label: c.label, to: c.to }))
        })
        
        // Remove duplicate consecutive breadcrumbs with the same label
        const deduplicatedCrumbs: Breadcrumb[] = []
        for (let i = 0; i < updatedCrumbs.length; i++) {
            const crumb = updatedCrumbs[i]
            const prevCrumb = deduplicatedCrumbs[deduplicatedCrumbs.length - 1]
            
            // Skip if this crumb has the same label as the previous one
            if (prevCrumb && prevCrumb.label === crumb.label) {
                debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Skipping duplicate breadcrumb:', crumb)
                console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] Skipping duplicate', { label: crumb.label })
                continue
            }
            deduplicatedCrumbs.push(crumb)
        }
        
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] FINAL RESULT', {
            deduplicatedCrumbs: deduplicatedCrumbs.map(c => ({ label: c.label, to: c.to }))
        })
        
        debugLog('[useCRUD6Breadcrumbs.setDetailBreadcrumbs] Final breadcrumbs:', deduplicatedCrumbs)
        page.breadcrumbs = deduplicatedCrumbs
        
        console.log('[BREADCRUMB DEBUG - setDetailBreadcrumbs] END - page.breadcrumbs set to', {
            breadcrumbs: page.breadcrumbs.map(b => ({ label: b.label, to: b.to }))
        })
    }

    return {
        updateBreadcrumbs,
        addRecordBreadcrumb,
        setListBreadcrumb,
        setDetailBreadcrumbs
    }
}
