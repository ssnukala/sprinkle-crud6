/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import type { CRUD6Schema } from './useCRUD6Schema'
import {
    required,
    minLength,
    maxLength,
    email,
    url,
    minValue,
    maxValue,
    integer,
    numeric,
    withMessage,
    type RegleRuleDefinition
} from '@regle/rules'
import type { InferRegleRoot } from '@regle/core'

/**
 * CRUD6 Direct Regle Validation Adapter.
 * 
 * Converts CRUD6 JSON schema validation rules directly to Regle validator format,
 * bypassing UserFrosting's YAML-based validation system to prevent unnecessary
 * YAML file imports (register.yaml, login.yaml, profile-settings.yaml, etc.).
 * 
 * This adapter eliminates the import chain that was causing YAML imports:
 * OLD: CRUD6 JSON → UF YAML format → useRuleSchemaAdapter (imports YAMLs) → Regle
 * NEW: CRUD6 JSON → Direct Regle rules
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
 * Regle format (what useRegle expects):
 * ```typescript
 * {
 *   user_name: {
 *     required: withMessage(required, 'This field is required'),
 *     minLength: withMessage(minLength(1), 'Minimum 1 characters'),
 *     maxLength: withMessage(maxLength(50), 'Maximum 50 characters')
 *   }
 * }
 * ```
 */

/**
 * Convert CRUD6 schema directly to Regle validation rules.
 * This bypasses UserFrosting's YAML-based adapter to prevent YAML imports.
 * 
 * @param schema CRUD6 schema object or Promise that resolves to schema
 * @returns Regle validation rules object, or Promise of that
 */
export function convertCRUD6ToRegleRules(schema: CRUD6Schema | Promise<CRUD6Schema | null> | null): any | Promise<any> {
    // If schema is a Promise, convert it when it resolves
    if (schema instanceof Promise) {
        return schema.then(resolvedSchema => {
            if (!resolvedSchema) return {}
            return convertSchemaToRegleRules(resolvedSchema)
        })
    }

    // If null or no schema, return empty object
    if (!schema) {
        return {}
    }

    return convertSchemaToRegleRules(schema)
}

/**
 * Synchronously convert CRUD6 schema to Regle validation rules.
 * 
 * @param schema CRUD6 schema object
 * @returns Regle validation rules
 */
function convertSchemaToRegleRules(schema: CRUD6Schema): any {
    if (!schema.fields) {
        return {}
    }

    const rules: Record<string, any> = {}

    // Convert each field to Regle validators
    for (const [fieldName, field] of Object.entries(schema.fields)) {
        const fieldRules: Record<string, any> = {}

        // Get field label for error messages
        const fieldLabel = field.label || fieldName

        // Handle 'required' - can be at field level or in validation object
        if (field.required === true || field.validation?.required === true) {
            fieldRules.required = withMessage(required, `${fieldLabel} is required`)
        }

        // Type-based validations (outside validation object so they work even without validation)
        // Email validation - from type or explicit validation
        if (field.type === 'email' || field.validation?.email) {
            fieldRules.email = withMessage(email, `${fieldLabel} must be a valid email address`)
        }

        // URL validation - from type or explicit validation
        if (field.type === 'url' || field.validation?.url) {
            fieldRules.url = withMessage(url, `${fieldLabel} must be a valid URL`)
        }

        // Integer validation - from type or explicit validation
        if (field.type === 'integer' || field.validation?.integer) {
            fieldRules.integer = withMessage(integer, `${fieldLabel} must be an integer`)
        }

        // Numeric validation - from type or explicit validation
        if (['number', 'decimal', 'float'].includes(field.type) || field.validation?.numeric) {
            fieldRules.numeric = withMessage(numeric, `${fieldLabel} must be a number`)
        }

        // Process validation rules from field.validation
        if (field.validation) {
            // Length validation
            if (field.validation.length) {
                if (field.validation.length.min !== undefined) {
                    fieldRules.minLength = withMessage(
                        minLength(field.validation.length.min),
                        `${fieldLabel} must be at least ${field.validation.length.min} characters`
                    )
                }
                if (field.validation.length.max !== undefined) {
                    fieldRules.maxLength = withMessage(
                        maxLength(field.validation.length.max),
                        `${fieldLabel} must be at most ${field.validation.length.max} characters`
                    )
                }
            }

            // Numeric range validation
            if (field.validation.min !== undefined) {
                fieldRules.minValue = withMessage(
                    minValue(field.validation.min),
                    `${fieldLabel} must be at least ${field.validation.min}`
                )
            }
            if (field.validation.max !== undefined) {
                fieldRules.maxValue = withMessage(
                    maxValue(field.validation.max),
                    `${fieldLabel} must be at most ${field.validation.max}`
                )
            }

            // Pattern (regex) validation
            if (field.validation.pattern) {
                const regex = new RegExp(field.validation.pattern)
                fieldRules.pattern = withMessage(
                    (value: any) => !value || regex.test(String(value)),
                    `${fieldLabel} format is invalid`
                )
            }

            // Note: Some validations like 'unique', 'matches' are server-side only
            // or require custom implementation. They are not included in client-side rules.
        }

        // Only add field to rules if it has validators
        if (Object.keys(fieldRules).length > 0) {
            rules[fieldName] = fieldRules
        }
    }

    return rules
}

/**
 * DEPRECATED: Use convertCRUD6ToRegleRules instead.
 * 
 * Convert CRUD6 schema to UserFrosting validator format.
 * This function is kept for backward compatibility but should not be used
 * as it leads to YAML imports through useRuleSchemaAdapter.
 * 
 * @param schema CRUD6 schema object or Promise that resolves to schema
 * @returns Schema object in UserFrosting validator format, or Promise of that
 * @deprecated Use convertCRUD6ToRegleRules to avoid YAML imports
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
 * Composable for converting CRUD6 schemas directly to Regle validation rules.
 * 
 * This adapter bypasses UserFrosting's YAML-based validation system to prevent
 * unnecessary YAML file imports while maintaining identical validation behavior.
 * 
 * **Important:** This only affects frontend validation. Backend validation in PHP
 * is completely separate and uses ServerSideValidator with RequestSchema.
 * Both frontend and backend read from the same CRUD6 JSON schemas.
 */
export function useCRUD6RegleAdapter() {
    return {
        /**
         * Convert CRUD6 schema to Regle validation rules.
         * Use this instead of useRuleSchemaAdapter().adapt() to avoid YAML imports.
         */
        adapt: convertCRUD6ToRegleRules
    }
}

/**
 * DEPRECATED: Use useCRUD6RegleAdapter instead.
 * 
 * Composable for converting CRUD6 schemas to UF validator format.
 * This leads to YAML imports through useRuleSchemaAdapter.
 * 
 * @deprecated Use useCRUD6RegleAdapter to avoid YAML imports
 */
export function useCRUD6ToUFSchemaConverter() {
    return {
        convert: convertCRUD6ToUFValidatorFormat
    }
}

