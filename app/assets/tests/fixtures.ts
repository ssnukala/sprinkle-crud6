/**
 * Test Fixtures Loader
 * 
 * Utility for loading JSON test fixtures and schemas in Vitest tests.
 * This provides realistic test data from examples/test/vitest/ directory.
 */

import { readFileSync } from 'fs'
import { join } from 'path'

/**
 * Base path for test fixtures
 */
const FIXTURES_BASE_PATH = join(__dirname, '../../..', 'examples/test/vitest')

/**
 * Load a schema fixture file
 * 
 * @param model - Model name (e.g., 'products', 'users')
 * @returns Parsed schema object
 */
export function loadSchemaFixture(model: string): any {
  try {
    const filePath = join(FIXTURES_BASE_PATH, 'schemas', `${model}.json`)
    const content = readFileSync(filePath, 'utf-8')
    return JSON.parse(content)
  } catch (error) {
    throw new Error(`Failed to load schema fixture for "${model}": ${error}`)
  }
}

/**
 * Load a data fixture file
 * 
 * @param model - Model name (e.g., 'products', 'users')
 * @returns Parsed data array
 */
export function loadDataFixture(model: string): any[] {
  try {
    const filePath = join(FIXTURES_BASE_PATH, 'fixtures', `${model}.json`)
    const content = readFileSync(filePath, 'utf-8')
    return JSON.parse(content)
  } catch (error) {
    throw new Error(`Failed to load data fixture for "${model}": ${error}`)
  }
}

/**
 * Load a single record from data fixture
 * 
 * @param model - Model name (e.g., 'products', 'users')
 * @param index - Index of record to load (default: 0)
 * @returns Single record object
 */
export function loadSingleRecordFixture(model: string, index: number = 0): any {
  const data = loadDataFixture(model)
  if (index >= data.length) {
    throw new Error(`Record index ${index} out of bounds for "${model}" fixture (length: ${data.length})`)
  }
  return data[index]
}

/**
 * Get editable fields from schema
 * 
 * @param schema - Schema object
 * @returns Array of editable field names
 */
export function getEditableFields(schema: any): string[] {
  const fields = schema.fields || {}
  return Object.keys(fields).filter(key => fields[key].editable !== false)
}

/**
 * Get viewable fields from schema
 * 
 * @param schema - Schema object
 * @returns Array of viewable field names
 */
export function getViewableFields(schema: any): string[] {
  const fields = schema.fields || {}
  return Object.keys(fields).filter(key => fields[key].viewable !== false)
}

/**
 * Get listable fields from schema
 * 
 * @param schema - Schema object
 * @returns Array of listable field names
 */
export function getListableFields(schema: any): string[] {
  const fields = schema.fields || {}
  return Object.keys(fields).filter(key => fields[key].listable === true)
}

/**
 * Filter data to include only specified fields
 * 
 * @param data - Record object
 * @param fields - Array of field names to include
 * @returns Filtered record object
 */
export function filterFields(data: any, fields: string[]): any {
  const result: any = {}
  for (const field of fields) {
    if (field in data) {
      result[field] = data[field]
    }
  }
  return result
}
