<script setup lang="ts">
import { computed } from 'vue'

// Define props to receive row data
interface Props {
  rowData: any
}

const props = defineProps<Props>()

// Computed properties can be used with Vue features
const isActive = computed(() => props.rowData.is_active)
const statusClass = computed(() => isActive.value ? 'uk-label-success' : 'uk-label-warning')
</script>

<template>
  <div class="uk-card uk-card-small uk-card-default uk-card-body">
    <div class="uk-grid-small" uk-grid>
      <div class="uk-width-auto">
        <span class="uk-badge">ID: {{ rowData.id }}</span>
      </div>
      <div class="uk-width-auto">
        <span class="uk-badge">SKU: {{ rowData.sku }}</span>
      </div>
      <div class="uk-width-auto">
        <span class="uk-label" :class="statusClass">
          <template v-if="isActive">Active</template>
          <template v-else>Inactive</template>
        </span>
      </div>
    </div>
    <p class="uk-text-small uk-margin-small-top">{{ rowData.description }}</p>
  </div>
</template>
