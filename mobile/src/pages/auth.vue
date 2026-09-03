<template>
  <div class="auth-page column">
    <header class="auth-header row items-center">
      <q-btn
        flat
        round
        icon="arrow_back"
        aria-label="Kembali"
        class="auth-back"
        @click="goBack"
      />
    </header>

    <main class="auth-body column items-center flex-1 q-px-md">
      <div class="auth-brand column items-center q-mt-lg q-mb-lg">
        <div class="auth-logo">
          <img src="~@/assets/summit-logo.jpg" alt="Logo SUMMIT" />
        </div>
        <div class="auth-brand-name">SUMMIT</div>
        <div class="auth-brand-tagline"
          >Petualangan Gunungmu Dimulai Di Sini</div
        >
      </div>

      <div class="auth-segmented" role="tablist">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          role="tab"
          :aria-selected="mode === tab.value"
          :class="{ 'is-active': mode === tab.value }"
          @click="mode = tab.value"
        >
          {{ tab.label }}
        </button>
      </div>

      <q-form class="auth-form column" @submit.prevent="onSubmit">
        <q-input
          v-if="mode === 'register'"
          v-model="form.name"
          outlined
          class="auth-input"
          placeholder="Nama Lengkap"
          type="text"
          autocomplete="name"
          :lazy-rules="true"
          :rules="[rules.required, rules.minLength(2)]"
        >
          <template #prepend>
            <q-icon name="person" />
          </template>
        </q-input>

        <q-input
          v-model="form.email"
          outlined
          class="auth-input"
          placeholder="Email atau Nomor Telepon"
          type="email"
          autocomplete="email"
          :lazy-rules="true"
          :rules="[rules.required, rules.email]"
        >
          <template #prepend>
            <q-icon name="mail" />
          </template>
        </q-input>

        <q-input
          v-model="form.password"
          outlined
          class="auth-input"
          :placeholder="mode === 'login' ? 'Kata Sandi' : 'Buat Kata Sandi'"
          :type="showPassword ? 'text' : 'password'"
          :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
          :lazy-rules="true"
          :rules="[rules.required, rules.minLength(8)]"
        >
          <template #prepend>
            <q-icon name="lock" />
          </template>
          <template #append>
            <q-btn
              flat
              round
              dense
              :icon="showPassword ? 'visibility_off' : 'visibility'"
              :aria-label="
                showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
              "
              @click="showPassword = !showPassword"
            />
          </template>
        </q-input>

        <q-input
          v-if="mode === 'register'"
          v-model="form.passwordConfirmation"
          outlined
          class="auth-input"
          placeholder="Konfirmasi Kata Sandi"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="new-password"
          :lazy-rules="true"
          :rules="[rules.required, rules.confirmPassword]"
        >
          <template #prepend>
            <q-icon name="lock" />
          </template>
        </q-input>

        <div v-if="mode === 'login'" class="auth-forgot row justify-end">
          <a
            href="#"
            class="auth-forgot-link"
            @click.prevent="onForgotPassword"
          >
            Lupa Kata Sandi?
          </a>
        </div>

        <q-btn
          type="submit"
          unelevated
          no-caps
          color="primary"
          class="auth-submit"
          :loading="loading"
        >
          {{ mode === 'login' ? 'Masuk ke Akun' : 'Daftar Sekarang' }}
          <q-icon
            name="arrow_forward"
            style="font-variation-settings: 'FILL' 1"
          />
        </q-btn>
      </q-form>

      <div class="auth-divider">
        <span>atau masuk dengan</span>
      </div>

      <div class="auth-social column q-gutter-sm">
        <q-btn
          unelevated
          no-caps
          color="white"
          text-color="black"
          class="auth-google"
          @click="onGoogleLogin"
        >
          <svg class="auth-google-logo" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              fill="#4285F4"
            />
            <path
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              fill="#34A853"
            />
            <path
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              fill="#FBBC05"
            />
            <path
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              fill="#EA4335"
            />
          </svg>
          <span class="q-ml-sm">Google</span>
        </q-btn>

        <q-btn
          unelevated
          no-caps
          color="white"
          text-color="black"
          class="auth-guest"
          @click="onGuestMode"
        >
          Masuk Tanpa Akun (Mode Jelajah)
        </q-btn>
      </div>
    </main>

    <footer class="auth-footer">
      <p class="auth-terms">
        Dengan masuk, Anda menyetujui
        <a href="#" class="auth-link" @click.prevent>Syarat &amp; Ketentuan</a>
        serta
        <a href="#" class="auth-link" @click.prevent>Kebijakan Privasi</a>
        kami.
      </p>
    </footer>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { login, register } from '@/api/auth'

const router = useRouter()
const $q = useQuasar()

const tabs = [
  { label: 'Masuk', value: 'login' },
  { label: 'Daftar', value: 'register' }
]

const mode = ref('login')
const showPassword = ref(false)
const loading = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: ''
})

const rules = {
  required: val => !!val || 'Wajib diisi',
  email: val =>
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Format email tidak valid',
  minLength: n => val => (val || '').length >= n || `Minimal ${n} karakter`,
  confirmPassword: val => val === form.password || 'Kata sandi tidak cocok'
}

function goBack() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}

async function onSubmit() {
  loading.value = true
  try {
    if (mode.value === 'login') {
      const result = await login({
        email: form.email,
        password: form.password
      })
      $q.notify({
        type: 'positive',
        message: result.message || 'Login berhasil'
      })
      router.push('/')
    } else {
      const result = await register({
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.passwordConfirmation
      })
      $q.notify({
        type: 'positive',
        message:
          result.message ||
          'Registrasi berhasil. Cek email Anda untuk kode OTP.'
      })
      router.push({ path: '/verify-otp', query: { email: form.email } })
    }
  } catch (error) {
    $q.notify({ type: 'negative', message: error.message })
  } finally {
    loading.value = false
  }
}

function onForgotPassword() {
  $q.notify({ type: 'info', message: 'Fitur lupa kata sandi segera hadir' })
}

function onGoogleLogin() {
  $q.notify({ type: 'info', message: 'Login dengan Google segera hadir' })
}

function onGuestMode() {
  router.push('/')
}
</script>

<style lang="scss">
.auth-page {
  min-height: 100vh;
  background: $summit-background;
  color: $summit-text-primary;
}

.auth-header {
  padding: 8px;
}

.auth-back {
  color: $summit-text-primary;
}

.auth-body {
  width: 100%;
  max-width: 448px;
  margin: 0 auto;
  padding-bottom: 24px;
}

.auth-brand {
  text-align: center;
}

.auth-logo {
  width: 80px;
  height: 80px;
  overflow: hidden;
  border-radius: 50%;
  background: $summit-surface;
  border: 1px solid $summit-border;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.auth-brand-name {
  margin-top: 8px;
  font-size: 1.375rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: $summit-text-primary;
}

.auth-brand-tagline {
  margin-top: 4px;
  font-size: 0.875rem;
  color: $summit-text-secondary;
}

.auth-segmented {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 2px;
  border-radius: 9999px;
  background: $summit-surface-container;

  button {
    flex: 1;
    height: 44px;
    border: none;
    border-radius: 9999px;
    background: transparent;
    color: $summit-text-secondary;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition:
      background-color 0.2s,
      color 0.2s;

    &.is-active {
      background: $primary;
      color: #fff;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.1);
    }
  }
}

.auth-form {
  width: 100%;
  margin-top: 24px;
  gap: 16px;
}

.auth-input {
  .q-field__control {
    min-height: 48px;
    border-radius: 8px;
    background: $summit-surface;
  }

  .q-field__native {
    color: $summit-text-primary;
  }

  .q-field__prepend .q-icon,
  .q-field__append .q-icon {
    color: $summit-text-secondary;
  }
}

.auth-forgot {
  margin-top: 2px;
}

.auth-forgot-link {
  color: $secondary;
  font-size: 0.875rem;
  font-weight: 500;
}

.auth-submit {
  min-height: 48px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  margin-top: 4px;
}

.auth-divider {
  display: flex;
  align-items: center;
  width: 100%;
  margin: 24px 0;
  color: $summit-text-secondary;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;

  &::before,
  &::after {
    content: '';
    flex: 1;
    border-top: 1px solid $summit-border;
  }

  span {
    padding: 0 16px;
  }
}

.auth-social {
  width: 100%;
}

.auth-google,
.auth-guest {
  width: 100%;
  min-height: 48px;
  border: 1px solid $summit-border;
  border-radius: 8px;
  font-weight: 500;
}

.auth-google-logo {
  width: 20px;
  height: 20px;
}

.auth-footer {
  padding: 16px;
}

.auth-terms {
  margin: 0;
  text-align: center;
  font-size: 0.75rem;
  color: $summit-text-secondary;
}

.auth-link {
  color: $summit-text-secondary;
  text-decoration: underline;
}
</style>
