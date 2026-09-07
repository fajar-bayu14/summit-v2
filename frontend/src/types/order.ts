export type PesananStatus =
  | 'pending'
  | 'paid'
  | 'on_going'
  | 'completed'
  | 'cancelled'
  | 'refunded'

export type ItemOperationalStatus =
  | 'pending'
  | 'ready'
  | 'active'
  | 'completed'
  | 'cancelled'

export interface PesananAnggota {
  id: number
  pesanan_id: number
  nama_anggota: string
  identitas_tipe?: string | null
  identitas_nomor?: string | null
  jenis_kelamin?: string | null
  telepon_darurat?: string | null
  alamat?: string | null
  created_at?: string
  updated_at?: string
}

export interface DetailPesananItem {
  id: number
  pesanan_id: number
  produk_id: number
  nama_produk: string
  harga_satuan: number
  kuantitas: number
  subtotal: number
  status_operasional: ItemOperationalStatus
  catatan_opsional?: string | null
  produk?: {
    id: number
    nama_produk: string
    kategori: string
    satuan?: string | null
    harga: number
  } | null
  created_at?: string
  updated_at?: string
}

export interface MitraPesanan {
  id: number
  invoice: string
  user_id: number
  basecamp_id: number
  tanggal_booking: string
  tanggal_pendakian?: string | null
  subtotal: number
  total_bayar: number
  pendapatan_mitra?: number
  status: PesananStatus
  user?: {
    id: number
    name: string
    email?: string
    telepon?: string | null
  } | null
  basecamp?: {
    id: number
    nama_basecamp: string
    nama?: string
    nama_gunung?: string
  } | null
  jalur?: {
    id: number
    nama_jalur: string
  } | null
  anggotas?: PesananAnggota[]
  details?: DetailPesananItem[]
  pembayaran?: {
    id: number
    pesanan_id: number
    metode_pembayaran?: string
    status?: string
    gross_amount?: number
    paid_at?: string
  } | null
  created_at?: string
  updated_at?: string
}

export interface PesananFilterParams {
  status?: string
  basecamp_id?: number
  tanggal_booking?: string
  search?: string
  page?: number
  per_page?: number
}

export interface MitraOrderMeta {
  total_pendapatan_bersih: number
  total_transaksi_paid: number
}
