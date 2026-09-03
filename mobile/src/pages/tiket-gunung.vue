<template>
  <div class="catalog">
    <!-- Desktop header (md+) -->
    <header class="catalog__desktop-header">
      <div class="catalog__desktop-brand">
        <span class="catalog__desktop-logo">SummitPeak</span>
      </div>
      <nav class="catalog__desktop-nav">
        <a class="is-active" aria-current="page" href="#" @click.prevent
          >Explore</a
        >
        <a href="#" @click.prevent="onSoon">Bookings</a>
        <a href="#" @click.prevent="onSoon">Saved</a>
      </nav>
      <div class="catalog__desktop-actions">
        <button
          class="catalog__icon-btn"
          aria-label="Kembali ke beranda"
          @click="goBack"
        >
          <q-icon name="arrow_back" aria-hidden="true" />
        </button>
      </div>
    </header>

    <!-- Mobile header -->
    <header class="catalog__header">
      <button class="catalog__icon-btn" aria-label="Kembali" @click="goBack">
        <q-icon name="arrow_back" aria-hidden="true" />
      </button>
      <h1 class="catalog__title">Tiket Gunung</h1>
      <span
        class="catalog__icon-btn catalog__header-spacer"
        aria-hidden="true"
      ></span>
    </header>

    <!-- Search & filter (sticky) -->
    <div class="catalog__controls" :class="{ 'is-hidden': isControlsHidden }">
      <div class="catalog__search">
        <q-icon class="catalog__search-icon" name="search" aria-hidden="true" />
        <input
          ref="searchInput"
          v-model="searchQuery"
          class="catalog__search-input"
          type="search"
          placeholder="Cari gunung atau lokasi..."
          aria-label="Cari gunung atau lokasi"
        />
        <button
          v-if="searchQuery"
          class="catalog__search-clear"
          aria-label="Hapus pencarian"
          @click="clearSearch"
        >
          <q-icon name="close" aria-hidden="true" />
        </button>
      </div>

      <div class="catalog__pills" role="group" aria-label="Filter katalog">
        <button
          v-for="pill in filterPills"
          :key="pill.key"
          class="catalog__pill"
          :class="{ 'is-active': isPillActive(pill) }"
          :aria-pressed="isPillActive(pill)"
          @click="togglePill(pill)"
        >
          {{ pill.label }}
        </button>
        <button
          class="catalog__tune"
          aria-label="Reset semua filter"
          @click="resetFilters"
        >
          <q-icon name="tune" aria-hidden="true" />
        </button>
      </div>
    </div>

    <!-- Main content -->
    <main class="catalog__main">
      <div class="catalog__stats">
        <span class="catalog__count"
          >Menampilkan {{ visibleCards.length }} Gunung</span
        >
        <button
          class="catalog__sort"
          aria-haspopup="true"
          :aria-expanded="sortOpen"
          @click.stop="sortOpen = !sortOpen"
        >
          Urutkan: {{ sortLabel }}
          <q-icon name="arrow_drop_down" aria-hidden="true" />
        </button>
      </div>

      <ul v-if="sortOpen" class="catalog__sort-menu" role="menu">
        <li v-for="option in sortOptions" :key="option.key">
          <button
            role="menuitem"
            :class="{ 'is-selected': sort === option.key }"
            @click.stop="setSort(option.key)"
          >
            <span>{{ option.label }}</span>
            <q-icon
              v-if="sort === option.key"
              name="check"
              size="16px"
              aria-hidden="true"
            />
          </button>
        </li>
      </ul>

      <!-- Initial loading skeletons -->
      <div v-if="isInitialLoading" class="catalog__grid" aria-hidden="true">
        <div v-for="n in 4" :key="n" class="ticket-card ticket-card--skeleton">
          <div class="skeleton skeleton--media"></div>
          <div class="ticket-card__body">
            <div class="skeleton skeleton--line skeleton--line-lg"></div>
            <div class="skeleton skeleton--line skeleton--line-sm"></div>
            <div class="skeleton skeleton--line skeleton--line-xs"></div>
            <div class="skeleton skeleton--line skeleton--line-md"></div>
          </div>
        </div>
      </div>

      <!-- Error banner with fallback data -->
      <div v-else-if="loadError && useFallback" class="catalog__notice">
        <q-icon name="cloud_off" size="16px" aria-hidden="true" />
        <span>Gagal memuat data terbaru, menampilkan contoh.</span>
        <button @click="retry">Coba lagi</button>
      </div>

      <!-- Empty state -->
      <div v-else-if="visibleCards.length === 0" class="catalog__empty">
        <q-icon name="travel_explore" size="44px" aria-hidden="true" />
        <h2 class="catalog__empty-title">Tiket tidak ditemukan</h2>
        <p class="catalog__empty-text">
          {{ emptyText }}
        </p>
        <button class="catalog__empty-action" @click="resetFilters"
          >Tampilkan semua tiket</button
        >
      </div>

      <!-- Ticket grid -->
      <div v-else class="catalog__grid">
        <article
          v-for="card in visibleCards"
          v-memo="[card.id, card.isSaved, card.closed]"
          :key="card.id"
          class="ticket-card"
          :class="{ 'is-closed': card.closed }"
          @click="openCard(card)"
        >
          <div class="ticket-card__media" :class="{ 'is-closed': card.closed }">
            <img :src="card.photo" :alt="card.name" loading="lazy" />
            <span
              class="ticket-card__badge"
              :class="card.closed ? 'is-error' : 'is-success'"
            >
              {{ card.closed ? 'TUTUP' : 'BUKA' }}
            </span>
            <button
              class="ticket-card__bookmark"
              :class="{ 'is-saved': card.isSaved }"
              :aria-label="
                card.isSaved ? 'Hapus dari tersimpan' : 'Simpan tiket'
              "
              :aria-pressed="card.isSaved"
              @click.stop="toggleSaved(card)"
            >
              <q-icon
                :name="card.isSaved ? 'bookmark' : 'bookmark_border'"
                aria-hidden="true"
              />
            </button>
          </div>
          <div class="ticket-card__body" :class="{ 'is-muted': card.closed }">
            <h2 class="ticket-card__name">{{ card.name }}</h2>
            <p class="ticket-card__meta">
              {{ card.location }} • {{ card.mdpl }} MDPL
            </p>
            <div class="ticket-card__rating">
              <q-icon name="star" size="12px" aria-hidden="true" />
              <span>{{ card.rating }}</span>
              <span class="ticket-card__reviews">({{ card.reviews }})</span>
            </div>
            <div class="ticket-card__footer">
              <span class="ticket-card__slot" :class="slotClass(card)">
                {{ slotText(card) }}
              </span>
              <span
                class="ticket-card__price"
                :class="{ 'is-strikethrough': card.closed }"
                >{{ card.price }}</span
              >
            </div>
          </div>
        </article>
      </div>

      <!-- Loading more -->
      <div
        v-if="isLoadingMore"
        class="catalog__loading-more"
        aria-hidden="true"
      >
        <q-spinner size="20px" color="primary" />
        <span>Memuat tiket lainnya...</span>
      </div>

      <!-- End of list -->
      <p
        v-else-if="
          !isInitialLoading &&
          !hasMore &&
          visibleCards.length > 0 &&
          !isLoadingMore
        "
        class="catalog__end"
      >
        Semua tiket telah ditampilkan
      </p>

      <!-- Infinite scroll sentinel -->
      <div ref="sentinel" class="catalog__sentinel" aria-hidden="true"></div>
    </main>

    <BottomNav active="beranda" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import BottomNav from '@/components/BottomNav.vue'
import { getMountains } from '@/api/mountains'

const $q = useQuasar()
const router = useRouter()

const searchQuery = ref('')
const debouncedQuery = ref('')
const cards = ref([])
const isInitialLoading = ref(true)
const isLoadingMore = ref(false)
const hasMore = ref(false)
const lastPage = ref(1)
const statusFilter = ref('all')
const provinceFilter = ref('all')
const sort = ref('popular')
const sortOpen = ref(false)
const useFallback = ref(false)
const loadError = ref('')
const searchInput = ref(null)
const sentinel = ref(null)

let searchTimer = null
let observer = null
let lastScrollY = 0
let page = 1
const seen = new Set()

const isControlsHidden = ref(false)

const DEFAULT_PHOTO =
  'https://lh3.googleusercontent.com/aida-public/AB6AXuCM5ktBFCTrjcRZgsXJvh_fQ8Bro1VfYRC1_EJP3yn9Ze_9CU8cXaCg2By0CYLODtmYvzighvfi4SmOC1GmhIu1vvdukBp-04LMg3-v5nJ2M3dyBWj6l4OOXjebpVMwfEGU5aJBB9_YcJAHYAD3w6oc7M3tGfVw58jCur70Y2EFdRga9MEQBNA6fHFlyoDJKcxJm92WBx_ZacgB2ahcPZxLa2fy-8qXirD2AFxshcIxGTOYAubMFPBMfQ'

const PRICE_BY_NAME = {
  'Gunung Merbabu': 50000,
  'Gunung Prau': 30000,
  'Gunung Slamet': 65000,
  'Gunung Merapi': 40000,
  'Gunung Rinjani': 150000
}

const FALLBACK_CARDS = [
  {
    id: 'fb-merbabu',
    name: 'Gunung Merbabu (Via Selo)',
    location: 'Boyolali',
    region: 'Boyolali, Jawa Tengah',
    mdpl: '3.142',
    rating: '4.9',
    ratingNumber: 4.9,
    reviews: 128,
    price: 'IDR 50.000',
    priceNumber: 50000,
    slots: 45,
    slotLevel: 'success',
    closed: false,
    isSaved: false,
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuCM5ktBFCTrjcRZgsXJvh_fQ8Bro1VfYRC1_EJP3yn9Ze_9CU8cXaCg2By0CYLODtmYvzighvfi4SmOC1GmhIu1vvdukBp-04LMg3-v5nJ2M3dyBWj6l4OOXjebpVMwfEGU5aJBB9_YcJAHYAD3w6oc7M3tGfVw58jCur70Y2EFdRga9MEQBNA6fHFlyoDJKcxJm92WBx_ZacgB2ahcPZxLa2fy-8qXirD2AFxshcIxGTOYAubMFPBMfQ'
  },
  {
    id: 'fb-prau',
    name: 'Gunung Prau (Via Patakbanteng)',
    location: 'Wonosobo',
    region: 'Wonosobo, Jawa Tengah',
    mdpl: '2.565',
    rating: '4.8',
    ratingNumber: 4.8,
    reviews: 89,
    price: 'IDR 30.000',
    priceNumber: 30000,
    slots: 12,
    slotLevel: 'success',
    closed: false,
    isSaved: false,
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuA7ANVZ4B96nNcaJd0fb8xEgpsPLe5Ki2lmK7xO3yTNbR61HGwvT5l_aQNze2947efYGidUT6HzGoFVEzIvEzqs6-Bwx_vnjyr2pLfMo7OKEmVdB2xwS3BDuV05FYAREPhNubt6mYXgZbtvy1zaY-QJ-rM60x0IRT-oV3XJ-lGlxo2jwAORiSqgOrnTa4q6ego16HcQdHgE97fXGvTSAbvMXgTLq5GMi1JNa9zIJ0DiMT1tvuZdXBODSg'
  },
  {
    id: 'fb-slamet',
    name: 'Gunung Slamet (Via Bambangan)',
    location: 'Purbalingga',
    region: 'Purbalingga, Jawa Tengah',
    mdpl: '3.428',
    rating: '4.7',
    ratingNumber: 4.7,
    reviews: 64,
    price: 'IDR 65.000',
    priceNumber: 65000,
    slots: 8,
    slotLevel: 'warning',
    closed: false,
    isSaved: false,
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuCdVzfEqtiL_qhNcey_whACYLgPkW8eMFeQIF6QdWaaUUYJPcyukYdycjLW0UoKncTixosYs4ggG-zynSpdSjB4WWvF9KXxqVQY3zdrX0Q6rLvqz8dGicy1oY9wqsQKavZWsAiCZlPPbuxEeYVs_QHtWDT7yObtoR1yLqmSIIRMmXJf5C3an9iJvs1soR2ooD_W4HnPnU8fqFxgHJCqQ6qBGZJ2TLBnpOFASzc709Vpl1PaNGLam9j_ZA'
  },
  {
    id: 'fb-merapi',
    name: 'Gunung Merapi (Via Selo)',
    location: 'Boyolali',
    region: 'Boyolali, Jawa Tengah',
    mdpl: '2.930',
    rating: '4.6',
    ratingNumber: 4.6,
    reviews: 210,
    price: 'IDR 40.000',
    priceNumber: 40000,
    slots: 0,
    slotLevel: 'error',
    closed: true,
    isSaved: false,
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuAou8R5AHsBSLohv9krE72VWwsE60axJVPyzeiF-CmFQBf_ox36oyiyVMXDcz_EIiYUVmCmUE9t7-H79zdfyHrUdE4Q47pXmGElX_hC0qVRMwo34kwOSYSK59OHnMbgSaroLM6yIbOiT8gKLYzgNZguxSeeRzDP5U6IyErhpG-9iBTuBpH0S7Z-7RYauSKlRWA764drVClv-lpd_oTDdqTtOAySruC7IhXNWAeqlfBeAWLIXtXU0dnRqA'
  }
]

const filterPills = [
  { key: 'status-all', group: 'status', label: 'Semua Status', value: 'all' },
  { key: 'status-buka', group: 'status', label: 'Jalur Buka', value: 'buka' },
  {
    key: 'province-jateng',
    group: 'province',
    label: 'Jawa Tengah',
    value: 'jawa tengah'
  },
  {
    key: 'province-jatim',
    group: 'province',
    label: 'Jawa Timur',
    value: 'jawa timur'
  }
]

const sortOptions = [
  { key: 'popular', label: 'Populer' },
  { key: 'cheap', label: 'Termurah' },
  { key: 'rating', label: 'Rating' }
]

const sortLabel = computed(
  () =>
    sortOptions.find(option => option.key === sort.value)?.label ?? 'Populer'
)

const visibleCards = computed(() => {
  let list = cards.value
  const q = debouncedQuery.value
  if (q) {
    list = list.filter(card =>
      [card.name, card.location, card.region].some(field =>
        (field ?? '').toLowerCase().includes(q)
      )
    )
  }
  if (statusFilter.value === 'buka') {
    list = list.filter(card => !card.closed)
  }
  if (provinceFilter.value !== 'all') {
    list = list.filter(card =>
      (card.region ?? '').toLowerCase().includes(provinceFilter.value)
    )
  }
  const result = [...list]
  if (sort.value === 'cheap') {
    result.sort((a, b) => a.priceNumber - b.priceNumber)
  } else if (sort.value === 'rating') {
    result.sort((a, b) => b.ratingNumber - a.ratingNumber)
  } else {
    result.sort((a, b) => b.reviews - a.reviews)
  }
  return result
})

const emptyText = computed(() => {
  if (debouncedQuery.value) {
    return `Tidak ada hasil untuk "${debouncedQuery.value}". Coba kata kunci lain.`
  }
  return 'Tidak ada tiket yang cocok dengan filter saat ini.'
})

watch(searchQuery, value => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    debouncedQuery.value = value.trim().toLowerCase()
  }, 250)
})

function isPillActive(pill) {
  const active =
    pill.group === 'status' ? statusFilter.value : provinceFilter.value
  return active === pill.value
}

function togglePill(pill) {
  const target = pill.group === 'status' ? statusFilter : provinceFilter
  target.value = target.value === pill.value ? 'all' : pill.value
}

function resetFilters() {
  statusFilter.value = 'all'
  provinceFilter.value = 'all'
  sort.value = 'popular'
  sortOpen.value = false
  clearSearch(true)
}

function setSort(key) {
  sort.value = key
  sortOpen.value = false
}

function clearSearch(keepFocus = false) {
  searchQuery.value = ''
  debouncedQuery.value = ''
  if (keepFocus && searchInput.value) searchInput.value.focus()
}

function toggleSaved(card) {
  card.isSaved = !card.isSaved
}

function slotClass(card) {
  return `is-${card.slotLevel}`
}

function slotText(card) {
  if (card.closed) return 'Cuaca Badai'
  return `Sisa ${card.slots} Slot`
}

function openCard(card) {
  $q.notify({ type: 'info', message: `Detail ${card.name} segera hadir` })
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

function formatNumber(value) {
  return new Intl.NumberFormat('id-ID').format(value)
}

function formatIDR(value) {
  return `IDR ${formatNumber(value)}`
}

function mapCards(items) {
  return items.map(item => {
    const firstJalur = item.jalurs?.[0]
    const rawLocation = item.lokasi ?? ''
    const province = rawLocation.split(',')[1]?.trim() ?? ''
    const trailName = (firstJalur?.nama_jalur ?? '').replace(/^Jalur\s+/i, '')
    const baseName = item.nama_gunung ?? 'Gunung'
    const closed = firstJalur?.status === 'close'
    const slots = closed ? 0 : 12 + ((item.id * 13) % 44)
    return {
      id: item.id,
      name: trailName ? `${baseName} (Via ${trailName})` : baseName,
      location: rawLocation.split(',')[0] || 'Indonesia',
      region: rawLocation,
      mdpl: formatNumber(item.tinggi_mdpl ?? 0),
      rating: (4.4 + (item.id % 6) * 0.1).toFixed(1),
      ratingNumber: 4.4 + (item.id % 6) * 0.1,
      reviews: 40 + ((item.id * 7) % 200),
      price: formatIDR(PRICE_BY_NAME[item.nama_gunung] ?? 50000),
      priceNumber: PRICE_BY_NAME[item.nama_gunung] ?? 50000,
      slots,
      slotLevel: closed ? 'error' : slots < 10 ? 'warning' : 'success',
      closed,
      isSaved: false,
      province,
      photo: item.foto || DEFAULT_PHOTO
    }
  })
}

function applyFallback() {
  cards.value = FALLBACK_CARDS
  seen.clear()
  FALLBACK_CARDS.forEach(card => seen.add(card.id))
  hasMore.value = false
  useFallback.value = true
}

async function loadPage(next = false) {
  if (next) {
    isLoadingMore.value = true
  } else {
    isInitialLoading.value = true
  }

  try {
    const { items, meta } = await getMountains(page)
    lastPage.value = meta.last_page ?? 1
    hasMore.value = page < lastPage.value

    if (items.length > 0) {
      const mapped = mapCards(items)
      if (next) {
        const fresh = mapped.filter(card => !seen.has(card.id))
        fresh.forEach(card => seen.add(card.id))
        cards.value = [...cards.value, ...fresh]
      } else {
        cards.value = mapped
        seen.clear()
        mapped.forEach(card => seen.add(card.id))
        useFallback.value = false
      }
      loadError.value = ''
    } else if (!next) {
      applyFallback()
    }
  } catch (error) {
    loadError.value = error.message || 'Gagal memuat data'
    if (!next) {
      applyFallback()
    } else {
      hasMore.value = false
    }
  } finally {
    isInitialLoading.value = false
    isLoadingMore.value = false
  }
}

function hasActiveFilter() {
  return (
    debouncedQuery.value ||
    statusFilter.value !== 'all' ||
    provinceFilter.value !== 'all'
  )
}

function setupObserver() {
  if (!('IntersectionObserver' in window) || !sentinel.value) return
  observer = new IntersectionObserver(
    entries => {
      const entry = entries[0]
      if (
        entry.isIntersecting &&
        hasMore.value &&
        !isLoadingMore.value &&
        !isInitialLoading.value &&
        !hasActiveFilter()
      ) {
        page += 1
        loadPage(true)
      }
    },
    { rootMargin: '200px' }
  )
  observer.observe(sentinel.value)
}

function onDocClick() {
  sortOpen.value = false
}

function onScroll() {
  const y = window.scrollY
  isControlsHidden.value = y > lastScrollY && y > 120
  lastScrollY = y
}

function retry() {
  page = 1
  loadPage(false)
}

onMounted(() => {
  document.addEventListener('click', onDocClick)
  window.addEventListener('scroll', onScroll, { passive: true })
  loadPage(false)
  setupObserver()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
  document.removeEventListener('click', onDocClick)
  window.removeEventListener('scroll', onScroll)
  if (observer) observer.disconnect()
})
</script>

<style lang="scss">
.catalog {
  min-height: 100vh;
  background: $summit-background;
  color: $summit-text-primary;
  font-family: 'Inter', Roboto, 'Helvetica Neue', sans-serif;
  overscroll-behavior: contain;
}

// Desktop header
.catalog__desktop-header {
  display: none;
}

// Mobile header
.catalog__header {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 16px;
  padding-top: env(safe-area-inset-top);
  background: $summit-surface;
  border-bottom: 1px solid $summit-border;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.catalog__title {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.01em;
  line-height: 1.2;
  color: $primary;
}

.catalog__icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: none;
  border-radius: 9999px;
  background: transparent;
  color: $primary;
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

.catalog__header-spacer {
  flex-shrink: 0;
  cursor: default;
  pointer-events: none;
}

// Sticky search + filters
.catalog__controls {
  position: sticky;
  top: calc(64px + env(safe-area-inset-top));
  z-index: 30;
  padding: 8px 16px 4px;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid $summit-border;
  transition: transform 0.25s ease;

  &.is-hidden {
    transform: translateY(-100%);
  }
}

.catalog__search {
  position: relative;
  display: flex;
  align-items: center;
  height: 40px;
  border: 1px solid $summit-border;
  border-radius: 8px;
  background: $summit-surface;
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
    padding: 0 40px 0 36px;
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
      background: $summit-surface-container-low;
      color: $summit-text-primary;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
    }
  }
}

.catalog__pills {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding: 8px 0 4px;
  scrollbar-width: none;

  &::-webkit-scrollbar {
    display: none;
  }
}

.catalog__pill {
  flex-shrink: 0;
  min-height: 40px;
  padding: 8px 12px;
  border: 1px solid $summit-border;
  border-radius: 9999px;
  background: $summit-surface;
  font-size: 0.75rem;
  font-weight: 500;
  color: $summit-text-secondary;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease;

  &:hover {
    background: $summit-surface-container-low;
  }

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }

  &.is-active {
    background: $primary;
    border-color: $primary;
    color: #ffffff;
    font-weight: 600;
  }
}

.catalog__tune {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 40px;
  margin-left: auto;
  border: 1px solid $summit-border;
  border-radius: 9999px;
  background: $summit-surface;
  color: $primary;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:hover {
    background: $summit-surface-container-low;
  }

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }
}

// Main content
.catalog__main {
  position: relative;
  padding: 8px 16px calc(112px + env(safe-area-inset-bottom));
}

.catalog__stats {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 8px 0;
}

.catalog__count {
  font-size: 0.75rem;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  color: $summit-text-secondary;
}

.catalog__sort {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  min-height: 32px;
  border: none;
  border-radius: 4px;
  background: transparent;
  font-size: 0.75rem;
  font-weight: 500;
  color: $primary;
  cursor: pointer;

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }
}

.catalog__sort-menu {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  z-index: 30;
  min-width: 160px;
  margin: 0;
  padding: 4px;
  list-style: none;
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

// Grid
.catalog__grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

// Ticket card
.ticket-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  cursor: pointer;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease,
    opacity 0.2s ease;

  &:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.09);
  }

  &:active {
    transform: scale(0.98);
  }

  &:focus-within {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }

  &.is-closed {
    opacity: 0.75;
  }

  &__media {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: $summit-surface-container-highest;

    &.is-closed img {
      filter: grayscale(30%);
    }

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }
  }

  &:hover &__media img {
    transform: scale(1.04);
  }

  &__badge {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 1;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: #ffffff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);

    &.is-success {
      background: $positive;
    }

    &.is-error {
      background: $negative;
    }
  }

  &__bookmark {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.85);
    color: $primary;
    cursor: pointer;
    transition:
      background-color 0.2s ease,
      color 0.2s ease,
      transform 0.2s ease;

    &:active {
      transform: scale(0.92);
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: 2px;
    }

    &.is-saved {
      color: $secondary;
      background: #ffffff;
    }
  }

  &__body {
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 2px;
    padding: 12px;

    &.is-muted {
      background: $summit-surface-container-lowest;
    }
  }

  &__name {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.25;
    color: $summit-text-primary;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__meta {
    margin: 0;
    font-size: 0.6875rem;
    color: $summit-text-secondary;
  }

  &__rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: $summit-text-primary;

    .q-icon {
      color: $warning;
    }
  }

  &__reviews {
    font-size: 0.625rem;
    font-weight: 400;
    color: $summit-text-secondary;
  }

  &__footer {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: auto;
    padding-top: 8px;
  }

  &__slot {
    font-size: 0.6875rem;
    font-weight: 600;

    &.is-success {
      color: $positive;
    }

    &.is-warning {
      color: $warning;
    }

    &.is-error {
      color: $negative;
    }
  }

  &__price {
    font-size: 0.8125rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: $primary;

    &.is-strikethrough {
      color: $summit-text-secondary;
      text-decoration: line-through;
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
  animation: catalog-shimmer 1.4s ease-in-out infinite;

  &--media {
    aspect-ratio: 4 / 3;
  }

  &--line {
    height: 12px;
    margin-bottom: 10px;
    border-radius: 6px;

    &-lg {
      width: 70%;
      height: 16px;
    }

    &-sm {
      width: 50%;
    }

    &-xs {
      width: 40%;
      margin-top: 6px;
    }

    &-md {
      width: 60%;
      margin-bottom: 0;
    }
  }
}

@keyframes catalog-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

// Loading more / end of list
.catalog__loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  font-size: 0.75rem;
  color: $summit-text-secondary;
}

.catalog__end {
  margin: 0;
  padding: 12px;
  font-size: 0.75rem;
  text-align: center;
  color: $summit-text-secondary;
}

.catalog__sentinel {
  height: 1px;
}

// Notice banner
.catalog__notice {
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
.catalog__empty {
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

// Reduced motion
@media (prefers-reduced-motion: reduce) {
  .catalog * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

// Desktop layout (md+)
@media (min-width: 768px) {
  .catalog__header {
    display: none;
  }

  .catalog__controls {
    top: 56px;
    max-width: 56rem;
    margin: 0 auto;
    border-radius: 0 0 12px 12px;
    border-left: 1px solid $summit-border;
    border-right: 1px solid $summit-border;
  }

  .catalog__main {
    max-width: 56rem;
    margin: 0 auto;
  }

  .catalog__grid {
    grid-template-columns: repeat(3, 1fr);
  }

  .catalog__desktop-header {
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
}
</style>
