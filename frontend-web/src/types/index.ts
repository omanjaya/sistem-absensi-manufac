// User & Authentication Types
export interface User {
  id: number
  name: string
  email: string
  role: 'admin' | 'employee'
  employee_id?: string
  phone?: string
  avatar?: string
  department?: string
  position?: string
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface LoginResponse {
  user: User
  token: string
}

// Employee Types
export interface Employee {
  id: number
  employee_id: string
  name: string
  email: string
  phone?: string
  department: string
  position: string
  join_date: string
  status: 'active' | 'inactive'
  avatar?: string
  created_at: string
  updated_at: string
}

// Attendance Types
export interface Attendance {
  id: number
  employee_id: number
  date: string
  clock_in: string | null
  clock_out: string | null
  work_hours: number
  status: 'present' | 'late' | 'absent' | 'partial'
  location_in?: string
  location_out?: string
  photo_in?: string
  photo_out?: string
  notes?: string
  created_at: string
  updated_at: string
  employee?: Employee
}

export interface AttendanceRequest {
  photo: string // base64
  latitude: number
  longitude: number
  type: 'clock_in' | 'clock_out'
}

// Leave Types
export interface Leave {
  id: number
  employee_id: number
  type: 'sick' | 'annual' | 'emergency' | 'other'
  start_date: string
  end_date: string
  days: number
  reason: string
  status: 'pending' | 'approved' | 'rejected'
  approved_by?: number
  approved_at?: string
  notes?: string
  attachment?: string
  created_at: string
  updated_at: string
  employee?: Employee
}

// Schedule Types
export interface Schedule {
  id: number
  employee_id: number
  day_of_week: number // 0-6 (Sunday-Saturday)
  start_time: string
  end_time: string
  is_active: boolean
  created_at: string
  updated_at: string
}

// Salary Types
export interface Salary {
  id: number
  employee_id: number
  month: string
  year: number
  basic_salary: number
  overtime_hours: number
  overtime_rate: number
  deductions: number
  bonuses: number
  total_salary: number
  status: 'draft' | 'finalized' | 'paid'
  generated_at: string
  employee?: Employee
}

// Face Recognition Types
export interface FaceRecognitionResponse {
  success: boolean
  user_id?: number
  confidence?: number
  message: string
}

export interface FaceRegistrationRequest {
  user_id: number
  photo: string // base64
}

// Location Types
export interface Location {
  latitude: number
  longitude: number
  accuracy?: number
  timestamp?: number
}

export interface OfficeLocation {
  latitude: number
  longitude: number
  radius: number // in meters
  name: string
}

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

// Form Types
export interface AttendanceFilters {
  employee_id?: number
  date_from?: string
  date_to?: string
  status?: string
}

export interface EmployeeFilters {
  search?: string
  department?: string
  status?: string
  page?: number
  per_page?: number
}

// Camera Types
export interface CameraConfig {
  width: number
  height: number
  quality: number
  facingMode: 'user' | 'environment'
}

// Settings Types
export interface AppSettings {
  office_location: OfficeLocation
  work_hours: {
    start: string
    end: string
  }
  attendance_rules: {
    late_threshold: number // minutes
    early_checkout_threshold: number // minutes
  }
  camera_config: CameraConfig
} 