<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manajemen Jadwal Guru</h1>
        <p class="mt-2 text-gray-600">Kelola jadwal mengajar guru dan deteksi konflik jadwal</p>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-500 text-white">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Jadwal</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.totalSchedules }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-500 text-white">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Aktif Hari Ini</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.todaySchedules }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-500 text-white">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.94-.833-2.71 0L3.104 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Konflik Jadwal</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.conflicts }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-500 text-white">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Total Guru</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.totalTeachers }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Guru</label>
            <select v-model="filters.user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Semua Guru</option>
              <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                {{ teacher.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hari</label>
            <select v-model="filters.day_of_week" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Semua Hari</option>
              <option value="monday">Senin</option>
              <option value="tuesday">Selasa</option>
              <option value="wednesday">Rabu</option>
              <option value="thursday">Kamis</option>
              <option value="friday">Jumat</option>
              <option value="saturday">Sabtu</option>
              <option value="sunday">Minggu</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select v-model="filters.status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">Semua Status</option>
              <option value="active">Aktif</option>
              <option value="inactive">Tidak Aktif</option>
              <option value="cancelled">Dibatalkan</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
            <input 
              v-model="filters.subject" 
              type="text" 
              placeholder="Cari mata pelajaran..."
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
        </div>
      </div>

      <!-- Schedule Table -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Daftar Jadwal</h2>
        </div>
        
        <!-- Simple table structure for demo -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guru</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ruangan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="schedule in schedules" :key="schedule.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <span class="text-sm font-medium text-gray-700">{{ getInitials(schedule.teacher_name) }}</span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ schedule.teacher_name }}</div>
                      <div class="text-sm text-gray-500">{{ schedule.employee_id }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ schedule.subject }}</div>
                  <div class="text-sm text-gray-500">{{ schedule.class_name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-900">{{ dayNames[schedule.day_of_week] }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-900">{{ schedule.start_time }} - {{ schedule.end_time }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-900">{{ schedule.room_number }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusBadgeClass(schedule.status)">
                    {{ getStatusLabel(schedule.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center space-x-2">
                    <button 
                      @click="viewSchedule(schedule)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      View
                    </button>
                    <button 
                      @click="editSchedule(schedule)"
                      class="text-yellow-600 hover:text-yellow-900"
                    >
                      Edit
                    </button>
                    <button 
                      @click="deleteSchedule(schedule)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              
              <!-- No data message -->
              <tr v-if="schedules.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                  <div class="flex flex-col items-center">
                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Belum ada jadwal yang dibuat</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Action Buttons -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button 
            @click="createSchedule"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center space-x-2"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span>Tambah Jadwal</span>
          </button>
          
          <button 
            @click="exportSchedules"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center space-x-2"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Export Excel</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

// Reactive data
const stats = reactive({
  totalSchedules: 0,
  todaySchedules: 0,
  conflicts: 0,
  totalTeachers: 0
})

const filters = reactive({
  user_id: '',
  day_of_week: '',
  status: '',
  subject: ''
})

const schedules = ref<any[]>([])
const teachers = ref<any[]>([])

const dayNames = {
  monday: 'Senin',
  tuesday: 'Selasa', 
  wednesday: 'Rabu',
  thursday: 'Kamis',
  friday: 'Jumat',
  saturday: 'Sabtu',
  sunday: 'Minggu'
}

// Methods
const fetchStats = async () => {
  try {
    // Mock data - replace with actual API call
    stats.totalSchedules = 45
    stats.todaySchedules = 12
    stats.conflicts = 2
    stats.totalTeachers = 15
  } catch (error) {
    console.error('Failed to fetch stats:', error)
  }
}

const fetchTeachers = async () => {
  try {
    // Mock data - replace with actual API call
    teachers.value = [
      { id: 1, name: 'John Teacher', employee_id: 'TCH001' },
      { id: 2, name: 'Jane Doe', employee_id: 'TCH002' },
      { id: 3, name: 'Bob Wilson', employee_id: 'TCH003' }
    ]
  } catch (error) {
    console.error('Failed to fetch teachers:', error)
  }
}

const fetchSchedules = async () => {
  try {
    // Mock data - replace with actual API call
    schedules.value = [
      {
        id: 1,
        teacher_name: 'John Teacher',
        employee_id: 'TCH001',
        subject: 'Matematika',
        class_name: 'XII IPA 1',
        day_of_week: 'monday',
        start_time: '08:00',
        end_time: '09:30',
        room_number: 'A101',
        status: 'active'
      },
      {
        id: 2,
        teacher_name: 'Jane Doe',
        employee_id: 'TCH002',
        subject: 'Bahasa Indonesia',
        class_name: 'XI IPS 2',
        day_of_week: 'tuesday',
        start_time: '10:00',
        end_time: '11:30',
        room_number: 'B205',
        status: 'active'
      }
    ]
  } catch (error) {
    console.error('Failed to fetch schedules:', error)
  }
}

const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getStatusBadgeClass = (status: string) => {
  const baseClasses = 'px-2 py-1 text-xs rounded-full'
  switch (status) {
    case 'active':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'inactive':
      return `${baseClasses} bg-gray-100 text-gray-800`
    case 'cancelled':
      return `${baseClasses} bg-red-100 text-red-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'active': return 'Aktif'
    case 'inactive': return 'Tidak Aktif'
    case 'cancelled': return 'Dibatalkan'
    default: return status
  }
}

const createSchedule = () => {
  alert('Fungsi Create Schedule - akan dialihkan ke form create')
}

const viewSchedule = (schedule: any) => {
  alert(`View Schedule: ${schedule.subject} - ${schedule.teacher_name}`)
}

const editSchedule = (schedule: any) => {
  alert(`Edit Schedule: ${schedule.subject} - ${schedule.teacher_name}`)
}

const deleteSchedule = (schedule: any) => {
  if (confirm(`Hapus jadwal ${schedule.subject} - ${schedule.teacher_name}?`)) {
    alert('Schedule deleted (demo)')
  }
}

const exportSchedules = () => {
  alert('Export to Excel functionality (demo)')
}

// Lifecycle
onMounted(() => {
  fetchStats()
  fetchTeachers()
  fetchSchedules()
})
</script> 