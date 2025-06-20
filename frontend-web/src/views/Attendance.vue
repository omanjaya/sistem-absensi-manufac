<template>
  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Absensi</h1>
      <p class="text-gray-600">Lakukan absensi masuk dan keluar dengan pengenalan wajah</p>
    </div>

    <!-- Today's Attendance Status -->
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Clock In Status -->
        <div class="card">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                todayAttendance?.clock_in ? 'bg-green-100' : 'bg-gray-100'
              ]">
                <ClockIcon :class="[
                  'w-6 h-6',
                  todayAttendance?.clock_in ? 'text-green-600' : 'text-gray-400'
                ]" />
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-900">Masuk</h3>
              <p :class="[
                'text-lg font-semibold',
                todayAttendance?.clock_in ? 'text-green-600' : 'text-gray-500'
              ]">
                {{ todayAttendance?.clock_in || '-' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Clock Out Status -->
        <div class="card">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                todayAttendance?.clock_out ? 'bg-red-100' : 'bg-gray-100'
              ]">
                <ClockIcon :class="[
                  'w-6 h-6',
                  todayAttendance?.clock_out ? 'text-red-600' : 'text-gray-400'
                ]" />
              </div>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-900">Keluar</h3>
              <p :class="[
                'text-lg font-semibold',
                todayAttendance?.clock_out ? 'text-red-600' : 'text-gray-500'
              ]">
                {{ todayAttendance?.clock_out || '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Camera Section -->
    <div class="card mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Kamera Absensi</h3>
      
      <!-- Camera Preview -->
      <div class="relative bg-gray-900 rounded-lg overflow-hidden mb-4" style="aspect-ratio: 4/3;">
        <video
          ref="videoRef"
          class="w-full h-full object-cover"
          autoplay
          muted
          playsinline
        />
        <canvas ref="canvasRef" class="hidden" />
        
        <!-- Overlay -->
        <div class="absolute inset-0 flex items-center justify-center" v-if="!camera.stream">
          <div class="text-center text-white">
            <CameraIcon class="w-16 h-16 mx-auto mb-4 opacity-50" />
            <p class="text-lg">{{ camera.error || 'Kamera belum aktif' }}</p>
            <button
              @click="startCamera"
              :disabled="camera.isLoading"
              class="btn-primary mt-4"
            >
              <div v-if="camera.isLoading" class="flex items-center">
                <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                Memuat...
              </div>
              <span v-else>Aktifkan Kamera</span>
            </button>
          </div>
        </div>

        <!-- Face Detection Overlay -->
        <div v-if="camera.stream" class="absolute inset-4 border-2 border-white border-dashed rounded-lg opacity-50">
          <div class="absolute -top-2 -left-2 w-6 h-6 border-t-2 border-l-2 border-primary-500"></div>
          <div class="absolute -top-2 -right-2 w-6 h-6 border-t-2 border-r-2 border-primary-500"></div>
          <div class="absolute -bottom-2 -left-2 w-6 h-6 border-b-2 border-l-2 border-primary-500"></div>
          <div class="absolute -bottom-2 -right-2 w-6 h-6 border-b-2 border-r-2 border-primary-500"></div>
        </div>
      </div>

      <!-- Location Status -->
      <div class="mb-4 p-3 rounded-lg" :class="[
        geolocation.isWithinOfficeRadius 
          ? 'bg-green-50 border border-green-200' 
          : 'bg-red-50 border border-red-200'
      ]">
        <div class="flex items-center">
          <MapPinIcon :class="[
            'w-5 h-5 mr-2',
            geolocation.isWithinOfficeRadius ? 'text-green-600' : 'text-red-600'
          ]" />
          <span :class="[
            'text-sm font-medium',
            geolocation.isWithinOfficeRadius ? 'text-green-800' : 'text-red-800'
          ]">
            {{ geolocation.getLocationStatus() }}
          </span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <button
          @click="handleClockIn"
          :disabled="!canClockIn || isProcessing"
          class="btn-primary flex items-center justify-center"
        >
          <ClockIcon class="w-5 h-5 mr-2" />
          <span v-if="isProcessing && attendanceType === 'clock_in'">Memproses...</span>
          <span v-else>Absen Masuk</span>
        </button>

        <button
          @click="handleClockOut"
          :disabled="!canClockOut || isProcessing"
          class="btn-danger flex items-center justify-center"
        >
          <ClockIcon class="w-5 h-5 mr-2" />
          <span v-if="isProcessing && attendanceType === 'clock_out'">Memproses...</span>
          <span v-else>Absen Keluar</span>
        </button>
      </div>
    </div>

    <!-- Instructions -->
    <div class="card bg-blue-50 border border-blue-200">
      <h3 class="text-lg font-medium text-blue-900 mb-3">Petunjuk Absensi</h3>
      <ul class="space-y-2 text-sm text-blue-800">
        <li class="flex items-start">
          <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
          Pastikan wajah Anda terlihat jelas di kamera
        </li>
        <li class="flex items-start">
          <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
          Pastikan Anda berada dalam radius kantor
        </li>
        <li class="flex items-start">
          <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
          Izinkan akses kamera dan lokasi pada browser
        </li>
        <li class="flex items-start">
          <span class="inline-block w-2 h-2 bg-blue-600 rounded-full mt-2 mr-3 flex-shrink-0"></span>
          Registrasi wajah terlebih dahulu jika belum pernah melakukan absensi
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { ClockIcon, CameraIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { useCamera } from '@/composables/useCamera'
import { useGeolocation } from '@/composables/useGeolocation'
import { attendanceApi, faceApi } from '@/services/api'
import type { Attendance } from '@/types'

const toast = useToast()

// Composables
const camera = useCamera()
const geolocation = useGeolocation()

// Template refs
const videoRef = ref<HTMLVideoElement>()
const canvasRef = ref<HTMLCanvasElement>()

// State
const todayAttendance = ref<Attendance | null>(null)
const isProcessing = ref(false)
const attendanceType = ref<'clock_in' | 'clock_out' | null>(null)

// Computed
const canClockIn = computed(() => {
  return camera.stream && 
         geolocation.isWithinOfficeRadius && 
         !todayAttendance.value?.clock_in
})

const canClockOut = computed(() => {
  return camera.stream && 
         geolocation.isWithinOfficeRadius && 
         todayAttendance.value?.clock_in && 
         !todayAttendance.value?.clock_out
})

// Methods
const startCamera = async () => {
  camera.video.value = videoRef.value
  camera.canvas.value = canvasRef.value
  
  const success = await camera.startCamera()
  if (success) {
    toast.success('Kamera berhasil diaktifkan')
  }
}

const processAttendance = async (type: 'clock_in' | 'clock_out') => {
  isProcessing.value = true
  attendanceType.value = type

  try {
    // Get current location
    const locationValidation = await geolocation.validateAttendanceLocation()
    
    if (!locationValidation.valid) {
      throw new Error(`Anda berada di luar radius kantor (${geolocation.formatDistance(locationValidation.distance)})`)
    }

    // Take photo
    const photo = await camera.takePhoto()
    if (!photo) {
      throw new Error(camera.error || 'Gagal mengambil foto')
    }

    // Verify face with AI server
    const faceResult = await faceApi.recognizeFace(photo)
    
    if (!faceResult.success) {
      throw new Error(faceResult.message || 'Wajah tidak dikenali')
    }

    // Submit attendance
    const attendance = await attendanceApi.createAttendance({
      photo,
      latitude: locationValidation.location.latitude,
      longitude: locationValidation.location.longitude,
      type
    })

    // Update local state
    todayAttendance.value = attendance
    
    toast.success(`Absensi ${type === 'clock_in' ? 'masuk' : 'keluar'} berhasil!`)
    
  } catch (error: any) {
    console.error('Attendance error:', error)
    toast.error(error.message || 'Gagal melakukan absensi')
  } finally {
    isProcessing.value = false
    attendanceType.value = null
  }
}

const handleClockIn = () => processAttendance('clock_in')
const handleClockOut = () => processAttendance('clock_out')

const fetchTodayAttendance = async () => {
  try {
    const attendance = await attendanceApi.getTodayAttendance()
    todayAttendance.value = attendance
  } catch (error) {
    console.error('Failed to fetch today attendance:', error)
  }
}

// Lifecycle
onMounted(async () => {
  // Assign refs to composables
  camera.video.value = videoRef.value
  camera.canvas.value = canvasRef.value
  
  // Fetch today's attendance
  await fetchTodayAttendance()
  
  // Get current location
  try {
    await geolocation.getCurrentPosition()
  } catch (error) {
    console.error('Geolocation error:', error)
  }
})

onUnmounted(() => {
  camera.stopCamera()
})
</script> 