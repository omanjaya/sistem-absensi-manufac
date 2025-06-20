<template>
  <div 
    :class="containerClasses"
    :aria-label="ariaLabel"
    role="status"
  >
    <div 
      :class="spinnerClasses"
      :style="customStyle"
    >
      <svg 
        class="animate-spin"
        :class="svgClasses"
        fill="none" 
        viewBox="0 0 24 24"
      >
        <circle 
          class="opacity-25" 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="4"
        />
        <path 
          class="opacity-75" 
          fill="currentColor" 
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
    
    <!-- Loading text -->
    <p 
      v-if="text && !inline" 
      :class="textClasses"
    >
      {{ text }}
    </p>
    
    <!-- Screen reader text -->
    <span v-if="!text" class="sr-only">{{ ariaLabel }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  color?: 'primary' | 'secondary' | 'white' | 'gray' | 'success' | 'warning' | 'danger'
  text?: string
  inline?: boolean
  overlay?: boolean
  fullScreen?: boolean
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  color: 'primary',
  inline: false,
  overlay: false,
  fullScreen: false,
  ariaLabel: 'Loading...'
})

const sizeClasses = computed(() => {
  const sizes = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4', 
    md: 'h-6 w-6',
    lg: 'h-8 w-8',
    xl: 'h-12 w-12'
  }
  return sizes[props.size]
})

const colorClasses = computed(() => {
  const colors = {
    primary: 'text-blue-600',
    secondary: 'text-gray-600',
    white: 'text-white',
    gray: 'text-gray-400',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    danger: 'text-red-600'
  }
  return colors[props.color]
})

const containerClasses = computed(() => [
  'flex items-center justify-center',
  {
    'inline-flex': props.inline,
    'flex-col space-y-2': !props.inline && props.text,
    'fixed inset-0 z-50 bg-black bg-opacity-50': props.fullScreen,
    'absolute inset-0 z-10 bg-white bg-opacity-75': props.overlay && !props.fullScreen,
    'relative': !props.overlay && !props.fullScreen
  }
])

const spinnerClasses = computed(() => [
  'relative',
  {
    'mr-2': props.inline && props.text
  }
])

const svgClasses = computed(() => [
  sizeClasses.value,
  colorClasses.value
])

const textClasses = computed(() => [
  'text-sm font-medium',
  colorClasses.value,
  {
    'text-white': props.fullScreen,
    'text-gray-700': props.overlay && !props.fullScreen
  }
])

const customStyle = computed(() => {
  if (props.fullScreen) {
    return {
      filter: 'drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1))'
    }
  }
  return {}
})
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