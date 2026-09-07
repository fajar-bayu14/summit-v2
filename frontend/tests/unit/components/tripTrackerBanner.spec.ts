import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TripTrackerBanner from '@/components/pendaki/TripTrackerBanner.vue'

describe('TripTrackerBanner Component (Task 6.4)', () => {
  it('should render mountain, trail, and steps', () => {
    const wrapper = mount(TripTrackerBanner, {
      props: {
        status: 'paid',
        mountainName: 'Gunung Slamet',
        trailName: 'Jalur Bambangan',
      },
    })

    expect(wrapper.text()).toContain('Gunung Slamet')
    expect(wrapper.text()).toContain('Jalur Bambangan')
    expect(wrapper.text()).toContain('Booking & Pembayaran')
    expect(wrapper.text()).toContain('Check-In Basecamp')
    expect(wrapper.text()).toContain('Di Jalur Pendakian')
    expect(wrapper.text()).toContain('Check-Out & Logbook')
  })

  it('should highlight ongoing step with pulse badge when status is on_going', () => {
    const wrapper = mount(TripTrackerBanner, {
      props: {
        status: 'on_going',
      },
    })

    expect(wrapper.text()).toContain('Sedang Mendaki')
  })
})
