export type RefundStatus =
  | 'pending'
  | 'approved_by_mitra'
  | 'rejected_by_mitra'
  | 'processing'
  | 'success'
  | 'failed'
  | 'disputed'
  | 'cancelled'

export type RefundCategory = 'pre_trip' | 'incident' | 'force_majeure' | 'dispute'

export interface StoreRefundPayload {
  alasan: string
  bank_tujuan: string
  rekening_tujuan: string
  nama_tujuan: string
  nominal?: number
  refund_category?: RefundCategory
}

export interface DisputeRefundPayload {
  alasan_dispute: string
}

export interface RefundItem {
  id: number
  pesanan_id: number
  pesanan_invoice?: string | null
  mitra_id?: number | null
  pembayaran_id?: number | null
  refund_category?: RefundCategory | string | null
  nominal: number
  nominal_disetujui?: number | null
  tipe?: string
  status: RefundStatus
  alasan: string
  bank_tujuan?: string | null
  rekening_tujuan?: string | null
  nama_tujuan?: string | null
  bukti_transfer?: string | null
  mitra_alasan_penolakan?: string | null
  mitra_reviewed_at?: string | null
  is_disputed?: boolean
  disputed_at?: string | null
  dispute_reason?: string | null
  admin_catatan?: string | null
  refunded_at?: string | null
  created_at?: string
  updated_at?: string
  pesanan?: {
    id: number
    invoice: string
    total_bayar: number
    tanggal_booking?: string
    status: string
    basecamp?: {
      id: number
      nama_basecamp: string
    } | null
    jalur?: {
      id: number
      nama_jalur: string
      gunung?: {
        id: number
        nama_gunung: string
      } | null
    } | null
  } | null
}
