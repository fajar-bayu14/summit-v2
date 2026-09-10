<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { pendakiProductsApi } from '@/api/pendakiProducts'
import { formatRupiah } from '@/lib/formatters'
import { Flame, ShieldCheck, ShoppingBag, Star, Zap } from 'lucide-vue-next'

interface DealItem {
  id: number
  nama: string
  kategori: string
  hargaAsli: number
  hargaDiskon: number
  diskonPersen: number
  gambar: string
  rating: number
  tersewa: number
  basecamp: string
  basecampId?: number
}

const defaultDeals: DealItem[] = [
  {
    id: 1,
    nama: 'Paket Tenda Dome 4P Double Layer + Footprint Waterproof',
    kategori: 'Tenda & Shelter',
    hargaAsli: 85000,
    hargaDiskon: 60000,
    diskonPersen: 30,
    gambar: 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=400&q=80',
    rating: 4.9,
    tersewa: 142,
    basecamp: 'Basecamp Bambangan'
  },
  {
    id: 2,
    nama: 'Sleeping Bag Bulu Angsa Extreme Comfort 0°C',
    kategori: 'Sleeping Bag',
    hargaAsli: 45000,
    hargaDiskon: 35000,
    diskonPersen: 22,
    gambar: 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=400&q=80',
    rating: 4.8,
    tersewa: 98,
    basecamp: 'Basecamp Selo'
  },
  {
    id: 3,
    nama: 'Kompor Ultralight Windproof + Cooking Set DS-308',
    kategori: 'Cooking Set',
    hargaAsli: 35000,
    hargaDiskon: 25000,
    diskonPersen: 28,
    gambar: 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=400&q=80',
    rating: 4.9,
    tersewa: 215,
    basecamp: 'Basecamp Patakbanteng'
  },
  {
    id: 4,
    nama: 'Jasa Porter Drop Pos 1 ke Pos 3 (Beban Max 20kg)',
    kategori: 'Jasa Porter',
    hargaAsli: 300000,
    hargaDiskon: 250000,
    diskonPersen: 17,
    gambar: 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=400&q=80',
    rating: 5.0,
    tersewa: 84,
    basecamp: 'Basecamp Kaliwurang'
  }
]

const deals = ref<DealItem[]>(defaultDeals)
const loading = ref(false)

async function fetchProducts() {
  loading.value = true
  try {
    const res = await pendakiProductsApi.getProducts({ per_page: 8 })
    const items = (res.data as any)?.items || (res.data as any)?.data || (Array.isArray(res.data) ? res.data : [])
    const nonTickets = items.filter((p: any) => p.kategori !== 'tiket')
    if (nonTickets.length > 0) {
      deals.value = nonTickets.slice(0, 4).map((p: any, idx: number) => {
        const harga = Number(p.harga) || 50000
        const diskon = [20, 25, 30, 15][idx % 4]
        const hargaAsli = Math.round(harga * (100 / (100 - diskon)))
        return {
          id: p.id,
          nama: p.nama_produk,
          kategori: p.kategori === 'rental' ? 'Sewa Alat Outdoor' : (p.kategori === 'jasa' ? 'Pemandu & Porter' : p.kategori),
          hargaAsli,
          hargaDiskon: harga,
          diskonPersen: diskon,
          gambar: p.foto || defaultDeals[idx % defaultDeals.length].gambar,
          rating: 4.8 + (idx % 3) * 0.1,
          tersewa: 50 + p.id * 12,
          basecamp: p.basecamp?.nama_basecamp || 'Basecamp Mitra',
          basecampId: p.basecamp_id
        }
      })
    }
  } catch {
    // Keep defaultDeals
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <div class="rounded-3xl border border-orange-100 bg-gradient-to-br from-orange-50/50 via-white to-orange-50/20 p-6 shadow-sm space-y-4">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-orange-100/80 pb-4">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-[#E65100] to-amber-500 text-white shadow-md">
          <Flame class="h-6 w-6 animate-pulse" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-lg font-black text-gray-900 tracking-tight">Flash Deals Logistik & Sewa Alat</h3>
            <span class="inline-flex items-center gap-1 rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white uppercase tracking-wider animate-bounce">
              <Zap class="h-2.5 w-2.5" />
              Hemat
            </span>
          </div>
          <p class="text-xs text-gray-500">Peralatan outdoor dan jasa basecamp terverifikasi standar keamanan</p>
        </div>
      </div>

      <router-link
        to="/pendaki/gunung"
        class="text-xs font-bold text-[#E65100] hover:text-[#d84a00] flex items-center gap-1 transition"
      >
        <span>Lihat Semua Penawaran</span>
        <span>&rarr;</span>
      </router-link>
    </div>

    <!-- Deals Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="item in deals"
        :key="item.id"
        class="group relative flex flex-col justify-between rounded-2xl border border-gray-200 bg-white p-3.5 shadow-xs transition hover:border-orange-300 hover:shadow-md"
      >
        <div class="space-y-2.5">
          <!-- Image & Discount Badge -->
          <div class="relative aspect-4/3 w-full overflow-hidden rounded-xl bg-gray-100">
            <img
              :src="item.gambar"
              :alt="item.nama"
              class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div class="absolute left-2 top-2 rounded-lg bg-[#E65100] px-2 py-0.5 text-[10px] font-extrabold text-white shadow-xs">
              -{{ item.diskonPersen }}%
            </div>
            <div class="absolute right-2 bottom-2 rounded-md bg-black/60 backdrop-blur-xs px-1.5 py-0.5 text-[10px] font-semibold text-white flex items-center gap-1">
              <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
              <span>{{ item.rating }}</span>
            </div>
          </div>

          <!-- Product Info -->
          <div>
            <div class="flex items-center gap-1 text-[11px] text-gray-400 mb-0.5">
              <ShieldCheck class="h-3 w-3 text-emerald-600" />
              <span>{{ item.basecamp }}</span>
            </div>
            <h4 class="text-xs font-bold text-gray-900 line-clamp-2 leading-snug group-hover:text-[#1E3A2B] transition">
              {{ item.nama }}
            </h4>
          </div>
        </div>

        <!-- Pricing & Action -->
        <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between">
          <div>
            <p class="text-[10px] text-gray-400 line-through">{{ formatRupiah(item.hargaAsli) }}</p>
            <p class="text-sm font-black text-[#E65100]">{{ formatRupiah(item.hargaDiskon) }}</p>
          </div>

          <router-link
            to="/pendaki/gunung"
            class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#1E3A2B] text-white shadow-xs hover:bg-[#15281e] transition"
            title="Pilih Basecamp & Sewa"
          >
            <ShoppingBag class="h-4 w-4" />
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>
