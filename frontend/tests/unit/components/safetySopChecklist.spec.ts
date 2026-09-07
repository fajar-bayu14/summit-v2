import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SafetySopChecklist from '@/components/pendaki/SafetySopChecklist.vue'

describe('SafetySopChecklist Component (Task 5.3)', () => {
  it('should render mountain and trail name in header and text', () => {
    const wrapper = mount(SafetySopChecklist, {
      props: {
        modelValue: false,
        mountainName: 'Gunung Slamet',
        trailName: 'Jalur Bambangan',
      },
    })

    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('Jalur Bambangan')
    expect(wrapper.text()).toContain('Zero Waste Policy')
  })

  it('should toggle checkbox and emit update:modelValue', async () => {
    const wrapper = mount(SafetySopChecklist, {
      props: {
        modelValue: false,
      },
    })

    const checkbox = wrapper.find('input[type="checkbox"]')
    expect(checkbox.exists()).toBe(true)
    await checkbox.setValue(true)

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    expect(emitted![0][0]).toBe(true)
  })

  it('should toggle accordion details when header is clicked', async () => {
    const wrapper = mount(SafetySopChecklist, {
      props: {
        modelValue: true,
      },
    })

    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBeGreaterThanOrEqual(4)
    // Click accordion #2 (index 1)
    await buttons[1].trigger('click')
    expect(wrapper.text()).toContain('Standar Perlengkapan Minimum')
  })
})
