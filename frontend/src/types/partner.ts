export interface MitraUser {
  id: number
  name: string
  email: string
  role: string
  email_verified_at?: string | null
}

export interface MitraBasecampSummary {
  id: number
  nama_basecamp: string
  latitude: string
  longitude: string
  jam_operasional: string
  jalur?: {
    id: number
    nama_jalur: string
    gunung?: {
      id: number
      nama_gunung: string
    }
  }
}

export interface MitraStaffSummary {
  id: number
  mitra_id: number
  nama_staff: string
  peran: 'guide' | 'porter' | 'admin_loket'
  nomor_hp: string
  status_aktif: boolean
}

export interface Mitra {
  id: number
  user_id: number
  nama_pemilik: string
  telepon: string
  alamat: string
  deskripsi?: string | null
  status: 'aktif' | 'suspend'
  npwp?: string | null
  nik: string
  rekening_bank: string
  nama_rekening: string
  bank: string
  ewallet?: string | null
  created_at?: string
  updated_at?: string
  user?: MitraUser
  basecamps?: MitraBasecampSummary[]
  staff?: MitraStaffSummary[]
}

export interface CreateMitraPayload {
  email: string
  password: string
  nama_pemilik: string
  telepon: string
  alamat: string
  deskripsi?: string
  status: 'aktif' | 'suspend'
  npwp?: string
  nik: string
  rekening_bank: string
  nama_rekening: string
  bank: string
  ewallet?: string
}

export interface UpdateMitraPayload {
  email?: string
  password?: string
  nama_pemilik?: string
  telepon?: string
  alamat?: string
  deskripsi?: string
  status?: 'aktif' | 'suspend'
  npwp?: string
  nik?: string
  rekening_bank?: string
  nama_rekening?: string
  bank?: string
  ewallet?: string
}

export interface MitraFilterParams {
  page?: number
  per_page?: number
  search?: string
  status?: string
}
