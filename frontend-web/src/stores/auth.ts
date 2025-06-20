import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import Cookies from 'js-cookie'
import { authApi } from '@/services/api'
import type { User, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isLoading = ref(false)
  const isInitialized = ref(false)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => user.value?.role || null)
  const isAdmin = computed(() => userRole.value === 'admin')

  // Actions
  const initializeAuth = async () => {
    if (isInitialized.value) return
    
    const storedToken = Cookies.get('auth_token')
    
    if (storedToken) {
      token.value = storedToken
      try {
        await fetchUser()
      } catch (error) {
        console.error('Failed to fetch user:', error)
        logout()
      }
    }
    
    isInitialized.value = true
  }

  const login = async (credentials: LoginCredentials) => {
    isLoading.value = true
    try {
      const response = await authApi.login(credentials)
      
      token.value = response.token
      user.value = response.user
      
      // Store token in cookie (httpOnly would be better in production)
      Cookies.set('auth_token', response.token, { 
        expires: 7, // 7 days
        secure: import.meta.env.PROD,
        sameSite: 'strict'
      })
      
      return response
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    Cookies.remove('auth_token')
  }

  const fetchUser = async () => {
    try {
      const userData = await authApi.getUser()
      user.value = userData
      return userData
    } catch (error) {
      logout()
      throw error
    }
  }

  const updateProfile = async (profileData: Partial<User>) => {
    try {
      const updatedUser = await authApi.updateProfile(profileData)
      user.value = updatedUser
      return updatedUser
    } catch (error) {
      throw error
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    isInitialized,
    
    // Getters
    isAuthenticated,
    userRole,
    isAdmin,
    
    // Actions
    initializeAuth,
    login,
    logout,
    fetchUser,
    updateProfile
  }
}) 