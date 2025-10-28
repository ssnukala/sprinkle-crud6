<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePageMeta } from '@userfrosting/sprinkle-core/stores'
import { useCRUD6Schema, useCRUD6Api, useCRUD6Relationships } from '@ssnukala/sprinkle-crud6/composables'

/**
 * Product Category Assignment Example with Auto Lookup
 * 
 * This example demonstrates how to manage many-to-many relationships
 * between products and categories using the relationship API.
 * 
 * ENHANCED VERSION: Now uses AutoLookup component for better UX
 * when searching for products to assign categories to.
 */

const route = useRoute()
const router = useRouter()
const page = usePageMeta()

// Set page metadata
page.title = 'Manage Product Categories'

// Get product ID from route or from lookup
const routeProductId = computed(() => route.params.id as string | undefined)
const selectedProductId = ref<number | null>(null)
const productId = computed(() => routeProductId.value || selectedProductId.value?.toString())

// Load product schema
const { schema: productSchema, loadSchema: loadProductSchema } = useCRUD6Schema()

// Load categories schema
const { schema: categorySchema, loadSchema: loadCategorySchema } = useCRUD6Schema()

// Fetch product details
const { fetchRow: fetchProduct, apiLoading: productLoading } = useCRUD6Api('products')

// Relationship management
const { 
  attachRelationships, 
  detachRelationships,
  apiLoading: relationshipLoading 
} = useCRUD6Relationships()

// State
const product = ref<any>(null)
const availableCategories = ref<any[]>([])
const selectedCategories = ref<number[]>([])
const assignedCategories = ref<number[]>([])

// Combined loading state
const isLoading = computed(() => 
  productLoading.value || relationshipLoading.value
)

// Load data when product is selected
async function loadProductData(prodId: string) {
  if (!prodId) return

  // Load product
  if (fetchProduct) {
    product.value = await fetchProduct(prodId)
  }

  // Load available categories (you would fetch this from API)
  // For demo purposes, using mock data
  availableCategories.value = [
    { id: 1, name: 'Electronics' },
    { id: 2, name: 'Clothing' },
    { id: 3, name: 'Books' },
    { id: 4, name: 'Home & Garden' },
    { id: 5, name: 'Sports' },
  ]

  // Load currently assigned categories (you would fetch this from API)
  // For demo purposes, using mock data
  assignedCategories.value = [1, 3] // Product is in Electronics and Books
  selectedCategories.value = [...assignedCategories.value]
}

// Load schemas on mount
onMounted(async () => {
  await loadProductSchema('products')
  await loadCategorySchema('categories')

  // If product ID is in route, load immediately
  if (routeProductId.value) {
    await loadProductData(routeProductId.value)
  }
})

// Handle product selection from AutoLookup
function handleProductSelect(productData: any) {
  selectedProductId.value = productData.id
  loadProductData(productData.id.toString())
}

// Toggle category selection
function toggleCategory(categoryId: number) {
  const index = selectedCategories.value.indexOf(categoryId)
  if (index > -1) {
    selectedCategories.value.splice(index, 1)
  } else {
    selectedCategories.value.push(categoryId)
  }
}

// Check if category is selected
function isCategorySelected(categoryId: number): boolean {
  return selectedCategories.value.includes(categoryId)
}

// Check if there are changes
const hasChanges = computed(() => {
  const current = [...selectedCategories.value].sort()
  const original = [...assignedCategories.value].sort()
  return JSON.stringify(current) !== JSON.stringify(original)
})

// Save changes
async function saveCategories() {
  if (!productId.value) return

  const toAttach = selectedCategories.value.filter(
    id => !assignedCategories.value.includes(id)
  )
  const toDetach = assignedCategories.value.filter(
    id => !selectedCategories.value.includes(id)
  )

  try {
    // Attach new categories
    if (toAttach.length > 0) {
      await attachRelationships('products', productId.value, 'categories', toAttach)
    }

    // Detach removed categories
    if (toDetach.length > 0) {
      await detachRelationships('products', productId.value, 'categories', toDetach)
    }

    // Update assigned categories
    assignedCategories.value = [...selectedCategories.value]

    console.log('[ProductCategories] Categories updated successfully')
  } catch (error) {
    console.error('[ProductCategories] Failed to update categories', error)
  }
}

// Cancel changes
function cancelChanges() {
  selectedCategories.value = [...assignedCategories.value]
  router.push('/crud6/products')
}
</script>

<template>
  <div class="product-categories-page">
    <div class="uk-container">
      <!-- Page Header -->
      <div class="uk-margin-medium-bottom">
        <h1 class="uk-heading-small">
          Manage Product Categories
        </h1>
        <p class="uk-text-meta">
          Search for a product and assign categories to it
        </p>
      </div>

      <!-- Product Selection (only show if not from route) -->
      <UFCardBox v-if="!routeProductId" title="Select Product">
        <div class="uk-margin">
          <label class="uk-form-label">
            Search for a Product
            <span class="uk-text-danger">*</span>
          </label>
          <UFCRUD6AutoLookup
            model="products"
            id-field="id"
            :display-fields="['sku', 'name']"
            placeholder="Type to search products by SKU or name..."
            v-model="selectedProductId"
            :required="true"
            @select="handleProductSelect"
          />
        </div>
      </UFCardBox>

      <!-- Product Info Display -->
      <UFCardBox v-if="product" class="uk-margin-top" title="Product Information">
        <dl class="uk-description-list uk-description-list-divider">
          <dt>Product Name:</dt>
          <dd><strong>{{ product.name }}</strong></dd>
          <dt>SKU:</dt>
          <dd>{{ product.sku }}</dd>
          <dt>Price:</dt>
          <dd>${{ product.price }}</dd>
        </dl>
      </UFCardBox>

      <!-- Loading State -->
      <div v-if="isLoading && !product" class="uk-text-center uk-padding">
        <div uk-spinner></div>
        <p>{{ $t('LOADING') }}</p>
      </div>

      <!-- Category Selection -->
      <UFCardBox v-if="product" class="uk-margin-top" title="Assign Categories">
        <div class="uk-grid-small uk-child-width-1-2@s uk-child-width-1-3@m" uk-grid>
          <div v-for="category in availableCategories" :key="category.id">
            <label class="category-checkbox">
              <input
                type="checkbox"
                class="uk-checkbox"
                :checked="isCategorySelected(category.id)"
                @change="toggleCategory(category.id)"
                :disabled="isLoading"
              />
              <span class="uk-margin-small-left">{{ category.name }}</span>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="uk-margin-top uk-text-right">
          <button 
            type="button"
            class="uk-button uk-button-default uk-margin-small-right"
            @click="cancelChanges"
            :disabled="isLoading">
            Cancel
          </button>
          <button 
            type="button"
            class="uk-button uk-button-primary"
            @click="saveCategories"
            :disabled="isLoading || !hasChanges">
            <span v-if="isLoading" uk-spinner="ratio: 0.5"></span>
            Save Categories
          </button>
        </div>

        <!-- Change Indicator -->
        <div v-if="hasChanges" class="uk-alert-primary uk-margin-top" uk-alert>
          <p>
            <strong>Unsaved changes:</strong> 
            {{ selectedCategories.length }} categories selected
          </p>
        </div>
      </UFCardBox>
    </div>
  </div>
</template>

<style scoped>
.product-categories-page {
  padding: 2rem 0;
}

.uk-heading-small {
  margin-bottom: 0.5rem;
}

.category-checkbox {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.category-checkbox:hover {
  background-color: #f8f8f8;
  border-color: #1e87f0;
}

.category-checkbox input[type="checkbox"] {
  cursor: pointer;
}

.uk-description-list dt {
  font-weight: normal;
  color: #999;
}

.uk-description-list dd {
  margin-left: 0;
  margin-bottom: 0.5rem;
}
</style>
