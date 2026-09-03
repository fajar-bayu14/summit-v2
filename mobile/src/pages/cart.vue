<template>
  <div class="summit-page">
    <!-- Header -->
    <header class="summit-top-bar">
      <button
        class="summit-icon-btn"
        aria-label="Kembali"
        @click="$router.back()"
      >
        <q-icon name="arrow_back" />
      </button>
      <h1 class="summit-top-bar__title">Keranjang Pendakian</h1>
      <button
        v-if="cartItems.length > 0"
        class="summit-icon-btn"
        aria-label="Kosongkan"
        @click="handleClearCart"
      >
        <q-icon name="delete_outline" />
      </button>
      <div v-else style="width: 36px"></div>
    </header>

    <main class="summit-page__content">
      <div v-if="loading" class="text-center q-pa-xl">
        <q-spinner color="primary" size="2.5em" />
      </div>

      <!-- Empty Cart -->
      <div v-else-if="cartItems.length === 0" class="empty-state">
        <q-icon name="shopping_cart" size="48px" class="text-grey-5" />
        <h3 class="empty-state__title">Keranjang Masih Kosong</h3>
        <p class="empty-state__text">
          Pilih tiket pendakian dan perlengkapan sewa yang kamu butuhkan.
        </p>
        <button class="btn-primary" @click="$router.push('/')">
          Jelajahi Gunung
        </button>
      </div>

      <!-- Cart Items -->
      <div v-else class="cart-container">
        <!-- Basecamp & Booking Schedule Card -->
        <section class="cart-section-card">
          <div class="cart-section-header">
            <q-icon name="event" size="18px" class="text-primary" />
            <h3>Jadwal & Lokasi Pendakian</h3>
          </div>
          <div class="cart-info-row">
            <span class="text-grey-7">Tanggal Naik:</span>
            <span class="font-weight-bold">{{
              bookingDate || 'Pilih tanggal'
            }}</span>
          </div>
          <div class="cart-info-row">
            <span class="text-grey-7">Tanggal Turun:</span>
            <span class="font-weight-bold">{{ bookingEndDate || '-' }}</span>
          </div>
        </section>

        <!-- Ticket & Gear Items -->
        <section class="cart-section-card">
          <div class="cart-section-header">
            <q-icon name="inventory_2" size="18px" class="text-primary" />
            <h3>Item Terpilih ({{ cartItems.length }})</h3>
          </div>

          <div class="cart-items-list">
            <div
              v-for="item in cartItems"
              :key="item.id || item.product_id"
              class="cart-item-row"
            >
              <div class="cart-item-info">
                <h4 class="cart-item-name">{{
                  item.product_name || item.name || 'Produk Tiket/Sewa'
                }}</h4>
                <span class="cart-item-type">{{
                  item.tipe === 'tiket' ? 'Tiket Simaksi' : 'Sewa Alat Outdoor'
                }}</span>
                <span class="cart-item-price"
                  >Rp {{ formatNumber(item.price || item.harga) }}</span
                >
              </div>

              <div class="cart-item-qty">
                <button
                  class="qty-btn"
                  :disabled="item.quantity <= 1"
                  @click="updateQty(item, item.quantity - 1)"
                >
                  <q-icon name="remove" size="14px" />
                </button>
                <span class="qty-num">{{ item.quantity }}</span>
                <button
                  class="qty-btn"
                  @click="updateQty(item, item.quantity + 1)"
                >
                  <q-icon name="add" size="14px" />
                </button>
                <button
                  class="qty-remove"
                  aria-label="Hapus Item"
                  @click="handleRemoveItem(item)"
                >
                  <q-icon name="close" size="14px" />
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Price Breakdown -->
        <section class="cart-section-card">
          <div class="cart-section-header">
            <q-icon name="receipt" size="18px" class="text-primary" />
            <h3>Ringkasan Biaya</h3>
          </div>

          <div class="cart-summary-rows">
            <div class="summary-row">
              <span>Subtotal Item</span>
              <span>Rp {{ formatNumber(subtotal) }}</span>
            </div>
            <div class="summary-row">
              <span>Biaya Layanan Platform</span>
              <span>Rp {{ formatNumber(serviceFee) }}</span>
            </div>
            <div class="summary-row is-total">
              <span>Total Pembayaran</span>
              <span class="total-amount">Rp {{ formatNumber(totalPay) }}</span>
            </div>
          </div>
        </section>

        <!-- Sticky Floating Checkout Button -->
        <footer class="floating-checkout-bar">
          <div class="checkout-total-col">
            <span class="checkout-label">Total Tagihan</span>
            <span class="checkout-value">Rp {{ formatNumber(totalPay) }}</span>
          </div>
          <button
            class="btn-primary checkout-btn"
            :disabled="checkingOut"
            @click="handleCheckout"
          >
            <q-spinner-dots v-if="checkingOut" />
            <span v-else>Lanjut Pembayaran</span>
          </button>
        </footer>
      </div>
    </main>

    <!-- Bottom navigation -->
    <BottomNav active="beranda" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import BottomNav from '@/components/BottomNav.vue'
import { getCart, updateCartItem, removeCartItem, clearCart } from '@/api/cart'
import { checkoutCart } from '@/api/orders'

const router = useRouter()
const $q = useQuasar()

const loading = ref(true)
const checkingOut = ref(false)
const cartData = ref(null)

const cartItems = computed(() => {
  return cartData.value?.items ?? []
})

const bookingDate = computed(() => cartData.value?.tanggal_booking || '')
const bookingEndDate = computed(
  () => cartData.value?.tanggal_selesai_booking || ''
)

const subtotal = computed(() => {
  return cartItems.value.reduce(
    (acc, item) =>
      acc + Number(item.price || item.harga || 0) * (item.quantity || 1),
    0
  )
})

const serviceFee = computed(() => {
  return subtotal.value > 0 ? 5000 : 0
})

const totalPay = computed(() => {
  return subtotal.value + serviceFee.value
})

onMounted(() => {
  fetchCart()
})

async function fetchCart() {
  loading.value = true
  try {
    const res = await getCart()
    cartData.value = res.data ?? res
  } catch (err) {
    // If not set up yet
    cartData.value = { items: [] }
  } finally {
    loading.value = false
  }
}

async function updateQty(item, newQty) {
  if (newQty < 1) return
  item.quantity = newQty
  try {
    if (item.id) {
      await updateCartItem(item.id, { quantity: newQty })
    }
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Gagal memperbarui jumlah item' })
  }
}

async function handleRemoveItem(item) {
  try {
    if (item.id) {
      await removeCartItem(item.id)
    }
    cartData.value.items = cartItems.value.filter(
      i => (i.id || i.product_id) !== (item.id || item.product_id)
    )
    $q.notify({ type: 'positive', message: 'Item dihapus dari keranjang.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Gagal menghapus item' })
  }
}

async function handleClearCart() {
  try {
    await clearCart()
    cartData.value = { items: [] }
    $q.notify({ type: 'positive', message: 'Keranjang dikosongkan.' })
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Gagal mengosongkan keranjang' })
  }
}

async function handleCheckout() {
  if (cartItems.value.length === 0) return

  checkingOut.value = true
  try {
    const res = await checkoutCart({
      metode_pembayaran: 'xendit',
      anggotas: [
        {
          nama_lengkap: 'Ketua Tim / Akun Utama',
          nik: '3201000000000001',
          nomor_telepon: '08123456789'
        }
      ]
    })

    $q.notify({
      type: 'positive',
      message: 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.'
    })
    router.push('/orders')
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Gagal memproses checkout'
    })
  } finally {
    checkingOut.value = false
  }
}

function formatNumber(num) {
  if (!num) return '0'
  return Number(num).toLocaleString('id-ID')
}
</script>

<style lang="scss">
.cart-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding-bottom: 70px;
}

.cart-section-card {
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  padding: 16px;
}

.cart-section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid $summit-surface-container;

  h3 {
    font-size: 0.9375rem;
    font-weight: 700;
    color: $summit-text-primary;
    margin: 0;
  }
}

.cart-info-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.8125rem;
  margin-bottom: 6px;
}

.cart-items-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cart-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 10px;
  border-bottom: 1px dashed $summit-surface-container;

  &:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }
}

.cart-item-info {
  flex: 1;
}

.cart-item-name {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0 0 2px 0;
  color: $summit-text-primary;
}

.cart-item-type {
  display: block;
  font-size: 0.6875rem;
  color: $summit-text-secondary;
  margin-bottom: 4px;
}

.cart-item-price {
  font-size: 0.8125rem;
  font-weight: 700;
  color: $primary;
  font-variant-numeric: tabular-nums;
}

.cart-item-qty {
  display: flex;
  align-items: center;
  gap: 6px;
}

.qty-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid $summit-border;
  background: $summit-surface-container-low;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;

  &:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}

.qty-num {
  font-size: 0.875rem;
  font-weight: 700;
  min-width: 20px;
  text-align: center;
}

.qty-remove {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: #ef4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-summary-rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.8125rem;
  color: $summit-text-secondary;

  &.is-total {
    font-size: 1rem;
    font-weight: 700;
    color: $summit-text-primary;
    padding-top: 8px;
    border-top: 1px solid $summit-surface-container;
  }
}

.total-amount {
  color: $primary;
  font-variant-numeric: tabular-nums;
}

.floating-checkout-bar {
  position: fixed;
  bottom: 64px;
  left: 0;
  right: 0;
  background: $summit-surface;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);
  z-index: 40;
}

.checkout-total-col {
  display: flex;
  flex-direction: column;
}

.checkout-label {
  font-size: 0.6875rem;
  color: $summit-text-secondary;
}

.checkout-value {
  font-size: 1rem;
  font-weight: 700;
  color: $primary;
  font-variant-numeric: tabular-nums;
}

.checkout-btn {
  padding: 10px 24px;
}
</style>
