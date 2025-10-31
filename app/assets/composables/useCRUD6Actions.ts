import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCRUD6Api } from './useCRUD6Api'
import type { ActionConfig } from './useCRUD6Schema'
import type { ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlerts } from '@userfrosting/sprinkle-core/stores'
import { useI18n } from 'vue-i18n'

/**
 * Vue composable for executing custom CRUD6 actions.
 * 
 * Provides methods to execute schema-defined custom actions like
 * field updates, modal displays, route navigation, and API calls.
 * 
 * Supports i18n for confirm and success messages. Messages can be either:
 * - Translation keys (e.g., "CRUD6.ACTION.RESET_PASSWORD.CONFIRM")
 * - Plain text strings for backward compatibility
 */
export function useCRUD6Actions(model?: string) {
    const router = useRouter()
    const { updateField } = useCRUD6Api()
    const alerts = useAlerts()
    const { t } = useI18n()
    
    const loading = ref(false)
    const error = ref<ApiErrorResponse | null>(null)

    /**
     * Execute a custom action based on its configuration
     */
    async function executeAction(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {
        // Check for confirmation using native browser dialog
        // Note: In a production application, consider using a UIKit modal
        // for better user experience and consistency
        if (action.confirm) {
            // Translate the confirmation message if it's a translation key
            const confirmMessage = t(action.confirm)
            if (!confirm(confirmMessage)) {
                return false
            }
        }

        loading.value = true
        error.value = null

        try {
            switch (action.type) {
                case 'field_update':
                    return await executeFieldUpdate(action, recordId, currentRecord)
                
                case 'route':
                    return executeRouteNavigation(action, recordId)
                
                case 'api_call':
                    return await executeApiCall(action, recordId)
                
                case 'modal':
                    // Modal handling would be done by the parent component
                    // This just returns success to indicate modal should be shown
                    return true
                
                default:
                    console.error('Unknown action type:', action.type)
                    return false
            }
        } catch (err: any) {
            console.error('Action execution failed:', err)
            error.value = err.response?.data || {
                title: 'Action Failed',
                description: 'Failed to execute action: ' + action.label
            }
            alerts.addError(error.value.description || 'Action failed')
            return false
        } finally {
            loading.value = false
        }
    }

    /**
     * Execute a field update action
     */
    async function executeFieldUpdate(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {
        if (!action.field) {
            console.error('Field update action requires a field property')
            return false
        }

        let newValue: any

        if (action.toggle && currentRecord) {
            // Toggle boolean field - handle null/undefined values
            const currentValue = currentRecord[action.field]
            
            // If field doesn't exist or is null/undefined, default to false then toggle to true
            if (currentValue === null || currentValue === undefined) {
                newValue = true
            } else {
                newValue = !currentValue
            }
        } else if (action.value !== undefined) {
            // Set specific value
            newValue = action.value
        } else {
            console.error('Field update action requires either toggle or value property')
            return false
        }

        if (!updateField) {
            console.error('updateField function not available')
            return false
        }

        try {
            await updateField(String(recordId), action.field, newValue)
            
            // Show success message - translate if it's a translation key
            const successMsg = action.success_message 
                ? t(action.success_message) 
                : t('CRUD6.ACTION.SUCCESS', { action: t(action.label) })
            alerts.addSuccess(successMsg)
            
            return true
        } catch (err) {
            console.error('Field update failed:', err)
            throw err
        }
    }

    /**
     * Execute a route navigation action
     */
    function executeRouteNavigation(
        action: ActionConfig,
        recordId: string | number
    ): boolean {
        if (!action.route) {
            console.error('Route action requires a route property')
            return false
        }

        if (!model) {
            console.error('Route action requires model to be specified in useCRUD6Actions')
            return false
        }

        router.push({
            name: action.route,
            params: { id: String(recordId), model: model }
        })

        return true
    }

    /**
     * Execute an API call action
     */
    async function executeApiCall(
        action: ActionConfig,
        recordId: string | number
    ): Promise<boolean> {
        if (!action.endpoint) {
            console.error('API call action requires an endpoint property')
            return false
        }

        const method = action.method || 'POST'
        const endpoint = action.endpoint.replace('{id}', String(recordId))
        
        try {
            await axios.request({
                method,
                url: endpoint,
                headers: {
                    'Content-Type': 'application/json'
                }
            })

            // Show success message - translate if it's a translation key
            const successMsg = action.success_message 
                ? t(action.success_message) 
                : t('CRUD6.ACTION.SUCCESS', { action: t(action.label) })
            alerts.addSuccess(successMsg)

            return true
        } catch (err) {
            console.error('API call failed:', err)
            throw err
        }
    }

    return {
        loading,
        error,
        executeAction,
        executeFieldUpdate,
        executeRouteNavigation,
        executeApiCall
    }
}
