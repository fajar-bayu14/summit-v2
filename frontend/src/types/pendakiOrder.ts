import type { BasecampMitraSummary, JalurDetail } from './pendakiMountain'
import type {
  PesananAnggota,
  Pembayaran,
  DetailPesanan,
  PesananStatus,
} from './pendakiCheckout'

export type { PesananStatus }
export type PesananItem = PesananDetail

export interface OrderFilterParams {
  status?: PesananStatus | string
  page?: number
  search?: string
}

export interface PesananDetail {
  id: number
  invoice: string
  user_id: number
  basecamp_id: number
  jalur_id: number
  status: PesananStatus
  subtotal: number
  tanggal_booking: string | null
  diskon: number
  biaya_layanan_user: number
  komisi_admin: number
  pendapatan_mitra: number
  total_bayar: number
  created_at?: string
  updated_at?: string
  user?: {
    id: number
    name: string
    email: string
    telepon?: string | null
  }
  basecamp?: BasecampMitraSummary
  jalur?: JalurDetail
  anggotas?: PesananAnggota[]
  details?: DetailPesanan[]
  pembayaran?: Pembayaran
}

export interface OrderListResponse {
  status: string
  data: PesananDetail[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface OrderDetailResponse {
  status: string
  data: PesananDetail
}
