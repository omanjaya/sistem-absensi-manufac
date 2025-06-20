<template>
  <div class="container mx-auto px-4 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profile Info -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Informasi Profil</h2>
          </div>
          <div class="p-6 text-center">
            <div class="w-24 h-24 mx-auto mb-4 bg-gray-300 rounded-full flex items-center justify-center">
              <span class="text-2xl font-semibold text-gray-700">
                {{ authStore.user?.name?.charAt(0).toUpperCase() }}
              </span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900">{{ authStore.user?.name }}</h3>
            <p class="text-gray-600">{{ authStore.user?.email }}</p>
            <p class="text-sm text-gray-500 mt-1">ID: {{ authStore.user?.employee_id }}</p>
            <div class="mt-4">
              <span :class="getRoleClass(authStore.user?.role)" class="px-3 py-1 text-sm font-medium rounded-full">
                {{ getRoleText(authStore.user?.role) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Profile Form -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Edit Profil</h2>
          </div>
          
          <form @submit.prevent="updateProfile" class="p-6">
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                  <input
                    v-model="profileForm.phone"
                    type="tel"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">ID Pegawai</label>
                  <input
                    :value="profileForm.employee_id"
                    type="text"
                    readonly
                    class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm"
                  />
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="updating"
                  class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
                >
                  {{ updating ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-lg shadow mt-6">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Ubah Password</h2>
          </div>
          
          <form @submit.prevent="changePassword" class="p-6">
            <div class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                  <input
                    v-model="passwordForm.new_password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                  <input
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="changingPassword"
                  class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700 disabled:opacity-50 transition-colors"
                >
                  {{ changingPassword ? 'Mengubah...' : 'Ubah Password' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()

const updating = ref(false)
const changingPassword = ref(false)

const profileForm = reactive({
  name: '',
  email: '',
  phone: '',
  employee_id: ''
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const updateProfile = async () => {
  try {
    updating.value = true

    const updatedUser = await authApi.updateProfile({
      name: profileForm.name,
      email: profileForm.email,
      phone: profileForm.phone
    })

    // Update local auth state
    authStore.setUser(updatedUser)
    
    toast.success('Profil berhasil diperbarui')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memperbarui profil')
  } finally {
    updating.value = false
  }
}

const changePassword = async () => {
  if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
    toast.error('Password baru dan konfirmasi password tidak cocok')
    return
  }

  try {
    changingPassword.value = true

    await authApi.changePassword({
      current_password: passwordForm.current_password,
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.new_password_confirmation
    })

    // Reset form
    Object.assign(passwordForm, {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    })

    toast.success('Password berhasil diubah')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal mengubah password')
  } finally {
    changingPassword.value = false
  }
}

const getRoleClass = (role?: string) => {
  const classes = {
    'admin': 'bg-purple-100 text-purple-800',
    'employee': 'bg-blue-100 text-blue-800'
  }
  return classes[role as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

const getRoleText = (role?: string) => {
  const roleMap = {
    'admin': 'Administrator',
    'employee': 'Pegawai'
  }
  return roleMap[role as keyof typeof roleMap] || role
}

onMounted(() => {
  if (authStore.user) {
    Object.assign(profileForm, {
      name: authStore.user.name,
      email: authStore.user.email,
      phone: authStore.user.phone || '',
      employee_id: authStore.user.employee_id
    })
  }
})
</script> 