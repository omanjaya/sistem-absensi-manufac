import { ref, onMounted, onUnmounted } from 'vue'
import type { CameraConfig } from '@/types'

export function useCamera() {
  const stream = ref<MediaStream | null>(null)
  const video = ref<HTMLVideoElement | null>(null)
  const canvas = ref<HTMLCanvasElement | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const isSupported = ref(false)

  // Camera configuration from environment
  const config: CameraConfig = {
    width: parseInt(import.meta.env.VITE_CAMERA_WIDTH) || 640,
    height: parseInt(import.meta.env.VITE_CAMERA_HEIGHT) || 480,
    quality: parseFloat(import.meta.env.VITE_PHOTO_QUALITY) || 0.85,
    facingMode: 'user' // Front camera for face recognition
  }

  // Check if camera is supported
  const checkSupport = () => {
    isSupported.value = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)
    return isSupported.value
  }

  // Start camera stream
  const startCamera = async () => {
    if (!checkSupport()) {
      error.value = 'Camera tidak didukung oleh browser ini'
      return false
    }

    isLoading.value = true
    error.value = null

    try {
      const constraints: MediaStreamConstraints = {
        video: {
          width: config.width,
          height: config.height,
          facingMode: config.facingMode
        },
        audio: false
      }

      stream.value = await navigator.mediaDevices.getUserMedia(constraints)
      
      if (video.value) {
        video.value.srcObject = stream.value
        await video.value.play()
      }

      return true
    } catch (err: any) {
      console.error('Camera access error:', err)
      
      if (err.name === 'NotAllowedError') {
        error.value = 'Akses kamera ditolak. Silakan izinkan akses kamera.'
      } else if (err.name === 'NotFoundError') {
        error.value = 'Kamera tidak ditemukan.'
      } else if (err.name === 'NotReadableError') {
        error.value = 'Kamera sedang digunakan oleh aplikasi lain.'
      } else {
        error.value = 'Gagal mengakses kamera: ' + err.message
      }
      
      return false
    } finally {
      isLoading.value = false
    }
  }

  // Stop camera stream
  const stopCamera = () => {
    if (stream.value) {
      stream.value.getTracks().forEach(track => track.stop())
      stream.value = null
    }
    
    if (video.value) {
      video.value.srcObject = null
    }
  }

  // Capture photo from video stream
  const capturePhoto = (): string | null => {
    if (!video.value || !canvas.value) {
      error.value = 'Video atau canvas tidak tersedia'
      return null
    }

    const context = canvas.value.getContext('2d')
    if (!context) {
      error.value = 'Gagal mendapatkan context canvas'
      return null
    }

    // Set canvas dimensions
    canvas.value.width = config.width
    canvas.value.height = config.height

    // Draw video frame to canvas
    context.drawImage(video.value, 0, 0, config.width, config.height)

    // Convert to base64
    try {
      const dataURL = canvas.value.toDataURL('image/jpeg', config.quality)
      return dataURL.split(',')[1] // Remove data:image/jpeg;base64, prefix
    } catch (err: any) {
      error.value = 'Gagal mengambil foto: ' + err.message
      return null
    }
  }

  // Take photo with validation
  const takePhoto = async (): Promise<string | null> => {
    if (!stream.value) {
      error.value = 'Kamera belum aktif'
      return null
    }

    const photoBase64 = capturePhoto()
    
    if (!photoBase64) {
      return null
    }

    // Validate file size
    const maxSize = parseInt(import.meta.env.VITE_MAX_FILE_SIZE) || 2097152 // 2MB
    const fileSize = (photoBase64.length * 3) / 4 // Approximate base64 to bytes
    
    if (fileSize > maxSize) {
      error.value = `Ukuran foto terlalu besar (${Math.round(fileSize / 1024)}KB). Maksimal ${Math.round(maxSize / 1024)}KB.`
      return null
    }

    return photoBase64
  }

  // Lifecycle hooks
  onMounted(() => {
    checkSupport()
  })

  onUnmounted(() => {
    stopCamera()
  })

  return {
    // State
    stream,
    video,
    canvas,
    isLoading,
    error,
    isSupported,
    config,

    // Methods
    startCamera,
    stopCamera,
    capturePhoto,
    takePhoto,
    checkSupport
  }
} 