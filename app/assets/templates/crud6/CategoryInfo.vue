<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

interface Props {
  rowData: any
}

const props = defineProps<Props>()
</script>

<template>
  <div class="uk-text-meta">
    <strong>ID:</strong> {{ rowData.id }} | <strong>Slug:</strong> {{ rowData.slug }}<br/>
    <strong>Status:</strong> 
    <span v-if="rowData.is_active" class="uk-text-success">Active</span>
    <span v-else class="uk-text-danger">Inactive</span>
    <br/>
    {{ rowData.description }}
  </div>
</template>
