<template>
  <Teleport to="body">
    <Transition
      enter-active-class="ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        @click="handleBackdropClick"
      >
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-if="show"
            :class="modalClasses"
            @click.stop
          >
            <!-- Header -->
            <div v-if="title || slots.header" class="border-b border-gray-200 pb-4">
              <slot name="header">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
                  <button
                    v-if="closable"
                    @click="close"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none"
                  >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </slot>
            </div>

            <!-- Content -->
            <div :class="contentClass">
              <slot />
            </div>

            <!-- Footer -->
            <div v-if="slots.footer" class="border-t border-gray-200 pt-4">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, useSlots } from 'vue'

const slots = useSlots()

interface Props {
  show: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
  closable?: boolean
  closeOnBackdrop?: boolean
  contentClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  closable: true,
  closeOnBackdrop: true,
  contentClass: 'py-4'
})

const emit = defineEmits<{
  close: []
  'update:show': [value: boolean]
}>()

const modalClasses = computed(() => {
  const sizeClasses = {
    sm: 'relative top-20 mx-auto p-5 border w-80 shadow-lg rounded-md bg-white',
    md: 'relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white',
    lg: 'relative top-20 mx-auto p-5 border w-2/3 max-w-2xl shadow-lg rounded-md bg-white',
    xl: 'relative top-20 mx-auto p-5 border w-3/4 max-w-4xl shadow-lg rounded-md bg-white',
    '2xl': 'relative top-20 mx-auto p-5 border w-5/6 max-w-6xl shadow-lg rounded-md bg-white',
    full: 'relative top-10 mx-auto p-5 border w-11/12 max-w-screen-xl shadow-lg rounded-md bg-white'
  }
  return sizeClasses[props.size]
})

function close() {
  emit('close')
  emit('update:show', false)
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    close()
  }
}
</script> 