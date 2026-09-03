<template>
  <nav class="bottom-nav" aria-label="Navigasi bawah">
    <button
      v-for="item in items"
      :key="item.key"
      class="bottom-nav__item"
      :class="{ 'is-active': item.key === active }"
      :aria-current="item.key === active ? 'page' : undefined"
      @click="onSelect(item)"
    >
      <q-icon :name="item.icon" aria-hidden="true" />
      <span>{{ item.label }}</span>
    </button>
  </nav>
</template>

<script setup>
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'

defineProps({
  active: { type: String, default: 'explore' }
})

const $q = useQuasar()
const router = useRouter()

const items = [
  { key: 'beranda', icon: 'home', label: 'Beranda' },
  { key: 'pesanan', icon: 'receipt_long', label: 'Pesanan' },
  { key: 'chat', icon: 'chat', label: 'Chat' },
  { key: 'profil', icon: 'person', label: 'Profil' }
]

function onSelect(item) {
  if (item.key === 'beranda') {
    router.push('/')
  } else if (item.key === 'pesanan') {
    router.push('/orders')
  } else if (item.key === 'chat') {
    router.push('/chat')
  } else if (item.key === 'profil') {
    router.push('/profile')
  }
}
</script>

<style lang="scss">
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: space-around;
  min-height: 64px;
  padding: 4px 16px;
  padding-bottom: calc(4px + env(safe-area-inset-bottom));
  background: $summit-surface;
  border-radius: 12px 12px 0 0;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.08);

  &__item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-width: 44px;
    min-height: 44px;
    padding: 4px 8px;
    border: none;
    border-radius: 12px;
    background: transparent;
    font-size: 0.75rem;
    font-weight: 500;
    color: $summit-text-secondary;
    cursor: pointer;
    transition:
      background-color 0.2s ease,
      color 0.2s ease,
      transform 0.2s ease;

    &:hover {
      color: $summit-text-primary;
      background: $summit-surface-container-low;
    }

    &:active {
      transform: scale(0.95);
    }

    &:focus-visible {
      outline: 2px solid $secondary;
      outline-offset: 2px;
    }

    &.is-active {
      color: $primary;
      font-weight: 700;
    }
  }
}

@media (min-width: 768px) {
  .bottom-nav {
    display: none;
  }
}
</style>
