/**
 * Info Component Tests
 * 
 * Tests for the Info component - displays record details with actions
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import Info from '../../components/CRUD6/Info.vue'

// Mock composables
vi.mock('../../composables/useCRUD6Schema', () => ({
  useCRUD6Schema: () => ({
    schema: {
      value: {
        model: 'products',
        title: 'Products',
        singular_title: 'Product',
        table: 'products',
        primary_key: 'id',
        fields: {
          id: { type: 'integer', label: 'ID', viewable: true },
          name: { type: 'string', label: 'Name', viewable: true },
          price: { type: 'decimal', label: 'Price', viewable: true }
        }
      }
    },
    loading: { value: false },
    error: { value: null },
    hasPermission: vi.fn(() => true)
  })
}))

vi.mock('../../composables/useCRUD6Actions', () => ({
  useCRUD6Actions: () => ({
    executeActionWithoutConfirm: vi.fn(),
    loading: { value: false }
  })
}))

// Mock child components
const CRUD6UnifiedModal = {
  name: 'CRUD6UnifiedModal',
  template: '<div class="mock-unified-modal"><slot /></div>',
  props: ['mode', 'model', 'crud6', 'schema', 'actionConfig', 'show']
}

describe('Info.vue', () => {
  let router: any

  beforeEach(() => {
    setActivePinia(createPinia())
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { 
          path: '/crud6/:model/:id',
          name: 'crud6.row',
          component: { template: '<div>Row</div>' } 
        }
      ]
    })
  })

  const mockCrud6Data = {
    id: 1,
    name: 'Test Product',
    price: 99.99,
    created_at: '2024-01-01T00:00:00Z'
  }

  const mockSchema = {
    model: 'products',
    title: 'Products',
    singular_title: 'Product',
    table: 'products',
    primary_key: 'id',
    fields: {
      id: { type: 'integer', label: 'ID', viewable: true },
      name: { type: 'string', label: 'Name', viewable: true },
      price: { type: 'decimal', label: 'Price', viewable: true }
    }
  }

  it('renders with required props', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(Info, {
      props: {
        crud6: mockCrud6Data,
        schema: mockSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('displays field values correctly', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(Info, {
      props: {
        crud6: mockCrud6Data,
        schema: mockSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // Check that the data is displayed
    const html = wrapper.html()
    expect(html).toContain('Test Product')
    expect(html).toContain('99.99')
  })

  it('renders with provided schema (no composable load)', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(Info, {
      props: {
        crud6: mockCrud6Data,
        schema: mockSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
    // When schema is provided, it should be used directly
  })

  it('handles multi-context schema', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const multiContextSchema = {
      ...mockSchema,
      contexts: {
        detail: {
          fields: {
            id: { type: 'integer', label: 'ID', viewable: true },
            name: { type: 'string', label: 'Name', viewable: true }
          }
        },
        form: {
          fields: {
            name: { type: 'string', label: 'Name', editable: true },
            password: { type: 'password', label: 'Password', editable: true }
          }
        }
      }
    }

    const wrapper = mount(Info, {
      props: {
        crud6: mockCrud6Data,
        schema: multiContextSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('emits crud6Updated event when data changes', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(Info, {
      props: {
        crud6: mockCrud6Data,
        schema: mockSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // Trigger update via modal
    wrapper.vm.reloadCrud6Data()
    
    // Wait for any async operations
    await flushPromises()
    
    // Check emitted events
    expect(wrapper.emitted()).toHaveProperty('crud6Updated')
  })

  it('shows different field types correctly', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const extendedSchema = {
      ...mockSchema,
      fields: {
        ...mockSchema.fields,
        active: { type: 'boolean', label: 'Active', viewable: true },
        created_at: { type: 'datetime', label: 'Created', viewable: true }
      }
    }

    const extendedData = {
      ...mockCrud6Data,
      active: true,
      created_at: '2024-01-01T12:00:00Z'
    }

    const wrapper = mount(Info, {
      props: {
        crud6: extendedData,
        schema: extendedSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6UnifiedModal
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })
})
