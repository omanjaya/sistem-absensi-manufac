<template>
  <Card :hover="hover" :custom-class="customClass">
    <div class="flex items-center">
      <!-- Icon -->
      <div class="flex-shrink-0">
        <div :class="iconBgClass" class="w-8 h-8 rounded-md flex items-center justify-center">
          <component :is="icon" :class="iconColorClass" class="w-5 h-5" />
        </div>
      </div>

      <!-- Content -->
      <div class="ml-5 w-0 flex-1">
        <dl>
          <dt class="text-sm font-medium text-gray-500 truncate">{{ label }}</dt>
          <dd class="flex items-baseline">
            <div class="text-2xl font-semibold text-gray-900">{{ value }}</div>
            
            <!-- Change Indicator -->
            <div v-if="change !== undefined" :class="changeColorClass" class="ml-2 flex items-baseline text-sm font-semibold">
              <component :is="changeIcon" class="self-center flex-shrink-0 h-5 w-5" :class="changeIconColor" />
              <span class="sr-only">{{ change > 0 ? 'Increased' : 'Decreased' }} by</span>
              {{ Math.abs(change) }}{{ changeUnit }}
            </div>
          </dd>
          
          <!-- Description -->
          <div v-if="description" class="text-xs text-gray-500 mt-1">
            {{ description }}
          </div>
        </dl>
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Card from './Card.vue'

interface Props {
  label: string
  value: string | number
  icon?: any
  iconBg?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'indigo' | 'gray'
  change?: number
  changeUnit?: string
  description?: string
  hover?: boolean
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  iconBg: 'blue',
  changeUnit: '%',
  hover: false,
  customClass: ''
})

const iconBgClass = computed(() => {
  const bgClasses = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    yellow: 'bg-yellow-100',
    red: 'bg-red-100',
    purple: 'bg-purple-100',
    indigo: 'bg-indigo-100',
    gray: 'bg-gray-100'
  }
  return bgClasses[props.iconBg]
})

const iconColorClass = computed(() => {
  const colorClasses = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    red: 'text-red-600',
    purple: 'text-purple-600',
    indigo: 'text-indigo-600',
    gray: 'text-gray-600'
  }
  return colorClasses[props.iconBg]
})

const changeColorClass = computed(() => {
  if (props.change === undefined) return ''
  return props.change >= 0 ? 'text-green-600' : 'text-red-600'
})

const changeIconColor = computed(() => {
  if (props.change === undefined) return ''
  return props.change >= 0 ? 'text-green-500' : 'text-red-500'
})

const changeIcon = computed(() => {
  if (props.change === undefined) return null
  
  if (props.change >= 0) {
    // Arrow up icon (trending up)
    return {
      template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
      `
    }
  } else {
    // Arrow down icon (trending down)
    return {
      template: `
        <svg fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
      `
    }
  }
})
</script> 