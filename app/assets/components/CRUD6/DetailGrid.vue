<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import type { DetailRecord } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6AutoLookup from './AutoLookup.vue'
import { getLookupConfig } from '../../composables/useCRUD6FieldRenderer'

/**
 * DetailGrid Component
 * 
 * An inline editable grid for managing detail records in a master-detail relationship.
 * Supports adding, editing, and deleting rows with validation.
 */

const props = defineProps<{
    modelValue: DetailRecord[]
    detailSchema: CRUD6Schema
    fields: string[]
    allowAdd?: boolean
    allowEdit?: boolean
    allowDelete?: boolean
    disabled?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: DetailRecord[]]
}>()

// Local copy of details for editing
const localDetails = ref<DetailRecord[]>([...props.modelValue])

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    localDetails.value = [...newValue]
}, { deep: true })

// Emit changes to parent
function updateParent() {
    emit('update:modelValue', [...localDetails.value])
}

// Get field configuration from schema
function getFieldConfig(fieldKey: string) {
    return props.detailSchema?.fields?.[fieldKey] || {}
}

// Get field label
function getFieldLabel(fieldKey: string): string {
    const config = getFieldConfig(fieldKey)
    return config.label || fieldKey.charAt(0).toUpperCase() + fieldKey.slice(1).replace(/_/g, ' ')
}

// Get field type
function getFieldType(fieldKey: string): string {
    const config = getFieldConfig(fieldKey)
    return config.type || 'string'
}

// Check if field is non-editable
function isFieldReadonly(fieldKey: string): boolean {
    const config = getFieldConfig(fieldKey)
    return config.editable === false || config.auto_increment === true
}

// Add new row
function addRow() {
    if (props.disabled || props.allowAdd === false) return

    // Create new row with default values based on schema
    const newRow: DetailRecord = {
        _action: 'create',
        _originalIndex: localDetails.value.length,
    }

    // Initialize fields with default values
    props.fields.forEach(fieldKey => {
        const config = getFieldConfig(fieldKey)
        if (config.editable !== false && !config.auto_increment) {
            switch (config.type) {
                case 'integer':
                case 'decimal':
                case 'float':
                    newRow[fieldKey] = config.default ?? 0
                    break
                case 'boolean':
                    newRow[fieldKey] = config.default ?? false
                    break
                case 'smartlookup':
                    newRow[fieldKey] = config.default ?? null
                    break
                default:
                    newRow[fieldKey] = config.default ?? ''
            }
        }
    })

    localDetails.value.push(newRow)
    updateParent()
}

// Mark row for deletion
function deleteRow(index: number) {
    if (props.disabled || props.allowDelete === false) return

    const row = localDetails.value[index]
    
    if (row.id) {
        // Existing record - mark for deletion
        row._action = 'delete'
        updateParent()
    } else {
        // New record - just remove it
        localDetails.value.splice(index, 1)
        updateParent()
    }
}

// Handle field change
function onFieldChange(index: number, fieldKey: string, value: any) {
    if (props.disabled || props.allowEdit === false) return

    const row = localDetails.value[index]
    row[fieldKey] = value

    // Mark existing records as updated
    if (row.id && row._action !== 'delete') {
        row._action = 'update'
    }

    updateParent()
}

// Calculate if row should be shown (not marked for deletion)
function isRowVisible(row: DetailRecord): boolean {
    return row._action !== 'delete'
}

// Filter visible rows
const visibleRows = computed(() => 
    localDetails.value.filter(isRowVisible)
)

// Calculate totals if needed (for Order use case)
const calculateLineTotal = (row: DetailRecord): number => {
    const quantity = Number(row.quantity) || 0
    const unitPrice = Number(row.unit_price) || 0
    return quantity * unitPrice
}

/**
 * Helper function to get lookup attributes for AutoLookup component
 * Uses centralized getLookupConfig from composable
 */
function getLookupAttributes(field: any) {
    const lookupConfig = getLookupConfig(field)
    return {
        model: lookupConfig.model,
        'id-field': lookupConfig.idField,
        'display-field': lookupConfig.displayField,
        disabled: field.disabled || field.editable === false
    }
}
</script>

<template>
    <div class="detail-grid">
        <div class="uk-overflow-auto">
            <table class="uk-table uk-table-divider uk-table-hover uk-table-small">
                <thead>
                    <tr>
                        <th v-for="fieldKey in fields" :key="fieldKey">
                            {{ getFieldLabel(fieldKey) }}
                        </th>
                        <th v-if="allowDelete !== false" class="uk-table-shrink">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="visibleRows.length === 0">
                        <td :colspan="fields.length + (allowDelete !== false ? 1 : 0)" class="uk-text-center uk-text-muted">
                            No detail records. Click "Add Row" to add items.
                        </td>
                    </tr>
                    <tr 
                        v-for="(row, index) in localDetails" 
                        :key="row.id || `new-${index}`"
                        v-show="isRowVisible(row)"
                        :class="{ 'uk-text-muted': row._action === 'delete' }">
                        <td v-for="fieldKey in fields" :key="fieldKey">
                            <!-- Readonly fields -->
                            <template v-if="isFieldReadonly(fieldKey) || disabled">
                                <template v-if="getFieldType(fieldKey) === 'boolean'">
                                    <span :class="row[fieldKey] ? 'uk-label-success' : 'uk-label-danger'" class="uk-label">
                                        {{ row[fieldKey] ? 'Yes' : 'No' }}
                                    </span>
                                </template>
                                <template v-else-if="getFieldType(fieldKey) === 'decimal' || getFieldType(fieldKey) === 'float'">
                                    {{ Number(row[fieldKey]).toFixed(2) }}
                                </template>
                                <template v-else-if="getFieldType(fieldKey) === 'smartlookup'">
                                    {{ row[fieldKey] }}
                                </template>
                                <template v-else>
                                    {{ row[fieldKey] }}
                                </template>
                            </template>

                            <!-- Editable fields -->
                            <template v-else>
                                <!-- Text input for string fields -->
                                <input
                                    v-if="getFieldType(fieldKey) === 'string' || getFieldType(fieldKey) === 'text'"
                                    type="text"
                                    class="uk-input uk-form-small"
                                    :value="row[fieldKey]"
                                    @input="onFieldChange(index, fieldKey, ($event.target as HTMLInputElement).value)"
                                    :disabled="disabled || allowEdit === false"
                                />

                                <!-- Number input for numeric fields -->
                                <input
                                    v-else-if="getFieldType(fieldKey) === 'integer' || getFieldType(fieldKey) === 'decimal' || getFieldType(fieldKey) === 'float'"
                                    type="number"
                                    class="uk-input uk-form-small"
                                    :value="row[fieldKey]"
                                    :step="getFieldType(fieldKey) === 'integer' ? '1' : '0.01'"
                                    @input="onFieldChange(index, fieldKey, Number(($event.target as HTMLInputElement).value))"
                                    :disabled="disabled || allowEdit === false"
                                />

                                <!-- Checkbox for boolean fields -->
                                <input
                                    v-else-if="getFieldType(fieldKey) === 'boolean'"
                                    type="checkbox"
                                    class="uk-checkbox"
                                    :checked="row[fieldKey]"
                                    @change="onFieldChange(index, fieldKey, ($event.target as HTMLInputElement).checked)"
                                    :disabled="disabled || allowEdit === false"
                                />

                                <!-- SmartLookup field -->
                                <CRUD6AutoLookup
                                    v-else-if="getFieldType(fieldKey) === 'smartlookup'"
                                    v-bind="{ ...getLookupAttributes(getFieldConfig(fieldKey)), disabled: disabled || allowEdit === false }"
                                    :model-value="row[fieldKey]"
                                    @update:model-value="onFieldChange(index, fieldKey, $event)"
                                />

                                <!-- Date input for date fields -->
                                <input
                                    v-else-if="getFieldType(fieldKey) === 'date'"
                                    type="date"
                                    class="uk-input uk-form-small"
                                    :value="row[fieldKey]"
                                    @input="onFieldChange(index, fieldKey, ($event.target as HTMLInputElement).value)"
                                    :disabled="disabled || allowEdit === false"
                                />

                                <!-- Datetime input for datetime fields -->
                                <input
                                    v-else-if="getFieldType(fieldKey) === 'datetime'"
                                    type="datetime-local"
                                    class="uk-input uk-form-small"
                                    :value="row[fieldKey]"
                                    @input="onFieldChange(index, fieldKey, ($event.target as HTMLInputElement).value)"
                                    :disabled="disabled || allowEdit === false"
                                />

                                <!-- Default text input -->
                                <input
                                    v-else
                                    type="text"
                                    class="uk-input uk-form-small"
                                    :value="row[fieldKey]"
                                    @input="onFieldChange(index, fieldKey, ($event.target as HTMLInputElement).value)"
                                    :disabled="disabled || allowEdit === false"
                                />
                            </template>
                        </td>
                        <td v-if="allowDelete !== false" class="uk-table-shrink">
                            <button
                                type="button"
                                class="uk-button uk-button-danger uk-button-small"
                                data-test="btn-delete-row"
                                @click="deleteRow(index)"
                                :disabled="disabled || allowDelete === false"
                                title="Delete row">
                                <span uk-icon="trash"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add Row Button -->
        <div v-if="allowAdd !== false" class="uk-margin-small-top">
            <button
                type="button"
                class="uk-button uk-button-primary uk-button-small"
                data-test="btn-add-row"
                @click="addRow"
                :disabled="disabled">
                <span uk-icon="plus"></span> Add Row
            </button>
        </div>
    </div>
</template>

<style scoped>
.detail-grid {
    margin: 1rem 0;
}

.uk-table-small td,
.uk-table-small th {
    padding: 8px 12px;
}

.uk-input.uk-form-small {
    height: 32px;
    padding: 4px 8px;
    font-size: 0.875rem;
}

.uk-button-small {
    padding: 0 12px;
    line-height: 28px;
}
</style>
