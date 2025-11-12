/**
 * CRUD6 Field Renderer Composable
 * 
 * Provides a centralized field rendering system that determines which
 * component or input type to use based on field configuration.
 * 
 * This eliminates the need for components to have extensive conditional
 * logic for different field types.
 */

import type { Component } from 'vue'
import { 
    getInputType, 
    getInputPattern, 
    parseTextareaConfig,
    isBooleanType,
    getBooleanUIType,
    isAddressType,
    isTextareaType,
    isTextInputType,
    isNumericType
} from '../utils/fieldTypes'

/**
 * Field configuration interface
 */
export interface FieldConfig {
    type: string
    label?: string
    placeholder?: string
    required?: boolean
    readonly?: boolean
    disabled?: boolean
    validation?: any
    rows?: number
    cols?: number
    address_fields?: any
    [key: string]: any
}

/**
 * Field renderer props interface
 */
export interface FieldRendererProps {
    field: FieldConfig
    fieldKey: string
    modelValue: any
    formData?: any
    components?: {
        AutoLookup?: Component
        GoogleAddress?: Component
    }
    handlers?: {
        onAddressSelected?: (data: any) => void
        [key: string]: any
    }
}

/**
 * Determine field renderer type
 */
export function getFieldRendererType(fieldType: string): string {
    // Special component types
    if (fieldType === 'smartlookup') return 'smartlookup'
    if (isAddressType(fieldType)) return 'address'
    
    // Standard input types
    if (isTextInputType(fieldType)) return 'text-input'
    if (isNumericType(fieldType)) return 'number-input'
    if (fieldType === 'password') return 'password-input'
    if (fieldType === 'date') return 'date-input'
    if (fieldType === 'datetime') return 'datetime-input'
    if (isTextareaType(fieldType)) return 'textarea'
    if (isBooleanType(fieldType)) {
        const uiType = getBooleanUIType(fieldType)
        if (uiType === 'select') return 'boolean-select'
        if (uiType === 'toggle') return 'boolean-toggle'
        return 'boolean-checkbox'
    }
    
    // Default
    return 'text-input'
}

/**
 * Get field component attributes
 */
export function getFieldAttributes(
    field: FieldConfig,
    fieldKey: string,
    modelValue: any
): Record<string, any> {
    const baseAttrs: Record<string, any> = {
        id: fieldKey,
        'aria-label': field.label || fieldKey,
        'data-test': fieldKey,
        required: field.required,
        disabled: field.readonly || field.disabled,
        placeholder: field.placeholder || field.label || fieldKey
    }
    
    // Add type-specific attributes
    const rendererType = getFieldRendererType(field.type)
    
    switch (rendererType) {
        case 'text-input':
            return {
                ...baseAttrs,
                class: 'uk-input',
                type: getInputType(field.type || 'string'),
                pattern: getInputPattern(field.type, field.validation)
            }
            
        case 'number-input':
            return {
                ...baseAttrs,
                class: 'uk-input',
                type: 'number',
                step: field.type === 'integer' ? '1' : 'any'
            }
            
        case 'password-input':
            return {
                ...baseAttrs,
                class: 'uk-input',
                type: 'password'
            }
            
        case 'date-input':
            return {
                ...baseAttrs,
                class: 'uk-input',
                type: 'date'
            }
            
        case 'datetime-input':
            return {
                ...baseAttrs,
                class: 'uk-input',
                type: 'datetime-local'
            }
            
        case 'textarea':
            const config = parseTextareaConfig(field.type)
            return {
                ...baseAttrs,
                class: 'uk-textarea',
                rows: config.rows,
                cols: config.cols
            }
            
        case 'boolean-select':
            return {
                ...baseAttrs,
                class: 'uk-select'
            }
            
        case 'boolean-checkbox':
            return {
                id: fieldKey,
                class: 'uk-checkbox',
                type: 'checkbox',
                'data-test': fieldKey,
                disabled: field.readonly || field.disabled
            }
            
        case 'boolean-toggle':
            return {
                id: fieldKey,
                class: 'uk-checkbox',
                type: 'checkbox',
                'data-test': fieldKey,
                disabled: field.readonly || field.disabled
            }
            
        default:
            return baseAttrs
    }
}

/**
 * Render field configuration
 */
export interface FieldRenderConfig {
    rendererType: string
    element: string
    component?: Component
    attributes: Record<string, any>
    wrapInLabel?: boolean
    labelText?: string
    options?: Array<{ value: any; label: string }>
}

/**
 * Get field render configuration
 */
export function getFieldRenderConfig(
    field: FieldConfig,
    fieldKey: string,
    modelValue: any,
    components?: FieldRendererProps['components']
): FieldRenderConfig {
    const rendererType = getFieldRendererType(field.type)
    const attributes = getFieldAttributes(field, fieldKey, modelValue)
    
    const config: FieldRenderConfig = {
        rendererType,
        element: 'input',
        attributes
    }
    
    switch (rendererType) {
        case 'smartlookup':
            if (components?.AutoLookup) {
                config.component = components.AutoLookup
                config.attributes = {
                    model: field.lookup_model || field.lookup?.model || field.model,
                    'id-field': field.lookup_id || field.lookup?.id || field.id || 'id',
                    'display-field': field.lookup_desc || field.lookup?.desc || field.desc || 'name',
                    placeholder: field.placeholder,
                    required: field.required,
                    disabled: field.readonly
                }
            }
            break
            
        case 'address':
            if (components?.GoogleAddress) {
                config.component = components.GoogleAddress
                config.attributes = {
                    'field-key': fieldKey,
                    placeholder: field.placeholder || field.label || 'Enter address',
                    required: field.required,
                    disabled: field.readonly,
                    'address-fields': field.address_fields
                }
            }
            break
            
        case 'textarea':
            config.element = 'textarea'
            break
            
        case 'boolean-select':
            config.element = 'select'
            config.options = [
                { value: true, label: 'Yes' },
                { value: false, label: 'No' }
            ]
            break
            
        case 'boolean-checkbox':
            config.element = 'input'
            config.wrapInLabel = true
            config.labelText = field.label || fieldKey
            break
            
        case 'boolean-toggle':
            config.element = 'input'
            config.wrapInLabel = false
            config.labelText = field.label || fieldKey
            break
    }
    
    return config
}

/**
 * Use CRUD6 Field Renderer
 * 
 * Returns configuration for rendering a field based on its type
 */
export function useCRUD6FieldRenderer() {
    return {
        getFieldRendererType,
        getFieldAttributes,
        getFieldRenderConfig
    }
}
