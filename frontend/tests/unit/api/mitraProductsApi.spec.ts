import { describe, it, expect, beforeEach, vi } from 'vitest'
import mitraProductsApi from '@/api/mitraProducts'
import { apiClient } from '@/lib/axios'
import type { StoreProductPayload, UpdateProductPayload } from '@/types/product'

describe('Mitra Products API Service (Task 2.1)', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('should call getProducts with given parameters', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Products fetched successfully.',
        data: {
          data: [
            {
              id: 1,
              basecamp_id: 2,
              nama_produk: 'Tenda Dome 4P',
              kategori: 'rental',
              harga: 50000,
              stok: 10,
              is_active: true,
            },
          ],
          total: 1,
          current_page: 1,
          last_page: 1,
        },
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const params = { page: 1, kategori: 'rental', search: 'dome', basecamp_id: 2 }
    const result = await mitraProductsApi.getProducts(params)

    expect(getSpy).toHaveBeenCalledWith('/mitra/products', { params })
    expect(result.status).toBe('success')
  })

  it('should call getProduct by ID', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Product details fetched successfully.',
        data: {
          id: 5,
          basecamp_id: 1,
          nama_produk: 'Tiket Pendakian Jalur Selo',
          kategori: 'ticket',
          harga: 25000,
          is_active: true,
          tiket: {
            id: 1,
            produk_id: 5,
            jalur_id: 2,
            jam_buka: '06:00',
            jam_tutup: '17:00',
          },
        },
      },
    }

    const getSpy = vi.spyOn(apiClient, 'get').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.getProduct(5)

    expect(getSpy).toHaveBeenCalledWith('/mitra/products/5')
    expect(result.data?.nama_produk).toBe('Tiket Pendakian Jalur Selo')
    expect(result.data?.tiket?.jam_buka).toBe('06:00')
  })

  it('should call createProduct with payload', async () => {
    const payload: StoreProductPayload = {
      basecamp_id: 1,
      nama_produk: 'Sleeping Bag Polar',
      kategori: 'rental',
      deskripsi: 'Hangat tahan hingga 5 derajat',
      harga: 20000,
      stok: 15,
      satuan: 'hari',
      is_active: true,
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Product created successfully.',
        data: { id: 10, ...payload },
      },
    }

    const postSpy = vi.spyOn(apiClient, 'post').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.createProduct(payload)

    expect(postSpy).toHaveBeenCalledWith('/mitra/products', payload)
    expect(result.data?.id).toBe(10)
    expect(result.status).toBe('success')
  })

  it('should call updateProduct with id and payload', async () => {
    const payload: UpdateProductPayload = {
      nama_produk: 'Sleeping Bag Bulu Angsa Ultra Warm',
      harga: 35000,
      stok: 8,
    }

    const mockResponse = {
      data: {
        status: 'success',
        message: 'Product updated successfully.',
        data: { id: 10, ...payload },
      },
    }

    const putSpy = vi.spyOn(apiClient, 'put').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.updateProduct(10, payload)

    expect(putSpy).toHaveBeenCalledWith('/mitra/products/10', payload)
    expect(result.status).toBe('success')
  })

  it('should call deleteProduct by id', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Product deleted successfully.',
        data: null,
      },
    }

    const deleteSpy = vi.spyOn(apiClient, 'delete').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.deleteProduct(10)

    expect(deleteSpy).toHaveBeenCalledWith('/mitra/products/10')
    expect(result.status).toBe('success')
  })

  it('should call toggleProductStatus with boolean', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Status produk berhasil diperbarui.',
        data: { id: 10, is_active: false },
      },
    }

    const patchSpy = vi.spyOn(apiClient, 'patch').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.toggleProductStatus(10, false)

    expect(patchSpy).toHaveBeenCalledWith('/mitra/products/10/toggle-status', { is_active: false })
    expect(result.data?.is_active).toBe(false)
  })

  it('should call updateProductStock with numeric stock', async () => {
    const mockResponse = {
      data: {
        status: 'success',
        message: 'Stok produk berhasil diperbarui.',
        data: { id: 10, stok: 25 },
      },
    }

    const patchSpy = vi.spyOn(apiClient, 'patch').mockResolvedValueOnce(mockResponse as any)

    const result = await mitraProductsApi.updateProductStock(10, 25)

    expect(patchSpy).toHaveBeenCalledWith('/mitra/products/10/stock', { stok: 25 })
    expect(result.data?.stok).toBe(25)
  })
})
