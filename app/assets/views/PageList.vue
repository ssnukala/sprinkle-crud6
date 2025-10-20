<!-- PageList.vue -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6CreateModal from '../components/CRUD6/CreateModal.vue'
import CRUD6EditModal from '../components/CRUD6/EditModal.vue'
import CRUD6DeleteModal from '../components/CRUD6/DeleteModal.vue'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'

const route = useRoute()
const router = useRouter()
const page = usePageMeta()
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
const schemaFields = computed(() => Object.entries(schema.value?.fields || {}))

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

// Import all template files eagerly
const templateFiles = import.meta.glob('../templates/crud6/*.html', { as: 'raw', eager: true })

// Render field template with row data
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
      console.error(`Template file not found: ${template}`)
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
onMounted(() => {
  if (model.value && loadSchema) {
    // Set initial page title immediately for breadcrumbs
    page.title = schema.value?.title || model.value.charAt(0).toUpperCase() + model.value.slice(1)
    
    const schemaPromise = loadSchema(model.value)
    if (schemaPromise && typeof schemaPromise.then === 'function') {
      schemaPromise.then(() => {
        // Update page title and description using schema
        if (schema.value) {
          page.title = schema.value.title || model.value
          page.description = schema.value.description || `A listing of the ${modelLabel.value} for your site. Provides management tools for editing and deleting ${modelLabel.value}.`
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
            <!-- Render using field_template with access to all row data -->
            <div v-html="renderFieldTemplate(field.field_template, row)"></div>
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
          <button class="uk-button uk-button-primary uk-text-nowrap" type="button">
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
