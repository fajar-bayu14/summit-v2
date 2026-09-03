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
      </div>

      <div class="otp-heading text-center q-mb-md">
        <div class="otp-title">Verifikasi Email</div>
        <div class="otp-subtitle">
          Kami telah mengirim kode 6 digit ke
          <span class="otp-email">{{ email || 'email Anda' }}</span>
        </div>
      </div>

      <q-form class="otp-form column" @submit.prevent="onVerify">
        <q-input
          ref="otpInput"
          v-model="otp"
          outlined
          class="otp-input"
          placeholder="000000"
          type="tel"
          inputmode="numeric"
          maxlength="6"
          mask="######"
          autocomplete="one-time-code"
          :lazy-rules="true"
          :rules="[rules.required, rules.digits]"
        >
          <template #prepend>
            <q-icon name="shield" />
          </template>
        </q-input>

        <q-btn
          type="submit"
          unelevated
          no-caps
          color="primary"
          class="auth-submit"
          :loading="loading"
        >
          Verifikasi
          <q-icon
            name="arrow_forward"
            style="font-variation-settings: 'FILL' 1"
          />
        </q-btn>

        <div class="otp-resend row items-center justify-center q-mt-sm">
          <template v-if="resendCooldown > 0">
            <span class="otp-resend-hint">
              Kirim ulang kode dalam {{ resendCooldown }} detik
            </span>
          </template>
          <template v-else>
            <span class="otp-resend-hint">Belum menerima kode?</span>
            <button type="button" class="otp-resend-link" @click="onResend">
              Kirim ulang
            </button>
          </template>
        </div>
      </q-form>
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import { resendOtp, verifyOtp } from '@/api/auth'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()

const email = computed(() => (route.query.email || '').toString())
const otp = ref('')
const loading = ref(false)
const resendCooldown = ref(60)
const otpInput = ref(null)
let resendTimer = null

const rules = {
  required: val => !!val || 'Masukkan kode OTP',
  digits: val => /^\d{6}$/.test(val) || 'Kode OTP terdiri dari 6 digit'
}

function goBack() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/auth')
  }
}

async function onVerify() {
  if (!email.value) {
    $q.notify({
      type: 'warning',
      message: 'Email tidak ditemukan. Silakan daftar ulang.'
    })
    router.push('/auth')
    return
  }

  loading.value = true
  try {
    const result = await verifyOtp({ email: email.value, otp: otp.value })
    $q.notify({
      type: 'positive',
      message: result.message || 'Email berhasil diverifikasi'
    })
    router.push('/')
  } catch (error) {
    $q.notify({ type: 'negative', message: error.message })
  } finally {
    loading.value = false
  }
}

function startCooldown() {
  resendCooldown.value = 60
  clearInterval(resendTimer)
  resendTimer = setInterval(() => {
    resendCooldown.value -= 1
    if (resendCooldown.value <= 0) {
      clearInterval(resendTimer)
    }
  }, 1000)
}

async function onResend() {
  if (!email.value) {
    $q.notify({ type: 'warning', message: 'Email tidak ditemukan.' })
    return
  }

  try {
    await resendOtp(email.value)
    $q.notify({ type: 'positive', message: 'Kode OTP baru telah dikirim' })
    startCooldown()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.message })
  }
}

onMounted(() => {
  if (otpInput.value) {
    otpInput.value.focus()
  }
  startCooldown()
})

onBeforeUnmount(() => {
  clearInterval(resendTimer)
})
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

.otp-title {
  font-size: 1.375rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: $summit-text-primary;
}

.otp-subtitle {
  margin-top: 8px;
  font-size: 0.875rem;
  line-height: 1.5;
  color: $summit-text-secondary;
}

.otp-email {
  font-weight: 600;
  color: $summit-text-primary;
}

.otp-form {
  width: 100%;
  gap: 16px;
}

.otp-input {
  .q-field__control {
    min-height: 56px;
    border-radius: 8px;
    background: $summit-surface;
  }

  .q-field__native {
    padding-left: 0.5em;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: 0.5em;
    color: $summit-text-primary;
  }

  .q-field__prepend .q-icon {
    color: $summit-text-secondary;
  }
}

.auth-submit {
  min-height: 48px;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  margin-top: 4px;
}

.otp-resend {
  gap: 4px;
}

.otp-resend-hint {
  font-size: 0.875rem;
  color: $summit-text-secondary;
}

.otp-resend-link {
  border: none;
  background: none;
  padding: 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: $secondary;
  cursor: pointer;
  text-decoration: underline;
}
</style>
