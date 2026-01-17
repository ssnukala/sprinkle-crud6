/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * ToggleSwitch Component Tests
 * 
 * Tests for the ToggleSwitch component - a modern boolean input UI
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ToggleSwitch from '../../components/CRUD6/ToggleSwitch.vue'

describe('ToggleSwitch.vue', () => {
  it('renders with default props (unchecked)', () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false
      }
    })

    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('.toggle-switch-checkbox').element.checked).toBe(false)
    expect(wrapper.find('.toggle-switch-label').text()).toBe('Disabled')
  })

  it('renders in checked state', () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: true
      }
    })

    expect(wrapper.find('.toggle-switch-checkbox').element.checked).toBe(true)
    expect(wrapper.find('.toggle-switch-label').text()).toBe('Enabled')
  })

  it('emits update:modelValue when toggled', async () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false
      }
    })

    await wrapper.find('.toggle-switch-checkbox').setValue(true)

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
  })

  it('applies disabled state correctly', () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false,
        disabled: true
      }
    })

    expect(wrapper.find('.toggle-switch-checkbox').element.disabled).toBe(true)
    expect(wrapper.find('.toggle-switch').classes()).toContain('toggle-switch-disabled')
  })

  it('accepts custom id prop', () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false,
        id: 'custom-toggle-id'
      }
    })

    expect(wrapper.find('.toggle-switch-checkbox').attributes('id')).toBe('custom-toggle-id')
  })

  it('accepts custom data-test prop', () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false,
        dataTest: 'my-toggle'
      }
    })

    expect(wrapper.find('.toggle-switch-checkbox').attributes('data-test')).toBe('my-toggle')
  })

  it('toggles between enabled and disabled on multiple clicks', async () => {
    const wrapper = mount(ToggleSwitch, {
      props: {
        modelValue: false
      }
    })

    const checkbox = wrapper.find('.toggle-switch-checkbox')
    
    // First click - enable
    await checkbox.setValue(true)
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
    
    // Second click - disable
    await checkbox.setValue(false)
    expect(wrapper.emitted('update:modelValue')?.[1]).toEqual([false])
  })
})
