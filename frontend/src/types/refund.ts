export type RefundStatus =
  | 'pending'
  | 'approved_by_mitra'
  | 'rejected_by_mitra'
  | 'processing'
  | 'success'
  | 'failed'
  | 'disputed'
  | 'cancelled'

export interface RefundItem {
  id: number
  pesanan_id: number
  pesanan_invoice?: string | null
  mitra_id: number
  pembayaran_id?: number | null
  refund_category?: string | null
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
    pendapatan_mitra?: number
    tanggal_booking?: string
    tanggal_pendakian?: string
    status: string
    user?: {
      id: number
      name: string
      email?: string
      telepon?: string | null
    } | null
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
  mitra?: {
    id: number
    nama_basecamp?: string
    nama_pemilik?: string
  } | null
}

export interface MitraRejectRefundPayload {
  alasan_penolakan: string
}

export interface MitraRefundFilterParams {
  status?: string
  search?: string
  page?: number
  per_page?: number
}
