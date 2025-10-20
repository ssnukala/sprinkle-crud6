<script setup lang="ts">
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
