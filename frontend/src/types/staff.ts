export type StaffRole = 'guide' | 'porter' | 'petugas'

export interface MitraStaff {
  id: number
  mitra_id: number
  basecamp_id?: number | null
  nama: string
  role: StaffRole
  telepon: string
  is_available: boolean
  jadwal_tugas?: string | null
  created_at?: string
  updated_at?: string
  basecamp?: {
    id: number
    nama_basecamp: string
  } | null
}

export interface StoreStaffPayload {
  basecamp_id?: number | null
  nama: string
  role: StaffRole
  telepon: string
  is_available?: boolean
  jadwal_tugas?: string | null
}

export interface UpdateStaffPayload {
  basecamp_id?: number | null
  nama?: string
  role?: StaffRole
  telepon?: string
  is_available?: boolean
  jadwal_tugas?: string | null
}

export interface StaffFilterParams {
  role?: StaffRole | ''
  basecamp_id?: number
  search?: string
  page?: number
  per_page?: number
}
