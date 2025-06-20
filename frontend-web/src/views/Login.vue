<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <!-- Loading overlay for better UX -->
    <LoadingSpinner 
      v-if="isLoading" 
      :overlay="true"
      text="Memverifikasi kredensial..."
      color="primary"
      size="lg"
    />
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-xl bg-blue-600 mb-4">
          <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">
          {{ appName }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Silakan masuk ke akun Anda
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <Card class="py-8 px-4 sm:px-10">
        <form @submit.prevent="handleSubmit" class="space-y-6" novalidate>
          <!-- Email Field with enhanced UX -->
          <Form
            v-model="form.email"
            type="email"
            label="Email"
            placeholder="nama@email.com"
            :error="errors.email"
            :loading="isValidatingEmail"
            :disabled="isLoading"
            autocomplete="email"
            required
            hint="Gunakan email yang terdaftar dalam sistem"
            @blur="validateEmail"
            @focus="clearEmailError"
          />

          <!-- Password Field with enhanced UX -->
          <Form
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            label="Password"
            placeholder="Masukkan password"
            :error="errors.password"
            :disabled="isLoading"
            autocomplete="current-password"
            required
            @blur="validatePassword"
            @focus="clearPasswordError"
            @enter="handleSubmit"
          >
            <template #icon-right>
              <button
                type="button"
                @click="togglePasswordVisibility"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                :disabled="isLoading"
              >
                <EyeIcon v-if="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" />
                <EyeSlashIcon v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" />
              </button>
            </template>
          </Form>

          <!-- Remember Me with improved styling -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                :disabled="isLoading"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors duration-200"
              />
              <label for="remember" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                Ingat saya
              </label>
            </div>
            
            <!-- Future: Forgot password link -->
            <div class="text-sm">
              <a href="#" class="font-medium text-blue-600 hover:text-blue-500 transition-colors duration-200">
                Lupa password?
              </a>
            </div>
          </div>

          <!-- Enhanced Submit Button -->
          <Button
            type="submit"
            variant="primary"
            size="lg"
            :loading="isLoading"
            :disabled="!isFormValid || isLoading"
            loading-text="Memverifikasi..."
            block
            aria-label="Masuk ke sistem"
          >
            Masuk
          </Button>

          <!-- Enhanced Error Display -->
          <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-center">
                <XCircleIcon class="h-5 w-5 text-red-400 mr-2" />
                <p class="text-sm text-red-600">{{ errorMessage }}</p>
              </div>
            </div>
          </Transition>
        </form>

        <!-- Enhanced Demo Credentials -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div class="flex items-center mb-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-600 mr-2" />
            <p class="text-sm text-blue-800 font-medium">Demo Login:</p>
          </div>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <div class="text-xs text-blue-700">
                <p><strong>Admin:</strong> admin@absensi.com / password</p>
              </div>
              <Button
                variant="ghost"
                size="xs"
                @click="fillDemoCredentials('admin')"
                :disabled="isLoading"
              >
                Isi
              </Button>
            </div>
            <div class="flex items-center justify-between">
              <div class="text-xs text-blue-700">
                <p><strong>Employee:</strong> john@absensi.com / password</p>
              </div>
              <Button
                variant="ghost"
                size="xs"
                @click="fillDemoCredentials('employee')"
                :disabled="isLoading"
              >
                Isi
              </Button>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Toast notifications will be handled by vue-toastification -->
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import { 
  EyeIcon, 
  EyeSlashIcon, 
  XCircleIcon, 
  InformationCircleIcon 
} from '@heroicons/vue/24/outline'
import { Button, Form, Card, LoadingSpinner } from '@/components/ui'
import type { LoginCredentials } from '@/types'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const appName = import.meta.env.VITE_APP_NAME || 'Sistem Absensi'

// Form state with enhanced validation
const form = reactive<LoginCredentials & { remember: boolean }>({
  email: '',
  password: '',
  remember: false
})

const errors = reactive<Record<string, string>>({})
const isLoading = ref(false)
const isValidatingEmail = ref(false)
const showPassword = ref(false)
const errorMessage = ref('')

// Computed properties for better UX
const isFormValid = computed(() => {
  return form.email && 
         form.password && 
         form.password.length >= 6 && 
         /\S+@\S+\.\S+/.test(form.email) &&
         Object.keys(errors).length === 0
})

// Watch for form changes to clear errors
watch(() => form.email, () => {
  if (errors.email) {
    clearEmailError()
  }
})

watch(() => form.password, () => {
  if (errors.password) {
    clearPasswordError()
  }
})

// Enhanced validation functions
const validateEmail = async () => {
  if (!form.email) {
    errors.email = 'Email wajib diisi'
    return false
  }
  
  if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.email = 'Format email tidak valid'
    return false
  }
  
  // Simulate email validation (could be API call)
  isValidatingEmail.value = true
  await new Promise(resolve => setTimeout(resolve, 500))
  isValidatingEmail.value = false
  
  return true
}

const validatePassword = () => {
  if (!form.password) {
    errors.password = 'Password wajib diisi'
    return false
  }
  
  if (form.password.length < 6) {
    errors.password = 'Password minimal 6 karakter'
    return false
  }
  
  return true
}

const clearEmailError = () => {
  delete errors.email
}

const clearPasswordError = () => {
  delete errors.password
}

// UI interaction functions
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const fillDemoCredentials = (type: 'admin' | 'employee') => {
  if (type === 'admin') {
    form.email = 'admin@absensi.com'
    form.password = 'password'
  } else {
    form.email = 'john@absensi.com'
    form.password = 'password'
  }
  
  // Clear any existing errors
  Object.keys(errors).forEach(key => delete errors[key])
  errorMessage.value = ''
  
  toast.info('Kredensial demo telah diisi')
}

// Enhanced form submission with better error handling
const handleSubmit = async () => {
  // Clear previous errors
  errorMessage.value = ''
  Object.keys(errors).forEach(key => delete errors[key])
  
  // Validate form
  const isEmailValid = await validateEmail()
  const isPasswordValid = validatePassword()
  
  if (!isEmailValid || !isPasswordValid) {
    toast.error('Mohon perbaiki error pada form')
    return
  }
  
  isLoading.value = true
  
  try {
    await authStore.login({
      email: form.email,
      password: form.password
    })
    
    // Success feedback
    toast.success(`Selamat datang, ${authStore.user?.name}! 🎉`, {
      timeout: 3000
    })
    
    // Redirect with slight delay for better UX
    setTimeout(() => {
      router.push('/')
    }, 500)
    
  } catch (error: any) {
    console.error('Login error:', error)
    
    // Enhanced error handling
    if (error.response?.status === 401) {
      errorMessage.value = 'Email atau password salah. Silakan periksa kembali kredensial Anda.'
      toast.error('Login gagal: Kredensial tidak valid')
    } else if (error.response?.status === 422) {
      const validationErrors = error.response.data.errors
      Object.assign(errors, validationErrors)
      toast.error('Mohon perbaiki error pada form')
    } else if (error.response?.status >= 500) {
      errorMessage.value = 'Server sedang bermasalah. Silakan coba lagi nanti.'
      toast.error('Server error. Silakan coba lagi')
    } else {
      errorMessage.value = 'Terjadi kesalahan jaringan. Periksa koneksi internet Anda.'
      toast.error('Terjadi kesalahan. Silakan coba lagi')
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Additional styles for enhanced UX */
.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus styles for better accessibility */
input:focus,
button:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

/* Hover effects for interactive elements */
label {
  transition: color 0.2s ease-in-out;
}

label:hover {
  color: #374151;
}
</style> 