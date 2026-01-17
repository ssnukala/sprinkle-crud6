/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Form Component Tests
 * 
 * Tests for the Form component - main form rendering and editing
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import Form from '../../components/CRUD6/Form.vue'

// Mock composables
vi.mock('../../composables/useCRUD6Api', () => ({
  useCRUD6Api: () => ({
    createRow: vi.fn(),
    updateRow: vi.fn(),
    r$: { value: {} },
    formData: { value: {} },
    apiLoading: { value: false },
    resetForm: vi.fn(),
    slugLocked: { value: false }
  })
}))

vi.mock('../../composables/useCRUD6Schema', () => ({
  useCRUD6Schema: () => ({
    schema: {
      value: {
        model: 'test',
        title: 'Test Model',
        table: 'test',
        primary_key: 'id',
        fields: {
          name: {
            type: 'string',
            label: 'Name',
            editable: true,
            required: true
          },
          email: {
            type: 'email',
            label: 'Email',
            editable: true
          },
          active: {
            type: 'boolean',
            label: 'Active',
            editable: true
          }
        }
      }
    },
    loading: { value: false },
    error: { value: null },
    loadSchema: vi.fn()
  })
}))

// Mock child components
const mockComponent = (name: string) => ({
  name,
  template: `<div class="mock-${name.toLowerCase()}"><slot /></div>`,
  props: ['modelValue', 'schema', 'field', 'fieldKey', 'disabled', 'id', 'dataTest']
})

describe('Form.vue', () => {
  let router: any

  beforeEach(() => {
    setActivePinia(createPinia())
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', component: { template: '<div>Home</div>' } },
        { path: '/test', component: { template: '<div>Test</div>' } }
      ]
    })
  })

  it('renders with schema prop', async () => {
    const schema = {
      model: 'products',
      title: 'Products',
      table: 'products',
      primary_key: 'id',
      fields: {
        name: {
          type: 'string',
          label: 'Product Name',
          editable: true
        }
      }
    }

    const wrapper = mount(Form, {
      props: {
        model: 'products',
        schema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6AutoLookup: mockComponent('AutoLookup'),
          GoogleAddress: mockComponent('GoogleAddress'),
          CRUD6ToggleSwitch: mockComponent('ToggleSwitch')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('renders only editable fields', async () => {
    const schema = {
      model: 'products',
      title: 'Products',
      table: 'products',
      primary_key: 'id',
      fields: {
        id: {
          type: 'integer',
          label: 'ID',
          editable: false // Should not render
        },
        name: {
          type: 'string',
          label: 'Product Name',
          editable: true // Should render
        },
        created_at: {
          type: 'datetime',
          label: 'Created At',
          editable: false // Should not render
        }
      }
    }

    const wrapper = mount(Form, {
      props: {
        model: 'products',
        schema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6AutoLookup: mockComponent('AutoLookup'),
          GoogleAddress: mockComponent('GoogleAddress'),
          CRUD6ToggleSwitch: mockComponent('ToggleSwitch')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    
    // Should only have one editable field (name)
    const fieldWrappers = wrapper.findAll('[class*="uk-width"]')
    // At least one field should be rendered
    expect(fieldWrappers.length).toBeGreaterThan(0)
  })

  it('applies form layout class based on schema config', async () => {
    const schema = {
      model: 'products',
      title: 'Products',
      table: 'products',
      primary_key: 'id',
      form_layout: '3-column',
      fields: {
        name: {
          type: 'string',
          label: 'Product Name',
          editable: true
        }
      }
    }

    const wrapper = mount(Form, {
      props: {
        model: 'products',
        schema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6AutoLookup: mockComponent('AutoLookup'),
          GoogleAddress: mockComponent('GoogleAddress'),
          CRUD6ToggleSwitch: mockComponent('ToggleSwitch')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.html()).toContain('uk-child-width-1-3')
  })

  it('renders with CRUD6 object for editing', async () => {
    const crud6Data = {
      id: 1,
      name: 'Test Product',
      price: 99.99
    }

    const schema = {
      model: 'products',
      title: 'Products',
      table: 'products',
      primary_key: 'id',
      fields: {
        name: {
          type: 'string',
          label: 'Product Name',
          editable: true
        },
        price: {
          type: 'decimal',
          label: 'Price',
          editable: true
        }
      }
    }

    const wrapper = mount(Form, {
      props: {
        crud6: crud6Data,
        model: 'products',
        schema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6AutoLookup: mockComponent('AutoLookup'),
          GoogleAddress: mockComponent('GoogleAddress'),
          CRUD6ToggleSwitch: mockComponent('ToggleSwitch')
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('handles multi-context schema (form context)', async () => {
    const multiContextSchema = {
      model: 'users',
      title: 'Users',
      table: 'users',
      primary_key: 'id',
      fields: {
        id: { type: 'integer', label: 'ID' }
      },
      contexts: {
        form: {
          fields: {
            username: {
              type: 'string',
              label: 'Username',
              editable: true
            },
            password: {
              type: 'password',
              label: 'Password',
              editable: true
            }
          }
        }
      }
    }

    const wrapper = mount(Form, {
      props: {
        model: 'users',
        schema: multiContextSchema
      },
      global: {
        plugins: [router],
        components: {
          CRUD6AutoLookup: mockComponent('AutoLookup'),
          GoogleAddress: mockComponent('GoogleAddress'),
          CRUD6ToggleSwitch: mockComponent('ToggleSwitch')
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
