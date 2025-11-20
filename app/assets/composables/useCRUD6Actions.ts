import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCRUD6Api } from './useCRUD6Api'
import type { ActionConfig } from './useCRUD6Schema'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore, useTranslator } from '@userfrosting/sprinkle-core/stores'
import { debugLog, debugWarn, debugError } from '../utils/debug'

/**
 * Strip HTML tags from a string.
 * Used to clean confirmation messages for native browser confirm() dialog.
 * 
 * @param html String that may contain HTML tags
 * @returns Plain text string with HTML tags removed
 */
function stripHtmlTags(html: string): string {
    // Create a temporary div element to parse HTML
    const tmp = document.createElement('div')
    tmp.innerHTML = html
    // Return text content (which strips all HTML tags)
    return tmp.textContent || tmp.innerText || ''
}

/**
 * Vue composable for executing custom CRUD6 actions.
 * 
 * Provides methods to execute schema-defined custom actions like
 * field updates, modal displays, route navigation, and API calls.
 * 
 * Supports i18n for confirm and success messages. Messages can be either:
 * - Translation keys (e.g., "CRUD6.ACTION.RESET_PASSWORD.CONFIRM")
 * - Plain text strings for backward compatibility
 * 
 * Note: Confirmation dialogs should be handled by components (e.g., ConfirmActionModal)
 * rather than using native browser confirm(). Use executeActionWithoutConfirm() when
 * the component handles confirmation.
 */
export function useCRUD6Actions(model?: string) {
    const router = useRouter()
    const { updateField } = useCRUD6Api()
    const alertsStore = useAlertsStore()
    const translator = useTranslator()
    
    const loading = ref(false)
    const error = ref<ApiErrorResponse | null>(null)

    /**
     * Execute a custom action based on its configuration (DEPRECATED)
     * 
     * @deprecated Use executeActionWithoutConfirm() and handle confirmation in component
     * This method uses native browser confirm() which doesn't render HTML properly.
     * For better UX, use ConfirmActionModal component with executeActionWithoutConfirm().
     */
    async function executeAction(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {
        // Check for confirmation using native browser dialog
        // Note: Native browser confirm() doesn't render HTML, so we strip HTML tags
        // DEPRECATED: Use ConfirmActionModal component instead
        if (action.confirm) {
            // Translate the confirmation message if it's a translation key
            let confirmMessage = translator.translate(action.confirm, currentRecord)
            
            // Strip HTML tags from confirmation message since native confirm() doesn't render HTML
            // This prevents showing raw HTML like <strong>{{name}}</strong> to users
            confirmMessage = stripHtmlTags(confirmMessage)
            
            if (!confirm(confirmMessage)) {
                return false
            }
        }

        return executeActionWithoutConfirm(action, recordId, currentRecord)
    }

    /**
     * Execute a custom action without confirmation prompt.
     * 
     * This method should be used when the component handles confirmation
     * (e.g., through ConfirmActionModal). It skips the native browser confirm()
     * and directly executes the action.
     * 
     * @param action The action configuration from schema
     * @param recordId The ID of the record to act on
     * @param currentRecord The current record data for field updates
     * @returns Promise<boolean> True if action succeeded, false otherwise
     */
    async function executeActionWithoutConfirm(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {

        loading.value = true
        error.value = null

        try {
            switch (action.type) {
                case 'field_update':
                    return await executeFieldUpdate(action, recordId, currentRecord)
                
                case 'password_update':
                    return await executePasswordUpdate(action, recordId, currentRecord)
                
                case 'route':
                    return executeRouteNavigation(action, recordId)
                
                case 'api_call':
                    return await executeApiCall(action, recordId)
                
                case 'modal':
                    // Modal handling would be done by the parent component
                    // This just returns success to indicate modal should be shown
                    return true
                
                default:
                    debugError('Unknown action type:', action.type)
                    return false
            }
        } catch (err: any) {
            debugError('Action execution failed:', err)
            error.value = err.response?.data || {
                title: 'Action Failed',
                description: 'Failed to execute action: ' + action.label
            }
            alertsStore.push({
                title: error.value.title || 'Action Failed',
                description: error.value.description || 'Action failed',
                style: Severity.Danger
            })
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
            debugError('Field update action requires a field property')
            return false
        }

        let newValue: any

        // Check if this is a field update with password data from PasswordInputModal
        // The modal passes the password in currentRecord.password
        if (currentRecord && currentRecord.password && 
            (action.field === 'password' || currentRecord[action.field] === undefined)) {
            newValue = currentRecord.password
        } else if (action.toggle && currentRecord) {
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
            debugError('Field update action requires either toggle, value, or password property')
            return false
        }

        if (!updateField) {
            debugError('updateField function not available')
            return false
        }

        try {
            await updateField(String(recordId), action.field, newValue)
            
            // Show success message - translate if it's a translation key
            const successMsg = action.success_message 
                ? translator.translate(action.success_message) 
                : translator.translate('CRUD6.ACTION.SUCCESS', { action: translator.translate(action.label) })
            alertsStore.push({
                title: translator.translate('CRUD6.ACTION.SUCCESS_TITLE') || 'Success',
                description: successMsg,
                style: Severity.Success
            })
            
            return true
        } catch (err) {
            debugError('Field update failed:', err)
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
            debugError('Route action requires a route property')
            return false
        }

        if (!model) {
            debugError('Route action requires model to be specified in useCRUD6Actions')
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
            debugError('API call action requires an endpoint property')
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
                ? translator.translate(action.success_message) 
                : translator.translate('CRUD6.ACTION.SUCCESS', { action: translator.translate(action.label) })
            alertsStore.push({
                title: translator.translate('CRUD6.ACTION.SUCCESS_TITLE') || 'Success',
                description: successMsg,
                style: Severity.Success
            })

            return true
        } catch (err) {
            debugError('API call failed:', err)
            throw err
        }
    }

    /**
     * Execute a password update action.
     * 
     * This method updates a password field. The password value should be provided
     * in the currentRecord parameter (typically from PasswordInputModal).
     * 
     * @param action The action configuration
     * @param recordId The record ID
     * @param currentRecord Current record data, must include 'password' property
     * @returns Promise<boolean> True if successful
     */
    async function executePasswordUpdate(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {
        const passwordField = action.password_field || 'password'
        
        if (!currentRecord || !currentRecord.password) {
            debugError('Password update action requires password in currentRecord')
            return false
        }

        if (!updateField) {
            debugError('updateField function not available')
            return false
        }

        try {
            await updateField(String(recordId), passwordField, currentRecord.password)
            
            // Show success message - translate if it's a translation key
            const successMsg = action.success_message 
                ? translator.translate(action.success_message) 
                : translator.translate('CRUD6.ACTION.SUCCESS', { action: translator.translate(action.label) })
            alertsStore.push({
                title: translator.translate('CRUD6.ACTION.SUCCESS_TITLE') || 'Success',
                description: successMsg,
                style: Severity.Success
            })
            
            return true
        } catch (err) {
            debugError('Password update failed:', err)
            throw err
        }
    }

    return {
        loading,
        error,
        executeAction,
        executeActionWithoutConfirm,
        executeFieldUpdate,
        executePasswordUpdate,
        executeRouteNavigation,
        executeApiCall
    }
}
