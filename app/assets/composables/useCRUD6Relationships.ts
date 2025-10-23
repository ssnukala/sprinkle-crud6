import { ref } from 'vue'
import axios from 'axios'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'

/**
 * Vue composable for CRUD6 many-to-many relationship management.
 *
 * Endpoints:
 * - POST   /api/crud6/{model}/{id}/{relation} -> Attach relationships
 * - DELETE /api/crud6/{model}/{id}/{relation} -> Detach relationships
 *
 * Reactive state:
 * - apiLoading: boolean
 * - apiError: ApiErrorResponse | null
 *
 * Methods:
 * - attachRelationships(model: string, id: string, relation: string, ids: number[]): Promise<void>
 * - detachRelationships(model: string, id: string, relation: string, ids: number[]): Promise<void>
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
