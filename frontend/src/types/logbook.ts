export type LogbookValidationStatus = 'pending' | 'approved' | 'rejected'

export interface LogbookEntry {
  id: number
  pesanan_id: number
  invoice?: string
  user_id: number
  nama_pendaki?: string
  gunung_nama?: string
  jalur_nama?: string
  tinggi_mdpl?: number
  foto_summit?: string | null
  latitude?: string | null
  longitude?: string | null
  waktu_summit?: string | null
  catatan_pendaki?: string | null
  status_validasi: LogbookValidationStatus
  catatan_petugas?: string | null
  validated_at?: string | null
  validated_by?: string | null
  certificate_url?: string | null
  created_at?: string
  updated_at?: string
  pesanan?: {
    id: number
    invoice: string
    status: string
    tanggal_booking?: string
    tanggal_pendakian?: string
    user?: {
      id: number
      name: string
      email?: string
      telepon?: string | null
    } | null
    jalur?: {
      id: number
      nama_jalur: string
      gunung?: {
        id: number
        nama_gunung: string
        tinggi_mdpl: number
      } | null
    } | null
  } | null
  user?: {
    id: number
    name: string
    email?: string
    telepon?: string | null
  } | null
}

export interface VerifyLogbookPayload {
  status_validasi: 'approved' | 'rejected'
  catatan_petugas?: string | null
}

export interface LogbookFilterParams {
  status_validasi?: LogbookValidationStatus | ''
  search?: string
  page?: number
  per_page?: number
}
