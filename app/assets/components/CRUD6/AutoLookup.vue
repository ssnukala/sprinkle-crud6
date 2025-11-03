<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import type { CRUD6SprunjerResponse } from '@ssnukala/sprinkle-crud6/interfaces'
import { debugLog, debugWarn, debugError } from '../utils/debug'

/**
 * AutoLookup Component
 * 
 * A generic searchable auto-complete/lookup component that can fetch records
 * from any CRUD6 model and display them with customizable fields.
 * 
 * Features:
 * - Dynamic model support
 * - Configurable ID field
 * - Configurable display field(s)
 * - Real-time search
 * - Dropdown with results
 * - Keyboard navigation support
 * 
 * Usage Example:
 * ```vue
 * <UFCRUD6AutoLookup
 *   model="products"
 *   id-field="id"
 *   display-field="name"
 *   :display-fields="['sku', 'name']"
 *   placeholder="Search for a product..."
 *   v-model="selectedProductId"
 *   @select="handleProductSelect"
 * />
 * ```
 */

export interface AutoLookupProps {
  /** The CRUD6 model to search (e.g., 'products', 'categories') */
  model: string
  /** The field name to use as the ID/value (default: 'id') */
  idField?: string
  /** Single field name to display (use this OR displayFields) */
  displayField?: string
  /** Multiple field names to display (use this OR displayField) */
  displayFields?: string[]
  /** Placeholder text for the input */
  placeholder?: string
  /** Initial/selected value (ID) */
  modelValue?: number | string | null
  /** Minimum characters before searching */
  minSearchLength?: number
  /** Debounce delay in milliseconds */
  debounceDelay?: number
  /** Whether the field is required */
  required?: boolean
  /** Whether the field is disabled */
  disabled?: boolean
  /** Custom display format function */
  displayFormat?: (item: any) => string
}

const props = withDefaults(defineProps<AutoLookupProps>(), {
  idField: 'id',
  displayField: 'name',
  minSearchLength: 1,
  debounceDelay: 300,
  required: false,
  disabled: false
})

const emit = defineEmits<{
  'update:modelValue': [value: number | string | null]
  'select': [item: any]
  'clear': []
}>()

// State
const searchQuery = ref('')
const results = ref<any[]>([])
const isLoading = ref(false)
const isOpen = ref(false)
const selectedIndex = ref(-1)
const selectedItem = ref<any>(null)
const debounceTimer = ref<number | null>(null)

// Computed
const displayText = computed(() => {
  if (selectedItem.value) {
    if (props.displayFormat) {
      return props.displayFormat(selectedItem.value)
    }
    if (props.displayFields && props.displayFields.length > 0) {
      return props.displayFields
        .map(field => selectedItem.value[field])
        .filter(val => val)
        .join(' - ')
    }
    return selectedItem.value[props.displayField] || ''
  }
  return searchQuery.value
})

const hasResults = computed(() => results.value.length > 0)

// Methods
async function search(query: string) {
  if (query.length < props.minSearchLength) {
    results.value = []
    isOpen.value = false
    return
  }

  isLoading.value = true
  
  try {
    // Use the CRUD6 sprunje API with search parameter
    const response = await axios.get<CRUD6SprunjerResponse>(
      `/api/crud6/${props.model}`,
      {
        params: {
          search: query,
          size: 20 // Limit results
        }
      }
    )
    
    results.value = response.data.rows || []
    isOpen.value = results.value.length > 0
    selectedIndex.value = -1
  } catch (error) {
    debugError('[AutoLookup] Search failed:', error)
    results.value = []
    isOpen.value = false
  } finally {
    isLoading.value = false
  }
}

function debouncedSearch(query: string) {
  if (debounceTimer.value !== null) {
    clearTimeout(debounceTimer.value)
  }
  
  debounceTimer.value = window.setTimeout(() => {
    search(query)
  }, props.debounceDelay)
}

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  searchQuery.value = target.value
  selectedItem.value = null
  emit('update:modelValue', null)
  debouncedSearch(target.value)
}

function selectItem(item: any, index?: number) {
  selectedItem.value = item
  selectedIndex.value = index ?? -1
  searchQuery.value = props.displayFormat 
    ? props.displayFormat(item)
    : (item[props.displayField] || '')
  
  const idValue = item[props.idField]
  emit('update:modelValue', idValue)
  emit('select', item)
  
  isOpen.value = false
  results.value = []
}

function clearSelection() {
  selectedItem.value = null
  searchQuery.value = ''
  emit('update:modelValue', null)
  emit('clear')
  results.value = []
  isOpen.value = false
}

function handleKeydown(event: KeyboardEvent) {
  if (!isOpen.value && results.value.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, results.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0 && results.value[selectedIndex.value]) {
        selectItem(results.value[selectedIndex.value], selectedIndex.value)
      }
      break
    case 'Escape':
      event.preventDefault()
      isOpen.value = false
      selectedIndex.value = -1
      break
  }
}

function handleFocus() {
  if (searchQuery.value.length >= props.minSearchLength && results.value.length > 0) {
    isOpen.value = true
  }
}

function handleBlur() {
  // Delay to allow click events on dropdown items
  setTimeout(() => {
    isOpen.value = false
  }, 200)
}

function getItemDisplayText(item: any): string {
  if (props.displayFormat) {
    return props.displayFormat(item)
  }
  if (props.displayFields && props.displayFields.length > 0) {
    return props.displayFields
      .map(field => item[field])
      .filter(val => val)
      .join(' - ')
  }
  return item[props.displayField] || ''
}

// Load initial selection if modelValue is provided
watch(() => props.modelValue, async (newValue) => {
  if (newValue && !selectedItem.value) {
    try {
      const response = await axios.get(`/api/crud6/${props.model}/${newValue}`)
      if (response.data && response.data.data) {
        selectedItem.value = response.data.data
        searchQuery.value = getItemDisplayText(response.data.data)
      }
    } catch (error) {
      debugError('[AutoLookup] Failed to load initial value:', error)
    }
  }
}, { immediate: true })
</script>

<template>
  <div class="crud6-auto-lookup uk-inline uk-width-1-1">
    <div class="uk-form-controls">
      <div class="uk-inline uk-width-1-1" style="position: relative;">
        <!-- Search Icon -->
        <span class="uk-form-icon">
          <font-awesome-icon icon="search" fixed-width />
        </span>
        
        <!-- Input Field -->
        <input
          type="text"
          class="uk-input"
          :class="{ 'uk-form-success': selectedItem, 'uk-form-danger': required && !selectedItem }"
          :placeholder="placeholder || `Search ${model}...`"
          :value="displayText"
          :required="required"
          :disabled="disabled"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeydown"
          autocomplete="off"
        />
        
        <!-- Clear Button -->
        <button
          v-if="selectedItem && !disabled"
          type="button"
          class="uk-form-icon uk-form-icon-flip"
          @click="clearSelection"
          style="pointer-events: auto; cursor: pointer; background: none; border: none;"
        >
          <font-awesome-icon icon="times-circle" fixed-width />
        </button>
        
        <!-- Loading Spinner -->
        <span v-if="isLoading" class="uk-form-icon uk-form-icon-flip">
          <div uk-spinner="ratio: 0.6"></div>
        </span>
        
        <!-- Dropdown Results -->
        <div
          v-if="isOpen && hasResults"
          class="uk-dropdown uk-dropdown-bottom-left"
          style="display: block; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;"
        >
          <ul class="uk-nav uk-dropdown-nav">
            <li
              v-for="(item, index) in results"
              :key="item[idField]"
              :class="{ 'uk-active': index === selectedIndex }"
            >
              <a
                href="#"
                @click.prevent="selectItem(item, index)"
                @mouseenter="selectedIndex = index"
              >
                {{ getItemDisplayText(item) }}
              </a>
            </li>
          </ul>
        </div>
        
        <!-- No Results Message -->
        <div
          v-if="isOpen && !hasResults && !isLoading && searchQuery.length >= minSearchLength"
          class="uk-dropdown uk-dropdown-bottom-left"
          style="display: block; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;"
        >
          <div class="uk-padding-small uk-text-center uk-text-muted">
            No results found
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.crud6-auto-lookup {
  position: relative;
}

.uk-dropdown {
  max-height: 300px;
  overflow-y: auto;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  border: 1px solid #e5e5e5;
}

.uk-dropdown-nav > li.uk-active > a {
  background-color: #1e87f0;
  color: white;
}

.uk-dropdown-nav > li > a {
  padding: 8px 15px;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.uk-dropdown-nav > li > a:hover {
  background-color: #f8f8f8;
  color: #333;
}

.uk-dropdown-nav > li.uk-active > a:hover {
  background-color: #1e87f0;
  color: white;
}

.uk-form-icon-flip {
  right: 10px;
}
</style>
