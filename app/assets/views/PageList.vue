<!-- PageList.vue -->
<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'
import CRUD6CreateModal from '../../components/Pages/CRUD6/Base/CreateModal.vue'
import CRUD6EditModal from '../../components/Pages/CRUD6/Base/EditModal.vue'
import CRUD6DeleteModal from '../../components/Pages/CRUD6/Base/DeleteModal.vue'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'

const route = useRoute()
const router = useRouter()

// Current model name from route
const model = computed(() => route.params.model as string)

// Track which modals have been requested by user interaction
const loadedEditModals = ref(new Set<string>())
const loadedDeleteModals = ref(new Set<string>())
const showCreateModal = ref(false)

// Helper functions to lazily load modals
function requestEditModal(recordId: string) {
  loadedEditModals.value.add(recordId)
}

function requestDeleteModal(recordId: string) {
  loadedDeleteModals.value.add(recordId)
}

function requestCreateModal() {
  showCreateModal.value = true
}

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

// Load schema
onMounted(() => {
  if (model.value && loadSchema) {
    console.log('[PageList] Loading schema for model:', model.value)
    const schemaPromise = loadSchema(model.value)
    if (schemaPromise && typeof schemaPromise.then === 'function') {
      schemaPromise.then(() => {
        console.log('[PageList] Schema loaded successfully for:', model.value)
      }).catch((error) => {
        console.error('[PageList] Failed to load schema:', error)
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
        <!-- Create button that triggers modal loading -->
        <button 
          v-if="hasCreatePermission && schema && !showCreateModal"
          @click="requestCreateModal()"
          class="uk-button uk-button-primary">
          <font-awesome-icon icon="plus" fixed-width /> {{ $t('CRUD6.CREATE') }}
        </button>
        
        <!-- Create Modal - only rendered after user clicks create -->
        <CRUD6CreateModal
          v-if="showCreateModal && hasCreatePermission && schema"
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
          {{ $t('CRUD6.ACTIONS') }}
        </UFSprunjeHeader>
      </template>

      <!-- Body -->
      <template #body="{ row, sprunjer }">
        <UFSprunjeColumn
          v-for="[fieldKey, field] in schemaFields"
          :key="fieldKey"
          :class="field.width ? `uk-width-${field.width}` : ''">
          
          <!-- Field rendering -->
          <template v-if="field.type === 'link' || fieldKey === schema.value?.primary_key">
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
                <!-- Edit button - shows modal loading on first click -->
                <a v-if="!loadedEditModals.has(row.id || row.slug)" 
                   @click="requestEditModal(row.id || row.slug)"
                   class="uk-drop-close">
                  <font-awesome-icon icon="pen-to-square" fixed-width /> {{ $t('CRUD6.EDIT') }}
                </a>
                
                <!-- Edit Modal - only rendered after user requests it -->
                <CRUD6EditModal 
                  v-if="loadedEditModals.has(row.id || row.slug)"
                  :crud6="row" 
                  :model="model" 
                  :schema="schema" 
                  @saved="sprunjer.fetch()" 
                  class="uk-drop-close" />
              </li>
              <li v-if="hasDeletePermission && schema">
                <!-- Delete button - shows modal loading on first click -->
                <a v-if="!loadedDeleteModals.has(row.id || row.slug)" 
                   @click="requestDeleteModal(row.id || row.slug)"
                   class="uk-drop-close">
                  <font-awesome-icon icon="trash" fixed-width /> {{ $t('CRUD6.DELETE') }}
                </a>
                
                <!-- Delete Modal - only rendered after user requests it -->
                <CRUD6DeleteModal 
                  v-if="loadedDeleteModals.has(row.id || row.slug)"
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
