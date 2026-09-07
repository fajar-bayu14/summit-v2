export type LogbookValidationStatus = 'pending' | 'approved' | 'rejected'

export interface LogbookEntry {
  id: number
  pesanan_id: number
  invoice: string
  user_id: number
  nama_pendaki: string
  gunung_nama: string
  jalur_nama: string
  tinggi_mdpl: number
  foto_summit: string | null
  latitude: string | null
  longitude: string | null
  waktu_summit: string | null
  catatan_pendaki: string | null
  status_validasi: LogbookValidationStatus
  catatan_petugas: string | null
  validated_at: string | null
  validated_by?: string | null
  certificate_url: string | null
  created_at: string
}

export interface LogbookSubmissionPayload {
  foto_summit: File
  catatan_pendaki?: string
  latitude?: string
  longitude?: string
  waktu_summit?: string
}

export interface BadgeItem {
  id: number
  badge_title: string
  gunung_nama: string
  tinggi_mdpl: number
  lokasi: string
  foto_gunung?: string | null
  tanggal_summit: string
  certificate_url?: string | null
}
