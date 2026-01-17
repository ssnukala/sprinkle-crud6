/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

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
import { useCRUD6RegleAdapter } from './useCRUD6ValidationAdapter'
import { useRoute } from 'vue-router'
import { useCRUD6SchemaStore } from '../stores/useCRUD6SchemaStore'
import { debugLog, debugWarn, debugError } from '../utils/debug'

/**
 * Vue composable for CRUD6 CRUD operations.
 *
 * Provides reactive state and methods for performing Create, Read, Update, 
 * and Delete operations on CRUD6 models. Integrates with the CRUD6 schema 
 * system for automatic validation using Regle.
 *
 * ## Endpoints
 * - `GET    /api/crud6/{model}/{id}`        → CRUD6Response (single record)
 * - `POST   /api/crud6/{model}`             → CRUD6CreateResponse (create)
 * - `PUT    /api/crud6/{model}/{id}`        → CRUD6EditResponse (full update)
 * - `PUT    /api/crud6/{model}/{id}/{field}` → CRUD6EditResponse (field update)
 * - `DELETE /api/crud6/{model}/{id}`        → CRUD6DeleteResponse (delete)
 *
 * ## Reactive State
 * - `apiLoading` - Boolean indicating if an API call is in progress
 * - `apiError` - Error response from the last failed API call, or null
 * - `formData` - Reactive form data object for create/update operations
 * - `r$` - Regle validation state for formData
 * - `slugLocked` - Boolean controlling automatic slug generation from name
 *
 * @param modelName - Optional model name. If not provided, uses route.params.model
 * @returns Object with reactive state and CRUD methods
 *
 * @example
 * ```typescript
 * // Basic usage in a Vue component
 * import { useCRUD6Api } from '@/composables/useCRUD6Api'
 * 
 * const { 
 *   fetchRow, 
 *   createRow, 
 *   updateRow, 
 *   deleteRow, 
 *   apiLoading, 
 *   formData,
 *   r$ 
 * } = useCRUD6Api('users')
 * 
 * // Fetch a single record
 * const user = await fetchRow('123')
 * console.log(user.name, user.email)
 * 
 * // Create a new record
 * formData.value = { name: 'John Doe', email: 'john@example.com' }
 * await createRow(formData.value)
 * 
 * // Update an existing record
 * await updateRow('123', { name: 'Jane Doe' })
 * 
 * // Update a single field
 * await updateField('123', 'status', 'active')
 * 
 * // Delete a record
 * await deleteRow('123')
 * ```
 * 
 * @example
 * ```typescript
 * // With form validation
 * const { formData, r$, createRow, apiLoading } = useCRUD6Api('products')
 * 
 * async function handleSubmit() {
 *   // Validate form before submission
 *   await r$.value.$validate()
 *   
 *   if (r$.value.$invalid) {
 *     console.log('Form has validation errors')
 *     return
 *   }
 *   
 *   await createRow(formData.value)
 * }
 * ```
 * 
 * @example
 * ```typescript
 * // Using with loading states in template
 * // <template>
 * //   <button :disabled="apiLoading" @click="handleSave">
 * //     {{ apiLoading ? 'Saving...' : 'Save' }}
 * //   </button>
 * //   <div v-if="apiError" class="error">{{ apiError.description }}</div>
 * // </template>
 * 
 * const { apiLoading, apiError, updateRow } = useCRUD6Api('orders')
 * ```
 */
export function useCRUD6Api(modelName?: string) {
    const defaultFormData = (): CRUD6CreateRequest => ({})

    const slugLocked = ref<boolean>(true)
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const formData = ref<CRUD6CreateRequest>(defaultFormData())
    const recordBreadcrumb = ref<string | null>(null)  // Store breadcrumb from API response

    const route = useRoute()
    const model = modelName || (route.params.model as string)
    
    // Use the global schema store to avoid duplicate API calls
    const schemaStore = useCRUD6SchemaStore()

    // Dynamically load the schema file for the current model
    // Uses the global store cache to prevent duplicate API calls
    // The store will wait if a broader context (like 'list,detail,form') is already loading
    async function loadSchema() {
        debugLog('[useCRUD6Api] ===== LOAD SCHEMA FOR VALIDATION =====', {
            model,
            context: 'form',
            purpose: 'validation rules',
            timestamp: new Date().toISOString()
        })
        
        try {
            // Use the store's loadSchema which has caching and waits for related contexts
            // Request 'form' context to get only editable fields with validation
            // If 'list,detail,form' is already loading, this will wait and use cached result
            const schema = await schemaStore.loadSchema(model, false, 'form')
            
            debugLog('[useCRUD6Api] ✅ Schema loaded for validation', {
                model,
                hasSchema: !!schema,
                fieldCount: schema?.fields ? Object.keys(schema.fields).length : 0
            })
            
            return schema || {}
        } catch (error) {
            debugError('[useCRUD6Api] ❌ Schema load error:', error)
            return {}
        }
    }

    // Load the schema and set up the validator
    // Convert CRUD6 JSON schema directly to Regle format, bypassing UserFrosting's
    // YAML-based adapter to prevent unnecessary YAML imports (register.yaml, login.yaml, etc.)
    // Backend validation in PHP remains unchanged and continues to use ServerSideValidator
    const adapter = useCRUD6RegleAdapter()
    const { r$ } = useRegle(formData, adapter.adapt(loadSchema()))

    async function fetchRow(id: string) {
        const url = `/api/crud6/${model}/${toValue(id)}`
        debugLog('[useCRUD6Api] ===== FETCH ROW REQUEST START =====', {
            model,
            id: toValue(id),
            url,
        })

        apiLoading.value = true
        apiError.value = null

        return axios
            .get<CRUD6Response>(url)
            .then((response) => {
                debugLog('[useCRUD6Api] ===== FETCH ROW RESPONSE RECEIVED =====', {
                    model,
                    id: toValue(id),
                    url,
                    status: response.status,
                    statusText: response.statusText,
                    hasData: !!response.data,
                    dataKeys: response.data ? Object.keys(response.data) : [],
                    timestamp: new Date().toISOString()
                })

                debugLog('[useCRUD6Api] Response data structure', {
                    model,
                    id: toValue(id),
                    responseData: response.data,
                    hasDataProperty: response.data && typeof response.data === 'object' && 'data' in response.data,
                })

                // Log the actual record data
                if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                    debugLog('[useCRUD6Api] ===== RECORD DATA =====', {
                        model,
                        id: toValue(id),
                        recordDataKeys: Object.keys(response.data.data as any),
                        recordData: response.data.data,
                        timestamp: new Date().toISOString()
                    })
                    
                    debugLog('[useCRUD6Api] ===== BREADCRUMB FROM API =====', {
                        hasBreadcrumbInResponse: 'breadcrumb' in response.data ? 'YES' : 'NO',
                        breadcrumbValue: response.data.breadcrumb ?? 'NOT PRESENT',
                        breadcrumbType: typeof response.data.breadcrumb,
                    })
                    
                    // Store breadcrumb from API response if available
                    if ('breadcrumb' in response.data && response.data.breadcrumb) {
                        recordBreadcrumb.value = response.data.breadcrumb as string
                        debugLog('[useCRUD6Api] Breadcrumb stored in reactive ref', {
                            breadcrumb: recordBreadcrumb.value
                        })
                    } else {
                        debugLog('[useCRUD6Api] ⚠️ NO BREADCRUMB in API response', {
                            responseKeys: Object.keys(response.data),
                        })
                    }
                    
                    // Attach breadcrumb to the returned data for immediate access
                    const recordData = response.data.data as any
                    if ('breadcrumb' in response.data) {
                        recordData._breadcrumb = response.data.breadcrumb
                        debugLog('[useCRUD6Api] ===== ATTACHED _breadcrumb to recordData =====', {
                            _breadcrumb: recordData._breadcrumb,
                        })
                    } else {
                        debugLog('[useCRUD6Api] ⚠️ NOT attaching _breadcrumb (not in response)', {})
                    }
                    
                    return recordData as CRUD6Response
                }
                
                debugLog('[useCRUD6Api] Returning full response data (no data property found)', {
                    model,
                    id: toValue(id),
                    responseData: response.data,
                })
                
                return response.data
            })
            .catch((err) => {
                debugError('[useCRUD6Api] ===== FETCH ROW REQUEST FAILED =====', {
                    model,
                    id: toValue(id),
                    url,
                    error: err,
                    response: err.response,
                    responseData: err.response?.data,
                    status: err.response?.status,
                })

                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
                debugLog('[useCRUD6Api] Fetch row request completed', {
                    model,
                    id: toValue(id),
                    loading: apiLoading.value,
                    hasError: !!apiError.value,
                })
            })
    }

    async function createRow(data: CRUD6CreateRequest) {
        const url = `/api/crud6/${model}`
        debugLog('[useCRUD6Api] ===== CREATE ROW REQUEST START =====', {
            model,
            url,
            data,
        })

        apiLoading.value = true
        apiError.value = null
        return axios
            .post<CRUD6CreateResponse>(url, data)
            .then((response) => {
                debugLog('[useCRUD6Api] Create row response received', {
                    model,
                    status: response.status,
                    data: response.data,
                    title: response.data.title,
                    description: response.data.description,
                })

                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                debugError('[useCRUD6Api] ===== CREATE ROW REQUEST FAILED =====', {
                    model,
                    url,
                    requestData: data,
                    error: err,
                    response: err.response,
                    responseData: err.response?.data,
                    status: err.response?.status,
                })

                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
                debugLog('[useCRUD6Api] Create row request completed', {
                    model,
                    loading: apiLoading.value,
                    hasError: !!apiError.value,
                })
            })
    }

    async function updateRow(id: string, data: CRUD6EditRequest) {
        const url = `/api/crud6/${model}/${id}`
        debugLog('[useCRUD6Api] ===== UPDATE ROW REQUEST START =====', {
            model,
            id,
            url,
            data,
        })

        apiLoading.value = true
        apiError.value = null
        return axios
            .put<CRUD6EditResponse>(url, data)
            .then((response) => {
                debugLog('[useCRUD6Api] Update row response received', {
                    model,
                    id,
                    status: response.status,
                    data: response.data,
                    title: response.data.title,
                    description: response.data.description,
                })

                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                debugError('[useCRUD6Api] ===== UPDATE ROW REQUEST FAILED =====', {
                    model,
                    id,
                    url,
                    requestData: data,
                    error: err,
                    response: err.response,
                    responseData: err.response?.data,
                    status: err.response?.status,
                    headers: err.response?.headers,
                })

                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
                debugLog('[useCRUD6Api] Update row request completed', {
                    model,
                    id,
                    loading: apiLoading.value,
                    hasError: !!apiError.value,
                })
            })
    }

    async function updateField(id: string, field: string, value: any) {
        const url = `/api/crud6/${model}/${id}/${field}`
        debugLog('[useCRUD6Api] ===== UPDATE FIELD REQUEST START =====', {
            model,
            id,
            field,
            value,
            url,
        })

        apiLoading.value = true
        apiError.value = null
        const data = { [field]: value }
        return axios
            .put<CRUD6EditResponse>(url, data)
            .then((response) => {
                debugLog('[useCRUD6Api] Update field response received', {
                    model,
                    id,
                    field,
                    status: response.status,
                    data: response.data,
                    title: response.data.title,
                    description: response.data.description,
                })

                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                debugError('[useCRUD6Api] ===== UPDATE FIELD REQUEST FAILED =====', {
                    model,
                    id,
                    field,
                    value,
                    url,
                    requestData: data,
                    error: err,
                    response: err.response,
                    responseData: err.response?.data,
                    status: err.response?.status,
                })

                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
                debugLog('[useCRUD6Api] Update field request completed', {
                    model,
                    id,
                    field,
                    loading: apiLoading.value,
                    hasError: !!apiError.value,
                })
            })
    }

    async function deleteRow(id: string) {
        const url = `/api/crud6/${model}/${id}`
        debugLog('[useCRUD6Api] ===== DELETE ROW REQUEST START =====', {
            model,
            id,
            url,
        })

        apiLoading.value = true
        apiError.value = null
        return axios
            .delete<CRUD6DeleteResponse>(url)
            .then((response) => {
                debugLog('[useCRUD6Api] Delete row response received', {
                    model,
                    id,
                    status: response.status,
                    data: response.data,
                    title: response.data.title,
                    description: response.data.description,
                })

                useAlertsStore().push({
                    title: response.data.title,
                    description: response.data.description,
                    style: Severity.Success
                })
            })
            .catch((err) => {
                debugError('[useCRUD6Api] ===== DELETE ROW REQUEST FAILED =====', {
                    model,
                    id,
                    url,
                    error: err,
                    response: err.response,
                    responseData: err.response?.data,
                    status: err.response?.status,
                })

                apiError.value = err.response.data
                throw apiError.value
            })
            .finally(() => {
                apiLoading.value = false
                debugLog('[useCRUD6Api] Delete row request completed', {
                    model,
                    id,
                    loading: apiLoading.value,
                    hasError: !!apiError.value,
                })
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
        slugLocked,
        recordBreadcrumb  // Expose breadcrumb data
    }
}
