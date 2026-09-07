export type BannerPosition = 'home_top' | 'mountain_detail' | 'search_sidebar'

export interface BannerAdItem {
  id: number
  mitra_id?: number | null
  judul: string
  gambar_url: string
  target_url?: string | null
  posisi: BannerPosition
  tipe: 'sponsor' | 'promo' | 'announcement'
  tanggal_mulai: string
  tanggal_selesai: string
  is_active: boolean
  total_impressions?: number
  total_clicks?: number
  created_at?: string
  mitra?: {
    id: number
    nama_basecamp?: string
  } | null
}
