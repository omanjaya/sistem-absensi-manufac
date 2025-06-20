<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    :aria-label="ariaLabel"
    :aria-disabled="disabled || loading"
    @click="handleClick"
    @keydown="handleKeydown"
  >
    <!-- Loading spinner -->
    <div
      v-if="loading"
      class="animate-spin -ml-1 mr-3 h-4 w-4 text-current"
      aria-hidden="true"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
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

    <!-- Icon (left) -->
    <component
      v-if="iconLeft && !loading"
      :is="iconLeft"
      :class="iconClasses"
      aria-hidden="true"
    />

    <!-- Button text -->
    <span v-if="$slots.default || (!iconLeft && !iconRight)">
      <slot>{{ loading ? loadingText : '' }}</slot>
    </span>

    <!-- Icon (right) -->
    <component
      v-if="iconRight && !loading"
      :is="iconRight"
      :class="iconClasses"
      aria-hidden="true"
    />

    <!-- Focus ring for accessibility -->
    <div class="absolute inset-0 rounded-md ring-2 ring-transparent group-focus-visible:ring-blue-500 pointer-events-none" />
  </button>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'ghost' | 'outline'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  disabled?: boolean
  loading?: boolean
  loadingText?: string
  type?: 'button' | 'submit' | 'reset'
  iconLeft?: Component
  iconRight?: Component
  ariaLabel?: string
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  loadingText: 'Loading...',
  type: 'button',
  block: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const baseClasses = 'relative group inline-flex items-center justify-center font-medium rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 ease-in-out'

const variantClasses = computed(() => {
  const variants = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-500 active:bg-blue-800 shadow-sm hover:shadow-md',
    secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200 focus-visible:ring-gray-500 active:bg-gray-300 border border-gray-300',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500 active:bg-red-800 shadow-sm hover:shadow-md',
    success: 'bg-green-600 text-white hover:bg-green-700 focus-visible:ring-green-500 active:bg-green-800 shadow-sm hover:shadow-md',
    warning: 'bg-yellow-500 text-white hover:bg-yellow-600 focus-visible:ring-yellow-500 active:bg-yellow-700 shadow-sm hover:shadow-md',
    ghost: 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus-visible:ring-gray-500 active:bg-gray-200',
    outline: 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus-visible:ring-gray-500 active:bg-gray-100'
  }
  return variants[props.variant]
})

const sizeClasses = computed(() => {
  const sizes = {
    xs: 'px-2.5 py-1.5 text-xs',
    sm: 'px-3 py-2 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-4 py-2 text-base',
    xl: 'px-6 py-3 text-base'
  }
  return sizes[props.size]
})

const iconClasses = computed(() => {
  const sizes = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-4 w-4',
    lg: 'h-5 w-5',
    xl: 'h-5 w-5'
  }
  return sizes[props.size]
})

const buttonClasses = computed(() => [
  baseClasses,
  variantClasses.value,
  sizeClasses.value,
  {
    'w-full': props.block,
    'cursor-not-allowed': props.disabled || props.loading,
    'animate-pulse': props.loading
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    handleClick(event as any)
  }
}
</script> 