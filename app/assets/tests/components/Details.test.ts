/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Details Component Tests
 * 
 * Tests for the Details component - displays related records in a table
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Details from '../../components/CRUD6/Details.vue'
import type { DetailConfig } from '../../composables/useCRUD6Relationships'

// Mock the schema store
vi.mock('../../stores/useCRUD6SchemaStore', () => ({
  useCRUD6SchemaStore: () => ({
    getSchema: vi.fn(() => ({
      title: 'Order Items',
      fields: {
        id: { label: 'ID', type: 'integer' },
        product_name: { label: 'Product', type: 'string' },
        quantity: { label: 'Quantity', type: 'integer' },
        active: { label: 'Active', type: 'boolean' }
      }
    })),
    loadSchema: vi.fn()
  })
}))

// Mock UFCardBox and UFSprunjeTable components
const UFCardBox = {
  name: 'UFCardBox',
  template: '<div class="mock-cardbox"><slot /></div>',
  props: ['title']
}

const UFSprunjeTable = {
  name: 'UFSprunjeTable',
  template: '<div class="mock-sprunje-table"><slot name="header" /><slot name="body" :row="testRow" /></div>',
  props: ['dataUrl', 'searchColumn', 'hideFilters'],
  data() {
    return {
      testRow: {
        id: 1,
        product_name: 'Test Product',
        quantity: 5,
        active: true
      }
    }
  }
}

const UFSprunjeHeader = {
  name: 'UFSprunjeHeader',
  template: '<th><slot /></th>',
  props: ['sort']
}

const UFSprunjeColumn = {
  name: 'UFSprunjeColumn',
  template: '<td><slot /></td>'
}

const UFLabel = {
  name: 'UFLabel',
  template: '<span :class="severity"><slot /></span>',
  props: ['severity']
}

describe('Details.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  const mockDetailConfig: DetailConfig = {
    model: 'order_items',
    foreign_key: 'order_id',
    list_fields: ['id', 'product_name', 'quantity'],
    title: 'Order Items'
  }

  it('renders with basic props', () => {
    const wrapper = mount(Details, {
      props: {
        recordId: '123',
        parentModel: 'orders',
        detailConfig: mockDetailConfig
      },
      global: {
        components: {
          UFCardBox,
          UFSprunjeTable,
          UFSprunjeHeader,
          UFSprunjeColumn,
          UFLabel
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.exists()).toBe(true)
  })

  it('constructs correct data URL', () => {
    const wrapper = mount(Details, {
      props: {
        recordId: '123',
        parentModel: 'orders',
        detailConfig: mockDetailConfig
      },
      global: {
        components: {
          UFCardBox,
          UFSprunjeTable,
          UFSprunjeHeader,
          UFSprunjeColumn,
          UFLabel
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    const sprunjeTable = wrapper.findComponent(UFSprunjeTable)
    expect(sprunjeTable.props('dataUrl')).toBe('/api/crud6/orders/123/order_items')
  })

  it('displays the detail title', () => {
    const wrapper = mount(Details, {
      props: {
        recordId: '123',
        parentModel: 'orders',
        detailConfig: mockDetailConfig
      },
      global: {
        components: {
          UFCardBox,
          UFSprunjeTable,
          UFSprunjeHeader,
          UFSprunjeColumn,
          UFLabel
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    const cardBox = wrapper.findComponent(UFCardBox)
    expect(cardBox.props('title')).toBe('Order Items')
  })

  it('renders correct number of column headers', () => {
    const wrapper = mount(Details, {
      props: {
        recordId: '123',
        parentModel: 'orders',
        detailConfig: mockDetailConfig
      },
      global: {
        components: {
          UFCardBox,
          UFSprunjeTable,
          UFSprunjeHeader,
          UFSprunjeColumn,
          UFLabel
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    const headers = wrapper.findAllComponents(UFSprunjeHeader)
    expect(headers).toHaveLength(mockDetailConfig.list_fields.length)
  })

  it('falls back to capitalized model name when no title provided', () => {
    const configWithoutTitle: DetailConfig = {
      model: 'order_items',
      foreign_key: 'order_id',
      list_fields: ['id', 'product_name']
    }

    const wrapper = mount(Details, {
      props: {
        recordId: '123',
        parentModel: 'orders',
        detailConfig: configWithoutTitle
      },
      global: {
        components: {
          UFCardBox,
          UFSprunjeTable,
          UFSprunjeHeader,
          UFSprunjeColumn,
          UFLabel
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    const cardBox = wrapper.findComponent(UFCardBox)
    expect(cardBox.props('title')).toBe('Order Items')
  })
})
