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
import { useCRUD6SchemaStore } from '../stores/useCRUD6SchemaStore'

/**
 * Vue composable for CRUD6 CRUD operations.
 *
 * Endpoints:
 * - GET    /api/crud6/{model}/{id}        -> CRUD6Response
 * - POST   /api/crud6/{model}             -> CRUD6CreateResponse
 * - PUT    /api/crud6/{model}/{id}        -> CRUD6EditResponse
 * - PUT    /api/crud6/{model}/{id}/{field} -> CRUD6EditResponse
 * - DELETE /api/crud6/{model}/{id}        -> CRUD6DeleteResponse
 *
 * Reactive state:
 * - apiLoading: boolean
 * - apiError: ApiErrorResponse | null
 * - formData: CRUD6CreateRequest
 * - r$: validation state from Regle for formData
 *
 * Methods:
 * - fetchRow(id: string): Promise<CRUD6Response>
 * - fetchRows(id: string): Promise<CRUD6Response> (alias for fetchRow)
 * - createRow(data: CRUD6CreateRequest): Promise<void>
 * - updateRow(id: string, data: CRUD6EditRequest): Promise<void>
 * - updateField(id: string, field: string, value: any): Promise<void>
 * - deleteRow(id: string): Promise<void>
 * - resetForm(): void
 */
export function useCRUD6Api(modelName?: string) {
    const defaultFormData = (): CRUD6CreateRequest => ({})

    const slugLocked = ref<boolean>(true)
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const formData = ref<CRUD6CreateRequest>(defaultFormData())

    const route = useRoute()
    const model = modelName || (route.params.model as string)
    
    // Use the global schema store to avoid duplicate API calls
    const schemaStore = useCRUD6SchemaStore()

    // Dynamically load the schema file for the current model
    // Uses the global store cache to prevent duplicate API calls
    async function loadSchema() {
        try {
            // Use the store's loadSchema which has caching
            const schema = await schemaStore.loadSchema(model)
            return schema || {}
        } catch (error) {
            console.error('[useCRUD6Api] Schema load error:', error)
            return {}
        }
    }

    // Load the schema and set up the validator
    const { r$ } = useRegle(formData, useRuleSchemaAdapter().adapt(loadSchema()))

    async function fetchRow(id: string) {
        apiLoading.value = true
        apiError.value = null

        return axios
            .get<CRUD6Response>(`/api/crud6/${model}/${toValue(id)}`)
            .then((response) => {
                // The API wraps the data in a response object with {message, model, id, data}
                // We need to extract just the data property for the CRUD6Response
                if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                    return response.data.data as CRUD6Response
                }
                return response.data
            })
            .catch((err) => {
                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
            })
    }

    async function createRow(data: CRUD6CreateRequest) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .post<CRUD6CreateResponse>(`/api/crud6/${model}`, data)
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

    async function updateRow(id: string, data: CRUD6EditRequest) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .put<CRUD6EditResponse>(`/api/crud6/${model}/${id}`, data)
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

    async function updateField(id: string, field: string, value: any) {
        apiLoading.value = true
        apiError.value = null
        const data = { [field]: value }
        return axios
            .put<CRUD6EditResponse>(`/api/crud6/${model}/${id}/${field}`, data)
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

    async function deleteRow(id: string) {
        apiLoading.value = true
        apiError.value = null
        return axios
            .delete<CRUD6DeleteResponse>(`/api/crud6/${model}/${id}`)
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
            if (slugLocked.value && name) {
                formData.value.slug = slug(name)
            }
        }
    )

    /**
     * Alias for fetchRow to maintain compatibility with theme components
     * This provides both fetchRow and fetchRows with the same functionality
     */
    function fetchRows(id: string) {
        return fetchRow(id)
    }

    return {
        fetchRow,
        fetchRows,
        createRow,
        updateRow,
        updateField,
        deleteRow,
        apiLoading,
        apiError,
        formData,
        r$,
        resetForm,
        slugLocked
    }
}
