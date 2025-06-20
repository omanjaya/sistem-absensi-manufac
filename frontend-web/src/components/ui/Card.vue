<template>
  <div 
    :class="[
      'bg-white rounded-lg shadow-sm border border-gray-200',
      paddingClass,
      hoverClass,
      customClass
    ]"
  >
    <!-- Header Section -->
    <div v-if="title || subtitle || $slots.header" class="border-b border-gray-200 pb-4 mb-4">
      <slot name="header">
        <div class="flex items-center justify-between">
          <div>
            <h3 v-if="title" class="text-lg font-medium text-gray-900">{{ title }}</h3>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
          </div>
          <slot name="headerActions" />
        </div>
      </slot>
    </div>

    <!-- Content Section -->
    <div :class="contentClass">
      <slot />
    </div>

    <!-- Footer Section -->
    <div v-if="$slots.footer" class="border-t border-gray-200 pt-4 mt-4">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title?: string
  subtitle?: string
  padding?: 'sm' | 'md' | 'lg' | 'xl' | 'none'
  hover?: boolean
  contentClass?: string
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  padding: 'md',
  hover: false,
  contentClass: '',
  customClass: ''
})

const paddingClass = computed(() => {
  const paddingClasses = {
    none: '',
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8',
    xl: 'p-10'
  }
  return paddingClasses[props.padding]
})

const hoverClass = computed(() => 
  props.hover ? 'hover:shadow-md transition-shadow cursor-pointer' : ''
)
</script> 