import type { User } from './auth'
import type { Basecamp } from './basecamp'
import type { MitraStaff } from './staff'

export interface MitraProfile {
  id: number
  user_id: number
  nama_pemilik: string
  telepon: string
  alamat: string
  deskripsi: string | null
  status: 'aktif' | 'pending' | 'nonaktif'
  npwp: string | null
  nik: string
  rekening_bank: string
  nama_rekening: string
  bank: string
  ewallet: string | null
  created_at?: string
  updated_at?: string
  user?: User
  basecamps?: Basecamp[]
  staff?: MitraStaff[]
}

export interface UpdateMitraProfilePayload {
  nama_pemilik: string
  telepon: string
  alamat: string
  deskripsi?: string
  npwp?: string
  nik: string
  rekening_bank: string
  nama_rekening: string
  bank: string
  ewallet?: string
}

export interface ChangePasswordPayload {
  current_password: string
  new_password: string
  new_password_confirmation: string
}
