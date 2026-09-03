export type MountainStatus = 'aktif' | 'tidak_aktif'
export type TrailStatus = 'open' | 'close'
export type TrailDifficulty = 'mudah' | 'sedang' | 'sulit' | 'ekstrem'

export interface JalurPendakian {
  id: number
  gunung_id: number
  nama_jalur: string
  deskripsi: string | null
  titik_awal_mdpl: string | null
  titik_akhir_mdpl: string | null
  waktu_tempuh: string | null
  status: TrailStatus
  panjang_jalur: string | null
  tingkat_kesulitan: TrailDifficulty
  created_at?: string
  updated_at?: string
  gunung?: Gunung
  basecamps_count?: number
  basecamps?: any[]
}

export interface Gunung {
  id: number
  nama_gunung: string
  deskripsi: string | null
  tinggi_mdpl: number
  lokasi: string
  foto: string | null
  status: MountainStatus
  created_at?: string
  updated_at?: string
  jalurs?: JalurPendakian[]
  jalur_pendakians?: JalurPendakian[]
  jalur_pendakians_count?: number
}

export interface CreateGunungPayload {
  nama_gunung: string
  deskripsi: string
  tinggi_mdpl: number
  lokasi: string
  status: MountainStatus
  foto?: File | null
}

export interface UpdateGunungPayload {
  nama_gunung: string
  deskripsi: string
  tinggi_mdpl: number
  lokasi: string
  status: MountainStatus
  foto?: File | null
}

export interface CreateJalurPayload {
  gunung_id: number
  nama_jalur: string
  deskripsi: string
  titik_awal_mdpl: string
  titik_akhir_mdpl: string
  waktu_tempuh: string
  status: TrailStatus
  panjang_jalur: string
  tingkat_kesulitan: TrailDifficulty
}

export interface UpdateJalurPayload {
  gunung_id: number
  nama_jalur: string
  deskripsi: string
  titik_awal_mdpl: string
  titik_akhir_mdpl: string
  waktu_tempuh: string
  status: TrailStatus
  panjang_jalur: string
  tingkat_kesulitan: TrailDifficulty
}

export interface TrailFilterParams {
  gunung_id?: number | ''
  status?: TrailStatus | ''
  tingkat_kesulitan?: TrailDifficulty | ''
  search?: string
  page?: number
  per_page?: number
}
