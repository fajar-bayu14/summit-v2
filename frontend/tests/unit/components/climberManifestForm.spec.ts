import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ClimberManifestForm from '@/components/pendaki/ClimberManifestForm.vue'
import type { PesananAnggotaPayload } from '@/types/pendakiCheckout'

describe('ClimberManifestForm Component (Task 5.2)', () => {
  it('should initialize and prefill leader from userProfile', async () => {
    const members: PesananAnggotaPayload[] = []
    const profile = {
      name: 'Fajar Bayu',
      nik: '3301234567890001',
      telepon: '081234567890',
      telepon_darurat: '081298765432',
      hubungan_darurat: 'Orang Tua',
    }

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: members,
        ticketQty: 1,
        userProfile: profile,
      },
    })

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const firstEmit = emitted![0][0] as PesananAnggotaPayload[]
    expect(firstEmit).toHaveLength(1)
    expect(firstEmit[0].nama_anggota).toBe('Fajar Bayu')
    expect(firstEmit[0].nik_identitas).toBe('3301234567890001')
  })

  it('should sync member count to ticketQty', async () => {
    const members: PesananAnggotaPayload[] = [
      {
        nama_anggota: 'Leader',
        nik_identitas: '3301234567890001',
        telepon: '081234567890',
        telepon_darurat: '081298765432',
        hubungan_darurat: 'Orang Tua',
      },
    ]

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: members,
        ticketQty: 2,
      },
    })

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const latest = emitted![emitted!.length - 1][0] as PesananAnggotaPayload[]
    expect(latest).toHaveLength(2)
  })

  it('should validate 16-digit NIK and emit validity-change', async () => {
    const invalidMembers: PesananAnggotaPayload[] = [
      {
        nama_anggota: 'Leader',
        nik_identitas: '123', // invalid NIK
      },
    ]

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: invalidMembers,
        ticketQty: 1,
      },
    })

    expect(wrapper.text()).toContain('NIK harus 16 digit angka')
    const validityEmits = wrapper.emitted('validity-change')
    expect(validityEmits).toBeTruthy()
    expect(validityEmits![validityEmits!.length - 1][0]).toBe(false)
  })

  it('should allow copying emergency contact from leader to all members', async () => {
    const members: PesananAnggotaPayload[] = [
      {
        nama_anggota: 'Leader',
        nik_identitas: '3301234567890001',
        telepon_darurat: '081298765432',
        hubungan_darurat: 'Ibu Kandung',
      },
      {
        nama_anggota: 'Member 2',
        nik_identitas: '3301234567890002',
        telepon_darurat: '',
        hubungan_darurat: '',
      },
    ]

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: members,
        ticketQty: 2,
      },
    })

    const copyAllBtn = wrapper.find('button.text-forest-700')
    expect(copyAllBtn.exists()).toBe(true)
    await copyAllBtn.trigger('click')

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const latest = emitted![emitted!.length - 1][0] as PesananAnggotaPayload[]
    expect(latest[1].telepon_darurat).toBe('081298765432')
    expect(latest[1].hubungan_darurat).toBe('Ibu Kandung')
  })

  it('should display dynamic digit count and informative error when NIK is 15 digits', async () => {
    const members: PesananAnggotaPayload[] = [
      {
        nama_anggota: 'Fajar Bayu',
        nik_identitas: '330606300305002', // 15 digits as in screenshot
      },
    ]

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: members,
        ticketQty: 1,
      },
    })

    // Shows 15/16 digit counter
    expect(wrapper.text()).toContain('15/16 digit')

    // Shows detailed error informing 1 digit is missing
    expect(wrapper.text()).toContain('NIK harus 16 digit angka (saat ini 15 digit, kurang 1 digit)')
  })

  it('should sanitize pasted input and not truncate 16th digit if spaces/delimiters are present', async () => {
    const members: PesananAnggotaPayload[] = [
      {
        nama_anggota: 'Fajar Bayu',
        nik_identitas: '',
      },
    ]

    const wrapper = mount(ClimberManifestForm, {
      props: {
        modelValue: members,
        ticketQty: 1,
      },
    })

    const nikInput = wrapper.find('input[inputmode="numeric"]')
    expect(nikInput.exists()).toBe(true)

    // Simulate pasting formatted 16-digit NIK: "3306 0630 0305 0002"
    await nikInput.trigger('paste', {
      clipboardData: {
        getData: (type: string) => (type === 'text' ? '3306 0630 0305 0002' : ''),
      },
    })

    const emitted = wrapper.emitted('update:modelValue')
    expect(emitted).toBeTruthy()
    const latest = emitted![emitted!.length - 1][0] as PesananAnggotaPayload[]
    expect(latest[0].nik_identitas).toBe('3306063003050002')
    expect(latest[0].nik_identitas).toHaveLength(16)
  })
})
