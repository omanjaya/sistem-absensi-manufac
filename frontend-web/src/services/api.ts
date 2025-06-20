import axios, { AxiosInstance, AxiosResponse } from 'axios'
import Cookies from 'js-cookie'
import { useToast } from 'vue-toastification'
import { getLaravelApiUrl, getFlaskApiUrl, logApiConfiguration } from '@/utils/api-config'
import type {
  User,
  LoginCredentials,
  LoginResponse,
  Employee,
  Attendance,
  AttendanceRequest,
  Leave,
  Salary,
  FaceRecognitionResponse,
  FaceRegistrationRequest,
  ApiResponse,
  PaginatedResponse,
  EmployeeFilters,
  AttendanceFilters
} from '@/types'

const toast = useToast()

// Laravel API Instance dengan URL dinamis
const laravelApi: AxiosInstance = axios.create({
  baseURL: getLaravelApiUrl(),
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Flask AI API Instance dengan URL dinamis
const flaskApi: AxiosInstance = axios.create({
  baseURL: getFlaskApiUrl(),
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Debug info di console (hanya di development)
logApiConfiguration()

// Request Interceptors
laravelApi.interceptors.request.use((config) => {
  const token = Cookies.get('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response Interceptors
laravelApi.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    if (error.response?.status === 401) {
      Cookies.remove('auth_token')
      window.location.href = '/login'
      toast.error('Session expired. Please login again.')
    } else if (error.response?.status >= 500) {
      toast.error('Server error occurred. Please try again later.')
    } else if (error.code === 'ECONNREFUSED' || error.message.includes('Network Error')) {
      toast.error(`Tidak dapat terhubung ke server Laravel di ${getLaravelApiUrl()}. Pastikan server berjalan.`)
    }
    return Promise.reject(error)
  }
)

flaskApi.interceptors.response.use(
  (response: AxiosResponse) => response,
  (error) => {
    console.error('Flask API Error:', error)
    if (error.code === 'ECONNREFUSED') {
      toast.error(`Face recognition service tidak tersedia di ${getFlaskApiUrl()}.`)
    }
    return Promise.reject(error)
  }
)

// Auth API
export const authApi = {
  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const { data } = await laravelApi.post<ApiResponse<LoginResponse>>('/auth/login', credentials)
    return data.data!
  },

  async logout(): Promise<void> {
    await laravelApi.post('/auth/logout')
  },

  async getUser(): Promise<User> {
    const { data } = await laravelApi.get<ApiResponse<User>>('/auth/user')
    return data.data!
  },

  async updateProfile(profileData: Partial<User>): Promise<User> {
    const { data } = await laravelApi.put<ApiResponse<User>>('/auth/profile', profileData)
    return data.data!
  },

  async changePassword(passwords: { current_password: string; new_password: string; new_password_confirmation: string }): Promise<void> {
    await laravelApi.put('/auth/password', passwords)
  }
}

// Employee API
export const employeeApi = {
  async getEmployees(params?: any) {
    const response = await laravelApi.get('/employees', { params })
    return response.data
  },

  async getEmployee(id: number) {
    const response = await laravelApi.get(`/employees/${id}`)
    return response.data
  },

  async createEmployee(data: any) {
    const response = await laravelApi.post('/employees', data)
    return response.data
  },

  async updateEmployee(id: number, data: any) {
    const response = await laravelApi.put(`/employees/${id}`, data)
    return response.data
  },

  async deleteEmployee(id: number) {
    const response = await laravelApi.delete(`/employees/${id}`)
    return response.data
  },

  async exportEmployees(params?: any) {
    const response = await laravelApi.get('/employees/export', { 
      params,
      responseType: 'blob'
    })
    return response.data
  },

  // Import Template Features
  async downloadTemplate() {
    const response = await laravelApi.get('/employees/template/download', {
      responseType: 'blob'
    })
    return response.data
  },

  async previewImport(formData: FormData) {
    const response = await laravelApi.post('/employees/import/preview', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  async executeImport(formData: FormData, options: any = {}) {
    const response = await laravelApi.post('/employees/import/execute', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      params: options
    })
    return response.data
  }
}

// Attendance API
export const attendanceApi = {
  async getAttendances(filters: AttendanceFilters = {}): Promise<PaginatedResponse<Attendance>> {
    const { data } = await laravelApi.get<ApiResponse<PaginatedResponse<Attendance>>>('/attendances', { params: filters })
    return data.data!
  },

  async getAttendance(id: number): Promise<Attendance> {
    const { data } = await laravelApi.get<ApiResponse<Attendance>>(`/attendances/${id}`)
    return data.data!
  },

  async createAttendance(attendanceData: AttendanceRequest): Promise<Attendance> {
    const { data } = await laravelApi.post<ApiResponse<Attendance>>('/attendances', attendanceData)
    return data.data!
  },

  async getTodayAttendance(): Promise<Attendance | null> {
    try {
      const { data } = await laravelApi.get<ApiResponse<Attendance>>('/attendances/today')
      return data.data!
    } catch (error: any) {
      if (error.response?.status === 404) {
        return null
      }
      throw error
    }
  },

  async exportAttendances(filters: AttendanceFilters = {}): Promise<Blob> {
    const { data } = await laravelApi.get('/attendances/export', {
      params: filters,
      responseType: 'blob'
    })
    return data
  }
}

// Leave API
export const leaveApi = {
  async getLeaves(filters: any = {}): Promise<PaginatedResponse<Leave>> {
    const { data } = await laravelApi.get<ApiResponse<PaginatedResponse<Leave>>>('/leaves', { params: filters })
    return data.data!
  },

  async getLeave(id: number): Promise<Leave> {
    const { data } = await laravelApi.get<ApiResponse<Leave>>(`/leaves/${id}`)
    return data.data!
  },

  async createLeave(leaveData: Partial<Leave>): Promise<Leave> {
    const { data } = await laravelApi.post<ApiResponse<Leave>>('/leaves', leaveData)
    return data.data!
  },

  async updateLeave(id: number, leaveData: Partial<Leave>): Promise<Leave> {
    const { data } = await laravelApi.put<ApiResponse<Leave>>(`/leaves/${id}`, leaveData)
    return data.data!
  },

  async approveLeave(id: number, notes?: string): Promise<Leave> {
    const { data } = await laravelApi.post<ApiResponse<Leave>>(`/leaves/${id}/approve`, { notes })
    return data.data!
  },

  async rejectLeave(id: number, notes?: string): Promise<Leave> {
    const { data } = await laravelApi.post<ApiResponse<Leave>>(`/leaves/${id}/reject`, { notes })
    return data.data!
  }
}

// Salary API
export const salaryApi = {
  async getSalaries(filters: any = {}): Promise<PaginatedResponse<Salary>> {
    const { data } = await laravelApi.get<ApiResponse<PaginatedResponse<Salary>>>('/salaries', { params: filters })
    return data.data!
  },

  async getSalary(id: number): Promise<Salary> {
    const { data } = await laravelApi.get<ApiResponse<Salary>>(`/salaries/${id}`)
    return data.data!
  },

  async generateSalary(month: string, year: number): Promise<Salary[]> {
    const { data } = await laravelApi.post<ApiResponse<Salary[]>>('/salaries/generate', { month, year })
    return data.data!
  },

  async exportSalaries(filters: any = {}): Promise<Blob> {
    const { data } = await laravelApi.get('/salaries/export', {
      params: filters,
      responseType: 'blob'
    })
    return data
  }
}

// Face Recognition API (Flask)
export const faceApi = {
  async recognizeFace(photo: string): Promise<FaceRecognitionResponse> {
    const { data } = await flaskApi.post<FaceRecognitionResponse>('/recognize', { photo })
    return data
  },

  async registerFace(request: FaceRegistrationRequest): Promise<{ success: boolean; message: string }> {
    const { data } = await flaskApi.post('/register-face', request)
    return data
  },

  async healthCheck(): Promise<{ status: string; message: string }> {
    const { data } = await flaskApi.get('/health')
    return data
  }
}

// Utility Functions
export const downloadFile = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

// Combined API object for compatibility
export const api = laravelApi

export default {
  authApi,
  employeeApi,
  attendanceApi,
  leaveApi,
  salaryApi,
  faceApi,
  downloadFile
} 