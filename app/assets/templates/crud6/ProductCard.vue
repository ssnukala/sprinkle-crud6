<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

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
