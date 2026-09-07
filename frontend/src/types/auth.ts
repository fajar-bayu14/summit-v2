import type { Basecamp } from './basecamp'

export type UserRole = 'admin' | 'mitra' | 'pendaki'

export interface MitraEntity {
  id: number
  user_id: number
  nama_pemilik: string
  telepon: string
  alamat?: string
  status?: string
  nik?: string
  npwp?: string
  deskripsi?: string
  rekening_bank?: string
  nama_rekening?: string
  bank?: string
  ewallet?: string
  basecamps?: Basecamp[]
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  email_verified_at?: string | null
  telepon?: string | null
  avatar?: string | null
  created_at?: string
  updated_at?: string
  pendaki?: any
  mitra?: MitraEntity
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface AuthResponseData {
  user: User
  token: string
}
