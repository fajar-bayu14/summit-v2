export type WalletTransactionType =
  | 'inflow_holding'
  | 'release_to_available'
  | 'withdrawal_lock'
  | 'withdrawal_settled'
  | 'withdrawal_refunded'
  | 'refund_deduction'

export type WithdrawalStatus =
  | 'pending'
  | 'approved'
  | 'processing'
  | 'completed'
  | 'rejected'
  | 'failed'

export interface RekeningTujuan {
  bank?: string | null
  rekening_bank?: string | null
  nama_rekening?: string | null
}

export interface MitraWallet {
  id: number
  mitra_id: number
  saldo_pending: number
  saldo_available: number
  total_withdrawn: number
  total_terhitung: number
  rekening_tujuan?: RekeningTujuan | null
  updated_at?: string
}

export interface WalletTransaction {
  id: number
  wallet_id: number
  pesanan_id?: number | null
  pesanan_invoice?: string | null
  type: WalletTransactionType
  nominal: number
  saldo_pending_after: number
  saldo_available_after: number
  catatan?: string | null
  created_at?: string
}

export interface WithdrawalRequest {
  id: number
  mitra_id: number
  mitra_nama?: string | null
  disbursement_id?: string | null
  nominal: number
  biaya_admin: number
  bank: string
  rekening_bank: string
  nama_rekening: string
  status: WithdrawalStatus
  catatan?: string | null
  alasan_penolakan?: string | null
  failure_reason?: string | null
  approved_at?: string | null
  completed_at?: string | null
  created_at?: string
}

export interface StoreWithdrawalPayload {
  nominal: number
  catatan?: string | null
}

export interface LedgerFilterParams {
  type?: WalletTransactionType | ''
  page?: number
  per_page?: number
}

export interface WithdrawalFilterParams {
  status?: WithdrawalStatus | ''
  page?: number
  per_page?: number
}
