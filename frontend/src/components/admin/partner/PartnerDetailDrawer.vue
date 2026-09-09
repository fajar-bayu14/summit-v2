<script setup lang="ts">
import { ref } from 'vue'
import {
  X,
  Building2,
  Phone,
  Mail,
  MapPin,
  CreditCard,
  FileText,
  Shield,
  Users,
  Compass,
  Edit2,
  Trash2,
  ExternalLink,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import type { Mitra } from '@/types/partner'

defineProps<{
  open: boolean
  mitra: Mitra | null
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'edit', mitra: Mitra): void
  (e: 'delete', mitra: Mitra): void
  (e: 'toggleStatus', mitra: Mitra): void
}>()

const activeSection = ref<'overview' | 'basecamps' | 'banking'>('overview')

function handleClose() {
  emit('update:open', false)
}
</script>

<template>
  <div>
    <!-- Backdrop overlay -->
    <div
      v-if="open"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs transition-opacity duration-300"
      @click="handleClose"
    />

    <!-- Slide-over Drawer Panel -->
    <div
      class="fixed inset-y-0 right-0 z-50 w-full sm:max-w-md bg-card border-l shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out"
      :class="open ? 'translate-x-0' : 'translate-x-full'"
    >
      <!-- Drawer Header -->
      <div class="p-5 border-b bg-muted/20 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
          <Avatar class="h-10 w-10 rounded-lg border border-border shrink-0">
            <AvatarFallback class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-bold text-sm">
              {{ mitra?.nama_pemilik?.slice(0, 2).toUpperCase() || 'MT' }}
            </AvatarFallback>
          </Avatar>
          <div class="flex flex-col min-w-0">
            <h3 class="font-bold text-sm truncate text-foreground">
              {{ mitra?.nama_pemilik || 'Detail Mitra' }}
            </h3>
            <p class="text-xs text-muted-foreground truncate">
              {{ mitra?.user?.email || '-' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-1.5">
          <Badge
            variant="outline"
            :class="
              mitra?.status === 'aktif'
                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
            "
            class="text-[11px] font-semibold"
          >
            {{ mitra?.status === 'aktif' ? '🟢 Aktif' : '🔴 Suspend' }}
          </Badge>
          <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground hover:text-foreground" @click="handleClose">
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>

      <!-- Section Tabs Navigation -->
      <div class="flex items-center border-b px-4 bg-muted/10 shrink-0 text-xs">
        <button
          type="button"
          class="py-2.5 px-3 border-b-2 font-medium transition-colors flex items-center gap-1.5"
          :class="activeSection === 'overview' ? 'border-primary text-foreground font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="activeSection = 'overview'"
        >
          <Shield class="h-3.5 w-3.5" />
          <span>Profil & Legalitas</span>
        </button>
        <button
          type="button"
          class="py-2.5 px-3 border-b-2 font-medium transition-colors flex items-center gap-1.5"
          :class="activeSection === 'basecamps' ? 'border-primary text-foreground font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="activeSection = 'basecamps'"
        >
          <Building2 class="h-3.5 w-3.5" />
          <span>Basecamp ({{ mitra?.basecamps?.length || 0 }})</span>
        </button>
        <button
          type="button"
          class="py-2.5 px-3 border-b-2 font-medium transition-colors flex items-center gap-1.5"
          :class="activeSection === 'banking' ? 'border-primary text-foreground font-semibold' : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="activeSection = 'banking'"
        >
          <CreditCard class="h-3.5 w-3.5" />
          <span>Rekening Bank</span>
        </button>
      </div>

      <!-- Drawer Body Content (Scrollable) -->
      <div v-if="mitra" class="flex-1 overflow-y-auto p-5 space-y-4">
        <!-- Overview Section -->
        <div v-show="activeSection === 'overview'" class="space-y-4">
          <div class="p-3.5 bg-muted/30 rounded-xl border border-border/60 space-y-3 text-xs">
            <div class="flex items-center justify-between pb-2 border-b border-border/40">
              <span class="text-muted-foreground flex items-center gap-1.5">
                <FileText class="h-3.5 w-3.5" /> NIK Penanggung Jawab
              </span>
              <span class="font-mono font-semibold text-foreground">{{ mitra.nik }}</span>
            </div>

            <div class="flex items-center justify-between pb-2 border-b border-border/40">
              <span class="text-muted-foreground flex items-center gap-1.5">
                <FileText class="h-3.5 w-3.5" /> NPWP Bisnis
              </span>
              <span class="font-mono text-foreground">{{ mitra.npwp || '-' }}</span>
            </div>

            <div class="flex items-center justify-between pb-2 border-b border-border/40">
              <span class="text-muted-foreground flex items-center gap-1.5">
                <Phone class="h-3.5 w-3.5" /> Nomor Telepon / WA
              </span>
              <a
                :href="'https://wa.me/' + mitra.telepon.replace(/\D/g, '')"
                target="_blank"
                class="font-medium text-emerald-600 hover:underline flex items-center gap-1"
              >
                {{ mitra.telepon }}
                <ExternalLink class="h-3 w-3" />
              </a>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-muted-foreground flex items-center gap-1.5">
                <Mail class="h-3.5 w-3.5" /> Akun Email Login
              </span>
              <span class="text-foreground truncate max-w-[180px]">{{ mitra.user?.email || '-' }}</span>
            </div>
          </div>

          <div class="space-y-1.5 text-xs">
            <span class="font-semibold text-foreground flex items-center gap-1.5">
              <MapPin class="h-3.5 w-3.5 text-muted-foreground" /> Alamat Lengkap
            </span>
            <p class="p-3 bg-muted/20 rounded-lg text-muted-foreground leading-relaxed border border-border/40">
              {{ mitra.alamat }}
            </p>
          </div>

          <div v-if="mitra.deskripsi" class="space-y-1.5 text-xs">
            <span class="font-semibold text-foreground">Deskripsi / Profil Singkat</span>
            <p class="p-3 bg-muted/20 rounded-lg text-muted-foreground leading-relaxed border border-border/40">
              {{ mitra.deskripsi }}
            </p>
          </div>

          <div v-if="mitra.staff && mitra.staff.length > 0" class="space-y-2 text-xs">
            <span class="font-semibold text-foreground flex items-center gap-1.5">
              <Users class="h-3.5 w-3.5 text-muted-foreground" /> Staf Operasional Terdaftar ({{ mitra.staff.length }})
            </span>
            <div class="divide-y border border-border/50 rounded-lg overflow-hidden">
              <div
                v-for="s in mitra.staff"
                :key="s.id"
                class="p-2.5 bg-card flex items-center justify-between text-xs"
              >
                <div class="flex flex-col">
                  <span class="font-medium text-foreground">{{ s.nama_staff }}</span>
                  <span class="text-[11px] text-muted-foreground">{{ s.nomor_hp }}</span>
                </div>
                <Badge variant="outline" class="text-[10px] capitalize">
                  {{ s.peran.replace('_', ' ') }}
                </Badge>
              </div>
            </div>
          </div>
        </div>

        <!-- Basecamps Section -->
        <div v-show="activeSection === 'basecamps'" class="space-y-3">
          <div v-if="!mitra.basecamps || mitra.basecamps.length === 0" class="text-center py-8 text-xs text-muted-foreground space-y-1.5">
            <Building2 class="h-8 w-8 mx-auto text-muted-foreground/40" />
            <p>Belum ada basecamp yang dinaungi oleh mitra ini.</p>
          </div>

          <div
            v-for="bc in mitra.basecamps"
            :key="bc.id"
            class="p-3.5 rounded-xl border border-border/60 bg-muted/20 space-y-2 text-xs"
          >
            <div class="flex items-start justify-between">
              <div class="space-y-0.5">
                <h4 class="font-bold text-foreground">{{ bc.nama_basecamp }}</h4>
                <p class="text-[11px] text-muted-foreground flex items-center gap-1">
                  <Compass class="h-3 w-3 text-emerald-600" />
                  <span>{{ bc.jalur?.gunung?.nama_gunung || 'Gunung' }} — {{ bc.jalur?.nama_jalur || 'Jalur' }}</span>
                </p>
              </div>
              <Badge variant="secondary" class="text-[10px]">
                {{ bc.jam_operasional }}
              </Badge>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-border/40 text-[11px]">
              <span class="font-mono text-muted-foreground">{{ bc.latitude }}, {{ bc.longitude }}</span>
              <a
                :href="`https://www.google.com/maps?q=${bc.latitude},${bc.longitude}`"
                target="_blank"
                class="text-emerald-600 hover:underline flex items-center gap-1"
              >
                Lihat Peta <ExternalLink class="h-3 w-3" />
              </a>
            </div>
          </div>
        </div>

        <!-- Banking Section -->
        <div v-show="activeSection === 'banking'" class="space-y-4">
          <div class="p-4 bg-gradient-to-br from-emerald-950 to-slate-900 text-white rounded-2xl shadow-md space-y-4 relative overflow-hidden">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold tracking-wider text-emerald-200 uppercase">{{ mitra.bank }}</span>
              <CreditCard class="h-5 w-5 text-emerald-300" />
            </div>

            <div class="space-y-1">
              <span class="text-[10px] text-emerald-300/80 uppercase tracking-wider">Nomor Rekening</span>
              <p class="text-lg font-mono font-bold tracking-widest">{{ mitra.rekening_bank }}</p>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-emerald-800/40 text-xs">
              <div>
                <span class="text-[10px] text-emerald-300/80 uppercase">Atas Nama</span>
                <p class="font-medium truncate max-w-[180px]">{{ mitra.nama_rekening }}</p>
              </div>
              <div v-if="mitra.ewallet" class="text-right">
                <span class="text-[10px] text-emerald-300/80 uppercase">E-Wallet</span>
                <p class="font-mono text-[11px]">{{ mitra.ewallet }}</p>
              </div>
            </div>
          </div>

          <div class="p-3 bg-muted/30 rounded-lg border border-border/50 text-xs space-y-1">
            <p class="font-semibold text-foreground">Integrasi Payout & Escrow</p>
            <p class="text-[11px] text-muted-foreground">
              Rekening ini digunakan otomatis oleh sistem pembayaran untuk mencairkan pendapatan tiket dan rental basecamp setelah pendaki menyelesaikan pendakian (*checkout*).
            </p>
          </div>
        </div>
      </div>

      <!-- Drawer Footer Actions -->
      <div v-if="mitra" class="p-4 border-t bg-card shrink-0 flex items-center justify-between gap-2">
        <Button
          variant="outline"
          size="sm"
          class="text-xs gap-1.5 text-destructive hover:bg-destructive/10 hover:text-destructive"
          @click="emit('delete', mitra)"
        >
          <Trash2 class="h-3.5 w-3.5" />
          <span>Hapus</span>
        </Button>

        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            class="text-xs gap-1.5"
            @click="emit('toggleStatus', mitra)"
          >
            <span>{{ mitra.status === 'aktif' ? 'Suspend Mitra' : 'Aktifkan Mitra' }}</span>
          </Button>

          <Button
            size="sm"
            class="text-xs gap-1.5 bg-emerald-700 hover:bg-emerald-800 text-white"
            @click="emit('edit', mitra)"
          >
            <Edit2 class="h-3.5 w-3.5" />
            <span>Edit Profil</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
