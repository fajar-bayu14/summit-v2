export type KategoriProdukEnum =
  | 'tiket'
  | 'rental'
  | 'jasa'
  | 'opentrip'
  | 'merchandise'
  | 'konsumsi'

export interface DailyQuotaItem {
  id: number
  produk_tiket_id: number
  tanggal: string
  kuota_total: number
  kuota_tersisa: number
}

export interface ProdukTiketDetail {
  id: number
  produk_id: number
  jalur_id: number
  jam_buka?: string | null
  jam_tutup?: string | null
  kuotas?: DailyQuotaItem[]
  jalur?: {
    id: number
    nama_jalur: string
  }
}

export interface ProdukOpentripDetail {
  id: number
  produk_id: number
  tanggal_mulai: string
  tanggal_selesai: string
  meeting_point: string
  kuota_maksimal: number
  kuota_terisi: number
  fasilitas_include?: string | null
  fasilitas_exclude?: string | null
}

export interface ProdukCatalogItem {
  id: number
  basecamp_id: number
  nama_produk: string
  kategori: KategoriProdukEnum | string
  deskripsi?: string | null
  harga: number
  stok?: number | null
  satuan?: string | null
  is_active: boolean
  gambar?: string | null
  created_at?: string
  updated_at?: string
  tiket?: ProdukTiketDetail | null
  opentrip?: ProdukOpentripDetail | null
  basecamp?: {
    id: number
    nama_basecamp: string
    jam_operasional?: string
    mitra?: {
      id: number
      nama_mitra: string
    }
  }
}

export interface ProductFilterParams {
  basecamp_id?: number | string
  kategori?: KategoriProdukEnum | string
  search?: string
  page?: number
  per_page?: number
}
