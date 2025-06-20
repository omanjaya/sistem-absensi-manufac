<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-900">Daftar Izin/Cuti</h1>
        <router-link
          to="/leaves/create"
          class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
        >
          Ajukan Izin
        </router-link>
      </div>
      
      <div class="p-6">
        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="filters.status"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Semua Status</option>
              <option value="pending">Menunggu</option>
              <option value="approved">Disetujui</option>
              <option value="rejected">Ditolak</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
            <select
              v-model="filters.type"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Semua Jenis</option>
              <option value="sick">Sakit</option>
              <option value="personal">Pribadi</option>
              <option value="annual">Tahunan</option>
              <option value="emergency">Darurat</option>
            </select>
          </div>
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
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-600">Memuat data...</p>
        </div>

        <!-- Data Table -->
        <div v-else-if="leaves.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pegawai
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Jenis
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tanggal
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Durasi
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="leave in leaves" :key="leave.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ leave.user?.name }}</div>
                  <div class="text-sm text-gray-500">{{ leave.user?.employee_id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getTypeClass(leave.type)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ getTypeText(leave.type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div>{{ formatDate(leave.start_date) }}</div>
                  <div v-if="leave.end_date !== leave.start_date" class="text-gray-500">
                    s/d {{ formatDate(leave.end_date) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ calculateDuration(leave.start_date, leave.end_date) }} hari
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(leave.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ getStatusText(leave.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    v-if="leave.status === 'pending' && authStore.isAdmin"
                    @click="approveLeave(leave)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Setujui
                  </button>
                  <button
                    v-if="leave.status === 'pending' && authStore.isAdmin"
                    @click="rejectLeave(leave)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Tolak
                  </button>
                  <router-link
                    v-if="leave.status === 'pending' && !authStore.isAdmin"
                    :to="`/leaves/${leave.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- No Data -->
        <div v-else class="text-center py-8">
          <p class="text-gray-500">Tidak ada data izin ditemukan.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { leaveApi } from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const leaves = ref([])

const filters = reactive({
  status: '',
  type: '',
  startDate: '',
  endDate: ''
})

const fetchLeaves = async () => {
  try {
    loading.value = true
    const response = await leaveApi.getLeaves(filters)
    leaves.value = response.data || []
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memuat data izin')
  } finally {
    loading.value = false
  }
}

const approveLeave = async (leave: any) => {
  try {
    await leaveApi.approveLeave(leave.id)
    leave.status = 'approved'
    toast.success('Izin berhasil disetujui')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal menyetujui izin')
  }
}

const rejectLeave = async (leave: any) => {
  try {
    await leaveApi.rejectLeave(leave.id)
    leave.status = 'rejected'
    toast.success('Izin berhasil ditolak')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal menolak izin')
  }
}

const getTypeClass = (type: string) => {
  const classes = {
    'sick': 'bg-red-100 text-red-800',
    'personal': 'bg-blue-100 text-blue-800',
    'annual': 'bg-green-100 text-green-800',
    'emergency': 'bg-orange-100 text-orange-800'
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getTypeText = (type: string) => {
  const typeMap = {
    'sick': 'Sakit',
    'personal': 'Pribadi',
    'annual': 'Tahunan',
    'emergency': 'Darurat'
  }
  return typeMap[type as keyof typeof typeMap] || type
}

const getStatusClass = (status: string) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status: string) => {
  const statusMap = {
    'pending': 'Menunggu',
    'approved': 'Disetujui',
    'rejected': 'Ditolak'
  }
  return statusMap[status as keyof typeof statusMap] || status
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const calculateDuration = (startDate: string, endDate: string) => {
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end.getTime() - start.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1
}

onMounted(() => {
  fetchLeaves()
})

// Watch filters for auto-refresh
watch(filters, () => {
  fetchLeaves()
}, { deep: true })
</script> 