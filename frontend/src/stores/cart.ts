import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { pendakiCartApi } from '@/api/pendakiCart'
import { extractApiError } from '@/lib/normalizer'
import type { Cart, CartItem, AddCartItemPayload } from '@/types/pendakiCart'

export const useCartStore = defineStore('cart', () => {
  const cart = ref<Cart | null>(null)
  const isLoading = ref(false)
  const errorMessage = ref<string | null>(null)

  // Getters
  const totalItems = computed(() => {
    if (!cart.value?.items) return 0
    return cart.value.items.reduce((sum, item) => sum + item.qty, 0)
  })

  const subtotal = computed(() => {
    if (!cart.value?.items) return 0
    return cart.value.items.reduce((sum, item) => {
      const price = item.produk?.harga || 0
      return sum + price * item.qty
    }, 0)
  })

  const ticketItem = computed<CartItem | null>(() => {
    if (!cart.value?.items) return null
    return (
      cart.value.items.find(
        item =>
          item.produk?.kategori === 'tiket' ||
          (item.produk as any)?.tipe === 'tiket' ||
          (item as any).tipe_produk === 'tiket'
      ) || null
    )
  })

  const rentalItems = computed<CartItem[]>(() => {
    if (!cart.value?.items) return []
    return cart.value.items.filter(
      item =>
        item.produk?.kategori === 'rental' ||
        (item.produk as any)?.tipe === 'rental' ||
        item.produk?.kategori === 'merchandise' ||
        item.produk?.kategori === 'konsumsi' ||
        (item as any).tipe_produk === 'rental'
    )
  })

  const serviceItems = computed<CartItem[]>(() => {
    if (!cart.value?.items) return []
    return cart.value.items.filter(
      item =>
        item.produk?.kategori === 'jasa' ||
        (item.produk as any)?.tipe === 'jasa' ||
        (item as any).tipe_produk === 'jasa'
    )
  })

  const hasItems = computed(() => totalItems.value > 0)
  const currentBasecampId = computed(() => cart.value?.basecamp_id || null)

  // Actions
  async function fetchCart() {
    isLoading.value = true
    errorMessage.value = null
    try {
      const res = await pendakiCartApi.getCart()
      cart.value = res.data || null
    } catch (err) {
      errorMessage.value = extractApiError(err).message
    } finally {
      isLoading.value = false
    }
  }

  async function addToCart(payload: AddCartItemPayload) {
    isLoading.value = true
    errorMessage.value = null
    try {
      const res = await pendakiCartApi.addItem(payload)
      cart.value = res.data || null
      return true
    } catch (err) {
      errorMessage.value = extractApiError(err).message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function updateQuantity(itemId: number, qty: number) {
    isLoading.value = true
    errorMessage.value = null
    try {
      const res = await pendakiCartApi.updateItem(itemId, { qty })
      cart.value = res.data || null
    } catch (err) {
      errorMessage.value = extractApiError(err).message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function removeItem(itemId: number) {
    isLoading.value = true
    errorMessage.value = null
    try {
      await pendakiCartApi.removeItem(itemId)
      if (cart.value?.items) {
        cart.value.items = cart.value.items.filter(i => i.id !== itemId)
        if (cart.value.items.length === 0) {
          cart.value = null
        }
      }
    } catch (err) {
      errorMessage.value = extractApiError(err).message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function clearCart() {
    isLoading.value = true
    errorMessage.value = null
    try {
      await pendakiCartApi.clearCart()
      cart.value = null
    } catch (err) {
      errorMessage.value = extractApiError(err).message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    cart,
    isLoading,
    errorMessage,
    totalItems,
    subtotal,
    ticketItem,
    rentalItems,
    serviceItems,
    hasItems,
    currentBasecampId,
    fetchCart,
    addToCart,
    updateQuantity,
    removeItem,
    clearCart,
  }
})
