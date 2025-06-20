import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import Cookies from 'js-cookie'

// Lazy loading components
const Login = () => import('@/views/Login.vue')
const Dashboard = () => import('@/views/Dashboard.vue')
const Attendance = () => import('@/views/Attendance.vue')
const AttendanceList = () => import('@/views/AttendanceList.vue')
const Employee = () => import('@/views/Employee.vue')
const EmployeeForm = () => import('@/views/EmployeeForm.vue')
const Leave = () => import('@/views/Leave.vue')
const LeaveForm = () => import('@/views/LeaveForm.vue')
const Salary = () => import('@/views/Salary.vue')
const ManageSchedules = () => import('@/views/ManageSchedules.vue')
const FaceRegistration = () => import('@/views/FaceRegistration.vue')
const Profile = () => import('@/views/Profile.vue')
const NotFound = () => import('@/views/NotFound.vue')

// Layout components
const AuthLayout = () => import('@/layouts/AuthLayout.vue')
const AppLayout = () => import('@/layouts/AppLayout.vue')

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: {
      layout: 'auth',
      requiresGuest: true,
      title: 'Login'
    }
  },
  {
    path: '/',
    component: AppLayout,
    meta: {
      layout: 'app',
      requiresAuth: true
    },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: Dashboard,
        meta: {
          title: 'Dashboard'
        }
      },
      {
        path: '/attendance',
        name: 'Attendance',
        component: Attendance,
        meta: {
          title: 'Absensi'
        }
      },
      {
        path: '/attendance/list',
        name: 'AttendanceList',
        component: AttendanceList,
        meta: {
          title: 'Daftar Absensi',
          requiresAdmin: true
        }
      },
      {
        path: '/employees',
        name: 'Employee',
        component: Employee,
        meta: {
          title: 'Daftar Pegawai',
          requiresAdmin: true
        }
      },
      {
        path: '/employees/create',
        name: 'EmployeeCreate',
        component: EmployeeForm,
        meta: {
          title: 'Tambah Pegawai',
          requiresAdmin: true
        }
      },
      {
        path: '/employees/:id/edit',
        name: 'EmployeeEdit',
        component: EmployeeForm,
        meta: {
          title: 'Edit Pegawai',
          requiresAdmin: true
        }
      },
      {
        path: '/leaves',
        name: 'Leave',
        component: Leave,
        meta: {
          title: 'Daftar Izin'
        }
      },
      {
        path: '/leaves/create',
        name: 'LeaveCreate',
        component: LeaveForm,
        meta: {
          title: 'Ajukan Izin'
        }
      },
      {
        path: '/leaves/:id/edit',
        name: 'LeaveEdit',
        component: LeaveForm,
        meta: {
          title: 'Edit Izin'
        }
      },
      {
        path: '/salary',
        name: 'Salary',
        component: Salary,
        meta: {
          title: 'Laporan Gaji',
          requiresAdmin: true
        }
      },
      {
        path: '/schedules',
        name: 'ManageSchedules',
        component: ManageSchedules,
        meta: {
          title: 'Manajemen Jadwal',
          requiresAdmin: true
        }
      },
      {
        path: '/face-registration',
        name: 'FaceRegistration',
        component: FaceRegistration,
        meta: {
          title: 'Registrasi Wajah'
        }
      },
      {
        path: '/profile',
        name: 'Profile',
        component: Profile,
        meta: {
          title: 'Profil'
        }
      },
      {
        path: '/analytics',
        name: 'Analytics',
        component: () => import('@/views/Analytics.vue'),
        meta: {
          title: 'Analytics Dashboard',
          requiresAdmin: true
        }
      },
      {
        path: '/security',
        name: 'Security',
        component: () => import('@/views/Security.vue'),
        meta: {
          title: 'Security Dashboard',
          requiresAdmin: true
        }
      },
      {
        path: '/workflows',
        name: 'Workflows',
        component: () => import('@/views/Workflows.vue'),
        meta: {
          title: 'Workflow Management',
          requiresAdmin: true
        }
      },
      {
        path: '/projects',
        name: 'Projects',
        component: () => import('@/views/Projects.vue'),
        meta: {
          title: 'Project Management'
        }
      },
      {
        path: '/time-tracking',
        name: 'TimeTracking',
        component: () => import('@/views/TimeTracking.vue'),
        meta: {
          title: 'Time Tracking'
        }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: {
      title: 'Halaman Tidak Ditemukan'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to: any, from: any, savedPosition: any) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Navigation Guards
router.beforeEach(async (to: any, from: any, next: any) => {
  const authStore = useAuthStore()
  const toast = useToast()
  
  // Wait for auth initialization to complete
  if (!authStore.isInitialized) {
    await authStore.initializeAuth()
  }
  
  // Set page title
  if (to.meta.title) {
    document.title = `${to.meta.title} - ${import.meta.env.VITE_APP_NAME || 'Sistem Absensi'}`
  }

  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    toast.error('Silakan login terlebih dahulu')
    next('/login')
    return
  }

  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next('/')
    return
  }

  // Check if route requires admin role
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    toast.error('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.')
    next('/')
    return
  }

  next()
})

export default router 