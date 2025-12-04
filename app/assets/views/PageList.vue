<!-- PageList.vue -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Schema, useCRUD6Breadcrumbs } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6CreateModal from '../components/CRUD6/CreateModal.vue'
import CRUD6EditModal from '../components/CRUD6/EditModal.vue'
import CRUD6DeleteModal from '../components/CRUD6/DeleteModal.vue'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import { debugLog, debugWarn, debugError } from '../utils/debug'

const route = useRoute()
const router = useRouter()
const page = usePageMeta()
const { setListBreadcrumb } = useCRUD6Breadcrumbs()

// Current model name from route
const model = computed(() => route.params.model as string)

// CRUD6 schema composable
const {
  schema,
  loading: schemaLoading,
  error: schemaError,
  loadSchema,
  hasPermission,
} = useCRUD6Schema()

// Permissions
const hasCreatePermission = computed(() => hasPermission('create'))
const hasEditPermission   = computed(() => hasPermission('update'))
const hasDeletePermission = computed(() => hasPermission('delete'))

// Model label for buttons - prioritize singular_title over model name
const modelLabel = computed(() => {
  if (schema.value?.singular_title) {
    return schema.value.singular_title
  }
  // Capitalize first letter of model name as fallback
  return model.value ? model.value.charAt(0).toUpperCase() + model.value.slice(1) : 'Record'
})

// Schema fields
const schemaFields = computed(() => {
  // If schema has contexts (multi-context response), use the list context
  if (schema.value?.contexts?.list?.fields) {
    return Object.entries(schema.value.contexts.list.fields)
  }
  // Otherwise use the fields directly (single-context or legacy response)
  return Object.entries(schema.value?.fields || {})
})

// API URL
const apiUrl = computed(() =>
  model.value ? `/api/crud6/${model.value}` : '/api/crud6/model-not-set'
)

// Search column
const searchColumn = computed(() => {
  const fields = schema.value?.fields
  if (fields) {
    const searchable = Object.keys(fields).find(key => fields[key].searchable)
    return searchable || 'name'
  }
  return 'name'
})

// Actions
function viewRecord(record: CRUD6Interface) {
  if (model.value && record) {
    const id = record[schema.value?.primary_key || 'id']
    router.push(`/crud6/${model.value}/${id}`)
  }
}

// Import all template files eagerly (HTML templates)
const templateFiles = import.meta.glob('../templates/crud6/*.html', { as: 'raw', eager: true })

// Import all Vue component templates
const vueTemplateFiles = import.meta.glob('../templates/crud6/*.vue', { eager: true })

// Check if a template is a Vue component
function isVueTemplate(template: string): boolean {
  return template.endsWith('.vue')
}

// Get Vue component for template
function getVueComponent(template: string): any {
  const templatePath = `../templates/crud6/${template}`
  return vueTemplateFiles[templatePath]?.default || null
}

// Render field template with row data (for HTML templates only)
function renderFieldTemplate(template: string, row: any): string {
  if (!template) return ''
  
  let templateContent = template
  
  // Check if template is a file reference (ends with .html or .htm)
  if (template.endsWith('.html') || template.endsWith('.htm')) {
    // Construct the full path for the glob import
    const templatePath = `../templates/crud6/${template}`
    
    // Get template content from imported files
    if (templateFiles[templatePath]) {
      templateContent = templateFiles[templatePath] as string
    } else {
      debugError(`Template file not found: ${template}`)
      return '' // Return empty string if template file not found
    }
  }
  
  // Replace placeholders like {{field_name}} with actual values from row
  let rendered = templateContent
  
  // Find all placeholders in the format {{field_name}}
  const placeholderRegex = /\{\{(\w+)\}\}/g
  rendered = rendered.replace(placeholderRegex, (match, fieldName) => {
    const value = row[fieldName]
    return value !== null && value !== undefined ? String(value) : ''
  })
  
  return rendered
}

// Load schema
onMounted(async () => {
  if (model.value && loadSchema) {
    debugLog('[PageList.onMounted] Starting - model:', model.value)
    debugLog('[PageList.onMounted] Current page.breadcrumbs:', page.breadcrumbs)
    
    // Set initial page title immediately for breadcrumbs
    const initialTitle = schema.value?.title || model.value.charAt(0).toUpperCase() + model.value.slice(1)
    page.title = initialTitle
    
    debugLog('[PageList.onMounted] Initial title:', initialTitle)
    debugLog('[PageList.onMounted] Calling setListBreadcrumb with initial title')
    
    // Update breadcrumbs to replace {{model}} placeholder with initial title
    await setListBreadcrumb(initialTitle)
    
    debugLog('[PageList.onMounted] After setListBreadcrumb, page.breadcrumbs:', page.breadcrumbs)
    
    // Request BOTH 'list' and 'form' contexts in a single call
    // This avoids duplicate API calls when the create/edit modal is opened
    const schemaPromise = loadSchema(model.value, false, 'list,form')
    if (schemaPromise && typeof schemaPromise.then === 'function') {
      schemaPromise.then(async () => {
        debugLog('[PageList.onMounted] Schema loaded')
        debugLog('[PageList.onMounted] page.breadcrumbs after schema load:', page.breadcrumbs)
        
        // Update page title and description using schema
        if (schema.value) {
          const schemaTitle = schema.value.title || model.value
          page.title = schemaTitle
          page.description = schema.value.description || `A listing of the ${modelLabel.value} for your site. Provides management tools for editing and deleting ${modelLabel.value}.`
          
          debugLog('[PageList.onMounted] Schema title:', schemaTitle)
          debugLog('[PageList.onMounted] Calling setListBreadcrumb with schema title')
          
          // Update breadcrumbs with schema title (may be different from initial title)
          await setListBreadcrumb(schemaTitle)
          
          debugLog('[PageList.onMounted] After final setListBreadcrumb, page.breadcrumbs:', page.breadcrumbs)
        }
      })
    }
  }
})
</script>

<template>
  <UFCardBox>
    <!-- Loading -->
    <div v-if="schemaLoading" class="uk-text-center uk-padding">
      <div uk-spinner></div>
      <p>{{ $t('LOADING') }}</p>
    </div>

    <!-- Error -->
    <div v-else-if="schemaError" class="uk-alert-danger" uk-alert>
      <h4>{{ schemaError.title }}</h4>
      <p>{{ schemaError.description }}</p>
    </div>

    <!-- Table -->
    <UFSprunjeTable
      v-else-if="schema"
      :dataUrl="apiUrl"
      :searchColumn="searchColumn">

      <!-- Actions -->
      <template #actions="{ sprunjer }">
        <CRUD6CreateModal
          v-if="hasCreatePermission && schema"
          :model="model"
          :schema="schema"
          @saved="sprunjer.fetch()"
          class="uk-button uk-button-primary" />
      </template>

      <!-- Header -->
      <template #header>
        <UFSprunjeHeader
          v-for="[fieldKey, field] in schemaFields"
          :key="fieldKey"
          :sort="fieldKey"
          :class="field.width ? `uk-width-${field.width}` : ''">
          {{ field.label || fieldKey }}
        </UFSprunjeHeader>
        <UFSprunjeHeader v-if="hasEditPermission || hasDeletePermission">
          {{ $t('ACTIONS') }}
        </UFSprunjeHeader>
      </template>

      <!-- Body -->
      <template #body="{ row, sprunjer }">
        <UFSprunjeColumn
          v-for="[fieldKey, field] in schemaFields"
          :key="fieldKey"
          :class="field.width ? `uk-width-${field.width}` : ''">
          
          <!-- Field rendering with template support -->
          <template v-if="field.field_template">
            <!-- Vue component template -->
            <component 
              v-if="isVueTemplate(field.field_template)"
              :is="getVueComponent(field.field_template)"
              :rowData="row"
            />
            <!-- HTML template with v-html -->
            <div v-else v-html="renderFieldTemplate(field.field_template, row)"></div>
          </template>
          <template v-else-if="field.type === 'link' || fieldKey === schema.value?.primary_key">
            <strong>
              <RouterLink
                :to="{ name: 'crud6.view', params: { model: model, id: row[schema.value?.primary_key || 'id'] } }"
                @click="viewRecord(row)">
                {{ row[fieldKey] }}
              </RouterLink>
            </strong>
          </template>
          <template v-else-if="field.type === 'badge'">
            <span class="uk-badge">{{ row[fieldKey] }}</span>
          </template>
          <template v-else-if="field.type === 'boolean'">
            <span :class="row[fieldKey] ? 'uk-text-success' : 'uk-text-danger'">
              {{ row[fieldKey] ? $t('YES') : $t('NO') }}
            </span>
          </template>
          <template v-else>
            {{ row[fieldKey] }}
          </template>
        </UFSprunjeColumn>

        <!-- Action column -->
        <UFSprunjeColumn v-if="hasEditPermission || hasDeletePermission">
          <button class="uk-button uk-button-primary uk-text-nowrap" type="button" data-test="btn-actions">
            {{ $t('ACTIONS') }} <span uk-drop-parent-icon></span>
          </button>
          <div class="uk-padding-small" uk-dropdown="pos: bottom-right; mode: click; offset: 2">
            <ul class="uk-nav uk-dropdown-nav">
              <li>
                <RouterLink
                  :to="{ name: 'crud6.view', params: { model: model, id: row[schema.value?.primary_key || 'id'] } }"
                  @click="viewRecord(row)">
                  <font-awesome-icon icon="eye" fixed-width /> View
                </RouterLink>
              </li>
              <li v-if="hasEditPermission && schema">
                <CRUD6EditModal 
                  :crud6="row" 
                  :model="model" 
                  :schema="schema" 
                  @saved="sprunjer.fetch()" 
                  class="uk-drop-close" />
              </li>
              <li v-if="hasDeletePermission && schema">
                <CRUD6DeleteModal 
                  :crud6="row" 
                  :model="model" 
                  :schema="schema" 
                  @deleted="sprunjer.fetch()" 
                  class="uk-drop-close" />
              </li>
            </ul>
          </div>
        </UFSprunjeColumn>
      </template>
    </UFSprunjeTable>

    <!-- No schema -->
    <div v-else class="uk-alert-warning" uk-alert>
      <p>{{ $t('CRUD6.NO_SCHEMA') }}</p>
    </div>
  </UFCardBox>
</template>
