// Status Enums
export type WithdrawalStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'rejected'
export type RefundCategory = 'pre_trip' | 'incident' | 'force_majeure' | 'dispute'
export type RefundStatus = 'pending' | 'approved_by_mitra' | 'rejected_by_mitra' | 'disputed' | 'success' | 'failed' | 'rejected'
export type WalletTransactionType = 'order_holding' | 'release_order' | 'withdrawal_lock' | 'withdrawal_success' | 'withdrawal_refund' | 'refund_deduction'

// Entitas Penarikan Dana (Withdrawal)
export interface WithdrawalRequest {
  id: number
  mitra_id: number
  wallet_id: number
  nominal: number
  bank: string
  rekening_bank: string
  nama_rekening: string
  status: WithdrawalStatus
  alasan_penolakan?: string | null
  disbursement_id?: string | null
  approved_by?: number | null
  created_at: string
  updated_at: string
  mitra?: {
    id: number
    nama_pemilik: string
    telepon: string
    rekening_bank: string
    nama_rekening: string
    bank: string
    user?: {
      name: string
      email: string
    }
  }
}

// Entitas Mutasi Ledger Escrow
export interface WalletTransaction {
  id: number
  wallet_id: number
  pesanan_id?: number | null
  withdrawal_id?: number | null
  type: WalletTransactionType
  nominal: number
  saldo_pending_after: number
  saldo_available_after: number
  catatan?: string | null
  created_at: string
  pesanan?: {
    invoice: string
    total_bayar: number
    pendapatan_mitra: number
    tanggal_booking: string
  }
  wallet?: {
    mitra_id: number
    saldo_pending: number
    saldo_available: number
    total_withdrawn: number
    mitra?: {
      nama_pemilik: string
      user?: {
        name: string
      }
    }
  }
}

// Entitas Klaim Refund & Sengketa
export interface RefundRecord {
  id: number
  pesanan_id: number
  pembayaran_id: number
  mitra_id?: number | null
  nominal: number
  nominal_disetujui?: number | null
  alasan: string
  status: RefundStatus
  refund_category: RefundCategory
  bank_tujuan: string
  rekening_tujuan: string
  nama_tujuan: string
  bukti_transfer?: string | null
  mitra_alasan_penolakan?: string | null
  mitra_reviewed_at?: string | null
  is_disputed: boolean
  disputed_at?: string | null
  dispute_reason?: string | null
  admin_catatan?: string | null
  tipe?: 'auto' | 'manual' | null
  refunded_at?: string | null
  created_at: string
  pesanan?: {
    id: number
    invoice: string
    status: string
    total_bayar: number
    pendapatan_mitra: number
    tanggal_booking: string
    user?: {
      id: number
      name: string
      email: string
      telepon?: string
    }
    basecamp?: {
      nama_basecamp: string
      jalur?: {
        nama_jalur: string
        gunung?: {
          nama_gunung: string
        }
      }
    }
  }
  mitra?: {
    id: number
    nama_pemilik: string
    bank: string
    rekening_bank: string
    nama_rekening: string
  }
}

// Payloads
export interface RejectWithdrawalPayload {
  alasan_penolakan: string
}

export interface ProcessRefundPayload {
  status: 'success' | 'rejected' | 'failed'
  tipe?: 'auto' | 'manual'
  nominal?: number
  bukti_transfer?: string
  catatan?: string
}

export interface ForceMajeurePayload {
  jalur_id: number
  tanggal_mulai?: string
  start_date?: string
  tanggal_selesai?: string
  end_date?: string
  alasan: string
}
