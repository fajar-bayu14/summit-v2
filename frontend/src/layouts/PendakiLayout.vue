<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Mountain } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col">
    <!-- Navbar -->
    <header class="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur">
      <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-8">
        <div class="flex items-center gap-2">
          <Mountain class="h-6 w-6 text-emerald-700" />
          <span class="font-bold text-lg tracking-tight">SUMMIT</span>
          <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-medium ml-2">Pendaki</span>
        </div>

        <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
          <router-link to="/" class="hover:text-emerald-700 transition">Portal Home</router-link>
          <router-link to="/pendaki" class="hover:text-emerald-700 transition">Jelajah Gunung</router-link>
          <router-link to="/mitra" class="text-muted-foreground hover:text-foreground transition">Portal Mitra</router-link>
          <router-link to="/admin" class="text-muted-foreground hover:text-foreground transition">Portal Admin</router-link>
        </nav>

        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm">Masuk</Button>
          <Button size="sm" class="bg-emerald-700 hover:bg-emerald-800 text-white">Daftar</Button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-1 container mx-auto px-4 sm:px-8 py-6">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="border-t py-6 bg-muted/40 text-center text-sm text-muted-foreground">
      <div class="container mx-auto px-4">
        &copy; {{ new Date().getFullYear() }} Summit Marketplace. All rights reserved.
      </div>
    </footer>
  </div>
</template>
