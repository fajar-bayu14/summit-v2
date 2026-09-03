<script setup lang="ts">
import LoginForm from '@/components/auth/LoginForm.vue'
</script>

<template>
  <div class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 md:p-10 relative overflow-hidden">
    <!-- Ambient Background Lighting -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-sm md:max-w-4xl relative z-10">
      <LoginForm />
    </div>
  </div>
</template>
