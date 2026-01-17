/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Test Fixtures Loader Tests
 * 
 * Tests the unified fixture loader that uses centralized test data
 * from the integration testing framework (examples/schema/ and
 * .github/config/integration-test-models.json)
 */

import { describe, it, expect } from 'vitest'
import { 
  loadSchemaFixture, 
  loadDataFixture, 
  loadSingleRecordFixture,
  getModelConfig,
  getEditableFields,
  getViewableFields,
  getListableFields,
  filterFields,
  getAvailableModels,
  getModelApiPaths
} from './fixtures'

describe('Fixtures Loader (Unified Data)', () => {
  describe('loadSchemaFixture', () => {
    it('loads users schema from examples/schema/', () => {
      const schema = loadSchemaFixture('users')
      
      expect(schema).toBeDefined()
      expect(schema.model).toBe('users')
      expect(schema.title).toBeDefined()
      expect(schema.fields).toBeDefined()
      expect(schema.fields.user_name).toBeDefined()
      expect(schema.fields.email).toBeDefined()
    })

    it('loads groups schema from examples/schema/', () => {
      const schema = loadSchemaFixture('groups')
      
      expect(schema).toBeDefined()
      expect(schema.model).toBe('groups')
      expect(schema.fields).toBeDefined()
      expect(schema.fields.slug).toBeDefined()
    })

    it('loads products schema from examples/schema/', () => {
      const schema = loadSchemaFixture('products')
      
      expect(schema).toBeDefined()
      expect(schema.model).toBe('products')
      expect(schema.fields).toBeDefined()
      expect(schema.fields.name).toBeDefined()
    })
  })

  describe('loadDataFixture', () => {
    it('loads users test data from integration-test-models.json', () => {
      const data = loadDataFixture('users')
      
      expect(data).toBeDefined()
      expect(Array.isArray(data)).toBe(true)
      expect(data.length).toBeGreaterThan(0)
      expect(data[0]).toHaveProperty('user_name')
      expect(data[0]).toHaveProperty('email')
      expect(data[0]).toHaveProperty('first_name')
      // Data comes from create_payload in integration-test-models.json
      expect(data[0].user_name).toBe('apitest')
    })

    it('loads groups test data from integration-test-models.json', () => {
      const data = loadDataFixture('groups')
      
      expect(data).toBeDefined()
      expect(Array.isArray(data)).toBe(true)
      expect(data.length).toBeGreaterThan(0)
      expect(data[0]).toHaveProperty('slug')
      expect(data[0]).toHaveProperty('name')
      // Data comes from create_payload
      expect(data[0].slug).toBe('api_test_group')
    })

    it('loads roles test data from integration-test-models.json', () => {
      const data = loadDataFixture('roles')
      
      expect(data).toBeDefined()
      expect(Array.isArray(data)).toBe(true)
      expect(data[0]).toHaveProperty('slug')
      expect(data[0].slug).toBe('api_test_role')
    })
  })

  describe('loadSingleRecordFixture', () => {
    it('loads first record by default', () => {
      const record = loadSingleRecordFixture('users')
      
      expect(record).toBeDefined()
      expect(record.id).toBe(2) // test_id from config
      expect(record.user_name).toBe('apitest')
    })

    it('loads specific record by index', () => {
      const record = loadSingleRecordFixture('groups', 0)
      
      expect(record).toBeDefined()
      expect(record.slug).toBe('api_test_group')
    })
  })

  describe('getModelConfig', () => {
    it('loads model configuration from integration-test-models.json', () => {
      const config = getModelConfig('users')
      
      expect(config).toBeDefined()
      expect(config.name).toBe('users')
      expect(config.api_prefix).toBe('/api/crud6')
      expect(config.test_id).toBe(2)
      expect(config.create_payload).toBeDefined()
    })

    it('includes relationships in config', () => {
      const config = getModelConfig('users')
      
      expect(config.relationships).toBeDefined()
      expect(Array.isArray(config.relationships)).toBe(true)
      expect(config.relationships.length).toBeGreaterThan(0)
    })
  })

  describe('Schema Field Helpers', () => {
    it('gets editable fields from schema', () => {
      const schema = loadSchemaFixture('users')
      const editableFields = getEditableFields(schema)
      
      expect(editableFields).toContain('user_name')
      expect(editableFields).toContain('email')
      expect(editableFields).toContain('first_name')
      expect(editableFields).not.toContain('id') // id is not editable
    })

    it('gets viewable fields from schema', () => {
      const schema = loadSchemaFixture('users')
      const viewableFields = getViewableFields(schema)
      
      expect(viewableFields).toContain('id')
      expect(viewableFields).toContain('user_name')
      expect(viewableFields).toContain('email')
    })

    it('gets listable fields from schema', () => {
      const schema = loadSchemaFixture('users')
      const listableFields = getListableFields(schema)
      
      // id is NOT listable in users schema - only appears in detail view
      expect(listableFields).not.toContain('id')
      expect(listableFields).toContain('user_name')
      expect(listableFields).toContain('email')
      expect(listableFields).toContain('first_name')
      expect(listableFields).toContain('last_name')
    })
  })

  describe('filterFields', () => {
    it('filters record to include only specified fields', () => {
      const record = loadSingleRecordFixture('users')
      const filtered = filterFields(record, ['id', 'user_name', 'email'])
      
      expect(filtered).toEqual({
        id: 2,
        user_name: 'apitest',
        email: 'apitest@example.com'
      })
      expect(filtered).not.toHaveProperty('first_name')
      expect(filtered).not.toHaveProperty('last_name')
    })
  })

  describe('getAvailableModels', () => {
    it('returns list of available models', () => {
      const models = getAvailableModels()
      
      expect(Array.isArray(models)).toBe(true)
      expect(models).toContain('users')
      expect(models).toContain('groups')
      expect(models).toContain('roles')
      expect(models).toContain('permissions')
      expect(models).toContain('activities')
    })
  })

  describe('getModelApiPaths', () => {
    it('returns API paths for model', () => {
      const paths = getModelApiPaths('users')
      
      expect(paths.schema).toBe('/api/crud6/users/schema')
      expect(paths.list).toBe('/api/crud6/users')
      expect(paths.create).toBe('/api/crud6/users')
      expect(paths.single(2)).toBe('/api/crud6/users/2')
      expect(paths.update(2)).toBe('/api/crud6/users/2')
      expect(paths.delete(2)).toBe('/api/crud6/users/2')
    })
  })

  describe('Integration with Schema', () => {
    it('uses schema to filter data for list view', () => {
      const schema = loadSchemaFixture('users')
      const data = loadDataFixture('users')
      const listableFields = getListableFields(schema)
      
      // Filter first record to show only listable fields
      const listViewRecord = filterFields(data[0], listableFields)
      
      // id is NOT in list view for users schema - only in detail
      expect(listViewRecord).not.toHaveProperty('id')
      expect(listViewRecord).toHaveProperty('user_name')
      expect(listViewRecord).toHaveProperty('email')
    })

    it('uses schema to filter data for form view', () => {
      const schema = loadSchemaFixture('users')
      const record = loadSingleRecordFixture('users')
      const editableFields = getEditableFields(schema)
      
      // Filter record to show only editable fields
      const formData = filterFields(record, editableFields)
      
      expect(formData).toHaveProperty('user_name')
      expect(formData).toHaveProperty('email')
      expect(formData).not.toHaveProperty('id') // id is not editable
    })

    it('combines model config with schema for complete test setup', () => {
      const schema = loadSchemaFixture('users')
      const config = getModelConfig('users')
      const data = loadSingleRecordFixture('users')
      
      // Verify we have all pieces needed for comprehensive testing
      expect(schema.fields).toBeDefined()
      expect(config.api_prefix).toBeDefined()
      expect(config.relationships).toBeDefined()
      expect(data.user_name).toBe(config.create_payload.user_name)
    })
  })
})
