import type { User } from './auth'

export type KycStatus = 'pending' | 'disetujui' | 'ditolak'

export interface KycProfile {
  id: number
  user_id: number
  nama_lengkap: string
  jenis_identitas: 'ktp' | 'paspor' | string
  nomor_identitas: string
  foto_identitas: string
  tanggal_lahir: string | null
  jenis_kelamin: 'l' | 'p' | string | null
  alamat: string | null
  telepon: string | null
  nama_kontak_darurat: string | null
  telepon_darurat: string | null
  hubungan_darurat: string | null
  status_verifikasi: KycStatus
  alasan_penolakan: string | null
  verified_at: string | null
  verified_by: number | null
  created_at: string
  updated_at: string
  user?: User
}

export interface VerifyKycPayload {
  status_verifikasi: 'disetujui' | 'ditolak'
  alasan_penolakan?: string | null
}

export interface KycFilterParams {
  status?: KycStatus | ''
  search?: string
  page?: number
  per_page?: number
}
