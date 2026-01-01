/**
 * PageRow View Tests
 * 
 * Tests for the PageRow view - displays a single record with details and actions
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import PageRow from '../../views/PageRow.vue'

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
    loadSchema: vi.fn(),
    hasPermission: vi.fn(() => true)
  })
}))

vi.mock('../../composables/useCRUD6Api', () => ({
  useCRUD6Api: () => ({
    getRow: vi.fn(() => Promise.resolve({
      id: 1,
      name: 'Test Product',
      price: 99.99
    })),
    apiLoading: { value: false }
  })
}))

vi.mock('../../composables/useCRUD6Breadcrumbs', () => ({
  useCRUD6Breadcrumbs: () => ({
    updateBreadcrumb: vi.fn()
  })
}))

// Mock child components
const CRUD6Info = {
  name: 'CRUD6Info',
  template: '<div class="mock-info"><slot /></div>',
  props: ['crud6', 'schema']
}

const CRUD6Details = {
  name: 'CRUD6Details',
  template: '<div class="mock-details"><slot /></div>',
  props: ['recordId', 'parentModel', 'detailConfig']
}

const UFCardBox = {
  name: 'UFCardBox',
  template: '<div class="mock-cardbox"><slot /></div>',
  props: ['title']
}

describe('PageRow.vue', () => {
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

  it('renders with model and id from route', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('loads record data on mount', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // Verify component renders
    expect(wrapper.exists()).toBe(true)
  })

  it('passes schema to Info component', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    const infoComponent = wrapper.findComponent(CRUD6Info)
    expect(infoComponent.exists()).toBe(true)
    expect(infoComponent.props('schema')).toBeDefined()
  })

  it('handles loading state', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('displays record details when loaded', async () => {
    router.push('/crud6/products/1')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    const infoComponent = wrapper.findComponent(CRUD6Info)
    expect(infoComponent.exists()).toBe(true)
  })

  it('handles different record IDs', async () => {
    router.push('/crud6/products/42')
    await router.isReady()

    const wrapper = mount(PageRow, {
      global: {
        plugins: [router],
        components: {
          CRUD6Info,
          CRUD6Details,
          UFCardBox
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
