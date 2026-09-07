import type { BasecampMitraSummary, JalurDetail } from './pendakiMountain'
import type { ProdukCatalogItem } from './pendakiProduct'

export interface PesananAnggotaPayload {
  nama_anggota: string
  nik_identitas: string
  telepon?: string | null
  telepon_darurat?: string | null
  hubungan_darurat?: string | null
}

export interface CheckoutPayload {
  anggotas: PesananAnggotaPayload[]
}

export interface PesananAnggota {
  id: number
  pesanan_id: number
  nama_anggota: string
  nik_identitas: string
  telepon: string | null
  telepon_darurat: string | null
  hubungan_darurat: string | null
  created_at?: string
  updated_at?: string
}

export type PaymentStatus = 'pending' | 'paid' | 'expired' | 'failed' | 'refunded'

export interface Pembayaran {
  id: number
  pesanan_id: number
  metode: string | null
  provider: string | null
  reference_id: string | null
  checkout_url: string | null
  amount: number
  paid_amount: number | null
  status: PaymentStatus
  biaya_gateway: number
  paid_at: string | null
  expired_at: string | null
  created_at?: string
  updated_at?: string
}

export interface DetailPesanan {
  id: number
  pesanan_id: number
  produk_id: number
  qty: number
  harga: number
  subtotal: number
  status_operasional: string
  kode_tiket: string | null
  created_at?: string
  updated_at?: string
  produk?: ProdukCatalogItem
}

export type PesananStatus =
  | 'pending'
  | 'paid'
  | 'confirmed'
  | 'active'
  | 'on_going'
  | 'completed'
  | 'cancelled'
  | 'refunded'

export interface Pesanan {
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

export interface CheckoutResponse {
  status: string
  message: string
  checkout_url?: string | null
  data: Pesanan
}

export interface OrderDetailResponse {
  status: string
  data: Pesanan
}

export interface OrderListResponse {
  status: string
  data: Pesanan[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
