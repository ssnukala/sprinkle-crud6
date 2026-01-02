/**
 * Test Fixtures Loader
 * 
 * Utility for loading JSON test fixtures and schemas in Vitest tests.
 * Uses centralized test data from the integration testing framework.
 * 
 * Data sources:
 * - Schemas: examples/schema/*.json (same as integration tests)
 * - Test data: .github/config/integration-test-models.json (create_payload fields)
 */

import { readFileSync } from 'fs'
import { join } from 'path'

/**
 * Base paths for test data
 */
const SCHEMA_BASE_PATH = join(__dirname, '../../..', 'examples/schema')
const MODELS_CONFIG_PATH = join(__dirname, '../../../.github/config', 'integration-test-models.json')

/**
 * Load a schema fixture file from examples/schema/
 * These are the same schema files used by integration tests
 * 
 * @param model - Model name (e.g., 'products', 'users', 'groups')
 * @returns Parsed schema object
 */
export function loadSchemaFixture(model: string): any {
  try {
    const filePath = join(SCHEMA_BASE_PATH, `${model}.json`)
    const content = readFileSync(filePath, 'utf-8')
    return JSON.parse(content)
  } catch (error) {
    throw new Error(`Failed to load schema fixture for "${model}": ${error}. Looking in: ${SCHEMA_BASE_PATH}`)
  }
}

/**
 * Load test data from integration-test-models.json
 * Uses the create_payload field for each model
 * 
 * @param model - Model name (e.g., 'users', 'groups', 'roles')
 * @returns Array with test record(s)
 */
export function loadDataFixture(model: string): any[] {
  try {
    const content = readFileSync(MODELS_CONFIG_PATH, 'utf-8')
    const modelsConfig = JSON.parse(content)
    
    if (!modelsConfig.models || !modelsConfig.models[model]) {
      throw new Error(`Model "${model}" not found in integration-test-models.json`)
    }
    
    const modelDef = modelsConfig.models[model]
    const createPayload = modelDef.create_payload || {}
    
    // Return array with test record
    // Add ID if not present (use test_id from config)
    const record = {
      id: modelDef.test_id || 1,
      ...createPayload
    }
    
    return [record]
  } catch (error) {
    throw new Error(`Failed to load data fixture for "${model}": ${error}`)
  }
}

/**
 * Load a single record from data fixture
 * 
 * @param model - Model name (e.g., 'users', 'groups')
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
 * Get model configuration from integration-test-models.json
 * 
 * @param model - Model name
 * @returns Model configuration object
 */
export function getModelConfig(model: string): any {
  try {
    const content = readFileSync(MODELS_CONFIG_PATH, 'utf-8')
    const modelsConfig = JSON.parse(content)
    
    if (!modelsConfig.models || !modelsConfig.models[model]) {
      throw new Error(`Model "${model}" not found in integration-test-models.json`)
    }
    
    return modelsConfig.models[model]
  } catch (error) {
    throw new Error(`Failed to load model config for "${model}": ${error}`)
  }
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

/**
 * Get available models from integration-test-models.json
 * 
 * @returns Array of model names
 */
export function getAvailableModels(): string[] {
  try {
    const content = readFileSync(MODELS_CONFIG_PATH, 'utf-8')
    const modelsConfig = JSON.parse(content)
    return Object.keys(modelsConfig.models || {})
  } catch (error) {
    throw new Error(`Failed to load available models: ${error}`)
  }
}

/**
 * Get API paths for a model from integration-test-models.json
 * 
 * @param model - Model name
 * @returns Object with API paths
 */
export function getModelApiPaths(model: string): any {
  const modelConfig = getModelConfig(model)
  return {
    schema: `${modelConfig.api_prefix}/${model}/schema`,
    list: `${modelConfig.api_prefix}/${model}`,
    single: (id: number) => `${modelConfig.api_prefix}/${model}/${id}`,
    create: `${modelConfig.api_prefix}/${model}`,
    update: (id: number) => `${modelConfig.api_prefix}/${model}/${id}`,
    delete: (id: number) => `${modelConfig.api_prefix}/${model}/${id}`
  }
}
