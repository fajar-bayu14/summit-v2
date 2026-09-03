<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Tent, ArrowLeft } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen bg-muted/20 flex flex-col">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur">
      <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-8">
        <div class="flex items-center gap-2">
          <Tent class="h-6 w-6 text-amber-600" />
          <span class="font-bold text-lg tracking-tight">SUMMIT</span>
          <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium ml-2">Mitra Basecamp</span>
        </div>

        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
          <router-link to="/" class="flex items-center gap-1.5 text-muted-foreground hover:text-foreground transition">
            <ArrowLeft class="h-4 w-4" /> Portal Selector
          </router-link>
          <router-link to="/mitra" class="hover:text-amber-600 transition">Dashboard Basecamp</router-link>
          <router-link to="/pendaki" class="text-muted-foreground hover:text-foreground transition">Web Pendaki</router-link>
          <router-link to="/admin" class="text-muted-foreground hover:text-foreground transition">Admin</router-link>
        </nav>

        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm">Akun Mitra</Button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 container mx-auto px-4 sm:px-8 py-6">
      <router-view />
    </main>
  </div>
</template>
