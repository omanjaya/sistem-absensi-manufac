<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Sistem Absensi</h1>
        <p class="mt-2 text-sm text-gray-600">Face Recognition Attendance System</p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <router-view />
      </div>
    </div>

    <div class="mt-8 text-center">
      <p class="text-xs text-gray-500">
        © 2024 Sistem Absensi. All rights reserved.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
// No script needed for this layout
</script> 