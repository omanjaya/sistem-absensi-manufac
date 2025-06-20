<template>
  <div class="space-y-6">
    <!-- Employee Header -->
    <div class="flex items-start space-x-4">
      <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
        {{ getInitials(employee.name) }}
      </div>
      <div class="flex-1">
        <h2 class="text-xl font-bold text-gray-900">{{ employee.name }}</h2>
        <p class="text-gray-600">{{ employee.position || 'No Position' }}</p>
        <p class="text-sm text-gray-500">{{ employee.department || 'No Department' }}</p>
        <div class="flex items-center space-x-2 mt-2">
          <Badge :variant="employee.status === 'active' ? 'success' : 'danger'">
            {{ employee.status === 'active' ? 'Aktif' : 'Nonaktif' }}
          </Badge>
          <Badge variant="info">{{ employee.employee_id }}</Badge>
        </div>
      </div>
      <Button variant="primary" size="sm" @click="$emit('edit', employee)">
        <PencilIcon class="w-4 h-4" />
        Edit
      </Button>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Basic Information Tab -->
      <div v-if="activeTab === 'basic'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Informasi Personal</h3>
            <div class="bg-gray-50 p-4 rounded-lg space-y-3">
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Nama Lengkap</span>
                <span class="text-sm text-gray-900">{{ employee.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Email</span>
                <span class="text-sm text-gray-900">{{ employee.email }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Telepon</span>
                <span class="text-sm text-gray-900">{{ employee.phone || 'Tidak ada' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">ID Pegawai</span>
                <span class="text-sm text-gray-900">{{ employee.employee_id }}</span>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Informasi Pekerjaan</h3>
            <div class="bg-gray-50 p-4 rounded-lg space-y-3">
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Posisi</span>
                <span class="text-sm text-gray-900">{{ employee.position || 'Tidak ada' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Departemen</span>
                <span class="text-sm text-gray-900">{{ employee.department || 'Tidak ada' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Tanggal Bergabung</span>
                <span class="text-sm text-gray-900">{{ formatDate(employee.join_date) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Masa Kerja</span>
                <span class="text-sm text-gray-900">{{ getWorkDuration(employee.join_date) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Status</span>
                <Badge :variant="employee.status === 'active' ? 'success' : 'danger'">
                  {{ employee.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Tab -->
      <div v-if="activeTab === 'stats'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <MetricCard
            label="Total Kehadiran"
            :value="stats.totalAttendance"
            description="Bulan ini"
            icon-bg="blue"
          />
          <MetricCard
            label="Jam Kerja"
            :value="`${stats.totalHours}h`"
            description="Bulan ini"
            icon-bg="green"
          />
          <MetricCard
            label="Izin Diambil"
            :value="stats.leavesTaken"
            description="Tahun ini"
            icon-bg="yellow"
          />
        </div>

        <div class="bg-white border rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Riwayat Kehadiran (7 Hari Terakhir)</h3>
          <div class="space-y-2">
            <div v-for="day in recentAttendance" :key="day.date" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
              <div>
                <span class="font-medium">{{ formatDate(day.date) }}</span>
                <span class="text-sm text-gray-500 ml-2">{{ formatDay(day.date) }}</span>
              </div>
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">
                  {{ day.clock_in || '-' }} - {{ day.clock_out || '-' }}
                </span>
                <Badge :variant="getAttendanceStatusVariant(day.status)">
                  {{ getAttendanceStatusText(day.status) }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents Tab -->
      <div v-if="activeTab === 'documents'" class="space-y-6">
        <div class="text-center py-12">
          <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada dokumen</h3>
          <p class="mt-1 text-sm text-gray-500">Fitur manajemen dokumen akan segera tersedia.</p>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end space-x-3 pt-6 border-t">
      <Button variant="secondary" @click="$emit('close')">
        Tutup
      </Button>
      <Button variant="primary" @click="$emit('edit', employee)">
        Edit Pegawai
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { PencilIcon, DocumentIcon } from '@heroicons/vue/24/outline'
import { Badge, Button, MetricCard } from '@/components/ui'
import type { Employee } from '@/types'

interface Props {
  employee: Employee
}

const props = defineProps<Props>()

const emit = defineEmits<{
  edit: [employee: Employee]
  close: []
}>()

const activeTab = ref('basic')

const tabs = [
  { id: 'basic', name: 'Informasi Dasar' },
  { id: 'stats', name: 'Statistik' },
  { id: 'documents', name: 'Dokumen' }
]

// Mock data for demonstration
const stats = computed(() => ({
  totalAttendance: 22,
  totalHours: 176,
  leavesTaken: 5
}))

const recentAttendance = computed(() => [
  { date: '2024-12-19', clock_in: '08:15', clock_out: '17:30', status: 'present' },
  { date: '2024-12-18', clock_in: '08:45', clock_out: '17:15', status: 'late' },
  { date: '2024-12-17', clock_in: '08:00', clock_out: '17:00', status: 'present' },
  { date: '2024-12-16', clock_in: null, clock_out: null, status: 'absent' },
  { date: '2024-12-15', clock_in: '08:30', clock_out: '17:45', status: 'present' },
  { date: '2024-12-14', clock_in: '08:10', clock_out: '17:20', status: 'present' },
  { date: '2024-12-13', clock_in: '08:20', clock_out: '17:00', status: 'present' }
])

// Utility functions
const getInitials = (name: string) => {
  return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID')
}

const formatDay = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID', { weekday: 'long' })
}

const getWorkDuration = (joinDate: string) => {
  const join = new Date(joinDate)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - join.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 30) return `${diffDays} hari`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} bulan`
  return `${Math.floor(diffDays / 365)} tahun`
}

const getAttendanceStatusVariant = (status: string) => {
  const variants: Record<string, string> = {
    present: 'success',
    late: 'warning',
    absent: 'danger',
    partial: 'info'
  }
  return variants[status] || 'gray'
}

const getAttendanceStatusText = (status: string) => {
  const statusTexts: Record<string, string> = {
    present: 'Hadir',
    late: 'Terlambat',
    absent: 'Tidak Hadir',
    partial: 'Sebagian'
  }
  return statusTexts[status] || status
}
</script> 