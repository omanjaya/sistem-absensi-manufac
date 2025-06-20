/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_LARAVEL_API_URL: string
  readonly VITE_FLASK_AI_URL: string
  readonly VITE_APP_NAME: string
  readonly VITE_ENABLE_GPS: string
  readonly VITE_ENABLE_CAMERA: string
  readonly VITE_ENABLE_NOTIFICATIONS: string
  readonly VITE_OFFICE_LATITUDE: string
  readonly VITE_OFFICE_LONGITUDE: string
  readonly VITE_OFFICE_RADIUS: string
  readonly VITE_CAMERA_WIDTH: string
  readonly VITE_CAMERA_HEIGHT: string
  readonly VITE_PHOTO_QUALITY: string
  readonly VITE_MAX_FILE_SIZE: string
  readonly VITE_DEBUG: string
  readonly VITE_LOG_LEVEL: string
  readonly PROD: boolean
  readonly DEV: boolean
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
} 