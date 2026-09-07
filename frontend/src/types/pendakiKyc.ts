/**
 * TypeScript definitions for Climber (Pendaki) KYC and Profile
 */

export type KycStatus = 'unverified' | 'pending' | 'verified' | 'rejected'

export type IdentityType = 'ktp' | 'paspor' | 'sim' | 'lainnya'

export type GenderType = 'l' | 'p'

export interface EmergencyContact {
  nama: string
  telepon: string
  hubungan: string
}

export interface PendakiProfile {
  id: number
  user_id: number
  nama_lengkap: string
  jenis_identitas: IdentityType
  nomor_identitas: string
  foto_identitas?: string | null
  tanggal_lahir: string
  jenis_kelamin: GenderType
  alamat: string
  telepon: string
  nama_kontak_darurat: string
  telepon_darurat: string
  hubungan_darurat: string
  status_verifikasi: KycStatus
  alasan_penolakan?: string | null
  created_at?: string
  updated_at?: string
}

export interface SubmitKycPayload {
  nama_lengkap: string
  jenis_identitas: IdentityType
  nomor_identitas: string
  foto_identitas: File
  tanggal_lahir: string
  jenis_kelamin: GenderType
  alamat: string
  telepon: string
  nama_kontak_darurat: string
  telepon_darurat: string
  hubungan_darurat: string
}

export interface KycStatusResponse {
  status: string
  message: string
  data: PendakiProfile | null
}

export interface ChangePasswordPayload {
  current_password: string
  new_password: string
  new_password_confirmation: string
}
