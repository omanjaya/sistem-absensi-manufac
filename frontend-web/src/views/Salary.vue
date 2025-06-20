<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-900">Laporan Gaji</h1>
        <div class="flex space-x-3">
          <button
            @click="generateSalary"
            :disabled="loading"
            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50 transition-colors"
          >
            Generate Gaji
          </button>
          <button
            @click="exportSalaries"
            :disabled="loading"
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            Export Excel
          </button>
        </div>
      </div>
      
      <div class="p-6">
        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
            <select
              v-model="filters.month"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option v-for="month in months" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
            <select
              v-model="filters.year"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
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
        <div v-else-if="salaries.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pegawai
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Periode
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Gaji Pokok
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tunjangan
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Lembur
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Potongan
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="salary in salaries" :key="salary.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ salary.user?.name }}</div>
                  <div class="text-sm text-gray-500">{{ salary.user?.employee_id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatPeriod(salary.month, salary.year) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(salary.basic_salary) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(salary.allowances) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(salary.overtime_amount) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(salary.deductions) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                  {{ formatCurrency(salary.net_salary) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- No Data -->
        <div v-else class="text-center py-8">
          <p class="text-gray-500">Tidak ada data gaji ditemukan.</p>
          <p class="text-sm text-gray-400 mt-1">
            Klik "Generate Gaji" untuk membuat slip gaji bulan ini.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { salaryApi, employeeApi, downloadFile } from '@/services/api'

const toast = useToast()

const loading = ref(false)
const salaries = ref([])
const employees = ref([])

const filters = reactive({
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  employeeId: ''
})

const months = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' }
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = currentYear - 2; i <= currentYear + 1; i++) {
    years.push(i)
  }
  return years
})

const fetchSalaries = async () => {
  try {
    loading.value = true
    const response = await salaryApi.getSalaries(filters)
    salaries.value = response.data || []
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memuat data gaji')
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

const generateSalary = async () => {
  try {
    loading.value = true
    await salaryApi.generateSalary(filters.month.toString(), filters.year)
    toast.success('Slip gaji berhasil digenerate')
    await fetchSalaries()
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal generate slip gaji')
  } finally {
    loading.value = false
  }
}

const exportSalaries = async () => {
  try {
    loading.value = true
    const blob = await salaryApi.exportSalaries(filters)
    
    const filename = `laporan-gaji-${months.find(m => m.value === filters.month)?.label}-${filters.year}.xlsx`
    downloadFile(blob, filename)
    
    toast.success('Laporan gaji berhasil diunduh')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal mengunduh laporan gaji')
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatPeriod = (month: number, year: number) => {
  const monthName = months.find(m => m.value === month)?.label
  return `${monthName} ${year}`
}

onMounted(() => {
  fetchEmployees()
  fetchSalaries()
})

// Watch filters for auto-refresh
watch(filters, () => {
  fetchSalaries()
}, { deep: true })
</script> 