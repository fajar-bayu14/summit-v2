import apiClient from '@/lib/axios'
import type { ApiResponse, LaravelPaginatedData } from '@/types/api'
import type {
  WithdrawalRequest,
  WalletTransaction,
  RefundRecord,
  RejectWithdrawalPayload,
  ProcessRefundPayload,
  ForceMajeurePayload,
} from '@/types/finance'

export interface WithdrawalQueryParams {
  status?: string
  search?: string
  page?: number
  per_page?: number
}

export interface EscrowLedgerQueryParams {
  mitra_id?: number
  type?: string
  search?: string
  page?: number
  per_page?: number
}

export interface RefundQueryParams {
  status?: string
  is_disputed?: boolean | string
  refund_category?: string
  search?: string
  page?: number
  per_page?: number
}

export const financeService = {
  // 1. Withdrawal Management (Admin)
  async getWithdrawals(params?: WithdrawalQueryParams): Promise<ApiResponse<LaravelPaginatedData<WithdrawalRequest>>> {
    const response = await apiClient.get<ApiResponse<LaravelPaginatedData<WithdrawalRequest>>>('/admin/withdrawals', {
      params,
    })
    return response.data
  },

  async approveWithdrawal(id: number): Promise<ApiResponse<WithdrawalRequest>> {
    const response = await apiClient.post<ApiResponse<WithdrawalRequest>>(`/admin/withdrawals/${id}/approve`)
    return response.data
  },

  async rejectWithdrawal(id: number, payload: RejectWithdrawalPayload): Promise<ApiResponse<WithdrawalRequest>> {
    const response = await apiClient.post<ApiResponse<WithdrawalRequest>>(`/admin/withdrawals/${id}/reject`, payload)
    return response.data
  },

  // 2. Escrow Ledger (Admin)
  async getEscrowLedger(params?: EscrowLedgerQueryParams): Promise<ApiResponse<LaravelPaginatedData<WalletTransaction>>> {
    const response = await apiClient.get<ApiResponse<LaravelPaginatedData<WalletTransaction>>>('/admin/escrow/ledger', {
      params,
    })
    return response.data
  },

  // 3. Refund & Dispute Management (Admin)
  async getRefunds(params?: RefundQueryParams): Promise<ApiResponse<LaravelPaginatedData<RefundRecord>>> {
    const response = await apiClient.get<ApiResponse<LaravelPaginatedData<RefundRecord>>>('/admin/refunds', {
      params,
    })
    return response.data
  },

  async processRefund(id: number, payload: ProcessRefundPayload): Promise<ApiResponse<RefundRecord>> {
    const response = await apiClient.post<ApiResponse<RefundRecord>>(`/admin/refunds/${id}/process`, payload)
    return response.data
  },

  async triggerForceMajeure(payload: ForceMajeurePayload): Promise<ApiResponse<{ total_pesanan_refunded: number; total_refunded: number }>> {
    const response = await apiClient.post<ApiResponse<{ total_pesanan_refunded: number; total_refunded: number }>>('/admin/refunds/force-majeure', payload)
    return response.data
  },
}
