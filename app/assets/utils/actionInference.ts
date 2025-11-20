/**
 * Action Property Inference Utilities
 * 
 * Provides convention-based inference for action properties to minimize
 * schema verbosity while maintaining flexibility for custom overrides.
 * 
 * Phase 2 Optimizations:
 * - Infer field from action key pattern
 * - Infer icons from action/field type
 * - Infer labels from field configuration
 * - Infer styles from action pattern
 */

import type { ActionConfig } from '../composables/useCRUD6Schema'

/**
 * Icon mapping for common action patterns and field types
 */
const DEFAULT_ICONS: Record<string, string> = {
    // Action key patterns
    'toggle': 'power-off',
    'password_action': 'key',
    'reset_password': 'envelope',
    'delete': 'trash',
    'edit': 'pen',
    'enable': 'check',
    'disable': 'ban',
    'verify': 'check-circle',
    
    // Field type patterns
    'password': 'key',
    'email': 'envelope',
    'boolean': 'check-circle',
    'date': 'calendar',
    'datetime': 'clock',
    'file': 'file',
    'image': 'image'
}

/**
 * Style mapping for common action patterns and field types
 */
const DEFAULT_STYLES: Record<string, string> = {
    // Action key patterns
    'delete': 'danger',
    'disable': 'danger',
    'enable': 'primary',
    'reset': 'secondary',
    'password': 'warning',
    'toggle': 'default',
    'verify': 'success',
    
    // Field type patterns
    'password': 'warning',
    'boolean': 'default'
}

/**
 * Infer field name from action key using {fieldname}_action pattern
 * 
 * @param actionKey Action key (e.g., "password_action")
 * @returns Inferred field name (e.g., "password") or null
 */
export function inferFieldFromKey(actionKey: string): string | null {
    // Check if key follows {fieldname}_action pattern
    if (actionKey.endsWith('_action')) {
        return actionKey.replace(/_action$/, '')
    }
    return null
}

/**
 * Infer icon for action based on key pattern or field type
 * 
 * @param action Action configuration
 * @param fieldType Type of the field being acted upon
 * @returns Icon name or undefined
 */
export function inferIcon(action: ActionConfig, fieldType?: string): string | undefined {
    // If icon explicitly set, use it
    if (action.icon) {
        return action.icon
    }
    
    // Check action key patterns
    for (const [pattern, icon] of Object.entries(DEFAULT_ICONS)) {
        if (action.key.includes(pattern)) {
            return icon
        }
    }
    
    // Check field type if available
    if (fieldType && DEFAULT_ICONS[fieldType]) {
        return DEFAULT_ICONS[fieldType]
    }
    
    // Default based on action type
    if (action.type === 'api_call') {
        return 'bolt'
    } else if (action.type === 'field_update' && action.toggle) {
        return 'power-off'
    } else if (action.type === 'field_update') {
        return 'pen'
    }
    
    return undefined
}

/**
 * Infer label for action based on field configuration
 * 
 * Priority:
 * 1. Explicit action.label (if provided)
 * 2. Translation key CRUD6.ACTION.EDIT_{FIELD} (if exists)
 * 3. Field label (from field configuration)
 * 4. Humanized field name
 * 
 * @param action Action configuration
 * @param fieldLabel Label from field configuration
 * @param fieldName Name of the field
 * @returns Label or translation key
 */
export function inferLabel(
    action: ActionConfig, 
    fieldLabel?: string,
    fieldName?: string
): string {
    // If label explicitly set, use it
    if (action.label) {
        return action.label
    }
    
    // For field_update actions, try to infer
    if (action.type === 'field_update') {
        const field = action.field || fieldName
        if (field) {
            // Return translation key that will be checked first
            // If not found, will fallback to field label
            return `CRUD6.ACTION.EDIT_${field.toUpperCase()}`
        }
    }
    
    // For toggle actions
    if (action.toggle) {
        const field = action.field || fieldName
        if (field) {
            return `CRUD6.ACTION.TOGGLE_${field.toUpperCase()}`
        }
    }
    
    // For API calls
    if (action.type === 'api_call') {
        return `CRUD6.ACTION.${action.key.toUpperCase()}`
    }
    
    // Fallback to humanized action key
    return action.key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

/**
 * Infer style for action based on key pattern or field type
 * 
 * @param action Action configuration
 * @param fieldType Type of the field being acted upon
 * @returns Style name or undefined
 */
export function inferStyle(action: ActionConfig, fieldType?: string): string | undefined {
    // If style explicitly set, use it
    if (action.style) {
        return action.style
    }
    
    // Check action key patterns
    for (const [pattern, style] of Object.entries(DEFAULT_STYLES)) {
        if (action.key.includes(pattern)) {
            return style
        }
    }
    
    // Check field type if available
    if (fieldType && DEFAULT_STYLES[fieldType]) {
        return DEFAULT_STYLES[fieldType]
    }
    
    // Default based on action type
    if (action.type === 'api_call') {
        return 'secondary'
    } else if (action.toggle) {
        return 'default'
    }
    
    return 'default'
}

/**
 * Get complete action configuration with inferred properties
 * 
 * @param action Base action configuration from schema
 * @param fieldConfig Field configuration (if action relates to a field)
 * @returns Complete action configuration with inferred properties
 */
export function getEnrichedAction(
    action: ActionConfig,
    fieldConfig?: { type?: string; label?: string }
): ActionConfig {
    // Infer field if not specified
    const field = action.field || inferFieldFromKey(action.key)
    
    // Get field info
    const fieldType = fieldConfig?.type
    const fieldLabel = fieldConfig?.label
    
    return {
        ...action,
        field: field || action.field,
        icon: inferIcon(action, fieldType),
        label: inferLabel(action, fieldLabel, field || undefined),
        style: inferStyle(action, fieldType)
    }
}
