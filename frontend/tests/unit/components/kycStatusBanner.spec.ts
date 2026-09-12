import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import KycStatusBanner from '@/components/pendaki/KycStatusBanner.vue'
import KycSecurityShield from '@/components/pendaki/KycSecurityShield.vue'

describe('KYC Status Banner & Security Shield Components (Task 1.3)', () => {
  describe('KycStatusBanner Component', () => {
    it('should render unverified banner with CTA button', async () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'unverified',
        },
      })

      expect(wrapper.text()).toContain('Akun Anda Belum Terverifikasi (KYC)')
      expect(wrapper.text()).toContain('Verifikasi Sekarang')

      const button = wrapper.find('button')
      expect(button.exists()).toBe(true)
      await button.trigger('click')
      expect(wrapper.emitted('verify')).toHaveLength(1)
    })

    it('should render pending review banner with info text', () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'pending',
        },
      })

      expect(wrapper.text()).toContain('Dokumen Identitas Sedang Ditinjau Admin')
      expect(wrapper.text()).toContain('Proses < 24 Jam')
    })

    it('should render verified banner with active status badge', () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'verified',
        },
      })

      expect(wrapper.text()).toContain('Identitas Terverifikasi Resmi (KYC Approved)')
      expect(wrapper.text()).toContain('Aktif')
    })

    it('should render rejected banner with rejection reason and retry button', async () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'rejected',
          rejectionReason: 'Foto KTP buram dan NIK tidak terbaca',
        },
      })

      expect(wrapper.text()).toContain('Pengajuan Verifikasi Identitas Ditolak')
      expect(wrapper.text()).toContain('Foto KTP buram dan NIK tidak terbaca')
      expect(wrapper.text()).toContain('Ajukan Ulang')

      const button = wrapper.find('button')
      await button.trigger('click')
      expect(wrapper.emitted('verify')).toHaveLength(1)
    })
    it('should render verified banner when status is Indonesian disetujui', () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'disetujui',
        },
      })

      expect(wrapper.text()).toContain('Identitas Terverifikasi Resmi (KYC Approved)')
      expect(wrapper.text()).toContain('Aktif')
    })

    it('should render rejected banner with rejection reason when status is Indonesian ditolak', async () => {
      const wrapper = mount(KycStatusBanner, {
        props: {
          status: 'ditolak',
          rejectionReason: 'Foto KTP terpotong',
        },
      })

      expect(wrapper.text()).toContain('Pengajuan Verifikasi Identitas Ditolak')
      expect(wrapper.text()).toContain('Foto KTP terpotong')
      expect(wrapper.text()).toContain('Ajukan Ulang')

      const button = wrapper.find('button')
      await button.trigger('click')
      expect(wrapper.emitted('verify')).toHaveLength(1)
    })
  })

  describe('KycSecurityShield Component', () => {
    it('should render verified badge label for disetujui status', () => {
      const wrapper = mount(KycSecurityShield, {
        props: {
          status: 'disetujui',
        },
      })

      expect(wrapper.text()).toBe('Terverifikasi')
      expect(wrapper.classes()).toContain('bg-emerald-100')
    })

    it('should render ditolak badge label for ditolak status', () => {
      const wrapper = mount(KycSecurityShield, {
        props: {
          status: 'ditolak',
        },
      })

      expect(wrapper.text()).toBe('Verifikasi Ditolak')
      expect(wrapper.classes()).toContain('bg-rose-100')
    })
    it('should render verified badge label', () => {
      const wrapper = mount(KycSecurityShield, {
        props: {
          status: 'verified',
        },
      })

      expect(wrapper.text()).toBe('Terverifikasi')
      expect(wrapper.classes()).toContain('bg-emerald-100')
    })

    it('should render pending badge label', () => {
      const wrapper = mount(KycSecurityShield, {
        props: {
          status: 'pending',
        },
      })

      expect(wrapper.text()).toBe('Menunggu Verifikasi')
      expect(wrapper.classes()).toContain('bg-blue-100')
    })

    it('should render unverified badge label', () => {
      const wrapper = mount(KycSecurityShield, {
        props: {
          status: 'unverified',
        },
      })

      expect(wrapper.text()).toBe('Belum Verifikasi')
      expect(wrapper.classes()).toContain('bg-amber-100')
    })
  })
})
