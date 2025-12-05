# CRUD6 Best Practices: Leveraging UserFrosting 6 & Vue 3

**Date:** 2025-12-05  
**Version:** 0.7.x  
**Objective:** Establish scalable, maintainable patterns for schema-driven CRUD applications

---

## Table of Contents

1. [Architecture Principles](#architecture-principles)
2. [Vue 3 Best Practices](#vue-3-best-practices)
3. [UserFrosting 6 Integration](#userfrosting-6-integration)
4. [Schema Design Patterns](#schema-design-patterns)
5. [Component Architecture](#component-architecture)
6. [State Management](#state-management)
7. [Performance Optimization](#performance-optimization)
8. [Testing Strategy](#testing-strategy)
9. [Code Organization](#code-organization)

---

## Architecture Principles

### 1. Schema-First Design ⭐

**Principle:** All functionality should be driven by JSON schemas, not hard-coded logic.

**✅ DO:**
```json
{
  "model": "users",
  "actions": [
    {
      "key": "reset_password",
      "type": "api_call",
      "confirm": "Send password reset email to {{email}}?",
      "modal_config": { "type": "confirm" }
    }
  ]
}
```

**❌ DON'T:**
```vue
<!-- Hard-coded component logic -->
<button @click="resetPassword">Reset Password</button>
```

**Why:**
- ✅ Declarative and maintainable
- ✅ No code changes for new features
- ✅ Backend controls frontend capabilities
- ✅ Easy to test and document

### 2. Composition Over Configuration

**Principle:** Use Vue 3 Composition API for reusable, testable logic.

**✅ DO:**
```typescript
// Composable: useCRUD6Actions.ts
export function useCRUD6Actions(model?: string) {
  const loading = ref(false)
  const error = ref<ApiErrorResponse | null>(null)
  
  async function executeAction(action: ActionConfig, recordId: string) {
    loading.value = true
    try {
      // Execute logic
    } catch (err) {
      error.value = err
    } finally {
      loading.value = false
    }
  }
  
  return { loading, error, executeAction }
}
```

**❌ DON'T:**
```vue
<!-- Options API with mixed concerns -->
<script>
export default {
  data() {
    return { loading: false, error: null }
  },
  methods: {
    executeAction() { /* ... */ }
  }
}
</script>
```

**Why:**
- ✅ Better code reuse
- ✅ Easier testing (functions, not components)
- ✅ TypeScript support
- ✅ Clearer separation of concerns

### 3. Dependency Injection via Composables

**Principle:** Use composables as dependency containers, not components.

**✅ DO:**
```typescript
// Component gets dependencies via composables
const { schema, loadSchema } = useCRUD6Schema()
const { executeAction } = useCRUD6Actions(model.value)
const translator = useTranslator()
const alerts = useAlertsStore()
```

**❌ DON'T:**
```typescript
// Importing services directly
import { schemaService } from '@/services/schema'
import { apiClient } from '@/services/api'
```

**Why:**
- ✅ Easy to mock for testing
- ✅ Consistent with UF6 patterns
- ✅ Reactive state management
- ✅ Follows Vue 3 best practices

---

## Vue 3 Best Practices

### 1. Script Setup Syntax (Required)

**✅ DO:**
```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'

const props = defineProps<{
  crud6: CRUD6Interface
  schema: any
}>()

const emits = defineEmits<{
  saved: []
  cancelled: []
}>()

const isValid = computed(() => props.crud6.id > 0)
</script>
```

**❌ DON'T:**
```vue
<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    crud6: Object,
    schema: Object
  },
  emits: ['saved', 'cancelled'],
  computed: {
    isValid() {
      return this.crud6.id > 0
    }
  }
})
</script>
```

**Benefits:**
- ✅ Less boilerplate (~40% less code)
- ✅ Better TypeScript inference
- ✅ Faster compilation
- ✅ Industry standard (Vue 3.2+)

### 2. TypeScript Props & Emits

**✅ DO:**
```typescript
// Type-safe props
interface Props {
  crud6?: CRUD6Interface
  model: string
  schema?: CRUD6Schema
  mode?: 'create' | 'edit'
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'edit'
})

// Type-safe emits
const emits = defineEmits<{
  saved: [record: CRUD6Interface]
  error: [error: ApiErrorResponse]
}>()

// Usage with autocomplete
emits('saved', record)  // TypeScript validates payload
```

**❌ DON'T:**
```javascript
// Untyped props
const props = defineProps({
  crud6: Object,
  model: String,
  schema: Object
})

// Untyped emits
const emits = defineEmits(['saved', 'error'])
emits('saved', record)  // No type checking
```

### 3. Computed vs Reactive

**✅ DO:**
```typescript
// Use computed for derived state
const filteredActions = computed(() => {
  return schema.value?.actions?.filter(isActionVisible) || []
})

// Use ref for mutable state
const selectedId = ref<number | null>(null)
const formData = ref<Record<string, any>>({})

// Use reactive for object state
const formState = reactive({
  loading: false,
  errors: {},
  touched: {}
})
```

**❌ DON'T:**
```typescript
// Don't use watchers for derived state
const filteredActions = ref([])
watch(() => schema.value, (newSchema) => {
  filteredActions.value = newSchema?.actions?.filter(isActionVisible) || []
})

// Don't use reactive for primitives
const selectedId = reactive({ value: null })  // Overkill
```

### 4. Template Refs (Typed)

**✅ DO:**
```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { ComponentPublicInstance } from 'vue'
import CRUD6Form from './Form.vue'

const formRef = ref<ComponentPublicInstance<typeof CRUD6Form>>()

onMounted(() => {
  formRef.value?.focusFirstField()
})
</script>

<template>
  <CRUD6Form ref="formRef" />
</template>
```

### 5. Slots with Scoped Bindings

**✅ DO:**
```vue
<!-- UnifiedModal.vue -->
<template>
  <slot name="trigger" :modal-id="modalId" :open-modal="openModal">
    <!-- Default trigger button -->
    <button @click="openModal">{{ action.label }}</button>
  </slot>
</template>

<!-- Usage -->
<UnifiedModal :action="deleteAction">
  <template #trigger="{ modalId, openModal }">
    <a :href="`#${modalId}`" uk-toggle class="custom-trigger">
      Delete
    </a>
  </template>
</UnifiedModal>
```

**Why:**
- ✅ Maximum flexibility
- ✅ Type-safe slot props
- ✅ Maintains encapsulation

---

## UserFrosting 6 Integration

### 1. Use UF6 Stores (Pinia)

**✅ DO:**
```typescript
import { useAlertsStore, useTranslator, usePageMeta } from '@userfrosting/sprinkle-core/stores'

const alerts = useAlertsStore()
const translator = useTranslator()
const page = usePageMeta()

// Show success alert
alerts.push({
  title: translator.translate('SUCCESS'),
  description: translator.translate('RECORD_SAVED'),
  style: Severity.Success
})

// Update page metadata
page.title = translator.translate('USERS.PAGE_TITLE')
```

**Why:**
- ✅ Consistent UX with UserFrosting
- ✅ Centralized state management
- ✅ Built-in i18n support

### 2. Leverage UF Components

**✅ DO:**
```vue
<!-- Use UserFrosting's modal components -->
<UFModal :id="modalId" closable>
  <template #header>{{ title }}</template>
  <template #default>
    <CRUD6Form :schema="schema" />
  </template>
</UFModal>

<UFModalConfirmation
  :id="confirmId"
  :title="deleteTitle"
  :acceptSeverity="Severity.Danger"
  @confirmed="handleDelete">
  <template #prompt>
    <div v-html="confirmMessage"></div>
  </template>
</UFModalConfirmation>

<!-- Use UF card components -->
<UFCardBox>
  <CRUD6Info :crud6="record" :schema="schema" />
</UFCardBox>
```

**Why:**
- ✅ Consistent styling with UF theme
- ✅ UIKit integration handled
- ✅ Accessibility features built-in

### 3. Translation Keys (i18n)

**✅ DO:**
```json
// Schema with translation keys
{
  "model": "users",
  "title": "USERS.PAGE_TITLE",
  "singular_title": "USER.SINGULAR",
  "fields": {
    "user_name": {
      "label": "USER.USERNAME",
      "validation": {
        "required": true
      }
    }
  },
  "actions": [
    {
      "key": "delete",
      "label": "USER.DELETE",
      "confirm": "USER.DELETE_CONFIRM"
    }
  ]
}
```

```typescript
// Component usage
const translator = useTranslator()
const title = translator.translate('USERS.PAGE_TITLE')
const confirmMsg = translator.translate('USER.DELETE_CONFIRM', { name: record.user_name })
```

**❌ DON'T:**
```json
// Hard-coded English text
{
  "model": "users",
  "title": "User Management",
  "singular_title": "User"
}
```

**Why:**
- ✅ Multi-language support
- ✅ Centralized text management
- ✅ Variable interpolation
- ✅ Consistent with UF patterns

### 4. Permission-Based Rendering

**✅ DO:**
```vue
<script setup lang="ts">
const { hasPermission } = useCRUD6Schema()

const canCreate = computed(() => hasPermission('create'))
const canEdit = computed(() => hasPermission('update'))
const canDelete = computed(() => hasPermission('delete'))
</script>

<template>
  <UnifiedModal
    v-if="canCreate"
    :action="createAction"
    @confirmed="handleCreate" />
    
  <UnifiedModal
    v-if="canEdit"
    :action="editAction"
    :record="record"
    @confirmed="handleEdit" />
</template>
```

**Why:**
- ✅ Security at component level
- ✅ Backend validation still required
- ✅ Better UX (hide disabled actions)

---

## Schema Design Patterns

### 1. Default Actions (Backend Auto-Injection)

**Backend Service:**
```php
// app/src/ServicesProvider/SchemaService.php
class SchemaService
{
    protected function enrichSchema(array $schema, ?string $context): array
    {
        // Auto-inject standard CRUD actions if not defined
        if (!isset($schema['actions'])) {
            $schema['actions'] = [];
        }
        
        $defaultActions = [
            [
                'key' => 'create',
                'label' => 'CRUD6.CREATE',
                'icon' => 'plus',
                'type' => 'crud_operation',
                'operation' => 'create',
                'style' => 'primary',
                'permission' => 'create',
                'scope' => 'list',
                'modal_config' => [
                    'type' => 'form',
                    'context' => 'create'
                ]
            ],
            [
                'key' => 'edit',
                'label' => 'CRUD6.EDIT',
                'icon' => 'pen-to-square',
                'type' => 'crud_operation',
                'operation' => 'update',
                'style' => 'primary',
                'permission' => 'update',
                'scope' => 'detail',
                'modal_config' => [
                    'type' => 'form',
                    'context' => 'edit'
                ]
            ],
            [
                'key' => 'delete',
                'label' => 'CRUD6.DELETE',
                'icon' => 'trash',
                'type' => 'crud_operation',
                'operation' => 'delete',
                'style' => 'danger',
                'permission' => 'delete',
                'scope' => 'detail',
                'confirm' => 'CRUD6.DELETE_CONFIRM',
                'modal_config' => [
                    'type' => 'confirm',
                    'buttons' => 'yes_no',
                    'warning' => 'WARNING_CANNOT_UNDONE'
                ]
            ]
        ];
        
        // Merge, allowing schema to override defaults
        $actionsByKey = [];
        foreach ($schema['actions'] as $action) {
            $actionsByKey[$action['key']] = $action;
        }
        
        foreach ($defaultActions as $defaultAction) {
            if (!isset($actionsByKey[$defaultAction['key']])) {
                $schema['actions'][] = $defaultAction;
            }
        }
        
        return $schema;
    }
}
```

### 2. Action Scope Filtering

**Schema:**
```json
{
  "actions": [
    {
      "key": "create",
      "scope": "list"
    },
    {
      "key": "edit",
      "scope": "detail"
    },
    {
      "key": "export",
      "scope": "list,detail"
    }
  ]
}
```

**Component:**
```typescript
function getScopedActions(scope: 'list' | 'detail'): ActionConfig[] {
  return schema.value?.actions?.filter(action => {
    if (!action.scope) return true  // No scope = show everywhere
    const scopes = action.scope.split(',').map(s => s.trim())
    return scopes.includes(scope)
  }) || []
}
```

### 3. Conditional Visibility

**Schema:**
```json
{
  "actions": [
    {
      "key": "approve",
      "label": "Approve Order",
      "visible_when": {
        "status": "pending",
        "flag_approved": false
      },
      "permission": "approve_orders"
    },
    {
      "key": "cancel",
      "label": "Cancel Order",
      "visible_when": {
        "status": ["pending", "approved"]
      }
    }
  ]
}
```

**Component:**
```typescript
function isActionVisible(action: ActionConfig, record: any): boolean {
  // Permission check
  if (action.permission && !hasPermission(action.permission)) {
    return false
  }
  
  // Conditional visibility
  if (action.visible_when) {
    for (const [field, expectedValue] of Object.entries(action.visible_when)) {
      const actualValue = record[field]
      
      // Array of allowed values
      if (Array.isArray(expectedValue)) {
        if (!expectedValue.includes(actualValue)) return false
      }
      // Boolean comparison with normalization
      else if (typeof expectedValue === 'boolean') {
        const normalized = actualValue === 1 || actualValue === '1' || actualValue === true
        if (normalized !== expectedValue) return false
      }
      // Strict equality
      else if (actualValue !== expectedValue) {
        return false
      }
    }
  }
  
  return true
}
```

### 4. Multi-Context Schemas

**Backend Response:**
```json
{
  "model": "users",
  "contexts": {
    "list": {
      "fields": {
        "id": { "type": "integer", "sortable": true },
        "user_name": { "type": "string", "searchable": true },
        "email": { "type": "email" }
      }
    },
    "detail": {
      "fields": {
        "id": { "type": "integer" },
        "user_name": { "type": "string" },
        "email": { "type": "email" },
        "created_at": { "type": "datetime" },
        "updated_at": { "type": "datetime" }
      }
    },
    "form": {
      "fields": {
        "user_name": { "type": "string", "required": true },
        "email": { "type": "email", "required": true },
        "password": { 
          "type": "password",
          "required": true,
          "validation": {
            "min": 8,
            "match": true
          }
        }
      }
    }
  }
}
```

**Component:**
```typescript
// Request multiple contexts in single call
const schema = await loadSchema(model, 'list,form')

// Extract specific context
const listFields = schema.contexts.list.fields
const formFields = schema.contexts.form.fields
```

---

## Component Architecture

### 1. Single Responsibility Principle

**✅ DO:**
```
UnifiedModal.vue (600 lines)
├── Responsibility: Modal orchestration only
├── Embeds: CRUD6Form for form rendering
├── Emits: confirmed, cancelled, success, error
└── Does NOT: Handle API calls, business logic

CRUD6Form.vue (543 lines)
├── Responsibility: Form rendering only
├── Uses: useCRUD6Api for submissions
├── Emits: success, error
└── Does NOT: Modal logic, action handling

Info.vue (408 lines)
├── Responsibility: Record display + action buttons
├── Uses: UnifiedModal for actions
├── Emits: crud6Updated
└── Does NOT: Form logic, API calls
```

### 2. Prop Drilling vs Provide/Inject

**✅ DO (Prop Drilling for 1-2 levels):**
```vue
<!-- PageRow.vue -->
<template>
  <Info :crud6="record" :schema="schema" :model="model" />
</template>

<!-- Info.vue -->
<template>
  <UnifiedModal :action="action" :record="crud6" :schema="schema" :model="model" />
</template>
```

**✅ DO (Provide/Inject for 3+ levels or shared context):**
```vue
<!-- PageRow.vue -->
<script setup>
import { provide } from 'vue'
provide('crud6Context', {
  model: computed(() => model.value),
  schema: computed(() => schema.value),
  record: computed(() => record.value)
})
</script>

<!-- Deep child component -->
<script setup>
import { inject } from 'vue'
const context = inject('crud6Context')
const model = context.model.value
</script>
```

**When to use which:**
- Prop drilling: Parent-child, 1-2 levels, explicit dependencies
- Provide/Inject: Deep nesting, shared context, implicit dependencies

### 3. Component Composition

**✅ DO:**
```vue
<!-- UnifiedModal.vue: Compose Form component -->
<template>
  <div uk-modal>
    <div v-if="modalConfig.type === 'form'">
      <CRUD6Form
          :crud6="record"
          :model="model"
          :schema="schema"
          :context="modalConfig.context"
          @success="handleFormSuccess"
          @error="handleFormError" />
    </div>
    <div v-else-if="modalConfig.type === 'input'">
      <!-- Inline field rendering -->
    </div>
  </div>
</template>
```

**❌ DON'T:**
```vue
<!-- UnifiedModal.vue: Duplicate form logic -->
<template>
  <div uk-modal>
    <div v-if="modalConfig.type === 'form'">
      <!-- Duplicated form field rendering logic -->
      <div v-for="field in formFields">
        <input :type="getFieldType(field)" />
      </div>
    </div>
  </div>
</template>
```

---

## State Management

### 1. Pinia Store for Global State

**✅ DO:**
```typescript
// useCRUD6SchemaStore.ts (Global schema cache)
export const useCRUD6SchemaStore = defineStore('crud6-schemas', () => {
  const schemas = ref<Record<string, CRUD6Schema>>({})
  
  function getSchema(model: string, context?: string): CRUD6Schema | undefined {
    const key = `${model}:${context || 'full'}`
    return schemas.value[key]
  }
  
  function setSchema(model: string, schema: CRUD6Schema, context?: string) {
    const key = `${model}:${context || 'full'}`
    schemas.value[key] = schema
  }
  
  return { schemas, getSchema, setSchema }
})
```

**When to use Pinia:**
- ✅ Cross-component state (schema cache)
- ✅ Global app state (user preferences)
- ✅ Persistent state (localStorage sync)

### 2. Composable for Component State

**✅ DO:**
```typescript
// useCRUD6Api.ts (Component-level state)
export function useCRUD6Api(modelName?: string) {
  const loading = ref(false)
  const error = ref<ApiErrorResponse | null>(null)
  const formData = ref<Record<string, any>>({})
  
  async function createRow(data: any) {
    loading.value = true
    try {
      const response = await axios.post(`/api/crud6/${modelName}`, data)
      return response.data
    } catch (err) {
      error.value = err.response?.data
      throw err
    } finally {
      loading.value = false
    }
  }
  
  return { loading, error, formData, createRow }
}
```

**When to use composables:**
- ✅ Component-scoped state
- ✅ Reusable logic
- ✅ Testable functions

### 3. Schema Cache Strategy

**Current Implementation (Excellent):**
```typescript
// Cache by model:context
const cacheKey = `${model}:${context || 'full'}`

// Example keys:
// - "users:full" (all fields, all contexts)
// - "users:list" (list context only)
// - "users:form" (form context only)
// - "users:list,form" (multiple contexts)

// Benefits:
// ✅ Prevents duplicate API calls
// ✅ Supports context-specific caching
// ✅ Efficient memory usage
```

---

## Performance Optimization

### 1. Lazy Loading & Code Splitting

**✅ DO:**
```typescript
// routes.ts
const routes = [
  {
    path: '/crud6/:model',
    component: () => import('./views/PageList.vue'),  // Lazy load
    children: [
      {
        path: ':id',
        component: () => import('./views/PageRow.vue')  // Lazy load
      }
    ]
  }
]

// Component
const CRUD6Form = defineAsyncComponent(() => import('./Form.vue'))
```

**Why:**
- ✅ Faster initial load
- ✅ Smaller bundle size
- ✅ Better perceived performance

### 2. Computed Caching

**✅ DO:**
```typescript
// Computed values are cached until dependencies change
const filteredActions = computed(() => {
  return schema.value?.actions
    ?.filter(a => isActionVisible(a, record.value))
    ?.map(a => enrichAction(a)) || []
})
```

**❌ DON'T:**
```typescript
// Re-runs on every render
function getFilteredActions() {
  return schema.value?.actions
    ?.filter(a => isActionVisible(a, record.value))
    ?.map(a => enrichAction(a)) || []
}
```

### 3. V-Once for Static Content

**✅ DO:**
```vue
<template>
  <!-- Static schema-driven labels -->
  <label v-once>{{ translator.translate(field.label) }}</label>
  
  <!-- Dynamic user data -->
  <span>{{ record.user_name }}</span>
</template>
```

### 4. Virtual Scrolling for Large Lists

**✅ DO (for 100+ rows):**
```vue
<script setup>
import { useVirtualList } from '@vueuse/core'

const { list, containerProps, wrapperProps } = useVirtualList(
  rows,
  { itemHeight: 50 }
)
</script>

<template>
  <div v-bind="containerProps" style="height: 400px; overflow: auto;">
    <div v-bind="wrapperProps">
      <div v-for="{ data, index } in list" :key="index">
        {{ data.user_name }}
      </div>
    </div>
  </div>
</template>
```

---

## Testing Strategy

### 1. Composable Testing

**✅ DO:**
```typescript
// useCRUD6Actions.test.ts
import { describe, it, expect, vi } from 'vitest'
import { useCRUD6Actions } from './useCRUD6Actions'

describe('useCRUD6Actions', () => {
  it('should execute field update action', async () => {
    const { executeAction } = useCRUD6Actions('users')
    
    const action = {
      key: 'toggle_enabled',
      type: 'field_update',
      field: 'flag_enabled',
      toggle: true
    }
    
    const result = await executeAction(action, '123', { flag_enabled: false })
    
    expect(result).toBe(true)
    // Assert API was called with correct data
  })
})
```

### 2. Component Testing

**✅ DO:**
```typescript
// UnifiedModal.test.ts
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import UnifiedModal from './UnifiedModal.vue'

describe('UnifiedModal', () => {
  it('should render confirm modal', () => {
    const wrapper = mount(UnifiedModal, {
      props: {
        action: {
          key: 'delete',
          confirm: 'Are you sure?',
          modal_config: { type: 'confirm' }
        }
      }
    })
    
    expect(wrapper.find('[data-test="modal-confirm-delete"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Are you sure?')
  })
  
  it('should emit confirmed event', async () => {
    const wrapper = mount(UnifiedModal, { /* ... */ })
    
    await wrapper.find('[data-test="btn-confirm"]').trigger('click')
    
    expect(wrapper.emitted('confirmed')).toBeTruthy()
  })
})
```

### 3. Schema Validation Tests

**✅ DO:**
```typescript
// schemaValidation.test.ts
import { validateSchema } from './schemaValidation'

describe('Schema Validation', () => {
  it('should validate action schema', () => {
    const action = {
      key: 'delete',
      type: 'crud_operation',
      modal_config: { type: 'confirm' }
    }
    
    const result = validateSchema(action)
    
    expect(result.valid).toBe(true)
    expect(result.errors).toEqual([])
  })
  
  it('should detect invalid action type', () => {
    const action = {
      key: 'invalid',
      type: 'unknown_type'
    }
    
    const result = validateSchema(action)
    
    expect(result.valid).toBe(false)
    expect(result.errors).toContain('Invalid action type')
  })
})
```

---

## Code Organization

### 1. File Structure

```
app/assets/
├── components/
│   └── CRUD6/
│       ├── UnifiedModal.vue          (Main modal component)
│       ├── Form.vue                  (Form rendering)
│       ├── Info.vue                  (Record display)
│       ├── Details.vue               (Detail view)
│       ├── DetailGrid.vue            (Related records)
│       ├── AutoLookup.vue            (Field component)
│       ├── ToggleSwitch.vue          (Field component)
│       ├── GoogleAddress.vue         (Field component)
│       └── MasterDetailForm.vue      (Specialized form)
├── composables/
│   ├── useCRUD6Schema.ts             (Schema loading)
│   ├── useCRUD6Api.ts                (API operations)
│   ├── useCRUD6Actions.ts            (Action execution)
│   ├── useCRUD6Breadcrumbs.ts        (Breadcrumb management)
│   └── useCRUD6FieldRenderer.ts      (Field rendering logic)
├── stores/
│   └── useCRUD6SchemaStore.ts        (Global schema cache)
├── utils/
│   ├── actionInference.ts            (Action enrichment)
│   ├── fieldTypes.ts                 (Field type utilities)
│   └── debug.ts                      (Debug logging)
├── views/
│   ├── PageList.vue                  (List view)
│   ├── PageRow.vue                   (Detail view)
│   └── PageMasterDetail.vue          (Master-detail view)
└── interfaces/
    └── index.ts                      (TypeScript interfaces)
```

### 2. Naming Conventions

**Components:**
- PascalCase: `UnifiedModal.vue`, `CRUD6Form.vue`
- Prefix with `CRUD6` for exports: `CRUD6UnifiedModal`

**Composables:**
- camelCase with `use` prefix: `useCRUD6Schema()`, `useCRUD6Actions()`

**Stores:**
- camelCase with `use` prefix: `useCRUD6SchemaStore()`

**Types/Interfaces:**
- PascalCase: `ActionConfig`, `CRUD6Interface`, `SchemaField`

**Files:**
- PascalCase for components: `UnifiedModal.vue`
- camelCase for composables: `useCRUD6Schema.ts`
- kebab-case for utilities: `action-inference.ts` (but we use camelCase currently)

### 3. Import Organization

**✅ DO:**
```typescript
// 1. Vue imports
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

// 2. Third-party imports
import axios from 'axios'
import UIkit from 'uikit'

// 3. UserFrosting imports
import { useAlertsStore, useTranslator } from '@userfrosting/sprinkle-core/stores'
import { Severity } from '@userfrosting/sprinkle-core/interfaces'

// 4. Local imports - composables
import { useCRUD6Schema, useCRUD6Actions } from '@ssnukala/sprinkle-crud6/composables'

// 5. Local imports - components
import CRUD6Form from './Form.vue'
import CRUD6UnifiedModal from './UnifiedModal.vue'

// 6. Local imports - types
import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
import type { ActionConfig } from '@ssnukala/sprinkle-crud6/composables'

// 7. Local imports - utilities
import { debugLog, debugError } from '../../utils/debug'
```

---

## Summary: Key Recommendations

### Architecture
1. ✅ **100% Schema-Driven** - All features defined in JSON
2. ✅ **Composition API** - Use `<script setup>` everywhere
3. ✅ **TypeScript** - Full type safety with interfaces
4. ✅ **Single Responsibility** - One component, one purpose

### UserFrosting Integration
1. ✅ **Use UF Stores** - Pinia for alerts, i18n, page meta
2. ✅ **Use UF Components** - UFModal, UFModalConfirmation
3. ✅ **Translation Keys** - All text via i18n
4. ✅ **Permission System** - Schema-driven permission checks

### Vue 3 Best Practices
1. ✅ **Script Setup** - Less boilerplate, better DX
2. ✅ **Typed Props/Emits** - Full TypeScript support
3. ✅ **Computed Caching** - Performance optimization
4. ✅ **Composable Logic** - Reusable, testable functions

### Performance
1. ✅ **Lazy Loading** - Code splitting for routes
2. ✅ **Schema Caching** - Prevent duplicate API calls
3. ✅ **Virtual Scrolling** - For large data sets
4. ✅ **V-Once Directive** - Static content optimization

### Testing
1. ✅ **Composable Tests** - Unit test business logic
2. ✅ **Component Tests** - Integration test UI
3. ✅ **Schema Validation** - Test schema contracts
4. ✅ **E2E Tests** - Critical user flows

---

**This architecture leverages the best of Vue 3 and UserFrosting 6 while maintaining the core principle: Everything driven by JSON schemas.**
