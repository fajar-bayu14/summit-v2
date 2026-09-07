export interface FinancialSummary {
  saldo_available: number
  saldo_pending: number
  total_penarikan: number
  total_pendapatan_bersih: number
  total_transaksi_paid: number
}

export interface OperationsTodaySummary {
  pendaki_berangkat_hari_ini: number
  pendaki_sedang_mendaki: number
  total_kuota_hari_ini: number
  kuota_terpakai_hari_ini: number
  sisa_kuota_hari_ini: number
  status_jalur: 'open' | 'close' | string
}

export interface ActionQueuesSummary {
  pesanan_paid_count: number
  logbook_pending_count: number
  refund_pending_count: number
}

export interface ResourcesSummary {
  total_basecamp: number
  total_produk_aktif: number
  total_staf_tersedia: number
  total_staf_bertugas: number
  produk_stok_menipis_count: number
}

export interface MitraAnalyticsSummary {
  financial: FinancialSummary
  operations_today: OperationsTodaySummary
  action_queues: ActionQueuesSummary
  resources: ResourcesSummary
}

export interface EmergencyTrailPayload {
  status: 'open' | 'close'
  alasan_penutupan?: string
}
