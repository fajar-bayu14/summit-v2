import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MultiBasecampGuardDialog from '@/components/pendaki/MultiBasecampGuardDialog.vue'

describe('MultiBasecampGuardDialog Component (Task 4.4)', () => {
  it('should expose confirm and cancel handler logic', async () => {
    const wrapper = mount(MultiBasecampGuardDialog, {
      props: {
        isOpen: true,
        currentBasecampName: 'Basecamp Selo',
        newBasecampName: 'Basecamp Bambangan',
      },
    })

    const vm = wrapper.vm as any
    vm.handleConfirm()
    expect(wrapper.emitted('confirm-switch')).toBeTruthy()

    vm.handleCancel()
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })
})
