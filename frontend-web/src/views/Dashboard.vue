<template>
  <div class="space-y-8">
    <!-- Loading overlay -->
    <LoadingSpinner 
      v-if="isInitialLoading" 
      :overlay="true"
      text="Memuat dashboard..."
      color="primary"
      size="lg"
    />

    <!-- Welcome Section with enhanced styling -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-6 text-white">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold mb-2">
            Selamat datang, {{ authStore.user?.name }}! 👋
          </h1>
          <p class="text-blue-100 text-lg">
            {{ getCurrentDateString() }}
          </p>
          <p class="text-blue-200 text-sm mt-1">
            {{ getCurrentTime() }}
          </p>
        </div>
        <div class="hidden md:block">
          <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats with MetricCard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        label="Status Hari Ini"
        :value="getTodayStatus()"
        :icon="ClockIcon"
        icon-bg="blue"
        :change="todayTrend.value"
      />
      
      <MetricCard
        label="Kehadiran Bulan Ini"
        :value="`${monthlyStats.present}/${monthlyStats.workDays}`"
        description="Hari kerja"
        :icon="CalendarDaysIcon"
        icon-bg="green"
        :change="monthlyTrend.value"
      />
      
      <MetricCard
        label="Izin Pending"
        :value="pendingLeaves"
        description="Request"
        :icon="DocumentTextIcon"
        icon-bg="yellow"
        hover
        @click="navigateToLeaves"
      />
      
      <MetricCard
        label="Produktivitas"
        :value="`${productivityScore}%`"
        :icon="ChartBarIcon"
        icon-bg="indigo"
        :change="productivityTrend.value"
      />
    </div>

    <!-- Quick Actions with enhanced cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Attendance Card -->
      <Card class="hover:shadow-lg transition-shadow duration-300">
        <template #header>
          <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
              <CameraIcon class="w-6 h-6 text-blue-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Absensi Cepat</h3>
          </div>
        </template>
        
        <p class="text-gray-600 mb-4">
          Lakukan absensi masuk atau keluar dengan pengenalan wajah
        </p>
        
        <template #footer>
          <Button
            variant="primary"
            @click="navigateToAttendance"
            :icon-left="CameraIcon"
            class="w-full"
          >
            Buka Absensi
          </Button>
        </template>
      </Card>

      <!-- Leave Request Card -->
      <Card class="hover:shadow-lg transition-shadow duration-300">
        <template #header>
          <div class="flex items-center">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
              <DocumentPlusIcon class="w-6 h-6 text-yellow-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Pengajuan Izin</h3>
          </div>
        </template>
        
        <p class="text-gray-600 mb-4">
          Ajukan izin sakit, cuti, atau keperluan lainnya
        </p>
        
        <template #footer>
          <Button
            variant="secondary"
            @click="navigateToLeaveForm"
            :icon-left="DocumentPlusIcon"
            class="w-full"
          >
            Ajukan Izin
          </Button>
        </template>
      </Card>

      <!-- Analytics Card (Admin Only) -->
      <Card 
        v-if="authStore.isAdmin" 
        class="hover:shadow-lg transition-shadow duration-300"
      >
        <template #header>
          <div class="flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
              <ChartBarIcon class="w-6 h-6 text-green-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Analytics</h3>
          </div>
        </template>
        
        <p class="text-gray-600 mb-4">
          Lihat laporan dan analisis kehadiran karyawan
        </p>
        
        <template #footer>
          <Button
            variant="success"
            @click="navigateToAnalytics"
            :icon-left="ChartBarIcon"
            class="w-full"
          >
            Lihat Analytics
          </Button>
        </template>
      </Card>
    </div>

    <!-- Recent Activity Timeline -->
    <Card>
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <ClockIcon class="w-6 h-6 text-gray-400 mr-2" />
            <h3 class="text-lg font-semibold text-gray-900">Aktivitas Terkini</h3>
          </div>
          <Button variant="ghost" size="sm" @click="refreshActivities">
            <ArrowPathIcon class="w-4 h-4" />
          </Button>
        </div>
      </template>

      <div v-if="isLoadingActivities" class="flex justify-center py-8">
        <LoadingSpinner size="md" text="Memuat aktivitas..." />
      </div>

      <div v-else-if="recentActivities.length === 0" class="text-center py-8 text-gray-500">
        <ClockIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
        <p>Belum ada aktivitas hari ini</p>
      </div>

      <div v-else class="space-y-4">
        <TransitionGroup
          name="activity"
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="transform translate-y-4 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="transform translate-y-0 opacity-100"
          leave-to-class="transform translate-y-4 opacity-0"
        >
          <div
            v-for="activity in recentActivities"
            :key="activity.id"
            class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200"
          >
            <div :class="getActivityIconClass(activity.type)" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
              <component :is="getActivityIcon(activity.type)" class="w-4 h-4" />
            </div>
            <div class="ml-3 flex-1">
              <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
              <p class="text-xs text-gray-500">{{ formatRelativeTime(activity.timestamp) }}</p>
            </div>
            <Badge :variant="getActivityBadgeVariant(activity.type)">
              {{ activity.status }}
            </Badge>
          </div>
        </TransitionGroup>
      </div>
    </Card>

    <!-- Recent Attendance Table (Admin Only) -->
    <Card v-if="authStore.isAdmin">
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Absensi Terkini</h3>
          <Button
            variant="ghost"
            size="sm"
            @click="navigateToAttendanceList"
          >
            Lihat Semua
          </Button>
        </div>
      </template>

      <div v-if="isLoadingAttendances">
        <LoadingSpinner size="md" text="Memuat data absensi..." />
      </div>

      <Table
        v-else
        :columns="attendanceColumns"
        :data="recentAttendances"
        :loading="isLoadingAttendances"
        show-pagination="false"
      >
        <template #employee="{ row }">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
              <span class="text-xs font-medium text-gray-600">
                {{ getInitials(row.employee?.name) }}
              </span>
            </div>
            <span class="font-medium text-gray-900">{{ row.employee?.name }}</span>
          </div>
        </template>

        <template #status="{ row }">
          <Badge :variant="getStatusBadgeVariant(row.status)">
            {{ getStatusText(row.status) }}
          </Badge>
        </template>
      </Table>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { attendanceApi } from '@/services/api'
import { useToast } from 'vue-toastification'
import {
  ClockIcon,
  CalendarDaysIcon,
  DocumentTextIcon,
  CameraIcon,
  DocumentPlusIcon,
  ChartBarIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'
import { 
  MetricCard, 
  Card, 
  Button, 
  LoadingSpinner, 
  Table, 
  Badge 
} from '@/components/ui'
import type { Attendance } from '@/types'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// State
const isInitialLoading = ref(true)
const isLoadingStats = ref(false)
const isLoadingActivities = ref(false)
const isLoadingAttendances = ref(false)
const recentAttendances = ref<Attendance[]>([])
const recentActivities = ref<any[]>([])
const monthlyStats = ref({ present: 18, workDays: 22 })
const pendingLeaves = ref(3)
const productivityScore = ref(87)
const currentTime = ref(new Date())

// Timer for real-time updates
let timeInterval: number | null = null

// Computed properties
const todayTrend = computed(() => ({ value: 0, direction: 'neutral' as const }))
const monthlyTrend = computed(() => ({ 
  value: Math.round((monthlyStats.value.present / monthlyStats.value.workDays) * 100 - 80), 
  direction: 'up' as const 
}))
const productivityTrend = computed(() => ({ value: 5, direction: 'up' as const }))

const getCurrentDateString = () => {
  return currentTime.value.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getCurrentTime = () => {
  return currentTime.value.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const getTodayStatus = () => {
  const hour = currentTime.value.getHours()
  if (hour < 9) return 'Belum Masuk'
  if (hour < 17) return 'Sedang Bekerja'
  return 'Sudah Pulang'
}

// Table columns for attendance
const attendanceColumns = [
  { key: 'employee', label: 'Pegawai' },
  { key: 'date', label: 'Tanggal', format: (value: string) => formatDate(value) },
  { key: 'clock_in', label: 'Masuk', format: (value: string) => value || '-' },
  { key: 'clock_out', label: 'Keluar', format: (value: string) => value || '-' },
  { key: 'status', label: 'Status' },
]

// Methods
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID')
}

const formatRelativeTime = (timestamp: string) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diff = now.getTime() - time.getTime()
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 1) return 'Baru saja'
  if (minutes < 60) return `${minutes} menit yang lalu`
  
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} jam yang lalu`
  
  const days = Math.floor(hours / 24)
  return `${days} hari yang lalu`
}

const getInitials = (name?: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'present': return 'success'
    case 'late': return 'warning'
    case 'absent': return 'danger'
    default: return 'gray'
  }
}

const getStatusText = (status: string) => {
  switch (status) {
    case 'present': return 'Hadir'
    case 'late': return 'Terlambat'
    case 'absent': return 'Tidak Hadir'
    default: return 'Unknown'
  }
}

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'attendance': return ClockIcon
    case 'leave': return DocumentTextIcon
    case 'overtime': return ChartBarIcon
    default: return ClockIcon
  }
}

const getActivityIconClass = (type: string) => {
  switch (type) {
    case 'attendance': return 'bg-blue-100 text-blue-600'
    case 'leave': return 'bg-yellow-100 text-yellow-600'
    case 'overtime': return 'bg-green-100 text-green-600'
    default: return 'bg-gray-100 text-gray-600'
  }
}

const getActivityBadgeVariant = (type: string) => {
  switch (type) {
    case 'attendance': return 'primary'
    case 'leave': return 'warning'
    case 'overtime': return 'success'
    default: return 'gray'
  }
}

// Navigation methods
const navigateToAttendance = () => router.push('/attendance')
const navigateToLeaves = () => router.push('/leaves')
const navigateToLeaveForm = () => router.push('/leaves/create')
const navigateToAnalytics = () => router.push('/analytics')
const navigateToAttendanceList = () => router.push('/attendance/list')

// Data fetching
const fetchDashboardData = async () => {
  try {
    isLoadingStats.value = true
    isLoadingActivities.value = true
    
    if (authStore.isAdmin) {
      isLoadingAttendances.value = true
      const response = await attendanceApi.getAttendances()
      recentAttendances.value = response.data.slice(0, 5) // Take first 5 records
      isLoadingAttendances.value = false
    }
    
    // Simulate API calls for other data
    await new Promise(resolve => setTimeout(resolve, 800))
    
    // Mock recent activities
    recentActivities.value = [
      {
        id: 1,
        type: 'attendance',
        description: 'Check-in berhasil',
        timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
        status: 'Success'
      },
      {
        id: 2,
        type: 'leave',
        description: 'Pengajuan izin sakit disetujui',
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        status: 'Approved'
      }
    ]
    
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
    toast.error('Gagal memuat data dashboard')
  } finally {
    isLoadingStats.value = false
    isLoadingActivities.value = false
    isInitialLoading.value = false
  }
}

const refreshActivities = async () => {
  isLoadingActivities.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 500))
    // Refresh activities logic here
    toast.success('Aktivitas berhasil diperbarui')
  } catch (error) {
    toast.error('Gagal memperbarui aktivitas')
  } finally {
    isLoadingActivities.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchDashboardData()
  
  // Update time every second
  timeInterval = window.setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})
</script>

<style scoped>
/* Activity transition styles */
.activity-enter-active,
.activity-leave-active {
  transition: all 0.3s ease;
}

.activity-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.activity-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Hover effects for cards */
.hover\:shadow-lg:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

/* Custom scrollbar for activities */
.activity-scroll::-webkit-scrollbar {
  width: 4px;
}

.activity-scroll::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.activity-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.activity-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style> 