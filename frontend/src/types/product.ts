import type { Basecamp } from './basecamp'
import type { Mitra } from './partner'

export type ProdukKategori =
  | 'ticket'
  | 'rental'
  | 'opentrip'
  | 'guide'
  | 'porter'
  | 'transport'
  | 'parkir'
  | 'merchandise'
  | 'kuliner'

export interface ProdukTiketDetail {
  id: number
  produk_id: number
  jalur_id?: number | null
  jam_buka?: string | null
  jam_tutup?: string | null
  kuotas?: any[]
}

export interface ProdukOpentripDetail {
  id: number
  produk_id: number
  tanggal_berangkat?: string | null
  tanggal_pulang?: string | null
  meeting_point?: string | null
  minimal_peserta?: number | null
  maksimal_peserta?: number | null
  sisa_kursi?: number | null
}

export interface Produk {
  id: number
  basecamp_id: number
  nama_produk: string
  kategori: ProdukKategori
  deskripsi?: string | null
  harga: number
  stok?: number | null
  satuan?: string | null
  is_active: boolean
  gambar?: string | null
  basecamp?: Basecamp | {
    id: number
    nama_basecamp?: string
    nama?: string
    nama_gunung?: string
    jam_operasional?: string
    mitra?: Mitra | any
    jalur?: any
    [key: string]: any
  } | null
  tiket?: ProdukTiketDetail | null
  opentrip?: ProdukOpentripDetail | null
  created_at?: string
  updated_at?: string
}

export type Product = Produk

export interface ProdukFilterParams {
  page?: number
  per_page?: number
  kategori?: string
  is_active?: boolean | number | string
  search?: string
  basecamp_id?: number
  mitra_id?: number | string
  status?: string
}

export type ProductFilterParams = ProdukFilterParams

export interface BasecampProductGroup {
  basecamp: Basecamp | any
  products: Produk[]
}

export interface PartnerGroupedCatalog {
  mitra: Mitra | any
  totalProducts: number
  totalTickets: number
  totalRentals: number
  totalTrips: number
  basecampGroups: BasecampProductGroup[]
}

export interface StoreProductPayload {
  basecamp_id: number
  nama_produk: string
  kategori: ProdukKategori
  deskripsi?: string | null
  harga: number
  stok?: number | null
  satuan?: string | null
  is_active?: boolean
  // Tiket fields
  jalur_id?: number | null
  jam_buka?: string | null
  jam_tutup?: string | null
  // Open Trip fields
  tanggal_berangkat?: string | null
  tanggal_pulang?: string | null
  meeting_point?: string | null
  minimal_peserta?: number | null
  maksimal_peserta?: number | null
}

export interface UpdateProductPayload extends Partial<StoreProductPayload> {}

export interface UpdateStockPayload {
  stok: number
}

export interface ToggleProductStatusPayload {
  is_active: boolean
}
