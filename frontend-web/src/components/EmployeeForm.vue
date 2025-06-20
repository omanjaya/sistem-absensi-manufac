<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Basic Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Informasi Dasar</h3>
        
        <Form.Input
          v-model="form.name"
          label="Nama Lengkap"
          placeholder="Masukkan nama lengkap"
          required
          :error="errors.name"
        />

        <Form.Input
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="contoh@email.com"
          required
          :error="errors.email"
        />

        <Form.Input
          v-model="form.employee_id"
          label="NIP/ID Staff"
          placeholder="198512345678901234 atau STF001"
          required
          :error="errors.employee_id"
        />

        <Form.Input
          v-model="form.phone"
          label="No. Telepon"
          placeholder="08123456789"
          :error="errors.phone"
        />
      </div>

      <!-- Employment Details -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Detail Pekerjaan</h3>
        
        <Form.Select
          v-model="form.role"
          label="Role"
          placeholder="Pilih Role"
          :options="roleOptions"
          required
          :error="errors.role"
        />

        <Form.Input
          v-model="form.position"
          label="Jabatan/Mata Pelajaran"
          placeholder="Wali Kelas VII-A / Guru Matematika"
          :error="errors.position"
        />

        <Form.Select
          v-model="form.department"
          label="Bidang/Mata Pelajaran"
          placeholder="Pilih bidang atau mata pelajaran"
          :options="departmentOptions"
          editable
          :error="errors.department"
        />

        <Form.Input
          v-model="form.join_date"
          label="Tanggal Mulai Mengajar"
          type="date"
          required
          :error="errors.join_date"
        />
      </div>
    </div>

    <!-- Salary Information -->
    <div class="border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Gaji/Honor</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Form.Input
          v-model.number="form.basic_salary"
          label="Gaji Pokok/Honor"
          type="number"
          placeholder="3500000"
          required
          :error="errors.basic_salary"
        />

        <Form.Select
          v-model="form.status"
          label="Status"
          placeholder="Pilih Status"
          :options="statusOptions"
          required
          :error="errors.status"
        />
      </div>
    </div>

    <!-- Password Section (for new employee) -->
    <div v-if="!employee" class="border-t pt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Password</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Form.Input
          v-model="form.password"
          label="Password"
          type="password"
          placeholder="Minimal 6 karakter"
          required
          :error="errors.password"
        />
        
        <Form.Input
          v-model="form.password_confirmation"
          label="Konfirmasi Password"
          type="password"
          placeholder="Ulangi password"
          required
          :error="errors.password_confirmation"
        />
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end space-x-3 pt-6 border-t">
      <Button 
        type="button" 
        variant="secondary" 
        @click="$emit('cancel')"
      >
        Batal
      </Button>
      <Button 
        type="submit" 
        variant="primary" 
        :loading="loading"
      >
        {{ employee ? 'Update' : 'Simpan' }}
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { Form, Button } from '@/components/ui'
import type { Employee } from '@/types'

interface Props {
  employee?: Employee | null
  departments?: Array<{ value: string; label: string }>
}

const props = withDefaults(defineProps<Props>(), {
  employee: null,
  departments: () => []
})

const emit = defineEmits<{
  save: [data: any]
  cancel: []
}>()

const loading = ref(false)
const errors = reactive<Record<string, string>>({})

const form = reactive({
  name: '',
  email: '',
  employee_id: '',
  phone: '',
  role: 'employee',
  position: '',
  department: '',
  join_date: '',
  basic_salary: 5000000,
  status: 'active',
  password: '',
  password_confirmation: ''
})

// Options
const roleOptions = [
  { value: 'admin', label: 'Admin Sekolah' },
  { value: 'teacher', label: 'Guru' },
  { value: 'staff', label: 'Staff' }
]

const statusOptions = [
  { value: 'active', label: 'Aktif' },
  { value: 'inactive', label: 'Nonaktif' }
]

const departmentOptions = computed(() => [
  ...props.departments,
  { value: 'Guru Matematika', label: 'Guru Matematika' },
  { value: 'Guru Bahasa Indonesia', label: 'Guru Bahasa Indonesia' },
  { value: 'Guru Bahasa Inggris', label: 'Guru Bahasa Inggris' },
  { value: 'Guru IPA', label: 'Guru IPA' },
  { value: 'Guru IPS', label: 'Guru IPS' },
  { value: 'Guru Seni Budaya', label: 'Guru Seni Budaya' },
  { value: 'Guru Olahraga', label: 'Guru Olahraga' },
  { value: 'Guru Agama', label: 'Guru Agama' },
  { value: 'Wali Kelas', label: 'Wali Kelas' },
  { value: 'Kepala Sekolah', label: 'Kepala Sekolah' },
  { value: 'Wakil Kepala Sekolah', label: 'Wakil Kepala Sekolah' },
  { value: 'Staff TU', label: 'Staff Tata Usaha' },
  { value: 'Staff Perpustakaan', label: 'Staff Perpustakaan' },
  { value: 'Staff Laboratorium', label: 'Staff Laboratorium' },
  { value: 'Satpam', label: 'Satpam' },
  { value: 'Cleaning Service', label: 'Cleaning Service' }
])

// Methods
const validateForm = () => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.name.trim()) {
    errors.name = 'Nama lengkap wajib diisi'
  }

  if (!form.email.trim()) {
    errors.email = 'Email wajib diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Format email tidak valid'
  }

  if (!form.employee_id.trim()) {
    errors.employee_id = 'ID Pegawai wajib diisi'
  }

  if (!form.role) {
    errors.role = 'Role wajib dipilih'
  }

  if (!form.join_date) {
    errors.join_date = 'Tanggal bergabung wajib diisi'
  }

  if (!form.basic_salary || form.basic_salary < 0) {
    errors.basic_salary = 'Gaji pokok harus lebih dari 0'
  }

  if (!props.employee) {
    if (!form.password) {
      errors.password = 'Password wajib diisi'
    } else if (form.password.length < 6) {
      errors.password = 'Password minimal 6 karakter'
    }

    if (form.password !== form.password_confirmation) {
      errors.password_confirmation = 'Password konfirmasi tidak cocok'
    }
  }

  return Object.keys(errors).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return

  loading.value = true
  
  try {
    const formData = {
      name: form.name,
      email: form.email,
      employee_id: form.employee_id,
      phone: form.phone || null,
      role: form.role,
      position: form.position || null,
      department: form.department || null,
      join_date: form.join_date,
      basic_salary: form.basic_salary,
      status: form.status,
      ...((!props.employee) && {
        password: form.password,
        password_confirmation: form.password_confirmation
      })
    }

    emit('save', formData)
  } finally {
    loading.value = false
  }
}

// Load employee data if editing
watch(() => props.employee, (employee) => {
  if (employee) {
    Object.assign(form, {
      name: employee.name,
      email: employee.email,
      employee_id: employee.employee_id,
      phone: employee.phone || '',
      role: 'employee', // Default since Employee interface doesn't have role
      position: employee.position || '',
      department: employee.department || '',
      join_date: employee.join_date,
      basic_salary: 5000000, // Default since Employee interface doesn't have basic_salary
      status: employee.status
    })
  }
}, { immediate: true })

onMounted(() => {
  // Set default join date to today for new employees
  if (!props.employee) {
    form.join_date = new Date().toISOString().split('T')[0]
  }
})
</script> 