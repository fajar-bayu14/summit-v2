import type { Basecamp, BasecampMitra, BasecampJalur } from './basecamp'
import type { Mitra } from './partner'
import type { JalurPendakian } from './mountain'

export type ProductCategory =
  | 'ticket'
  | 'rental'
  | 'opentrip'
  | 'guide'
  | 'porter'
  | 'transport'
  | 'parkir'
  | 'merchandise'
  | 'kuliner'

export interface KuotaHarianTiket {
  id: number
  produk_tiket_id: number
  tanggal: string
  kuota_total: number
  kuota_tersisa: number
  created_at?: string
  updated_at?: string
}

export interface ProdukTiket {
  id: number
  produk_id: number
  jalur_id: number
  jam_buka?: string | null
  jam_tutup?: string | null
  jalur?: JalurPendakian | BasecampJalur
  kuotas?: KuotaHarianTiket[]
}

export interface ProdukOpentrip {
  id: number
  produk_id: number
  tanggal_berangkat?: string | null
  tanggal_pulang?: string | null
  meeting_point?: string | null
  minimal_peserta: number
  maksimal_peserta: number
  sisa_kursi: number
}

export interface Product {
  id: number
  basecamp_id: number
  nama_produk: string
  kategori: ProductCategory | string
  deskripsi: string | null
  harga: number
  stok: number | null
  satuan: string | null
  is_active: boolean
  gambar: string | null
  created_at: string
  updated_at: string
  basecamp?: Basecamp
  opentrip?: ProdukOpentrip | null
  tiket?: ProdukTiket | null
}

export interface ProductFilterParams {
  mitra_id?: number | string
  basecamp_id?: number | string
  kategori?: string
  is_active?: boolean | string
  search?: string
  page?: number
  per_page?: number
}

export interface BasecampProductGroup {
  basecamp: Basecamp
  products: Product[]
}

export interface PartnerGroupedCatalog {
  mitra: BasecampMitra | Mitra
  totalProducts: number
  totalTickets: number
  totalRentals: number
  totalTrips: number
  basecampGroups: BasecampProductGroup[]
}
