<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTranslator } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Schema, useCRUD6Actions } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Response } from '@ssnukala/sprinkle-crud6/interfaces'
import type { ActionConfig } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6EditModal from './EditModal.vue'
import CRUD6DeleteModal from './DeleteModal.vue'
import CRUD6ActionModal from './ActionModal.vue'
import { debugLog, debugWarn, debugError } from '../../utils/debug'
import { getEnrichedAction, inferFieldFromKey } from '../../utils/actionInference'

const route = useRoute()
const router = useRouter()
const translator = useTranslator()

const { crud6, schema: providedSchema } = defineProps<{
    crud6: CRUD6Response
    schema?: any
}>()

debugLog('[Info] Component initialized - hasProvidedSchema:', !!providedSchema, 'crud6.id:', crud6?.id)

const emits = defineEmits(['crud6Updated'])

// Get model from route parameter for schema loading
const model = computed(() => route.params.model as string)

// Conditional composable usage - only when no schema prop provided
// This prevents the automatic schema loading that happens in useCRUD6Schema initialization
debugLog('[Info] Creating schemaComposable - providedSchema exists:', !!providedSchema, 'model:', model.value)
const schemaComposable = providedSchema ? null : useCRUD6Schema()

// Create actions composable for executing custom actions
const { executeActionWithoutConfirm, loading: actionLoading } = useCRUD6Actions(model.value)

// Extract functions with fallbacks
const hasPermission = schemaComposable?.hasPermission || (() => true)

// Final schema resolution - prioritize provided schema
const finalSchema = computed(() => {
    if (providedSchema) {
        debugLog('[Info] Using PROVIDED schema from parent')
        return providedSchema
    } else if (schemaComposable?.schema.value) {
        debugLog('[Info] Using COMPOSABLE schema (fallback - this may indicate duplicate load)')
        return schemaComposable.schema.value
    } else {
        debugLog('[Info] NO schema available')
        return null
    }
})

// Permission checks using schema-driven permissions
const hasUpdatePermission = computed(() => hasPermission('update'))
const hasDeletePermission = computed(() => hasPermission('delete'))
const hasViewFieldPermission = computed(() => hasPermission('view_field'))

// Schema fields for ActionModal - merge fields from all contexts to ensure
// action modals can access fields like 'password' that may only be in 'form' context
// Direct template access to finalSchema?.fields only returns context-filtered fields
const schemaFieldsForModal = computed(() => {
    const schema = providedSchema || finalSchema.value
    if (!schema) return {}
    
    // Start with base fields (from detail context usually)
    let allFields = { ...(schema.fields || {}) }
    
    // If schema has contexts (multi-context response), merge fields from all contexts
    // This ensures action modals can access fields from 'form' context (like password)
    // that may not be in 'detail' context
    if (schema.contexts) {
        // Merge form context fields (includes create/edit fields like password)
        if (schema.contexts.form?.fields) {
            allFields = { ...allFields, ...schema.contexts.form.fields }
        }
        // Also check create and edit contexts specifically
        if (schema.contexts.create?.fields) {
            allFields = { ...allFields, ...schema.contexts.create.fields }
        }
        if (schema.contexts.edit?.fields) {
            allFields = { ...allFields, ...schema.contexts.edit.fields }
        }
    }
    
    debugLog('[Info] schemaFieldsForModal - total fields:', Object.keys(allFields).length, 'keys:', Object.keys(allFields))
    return allFields
})

// Model label for buttons - prioritize singular_title over model name
// Support translation keys (e.g., "USER.SINGULAR") or plain text
const modelLabel = computed(() => {
    if (finalSchema.value?.singular_title) {
        // Try to translate - if key doesn't exist, returns the key itself
        return translator.translate(finalSchema.value.singular_title)
    }
    // Capitalize first letter of model name as fallback
    return model.value ? model.value.charAt(0).toUpperCase() + model.value.slice(1) : 'Record'
})

// Computed properties for dynamic display
const displayFields = computed(() => {
    if (!finalSchema.value?.fields) return {}
    return Object.fromEntries(
        Object.entries(finalSchema.value.fields).filter(([key, field]) => 
            field.displayable !== false && key !== 'icon'
        )
    )
})

const iconField = computed(() => {
    if (!finalSchema.value?.fields) return null
    return finalSchema.value.fields.icon || null
})

// Helper function to format field values for display
function formatFieldValue(value: any, field: any): string {
    if (value === null || value === undefined) return ''
    
    switch (field.type) {
        case 'boolean':
        case 'boolean-tgl':
        case 'boolean-toggle':
            return value ? 'Yes' : 'No'
        case 'boolean-yn':
            return value ? 'Yes' : 'No'
        case 'date':
            return new Date(value).toLocaleDateString()
        case 'datetime':
            return new Date(value).toLocaleString()
        case 'badge':
        case 'count':
            return String(value)
        case 'json':
            return JSON.stringify(value, null, 2)
        default:
            return String(value)
    }
}

// Handle custom action execution (called after modal confirmation)
async function handleActionClick(action: ActionConfig, actionData?: Record<string, any>) {
    // For actions with input data (password, field updates, etc.), merge with record
    const recordData = actionData ? { ...crud6, ...actionData } : crud6
    
    const success = await executeActionWithoutConfirm(action, crud6.id, recordData)
    if (success && (action.type === 'field_update' || action.type === 'password_update')) {
        // Refresh the record data after field update
        emits('crud6Updated')
    }
}

// Check if action requires a modal (either for confirmation or input)
function requiresModal(action: ActionConfig): boolean {
    // Action has confirmation message
    if (action.confirm) {
        return true
    }
    
    // Action has modal_config with input type
    if (action.modal_config?.type === 'input' || action.modal_config?.type === 'form') {
        return true
    }
    
    // Legacy: password_update type or requires_password_input flag
    if (action.type === 'password_update' || action.requires_password_input === true) {
        return true
    }
    
    // Field update with validation.match (requires confirmation input)
    if (action.type === 'field_update' && action.field && finalSchema.value?.fields) {
        const fieldConfig = finalSchema.value.fields[action.field]
        if (fieldConfig?.validation?.match === true) {
            return true
        }
    }
    
    return false
}

// Get action label with proper fallback logic
function getActionLabel(action: ActionConfig): string {
    // If action has explicit label, try to translate it
    if (action.label) {
        const translated = translator.translate(action.label)
        
        // If translation returns the key itself (not found), check for field label fallback
        if (translated === action.label && action.label.startsWith('CRUD6.ACTION.EDIT_')) {
            // This is an auto-generated translation key that doesn't exist
            // Fallback to field label
            const field = action.field || inferFieldFromKey(action.key)
            const fieldConfig = field && finalSchema.value?.fields ? finalSchema.value.fields[field] : null
            if (fieldConfig?.label) {
                return translator.translate(fieldConfig.label) || fieldConfig.label
            }
        }
        
        return translated
    }
    
    // No label - use humanized key as ultimate fallback
    return action.key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

// Check if action should be visible based on permissions and visible_when conditions
function isActionVisible(action: ActionConfig): boolean {
    // Check permission first
    if (action.permission && !hasPermission(action.permission as any)) {
        return false
    }
    
    // Check visible_when conditions against current record data
    if (action.visible_when && crud6) {
        for (const [field, expectedValue] of Object.entries(action.visible_when)) {
            const actualValue = crud6[field]
            
            // Handle boolean comparisons - database values may be 0/1, '0'/'1', true/false, or null
            if (typeof expectedValue === 'boolean') {
                // Normalize database boolean values to JavaScript boolean
                // Common representations: 0, 1, '0', '1', true, false, null, undefined
                const normalizedActual = actualValue === 1 || actualValue === '1' || actualValue === true
                if (normalizedActual !== expectedValue) {
                    return false
                }
            } else if (actualValue !== expectedValue) {
                // Strict equality for non-boolean values
                return false
            }
        }
    }
    
    return true
}

// Get custom actions from schema with enriched properties
const customActions = computed(() => {
    if (!finalSchema.value?.actions) return []
    
    // Enrich each action with inferred properties
    return finalSchema.value.actions
        .map(action => {
            // Infer field if not specified
            const field = action.field || inferFieldFromKey(action.key)
            
            // Get field configuration if field exists
            const fieldConfig = field && finalSchema.value?.fields?.[field] 
                ? finalSchema.value.fields[field]
                : undefined
            
            // Return enriched action with inferred properties
            return getEnrichedAction(action, fieldConfig)
        })
        .filter(isActionVisible)
})

// Schema loading is completely handled by parent PageRow component and passed as a prop
// When schema prop is provided, we don't use the composable for loading at all to avoid redundant API calls
// Modal components (EditModal, DeleteModal) include their own trigger buttons and use UIKit modals
</script>

<template>
    <UFCardBox>
        <!-- Dynamic content based on schema (provided by PageRow) -->
        <template v-if="finalSchema">
            <!-- Icon display (if icon field exists and has value) -->
            <div v-if="iconField && crud6.icon" class="uk-text-center">
                <font-awesome-icon :icon="crud6.icon" class="fa-5x" />
            </div>
            
            <!-- Title - use schema title field or fallback to ID -->
            <h3 class="uk-text-center uk-margin-remove">
                {{ finalSchema.title_field ? (crud6[finalSchema.title_field] || crud6.id) : crud6.id }}
            </h3>
            
            <!-- Description - use schema description field or fallback -->
            <p v-if="crud6[finalSchema.description_field || 'description']" class="uk-text-meta">
                {{ crud6[finalSchema.description_field || 'description'] }}
            </p>
            
            <hr />
            
            <!-- Dynamic field display based on schema -->
            <dl class="uk-description-list" v-if="hasViewFieldPermission">
                <template v-for="[fieldKey, field] in Object.entries(displayFields)" :key="fieldKey">
                    <dt v-if="crud6[fieldKey] !== null && crud6[fieldKey] !== undefined">
                        <font-awesome-icon 
                            v-if="field.icon" 
                            :icon="field.icon" 
                            class="uk-margin-small-right" />
                        {{ field.label || fieldKey }}
                    </dt>
                    <dd v-if="crud6[fieldKey] !== null && crud6[fieldKey] !== undefined">
                        <!-- Special handling for badge/count fields -->
                        <span 
                            v-if="field.type === 'badge' || field.type === 'count'" 
                            class="uk-badge">
                            {{ formatFieldValue(crud6[fieldKey], field) }}
                        </span>
                        <!-- Special handling for boolean fields -->
                        <span 
                            v-else-if="field.type === 'boolean' || field.type === 'boolean-tgl' || field.type === 'boolean-toggle' || field.type === 'boolean-yn'"
                            :class="crud6[fieldKey] ? 'uk-text-success' : 'uk-text-danger'">
                            {{ formatFieldValue(crud6[fieldKey], field) }}
                        </span>
                        <!-- Default display -->
                        <span v-else>
                            {{ formatFieldValue(crud6[fieldKey], field) }}
                        </span>
                    </dd>
                </template>
            </dl>
            
            <hr />
            
            <!-- Action buttons with dynamic permissions -->
            
            <!-- Custom action buttons from schema -->
            <!-- Unified ActionModal handles all cases: confirmation, input, or both -->
            <template v-for="action in customActions" :key="action.key">
                <!-- Actions requiring modal (confirmation or input) -->
                <CRUD6ActionModal
                    v-if="requiresModal(action)"
                    :action="action"
                    :record="crud6"
                    :schema-fields="schemaFieldsForModal"
                    :model="model"
                    @confirmed="handleActionClick(action, $event)" />
                
                <!-- Action without modal - direct button -->
                <button
                    v-else
                    @click="handleActionClick(action)"
                    :disabled="actionLoading"
                    :data-test="`btn-action-${action.key}`"
                    :class="[
                        'uk-width-1-1',
                        'uk-margin-small-bottom',
                        'uk-button',
                        'uk-button-small',
                        action.style ? `uk-button-${action.style}` : 'uk-button-default'
                    ]">
                    <font-awesome-icon v-if="action.icon" :icon="action.icon" fixed-width />
                    {{ getActionLabel(action) }}
                </button>
            </template>
            
            <!-- Edit Modal - renders trigger button and modal together (UIKit handles visibility) -->
            <CRUD6EditModal
                v-if="hasUpdatePermission"
                :crud6="crud6"
                :model="model"
                :schema="finalSchema"
                @saved="emits('crud6Updated')"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-primary uk-button-small" />
            
            <!-- Delete Modal - renders trigger button and modal together (UIKit handles visibility) -->
            <CRUD6DeleteModal
                v-if="hasDeletePermission"
                :crud6="crud6"
                :model="model"
                :schema="finalSchema"
                @deleted="router.push({ name: 'crud6.list', params: { model: model } })"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-danger uk-button-small" />
            
            <!-- Slot for additional content -->
            <slot data-test="slot"></slot>
        </template>
        
        <!-- Fallback for legacy display (no schema) -->
        <template v-else>
            <div class="uk-text-center">
                <font-awesome-icon v-if="crud6.icon" :icon="crud6.icon" class="fa-5x" />
            </div>
            <h3 class="uk-text-center uk-margin-remove">{{ crud6.name }}</h3>
            <p class="uk-text-meta">
                {{ crud6.description }}
            </p>
            <hr />
            <dl class="uk-description-list" v-if="$checkAccess('view_crud6_field')">
                <dt><font-awesome-icon icon="users" /> {{ $t('USER', crud6.users_count) }}</dt>
                <dd>
                    <span class="uk-badge">{{ crud6.users_count }}</span>
                </dd>
            </dl>
            <hr />
            <!-- Legacy Edit Modal - always rendered for backward compatibility -->
            <CRUD6EditModal
                v-if="$checkAccess('update_crud6_field')"
                :crud6="crud6"
                :schema="finalSchema"
                @saved="emits('crud6Updated')"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-primary uk-button-small" />
            
            <!-- Legacy Delete Modal - always rendered for backward compatibility -->
            <CRUD6DeleteModal
                v-if="$checkAccess('delete_crud6_row')"
                :crud6="crud6"
                :schema="finalSchema"
                @deleted="router.push({ name: 'crud6.list', params: { model: model } })"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-danger uk-button-small" />
            <slot data-test="slot"></slot>
        </template>
    </UFCardBox>
</template>
