<template>
  <span :class="badgeClasses">
    <component v-if="icon" :is="icon" class="w-3 h-3 mr-1" />
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'primary'
  size?: 'sm' | 'md' | 'lg'
  icon?: any
  label?: string
  outline?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'gray',
  size: 'md',
  outline: false
})

const badgeClasses = computed(() => {
  const baseClasses = 'inline-flex items-center font-medium rounded-full'
  
  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-sm',
    lg: 'px-3 py-1 text-sm'
  }
  
  const variantClasses = {
    success: props.outline 
      ? 'text-green-700 bg-green-50 ring-1 ring-green-600/20'
      : 'text-green-800 bg-green-100',
    warning: props.outline
      ? 'text-yellow-700 bg-yellow-50 ring-1 ring-yellow-600/20'
      : 'text-yellow-800 bg-yellow-100',
    danger: props.outline
      ? 'text-red-700 bg-red-50 ring-1 ring-red-600/20'
      : 'text-red-800 bg-red-100',
    info: props.outline
      ? 'text-blue-700 bg-blue-50 ring-1 ring-blue-600/20'
      : 'text-blue-800 bg-blue-100',
    primary: props.outline
      ? 'text-indigo-700 bg-indigo-50 ring-1 ring-indigo-600/20'
      : 'text-indigo-800 bg-indigo-100',
    gray: props.outline
      ? 'text-gray-700 bg-gray-50 ring-1 ring-gray-600/20'
      : 'text-gray-800 bg-gray-100'
  }
  
  return [baseClasses, sizeClasses[props.size], variantClasses[props.variant]].join(' ')
})
</script> 