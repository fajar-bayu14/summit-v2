<template>
  <div class="summit-home">
    <!-- Desktop header (md+) -->
    <header class="summit-header-desktop">
      <div class="summit-header-desktop__brand">
        <span class="summit-header-desktop__logo">SummitPeak</span>
      </div>
      <nav class="summit-header-desktop__nav">
        <a class="is-active" aria-current="page" href="#">Explore</a>
        <a href="#" @click.prevent="onSoon">Bookings</a>
        <a href="#" @click.prevent="onSoon">Saved</a>
      </nav>
      <div class="summit-header-desktop__actions">
        <button
          class="summit-icon-btn"
          aria-label="Cari gunung"
          @click="onSearchFocus"
        >
          <q-icon name="search" />
        </button>
        <img
          class="summit-header-desktop__avatar"
          :src="avatar"
          alt="Foto profil"
        />
      </div>
    </header>

    <!-- Mobile header -->
    <header class="summit-header-mobile">
      <div class="summit-header-mobile__identity">
        <img
          class="summit-header-mobile__avatar"
          :src="avatar"
          alt="Foto profil"
        />
        <div>
          <h1 class="summit-header-mobile__greeting">Halo, {{ firstName }}!</h1>
          <p class="summit-header-mobile__subtitle">Siap mendaki hari ini?</p>
        </div>
      </div>
      <div class="summit-header-mobile__actions">
        <button
          class="summit-icon-btn"
          aria-label="Gunung tersimpan"
          @click="onSoon"
        >
          <q-icon name="bookmark" />
        </button>
        <button
          class="summit-icon-btn summit-icon-btn--notif"
          aria-label="Notifikasi"
          @click="onSoon"
        >
          <q-icon name="notifications" />
          <span class="summit-notif-dot" aria-hidden="true"></span>
        </button>
      </div>
    </header>

    <main class="summit-main">
      <!-- Search bar -->
      <section class="search-bar" aria-label="Pencarian">
        <q-icon class="search-bar__icon" name="search" aria-hidden="true" />
        <input
          ref="searchInput"
          v-model="searchQuery"
          class="search-bar__input"
          type="search"
          placeholder="Cari gunung, jalur, atau lokasi..."
          aria-label="Cari gunung, jalur, atau lokasi"
        />
        <button
          v-if="searchQuery"
          class="search-bar__clear"
          aria-label="Hapus pencarian"
          @click="clearSearch"
        >
          <q-icon name="close" />
        </button>
        <button
          class="search-bar__filter"
          aria-label="Filter pencarian"
          @click="onSoon"
        >
          <q-icon name="tune" />
        </button>
      </section>

      <!-- Promo banner -->
      <section class="promo-banner" aria-label="Promo">
        <div class="promo-banner__glow" aria-hidden="true"></div>
        <svg
          class="promo-banner__peaks"
          viewBox="0 0 120 60"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path
            d="M0 60 L18 24 L32 44 L52 10 L74 46 L92 26 L120 60 Z"
            fill="rgba(255,255,255,0.08)"
          />
        </svg>
        <span class="promo-banner__pill">Promo</span>
        <h3 class="promo-banner__title">Tiket Merbabu</h3>
        <p class="promo-banner__subtitle"
          >Diskon 20% Paket Bundling Sewa Alat</p
        >
        <div class="promo-banner__footer">
          <div class="promo-banner__dots" aria-hidden="true">
            <span class="is-active"></span>
            <span></span>
            <span></span>
          </div>
          <button class="promo-banner__cta" @click="onSoon">Lihat Promo</button>
        </div>
      </section>

      <!-- Quick services -->
      <section class="quick-services">
        <h2 class="section-title">Layanan Cepat</h2>
        <div class="quick-services__grid">
          <button
            v-for="service in quickServices"
            :key="service.label"
            class="quick-service"
            :aria-label="service.label"
            @click="onService(service)"
          >
            <span class="quick-service__icon">
              <q-icon :name="service.icon" aria-hidden="true" />
            </span>
            <span class="quick-service__label">{{ service.label }}</span>
          </button>
        </div>
      </section>

      <!-- Live status -->
      <section class="live-status">
        <div class="section-row">
          <h2 class="section-title">Status Terkini</h2>
          <a class="section-link" href="#" @click.prevent="onSoon">
            Lihat Semua
            <q-icon name="chevron_right" size="14px" aria-hidden="true" />
          </a>
        </div>
        <div class="live-status__scroll">
          <div
            v-for="status in liveStatuses"
            :key="`${status.title}-${status.route}`"
            class="status-card"
          >
            <div class="status-card__icon" :class="`is-${status.type}`">
              <q-icon :name="status.icon" aria-hidden="true" />
            </div>
            <div class="status-card__body">
              <h3 class="status-card__title">{{ status.title }}</h3>
              <p class="status-card__route">
                <span
                  v-if="status.type === 'success'"
                  class="status-card__live"
                  aria-hidden="true"
                ></span>
                {{ status.route }}
              </p>
              <div class="status-card__pill" :class="`is-${status.type}`">
                <q-icon
                  :name="status.pillIcon"
                  size="12px"
                  aria-hidden="true"
                />
                <span>{{ status.pillText }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Popular mountains -->
      <section class="popular-mountains">
        <div class="section-row">
          <h2 class="section-title">Gunung Populer</h2>
          <span v-if="debouncedQuery" class="section-count"
            >{{ filteredMountains.length }} hasil</span
          >
        </div>

        <!-- Loading skeletons -->
        <div
          v-if="isLoading"
          class="popular-mountains__list"
          aria-hidden="true"
        >
          <div
            v-for="n in 2"
            :key="n"
            class="mountain-card mountain-card--skeleton"
          >
            <div class="skeleton skeleton--media"></div>
            <div class="mountain-card__body">
              <div class="skeleton skeleton--line skeleton--line-lg"></div>
              <div class="skeleton skeleton--line skeleton--line-sm"></div>
              <div class="skeleton skeleton--line skeleton--line-md"></div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredMountains.length === 0" class="empty-state">
          <q-icon name="travel_explore" size="44px" aria-hidden="true" />
          <h3 class="empty-state__title">Gunung tidak ditemukan</h3>
          <p class="empty-state__text">
            Tidak ada hasil untuk "{{ debouncedQuery }}". Coba kata kunci lain.
          </p>
          <button class="empty-state__action" @click="clearSearch"
            >Tampilkan semua gunung</button
          >
        </div>

        <!-- Mountain list -->
        <div v-else class="popular-mountains__list">
          <article
            v-for="mountain in filteredMountains"
            v-memo="[mountain.id, mountain.price, mountain.quota]"
            :key="mountain.id"
            class="mountain-card"
            @click="onOpenMountain(mountain)"
          >
            <div class="mountain-card__media">
              <img :src="mountain.photo" :alt="mountain.name" loading="lazy" />
              <span class="mountain-card__rating">
                <q-icon name="star" size="14px" aria-hidden="true" />
                {{ mountain.rating }}
              </span>
              <span class="mountain-card__location">{{
                mountain.location
              }}</span>
            </div>
            <div class="mountain-card__body">
              <div class="mountain-card__heading">
                <h3 class="mountain-card__name">{{ mountain.name }}</h3>
                <span class="mountain-card__mdpl"
                  >{{ mountain.mdpl }} MDPL</span
                >
              </div>
              <p class="mountain-card__trail">
                <q-icon name="location_on" size="14px" aria-hidden="true" />
                {{ mountain.trail }}
              </p>
              <div class="mountain-card__footer">
                <div>
                  <span class="mountain-card__label">Mulai dari</span>
                  <span class="mountain-card__price">{{ mountain.price }}</span>
                </div>
                <div class="mountain-card__quota">
                  <span class="mountain-card__label">Sisa Kuota</span>
                  <span class="mountain-card__quota-value">{{
                    mountain.quota
                  }}</span>
                </div>
              </div>
            </div>
          </article>
        </div>
      </section>
    </main>

    <!-- Bottom navigation -->
    <BottomNav active="beranda" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import BottomNav from '@/components/BottomNav.vue'
import { getMountains } from '@/api/mountains'
import { getProfile } from '@/api/profile'
import { getToken } from '@/api/http'
import summitLogo from '@/assets/summit-logo.jpg'

const $q = useQuasar()
const router = useRouter()

const searchQuery = ref('')
const debouncedQuery = ref('')
const profileName = ref('')
const mountains = ref([])
const isLoading = ref(true)
const searchInput = ref(null)
let searchTimer = null

const DEFAULT_MOUNTAINS = [
  {
    id: 'rinjani',
    name: 'Gunung Rinjani',
    mdpl: '3.726',
    location: 'Lombok',
    trail: 'Jalur Sembalun',
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuB5u0D_cSCnpFohnbVaOi4dMg8Q56SVsAMZW60aBc3sSdg3xtTnLJlqgkMBquKeBYU9AsoWaRu8vb-FOPmiAtR-WaFbG25i3wNUdFfTdzWeHbxJhziickUyt2V5kLVDxgU4GJfVFKKbCzc5e80HBZ_dJWxpZsL0fe62zXaS8zw55D6DE6KhESjxhh85ypkXGmxQzOtXCI3vCcxiLnyvGbHqnDk3nMTaHO9_u7_-7NC-sUi2afgw1_76mw',
    rating: '4.9',
    price: 'IDR 150.000',
    quota: '120 Orang'
  },
  {
    id: 'prau',
    name: 'Gunung Prau',
    mdpl: '2.565',
    location: 'Dieng',
    trail: 'Jalur Patak Banteng',
    photo:
      'https://lh3.googleusercontent.com/aida-public/AB6AXuBJ_bOJLRMhFFcuP98wYobAKKKx0F6e7oY7Eb_a2bT7IbjFIWgNx45qNhtROFzoyoNBYMnlGNFVWj3LARKoWrBAPD_lVC4DsajCi3Lo4O2SwRKUU61OBbC-1dUGq8qOlAoqTugKLK_nl5aFLU-Okf-eJjgEe2E0IXPJaPqjkaUkk2ezqi30-o5kms1UBYAaV5t4TYr1rnrZxOwBKH7MSYu2l-WFFkBDTW7lYxwPywyPW9T30e6JYDSVug',
    rating: '4.7',
    price: 'IDR 30.000',
    quota: '—'
  }
]

const PRICE_BY_NAME = { 'Gunung Rinjani': 150000, 'Gunung Prau': 30000 }

const quickServices = [
  { icon: 'landscape', label: 'Tiket Gunung' },
  { icon: 'backpack', label: 'Sewa Alat' },
  { icon: 'hiking', label: 'Porter & Guide' },
  { icon: 'groups', label: 'Open Trip' }
]

const liveStatuses = computed(() => {
  const fromApi = mountains.value
    .flatMap(mountain =>
      (mountain.jalurs ?? []).map(jalur => ({
        title: mountain.name,
        route: `${jalur.nama_jalur} - ${jalur.status === 'open' ? 'Buka' : 'Tutup'}`,
        type: jalur.status === 'open' ? 'success' : 'error',
        icon: jalur.status === 'open' ? 'check_circle' : 'cancel',
        pillText:
          jalur.status === 'open'
            ? `${slotText()} Slot Tersedia`
            : 'Cuaca Badai',
        pillIcon: jalur.status === 'open' ? 'group' : 'thunderstorm'
      }))
    )
    .slice(0, 4)

  if (fromApi.length > 0) return fromApi

  return [
    {
      title: 'Gn. Merbabu',
      route: 'Jalur Selo - Buka',
      type: 'success',
      icon: 'check_circle',
      pillText: '45 Slot Tersedia',
      pillIcon: 'group'
    },
    {
      title: 'Gn. Merapi',
      route: 'Jalur Selo - Tutup',
      type: 'error',
      icon: 'cancel',
      pillText: 'Cuaca Badai',
      pillIcon: 'thunderstorm'
    }
  ]
})

const avatar = summitLogo

const firstName = computed(() => {
  const name = profileName.value.trim()
  return name ? name.split(/\s+/)[0] : 'Alex'
})

const filteredMountains = computed(() => {
  const q = debouncedQuery.value
  if (!q) return mountains.value
  return mountains.value.filter(m =>
    [m.name, m.trail, m.location].some(field => field.toLowerCase().includes(q))
  )
})

watch(searchQuery, value => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    debouncedQuery.value = value.trim().toLowerCase()
  }, 250)
})

function slotText() {
  return '45'
}

function formatNumber(value) {
  return new Intl.NumberFormat('id-ID').format(value)
}

function formatIDR(value) {
  return `IDR ${formatNumber(value)}`
}

function mapMountains(items) {
  return items.map(item => {
    const firstJalur = item.jalurs?.[0]
    const rawLocation = item.lokasi ?? ''
    return {
      id: item.id,
      name: item.nama_gunung ?? 'Gunung',
      mdpl: new Intl.NumberFormat('id-ID').format(item.tinggi_mdpl ?? 0),
      location: rawLocation.split(',')[0] || 'Indonesia',
      trail: firstJalur?.nama_jalur ?? 'Jalur utama',
      photo: item.foto || DEFAULT_MOUNTAINS[0].photo,
      rating: '4.8',
      price: formatIDR(PRICE_BY_NAME[item.nama_gunung] ?? 150000),
      quota: '120 Orang',
      jalurs: item.jalurs
    }
  })
}

async function loadProfile() {
  try {
    const data = await getProfile()
    profileName.value = data.name || ''
  } catch {
    profileName.value = ''
  }
}

async function loadMountains() {
  isLoading.value = true
  try {
    const { items } = await getMountains()
    mountains.value = items.length > 0 ? mapMountains(items) : DEFAULT_MOUNTAINS
  } catch {
    mountains.value = DEFAULT_MOUNTAINS
  } finally {
    isLoading.value = false
  }
}

function clearSearch() {
  searchQuery.value = ''
  debouncedQuery.value = ''
  if (searchInput.value) searchInput.value.focus()
}

function onSearchFocus() {
  if (searchInput.value) searchInput.value.focus()
}

function onOpenMountain(mountain) {
  $q.notify({ type: 'info', message: `Detail ${mountain.name} segera hadir` })
}

function onService(service) {
  if (service.label === 'Tiket Gunung') {
    router.push('/tiket-gunung')
    return
  }
  if (service.label === 'Sewa Alat') {
    router.push('/sewa-alat')
    return
  }
  $q.notify({ type: 'info', message: 'Fitur ini segera hadir' })
}

function onSoon() {
  $q.notify({ type: 'info', message: 'Fitur ini segera hadir' })
}

onMounted(() => {
  if (getToken()) {
    loadProfile()
  }
  loadMountains()
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
})
</script>

<style lang="scss">
.summit-home {
  min-height: 100vh;
  background: $summit-background;
  color: $summit-text-primary;
  font-family: 'Inter', Roboto, 'Helvetica Neue', sans-serif;
  overscroll-behavior: contain;
}

// Shared
.section-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  line-height: 1.3;
  color: $summit-text-primary;
}

.section-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.section-count {
  font-size: 0.75rem;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  color: $summit-text-secondary;
}

.section-link {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  min-height: 32px;
  font-size: 0.75rem;
  font-weight: 500;
  color: $primary;
  text-decoration: none;
  border-radius: 4px;

  &:focus-visible {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }
}

.summit-icon-btn {
  position: relative;
  display: inline-flex;
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

.summit-notif-dot {
  position: absolute;
  top: 11px;
  right: 11px;
  width: 9px;
  height: 9px;
  border: 2px solid $summit-surface;
  border-radius: 9999px;
  background: $negative;
}

// Desktop header
.summit-header-desktop {
  display: none;
}

// Mobile header
.summit-header-mobile {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 16px 8px;
  padding-top: calc(16px + env(safe-area-inset-top));
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid $summit-border;

  &__identity {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  &__avatar {
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    object-fit: cover;
    border: 2px solid $summit-primary-fixed;
  }

  &__greeting {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.3;
    color: $summit-text-primary;
  }

  &__subtitle {
    margin: 0;
    font-size: 0.75rem;
    color: $summit-text-secondary;
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: 8px;
  }
}

// Main content
.summit-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 100%;
  max-width: 56rem;
  margin: 0 auto;
  padding: 16px;
  padding-bottom: calc(112px + env(safe-area-inset-bottom));
}

// Search bar
.search-bar {
  display: flex;
  align-items: center;
  height: 48px;
  padding: 0 12px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 9999px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;

  &:focus-within {
    border-color: $primary;
    box-shadow: 0 0 0 3px rgba(30, 58, 43, 0.12);
  }

  &__icon {
    margin-right: 8px;
    color: $summit-text-secondary;
  }

  &__input {
    flex: 1;
    min-width: 0;
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

  &__clear,
  &__filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    color: $summit-text-secondary;
    cursor: pointer;
    border-radius: 9999px;
    transition:
      background-color 0.2s ease,
      color 0.2s ease;

    &:hover {
      background: $summit-surface-container-low;
      color: $summit-text-primary;
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: -2px;
    }
  }

  &__filter {
    border-left: 1px solid $summit-border;
    color: $primary;
  }
}

// Promo banner
.promo-banner {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 148px;
  padding: 16px;
  overflow: hidden;
  background: linear-gradient(
    135deg,
    #14301f 0%,
    $summit-primary-container 45%,
    #2f5a41 100%
  );
  border: 1px solid rgba(202, 234, 212, 0.35);
  border-radius: 16px;

  &__glow {
    position: absolute;
    top: -48px;
    right: -40px;
    width: 160px;
    height: 160px;
    border-radius: 9999px;
    background: radial-gradient(
      circle,
      rgba(252, 96, 24, 0.28),
      transparent 70%
    );
    pointer-events: none;
  }

  &__peaks {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 140px;
    height: 60px;
    pointer-events: none;
  }

  &__pill {
    position: relative;
    z-index: 1;
    align-self: flex-start;
    margin-bottom: 10px;
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #ffffff;
    background: $secondary;
    border-radius: 4px;
  }

  &__title {
    position: relative;
    z-index: 1;
    margin: 0 0 4px;
    max-width: 70%;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.25;
    color: #ffffff;
  }

  &__subtitle {
    position: relative;
    z-index: 1;
    margin: 0;
    max-width: 70%;
    font-size: 0.75rem;
    font-weight: 500;
    color: $summit-primary-fixed;
  }

  &__footer {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
  }

  &__dots {
    display: flex;
    align-items: center;
    gap: 4px;

    span {
      width: 4px;
      height: 4px;
      border-radius: 9999px;
      background: #ffffff;
      opacity: 0.4;
      transition: width 0.2s ease;

      &.is-active {
        width: 16px;
        opacity: 1;
      }
    }
  }

  &__cta {
    min-height: 32px;
    padding: 6px 12px;
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background: rgba(255, 255, 255, 0.22);
    }

    &:active {
      background: rgba(255, 255, 255, 0.3);
    }

    &:focus-visible {
      outline: 2px solid #ffffff;
      outline-offset: 2px;
    }
  }
}

// Quick services
.quick-services {
  &__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
  }
}

.quick-service {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  min-height: 84px;
  padding: 8px 4px;
  border: none;
  border-radius: 12px;
  background: transparent;
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
    outline-offset: 2px;
  }

  &__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border: 1px solid $summit-border;
    border-radius: 12px;
    background: $summit-surface-container;
    color: $primary;
    transition:
      background-color 0.2s ease,
      color 0.2s ease,
      transform 0.2s ease;
  }

  &:hover &__icon,
  &:active &__icon {
    background: $summit-primary-fixed;
    color: $summit-primary-container;
  }

  &:active &__icon {
    transform: scale(0.94);
  }

  &__label {
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.2;
    text-align: center;
    color: $summit-text-primary;
  }
}

// Live status
.live-status {
  &__scroll {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    margin: 0 -16px;
    padding: 0 16px 8px;
    overscroll-behavior-x: contain;
    scrollbar-width: none;

    &::-webkit-scrollbar {
      display: none;
    }
  }
}

.status-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex-shrink: 0;
  min-width: 240px;
  padding: 12px;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
  }

  &__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    flex-shrink: 0;

    &.is-success {
      color: $positive;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.2);
    }

    &.is-error {
      color: $negative;
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
    }
  }

  &__title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.25;
    color: $summit-text-primary;
  }

  &__route {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 4px 0 8px;
    font-size: 0.75rem;
    font-weight: 500;
    color: $summit-text-secondary;
  }

  &__live {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: $positive;
    animation: summit-pulse 2s ease-in-out infinite;
  }

  &__pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    width: max-content;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 500;

    &.is-success {
      color: $summit-text-secondary;
      background: $summit-surface-container-low;
    }

    &.is-error {
      color: $summit-on-error-container;
      background: $summit-error-container;
    }
  }
}

@keyframes summit-pulse {
  0%,
  100% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
  }
  50% {
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0);
  }
}

// Popular mountains
.mountain-card {
  overflow: hidden;
  background: $summit-surface;
  border: 1px solid $summit-border;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  cursor: pointer;
  transition:
    box-shadow 0.2s ease,
    transform 0.2s ease;

  &:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.09);
  }

  &:active {
    transform: scale(0.99);
  }

  &:focus-within {
    outline: 2px solid $secondary;
    outline-offset: 2px;
  }

  &__media {
    position: relative;
    height: 160px;
    overflow: hidden;

    &::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to top,
        rgba(7, 36, 23, 0.45),
        transparent 45%
      );
      pointer-events: none;
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

  &__rating {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(229, 231, 235, 0.5);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: $summit-text-primary;

    .q-icon {
      color: $warning;
    }
  }

  &__location {
    position: absolute;
    bottom: 10px;
    left: 10px;
    z-index: 1;
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-transform: uppercase;
    color: #ffffff;
    background: rgba(30, 58, 43, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
  }

  &__body {
    padding: 12px;
  }

  &__heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
  }

  &__name {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.3;
    color: $summit-text-primary;
  }

  &__mdpl {
    flex-shrink: 0;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: $summit-text-secondary;
    border: 1px solid $summit-border;
    border-radius: 6px;
  }

  &__trail {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0 0 12px;
    font-size: 0.75rem;
    font-weight: 500;
    color: $summit-text-secondary;

    .q-icon {
      color: $summit-text-secondary;
    }
  }

  &__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 12px;
    border-top: 1px solid $summit-border;
  }

  &__label {
    display: block;
    font-size: 10px;
    color: $summit-text-secondary;
  }

  &__price {
    font-size: 0.875rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: $primary;
  }

  &__quota {
    text-align: right;

    &-value {
      font-size: 0.875rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      color: $positive;
    }
  }
}

.popular-mountains__list {
  display: flex;
  flex-direction: column;
  gap: 16px;
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
  animation: summit-shimmer 1.4s ease-in-out infinite;

  &--media {
    height: 160px;
  }

  &--line {
    height: 14px;
    margin-bottom: 10px;
    border-radius: 6px;

    &-lg {
      width: 60%;
      height: 20px;
    }

    &-sm {
      width: 40%;
    }

    &-md {
      width: 80%;
      margin-bottom: 0;
    }
  }
}

@keyframes summit-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

// Empty state
.empty-state {
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

  &__title {
    margin: 12px 0 4px;
    font-size: 1rem;
    font-weight: 600;
    color: $summit-text-primary;
  }

  &__text {
    margin: 0 0 16px;
    font-size: 0.875rem;
    line-height: 1.5;
    color: $summit-text-secondary;
  }

  &__action {
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
      background: darken($primary, 6%);
    }

    &:active {
      background: darken($primary, 10%);
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: 2px;
    }
  }
}

// Reduced motion
@media (prefers-reduced-motion: reduce) {
  .summit-home * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

// Desktop layout (md+)
@media (min-width: 768px) {
  .summit-header-mobile,
  .bottom-nav {
    display: none;
  }

  .summit-header-desktop {
    position: fixed;
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

    &__brand {
      display: flex;
      align-items: center;
    }

    &__logo {
      font-size: 20px;
      font-weight: 600;
      color: $primary;
    }

    &__nav {
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

    &__actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    &__avatar {
      width: 32px;
      height: 32px;
      border-radius: 9999px;
      object-fit: cover;
      border: 1px solid $summit-border;
    }
  }

  .summit-main {
    padding-top: 72px;
  }
}
</style>
