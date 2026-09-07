<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  Building2,
  Calendar,
  MapPin,
  Ticket,
  Tent,
  Users,
  ShieldCheck,
  CreditCard,
  AlertCircle,
  Loader2,
  ShoppingBag,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { pendakiCheckoutApi } from '@/api/pendakiCheckout'
import ClimberManifestForm from '@/components/pendaki/ClimberManifestForm.vue'
import SafetySopChecklist from '@/components/pendaki/SafetySopChecklist.vue'
import PaymentModal from '@/components/pendaki/PaymentModal.vue'
import KycStatusBanner from '@/components/pendaki/KycStatusBanner.vue'
import { formatRupiah, formatDateIndonesia } from '@/lib/formatters'
import { extractApiError } from '@/lib/normalizer'
import type { PesananAnggotaPayload, Pesanan } from '@/types/pendakiCheckout'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

const SERVICE_FEE = 2500

const manifestMembers = ref<PesananAnggotaPayload[]>([])
const isManifestValid = ref(false)
const sopAgreed = ref(false)
const isSubmitting = ref(false)
const submitError = ref('')

const isPaymentModalOpen = ref(false)
const createdOrder = ref<Pesanan | null>(null)
const checkoutUrl = ref<string | null>(null)

const userProfile = computed(() => {
  return {
    name: authStore.user?.name || '',
    nik: authStore.pendakiProfile?.nik || '',
    telepon: authStore.user?.telepon || '',
    telepon_darurat: authStore.pendakiProfile?.telepon_darurat || '',
    hubungan_darurat: authStore.pendakiProfile?.hubungan_darurat || '',
  }
})

const ticketCount = computed(() => {
  return cartStore.ticketItem ? cartStore.ticketItem.qty : 1
})

const isFormReady = computed(() => {
  return (
    cartStore.hasItems &&
    isManifestValid.value &&
    sopAgreed.value &&
    !isSubmitting.value
  )
})

async function handleCheckout() {
  if (!isFormReady.value) return

  isSubmitting.value = true
  submitError.value = ''

  try {
    const response = await pendakiCheckoutApi.checkout({
      anggotas: manifestMembers.value,
    })

    if (response.data) {
      createdOrder.value = response.data
      checkoutUrl.value = response.checkout_url || response.data.pembayaran?.checkout_url || null
      isPaymentModalOpen.value = true

      // Refresh cart in background
      await cartStore.fetchCart()
    }
  } catch (err: unknown) {
    submitError.value = extractApiError(err).message
  } finally {
    isSubmitting.value = false
  }
}

function handlePaymentSuccess(order: Pesanan) {
  router.push({
    name: 'pendaki.profile',
    query: { tab: 'orders', invoice: order.invoice },
  })
}

onMounted(async () => {
  if (!cartStore.cart) {
    await cartStore.fetchCart()
  }
  if (!authStore.pendakiProfile) {
    await authStore.fetchKycStatus()
  }
})
</script>

<template>
  <div class="space-y-8 max-w-5xl mx-auto pb-20">
    <!-- Header Navigation & Title -->
    <div class="space-y-4">
      <button
        type="button"
        class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
        @click="router.push('/pendaki/cart')"
      >
        <ArrowLeft class="w-4 h-4" />
        <span>Kembali ke Keranjang</span>
      </button>

      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-slate-100 font-display">
          Manifes Rombongan &amp; Checkout
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
          Lengkapi identitas rombongan pendaki dan setujui SOP keselamatan untuk menerbitkan izin SIMAKSI resmi.
        </p>
      </div>
    </div>

    <!-- KYC Banner if unverified -->
    <KycStatusBanner
      v-if="!authStore.isKycVerified"
      :status="authStore.kycStatus"
      @request-verification="router.push('/pendaki/kyc')"
    />

    <!-- Empty Cart Guard -->
    <div
      v-if="!cartStore.hasItems && !createdOrder"
      class="p-12 text-center rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-4"
    >
      <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
        <ShoppingBag class="w-7 h-7" />
      </div>
      <div class="space-y-1 max-w-sm mx-auto">
        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Keranjang Masih Kosong</h3>
        <p class="text-xs text-slate-500">
          Silakan tambahkan tiket atau perlengkapan sewa sebelum melanjutkan ke tahap pengisian manifes.
        </p>
      </div>
      <router-link to="/pendaki/gunung">
        <Button class="rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs">
          Cari Gunung &amp; Jalur
        </Button>
      </router-link>
    </div>

    <!-- Main 2-Column Form Layout -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
      <!-- Left Column: Manifest & SOP (2 Cols) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Error Alert -->
        <div
          v-if="submitError"
          class="p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-3"
        >
          <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
          <div class="space-y-1">
            <strong class="font-bold block">Gagal Memproses Pesanan</strong>
            <p>{{ submitError }}</p>
          </div>
        </div>

        <!-- Climber Manifest Form -->
        <ClimberManifestForm
          v-model="manifestMembers"
          :ticket-qty="ticketCount"
          :user-profile="userProfile"
          :disabled="isSubmitting"
          @validity-change="isManifestValid = $event"
        />

        <!-- Safety SOP Checklist -->
        <SafetySopChecklist
          v-model="sopAgreed"
          :mountain-name="cartStore.cart?.basecamp?.nama_basecamp || 'Kawasan Konservasi'"
          :trail-name="cartStore.cart?.jalur?.nama_jalur || 'Jalur Resmi'"
          :disabled="isSubmitting"
        />
      </div>

      <!-- Right Column: Order Summary & Sticky CTA (1 Col) -->
      <div class="space-y-4 sticky top-24">
        <div class="rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm space-y-5">
          <!-- Booking Context -->
          <div class="p-4 rounded-2xl bg-emerald-950 text-white space-y-2">
            <div class="flex items-center gap-1.5 text-[11px] text-emerald-300 font-semibold">
              <Building2 class="w-3.5 h-3.5" />
              <span>Basecamp Tujuan</span>
            </div>
            <h4 class="font-bold text-sm leading-snug">
              {{ cartStore.cart?.basecamp?.nama_basecamp || 'Basecamp Pengelola' }}
            </h4>
            <div class="flex flex-col gap-1 text-[11px] text-emerald-200/80 pt-1">
              <span class="flex items-center gap-1.5">
                <MapPin class="w-3 h-3 text-orange-400 shrink-0" />
                {{ cartStore.cart?.jalur?.nama_jalur || 'Jalur Resmi' }}
              </span>
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3 h-3 text-emerald-400 shrink-0" />
                {{ cartStore.cart?.tanggal_booking ? formatDateIndonesia(cartStore.cart.tanggal_booking) : 'Sesuai Jadwal' }}
              </span>
            </div>
          </div>

          <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 pb-2 border-b border-slate-100 dark:border-slate-800">
            Rincian Pembayaran
          </h3>

          <!-- Items list preview -->
          <div class="space-y-2 text-xs divide-y divide-slate-100 dark:divide-slate-800">
            <!-- Ticket -->
            <div v-if="cartStore.ticketItem" class="pt-2 first:pt-0 flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                <Ticket class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>SIMAKSI ({{ cartStore.ticketItem.qty }} Org)</span>
              </span>
              <strong class="text-slate-900 dark:text-slate-100">
                {{ formatRupiah((cartStore.ticketItem.produk?.harga || 0) * cartStore.ticketItem.qty) }}
              </strong>
            </div>

            <!-- Rental summary -->
            <div v-if="cartStore.rentalItems.length > 0" class="pt-2 flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                <Tent class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                <span>Sewa Alat ({{ cartStore.rentalItems.length }} item)</span>
              </span>
              <strong class="text-slate-900 dark:text-slate-100">
                {{ formatRupiah(cartStore.rentalItems.reduce((acc, it) => acc + (it.produk?.harga || 0) * it.qty, 0)) }}
              </strong>
            </div>

            <!-- Service summary -->
            <div v-if="cartStore.serviceItems.length > 0" class="pt-2 flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                <Users class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                <span>Guide &amp; Porter ({{ cartStore.serviceItems.length }} item)</span>
              </span>
              <strong class="text-slate-900 dark:text-slate-100">
                {{ formatRupiah(cartStore.serviceItems.reduce((acc, it) => acc + (it.produk?.harga || 0) * it.qty, 0)) }}
              </strong>
            </div>

            <!-- Service Fee -->
            <div class="pt-2 flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-400">Biaya Layanan Platform</span>
              <strong class="text-slate-900 dark:text-slate-100">{{ formatRupiah(SERVICE_FEE) }}</strong>
            </div>

            <!-- Total -->
            <div class="pt-3 flex items-center justify-between text-sm">
              <span class="font-bold text-slate-900 dark:text-slate-100">Total Tagihan:</span>
              <span class="font-black text-lg text-emerald-700 dark:text-emerald-400 font-display">
                {{ formatRupiah(cartStore.subtotal + SERVICE_FEE) }}
              </span>
            </div>
          </div>

          <!-- Submit CTA Button -->
          <Button
            class="w-full h-12 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs sm:text-sm gap-2 shadow-md transition-all active:scale-[0.99] disabled:opacity-50"
            :disabled="!isFormReady"
            @click="handleCheckout"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <CreditCard v-else class="w-4 h-4" />
            <span>{{ isSubmitting ? 'Membuat Pesanan...' : 'Bayar Sekarang (Xendit)' }}</span>
          </Button>

          <!-- Form validation hints if disabled -->
          <div v-if="!isFormReady" class="text-[11px] text-amber-700 space-y-1 bg-amber-50/60 p-2.5 rounded-xl border border-amber-200/60">
            <span class="font-bold block">Perhatian:</span>
            <ul class="list-disc list-inside space-y-0.5">
              <li v-if="!isManifestValid">Lengkapi nama &amp; 16-digit NIK semua anggota manifes.</li>
              <li v-if="!sopAgreed">Centang kotak persetujuan SOP Pendakian.</li>
            </ul>
          </div>

          <!-- Trust Badges -->
          <div class="pt-2 border-t border-slate-100 dark:border-slate-800 space-y-2 text-[11px] text-slate-500">
            <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400 font-semibold">
              <ShieldCheck class="w-3.5 h-3.5" />
              <span>Garansi Keabsahan SIMAKSI</span>
            </div>
            <p>Izin dan QR code otomatis aktif dan siap digunakan untuk check-in basecamp setelah pembayaran terkonfirmasi.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Xendit Payment Gateway Modal -->
    <PaymentModal
      v-model:is-open="isPaymentModalOpen"
      :order="createdOrder"
      :checkout-url="checkoutUrl"
      @payment-success="handlePaymentSuccess"
    />
  </div>
</template>
