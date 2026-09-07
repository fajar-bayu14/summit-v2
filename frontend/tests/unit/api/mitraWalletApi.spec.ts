import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiClient } from '@/lib/axios'
import mitraWalletApi from '@/api/mitraWallet'
import type { MitraWallet, StoreWithdrawalPayload } from '@/types/wallet'

vi.mock('@/lib/axios', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('Mitra Wallet API Service (Task 6.1)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  const mockWallet: MitraWallet = {
    id: 1,
    mitra_id: 1,
    saldo_pending: 350000,
    saldo_available: 1500000,
    total_withdrawn: 5000000,
    total_terhitung: 1850000,
    rekening_tujuan: {
      bank: 'BCA',
      rekening_bank: '1234567890',
      nama_rekening: 'Bayu Mitra Basecamp',
    },
  }

  it('should fetch wallet balance summary', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Ringkasan saldo dompet berhasil dimuat.',
        data: mockWallet,
      },
    })

    const res = await mitraWalletApi.getWalletSummary()

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/wallet')
    expect(res.status).toBe('success')
    expect(res.data.saldo_available).toBe(1500000)
    expect(res.data.rekening_tujuan?.bank).toBe('BCA')
  })

  it('should fetch ledger transactions with filter params', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Mutasi transaksi dompet berhasil dimuat.',
        data: {
          data: [
            {
              id: 1,
              wallet_id: 1,
              pesanan_id: 10,
              pesanan_invoice: 'INV-PRAU-001',
              type: 'inflow_holding',
              nominal: 150000,
              saldo_pending_after: 150000,
              saldo_available_after: 0,
              catatan: 'Dana masuk escrow holding',
              created_at: '2026-09-06T10:00:00Z',
            },
          ],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      },
    })

    const res = await mitraWalletApi.getLedgerTransactions({
      type: 'inflow_holding',
      page: 1,
    })

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/wallet/ledger', {
      params: { type: 'inflow_holding', page: 1 },
    })
    expect(res.status).toBe('success')
    expect((res.data as any).data[0].pesanan_invoice).toBe('INV-PRAU-001')
  })

  it('should request payout withdrawal', async () => {
    const payload: StoreWithdrawalPayload = {
      nominal: 500000,
      catatan: 'Penarikan mingguan operasional basecamp',
    }

    vi.mocked(apiClient.post).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Pengajuan penarikan dana berhasil dibuat.',
        data: {
          id: 101,
          mitra_id: 1,
          nominal: 500000,
          biaya_admin: 0,
          bank: 'BCA',
          rekening_bank: '1234567890',
          nama_rekening: 'Bayu Mitra Basecamp',
          status: 'pending',
          catatan: payload.catatan,
          created_at: '2026-09-07T10:00:00Z',
        },
      },
    })

    const res = await mitraWalletApi.requestWithdrawal(payload)

    expect(apiClient.post).toHaveBeenCalledWith('/mitra/withdrawals', payload)
    expect(res.status).toBe('success')
    expect(res.data.nominal).toBe(500000)
    expect(res.data.status).toBe('pending')
  })

  it('should fetch withdrawals history with status filter', async () => {
    vi.mocked(apiClient.get).mockResolvedValueOnce({
      data: {
        status: 'success',
        message: 'Riwayat penarikan dana berhasil dimuat.',
        data: {
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 0,
        },
      },
    })

    const res = await mitraWalletApi.getWithdrawals({ status: 'completed' })

    expect(apiClient.get).toHaveBeenCalledWith('/mitra/withdrawals', {
      params: { status: 'completed' },
    })
    expect(res.status).toBe('success')
  })
})
