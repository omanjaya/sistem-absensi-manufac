<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Pegawai' : 'Tambah Pegawai' }}
        </h1>
      </div>
      
      <form @submit.prevent="saveEmployee" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Basic Information -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Informasi Dasar</h3>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Masukkan nama lengkap"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="contoh@email.com"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ID Pegawai *</label>
              <input
                v-model="form.employee_id"
                type="text"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="EMP001"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="08123456789"
              />
            </div>
          </div>

          <!-- Employment Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Detail Pekerjaan</h3>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
              <select
                v-model="form.role"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Pilih Role</option>
                <option value="admin">Admin</option>
                <option value="employee">Pegawai</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Posisi</label>
              <input
                v-model="form.position"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Masukkan posisi"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
              <input
                v-model="form.department"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Masukkan departemen"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bergabung</label>
              <input
                v-model="form.join_date"
                type="date"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Gaji Pokok</label>
              <input
                v-model="form.basic_salary"
                type="number"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="5000000"
              />
            </div>

            <div class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
              <label class="ml-2 text-sm text-gray-700">Status Aktif</label>
            </div>
          </div>
        </div>

        <!-- Password Section (for new employee) -->
        <div v-if="!isEdit" class="mt-6 border-t pt-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Password</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
              <input
                v-model="form.password"
                type="password"
                :required="!isEdit"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Minimal 8 karakter"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password *</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                :required="!isEdit"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Ulangi password"
              />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex justify-end space-x-3">
          <router-link
            to="/employees"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Batal
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ loading ? 'Menyimpan...' : (isEdit ? 'Update' : 'Simpan') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { employeeApi } from '@/services/api'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const loading = ref(false)
const employeeId = computed(() => route.params.id as string)
const isEdit = computed(() => !!employeeId.value)

const form = reactive({
  name: '',
  email: '',
  employee_id: '',
  phone: '',
  role: 'employee',
  position: '',
  department: '',
  join_date: '',
  basic_salary: null as number | null,
  is_active: true,
  password: '',
  password_confirmation: ''
})

const fetchEmployee = async () => {
  if (!isEdit.value) return
  
  try {
    loading.value = true
    const employee = await employeeApi.getEmployee(parseInt(employeeId.value))
    
    Object.assign(form, {
      name: employee.name,
      email: employee.email,
      employee_id: employee.employee_id,
      phone: employee.phone || '',
      role: employee.role,
      position: employee.position || '',
      department: employee.department || '',
      join_date: employee.join_date || '',
      basic_salary: employee.basic_salary || null,
      is_active: employee.is_active || true
    })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memuat data pegawai')
    router.push('/employees')
  } finally {
    loading.value = false
  }
}

const saveEmployee = async () => {
  // Validate passwords match for new employee
  if (!isEdit.value && form.password !== form.password_confirmation) {
    toast.error('Password dan konfirmasi password tidak cocok')
    return
  }

  try {
    loading.value = true
    
    const employeeData = {
      name: form.name,
      email: form.email,
      employee_id: form.employee_id,
      phone: form.phone,
      role: form.role,
      position: form.position,
      department: form.department,
      join_date: form.join_date,
      basic_salary: form.basic_salary,
      is_active: form.is_active,
      ...((!isEdit.value) && { 
        password: form.password, 
        password_confirmation: form.password_confirmation 
      })
    }

    if (isEdit.value) {
      await employeeApi.updateEmployee(parseInt(employeeId.value), employeeData)
      toast.success('Data pegawai berhasil diperbarui')
    } else {
      await employeeApi.createEmployee(employeeData)
      toast.success('Pegawai baru berhasil ditambahkan')
    }
    
    router.push('/employees')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan data pegawai')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    fetchEmployee()
  }
})
</script> 