/**
 * Utility untuk konfigurasi API yang dinamis
 * Secara otomatis mendeteksi port backend berdasarkan port frontend
 */

// Konfigurasi API yang fleksibel dengan auto-detection
const DEFAULT_LARAVEL_PORT = 8000
const DEFAULT_FLASK_PORT = 5000
const DEFAULT_FRONTEND_PORT = 3000

// Deteksi port yang sedang berjalan
const getCurrentPort = () => {
  if (typeof window !== 'undefined') {
    return window.location.port ? parseInt(window.location.port) : (window.location.protocol === 'https:' ? 443 : 80)
  }
  return DEFAULT_FRONTEND_PORT
}

const currentPort = getCurrentPort()

// Konfigurasi port mapping - sesuaikan dengan setup Anda
export const PORT_CONFIG = {
  // Laravel API ports to try
  laravel: [DEFAULT_LARAVEL_PORT, 8001, 8002, 8080],
  
  // Flask AI ports to try  
  flask: [DEFAULT_FLASK_PORT, 5001, 5002],
  
  // Frontend ports
  frontend: [currentPort, DEFAULT_FRONTEND_PORT, 3001, 3002, 5173, 4173],
  
  // Current detected ports
  current: {
    frontend: currentPort,
    laravel: DEFAULT_LARAVEL_PORT,
    flask: DEFAULT_FLASK_PORT
  }
}

// Environment detection
export const ENV_CONFIG = {
  isDevelopment: import.meta.env.DEV,
  isProduction: import.meta.env.PROD,
  mode: import.meta.env.MODE,
  base: import.meta.env.BASE_URL
}

// Prioritas deteksi berdasarkan environment variables
const getEnvApiUrl = (service: 'laravel' | 'flask') => {
  const envVars = {
    laravel: [
      import.meta.env.VITE_LARAVEL_API_URL,
      import.meta.env.VITE_API_URL,
      import.meta.env.VITE_BACKEND_URL
    ],
    flask: [
      import.meta.env.VITE_FLASK_API_URL,
      import.meta.env.VITE_AI_URL,
      import.meta.env.VITE_FACE_API_URL
    ]
  }
  
  return envVars[service].find(url => url && url.trim() !== '')
}

// Generate URLs based on detected port dan environment
export const getLaravelApiUrl = (): string => {
  // 1. Cek environment variable dulu
  const envUrl = getEnvApiUrl('laravel')
  if (envUrl) {
    // Clean up the URL - remove trailing slashes and duplicated /api
    const cleanUrl = envUrl.replace(/\/+$/, '') // Remove trailing slashes
    return cleanUrl.endsWith('/api') ? cleanUrl : `${cleanUrl}/api`
  }
  
  // 2. Generate berdasarkan current environment
  const protocol = window.location.protocol
  const hostname = window.location.hostname || 'localhost'
  
  return `${protocol}//${hostname}:${PORT_CONFIG.current.laravel}/api`
}

export const getFlaskApiUrl = (): string => {
  // 1. Cek environment variable dulu
  const envUrl = getEnvApiUrl('flask')
  if (envUrl) {
    // Clean up the URL - remove trailing slashes
    return envUrl.replace(/\/+$/, '')
  }
  
  // 2. Generate berdasarkan current environment
  const protocol = window.location.protocol
  const hostname = window.location.hostname || 'localhost'
  
  return `${protocol}//${hostname}:${PORT_CONFIG.current.flask}`
}

// Health check functions untuk auto-detection
export const checkServiceHealth = async (url: string): Promise<boolean> => {
  try {
    const response = await fetch(`${url}/health`, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
      signal: AbortSignal.timeout(5000) // 5 second timeout
    })
    return response.ok
  } catch {
    return false
  }
}

// Auto-detect available services
export const detectAvailableServices = async () => {
  const results = {
    laravel: null as string | null,
    flask: null as string | null
  }
  
  // Test Laravel ports
  for (const port of PORT_CONFIG.laravel) {
    const url = `${window.location.protocol}//${window.location.hostname || 'localhost'}:${port}/api`
    if (await checkServiceHealth(url)) {
      results.laravel = url
      PORT_CONFIG.current.laravel = port
      break
    }
  }
  
  // Test Flask ports
  for (const port of PORT_CONFIG.flask) {
    const url = `${window.location.protocol}//${window.location.hostname || 'localhost'}:${port}`
    if (await checkServiceHealth(url)) {
      results.flask = url
      PORT_CONFIG.current.flask = port
      break
    }
  }
  
  return results
}

// Log configuration untuk development
export const logApiConfiguration = () => {
  if (ENV_CONFIG.isDevelopment) {
    const laravelUrl = getLaravelApiUrl()
    const flaskUrl = getFlaskApiUrl()
    
    console.log('🔗 API Configuration:')
    console.log('  Laravel API:', laravelUrl)
    console.log('  Flask AI:', flaskUrl)
    console.log('  Frontend:', `${window.location.protocol}//${window.location.hostname || 'localhost'}:${currentPort}`)
    console.log('  Port Mapping:', PORT_CONFIG)
    console.log('  Current Port:', currentPort)
    console.log('  Environment:', ENV_CONFIG)
    
    // Additional debugging
    console.log('🐛 Debug Info:')
    console.log('  Environment Variables:', {
      VITE_LARAVEL_API_URL: import.meta.env.VITE_LARAVEL_API_URL,
      VITE_FLASK_API_URL: import.meta.env.VITE_FLASK_API_URL,
    })
    console.log('  Final URLs will be:')
    console.log('    - Login:', `${laravelUrl}/auth/login`)
    console.log('    - Face Recognition:', `${flaskUrl}/recognize`)
  }
}

// Export default configuration
export default {
  getLaravelApiUrl,
  getFlaskApiUrl,
  PORT_CONFIG,
  ENV_CONFIG,
  detectAvailableServices,
  checkServiceHealth,
  logApiConfiguration
} 