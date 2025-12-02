// CRUD6
export { useCRUD6Api } from './useCRUD6Api'
export { useCRUD6sApi } from './useCRUD6sApi'
export { useCRUD6Schema } from './useCRUD6Schema'
export { useCRUD6Relationships } from './useCRUD6Relationships'
export { useMasterDetail } from './useMasterDetail'
export { useCRUD6Actions, isPasswordFieldAction } from './useCRUD6Actions'
export { useCRUD6FieldRenderer } from './useCRUD6FieldRenderer'
export { useCRUD6Breadcrumbs } from './useCRUD6Breadcrumbs'

// CRUD6 Validation Adapters
export { useCRUD6RegleAdapter, convertCRUD6ToRegleRules } from './useCRUD6ValidationAdapter'
// Deprecated: Use useCRUD6RegleAdapter instead to avoid YAML imports
export { useCRUD6ToUFSchemaConverter, convertCRUD6ToUFValidatorFormat } from './useCRUD6ValidationAdapter'

// Export schema types for external use
export type { CRUD6Schema, SchemaField, DetailConfig, DetailEditableConfig, ActionConfig, ModalConfig, ModalButtonConfig } from './useCRUD6Schema'

// Export master-detail types
export type { DetailRecord, MasterDetailSaveRequest, MasterDetailSaveResponse } from './useMasterDetail'

// Export field renderer types
export type { FieldConfig, FieldRendererProps, FieldRenderConfig } from './useCRUD6FieldRenderer'

