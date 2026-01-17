<script setup lang="ts">
/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import { ref } from 'vue'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'

/**
 * Auto Lookup Example
 * 
 * This example demonstrates how to use the AutoLookup component
 * for searchable product selection in various scenarios.
 */

const page = usePageMeta()
page.title = 'Auto Lookup Examples'

// Example 1: Simple product lookup by ID and name
const selectedProductId = ref<number | null>(null)
const selectedProduct = ref<any>(null)

function handleProductSelect(product: any) {
  selectedProduct.value = product
  console.log('[Example 1] Product selected:', product)
}

function handleProductClear() {
  selectedProduct.value = null
  console.log('[Example 1] Product selection cleared')
}

// Example 2: Category lookup with custom display
const selectedCategoryId = ref<number | null>(null)
const selectedCategory = ref<any>(null)

function handleCategorySelect(category: any) {
  selectedCategory.value = category
  console.log('[Example 2] Category selected:', category)
}

// Example 3: Product lookup with SKU and name display
const selectedProductBySku = ref<number | null>(null)

function handleProductBySkuSelect(product: any) {
  console.log('[Example 3] Product selected by SKU:', product)
}

// Example 4: Custom format function
const selectedProductCustom = ref<number | null>(null)

function customProductFormat(item: any): string {
  return `${item.sku} - ${item.name} ($${item.price})`
}

function handleCustomSelect(product: any) {
  console.log('[Example 4] Product with custom format:', product)
}
</script>

<template>
  <div class="auto-lookup-examples">
    <div class="uk-container">
      <h1 class="uk-heading-small">Auto Lookup Component Examples</h1>
      <p class="uk-text-lead">
        Demonstrates various use cases for the generic AutoLookup component
      </p>

      <div class="uk-grid-medium uk-child-width-1-1@s" uk-grid>
        <!-- Example 1: Basic Product Lookup -->
        <div>
          <UFCardBox title="Example 1: Basic Product Lookup">
            <div class="uk-margin">
              <label class="uk-form-label">
                Search for a Product
                <span class="uk-text-danger">*</span>
              </label>
              <UFCRUD6AutoLookup
                model="products"
                id-field="id"
                display-field="name"
                placeholder="Type to search products..."
                v-model="selectedProductId"
                :required="true"
                @select="handleProductSelect"
                @clear="handleProductClear"
              />
            </div>
            
            <div v-if="selectedProduct" class="uk-alert-success uk-margin-top" uk-alert>
              <h4>Selected Product:</h4>
              <dl class="uk-description-list">
                <dt>ID:</dt>
                <dd>{{ selectedProduct.id }}</dd>
                <dt>Name:</dt>
                <dd>{{ selectedProduct.name }}</dd>
                <dt>SKU:</dt>
                <dd>{{ selectedProduct.sku }}</dd>
                <dt>Price:</dt>
                <dd>${{ selectedProduct.price }}</dd>
              </dl>
            </div>
          </UFCardBox>
        </div>

        <!-- Example 2: Category Lookup -->
        <div>
          <UFCardBox title="Example 2: Category Lookup">
            <div class="uk-margin">
              <label class="uk-form-label">Select Category</label>
              <UFCRUD6AutoLookup
                model="categories"
                id-field="id"
                display-field="name"
                placeholder="Search categories..."
                v-model="selectedCategoryId"
                @select="handleCategorySelect"
              />
            </div>
            
            <div v-if="selectedCategory" class="uk-alert-primary uk-margin-top" uk-alert>
              <p>
                <strong>Selected:</strong> {{ selectedCategory.name }}
                (ID: {{ selectedCategory.id }})
              </p>
            </div>
          </UFCardBox>
        </div>

        <!-- Example 3: Multiple Display Fields -->
        <div>
          <UFCardBox title="Example 3: Product Lookup with Multiple Fields">
            <div class="uk-margin">
              <label class="uk-form-label">Search by SKU or Name</label>
              <UFCRUD6AutoLookup
                model="products"
                id-field="id"
                :display-fields="['sku', 'name']"
                placeholder="Search by SKU or product name..."
                v-model="selectedProductBySku"
                @select="handleProductBySkuSelect"
              />
            </div>
            
            <div class="uk-text-meta uk-margin-top">
              <p>This example shows products with both SKU and name in the dropdown.</p>
            </div>
          </UFCardBox>
        </div>

        <!-- Example 4: Custom Display Format -->
        <div>
          <UFCardBox title="Example 4: Custom Display Format">
            <div class="uk-margin">
              <label class="uk-form-label">Product with Price</label>
              <UFCRUD6AutoLookup
                model="products"
                id-field="id"
                :display-format="customProductFormat"
                placeholder="Search products with pricing..."
                v-model="selectedProductCustom"
                @select="handleCustomSelect"
              />
            </div>
            
            <div class="uk-text-meta uk-margin-top">
              <p>
                This example uses a custom format function to display:
                <code>SKU - Name ($Price)</code>
              </p>
            </div>
          </UFCardBox>
        </div>

        <!-- Example 5: In a Form Context -->
        <div>
          <UFCardBox title="Example 5: Order Entry Form">
            <form class="uk-form-stacked">
              <div class="uk-margin">
                <label class="uk-form-label">
                  Product
                  <span class="uk-text-danger">*</span>
                </label>
                <UFCRUD6AutoLookup
                  model="products"
                  id-field="id"
                  :display-fields="['sku', 'name']"
                  placeholder="Select product for order..."
                  :required="true"
                />
              </div>
              
              <div class="uk-grid-small" uk-grid>
                <div class="uk-width-1-2@s">
                  <label class="uk-form-label">Quantity</label>
                  <input 
                    type="number" 
                    class="uk-input" 
                    placeholder="Quantity"
                    min="1"
                  />
                </div>
                <div class="uk-width-1-2@s">
                  <label class="uk-form-label">Unit Price</label>
                  <input 
                    type="number" 
                    class="uk-input" 
                    placeholder="Unit Price"
                    step="0.01"
                  />
                </div>
              </div>
              
              <div class="uk-margin-top">
                <button class="uk-button uk-button-primary" type="submit">
                  Add to Order
                </button>
              </div>
            </form>
          </UFCardBox>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auto-lookup-examples {
  padding: 2rem 0;
}

.uk-heading-small {
  margin-bottom: 0.5rem;
}

.uk-description-list dt {
  font-weight: bold;
  margin-top: 0.5rem;
}

.uk-description-list dd {
  margin-left: 1rem;
}
</style>
