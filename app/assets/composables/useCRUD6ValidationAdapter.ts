import type { CRUD6Schema } from './useCRUD6Schema'

/**
 * CRUD6 to UserFrosting Schema Converter.
 * 
 * Converts CRUD6 JSON schema validation rules to the format expected by
 * UserFrosting's useRuleSchemaAdapter, which is designed for YAML schemas.
 * 
 * This allows us to use UserFrosting's existing validation infrastructure
 * while working with our JSON-based schemas, preventing duplicate YAML imports.
 * 
 * Schema Format Conversion:
 * 
 * CRUD6 JSON format:
 * ```json
 * {
 *   "fields": {
 *     "user_name": {
 *       "type": "string",
 *       "required": true,
 *       "validation": {
 *         "required": true,
 *         "length": { "min": 1, "max": 50 },
 *         "unique": true
 *       }
 *     }
 *   }
 * }
 * ```
 * 
 * UserFrosting YAML format (what useRuleSchemaAdapter expects):
 * ```json
 * {
 *   "user_name": {
 *     "validators": {
 *       "required": {},
 *       "length": { "min": 1, "max": 50 }
 *     }
 *   }
 * }
 * ```
 */

/**
 * Convert CRUD6 schema to UserFrosting validator format.
 * 
 * @param schema CRUD6 schema object or Promise that resolves to schema
 * @returns Schema object in UserFrosting validator format, or Promise of that
 */
export function convertCRUD6ToUFValidatorFormat(schema: CRUD6Schema | Promise<CRUD6Schema | null> | null): any | Promise<any> {
    // If schema is a Promise, convert it when it resolves
    if (schema instanceof Promise) {
        return schema.then(resolvedSchema => {
            if (!resolvedSchema) return {}
            return convertSchemaSync(resolvedSchema)
        })
    }

    // If null or no schema, return empty object
    if (!schema) {
        return {}
    }

    return convertSchemaSync(schema)
}

/**
 * Synchronously convert CRUD6 schema to UF format.
 * 
 * @param schema CRUD6 schema object
 * @returns Schema in UF validator format
 */
function convertSchemaSync(schema: CRUD6Schema): any {
    if (!schema.fields) {
        return {}
    }

    const ufSchema: any = {}

    // Convert each field
    for (const [fieldName, field] of Object.entries(schema.fields)) {
        const validators: any = {}

        // Handle 'required' - can be at field level or in validation object
        if (field.required === true || field.validation?.required === true) {
            validators.required = {}
        }

        // Copy validation rules from field.validation to validators
        if (field.validation) {
            // Length validation
            if (field.validation.length) {
                validators.length = { ...field.validation.length }
            }

            // Email validation
            if (field.validation.email || field.type === 'email') {
                validators.email = {}
            }

            // URL validation
            if (field.validation.url || field.type === 'url') {
                validators.url = {}
            }

            // Numeric range validation
            if (field.validation.min !== undefined || field.validation.max !== undefined) {
                validators.range = {}
                if (field.validation.min !== undefined) {
                    validators.range.min = field.validation.min
                }
                if (field.validation.max !== undefined) {
                    validators.range.max = field.validation.max
                }
            }

            // Pattern (regex) validation
            if (field.validation.pattern) {
                validators.regex = {
                    regex: field.validation.pattern
                }
            }

            // Matches validation (compare with another field)
            if (field.validation.matches) {
                validators.matches = {
                    field: field.validation.matches
                }
            }

            // Integer validation
            if (field.type === 'integer' || field.validation.integer) {
                validators.integer = {}
            }

            // Numeric validation
            if (['number', 'decimal', 'float'].includes(field.type) || field.validation.numeric) {
                validators.numeric = {}
            }

            // Unique validation (note: this is typically server-side only)
            if (field.validation.unique) {
                validators.unique = {}
            }

            // Array validation
            if (field.validation.array) {
                validators.array = {}
            }

            // No whitespace validation
            if (field.validation.no_whitespace) {
                validators.no_whitespace = {}
            }

            // Username validation
            if (field.validation.username) {
                validators.username = {}
            }

            // No leading whitespace
            if (field.validation.no_leading_whitespace) {
                validators.no_leading_whitespace = {}
            }

            // No trailing whitespace
            if (field.validation.no_trailing_whitespace) {
                validators.no_trailing_whitespace = {}
            }

            // Telephone validation
            if (field.validation.telephone || field.type === 'tel') {
                validators.telephone = {}
            }

            // URI validation
            if (field.validation.uri) {
                validators.uri = {}
            }
        }

        // Only add field to schema if it has validators
        if (Object.keys(validators).length > 0) {
            ufSchema[fieldName] = {
                validators: validators
            }
        }
    }

    return ufSchema
}

/**
 * Composable for converting CRUD6 schemas to UF validator format.
 * 
 * Provides a conversion function that can be used before passing schemas
 * to UserFrosting's useRuleSchemaAdapter.
 */
export function useCRUD6ToUFSchemaConverter() {
    return {
        convert: convertCRUD6ToUFValidatorFormat
    }
}

