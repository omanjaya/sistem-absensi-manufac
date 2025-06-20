import { ref, computed } from 'vue'
import type { Location, OfficeLocation } from '@/types'

export function useGeolocation() {
  const currentLocation = ref<Location | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const isSupported = ref(false)

  // Office location from environment
  const officeLocation: OfficeLocation = {
    latitude: parseFloat(import.meta.env.VITE_OFFICE_LATITUDE) || -6.2088,
    longitude: parseFloat(import.meta.env.VITE_OFFICE_LONGITUDE) || 106.8456,
    radius: parseInt(import.meta.env.VITE_OFFICE_RADIUS) || 100,
    name: 'Kantor Pusat'
  }

  // Check if geolocation is supported
  const checkSupport = () => {
    isSupported.value = !!(navigator.geolocation)
    return isSupported.value
  }

  // Calculate distance between two coordinates using Haversine formula
  const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371e3 // Earth's radius in meters
    const φ1 = (lat1 * Math.PI) / 180
    const φ2 = (lat2 * Math.PI) / 180
    const Δφ = ((lat2 - lat1) * Math.PI) / 180
    const Δλ = ((lon2 - lon1) * Math.PI) / 180

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

    return R * c // Distance in meters
  }

  // Check if current location is within office radius
  const isWithinOfficeRadius = computed(() => {
    if (!currentLocation.value) return false

    const distance = calculateDistance(
      currentLocation.value.latitude,
      currentLocation.value.longitude,
      officeLocation.latitude,
      officeLocation.longitude
    )

    return distance <= officeLocation.radius
  })

  // Get distance to office
  const distanceToOffice = computed(() => {
    if (!currentLocation.value) return null

    return calculateDistance(
      currentLocation.value.latitude,
      currentLocation.value.longitude,
      officeLocation.latitude,
      officeLocation.longitude
    )
  })

  // Get current position
  const getCurrentPosition = (): Promise<Location> => {
    return new Promise((resolve, reject) => {
      if (!checkSupport()) {
        reject(new Error('Geolocation tidak didukung oleh browser ini'))
        return
      }

      isLoading.value = true
      error.value = null

      const options: PositionOptions = {
        enableHighAccuracy: true,
        timeout: 15000, // 15 seconds
        maximumAge: 300000 // 5 minutes
      }

      navigator.geolocation.getCurrentPosition(
        (position: GeolocationPosition) => {
          const location: Location = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: position.timestamp
          }

          currentLocation.value = location
          isLoading.value = false
          resolve(location)
        },
        (err: GeolocationPositionError) => {
          isLoading.value = false
          
          let errorMessage = 'Gagal mendapatkan lokasi'
          
          switch (err.code) {
            case err.PERMISSION_DENIED:
              errorMessage = 'Akses lokasi ditolak. Silakan izinkan akses lokasi.'
              break
            case err.POSITION_UNAVAILABLE:
              errorMessage = 'Informasi lokasi tidak tersedia.'
              break
            case err.TIMEOUT:
              errorMessage = 'Waktu permintaan lokasi habis.'
              break
            default:
              errorMessage = `Error: ${err.message}`
              break
          }
          
          error.value = errorMessage
          reject(new Error(errorMessage))
        },
        options
      )
    })
  }

  // Watch position (real-time tracking)
  const watchPosition = (callback: (location: Location) => void) => {
    if (!checkSupport()) {
      error.value = 'Geolocation tidak didukung oleh browser ini'
      return null
    }

    const options: PositionOptions = {
      enableHighAccuracy: true,
      timeout: 30000,
      maximumAge: 60000 // 1 minute
    }

    const watchId = navigator.geolocation.watchPosition(
      (position: GeolocationPosition) => {
        const location: Location = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          accuracy: position.coords.accuracy,
          timestamp: position.timestamp
        }

        currentLocation.value = location
        callback(location)
      },
      (err: GeolocationPositionError) => {
        error.value = `Error watching position: ${err.message}`
      },
      options
    )

    return watchId
  }

  // Clear watch
  const clearWatch = (watchId: number) => {
    navigator.geolocation.clearWatch(watchId)
  }

  // Validate attendance location
  const validateAttendanceLocation = async (): Promise<{ valid: boolean; location: Location; distance: number }> => {
    try {
      const location = await getCurrentPosition()
      const distance = calculateDistance(
        location.latitude,
        location.longitude,
        officeLocation.latitude,
        officeLocation.longitude
      )

      return {
        valid: distance <= officeLocation.radius,
        location,
        distance
      }
    } catch (err: any) {
      throw new Error(`Gagal validasi lokasi: ${err.message}`)
    }
  }

  // Format distance for display
  const formatDistance = (meters: number): string => {
    if (meters < 1000) {
      return `${Math.round(meters)}m`
    } else {
      return `${(meters / 1000).toFixed(1)}km`
    }
  }

  // Get location status message
  const getLocationStatus = (): string => {
    if (!currentLocation.value) {
      return 'Lokasi belum tersedia'
    }

    if (isWithinOfficeRadius.value) {
      return `✅ Dalam radius kantor (${formatDistance(distanceToOffice.value!)})`
    } else {
      return `❌ Di luar radius kantor (${formatDistance(distanceToOffice.value!)})`
    }
  }

  return {
    // State
    currentLocation,
    isLoading,
    error,
    isSupported,
    officeLocation,

    // Computed
    isWithinOfficeRadius,
    distanceToOffice,

    // Methods
    checkSupport,
    getCurrentPosition,
    watchPosition,
    clearWatch,
    calculateDistance,
    validateAttendanceLocation,
    formatDistance,
    getLocationStatus
  }
} 