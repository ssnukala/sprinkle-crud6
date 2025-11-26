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
 * - Infer permissions from action type and model (NEW)
 * 
 * All inferred properties can be overridden by explicit values in the schema.
 */

import type { ActionConfig } from '../composables/useCRUD6Schema'

/**
 * Icon mapping for common action patterns and field types
 * 
 * Priority:
 * 1. Explicit action.icon (if provided)
 * 2. Match against action key patterns
 * 3. Match against field type patterns
 * 4. Default based on action type
 */
const DEFAULT_ICONS: Record<string, string> = {
    // Action key patterns - common CRUD operations
    'toggle': 'power-off',
    'password_action': 'key',
    'reset_password': 'envelope',
    'delete': 'trash',
    'edit': 'pen',
    'enable': 'check',
    'disable': 'ban',
    'verify': 'check-circle',
    
    // Action key patterns - common business operations
    'approve': 'check-circle',
    'reject': 'times-circle',
    'archive': 'archive',
    'restore': 'undo',
    'export': 'file-export',
    'import': 'file-import',
    'send': 'paper-plane',
    'sync': 'sync',
    'refresh': 'sync-alt',
    'print': 'print',
    'download': 'download',
    'upload': 'upload',
    'search': 'search',
    'filter': 'filter',
    'sort': 'sort',
    'copy': 'copy',
    'duplicate': 'clone',
    'lock': 'lock',
    'unlock': 'unlock',
    'suspend': 'pause',
    'activate': 'play',
    'deactivate': 'stop',
    
    // Field type patterns
    'password': 'key',
    'email': 'envelope',
    'boolean': 'check-circle',
    'date': 'calendar',
    'datetime': 'clock',
    'file': 'file',
    'image': 'image',
    'phone': 'phone',
    'address': 'map-marker-alt',
    'url': 'link',
    'money': 'dollar-sign',
    'currency': 'dollar-sign',
}

/**
 * Style mapping for common action patterns and field types
 * 
 * Maps action key patterns to UIkit button styles:
 * - 'danger': Destructive actions (delete, disable, reject, etc.)
 * - 'warning': Actions requiring caution (password, archive, etc.)
 * - 'success': Positive actions (approve, verify, enable, etc.)
 * - 'primary': Main actions (edit, update, etc.)
 * - 'secondary': Neutral actions (export, download, etc.)
 * - 'default': Default button style
 */
const DEFAULT_STYLES: Record<string, string> = {
    // Dangerous/destructive actions
    'delete': 'danger',
    'disable': 'danger',
    'reject': 'danger',
    'revoke': 'danger',
    'suspend': 'danger',
    'deactivate': 'danger',
    'remove': 'danger',
    'terminate': 'danger',
    'block': 'danger',
    
    // Warning/caution actions
    'password': 'warning',
    'archive': 'warning',
    'cancel': 'warning',
    'lock': 'warning',
    
    // Success/positive actions
    'enable': 'success',
    'approve': 'success',
    'verify': 'success',
    'activate': 'success',
    'restore': 'success',
    'confirm': 'success',
    'accept': 'success',
    'unlock': 'success',
    
    // Primary actions
    'edit': 'primary',
    'update': 'primary',
    'save': 'primary',
    'create': 'primary',
    'add': 'primary',
    
    // Secondary/neutral actions
    'reset': 'secondary',
    'export': 'secondary',
    'import': 'secondary',
    'download': 'secondary',
    'sync': 'secondary',
    'refresh': 'secondary',
    'copy': 'secondary',
    'duplicate': 'secondary',
    
    // Default for toggles and other
    'toggle': 'default',
    
    // Field type patterns
    'boolean': 'default',
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
 * Infer permission for action based on action type and optional model name
 * 
 * Permission patterns:
 * - field_update: `update_{model}_field` or fallback to `update_field`
 * - api_call: `update_{model}_field` or fallback to `update_field`
 * - modal: `update_{model}` or fallback to `update`
 * - route: `view_{model}` or fallback to `view`
 * 
 * @param action Action configuration
 * @param model Optional model name for permission prefix
 * @returns Permission string or undefined
 */
export function inferPermission(action: ActionConfig, model?: string): string | undefined {
    // If permission explicitly set, use it
    if (action.permission) {
        return action.permission
    }
    
    // Define permission patterns by action type
    const permissionPatterns: Record<string, { withModel: string; fallback: string }> = {
        'field_update': { withModel: `update_${model}_field`, fallback: 'update_field' },
        'password_update': { withModel: `update_${model}_field`, fallback: 'update_field' },
        'api_call': { withModel: `update_${model}_field`, fallback: 'update_field' },
        'modal': { withModel: `update_${model}`, fallback: 'update' },
        'route': { withModel: `view_${model}`, fallback: 'view' },
    }
    
    const pattern = permissionPatterns[action.type]
    if (pattern) {
        return model ? pattern.withModel : pattern.fallback
    }
    
    return undefined
}

/**
 * Get complete action configuration with inferred properties
 * 
 * @param action Base action configuration from schema
 * @param fieldConfig Field configuration (if action relates to a field)
 * @param model Optional model name for permission inference
 * @returns Complete action configuration with inferred properties
 */
export function getEnrichedAction(
    action: ActionConfig,
    fieldConfig?: { type?: string; label?: string },
    model?: string
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
        style: inferStyle(action, fieldType),
        permission: inferPermission(action, model)
    }
}
