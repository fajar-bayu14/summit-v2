<template>
  <div class="rental">
    <!-- Desktop header (md+) -->
    <header class="rental__desktop-header">
      <div class="rental__desktop-brand">
        <span class="rental__desktop-logo">SummitPeak</span>
      </div>
      <nav class="rental__desktop-nav">
        <a class="is-active" aria-current="page" href="#" @click.prevent
          >Explore</a
        >
        <a href="#" @click.prevent="onSoon">Bookings</a>
        <a href="#" @click.prevent="onSoon">Saved</a>
      </nav>
      <div class="rental__desktop-actions">
        <button
          class="rental__icon-btn"
          aria-label="Kembali ke beranda"
          @click="goBack"
        >
          <q-icon name="arrow_back" aria-hidden="true" />
        </button>
      </div>
    </header>

    <!-- Mobile header -->
    <header class="rental__header">
      <button class="rental__icon-btn" aria-label="Kembali" @click="goBack">
        <q-icon name="arrow_back" aria-hidden="true" />
      </button>
      <h1 class="rental__title">Sewa Alat Pendakian</h1>
      <button
        class="rental__icon-btn rental__cart"
        aria-label="Buka keranjang"
        @click="onCart"
      >
        <q-icon name="shopping_cart" aria-hidden="true" />
        <span
          v-if="cartCount > 0"
          class="rental__cart-badge"
          aria-hidden="true"
          >{{ cartCount > 9 ? '9+' : cartCount }}</span
        >
      </button>
    </header>

    <!-- Sticky search & location -->
    <div class="rental__controls" :class="{ 'is-hidden': isControlsHidden }">
      <div class="rental__search">
        <q-icon class="rental__search-icon" name="search" aria-hidden="true" />
        <input
          ref="searchInput"
          v-model="searchQuery"
          class="rental__search-input"
          type="search"
          placeholder="Cari alat, tenda, atau nama basecamp..."
          aria-label="Cari alat, tenda, atau nama basecamp"
        />
        <button
          v-if="searchQuery"
          class="rental__search-clear"
          aria-label="Hapus pencarian"
          @click="clearSearch"
        >
          <q-icon name="close" aria-hidden="true" />
        </button>
      </div>

      <div class="rental__location">
        <button
          class="rental__location-btn"
          :aria-expanded="locationOpen"
          aria-haspopup="listbox"
          @click.stop="toggleLocation"
        >
          <q-icon name="location_on" aria-hidden="true" />
          <span class="rental__location-label"
            >Lokasi: {{ locationLabel }}</span
          >
          <q-icon name="arrow_drop_down" aria-hidden="true" />
        </button>
        <div v-if="locationOpen" class="rental__location-menu" role="listbox">
          <button
            v-for="option in locationOptions"
            :key="option.key"
            role="option"
            :aria-selected="selectedLocation === option.key"
            :class="{ 'is-selected': selectedLocation === option.key }"
            @click.stop="selectLocation(option.key)"
          >
            <span class="rental__location-option">{{ option.label }}</span>
            <q-icon
              v-if="selectedLocation === option.key"
              name="check"
              size="16px"
              aria-hidden="true"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <main class="rental__main">
      <!-- Loading skeletons -->
      <div v-if="isLoading" class="rental__list" aria-hidden="true">
        <div
          v-for="n in 2"
          :key="n"
          class="basecamp-card basecamp-card--skeleton"
        >
          <div class="basecamp-card__head">
            <div class="skeleton skeleton--line skeleton--line-lg"></div>
            <div class="skeleton skeleton--line skeleton--line-sm"></div>
          </div>
          <div class="basecamp-card__products">
            <div class="skeleton skeleton--line skeleton--line-xs"></div>
            <div class="basecamp-card__scroll">
              <div v-for="m in 3" :key="m" class="product-card">
                <div class="skeleton skeleton--media"></div>
                <div class="product-card__body">
                  <div class="skeleton skeleton--line skeleton--line-md"></div>
                  <div class="skeleton skeleton--line skeleton--line-sm"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error banner with fallback data -->
      <div v-else-if="loadError && useFallback" class="rental__notice">
        <q-icon name="cloud_off" size="16px" aria-hidden="true" />
        <span>Gagal memuat data terbaru, menampilkan contoh.</span>
        <button @click="retry">Coba lagi</button>
      </div>

      <!-- Empty state -->
      <div v-else-if="visibleBasecamps.length === 0" class="rental__empty">
        <q-icon name="backpack" size="44px" aria-hidden="true" />
        <h2 class="rental__empty-title">Alat tidak ditemukan</h2>
        <p class="rental__empty-text">{{ emptyText }}</p>
        <button class="rental__empty-action" @click="resetFilters"
          >Tampilkan semua alat</button
        >
      </div>

      <!-- Basecamp list -->
      <div v-else class="rental__list">
        <section
          v-for="basecamp in visibleBasecamps"
          :key="basecamp.id"
          class="basecamp-card"
        >
          <header class="basecamp-card__head">
            <div class="basecamp-card__heading">
              <h2 class="basecamp-card__name">
                {{ basecamp.name }}
                <q-icon
                  v-if="basecamp.verified"
                  name="verified"
                  size="16px"
                  class="basecamp-card__verified"
                  aria-hidden="true"
                />
              </h2>
              <p
                v-if="basecamp.rating || basecamp.distance"
                class="basecamp-card__meta"
              >
                <span v-if="basecamp.distance">
                  <q-icon name="map" size="14px" aria-hidden="true" />
                  {{ basecamp.distance }}
                </span>
                <span v-if="basecamp.rating">
                  <q-icon
                    name="star"
                    size="14px"
                    class="basecamp-card__star"
                    aria-hidden="true"
                  />
                  {{ basecamp.rating }}
                </span>
              </p>
            </div>
          </header>

          <div class="basecamp-card__products">
            <h3 class="basecamp-card__label">Tersedia untuk disewa</h3>
            <div
              class="basecamp-card__scroll"
              tabindex="0"
              role="list"
              :aria-label="`Produk sewaan ${basecamp.name}`"
            >
              <article
                v-for="product in basecamp.products"
                :key="product.id"
                role="listitem"
                class="product-card"
                :class="{ 'is-unavailable': !product.available }"
              >
                <div class="product-card__media">
                  <img
                    :src="product.photo"
                    :alt="product.name"
                    loading="lazy"
                  />
                </div>
                <div class="product-card__body">
                  <h4 class="product-card__name">{{ product.name }}</h4>
                  <span
                    class="product-card__status"
                    :class="
                      product.available ? 'is-available' : 'is-unavailable'
                    "
                  >
                    {{ product.available ? 'Tersedia' : 'Habis' }}
                  </span>
                  <div class="product-card__footer">
                    <div class="product-card__price">
                      <span class="product-card__price-value">{{
                        formatIDR(product.price)
                      }}</span>
                      <span class="product-card__price-unit"
                        >/{{ product.unit }}</span
                      >
                    </div>
                    <button
                      class="product-card__add"
                      :class="{ 'is-adding': addingState[product.id] }"
                      :disabled="!product.available || addingState[product.id]"
                      :aria-label="`Tambah ${product.name} ke keranjang`"
                      @click="addToCart(product)"
                    >
                      <q-icon
                        v-if="!addingState[product.id]"
                        name="add"
                        aria-hidden="true"
                      />
                      <q-spinner
                        v-else
                        size="18px"
                        color="white"
                        aria-hidden="true"
                      />
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </div>

          <button class="basecamp-card__more" @click="openBasecamp(basecamp)">
            Lihat Semua {{ basecamp.total }} Alat
          </button>
        </section>
      </div>
    </main>

    <BottomNav active="beranda" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import BottomNav from '@/components/BottomNav.vue'
import { getRentalProducts } from '@/api/mountains'

const $q = useQuasar()
const router = useRouter()

const searchQuery = ref('')
const debouncedQuery = ref('')
const basecamps = ref([])
const isLoading = ref(true)
const useFallback = ref(false)
const loadError = ref('')
const searchInput = ref(null)
const locationOpen = ref(false)
const selectedLocation = ref('all')
const cartCount = ref(Number(localStorage.getItem('summit_cart_count') || 0))
const addingState = ref({})
const isControlsHidden = ref(false)

let searchTimer = null
let lastScrollY = 0

const DEFAULT_PHOTO =
  'https://lh3.googleusercontent.com/aida-public/AB6AXuD2FtWifhlUqOXDj5Ni7zwACP4RQevnrVRY6A9uanBiRsiww94iBAPvwUZhZUwjN8w6w57Uz2aF70X4MdYiDnJ3RusPS7pMgtuHpEpNtPGrRZpKE9PL9SiCiYsvlR7w7XURFdxh7R89FLoKAYSkluxlBS4C-UALzb0W1C5ctRABIQ6ooeQCEzD86O2b7L6NuaUlikl7dapJr8ZbYXfUPVhN73z0WlZBAwz0JG6FcwIpOzLkG7Q7TAoH9Q'

const FALLBACK_BASECAMPS = [
  {
    id: 'fb-selo',
    name: 'Mitra Basecamp Selo Outdoor',
    verified: true,
    distance: '0.5 km',
    rating: '4.9',
    total: 12,
    products: [
      {
        id: 'fb-selo-tenda',
        name: '4-Person Dome Tent',
        price: 60000,
        unit: 'jam',
        photo:
          'https://lh3.googleusercontent.com/aida-public/AB6AXuD2FtWifhlUqOXDj5Ni7zwACP4RQevnrVRY6A9uanBiRsiww94iBAPvwUZhZUwjN8w6w57Uz2aF70X4MdYiDnJ3RusPS7pMgtuHpEpNtPGrRZpKE9PL9SiCiYsvlR7w7XURFdxh7R89FLoKAYSkluxlBS4C-UALzb0W1C5ctRABIQ6ooeQCEzD86O2b7L6NuaUlikl7dapJr8ZbYXfUPVhN73z0WlZBAwz0JG6FcwIpOzLkG7Q7TAoH9Q',
        available: true
      },
      {
        id: 'fb-selo-sleeping',
        name: 'Sleeping Bag -5°C',
        price: 20000,
        unit: 'jam',
        photo:
          'https://lh3.googleusercontent.com/aida-public/AB6AXuAZX0R5abS3I6rY3qux8l-fe_2bqyVdoRhZaxcZLZ_q2eYHrNKD27rpMHXhXrpXP-Sh_lJ7CGJfx44opGVVxeIiLuZBRT-YEoQ6KBzQtNJWJXkAS-V7t04uMXW9CnDoQu64kAOnsM0cMTUzF6fGy7cEpXLjWY5gDbOz4w1NzH5g2r4NoWJp_rmBUb7Ze-PlcI0R4SabFCBNVvaTLrfO78A0sruf0irnEDV1YHeF2Pu6CBiKpDH3YEHorw',
        available: true
      },
      {
        id: 'fb-selo-stove',
        name: 'Portable Stove',
        price: 15000,
        unit: 'jam',
        photo:
          'https://lh3.googleusercontent.com/aida-public/AB6AXuCPPJZrZiiqz9O1WoiwFFjKs4FCDPWJ-SBato3vL9BixwKDdr196G8G1SeOmCSvubFkh-CEhE6UtyT575cyDCwBQ06iD0wPpgdqEU3lxBiPoVyi6v0QkAU2ingHfxzgr-IVV7NtMcrYG99Ay-2bwwsc-iSPv7NtCWGWFrcIVmGQreY-DyZRdV-jw22TMg3uOUQp4x8bEkvj6ZAOKaGMuJKGfxL7SxnoG3GSsOX9oIGuKK_G1BKcySbUNQ',
        available: true
      }
    ]
  },
  {
    id: 'fb-jelajah',
    name: 'Basecamp Jelajah Merbabu Rental',
    verified: false,
    distance: '1.2 km',
    rating: '4.8',
    total: 8,
    products: [
      {
        id: 'fb-jelajah-carrier',
        name: 'Carrier 60L',
        price: 45000,
        unit: 'jam',
        photo:
          'https://lh3.googleusercontent.com/aida-public/AB6AXuDmUYCFmsPTqabkuAsMXWOa4E6yXNo8J5YOC9GyafBMSyjQCa76ocPDvb6QU2QqZ0Tu8wERP7bnzKT_SRBYy925bKu7fbTIKojlQHV7e5UQJSEdqgtvRD8mR7x05P3IysrMytjni1qj4bRQ0pWWNHXyMon2KE1NwMcCYVKz7KdlWUHAucsX78s_huti5Rqj7yIZie-lJpYeyAGg9zni8Ta5F8Lf4crUez9B5w0YkmWwB0NUbZYjBsrE1A',
        available: true
      },
      {
        id: 'fb-jelajah-headlamp',
        name: 'Headlamp 1000lm',
        price: 10000,
        unit: 'jam',
        photo:
          'https://lh3.googleusercontent.com/aida-public/AB6AXuBz1u1wDqC90Eh3U6_pXnlCHtkwu6zUkqAWZpTmbgfQwzwGezbP7KazhrRF29FxMUecZE_DaE_02d-fOz2rm9jIoDozS5bxLiIRb3NGZ-laiu3qXli9jLyQbrb2qlal3PbXyvUIBfAjSaWD_U3YKWkzwScdZvt2FBezdlYsqhO9tDY3KkdXkIAm44DCQyDG162uRq20xPE0MIJ9aZLo9ibg_jzKDcqGAVN0egRwl8QT3na9FykZC27YLQ',
        available: true
      }
    ]
  }
]

const locationOptions = computed(() => [
  { key: 'all', label: 'Semua Basecamp' },
  ...basecamps.value.map(basecamp => ({
    key: String(basecamp.id),
    label: basecamp.name
  }))
])

const locationLabel = computed(() => {
  const option = locationOptions.value.find(
    option => option.key === selectedLocation.value
  )
  return option?.label ?? 'Semua Basecamp'
})

const visibleBasecamps = computed(() => {
  let list = basecamps.value
  if (selectedLocation.value !== 'all') {
    list = list.filter(
      basecamp => String(basecamp.id) === selectedLocation.value
    )
  }
  const q = debouncedQuery.value
  if (q) {
    list = list
      .map(basecamp => ({
        ...basecamp,
        products: basecamp.products.filter(product =>
          product.name.toLowerCase().includes(q)
        )
      }))
      .filter(
        basecamp =>
          basecamp.name.toLowerCase().includes(q) ||
          basecamp.products.length > 0
      )
  }
  return list
})

const emptyText = computed(() => {
  if (debouncedQuery.value) {
    return `Tidak ada hasil untuk "${debouncedQuery.value}". Coba kata kunci lain.`
  }
  return 'Tidak ada alat yang cocok dengan lokasi saat ini.'
})

watch(searchQuery, value => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    debouncedQuery.value = value.trim().toLowerCase()
  }, 250)
})

function formatIDR(value) {
  return `IDR ${new Intl.NumberFormat('id-ID').format(value)}`
}

function mapBasecamps(items) {
  const groups = new Map()
  for (const item of items) {
    const basecamp = item.basecamp ?? {}
    const bcId = String(basecamp.id ?? item.basecamp_id ?? 'unknown')
    if (!groups.has(bcId)) {
      groups.set(bcId, {
        id: bcId,
        name: basecamp.nama_basecamp ?? 'Basecamp',
        verified: false,
        distance: null,
        rating: null,
        total: 0,
        products: []
      })
    }
    const group = groups.get(bcId)
    group.total += 1
    group.products.push({
      id: item.id,
      name: item.nama_produk ?? 'Alat',
      price: Number(item.harga ?? 0),
      unit: item.satuan || 'hari',
      photo: item.gambar || DEFAULT_PHOTO,
      available: (item.stok ?? 1) > 0
    })
  }
  return Array.from(groups.values())
}

function applyFallback() {
  basecamps.value = FALLBACK_BASECAMPS
  useFallback.value = true
  selectedLocation.value = 'all'
}

async function loadBasecamps() {
  isLoading.value = true
  try {
    const { items } = await getRentalProducts()
    if (items.length > 0) {
      basecamps.value = mapBasecamps(items)
      loadError.value = ''
      useFallback.value = false
    } else {
      applyFallback()
    }
  } catch (error) {
    loadError.value = error.message || 'Gagal memuat data'
    applyFallback()
  } finally {
    isLoading.value = false
  }
}

function retry() {
  loadBasecamps()
}

function toggleLocation() {
  locationOpen.value = !locationOpen.value
}

function selectLocation(key) {
  selectedLocation.value = key
  locationOpen.value = false
}

function clearSearch() {
  searchQuery.value = ''
  debouncedQuery.value = ''
  if (searchInput.value) searchInput.value.focus()
}

function resetFilters() {
  selectedLocation.value = 'all'
  clearSearch()
}

function addToCart(product) {
  if (!product.available || addingState.value[product.id]) return
  addingState.value[product.id] = true
  setTimeout(() => {
    cartCount.value += 1
    localStorage.setItem('summit_cart_count', String(cartCount.value))
    addingState.value[product.id] = false
    $q.notify({
      type: 'positive',
      message: `${product.name} ditambahkan ke keranjang`
    })
  }, 350)
}

function onCart() {
  $q.notify({ type: 'info', message: 'Keranjang segera hadir' })
}

function openBasecamp(basecamp) {
  $q.notify({
    type: 'info',
    message: `Detail ${basecamp.name} segera hadir`
  })
}

function onSoon() {
  $q.notify({ type: 'info', message: 'Fitur ini segera hadir' })
}

function goBack() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}

function onDocClick() {
  locationOpen.value = false
}

function onScroll() {
  const y = window.scrollY
  isControlsHidden.value = y > lastScrollY && y > 120
  lastScrollY = y
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  window.addEventListener('scroll', onScroll, { passive: true })
  loadBasecamps()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
  document.removeEventListener('click', onDocClick)
  window.removeEventListener('scroll', onScroll)
})
</script>

<style lang="scss">
.rental {
  min-height: 100vh;
  background: $summit-background;
  color: $summit-text-primary;
  font-family: 'Inter', Roboto, 'Helvetica Neue', sans-serif;
  overscroll-behavior: contain;
}

// Desktop header
.rental__desktop-header {
  display: none;
}

// Mobile header
.rental__header {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 60px;
  padding: 0 16px;
  padding-top: env(safe-area-inset-top);
  background: $summit-surface;
  border-bottom: 1px solid $summit-border;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
}

.rental__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.3;
  color: $primary;
}

.rental__icon-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 9999px;
  background: transparent;
  color: $summit-text-primary;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:hover {
    background: $summit-surface-container-low;
  }

  &:active {
    background: $summit-surface-container;
  }

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: -2px;
  }
}

.rental__cart-badge {
  position: absolute;
  top: 4px;
  right: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border: 2px solid $summit-surface;
  border-radius: 9999px;
  background: $summit-secondary-container;
  color: #ffffff;
  font-size: 10px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  line-height: 1;
}

// Sticky search + location
.rental__controls {
  position: sticky;
  top: 60px;
  z-index: 30;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 16px 4px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid $summit-border;
  transition: transform 0.25s ease;

  &.is-hidden {
    transform: translateY(-100%);
  }
}

.rental__search {
  position: relative;
  display: flex;
  align-items: center;
  height: 44px;
  background: $summit-surface-container-low;
  border: 1px solid $summit-border;
  border-radius: 8px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;

  &:focus-within {
    border-color: $primary;
    box-shadow: 0 0 0 3px rgba(30, 58, 43, 0.12);
  }

  &-icon {
    position: absolute;
    left: 12px;
    color: $summit-text-secondary;
  }

  &-input {
    flex: 1;
    min-width: 0;
    height: 100%;
    padding: 0 40px 0 40px;
    border: none;
    background: transparent;
    font-size: 0.875rem;
    font-family: inherit;
    color: $summit-text-primary;
    outline: none;

    &::placeholder {
      color: $summit-text-secondary;
    }

    &::-webkit-search-cancel-button {
      display: none;
    }
  }

  &-clear {
    position: absolute;
    right: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 9999px;
    background: transparent;
    color: $summit-text-secondary;
    cursor: pointer;

    &:hover {
      background: $summit-surface-container;
      color: $summit-text-primary;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
    }
  }
}

.rental__location {
  position: relative;
}

.rental__location-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  height: 44px;
  padding: 0 12px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 8px;
  color: $summit-text-primary;
  cursor: pointer;
  text-align: left;
  transition: background-color 0.2s ease;

  .q-icon:first-child {
    flex-shrink: 0;
    color: $summit-text-secondary;
    font-size: 18px;
  }

  .q-icon:last-child {
    flex-shrink: 0;
    margin-left: auto;
    color: $summit-text-secondary;
    font-size: 20px;
  }

  &:hover {
    background: $summit-surface-container-low;
  }

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }
}

.rental__location-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rental__location-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 30;
  max-height: 240px;
  overflow-y: auto;
  padding: 4px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);

  button {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    width: 100%;
    min-height: 44px;
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    background: transparent;
    font-size: 0.875rem;
    font-family: inherit;
    color: $summit-text-primary;
    text-align: left;
    cursor: pointer;

    &:hover {
      background: $summit-surface-container-low;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: -2px;
    }

    &.is-selected {
      color: $primary;
      font-weight: 600;
    }
  }
}

.rental__location-option {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// Main content
.rental__main {
  padding: 16px;
  padding-bottom: calc(112px + env(safe-area-inset-bottom));
}

.rental__list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

// Notice banner
.rental__notice {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;
  font-size: 0.75rem;
  color: $summit-on-error-container;
  background: $summit-error-container;
  border-radius: 8px;

  .q-icon {
    flex-shrink: 0;
  }

  button {
    margin-left: auto;
    flex-shrink: 0;
    min-height: 32px;
    padding: 6px 12px;
    border: none;
    border-radius: 8px;
    background: $summit-on-error-container;
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;

    &:focus-visible {
      outline: 2px solid $summit-surface;
      outline-offset: 2px;
    }
  }
}

// Empty state
.rental__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 16px;
  background: $summit-surface;
  border: 1px dashed $summit-border;
  border-radius: 12px;
  text-align: center;

  .q-icon {
    color: $summit-text-secondary;
  }

  &-title {
    margin: 12px 0 4px;
    font-size: 1rem;
    font-weight: 600;
    color: $summit-text-primary;
  }

  &-text {
    margin: 0 0 16px;
    font-size: 0.875rem;
    line-height: 1.5;
    color: $summit-text-secondary;
  }

  &-action {
    min-height: 44px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    background: $primary;
    color: #ffffff;
    font-size: 0.875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background: #183128;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: 2px;
    }
  }
}

// Basecamp card
.basecamp-card {
  overflow: hidden;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;

  &__head {
    padding: 16px;
    border-bottom: 1px solid $summit-border;
  }

  &__name {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0 0 4px;
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.3;
    color: $summit-text-primary;
  }

  &__verified {
    flex-shrink: 0;
    color: $positive;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    font-size: 0.75rem;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: $summit-text-secondary;

    span {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .q-icon {
      color: $summit-text-secondary;
    }
  }

  &__star {
    color: $warning;
  }

  &__products {
    padding: 8px 16px 0 16px;
  }

  &__label {
    margin: 8px 0 12px;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.2;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: $summit-text-secondary;
  }

  &__scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 8px;
    padding-right: 16px;
    margin: 0 -16px;
    padding-left: 16px;
    scroll-snap-type: x mandatory;
    overscroll-behavior-x: contain;
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: -2px;
    }
  }

  &__more {
    display: block;
    width: 100%;
    min-height: 44px;
    padding: 12px;
    border: none;
    border-top: 1px solid $summit-border;
    background: transparent;
    font-size: 0.75rem;
    font-weight: 600;
    color: $primary;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background: $summit-surface-container-low;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: -2px;
    }
  }
}

// Product card
.product-card {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  width: 140px;
  overflow: hidden;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 8px;
  scroll-snap-align: start;
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;

  &.is-unavailable {
    opacity: 0.65;
  }

  &__media {
    height: 100px;
    width: 100%;
    overflow: hidden;
    background: $summit-surface-container-low;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
  }

  &__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 8px;
  }

  &__name {
    margin: 0 0 8px;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.3;
    color: $summit-text-primary;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__status {
    margin-bottom: 8px;
    font-size: 10px;
    font-weight: 500;
    line-height: 1.2;

    &.is-available {
      color: $positive;
    }

    &.is-unavailable {
      color: $summit-text-secondary;
    }
  }

  &__footer {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
  }

  &__price {
    display: flex;
    align-items: baseline;
    gap: 2px;
    min-width: 0;
  }

  &__price-value {
    font-size: 0.625rem;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: $summit-text-secondary;
    white-space: nowrap;
  }

  &__price-unit {
    font-size: 0.625rem;
    font-weight: 500;
    color: $summit-text-secondary;
  }

  &__add {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border: none;
    border-radius: 9999px;
    background: $summit-primary-container;
    color: #ffffff;
    cursor: pointer;
    transition:
      background-color 0.2s ease,
      transform 0.15s ease;

    &:hover {
      background: $primary;
    }

    &:active {
      transform: scale(0.92);
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: 2px;
    }

    &:disabled {
      cursor: default;
      opacity: 0.5;

      &:active {
        transform: none;
      }
    }
  }
}

// Skeletons
.skeleton {
  background: linear-gradient(
    90deg,
    $summit-surface-container 25%,
    $summit-surface-container-high 50%,
    $summit-surface-container 75%
  );
  background-size: 200% 100%;
  animation: rental-shimmer 1.4s ease-in-out infinite;

  &--media {
    height: 100px;
    width: 100%;
  }

  &--line {
    height: 12px;
    margin-bottom: 10px;
    border-radius: 6px;

    &-lg {
      width: 60%;
      height: 20px;
    }

    &-sm {
      width: 40%;
    }

    &-xs {
      width: 45%;
      margin-bottom: 12px;
    }

    &-md {
      width: 80%;
      margin-bottom: 0;
    }
  }
}

@keyframes rental-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

// Reduced motion
@media (prefers-reduced-motion: reduce) {
  .rental * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

// Desktop layout (md+)
@media (min-width: 768px) {
  .rental__header,
  .rental__controls {
    display: none;
  }

  .rental__desktop-header {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 56px;
    padding: 0 16px;
    background: $summit-surface;
    border-bottom: 1px solid $summit-border;

    &-brand {
      display: flex;
      align-items: center;
    }

    &-logo {
      font-size: 20px;
      font-weight: 600;
      color: $primary;
    }

    &-nav {
      display: flex;
      gap: 24px;

      a {
        display: inline-flex;
        align-items: center;
        min-height: 36px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
        color: $summit-text-secondary;
        text-decoration: none;
        transition:
          color 0.2s ease,
          background-color 0.2s ease;

        &.is-active {
          color: $primary;
        }

        &:hover {
          background: $summit-surface-container-low;
        }

        &:focus-visible {
          outline: 2px solid $secondary;
          outline-offset: 2px;
        }
      }
    }

    &-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }
  }

  .rental__main {
    max-width: 56rem;
    margin: 0 auto;
  }

  .rental__list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    align-items: start;
  }
}
</style>
