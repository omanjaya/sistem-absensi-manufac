<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-900">Daftar Absensi</h1>
      </div>
      
      <div class="p-6">
        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
            <input
              v-model="filters.startDate"
              type="date"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
            <input
              v-model="filters.endDate"
              type="date"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai</label>
            <select
              v-model="filters.employeeId"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Semua Pegawai</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ employee.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-600">Memuat data...</p>
        </div>

        <!-- Data Table -->
        <div v-else-if="attendances.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pegawai
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tanggal
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Check In
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Check Out
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Jam Kerja
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="attendance in attendances" :key="attendance.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ attendance.employee?.name }}</div>
                  <div class="text-sm text-gray-500">{{ attendance.employee?.employee_id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatDate(attendance.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ attendance.clock_in || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ attendance.clock_out || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(attendance.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ getStatusText(attendance.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ calculateWorkHours(attendance) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- No Data -->
        <div v-else class="text-center py-8">
          <p class="text-gray-500">Tidak ada data absensi ditemukan.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { attendanceApi, employeeApi } from '@/services/api'
import type { Attendance, Employee } from '@/types'

const toast = useToast()

const loading = ref(false)
const attendances = ref<Attendance[]>([])
const employees = ref<Employee[]>([])

const filters = reactive({
  startDate: '',
  endDate: '',
  employeeId: ''
})

const fetchAttendances = async () => {
  try {
    loading.value = true
    const response = await attendanceApi.getAttendances(filters as any)
    attendances.value = response.data || []
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memuat data absensi')
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await employeeApi.getEmployees()
    employees.value = response.data || []
  } catch (error: any) {
    console.error('Failed to fetch employees:', error)
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStatusClass = (status: string) => {
  const classes = {
    'present': 'bg-green-100 text-green-800',
    'late': 'bg-yellow-100 text-yellow-800',
    'absent': 'bg-red-100 text-red-800',
    'partial': 'bg-orange-100 text-orange-800'
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status: string) => {
  const statusMap = {
    'present': 'Hadir',
    'late': 'Terlambat',
    'absent': 'Tidak Hadir',
    'partial': 'Tidak Lengkap'
  }
  return statusMap[status as keyof typeof statusMap] || status
}

const calculateWorkHours = (attendance: any) => {
  if (!attendance.clock_in || !attendance.clock_out) {
    return '-'
  }
  
  const checkIn = new Date(`${attendance.date} ${attendance.clock_in}`)
  const checkOut = new Date(`${attendance.date} ${attendance.clock_out}`)
  const diff = checkOut.getTime() - checkIn.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  
  return `${hours}j ${minutes}m`
}

// Set default date range (current month)
const setDefaultDateRange = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  
  filters.startDate = firstDay.toISOString().split('T')[0]
  filters.endDate = lastDay.toISOString().split('T')[0]
}

onMounted(() => {
  setDefaultDateRange()
  fetchEmployees()
  fetchAttendances()
})

// Watch filters for auto-refresh
watch(filters, () => {
  fetchAttendances()
}, { deep: true })
</script> 