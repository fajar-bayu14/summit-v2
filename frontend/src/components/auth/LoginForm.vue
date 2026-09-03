<script setup lang="ts">
import { ref, type HTMLAttributes } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toTypedSchema } from '@vee-validate/zod'
import { configure, useForm } from 'vee-validate'
import { Mountain, AlertCircle, Loader2, Lock, Mail, Eye, EyeOff } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { loginSchema, type LoginFormValues } from '@/schemas/auth'
import { useAuthStore } from '@/stores/auth'
import { getDashboardRouteByRole } from '@/router/guards'

import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import heroImage from '@/assets/hero.jpg'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const showPassword = ref(false)
const generalError = ref<string | null>(null)

// Configure Vee-Validate to strictly validate on Form Submit only
configure({
  validateOnBlur: false,
  validateOnChange: false,
  validateOnInput: false,
  validateOnModelUpdate: false,
})

// Initialize Vee-Validate Form with Zod Schema
const form = useForm<LoginFormValues>({
  validationSchema: toTypedSchema(loginSchema),
  initialValues: {
    email: '',
    password: '',
    remember: false,
  },
})

const onSubmit = form.handleSubmit(async (values) => {
  generalError.value = null
  authStore.clearErrors()

  try {
    const user = await authStore.login({
      email: values.email,
      password: values.password,
      remember: values.remember,
    })

    // Determine redirection target
    const redirectQuery = route.query.redirect as string | undefined
    if (redirectQuery && !redirectQuery.startsWith('/login')) {
      router.push(redirectQuery)
    } else {
      router.push(getDashboardRouteByRole(user.role))
    }
  } catch (err: any) {
    // 1. Handle 422 Unprocessable Content (Laravel Validation Errors)
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const backendErrors = err.response.data.errors
      const formattedErrors: Record<string, string> = {}
      
      for (const [field, messages] of Object.entries(backendErrors)) {
        if (Array.isArray(messages) && messages.length > 0) {
          formattedErrors[field] = messages[0] as string
        }
      }
      form.setErrors(formattedErrors)
      generalError.value = err.response.data.message || 'Data yang dikirimkan tidak valid.'
    } 
    // 2. Handle 401 Unauthorized (Invalid credentials)
    else if (err.response?.status === 401) {
      generalError.value = err.response?.data?.message || 'Email atau kata sandi yang Anda masukkan salah.'
    } 
    // 3. Handle 403 Forbidden (Unverified email)
    else if (err.response?.status === 403) {
      generalError.value = err.response?.data?.message || 'Akun Anda belum diverifikasi. Silakan cek email Anda.'
    } 
    // 4. Handle 429 Too Many Requests
    else if (err.response?.status === 429) {
      generalError.value = 'Terlalu banyak percobaan masuk. Silakan tunggu beberapa saat lagi.'
    } 
    // 5. Handle Network Error / Backend unreachable
    else if (!err.response || err.code === 'ERR_NETWORK' || err.message === 'Network Error') {
      generalError.value = 'Gagal terhubung ke server backend (Network Error). Pastikan server Laravel aktif di port 8000.'
    }
    // 6. Fallback general error
    else {
      generalError.value = authStore.error || err.response?.data?.message || 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.'
    }
  }
})

// Quick Fill Demo Credentials Helper
const fillCredentials = (role: 'admin' | 'pendaki' | 'mitra') => {
  generalError.value = null
  authStore.clearErrors()
  if (role === 'admin') {
    form.setFieldValue('email', 'admin@example.com')
    form.setFieldValue('password', 'password')
  } else if (role === 'mitra') {
    form.setFieldValue('email', 'mitra@example.com')
    form.setFieldValue('password', 'password')
  } else {
    form.setFieldValue('email', 'pendaki@example.com')
    form.setFieldValue('password', 'password')
  }
}
</script>

<template>
  <div :class="cn('flex flex-col gap-6', props.class)">
    <Card class="overflow-hidden p-0 shadow-2xl border-slate-200/80 rounded-3xl">
      <CardContent class="grid p-0 md:grid-cols-2">
        <!-- Form Column -->
        <form class="p-6 sm:p-8 md:p-10 flex flex-col justify-between" @submit="onSubmit">
          <div class="space-y-6">
            <!-- Brand Header -->
            <div class="flex flex-col items-center text-center space-y-2">
              <router-link to="/" class="flex items-center gap-2 group mb-1">
                <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shadow-md shadow-primary/20 group-hover:scale-105 transition-transform">
                  <Mountain class="w-5 h-5" />
                </div>
                <span class="font-bold text-2xl tracking-tighter text-slate-900">SUMMIT</span>
              </router-link>
              <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Selamat Datang Kembali
              </h1>
              <p class="text-sm text-muted-foreground text-balance">
                Masuk ke akun Summit untuk melanjutkan petualangan pendakianmu
              </p>
            </div>

            <!-- General / 401 / 403 Alert Feedback -->
            <Alert v-if="generalError" variant="destructive" class="border-red-200 bg-red-50 text-red-900">
              <AlertCircle class="h-4 w-4 text-red-600" />
              <AlertTitle class="font-semibold text-red-900">Autentikasi Gagal</AlertTitle>
              <AlertDescription class="text-xs text-red-800 mt-1">
                {{ generalError }}
              </AlertDescription>
            </Alert>

            <!-- Form Fields -->
            <div class="space-y-4">
              <!-- Email Field -->
              <FormField
                v-slot="{ componentField }"
                name="email"
                :validate-on-blur="false"
                :validate-on-change="false"
                :validate-on-input="false"
                :validate-on-model-update="false"
              >
                <FormItem class="space-y-1.5">
                  <FormLabel class="text-xs font-semibold text-slate-700">Alamat Email</FormLabel>
                  <FormControl>
                    <div class="relative">
                      <Mail class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                      <Input
                        type="email"
                        placeholder="nama@email.com"
                        class="pl-9 h-11 rounded-xl bg-slate-50/70 border-slate-200 focus:bg-white transition-colors"
                        v-bind="componentField"
                        autocomplete="email"
                        :disabled="authStore.isLoading"
                      />
                    </div>
                  </FormControl>
                  <FormMessage class="text-xs text-red-600 font-medium" />
                </FormItem>
              </FormField>

              <!-- Password Field -->
              <FormField
                v-slot="{ componentField }"
                name="password"
                :validate-on-blur="false"
                :validate-on-change="false"
                :validate-on-input="false"
                :validate-on-model-update="false"
              >
                <FormItem class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <FormLabel class="text-xs font-semibold text-slate-700">Kata Sandi</FormLabel>
                    <a
                      href="#"
                      class="text-xs text-primary font-medium hover:underline focus:outline-none"
                      @click.prevent
                    >
                      Lupa kata sandi?
                    </a>
                  </div>
                  <FormControl>
                    <div class="relative">
                      <Lock class="w-4 h-4 text-muted-foreground absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                      <Input
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="••••••••"
                        class="pl-9 pr-10 h-11 rounded-xl bg-slate-50/70 border-slate-200 focus:bg-white transition-colors"
                        v-bind="componentField"
                        autocomplete="current-password"
                        :disabled="authStore.isLoading"
                      />
                      <button
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-slate-900 focus:outline-none"
                        tabindex="-1"
                        @click="showPassword = !showPassword"
                      >
                        <EyeOff v-if="showPassword" class="w-4 h-4" />
                        <Eye v-else class="w-4 h-4" />
                      </button>
                    </div>
                  </FormControl>
                  <FormMessage class="text-xs text-red-600 font-medium" />
                </FormItem>
              </FormField>

              <!-- Submit Button -->
              <Button
                type="submit"
                class="w-full h-11 rounded-xl font-semibold bg-primary hover:bg-primary/90 text-white shadow-lg shadow-primary/20 transition-all flex items-center justify-center gap-2 cursor-pointer mt-2"
                :disabled="authStore.isLoading"
              >
                <Loader2 v-if="authStore.isLoading" class="w-4 h-4 animate-spin" />
                <span>{{ authStore.isLoading ? 'Memproses Masuk...' : 'Masuk ke Akun' }}</span>
              </Button>
            </div>

            <!-- Quick Demo Credentials Helper -->
            <div class="pt-2">
              <div class="relative flex items-center justify-center my-3">
                <div class="border-t border-slate-200 w-full"></div>
                <span class="bg-white px-3 text-[11px] font-medium text-muted-foreground uppercase tracking-wider absolute">
                  Demo Cepat Akun
                </span>
              </div>
              <div class="grid grid-cols-3 gap-2">
                <button
                  type="button"
                  class="text-xs py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium transition-colors cursor-pointer"
                  @click="fillCredentials('admin')"
                >
                  Admin
                </button>
                <button
                  type="button"
                  class="text-xs py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium transition-colors cursor-pointer"
                  @click="fillCredentials('mitra')"
                >
                  Mitra Basecamp
                </button>
                <button
                  type="button"
                  class="text-xs py-1.5 px-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium transition-colors cursor-pointer"
                  @click="fillCredentials('pendaki')"
                >
                  Pendaki
                </button>
              </div>
            </div>

            <!-- Bottom Register Link -->
            <p class="text-center text-xs text-muted-foreground pt-2">
              Belum punya akun pendaki?
              <router-link to="/#register" class="font-semibold text-primary hover:underline">
                Daftar sekarang
              </router-link>
            </p>
          </div>
        </form>

        <!-- Visual / Image Column (login-04 style with Summit Aesthetics) -->
        <div class="bg-pine-950 relative hidden md:block overflow-hidden">
          <img
            :src="heroImage"
            alt="Summit Mountain"
            class="absolute inset-0 h-full w-full object-cover opacity-80 mix-blend-overlay"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-pine-950 via-pine-950/40 to-transparent"></div>
          
          <!-- Atmospheric Content Overlay -->
          <div class="absolute bottom-10 left-8 right-8 text-white space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-primary-fixed">
              <Mountain class="w-3.5 h-3.5" />
              <span>Summit Official Simaksi</span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-white leading-snug">
              Jelajahi Puncak Impian dengan Izin Resmi &amp; Aman
            </h2>
            <p class="text-xs text-white/80 leading-relaxed">
              Platform terintegrasi simaksi online, pemantauan kuota pendakian real-time, dan logistik basecamp di seluruh Indonesia.
            </p>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Footer Terms Note -->
    <p class="px-6 text-center text-xs text-muted-foreground">
      Dengan masuk, Anda menyetujui
      <a href="#" class="underline underline-offset-4 hover:text-slate-900">Ketentuan Layanan</a>
      dan
      <a href="#" class="underline underline-offset-4 hover:text-slate-900">Kebijakan Privasi</a>
      Summit.
    </p>
  </div>
</template>
