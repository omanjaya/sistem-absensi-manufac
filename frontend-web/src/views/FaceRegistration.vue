<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-900">Registrasi Wajah</h1>
        <p class="text-sm text-gray-600 mt-1">
          Daftarkan wajah Anda untuk sistem absensi dengan face recognition
        </p>
      </div>
      
      <div class="p-6">
        <div class="max-w-2xl mx-auto">
          <!-- Camera Section -->
          <div class="mb-8">
            <div class="bg-gray-100 rounded-lg p-6 text-center">
              <div v-if="!isCameraReady" class="py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-600">Mempersiapkan kamera...</p>
              </div>
              
              <div v-else-if="!isRegistered" class="space-y-4">
                <video
                  ref="videoRef"
                  autoplay
                  muted
                  class="w-full max-w-md mx-auto rounded-lg shadow-lg"
                ></video>
                
                <div class="flex justify-center space-x-4">
                  <button
                    @click="capturePhoto"
                    :disabled="isCapturing"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
                  >
                    {{ isCapturing ? 'Memproses...' : 'Ambil Foto' }}
                  </button>
                  <button
                    @click="startCamera"
                    class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 transition-colors"
                  >
                    Reset Kamera
                  </button>
                </div>
              </div>

              <div v-else class="py-12">
                <div class="text-green-600 mb-4">
                  <svg class="w-16 h-16 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Registrasi Berhasil!</h3>
                <p class="text-gray-600">Wajah Anda telah berhasil didaftarkan untuk sistem absensi.</p>
              </div>
            </div>
          </div>

          <!-- Captured Photos -->
          <div v-if="capturedPhotos.length > 0 && !isRegistered" class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Foto yang Diambil</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="(photo, index) in capturedPhotos"
                :key="index"
                class="relative"
              >
                <img
                  :src="photo"
                  :alt="`Foto ${index + 1}`"
                  class="w-full h-32 object-cover rounded-lg shadow"
                />
                <button
                  @click="removePhoto(index)"
                  class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700"
                >
                  ×
                </button>
              </div>
            </div>
            
            <div class="mt-6 flex justify-center space-x-4">
              <button
                v-if="capturedPhotos.length < 5"
                @click="capturePhoto"
                :disabled="isCapturing"
                class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                Ambil Foto Lagi ({{ capturedPhotos.length }}/5)
              </button>
              
              <button
                v-if="capturedPhotos.length >= 3"
                @click="registerFace"
                :disabled="isRegistering"
                class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 disabled:opacity-50 transition-colors"
              >
                {{ isRegistering ? 'Mendaftarkan...' : 'Daftarkan Wajah' }}
              </button>
            </div>
          </div>

          <!-- Instructions -->
          <div v-if="!isRegistered" class="bg-blue-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">Petunjuk:</h3>
            <ul class="text-blue-800 space-y-2">
              <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Pastikan wajah Anda terlihat jelas dan tidak tertutup
              </li>
              <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Ambil foto minimal 3 kali dengan sudut yang berbeda
              </li>
              <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Pastikan pencahayaan cukup dan tidak ada bayangan di wajah
              </li>
              <li class="flex items-start">
                <span class="text-blue-600 mr-2">•</span>
                Tatap lurus ke kamera saat pengambilan foto
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { faceApi } from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()

const videoRef = ref<HTMLVideoElement>()
const isCameraReady = ref(false)
const isCapturing = ref(false)
const isRegistering = ref(false)
const isRegistered = ref(false)
const capturedPhotos = ref<string[]>([])
let stream: MediaStream | null = null

const startCamera = async () => {
  try {
    isCameraReady.value = false
    
    if (stream) {
      stream.getTracks().forEach(track => track.stop())
    }

    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: 640 },
        height: { ideal: 480 },
        facingMode: 'user'
      }
    })

    if (videoRef.value) {
      videoRef.value.srcObject = stream
      await new Promise(resolve => {
        if (videoRef.value) {
          videoRef.value.onloadedmetadata = resolve
        }
      })
      isCameraReady.value = true
    }
  } catch (error) {
    toast.error('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.')
    console.error('Camera error:', error)
  }
}

const capturePhoto = async () => {
  if (!videoRef.value || !isCameraReady.value) return

  try {
    isCapturing.value = true

    const canvas = document.createElement('canvas')
    const context = canvas.getContext('2d')
    
    if (!context) {
      throw new Error('Canvas context not available')
    }

    canvas.width = videoRef.value.videoWidth
    canvas.height = videoRef.value.videoHeight
    
    context.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
    
    const photoDataUrl = canvas.toDataURL('image/jpeg', 0.8)
    capturedPhotos.value.push(photoDataUrl)

    toast.success(`Foto ${capturedPhotos.value.length} berhasil diambil`)
  } catch (error) {
    toast.error('Gagal mengambil foto')
    console.error('Capture error:', error)
  } finally {
    isCapturing.value = false
  }
}

const removePhoto = (index: number) => {
  capturedPhotos.value.splice(index, 1)
}

const registerFace = async () => {
  if (capturedPhotos.value.length < 3) {
    toast.error('Minimal 3 foto diperlukan untuk registrasi')
    return
  }

  try {
    isRegistering.value = true

    const response = await faceApi.registerFace({
      user_id: authStore.user?.id || 0,
      photos: capturedPhotos.value.map(photo => photo.split(',')[1]) // Remove data:image/jpeg;base64, prefix
    })

    if (response.success) {
      isRegistered.value = true
      toast.success('Wajah berhasil didaftarkan!')
      
      // Stop camera
      if (stream) {
        stream.getTracks().forEach(track => track.stop())
        stream = null
      }
    } else {
      toast.error(response.message || 'Gagal mendaftarkan wajah')
    }
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal mendaftarkan wajah')
  } finally {
    isRegistering.value = false
  }
}

onMounted(() => {
  startCamera()
})

onUnmounted(() => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
  }
})
</script> 