import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCRUD6Api } from './useCRUD6Api'
import type { ActionConfig } from './useCRUD6Schema'
import { Severity, type ApiErrorResponse } from '@userfrosting/sprinkle-core/interfaces'
import { useAlertsStore, useTranslator } from '@userfrosting/sprinkle-core/stores'
import { debugLog, debugWarn, debugError } from '../utils/debug'
import { getEnrichedAction, inferFieldFromKey } from '../utils/actionInference'

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
 * Check if an action represents a password field update.
 * 
 * Password fields are detected by:
 * 1. Explicitly marked with requires_password_input flag
 * 2. Field name is 'password' or matches password_field property
 * 3. Legacy password_update type (deprecated)
 * 
 * This helper function centralizes password field detection logic
 * to ensure consistency across different parts of the action system.
 * 
 * @param action The action configuration
 * @param field The resolved field name (may be inferred from action key)
 * @returns True if this is a password field update
 */
export function isPasswordFieldAction(action: ActionConfig, field?: string | null): boolean {
    return action.requires_password_input === true ||
           field === 'password' ||
           (!!action.password_field && action.password_field === field) ||
           action.type === 'password_update'
}

/**
 * Vue composable for executing custom CRUD6 actions.
 * 
 * Provides methods to execute schema-defined custom actions like
 * field updates, modal displays, route navigation, and API calls.
 * 
 * ## Features
 * - Automatic action enrichment (infers field, icon, label from key)
 * - i18n support for confirm and success messages
 * - Field toggle support for boolean fields
 * - Password field handling with confirmation modal
 * 
 * ## Action Types
 * - `field_update` - Update a field value (including toggles and password)
 * - `modal` - Show a modal component
 * - `route` - Navigate to a route
 * - `api_call` - Make a custom API call
 * 
 * ## Reactive State
 * - `loading` - Boolean indicating if an action is being executed
 * - `error` - Error response from the last failed action, or null
 *
 * @param model - Optional model name for route navigation and API calls
 * @returns Object with reactive state and action methods
 *
 * @example
 * ```typescript
 * // Basic usage - execute a toggle action
 * import { useCRUD6Actions } from '@/composables/useCRUD6Actions'
 * 
 * const { executeActionWithoutConfirm, loading } = useCRUD6Actions('users')
 * 
 * const toggleAction = {
 *   key: 'toggle_enabled',
 *   type: 'field_update',
 *   field: 'enabled',
 *   toggle: true
 * }
 * 
 * await executeActionWithoutConfirm(toggleAction, '123', currentRecord)
 * ```
 *
 * @example
 * ```typescript
 * // Password update with modal confirmation
 * const { executeActionWithoutConfirm } = useCRUD6Actions('users')
 * 
 * const passwordAction = {
 *   key: 'password_action',
 *   type: 'field_update',
 *   field: 'password',
 *   requires_password_input: true
 * }
 * 
 * // After PasswordInputModal provides the new password:
 * await executeActionWithoutConfirm(passwordAction, '123', { password: 'newPassword123' })
 * ```
 *
 * @example
 * ```typescript
 * // Navigate to a detail route
 * const { executeActionWithoutConfirm } = useCRUD6Actions('orders')
 * 
 * const viewAction = {
 *   key: 'view_details',
 *   type: 'route',
 *   route: 'order-detail'
 * }
 * 
 * executeActionWithoutConfirm(viewAction, '456')
 * // Navigates to: /orders/456/detail
 * ```
 *
 * @example
 * ```typescript
 * // Custom API call action
 * const { executeActionWithoutConfirm } = useCRUD6Actions('invoices')
 * 
 * const sendEmailAction = {
 *   key: 'send_email',
 *   type: 'api_call',
 *   endpoint: '/api/invoices/{id}/send',
 *   method: 'POST',
 *   success_message: 'INVOICE.EMAIL_SENT'
 * }
 * 
 * await executeActionWithoutConfirm(sendEmailAction, '789')
 * ```
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
            // Enrich action with inferred properties (field, icon, label, style, permission)
            // Pass model for permission inference. Field config would need to be passed from 
            // schema context, which happens in the Info.vue component before calling this.
            // For executions that go through Info.vue, the action is already enriched.
            // This additional enrichment ensures consistent behavior for direct calls.
            const enrichedAction = getEnrichedAction(action, undefined, model)
            
            switch (enrichedAction.type) {
                case 'field_update':
                    return await executeFieldUpdate(enrichedAction, recordId, currentRecord)
                
                case 'password_update':
                    // DEPRECATED: password_update is now handled by field_update with requires_password_input flag
                    // This case is maintained for backward compatibility only
                    debugWarn('[useCRUD6Actions] DEPRECATED: "password_update" type is deprecated. Use "field_update" with "requires_password_input: true" instead.')
                    return await executeFieldUpdate(enrichedAction, recordId, currentRecord)
                
                case 'route':
                    return executeRouteNavigation(enrichedAction, recordId)
                
                case 'api_call':
                    return await executeApiCall(enrichedAction, recordId)
                
                case 'modal':
                    // Modal handling would be done by the parent component
                    // This just returns success to indicate modal should be shown
                    return true
                
                default:
                    debugError('Unknown action type:', enrichedAction.type)
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
     * 
     * Handles all field updates including:
     * - Boolean toggle fields
     * - Explicit value updates
     * - Password fields (requires password data in currentRecord.password)
     * - Legacy password_update type (now unified into field_update)
     * 
     * Password field detection uses the isPasswordFieldAction() helper for consistency.
     * @see isPasswordFieldAction
     */
    async function executeFieldUpdate(
        action: ActionConfig,
        recordId: string | number,
        currentRecord?: any
    ): Promise<boolean> {
        // Infer field from action key if not specified (e.g., "password_action" -> "password")
        const field = action.field || inferFieldFromKey(action.key)
        
        if (!field) {
            debugError('Field update action requires a field property or inferrable key pattern')
            return false
        }

        let newValue: any

        // Use helper function for password field detection
        const isPasswordField = isPasswordFieldAction(action, field)

        if (isPasswordField && currentRecord?.password) {
            // Password update - value comes from PasswordInputModal
            newValue = currentRecord.password
            debugLog('[useCRUD6Actions] Executing password field update for field:', field)
        } else if (action.toggle && currentRecord) {
            // Toggle boolean field - handle null/undefined values
            const currentValue = currentRecord[field]
            
            // If field doesn't exist or is null/undefined, default to false then toggle to true
            if (currentValue === null || currentValue === undefined) {
                newValue = true
            } else {
                newValue = !currentValue
            }
            debugLog('[useCRUD6Actions] Toggling boolean field:', field, 'from', currentValue, 'to', newValue)
        } else if (action.value !== undefined) {
            // Set specific value
            newValue = action.value
            debugLog('[useCRUD6Actions] Setting field:', field, 'to value:', newValue)
        } else if (!isPasswordField) {
            // Only error if not a password field waiting for input
            debugError('Field update action requires either toggle, value, or password property')
            return false
        } else {
            // Password field but no password provided
            debugError('Password field update requires password in currentRecord')
            return false
        }

        if (!updateField) {
            debugError('updateField function not available')
            return false
        }

        try {
            await updateField(String(recordId), field, newValue)
            
            // Show success message - translate if it's a translation key
            const actionLabel = action.label || `Update ${field}`
            const successMsg = action.success_message 
                ? translator.translate(action.success_message) 
                : translator.translate('CRUD6.ACTION.SUCCESS', { action: translator.translate(actionLabel) })
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
     * 
     * If no endpoint is specified, automatically generates one based on:
     * - Model name (from useCRUD6Actions initialization)
     * - Action key
     * Format: /api/crud6/{model}/{id}/a/{actionKey}
     */
    async function executeApiCall(
        action: ActionConfig,
        recordId: string | number
    ): Promise<boolean> {
        // Auto-generate endpoint if not provided
        let endpoint: string
        if (action.endpoint) {
            endpoint = action.endpoint.replace('{id}', String(recordId))
        } else if (model) {
            // Infer endpoint from model and action key
            endpoint = `/api/crud6/${model}/${recordId}/a/${action.key}`
        } else {
            debugError('API call action requires either an endpoint property or model context')
            return false
        }

        const method = action.method || 'POST'
        
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
     * @deprecated Use executeFieldUpdate with action.requires_password_input = true instead.
     * This method is maintained for backward compatibility only.
     * 
     * New schema format:
     * ```json
     * {
     *   "key": "password_action",
     *   "type": "field_update",
     *   "field": "password",
     *   "requires_password_input": true
     * }
     * ```
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
        debugWarn('[useCRUD6Actions] DEPRECATED: executePasswordUpdate() is deprecated. Use executeFieldUpdate() with requires_password_input: true instead.')
        
        // Convert to field_update format and delegate
        const fieldUpdateAction: ActionConfig = {
            ...action,
            type: 'field_update',
            field: action.password_field || action.field || 'password',
            requires_password_input: true
        }
        
        return executeFieldUpdate(fieldUpdateAction, recordId, currentRecord)
    }

    return {
        loading,
        error,
        executeAction,
        executeActionWithoutConfirm,
        executeFieldUpdate,
        /** @deprecated Use executeFieldUpdate with requires_password_input: true instead */
        executePasswordUpdate,
        executeRouteNavigation,
        executeApiCall
    }
}
