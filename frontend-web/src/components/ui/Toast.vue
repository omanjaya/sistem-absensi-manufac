<template>
  <Transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      :class="toastClasses"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <!-- Icon -->
      <div class="flex-shrink-0">
        <component
          :is="iconComponent"
          :class="iconClasses"
          aria-hidden="true"
        />
      </div>

      <!-- Content -->
      <div class="ml-3 w-0 flex-1">
        <p :class="titleClasses">
          {{ title }}
        </p>
        <p 
          v-if="message"
          :class="messageClasses"
        >
          {{ message }}
        </p>
        
        <!-- Action button -->
        <div v-if="actionText" class="mt-3 flex space-x-7">
          <button
            type="button"
            :class="actionButtonClasses"
            @click="handleAction"
          >
            {{ actionText }}
          </button>
          <button
            type="button"
            class="text-sm font-medium text-gray-700 hover:text-gray-500"
            @click="handleDismiss"
          >
            Dismiss
          </button>
        </div>
      </div>

      <!-- Close button -->
      <div class="ml-4 flex-shrink-0 flex">
        <button
          type="button"
          :class="closeButtonClasses"
          @click="handleDismiss"
        >
          <span class="sr-only">Close</span>
          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { 
  CheckCircleIcon, 
  ExclamationTriangleIcon, 
  InformationCircleIcon, 
  XCircleIcon,
  XMarkIcon 
} from '@heroicons/vue/24/outline'

type ToastType = 'success' | 'error' | 'warning' | 'info'

interface Props {
  type?: ToastType
  title: string
  message?: string
  actionText?: string
  duration?: number
  persistent?: boolean
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  duration: 5000,
  persistent: false,
  position: 'top-right'
})

const emit = defineEmits<{
  dismiss: []
  action: []
}>()

const visible = ref(true)
let timeoutId: number | null = null

// Auto dismiss
onMounted(() => {
  if (!props.persistent && props.duration > 0) {
    timeoutId = window.setTimeout(() => {
      handleDismiss()
    }, props.duration)
  }
})

onUnmounted(() => {
  if (timeoutId) {
    clearTimeout(timeoutId)
  }
})

// Icon mapping
const iconComponent = computed(() => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[props.type]
})

// Styling
const toastClasses = computed(() => [
  'max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden',
  'p-4 flex items-start'
])

const iconClasses = computed(() => {
  const colors = {
    success: 'text-green-400',
    error: 'text-red-400', 
    warning: 'text-yellow-400',
    info: 'text-blue-400'
  }
  return `h-6 w-6 ${colors[props.type]}`
})

const titleClasses = computed(() => {
  const colors = {
    success: 'text-green-800',
    error: 'text-red-800',
    warning: 'text-yellow-800', 
    info: 'text-blue-800'
  }
  return `text-sm font-medium ${colors[props.type]}`
})

const messageClasses = computed(() => [
  'mt-1 text-sm text-gray-500'
])

const actionButtonClasses = computed(() => {
  const colors = {
    success: 'text-green-600 hover:text-green-500',
    error: 'text-red-600 hover:text-red-500',
    warning: 'text-yellow-600 hover:text-yellow-500',
    info: 'text-blue-600 hover:text-blue-500'
  }
  return `text-sm font-medium ${colors[props.type]} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`
})

const closeButtonClasses = computed(() => [
  'bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500',
  'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
])

// Event handlers
const handleDismiss = () => {
  visible.value = false
  setTimeout(() => {
    emit('dismiss')
  }, 300) // Wait for transition
}

const handleAction = () => {
  emit('action')
}
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style> 