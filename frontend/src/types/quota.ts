export interface KuotaHarian {
  id: number
  produk_tiket_id: number
  tanggal: string
  kuota_total: number
  kuota_tersisa: number
  kuota_terpakai?: number
  created_at?: string
  updated_at?: string
}

export interface BatchQuotaPayload {
  start_date: string
  end_date: string
  kuota_total: number
}

export interface UpdateSingleQuotaPayload {
  kuota_total: number
}

export interface QuotaFilterParams {
  start_date?: string
  end_date?: string
}

export interface QuotaSummaryStats {
  kuota_hari_ini: number
  tersisa_hari_ini: number
  total_kuota_bulan_ini: number
  terpesan_bulan_ini: number
  occupancy_rate: number
}
