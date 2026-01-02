/**
 * Example Test Using Fixtures
 * 
 * This test demonstrates how to use JSON fixtures from examples/test/vitest/
 * instead of inline mock data. This makes tests more realistic and maintainable.
 */

import { describe, it, expect } from 'vitest'
import { 
  loadSchemaFixture, 
  loadDataFixture, 
  loadSingleRecordFixture,
  getEditableFields,
  getViewableFields,
  getListableFields,
  filterFields
} from '../fixtures'

describe('Fixtures Loader', () => {
  describe('loadSchemaFixture', () => {
    it('loads products schema from fixture', () => {
      const schema = loadSchemaFixture('products')
      
      expect(schema).toBeDefined()
      expect(schema.model).toBe('products')
      expect(schema.title).toBe('Product Management')
      expect(schema.fields).toBeDefined()
      expect(schema.fields.name).toBeDefined()
      expect(schema.fields.name.type).toBe('string')
    })

    it('loads users schema from fixture', () => {
      const schema = loadSchemaFixture('users')
      
      expect(schema).toBeDefined()
      expect(schema.model).toBe('users')
      expect(schema.title).toBe('User Management')
      expect(schema.fields).toBeDefined()
      expect(schema.fields.user_name).toBeDefined()
    })
  })

  describe('loadDataFixture', () => {
    it('loads products data from fixture', () => {
      const data = loadDataFixture('products')
      
      expect(data).toBeDefined()
      expect(Array.isArray(data)).toBe(true)
      expect(data.length).toBeGreaterThan(0)
      expect(data[0]).toHaveProperty('id')
      expect(data[0]).toHaveProperty('name')
      expect(data[0]).toHaveProperty('sku')
      expect(data[0]).toHaveProperty('price')
    })

    it('loads users data from fixture', () => {
      const data = loadDataFixture('users')
      
      expect(data).toBeDefined()
      expect(Array.isArray(data)).toBe(true)
      expect(data.length).toBeGreaterThan(0)
      expect(data[0]).toHaveProperty('id')
      expect(data[0]).toHaveProperty('user_name')
      expect(data[0]).toHaveProperty('email')
    })
  })

  describe('loadSingleRecordFixture', () => {
    it('loads first record by default', () => {
      const record = loadSingleRecordFixture('products')
      
      expect(record).toBeDefined()
      expect(record.id).toBe(1)
      expect(record.name).toBe('Test Product 1')
    })

    it('loads specific record by index', () => {
      const record = loadSingleRecordFixture('products', 1)
      
      expect(record).toBeDefined()
      expect(record.id).toBe(2)
      expect(record.name).toBe('Test Product 2')
    })
  })

  describe('Schema Field Helpers', () => {
    it('gets editable fields from schema', () => {
      const schema = loadSchemaFixture('products')
      const editableFields = getEditableFields(schema)
      
      expect(editableFields).toContain('name')
      expect(editableFields).toContain('sku')
      expect(editableFields).toContain('price')
      expect(editableFields).not.toContain('id') // id is not editable
    })

    it('gets viewable fields from schema', () => {
      const schema = loadSchemaFixture('products')
      const viewableFields = getViewableFields(schema)
      
      expect(viewableFields).toContain('id')
      expect(viewableFields).toContain('name')
      expect(viewableFields).toContain('price')
    })

    it('gets listable fields from schema', () => {
      const schema = loadSchemaFixture('products')
      const listableFields = getListableFields(schema)
      
      expect(listableFields).toContain('id')
      expect(listableFields).toContain('name')
      expect(listableFields).toContain('sku')
      expect(listableFields).not.toContain('description') // description is not listable
    })
  })

  describe('filterFields', () => {
    it('filters record to include only specified fields', () => {
      const record = loadSingleRecordFixture('products')
      const filtered = filterFields(record, ['id', 'name', 'price'])
      
      expect(filtered).toEqual({
        id: 1,
        name: 'Test Product 1',
        price: 99.99
      })
      expect(filtered).not.toHaveProperty('sku')
      expect(filtered).not.toHaveProperty('description')
    })
  })

  describe('Integration with Schema', () => {
    it('uses schema to filter data for list view', () => {
      const schema = loadSchemaFixture('products')
      const data = loadDataFixture('products')
      const listableFields = getListableFields(schema)
      
      // Filter first record to show only listable fields
      const listViewRecord = filterFields(data[0], listableFields)
      
      expect(listViewRecord).toHaveProperty('id')
      expect(listViewRecord).toHaveProperty('name')
      expect(listViewRecord).toHaveProperty('sku')
      expect(listViewRecord).not.toHaveProperty('description')
    })

    it('uses schema to filter data for form view', () => {
      const schema = loadSchemaFixture('products')
      const record = loadSingleRecordFixture('products')
      const editableFields = getEditableFields(schema)
      
      // Filter record to show only editable fields
      const formData = filterFields(record, editableFields)
      
      expect(formData).toHaveProperty('name')
      expect(formData).toHaveProperty('sku')
      expect(formData).toHaveProperty('price')
      expect(formData).not.toHaveProperty('id') // id is not editable
    })
  })
})
