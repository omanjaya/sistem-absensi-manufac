<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Mobile sidebar backdrop -->
    <div 
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
      @click="isMobileMenuOpen = false"
    />

    <!-- Mobile sidebar -->
    <div 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out lg:hidden',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex items-center justify-between h-16 px-4 border-b">
        <h1 class="text-lg font-semibold text-gray-900">{{ appName }}</h1>
        <button
          @click="isMobileMenuOpen = false"
          class="p-2 text-gray-400 hover:text-gray-600"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>
      <nav class="mt-8">
        <SidebarMenu @navigate="isMobileMenuOpen = false" />
      </nav>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:w-64 lg:block">
      <div class="flex flex-col h-full bg-white border-r border-gray-200">
        <div class="flex items-center h-16 px-6 border-b">
          <h1 class="text-lg font-semibold text-gray-900">{{ appName }}</h1>
        </div>
        <nav class="flex-1 mt-8">
          <SidebarMenu />
        </nav>
      </div>
    </div>

    <!-- Main content -->
    <div class="lg:pl-64">
      <!-- Top header -->
      <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6">
          <!-- Mobile menu button -->
          <button
            @click="isMobileMenuOpen = true"
            class="p-2 text-gray-400 hover:text-gray-600 lg:hidden"
          >
            <Bars3Icon class="w-6 h-6" />
          </button>

          <!-- Page title -->
          <div class="flex-1 lg:ml-0">
            <h2 class="text-xl font-semibold text-gray-900">
              {{ $route.meta.title || 'Dashboard' }}
            </h2>
          </div>

          <!-- User menu -->
          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <NotificationCenter />

            <!-- User dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="flex items-center space-x-2 p-2 text-sm rounded-full hover:bg-gray-100">
                <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                  <span class="text-sm font-medium text-white">
                    {{ userInitials }}
                  </span>
                </div>
                <ChevronDownIcon class="w-4 h-4 text-gray-400" />
              </MenuButton>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <router-link
                        to="/profile"
                        :class="[
                          active ? 'bg-gray-100' : '',
                          'block px-4 py-2 text-sm text-gray-700'
                        ]"
                      >
                        <UserIcon class="w-4 h-4 inline mr-2" />
                        Profil
                      </router-link>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="handleLogout"
                        :class="[
                          active ? 'bg-gray-100' : '',
                          'block w-full text-left px-4 py-2 text-sm text-gray-700'
                        ]"
                      >
                        <ArrowRightOnRectangleIcon class="w-4 h-4 inline mr-2" />
                        Logout
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1">
        <div class="py-6">
          <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <router-view />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineComponent } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  Bars3Icon,
  XMarkIcon,
  BellIcon,
  ChevronDownIcon,
  UserIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'
import SidebarMenu from '@/components/SidebarMenu.vue'
import NotificationCenter from '@/components/NotificationCenter.vue'

// Reactive data
const isMobileMenuOpen = ref(false)
const appName = import.meta.env.VITE_APP_NAME || 'Sistem Absensi'

// Stores and composables
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// Computed properties
const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U'
  return authStore.user.name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// Methods
const handleLogout = async () => {
  try {
    await authStore.logout()
    toast.success('Berhasil logout')
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
    toast.error('Gagal logout')
  }
}
</script> 