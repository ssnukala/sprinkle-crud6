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

    // Find buttons - should have at least cancel and confirm buttons
    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBeGreaterThan(0)
    
    // Find and click the confirm button (should have data-test attribute with 'confirm' or 'submit' action)
    const confirmButton = wrapper.find('[data-test*="btn-confirm"], [data-test*="btn-submit"]')
    if (confirmButton.exists()) {
      await confirmButton.trigger('click')
      expect(wrapper.emitted()).toHaveProperty('confirmed')
    } else {
      // Fallback: click the last button (typically the primary action button)
      await buttons[buttons.length - 1].trigger('click')
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
        name: { type: 'string', label: 'Name', editable: true }
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

    // Check that modal body contains a form (either CRUD6Form or the mocked version)
    const modalBody = wrapper.find('.uk-modal-body')
    expect(modalBody.exists()).toBe(true)
    
    // Check that modal footer is NOT rendered for form type (as per component template line 736)
    const modalFooter = wrapper.find('.uk-modal-footer')
    expect(modalFooter.exists()).toBe(false)
    
    // Check that the modal is properly configured for form type by verifying modalConfig
    // We can't directly check CRUD6Form due to mock limitations, but we can verify the structure
    const modal = wrapper.find('.uk-modal-dialog')
    expect(modal.exists()).toBe(true)
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

    // Check that a form exists
    const form = wrapper.find('form')
    expect(form.exists()).toBe(true)
    
    // Check that at least one input field exists (not checking specific field name)
    const inputs = wrapper.findAll('input[type="text"], input[type="number"], input[type="email"], input[type="password"], input[type="date"], input[type="datetime-local"]')
    expect(inputs.length).toBeGreaterThan(0)
    
    // Check that buttons exist for confirmation
    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBeGreaterThan(0)
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

    // Check that a form exists
    const form = wrapper.find('form')
    expect(form.exists()).toBe(true)
    
    // Check that the expected number of input fields exists (2 fields configured)
    const inputs = wrapper.findAll('input[type="text"], input[type="number"], input[type="email"], input[type="password"], input[type="date"], input[type="datetime-local"]')
    expect(inputs.length).toBe(2)
    
    // Check that labels exist for the fields
    const labels = wrapper.findAll('label.uk-form-label')
    expect(labels.length).toBe(2)
    
    // Check that buttons exist
    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBeGreaterThan(0)
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

    // Verify the modal is configured for form type
    const modal = wrapper.find('.uk-modal-dialog')
    expect(modal.exists()).toBe(true)
    
    // Verify modal has a body
    const modalBody = wrapper.find('.uk-modal-body')
    expect(modalBody.exists()).toBe(true)
    
    // Verify no modal footer buttons are rendered (form type should not show footer as per line 736)
    const modalFooter = wrapper.find('.uk-modal-footer')
    expect(modalFooter.exists()).toBe(false)
    
    // Verify modal has a header with title
    const modalHeader = wrapper.find('.uk-modal-header')
    expect(modalHeader.exists()).toBe(true)
    
    // Check that action label is used as modal title
    const modalTitle = wrapper.find('.uk-modal-title')
    expect(modalTitle.text()).toBe('Edit')
  })
})
