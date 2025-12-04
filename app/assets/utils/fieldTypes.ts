/**
 * CRUD6 Field Type Utilities
 * 
 * Provides utilities for handling different CRUD6 field types including:
 * - Field type to HTML input type mapping
 * - Pattern validation for specific field types
 * - Textarea configuration parsing
 * - Extensible field type registry
 */

/**
 * Field type to HTML5 input type mapping
 */
export const FIELD_TYPE_MAP: Record<string, string> = {
    'email': 'email',
    'url': 'url',
    'phone': 'tel',
    'zip': 'text',
    'password': 'password',
    'date': 'date',
    'datetime': 'datetime-local',
    'number': 'number',
    'integer': 'number',
    'decimal': 'number',
    'float': 'number',
    'boolean': 'checkbox',
    'boolean-yn': 'select',
    'boolean-tgl': 'checkbox',
    'boolean-toggle': 'checkbox',
    'address': 'text',
}

/**
 * Default validation patterns for field types
 */
export const FIELD_PATTERN_MAP: Record<string, string> = {
    'zip': '\\d{5}(-\\d{4})?',  // US ZIP: 5 digits or 9 digits with dash
    'phone': '\\d{3}-\\d{3}-\\d{4}',  // US phone: XXX-XXX-XXXX
}

/**
 * Predefined regex patterns that can be referenced by name
 * Can be extended with custom patterns as needed
 */
export const NAMED_PATTERNS: Record<string, string> = {
    // US Formats
    'us_zip_5': '^\\d{5}$',
    'us_zip_9': '^\\d{5}(-\\d{4})?$',
    'us_phone': '^\\d{3}-\\d{3}-\\d{4}$',
    'us_phone_flexible': '^\\(?\d{3}\\)?[-\\.\\s]?\\d{3}[-\\.\\s]?\\d{4}$',
    
    // Common Patterns
    'alphanumeric': '^[a-zA-Z0-9]+$',
    'alphanumeric_dash': '^[a-zA-Z0-9-_]+$',
    'slug': '^[a-z0-9-]+$',
    'hex_color': '^#[0-9A-Fa-f]{6}$',
    'ipv4': '^(?:\\d{1,3}\\.){3}\\d{1,3}$',
    'url_safe': '^[a-zA-Z0-9._~:/?#\\[\\]@!$&\'()*+,;=-]+$',
}

/**
 * Textarea configuration result
 */
export interface TextareaConfig {
    rows: number
    cols: number | undefined
}

/**
 * Parse textarea type format (e.g., "textarea-r5c60")
 * 
 * Supports formats:
 * - "text" or "textarea" - default rows (6), no cols
 * - "textarea-r5" - 5 rows, default cols
 * - "textarea-r3c60" - 3 rows, 60 cols
 * - "text-r2c50" - 2 rows, 50 cols
 * 
 * @param type - Field type string
 * @returns Textarea configuration with rows and optional cols
 */
export function parseTextareaConfig(type: string): TextareaConfig {
    if (!type || type === 'text' || type === 'textarea') {
        return { rows: 6, cols: undefined }
    }
    
    // Match patterns like "textarea-r5c60", "textarea-r5", "text-r3c40"
    const match = type.match(/^(?:text|textarea)(?:-r(\d+))?(?:c(\d+))?$/)
    if (match) {
        const rows = match[1] ? parseInt(match[1]) : 6
        const cols = match[2] ? parseInt(match[2]) : undefined
        return { rows, cols }
    }
    
    return { rows: 6, cols: undefined }
}

/**
 * Get HTML input type for a CRUD6 field type
 * 
 * Maps CRUD6 field types to appropriate HTML5 input types
 * for better native validation and mobile keyboard support.
 * 
 * @param fieldType - CRUD6 field type
 * @returns HTML5 input type
 */
export function getInputType(fieldType: string): string {
    return FIELD_TYPE_MAP[fieldType] || 'text'
}

/**
 * Get pattern attribute for field validation
 * 
 * Priority:
 * 1. Custom regex pattern from validation.regex
 * 2. Named pattern reference from validation.regex
 * 3. Default pattern for field type from FIELD_PATTERN_MAP
 * 
 * @param fieldType - CRUD6 field type
 * @param validation - Validation configuration object
 * @returns Pattern string or undefined
 */
export function getInputPattern(fieldType: string, validation?: any): string | undefined {
    // If validation has regex, use that
    if (validation?.regex) {
        // String reference to named pattern
        if (typeof validation.regex === 'string') {
            // Check if it's a named pattern reference
            if (NAMED_PATTERNS[validation.regex]) {
                return NAMED_PATTERNS[validation.regex]
            }
            // Otherwise use as-is (custom regex)
            return validation.regex
        }
        // Object with pattern property
        if (validation.regex.pattern) {
            return validation.regex.pattern
        }
    }
    
    // Default patterns for field types
    return FIELD_PATTERN_MAP[fieldType]
}

/**
 * Check if a field type is a textarea variant
 * 
 * @param fieldType - CRUD6 field type
 * @returns True if field type is a textarea variant
 */
export function isTextareaType(fieldType: string): boolean {
    return fieldType === 'text' || 
           fieldType === 'textarea' || 
           (fieldType && fieldType.indexOf('textarea-') === 0) || 
           (fieldType && fieldType.indexOf('text-') === 0)
}

/**
 * Check if a field type should use standard text input
 * 
 * @param fieldType - CRUD6 field type
 * @returns True if field type should use text input
 */
export function isTextInputType(fieldType: string): boolean {
    const types = ['string', 'email', 'url', 'phone', 'zip']
    return types.indexOf(fieldType) !== -1 || !fieldType
}

/**
 * Check if a field type is numeric
 * 
 * @param fieldType - CRUD6 field type
 * @returns True if field type is numeric
 */
export function isNumericType(fieldType: string): boolean {
    const types = ['number', 'integer', 'decimal', 'float']
    return types.indexOf(fieldType) !== -1
}

/**
 * Check if a field type is a boolean variant
 * 
 * @param fieldType - CRUD6 field type
 * @returns True if field type is boolean
 */
export function isBooleanType(fieldType: string): boolean {
    return fieldType === 'boolean' || 
           fieldType === 'boolean-yn' || 
           fieldType === 'boolean-tgl' ||
           fieldType === 'boolean-toggle'
}

/**
 * Check if a field type is an address type
 * 
 * @param fieldType - CRUD6 field type
 * @returns True if field type is address
 */
export function isAddressType(fieldType: string): boolean {
    return fieldType === 'address'
}

/**
 * Get boolean field UI type
 * 
 * @param fieldType - CRUD6 field type
 * @returns 'toggle' | 'checkbox' | 'select' | null
 */
export function getBooleanUIType(fieldType: string): 'toggle' | 'checkbox' | 'select' | null {
    if (fieldType === 'boolean-yn') {
        return 'select'
    }
    if (fieldType === 'boolean-tgl' || fieldType === 'boolean-toggle') {
        return 'toggle'
    }
    if (fieldType === 'boolean') {
        return 'checkbox'
    }
    return null
}

/**
 * Register a custom field type mapping
 * 
 * Allows extending the field type system with custom types
 * 
 * @param fieldType - Custom field type name
 * @param htmlType - HTML5 input type
 * @param pattern - Optional default pattern for validation
 */
export function registerFieldType(fieldType: string, htmlType: string, pattern?: string): void {
    FIELD_TYPE_MAP[fieldType] = htmlType
    if (pattern) {
        FIELD_PATTERN_MAP[fieldType] = pattern
    }
}

/**
 * Register a named pattern
 * 
 * Allows extending the named pattern library
 * 
 * @param name - Pattern name (e.g., 'ca_postal_code')
 * @param pattern - Regex pattern string
 */
export function registerNamedPattern(name: string, pattern: string): void {
    NAMED_PATTERNS[name] = pattern
}

/**
 * Get all registered field types
 * 
 * @returns Array of registered field type names
 */
export function getRegisteredFieldTypes(): string[] {
    return Object.keys(FIELD_TYPE_MAP)
}

/**
 * Get all named patterns
 * 
 * @returns Object with all named patterns
 */
export function getNamedPatterns(): Record<string, string> {
    return { ...NAMED_PATTERNS }
}

/**
 * Get appropriate autocomplete attribute value based on field name and type
 * 
 * Follows HTML5 autocomplete specification to help browsers provide
 * better autofill suggestions and reduce warnings in DevTools.
 * 
 * @param fieldKey - Field name/key (e.g., 'user_name', 'email', 'first_name')
 * @param fieldType - CRUD6 field type (e.g., 'email', 'password', 'string')
 * @returns Autocomplete attribute value or 'off' for fields that shouldn't autocomplete
 * 
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
 */
export function getAutocompleteAttribute(fieldKey: string, fieldType?: string): string {
    const lowerKey = fieldKey.toLowerCase()
    
    // Password fields
    if (fieldType === 'password' || lowerKey.includes('password')) {
        return 'new-password' // Use new-password for create/update forms
    }
    
    // Email fields
    if (fieldType === 'email' || lowerKey === 'email' || lowerKey.includes('email')) {
        return 'email'
    }
    
    // Name fields
    if (lowerKey === 'name' || lowerKey === 'full_name' || lowerKey === 'fullname') {
        return 'name'
    }
    if (lowerKey === 'first_name' || lowerKey === 'firstname' || lowerKey === 'given_name') {
        return 'given-name'
    }
    if (lowerKey === 'last_name' || lowerKey === 'lastname' || lowerKey === 'family_name' || lowerKey === 'surname') {
        return 'family-name'
    }
    
    // Username
    if (lowerKey === 'user_name' || lowerKey === 'username' || lowerKey === 'login') {
        return 'username'
    }
    
    // Organization
    if (lowerKey === 'organization' || lowerKey === 'company' || lowerKey === 'org') {
        return 'organization'
    }
    
    // Address fields
    if (lowerKey === 'address' || lowerKey === 'street' || lowerKey === 'addr_line1' || lowerKey === 'address_line1') {
        return 'address-line1'
    }
    if (lowerKey === 'addr_line2' || lowerKey === 'address_line2') {
        return 'address-line2'
    }
    if (lowerKey === 'city' || lowerKey === 'locality') {
        return 'address-level2'
    }
    if (lowerKey === 'state' || lowerKey === 'province' || lowerKey === 'region') {
        return 'address-level1'
    }
    if (lowerKey === 'zip' || lowerKey === 'postal_code' || lowerKey === 'postcode' || lowerKey === 'zipcode') {
        return 'postal-code'
    }
    if (lowerKey === 'country') {
        return 'country-name'
    }
    
    // Phone fields
    if (fieldType === 'phone' || lowerKey === 'phone' || lowerKey === 'telephone' || lowerKey === 'tel' || lowerKey.includes('phone')) {
        return 'tel'
    }
    
    // URL fields
    if (fieldType === 'url' || lowerKey === 'url' || lowerKey === 'website') {
        return 'url'
    }
    
    // Date fields
    if (fieldType === 'date' && lowerKey.includes('birth')) {
        return 'bday'
    }
    
    // Default: turn off autocomplete for generic fields
    // This prevents browser warnings while not providing unhelpful suggestions
    return 'off'
}
