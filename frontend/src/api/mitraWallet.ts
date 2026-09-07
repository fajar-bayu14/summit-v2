import { apiClient } from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  MitraWallet,
  WalletTransaction,
  WithdrawalRequest,
  StoreWithdrawalPayload,
  LedgerFilterParams,
  WithdrawalFilterParams,
} from '@/types/wallet'

export interface MitraLedgerResponse extends ApiResponse<LaravelPaginatedData<WalletTransaction> | WalletTransaction[]> {}
export interface MitraWithdrawalsResponse extends ApiResponse<LaravelPaginatedData<WithdrawalRequest> | WithdrawalRequest[]> {}

export const mitraWalletApi = {
  /**
   * Mengambil ringkasan saldo dompet escrow & available mitra
   */
  async getWalletSummary(): Promise<ApiResponse<MitraWallet>> {
    const response = await apiClient.get<ApiResponse<MitraWallet>>('/mitra/wallet')
    return response.data
  },

  /**
   * Mengambil riwayat mutasi transaksi buku besar (ledger) dompet
   */
  async getLedgerTransactions(params?: LedgerFilterParams): Promise<MitraLedgerResponse> {
    const response = await apiClient.get<MitraLedgerResponse>('/mitra/wallet/ledger', {
      params,
    })
    return response.data
  },

  /**
   * Mengajukan penarikan dana (payout/withdrawal) ke rekening bank
   */
  async requestWithdrawal(payload: StoreWithdrawalPayload): Promise<ApiResponse<WithdrawalRequest>> {
    const response = await apiClient.post<ApiResponse<WithdrawalRequest>>(
      '/mitra/withdrawals',
      payload
    )
    return response.data
  },

  /**
   * Mengambil riwayat pengajuan penarikan dana mitra
   */
  async getWithdrawals(params?: WithdrawalFilterParams): Promise<MitraWithdrawalsResponse> {
    const response = await apiClient.get<MitraWithdrawalsResponse>('/mitra/withdrawals', {
      params,
    })
    return response.data
  },
}

export default mitraWalletApi
