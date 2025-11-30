import { ref } from 'vue'
import axios from 'axios'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'

/**
 * Vue composable for CRUD6 many-to-many relationship management.
 *
 * Provides methods to attach and detach related records in many-to-many
 * relationships, commonly used for managing user-role, product-category,
 * or other pivot table relationships.
 *
 * ## Endpoints
 * - `POST   /api/crud6/{model}/{id}/{relation}` → Attach relationships
 * - `DELETE /api/crud6/{model}/{id}/{relation}` → Detach relationships
 *
 * ## Reactive State
 * - `apiLoading` - Boolean indicating if an API call is in progress
 * - `apiError` - Error response from the last failed API call, or null
 *
 * @returns Object with reactive state and relationship methods
 *
 * @example
 * ```typescript
 * // Basic usage - manage user roles
 * import { useCRUD6Relationships } from '@/composables/useCRUD6Relationships'
 * 
 * const { attachRelationships, detachRelationships, apiLoading } = useCRUD6Relationships()
 * 
 * // Attach roles to a user
 * await attachRelationships('users', '123', 'roles', [1, 2, 3])
 * 
 * // Detach roles from a user
 * await detachRelationships('users', '123', 'roles', [2])
 * ```
 *
 * @example
 * ```typescript
 * // Using with async/await and error handling
 * const { attachRelationships, apiError, apiLoading } = useCRUD6Relationships()
 * 
 * async function addCategoriesToProduct(productId: string, categoryIds: number[]) {
 *   try {
 *     await attachRelationships('products', productId, 'categories', categoryIds)
 *     console.log('Categories attached successfully')
 *   } catch (error) {
 *     console.error('Failed to attach categories:', apiError.value?.description)
 *   }
 * }
 * ```
 *
 * @example
 * ```typescript
 * // In a Vue component with loading state
 * // <template>
 * //   <button :disabled="apiLoading" @click="togglePermission">
 * //     {{ apiLoading ? 'Saving...' : 'Add Permission' }}
 * //   </button>
 * // </template>
 * 
 * const { attachRelationships, apiLoading } = useCRUD6Relationships()
 * 
 * async function togglePermission() {
 *   await attachRelationships('roles', roleId, 'permissions', [newPermissionId])
 * }
 * ```
 */
export function useCRUD6Relationships() {
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)

    /**
     * Attach related records in a many-to-many relationship
     * 
     * @param model - The parent model name
     * @param id - The parent record ID
     * @param relation - The relationship name
     * @param ids - Array of IDs to attach
     */
    async function attachRelationships(
        model: string,
        id: string,
        relation: string,
        ids: number[]
    ): Promise<void> {
        apiLoading.value = true
        apiError.value = null

        return axios
            .post(`/api/crud6/${model}/${id}/${relation}`, { ids })
            .then((response) => {
                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
            })
    }

    /**
     * Detach related records in a many-to-many relationship
     * 
     * @param model - The parent model name
     * @param id - The parent record ID
     * @param relation - The relationship name
     * @param ids - Array of IDs to detach
     */
    async function detachRelationships(
        model: string,
        id: string,
        relation: string,
        ids: number[]
    ): Promise<void> {
        apiLoading.value = true
        apiError.value = null

        return axios
            .delete(`/api/crud6/${model}/${id}/${relation}`, { data: { ids } })
            .then((response) => {
                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
            })
    }

    return {
        attachRelationships,
        detachRelationships,
        apiLoading,
        apiError
    }
}
