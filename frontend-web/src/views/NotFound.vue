<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full text-center">
      <div class="mb-8">
        <h1 class="text-9xl font-bold text-gray-300">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">
          Halaman Tidak Ditemukan
        </h2>
        <p class="text-gray-500 mb-8">
          Maaf, halaman yang Anda cari tidak dapat ditemukan.
        </p>
      </div>
      
      <div class="space-y-4">
        <router-link 
          to="/dashboard" 
          class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Kembali ke Dashboard
        </router-link>
        
        <div>
          <router-link 
            to="/login" 
            class="text-blue-600 hover:text-blue-700 transition-colors"
          >
            Atau kembali ke Login
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

// Auto redirect to dashboard after 10 seconds if user is authenticated
setTimeout(() => {
  const token = localStorage.getItem('token')
  if (token) {
    router.push('/dashboard')
  } else {
    router.push('/login')
  }
}, 10000)
</script> 