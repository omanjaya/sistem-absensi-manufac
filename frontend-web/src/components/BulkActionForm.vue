<template>
  <div class="space-y-6">
    <div class="text-center">
      <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full">
        <CogIcon class="w-6 h-6 text-blue-600" />
      </div>
      <h3 class="mt-4 text-lg font-medium text-gray-900">Aksi Bulk untuk {{ employees.length }} Pegawai</h3>
      <p class="mt-2 text-sm text-gray-500">
        Pilih aksi yang ingin diterapkan untuk semua pegawai yang dipilih
      </p>
    </div>

    <!-- Selected Employees Preview -->
    <div class="bg-gray-50 rounded-lg p-4">
      <h4 class="text-sm font-medium text-gray-900 mb-3">Pegawai yang Dipilih:</h4>
      <div class="space-y-2 max-h-32 overflow-y-auto">
        <div v-for="employee in employees.slice(0, 3)" :key="employee.id" class="flex items-center text-sm">
          <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
            {{ getInitials(employee.name) }}
          </div>
          <span class="text-gray-900">{{ employee.name }}</span>
          <span class="ml-auto text-gray-500">{{ employee.department || 'Tidak ada departemen' }}</span>
        </div>
        <div v-if="employees.length > 3" class="text-xs text-gray-500 text-center pt-2">
          dan {{ employees.length - 3 }} pegawai lainnya...
        </div>
      </div>
    </div>

    <!-- Action Selection -->
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Pilih Aksi yang Akan Dilakukan
        </label>
        <select 
          v-model="selectedAction" 
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">-- Pilih Aksi --</option>
          <option value="status">Ubah Status</option>
          <option value="department">Ubah Bidang/Mata Pelajaran</option>
          <option value="salary">Ubah Gaji Pokok</option>
          <option value="position">Ubah Jabatan</option>
        </select>
      </div>

      <!-- Status Change Form -->
      <div v-if="selectedAction === 'status'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Status Baru
          </label>
          <select 
            v-model="actionData.status" 
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">-- Pilih Status --</option>
            <option value="active">Aktif</option>
            <option value="inactive">Non-aktif</option>
          </select>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
            <div class="ml-3">
              <p class="text-sm text-yellow-800">
                Perubahan status akan berlaku untuk semua {{ employees.length }} pegawai yang dipilih.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Department Change Form -->
      <div v-if="selectedAction === 'department'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Bidang/Mata Pelajaran Baru
          </label>
          <select 
            v-model="actionData.department" 
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">-- Pilih Bidang --</option>
            <option v-for="dept in departmentOptions" :key="dept.value" :value="dept.value">
              {{ dept.label }}
            </option>
          </select>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <div class="flex">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
            <div class="ml-3">
              <p class="text-sm text-blue-800">
                Perubahan bidang akan mempengaruhi laporan kehadiran dan penggajian pegawai.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Salary Change Form -->
      <div v-if="selectedAction === 'salary'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipe Perubahan Gaji
          </label>
          <select 
            v-model="actionData.salaryType" 
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">-- Pilih Tipe --</option>
            <option value="fixed">Gaji Tetap (Rp)</option>
            <option value="percentage">Persentase (%)</option>
          </select>
        </div>
        
        <div v-if="actionData.salaryType === 'fixed'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Gaji Pokok Baru (Rp)
          </label>
          <input 
            v-model.number="actionData.salary" 
            type="number" 
            placeholder="3000000"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>

        <div v-if="actionData.salaryType === 'percentage'">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Persentase Kenaikan/Penurunan (%)
          </label>
          <input 
            v-model.number="actionData.salaryPercentage" 
            type="number" 
            placeholder="10"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
          <p class="mt-1 text-xs text-gray-500">
            Gunakan nilai positif untuk kenaikan, negatif untuk penurunan
          </p>
        </div>
      </div>

      <!-- Position Change Form -->
      <div v-if="selectedAction === 'position'" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Jabatan Baru
          </label>
          <input 
            v-model="actionData.position" 
            type="text" 
            placeholder="Wali Kelas, Guru Senior, Staff Admin, dll"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div v-if="selectedAction" class="bg-gray-50 rounded-lg p-4">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Ringkasan Perubahan:</h4>
      <ul class="text-sm text-gray-600 space-y-1">
        <li>• {{ employees.length }} pegawai akan diproses</li>
        <li v-if="selectedAction === 'status'">• Status akan diubah menjadi: {{ actionData.status === 'active' ? 'Aktif' : 'Non-aktif' }}</li>
        <li v-if="selectedAction === 'department'">• Bidang akan diubah menjadi: {{ actionData.department }}</li>
        <li v-if="selectedAction === 'salary' && actionData.salaryType === 'fixed'">• Gaji pokok akan diubah menjadi: Rp {{ formatCurrency(actionData.salary) }}</li>
        <li v-if="selectedAction === 'salary' && actionData.salaryType === 'percentage'">• Gaji akan {{ actionData.salaryPercentage > 0 ? 'naik' : 'turun' }} sebesar {{ Math.abs(actionData.salaryPercentage) }}%</li>
        <li v-if="selectedAction === 'position'">• Jabatan akan diubah menjadi: {{ actionData.position }}</li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
      <Button variant="ghost" @click="$emit('cancel')">
        Batal
      </Button>
      <Button 
        variant="primary" 
        @click="executeAction"
        :disabled="!canExecute"
        :loading="loading"
      >
        <CheckIcon class="w-4 h-4" />
        Terapkan Perubahan
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Button } from '@/components/ui'
import { 
  CogIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  CheckIcon 
} from '@heroicons/vue/24/outline'

interface Employee {
  id: number
  name: string
  department?: string
  status: string
  salary?: number
}

interface Props {
  employees: Employee[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  action: [action: string, data: any]
  cancel: []
}>()

// State
const loading = ref(false)
const selectedAction = ref('')
const actionData = reactive({
  status: '',
  department: '',
  salary: 0,
  salaryType: '',
  salaryPercentage: 0,
  position: ''
})

// Department options for school context
const departmentOptions = [
  { value: 'Matematika', label: 'Matematika' },
  { value: 'Bahasa Indonesia', label: 'Bahasa Indonesia' },
  { value: 'Bahasa Inggris', label: 'Bahasa Inggris' },
  { value: 'IPA', label: 'IPA (Fisika, Kimia, Biologi)' },
  { value: 'IPS', label: 'IPS (Sejarah, Geografi, Ekonomi)' },
  { value: 'Seni Budaya', label: 'Seni Budaya' },
  { value: 'Olahraga', label: 'Pendidikan Jasmani' },
  { value: 'Agama', label: 'Pendidikan Agama' },
  { value: 'Wali Kelas', label: 'Wali Kelas' },
  { value: 'Kepala Sekolah', label: 'Kepala Sekolah' },
  { value: 'Wakil Kepala Sekolah', label: 'Wakil Kepala Sekolah' },
  { value: 'Staff TU', label: 'Staff Tata Usaha' },
  { value: 'Staff Perpustakaan', label: 'Staff Perpustakaan' },
  { value: 'Staff Laboratorium', label: 'Staff Laboratorium' },
  { value: 'Satpam', label: 'Satuan Pengamanan' },
  { value: 'Cleaning Service', label: 'Petugas Kebersihan' }
]

// Computed
const canExecute = computed(() => {
  if (!selectedAction.value) return false
  
  switch (selectedAction.value) {
    case 'status':
      return !!actionData.status
    case 'department':
      return !!actionData.department
    case 'salary':
      return actionData.salaryType === 'fixed' ? actionData.salary > 0 : 
             actionData.salaryType === 'percentage' ? actionData.salaryPercentage !== 0 : false
    case 'position':
      return !!actionData.position.trim()
    default:
      return false
  }
})

// Methods
const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID').format(amount)
}

const executeAction = async () => {
  if (!canExecute.value) return
  
  loading.value = true
  
  try {
    let data: any = {}
    
    switch (selectedAction.value) {
      case 'status':
        data = { status: actionData.status }
        break
      case 'department':
        data = { department: actionData.department }
        break
      case 'salary':
        if (actionData.salaryType === 'fixed') {
          data = { salary: actionData.salary }
        } else {
          data = { 
            salaryPercentage: actionData.salaryPercentage,
            salaryType: 'percentage'
          }
        }
        break
      case 'position':
        data = { position: actionData.position }
        break
    }
    
    emit('action', selectedAction.value, data)
  } finally {
    loading.value = false
  }
}
</script> 