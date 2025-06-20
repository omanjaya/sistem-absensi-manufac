<template>
  <div class="px-6">
    <ul class="space-y-2">
      <li v-for="item in menuItems" :key="item.name">
        <router-link
          :to="item.to"
          :class="[
            'flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors',
            $route.name === item.name
              ? 'bg-primary-100 text-primary-700 border-r-2 border-primary-600'
              : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
          ]"
          @click="$emit('navigate')"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          {{ item.label }}
        </router-link>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  ClockIcon,
  UsersIcon,
  CalendarDaysIcon,
  BanknotesIcon,
  FaceSmileIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  ShieldCheckIcon,
  Cog6ToothIcon,
  FolderIcon,
  ClockIcon as TimeIcon
} from '@heroicons/vue/24/outline'

defineEmits<{
  navigate: []
}>()

const authStore = useAuthStore()

// Base menu items for all users
const baseMenuItems = [
  {
    name: 'Dashboard',
    label: 'Dashboard',
    to: '/',
    icon: HomeIcon
  },
  {
    name: 'Attendance',
    label: 'Absensi',
    to: '/attendance',
    icon: ClockIcon
  },
  {
    name: 'Leave',
    label: 'Izin',
    to: '/leaves',
    icon: CalendarDaysIcon
  },
  {
    name: 'Projects',
    label: 'Projects',
    to: '/projects',
    icon: FolderIcon
  },
  {
    name: 'TimeTracking',
    label: 'Time Tracking',
    to: '/time-tracking',
    icon: TimeIcon
  },
  {
    name: 'FaceRegistration',
    label: 'Registrasi Wajah',
    to: '/face-registration',
    icon: FaceSmileIcon
  }
]

// Admin-only menu items
const adminMenuItems = [
  {
    name: 'AttendanceList',
    label: 'Daftar Absensi',
    to: '/attendance/list',
    icon: ClipboardDocumentListIcon
  },
  {
    name: 'Employee',
    label: 'Guru & Staff',
    to: '/employees',
    icon: UsersIcon
  },
  {
    name: 'Salary',
    label: 'Gaji & Honor',
    to: '/salary',
    icon: BanknotesIcon
  },
  {
    name: 'Analytics',
    label: 'Analytics',
    to: '/analytics',
    icon: ChartBarIcon
  },
  {
    name: 'Security',
    label: 'Security',
    to: '/security',
    icon: ShieldCheckIcon
  },
  {
    name: 'Workflows',
    label: 'Workflows',
    to: '/workflows',
    icon: Cog6ToothIcon
  }
]

const menuItems = computed(() => {
  const items = [...baseMenuItems]
  
  if (authStore.isAdmin) {
    items.splice(2, 0, ...adminMenuItems) // Insert after Attendance
  }
  
  return items
})
</script> 