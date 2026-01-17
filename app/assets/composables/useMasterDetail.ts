/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { ref } from 'vue'
import axios from 'axios'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore } from '@userfrosting/sprinkle-core/stores'
import { debugLog, debugWarn, debugError } from '../utils/debug'

/**
 * Interface for detail record in master-detail operations
 */
export interface DetailRecord {
    [key: string]: any
    _action?: 'create' | 'update' | 'delete' // Internal flag for tracking changes
    _originalIndex?: number // Internal flag for tracking position
}

/**
 * Interface for master-detail save request
 */
export interface MasterDetailSaveRequest {
    master: Record<string, any>
    details: DetailRecord[]
}

/**
 * Interface for master-detail save response
 */
export interface MasterDetailSaveResponse {
    success: boolean
    message: string
    title: string
    description: string
    master_id?: string | number
    details_created?: number
    details_updated?: number
    details_deleted?: number
}

/**
 * Vue composable for master-detail CRUD operations.
 * 
 * Handles saving master records along with their associated detail records
 * in a single transaction. Supports both one-to-many (e.g., Order -> OrderDetails)
 * and many-to-many (e.g., Product <-> Categories via pivot) relationships.
 * 
 * @param masterModel - The name of the master model (e.g., 'orders')
 * @param detailModel - The name of the detail model (e.g., 'order_details')
 * @param foreignKey - The foreign key field in detail records (e.g., 'order_id')
 */
export function useMasterDetail(
    masterModel: string,
    detailModel: string,
    foreignKey: string
) {
    const apiLoading = ref<boolean>(false)
    const apiError = ref<ApiErrorResponse | null>(null)
    const alertsStore = useAlertsStore()

    /**
     * Save master record with its detail records
     * 
     * @param masterId - ID of master record (null for create, number for update)
     * @param masterData - Master record data
     * @param detailRecords - Array of detail records with _action flags
     * @returns Promise<MasterDetailSaveResponse>
     */
    async function saveMasterWithDetails(
        masterId: string | number | null,
        masterData: Record<string, any>,
        detailRecords: DetailRecord[]
    ): Promise<MasterDetailSaveResponse> {
        debugLog('[useMasterDetail] ===== SAVE MASTER WITH DETAILS START =====', {
            masterModel,
            detailModel,
            masterId,
            masterData,
            detailCount: detailRecords.length,
        })

        apiLoading.value = true
        apiError.value = null

        try {
            // Step 1: Save or update master record
            let masterRecordId = masterId
            const masterUrl = masterId 
                ? `/api/crud6/${masterModel}/${masterId}`
                : `/api/crud6/${masterModel}`
            
            debugLog('[useMasterDetail] Saving master record', {
                url: masterUrl,
                method: masterId ? 'PUT' : 'POST',
                data: masterData,
            })

            const masterResponse = masterId
                ? await axios.put(masterUrl, masterData)
                : await axios.post(masterUrl, masterData)

            debugLog('[useMasterDetail] Master record saved', {
                status: masterResponse.status,
                data: masterResponse.data,
            })

            // Extract master ID from response
            if (!masterId && masterResponse.data) {
                // For create operations, get the ID from response
                if (masterResponse.data.data?.id) {
                    masterRecordId = masterResponse.data.data.id
                } else if (masterResponse.data.id) {
                    masterRecordId = masterResponse.data.id
                }
            }

            debugLog('[useMasterDetail] Master record ID', { masterRecordId })

            // Step 2: Process detail records
            let detailsCreated = 0
            let detailsUpdated = 0
            let detailsDeleted = 0

            for (const detail of detailRecords) {
                const action = detail._action || 'create'
                const detailData = { ...detail }
                
                // Remove internal flags
                delete detailData._action
                delete detailData._originalIndex
                
                // Set foreign key to master ID
                detailData[foreignKey] = masterRecordId

                try {
                    if (action === 'delete' && detail.id) {
                        // Delete existing detail record
                        await axios.delete(`/api/crud6/${detailModel}/${detail.id}`)
                        detailsDeleted++
                        debugLog('[useMasterDetail] Detail record deleted', { id: detail.id })
                    } else if (action === 'update' && detail.id) {
                        // Update existing detail record
                        await axios.put(`/api/crud6/${detailModel}/${detail.id}`, detailData)
                        detailsUpdated++
                        debugLog('[useMasterDetail] Detail record updated', { id: detail.id })
                    } else if (action === 'create') {
                        // Create new detail record
                        await axios.post(`/api/crud6/${detailModel}`, detailData)
                        detailsCreated++
                        debugLog('[useMasterDetail] Detail record created', { data: detailData })
                    }
                } catch (detailError: any) {
                    debugError('[useMasterDetail] Error processing detail record', {
                        action,
                        detail,
                        error: detailError,
                    })
                    // Continue processing other details even if one fails
                }
            }

            debugLog('[useMasterDetail] Details processing complete', {
                created: detailsCreated,
                updated: detailsUpdated,
                deleted: detailsDeleted,
            })

            // Step 3: Build success response
            const response: MasterDetailSaveResponse = {
                success: true,
                title: masterId ? 'Record Updated' : 'Record Created',
                message: masterId 
                    ? `Successfully updated ${masterModel} and ${detailsCreated + detailsUpdated + detailsDeleted} detail records`
                    : `Successfully created ${masterModel} with ${detailsCreated} detail records`,
                description: masterId 
                    ? `Updated master record and ${detailsCreated + detailsUpdated + detailsDeleted} detail records`
                    : `Created master record with ${detailsCreated} detail records`,
                master_id: masterRecordId,
                details_created: detailsCreated,
                details_updated: detailsUpdated,
                details_deleted: detailsDeleted,
            }

            // Show success alert
            alertsStore.push({
                title: response.title,
                description: response.description,
                severity: Severity.SUCCESS,
            })

            debugLog('[useMasterDetail] ===== SAVE COMPLETE =====', response)
            return response

        } catch (error: any) {
            debugError('[useMasterDetail] ===== SAVE FAILED =====', {
                error,
                response: error.response,
            })

            apiError.value = error.response?.data || {
                message: 'Failed to save master-detail records',
                severity: Severity.DANGER,
            }

            // Show error alert
            alertsStore.push({
                title: 'Save Failed',
                description: apiError.value.message || 'An error occurred while saving',
                severity: Severity.DANGER,
            })

            throw apiError.value
        } finally {
            apiLoading.value = false
        }
    }

    /**
     * Load detail records for a master record
     * 
     * @param masterId - ID of the master record
     * @returns Promise<DetailRecord[]>
     */
    async function loadDetails(masterId: string | number): Promise<DetailRecord[]> {
        debugLog('[useMasterDetail] Loading details', { masterId })

        apiLoading.value = true
        apiError.value = null

        try {
            const url = `/api/crud6/${masterModel}/${masterId}/${detailModel}`
            const response = await axios.get(url)

            debugLog('[useMasterDetail] Details loaded', {
                count: response.data?.rows?.length || 0,
            })

            // Extract rows from Sprunje response format
            return response.data?.rows || []

        } catch (error: any) {
            debugError('[useMasterDetail] Failed to load details', { error })
            apiError.value = error.response?.data
            throw apiError.value
        } finally {
            apiLoading.value = false
        }
    }

    return {
        apiLoading,
        apiError,
        saveMasterWithDetails,
        loadDetails,
    }
}
