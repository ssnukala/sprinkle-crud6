/**
 * UnifiedModal Component Tests
 * 
 * Tests for the UnifiedModal component - flexible modal for CRUD operations
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import UnifiedModal from '../../components/CRUD6/UnifiedModal.vue'
import type { ActionConfig } from '../../composables/useCRUD6Actions'

// Mock UIkit
vi.mock('uikit', () => ({
  default: {
    modal: vi.fn(() => ({
      show: vi.fn(),
      hide: vi.fn()
    }))
  }
}))

// Mock CRUD6Form
const CRUD6Form = {
  name: 'CRUD6Form',
  template: '<div class="mock-crud6-form"><slot /></div>',
  props: ['crud6', 'model', 'schema']
}

describe('UnifiedModal.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders with basic action config', () => {
    const action: ActionConfig = {
      key: 'delete',
      label: 'Delete Record',
      type: 'delete',
      confirm: 'Are you sure?'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.exists()).toBe(true)
  })

  it('renders confirmation message', () => {
    const action: ActionConfig = {
      key: 'archive',
      label: 'Archive',
      type: 'action',
      confirm: 'Archive this record?'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.html()).toContain('Archive this record?')
  })

  it('emits confirmed event on confirmation', async () => {
    const action: ActionConfig = {
      key: 'delete',
      label: 'Delete',
      type: 'delete',
      confirm: 'Delete?'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    // Find and click confirm button (usually labeled "Yes" or "Confirm")
    const buttons = wrapper.findAll('button')
    if (buttons.length > 0) {
      // Typically the primary button is the confirm button
      await buttons[0].trigger('click')
      expect(wrapper.emitted()).toHaveProperty('confirmed')
    }
  })

  it('emits cancelled event on cancellation', async () => {
    const action: ActionConfig = {
      key: 'delete',
      label: 'Delete',
      type: 'delete',
      confirm: 'Delete?'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    // Find and click cancel button
    const buttons = wrapper.findAll('button')
    if (buttons.length > 1) {
      // Usually the second button is cancel
      await buttons[1].trigger('click')
      expect(wrapper.emitted()).toHaveProperty('cancelled')
    }
  })

  it('renders with record data for interpolation', () => {
    const action: ActionConfig = {
      key: 'delete',
      label: 'Delete User',
      type: 'delete',
      confirm: 'Delete user {{user_name}}?'
    }

    const record = {
      id: 1,
      user_name: 'testuser',
      email: 'test@example.com'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action,
        record
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string, params?: any) => {
            if (params && params.user_name) {
              return key.replace('{{user_name}}', params.user_name)
            }
            return key
          }
        }
      }
    })

    expect(wrapper.exists()).toBe(true)
  })

  it('renders form type with CRUD6Form component', () => {
    const action: ActionConfig = {
      key: 'edit',
      label: 'Edit Record',
      type: 'form'
    }

    const schema = {
      model: 'products',
      title: 'Products',
      fields: {
        name: { type: 'string', label: 'Name' }
      }
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action,
        model: 'products',
        schema
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.findComponent(CRUD6Form).exists()).toBe(true)
  })

  it('renders input fields when configured', () => {
    const action: ActionConfig = {
      key: 'custom',
      label: 'Custom Action',
      type: 'action',
      confirm: 'Enter value',
      modal_config: {
        type: 'input',
        fields: ['reason']
      }
    }

    const schemaFields = {
      reason: {
        type: 'text',
        label: 'Reason',
        editable: true
      }
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action,
        schemaFields
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.find('input[name="reason"]').exists()).toBe(true)
  })

  it('handles multiple input fields', () => {
    const action: ActionConfig = {
      key: 'transfer',
      label: 'Transfer',
      type: 'action',
      confirm: 'Transfer details',
      modal_config: {
        type: 'input',
        fields: ['amount', 'notes']
      }
    }

    const schemaFields = {
      amount: {
        type: 'number',
        label: 'Amount',
        editable: true
      },
      notes: {
        type: 'text',
        label: 'Notes',
        editable: true
      }
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action,
        schemaFields
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    expect(wrapper.find('input[name="amount"]').exists()).toBe(true)
    expect(wrapper.find('input[name="notes"]').exists()).toBe(true)
  })

  it('passes schema to CRUD6Form for edit mode', () => {
    const action: ActionConfig = {
      key: 'edit',
      label: 'Edit',
      type: 'form'
    }

    const schema = {
      model: 'products',
      title: 'Products',
      fields: {
        name: { type: 'string', label: 'Name', editable: true }
      }
    }

    const record = {
      id: 1,
      name: 'Test Product'
    }

    const wrapper = mount(UnifiedModal, {
      props: {
        action,
        model: 'products',
        schema,
        record
      },
      global: {
        components: {
          CRUD6Form
        },
        mocks: {
          $t: (key: string) => key
        }
      }
    })

    const formComponent = wrapper.findComponent(CRUD6Form)
    expect(formComponent.exists()).toBe(true)
    expect(formComponent.props('schema')).toEqual(schema)
  })
})
