import { ref, toValue, watch } from 'vue'
import axios from 'axios'
import { useRegle } from '@regle/core'
import slug from 'limax'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import type {
    CRUD6CreateRequest,
    CRUD6CreateResponse,
    CRUD6DeleteResponse,
    CRUD6EditRequest,
    CRUD6EditResponse,
    CRUD6Response
} from '../interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { useRuleSchemaAdapter } from '@userfrosting/sprinkle-core/composables'
import { useRoute } from 'vue-router'

/**
 * Vue composable for CRUD6 CRUD operations.
 *
 * Endpoints:
 * - GET    /api/CRUD6s/g/{slug}  -> CRUD6Response
 * - POST   /api/CRUD6s           -> CRUD6CreateResponse
 * - PUT    /api/CRUD6s/g/{slug}  -> CRUD6EditResponse
 * - DELETE /api/CRUD6s/g/{slug}  -> CRUD6DeleteResponse
 *
 * Reactive state:
 * - apiLoading: boolean
 * - apiError: ApiErrorResponse | null
 * - formData: CRUD6CreateRequest
 * - r$: validation state from Regle for formData
 *
 * Methods:
 * - fetchCRUD6(slug: string): Promise<CRUD6Response>
 * - createCRUD6(data: CRUD6CreateRequest): Promise<void>
 * - updateCRUD6(slug: string, data: CRUD6EditRequest): Promise<void>
 * - deleteCRUD6(slug: string): Promise<void>
 * - resetForm(): void
 */
export function useCRUD6Api() {
    const defaultFormData = (): CRUD6CreateRequest => ({
    })

    const slugLocked = ref<boolean>(true)
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const formData = ref<CRUD6CreateRequest>(defaultFormData())

    const route = useRoute()
    const model = route.params.model as string

    // Dynamically load the schema file for the current model
    async function loadSchema() {
        const response = await axios.get(`/schema/requests/${model}.yaml`)
        // If you need JSON, convert YAML to JSON here
        return response.data
    }

    // Load the schema and set up the validator
    const { r$ } = useRegle(formData, useRuleSchemaAdapter().adapt(loadSchema()))

    async function fetchCRUD6(slug: string) {
        apiLoading.value = true
        apiError.value = null

        return axios
            .get<CRUD6Response>(`/api/${model}/g/${toValue(slug)}`)
            .then((response) => response.data)
            .catch((err) => {
                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
            })
    }

    async function createCRUD6(data: CRUD6CreateRequest) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .post<CRUD6CreateResponse>(`/api/${model}`, data)
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

    async function updateCRUD6(slug: string, data: CRUD6EditRequest) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .put<CRUD6EditResponse>(`/api/${model}/g/${slug}`, data)
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

    async function deleteCRUD6(slug: string) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .delete<CRUD6DeleteResponse>(`/api/${model}/g/${slug}`)
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

    function resetForm() {
        formData.value = defaultFormData()
    }

    watch(
        () => formData.value.name,
        (name) => {
            if (slugLocked.value) {
                formData.value.slug = slug(name)
            }
        }
    )

    return {
        fetchCRUD6,
        createCRUD6,
        updateCRUD6,
        deleteCRUD6,
        apiLoading,
        apiError,
        formData,
        r$,
        resetForm,
        slugLocked
    }
}
