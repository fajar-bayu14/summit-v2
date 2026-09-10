import type { ProdukCatalogItem } from './pendakiProduct'

export interface CartItem {
  id: number
  cart_id: number
  produk_id: number
  qty: number
  tanggal_mulai_sewa?: string | null
  tanggal_selesai_sewa?: string | null
  catatan_item?: Record<string, any> | null
  produk: ProdukCatalogItem
}

export interface Cart {
  id: number
  user_id: number
  basecamp_id: number
  jalur_id: number
  tanggal_booking: string
  tanggal_selesai_booking?: string | null
  status: 'active' | 'ordered' | 'abandoned' | string
  total_item: number
  subtotal: number
  items: CartItem[]
  basecamp?: {
    id: number
    nama_basecamp: string
    mitra?: {
      id: number
      nama_mitra?: string
      nama_pemilik?: string
    }
  }
  jalur?: {
    id: number
    nama_jalur: string
    gunung?: {
      id: number
      nama_gunung: string
    }
  }
}

export interface CreateCartPayload {
  basecamp_id: number
  jalur_id: number
  tanggal_booking: string
  tanggal_selesai_booking?: string
}

export interface AddCartItemPayload {
  produk_id: number
  qty: number
  jalur_id?: number
  tanggal_booking?: string
  tanggal_selesai_booking?: string
  tanggal_mulai_sewa?: string
  tanggal_selesai_sewa?: string
  catatan_item?: Record<string, any>
}

export interface UpdateCartItemPayload {
  qty: number
  tanggal_mulai_sewa?: string
  tanggal_selesai_sewa?: string
  catatan_item?: Record<string, any>
}
