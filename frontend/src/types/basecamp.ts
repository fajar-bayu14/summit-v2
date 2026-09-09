export interface BasecampJalur {
  id: number
  gunung_id: number
  nama_jalur: string
  titik_awal_mdpl?: string
  titik_akhir_mdpl?: string
  waktu_tempuh?: string
  status?: string
  gunung?: {
    id: number
    nama_gunung: string
    tinggi_mdpl?: number
    lokasi?: string
  }
}

export interface BasecampMitra {
  id: number
  user_id?: number
  nama_pemilik: string
  telepon: string
  bank?: string
  rekening_bank?: string
  user?: {
    id: number
    name: string
    email: string
  }
}

export interface Basecamp {
  id: number
  mitra_id: number
  jalur_id: number
  nama_basecamp: string
  latitude: string
  longitude: string
  jam_operasional: string
  created_at?: string
  updated_at?: string
  mitra?: BasecampMitra
  jalur?: BasecampJalur
  produks?: any[]
}

export interface CreateBasecampPayload {
  mitra_id: number
  jalur_id: number
  nama_basecamp: string
  latitude: string
  longitude: string
  jam_operasional: string
}

export interface UpdateBasecampPayload {
  mitra_id?: number
  jalur_id?: number
  nama_basecamp?: string
  latitude?: string
  longitude?: string
  jam_operasional?: string
}

export interface BasecampFilterParams {
  page?: number
  per_page?: number
  search?: string
  mitra_id?: number
  jalur_id?: number
}
