<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import type { DetailEditableConfig } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Order Entry Page Example
 * 
 * This example demonstrates how to use the MasterDetailForm component
 * for order entry with line items (one-to-many relationship).
 */

const route = useRoute()
const router = useRouter()
const page = usePageMeta()

// Set page metadata
page.title = route.params.id ? 'Edit Order' : 'Create Order'

// Get order ID from route (if editing)
const orderId = computed(() => route.params.id as string | undefined)

// Configure detail section for order items
const detailConfig: DetailEditableConfig = {
  model: 'order_details',
  foreign_key: 'order_id',
  fields: ['line_number', 'sku', 'product_name', 'quantity', 'unit_price', 'line_total', 'notes'],
  title: 'Order Items',
  allow_add: true,
  allow_edit: true,
  allow_delete: true
}

// Handle save event
function handleSaved() {
  console.log('[OrderEntry] Order saved successfully')
  router.push('/crud6/orders')
}

// Handle cancel event
function handleCancelled() {
  console.log('[OrderEntry] Order entry cancelled')
  router.push('/crud6/orders')
}
</script>

<template>
  <div class="order-entry-page">
    <div class="uk-container uk-container-large">
      <!-- Page Header -->
      <div class="uk-margin-medium-bottom">
        <h1 class="uk-heading-small">
          {{ orderId ? 'Edit Order' : 'Create New Order' }}
        </h1>
        <p class="uk-text-meta">
          {{ orderId 
            ? 'Update the order details and line items below' 
            : 'Enter order information and add line items'
          }}
        </p>
      </div>

      <!-- Master-Detail Form -->
      <UFCRUD6MasterDetailForm
        model="orders"
        :record-id="orderId"
        :detail-config="detailConfig"
        @saved="handleSaved"
        @cancelled="handleCancelled"
      />
    </div>
  </div>
</template>

<style scoped>
.order-entry-page {
  padding: 2rem 0;
}

.uk-heading-small {
  margin-bottom: 0.5rem;
}
</style>
