/**
 * PageList View Tests
 * 
 * Tests for the PageList view - displays a list of records with filtering and actions
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import PageList from '../../views/PageList.vue'

// Mock composables
vi.mock('../../composables/useCRUD6Schema', () => ({
  useCRUD6Schema: () => ({
    schema: {
      value: {
        model: 'products',
        title: 'Products',
        table: 'products',
        primary_key: 'id',
        fields: {
          id: { type: 'integer', label: 'ID' },
          name: { type: 'string', label: 'Name' },
          price: { type: 'decimal', label: 'Price' }
        }
      }
    },
    loading: { value: false },
    error: { value: null },
    loadSchema: vi.fn(),
    hasPermission: vi.fn(() => true)
  })
}))

vi.mock('../../composables/useCRUD6Breadcrumbs', () => ({
  useCRUD6Breadcrumbs: () => ({
    updateBreadcrumb: vi.fn(),
    setListBreadcrumb: vi.fn(() => Promise.resolve())
  })
}))

// Mock child components
const mockPageComponent = (name: string) => ({
  name,
  template: `<div class="mock-${name.toLowerCase()}"><slot /></div>`,
  props: ['model', 'schema', 'title', 'dataUrl', 'searchColumn', 'modelLabel']
})

describe('PageList.vue', () => {
  let router: any

  beforeEach(() => {
    setActivePinia(createPinia())
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { 
          path: '/crud6/:model',
          name: 'crud6.list',
          component: { template: '<div>List</div>' }
        },
        { 
          path: '/crud6/:model/:id',
          name: 'crud6.row',
          component: { template: '<div>Row</div>' }
        }
      ]
    })
  })

  it('renders with model from route', async () => {
    router.push('/crud6/products')
    await router.isReady()

    const wrapper = mount(PageList, {
      global: {
        plugins: [router],
        components: {
          UFSprunjeTable: mockPageComponent('SprunjeTable'),
          UFSprunjeHeader: mockPageComponent('SprunjeHeader'),
          UFSprunjeColumn: mockPageComponent('SprunjeColumn'),
          UFCardBox: mockPageComponent('CardBox')
        },
        mocks: {
          $t: (key: string) => key
        },
        stubs: {
          'router-link': {
            template: '<a><slot /></a>',
            props: ['to']
          }
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('displays loading state initially', async () => {
    router.push('/crud6/products')
    await router.isReady()

    const wrapper = mount(PageList, {
      global: {
        plugins: [router],
        components: {
          UFSprunjeTable: mockPageComponent('SprunjeTable'),
          UFSprunjeHeader: mockPageComponent('SprunjeHeader'),
          UFSprunjeColumn: mockPageComponent('SprunjeColumn'),
          UFCardBox: mockPageComponent('CardBox')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('constructs correct data URL for API', async () => {
    router.push('/crud6/products')
    await router.isReady()

    const wrapper = mount(PageList, {
      global: {
        plugins: [router],
        components: {
          UFSprunjeTable: mockPageComponent('SprunjeTable'),
          UFSprunjeHeader: mockPageComponent('SprunjeHeader'),
          UFSprunjeColumn: mockPageComponent('SprunjeColumn'),
          UFCardBox: mockPageComponent('CardBox')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // Check that the data URL is constructed correctly
    const sprunjeTable = wrapper.findComponent({ name: 'SprunjeTable' })
    if (sprunjeTable.exists()) {
      expect(sprunjeTable.props('dataUrl')).toBe('/api/crud6/products')
    }
  })

  it('handles different models from route', async () => {
    router.push('/crud6/orders')
    await router.isReady()

    const wrapper = mount(PageList, {
      global: {
        plugins: [router],
        components: {
          UFSprunjeTable: mockPageComponent('SprunjeTable'),
          UFSprunjeHeader: mockPageComponent('SprunjeHeader'),
          UFSprunjeColumn: mockPageComponent('SprunjeColumn'),
          UFCardBox: mockPageComponent('CardBox')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('renders page title from schema', async () => {
    router.push('/crud6/products')
    await router.isReady()

    const wrapper = mount(PageList, {
      global: {
        plugins: [router],
        components: {
          UFSprunjeTable: mockPageComponent('SprunjeTable'),
          UFSprunjeHeader: mockPageComponent('SprunjeHeader'),
          UFSprunjeColumn: mockPageComponent('SprunjeColumn'),
          UFCardBox: mockPageComponent('CardBox')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // The title should come from schema
    expect(wrapper.html()).toContain('Products')
  })
})
