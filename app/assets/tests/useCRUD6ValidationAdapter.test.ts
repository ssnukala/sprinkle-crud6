/**
 * CRUD6 Validation Adapter Tests
 * 
 * Unit tests for the direct CRUD6-to-Regle validation adapter
 * Verifies that CRUD6 schemas are correctly converted to Regle validation rules
 * without requiring UserFrosting's YAML-based adapter
 */

import { describe, it, expect } from 'vitest'
import { convertCRUD6ToRegleRules, useCRUD6RegleAdapter } from '../composables/useCRUD6ValidationAdapter'
import type { CRUD6Schema } from '../composables/useCRUD6Schema'

describe('useCRUD6ValidationAdapter', () => {
  describe('convertCRUD6ToRegleRules', () => {
    it('should convert required field to Regle format', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          username: {
            type: 'string',
            label: 'Username',
            required: true
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('username')
      expect(rules.username).toHaveProperty('required')
      expect(typeof rules.username.required).toBe('object')
    })

    it('should convert length validation to Regle minLength/maxLength', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          username: {
            type: 'string',
            label: 'Username',
            validation: {
              length: {
                min: 3,
                max: 50
              }
            }
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('username')
      expect(rules.username).toHaveProperty('minLength')
      expect(rules.username).toHaveProperty('maxLength')
    })

    it('should convert email validation to Regle email', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          email: {
            type: 'email',
            label: 'Email Address'
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('email')
      expect(rules.email).toHaveProperty('email')
    })

    it('should convert numeric range validation to Regle minValue/maxValue', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          age: {
            type: 'integer',
            label: 'Age',
            validation: {
              min: 18,
              max: 120
            }
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('age')
      expect(rules.age).toHaveProperty('minValue')
      expect(rules.age).toHaveProperty('maxValue')
    })

    it('should convert integer type to Regle integer validator', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          quantity: {
            type: 'integer',
            label: 'Quantity'
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('quantity')
      expect(rules.quantity).toHaveProperty('integer')
    })

    it('should handle fields without validation', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          description: {
            type: 'text',
            label: 'Description'
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      // Fields without validation rules should not appear in the rules object
      expect(rules).not.toHaveProperty('description')
    })

    it('should handle null schema', () => {
      const rules = convertCRUD6ToRegleRules(null)
      
      expect(rules).toEqual({})
    })

    it('should handle Promise that resolves to schema', async () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          username: {
            type: 'string',
            label: 'Username',
            required: true
          }
        }
      }

      const schemaPromise = Promise.resolve(schema)
      const rulesPromise = convertCRUD6ToRegleRules(schemaPromise)
      
      expect(rulesPromise).toBeInstanceOf(Promise)
      
      const rules = await rulesPromise
      expect(rules).toHaveProperty('username')
      expect(rules.username).toHaveProperty('required')
    })

    it('should handle Promise that resolves to null', async () => {
      const schemaPromise = Promise.resolve(null)
      const rulesPromise = convertCRUD6ToRegleRules(schemaPromise)
      
      const rules = await rulesPromise
      expect(rules).toEqual({})
    })

    it('should combine multiple validation rules for a field', () => {
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          email: {
            type: 'string',
            label: 'Email',
            required: true,
            validation: {
              email: true,
              length: {
                min: 5,
                max: 100
              }
            }
          }
        }
      }

      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('email')
      expect(rules.email).toHaveProperty('required')
      expect(rules.email).toHaveProperty('email')
      expect(rules.email).toHaveProperty('minLength')
      expect(rules.email).toHaveProperty('maxLength')
    })
  })

  describe('useCRUD6RegleAdapter', () => {
    it('should return adapter with adapt method', () => {
      const adapter = useCRUD6RegleAdapter()
      
      expect(adapter).toHaveProperty('adapt')
      expect(typeof adapter.adapt).toBe('function')
    })

    it('should convert schema through adapt method', () => {
      const adapter = useCRUD6RegleAdapter()
      
      const schema: CRUD6Schema = {
        model: 'test',
        title: 'Test',
        table: 'test',
        primary_key: 'id',
        fields: {
          name: {
            type: 'string',
            label: 'Name',
            required: true
          }
        }
      }

      const rules = adapter.adapt(schema)
      
      expect(rules).toHaveProperty('name')
      expect(rules.name).toHaveProperty('required')
    })
  })

  describe('Validation without YAML imports', () => {
    it('should not import or reference UserFrosting YAML files', () => {
      // This test verifies that the adapter works independently
      // without requiring useRuleSchemaAdapter or YAML files
      
      const schema: CRUD6Schema = {
        model: 'users',
        title: 'Users',
        table: 'users',
        primary_key: 'id',
        fields: {
          user_name: {
            type: 'string',
            label: 'Username',
            required: true,
            validation: {
              length: { min: 3, max: 50 }
            }
          },
          email: {
            type: 'email',
            label: 'Email',
            required: true
          }
        }
      }

      // This should work without any YAML imports
      const rules = convertCRUD6ToRegleRules(schema)
      
      expect(rules).toHaveProperty('user_name')
      expect(rules).toHaveProperty('email')
      expect(rules.user_name.required).toBeDefined()
      expect(rules.user_name.minLength).toBeDefined()
      expect(rules.user_name.maxLength).toBeDefined()
      expect(rules.email.required).toBeDefined()
      expect(rules.email.email).toBeDefined()
    })
  })
})
