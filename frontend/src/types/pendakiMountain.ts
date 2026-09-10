export type TrailDifficulty = 'mudah' | 'sedang' | 'sulit' | 'ekstrem'
export type TrailStatus = 'open' | 'close'
export type MountainStatus = 'aktif' | 'nonaktif' | 'tutup_sementara'

export interface BasecampMitraSummary {
  id: number
  mitra_id: number
  jalur_id: number
  nama_basecamp: string
  latitude?: string | null
  longitude?: string | null
  jam_operasional?: string | null
  mitra?: {
    id: number
    nama_mitra?: string
    nama_pemilik?: string
    badan_usaha?: string
    is_verified?: boolean
    status?: string
    telepon?: string
  }
}

export interface JalurDetail {
  id: number
  gunung_id: number
  nama_jalur: string
  deskripsi?: string | null
  titik_awal_mdpl?: string | null
  titik_akhir_mdpl?: string | null
  waktu_tempuh?: string | null
  panjang_jalur?: string | null
  tingkat_kesulitan?: TrailDifficulty
  status: TrailStatus
  basecamps?: BasecampMitraSummary[]
  basecamps_count?: number
}

export interface GunungItem {
  id: number
  nama_gunung: string
  deskripsi?: string | null
  tinggi_mdpl: number
  lokasi: string
  foto?: string | null
  status: MountainStatus | string
  jalurs?: JalurDetail[]
  created_at?: string
  updated_at?: string
}

export interface MountainFilterParams {
  page?: number
  per_page?: number
  search?: string
  provinsi?: string
  min_mdpl?: number
  max_mdpl?: number
  kesulitan?: TrailDifficulty
  status?: string
}
