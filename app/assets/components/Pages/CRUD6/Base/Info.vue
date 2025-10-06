<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import type { CRUD6Response } from '@ssnukala/sprinkle-crud6/interfaces'
import CRUD6EditModal from './EditModal.vue'
import CRUD6DeleteModal from './DeleteModal.vue'

const route = useRoute()
const router = useRouter()

const { crud6, schema: providedSchema } = defineProps<{
    crud6: CRUD6Response
    schema?: any
}>()

console.log('[Info] 🚀 Component setup - hasProvidedSchema:', !!providedSchema, 'crud6.id:', crud6?.id, 'route:', route.path)

const emits = defineEmits(['crud6Updated'])

// Get model from route parameter for schema loading
const model = computed(() => route.params.model as string)

// Lazy loading state for modals (following PageList pattern)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

// Helper functions to lazily load modals
function requestEditModal() {
    console.log('[Info] 🔧 Edit modal requested - lazy loading EditModal component')
    console.log('[Info] 📊 Schema will be passed to EditModal - title:', finalSchema.value?.title, 'hasFields:', !!finalSchema.value?.fields)
    showEditModal.value = true
}

function requestDeleteModal() {
    console.log('[Info] 🗑️  Delete modal requested - lazy loading DeleteModal component') 
    console.log('[Info] 📊 Schema will be passed to DeleteModal - title:', finalSchema.value?.title, 'hasFields:', !!finalSchema.value?.fields)
    showDeleteModal.value = true
}

// Conditional composable usage - only when no schema prop provided
// This prevents the automatic schema loading that happens in useCRUD6Schema initialization
const schemaComposable = providedSchema ? null : useCRUD6Schema()

// Extract functions with fallbacks
const hasPermission = schemaComposable?.hasPermission || (() => true)

// Final schema resolution - prioritize provided schema
const finalSchema = computed(() => {
    if (providedSchema) {
        console.log('[Info] ✅ Using provided schema prop from PageRow - NO API call needed')
        console.log('[Info] 📋 Provided schema details - title:', providedSchema?.title, 'fields:', Object.keys(providedSchema?.fields || {}))
        return providedSchema
    } else if (schemaComposable?.schema.value) {
        console.log('[Info] 🔄 Using composable schema (fallback) - this indicates missing schema prop')
        return schemaComposable.schema.value
    } else {
        console.log('[Info] ⚠️  No schema available from either source')
        return null
    }
})

// Permission checks using schema-driven permissions
const hasUpdatePermission = computed(() => hasPermission('update'))
const hasDeletePermission = computed(() => hasPermission('delete'))
const hasViewFieldPermission = computed(() => hasPermission('view_field'))

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

// Schema loading is completely handled by parent PageRow component and passed as a prop
// When schema prop is provided, we don't use the composable for loading at all to avoid redundant API calls
// The lazy loading pattern prevents modal components from being instantiated until user interaction
</script>

<template>
    <UFCardBox>
        <!-- Dynamic content based on schema (provided by PageRow) -->
        <template v-if="finalSchema">
            <!-- Icon display (if icon field exists and has value) -->
            <div v-if="iconField && crud6.icon" class="uk-text-center">
                <font-awesome-icon :icon="crud6.icon" class="fa-5x" />
            </div>
            
            <!-- Title - use schema title field or fallback to name -->
            <h3 class="uk-text-center uk-margin-remove">
                {{ crud6[finalSchema.title_field || 'name'] || crud6.name }}
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
                            v-else-if="field.type === 'boolean'"
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
            
            <!-- Action buttons with dynamic permissions - Lazy Loading Pattern -->
            <!-- Edit button - shows modal only after user clicks -->
            <button
                v-if="hasUpdatePermission && !showEditModal"
                @click="requestEditModal()"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-primary uk-button-small">
                <font-awesome-icon icon="pen-to-square" fixed-width /> {{ $t('CRUD6.EDIT') }}
            </button>
            
            <!-- Edit Modal - only rendered after user requests it -->
            <CRUD6EditModal
                v-if="hasUpdatePermission && showEditModal"
                :crud6="crud6"
                :model="model"
                :schema="finalSchema"
                @saved="emits('crud6Updated')"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-primary uk-button-small" />
            
            <!-- Delete button - shows modal only after user clicks -->
            <button
                v-if="hasDeletePermission && !showDeleteModal"
                @click="requestDeleteModal()"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-danger uk-button-small">
                <font-awesome-icon icon="trash" fixed-width /> {{ $t('CRUD6.DELETE') }}
            </button>
            
            <!-- Delete Modal - only rendered after user requests it -->
            <CRUD6DeleteModal
                v-if="hasDeletePermission && showDeleteModal"
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
                @deleted="router.push({ name: 'crud6.list' })"
                class="uk-width-1-1 uk-margin-small-bottom uk-button uk-button-danger uk-button-small" />
            <slot data-test="slot"></slot>
        </template>
    </UFCardBox>
</template>
