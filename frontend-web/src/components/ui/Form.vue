<template>
  <div class="space-y-1">
    <!-- Label -->
    <label 
      v-if="label" 
      :for="inputId"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300"
      :class="{ 'text-red-700 dark:text-red-400': hasError }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1" aria-label="required">*</span>
    </label>

    <!-- Input wrapper -->
    <div class="relative">
      <!-- Text Input -->
      <input
        v-if="type === 'text' || type === 'email' || type === 'password' || type === 'number' || type === 'tel' || type === 'url'"
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled || loading"
        :required="required"
        :autocomplete="autocomplete"
        :class="inputClasses"
        :aria-describedby="hasError ? `${inputId}-error` : hasHint ? `${inputId}-hint` : undefined"
        :aria-invalid="hasError"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown.enter="$emit('enter')"
      />

      <!-- Textarea -->
      <textarea
        v-else-if="type === 'textarea'"
        :id="inputId"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled || loading"
        :required="required"
        :rows="rows"
        :class="textareaClasses"
        :aria-describedby="hasError ? `${inputId}-error` : hasHint ? `${inputId}-hint` : undefined"
        :aria-invalid="hasError"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      ></textarea>

      <!-- Select -->
      <select
        v-else-if="type === 'select'"
        :id="inputId"
        :value="modelValue"
        :disabled="disabled || loading"
        :required="required"
        :class="selectClasses"
        :aria-describedby="hasError ? `${inputId}-error` : hasHint ? `${inputId}-hint` : undefined"
        :aria-invalid="hasError"
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="getOptionValue(option)"
          :value="getOptionValue(option)"
        >
          {{ getOptionLabel(option) }}
        </option>
      </select>

      <!-- Loading indicator -->
      <div
        v-if="loading"
        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
      >
        <div class="animate-spin h-4 w-4 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
      </div>

      <!-- Validation icon -->
      <div
        v-else-if="showValidationIcon && (hasError || isValid)"
        class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
      >
        <!-- Error icon -->
        <svg
          v-if="hasError"
          class="h-5 w-5 text-red-500"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <!-- Success icon -->
        <svg
          v-else-if="isValid"
          class="h-5 w-5 text-green-500"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>

    <!-- Hint text -->
    <p
      v-if="hint && !hasError"
      :id="`${inputId}-hint`"
      class="text-sm text-gray-500 dark:text-gray-400"
    >
      {{ hint }}
    </p>

    <!-- Error message -->
    <div
      v-if="hasError"
      :id="`${inputId}-error`"
      class="flex items-center space-x-1 text-sm text-red-600 dark:text-red-400"
      role="alert"
      aria-live="polite"
    >
      <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"
        />
      </svg>
      <span>{{ error }}</span>
    </div>

    <!-- Character count -->
    <div
      v-if="maxLength && type === 'textarea'"
      class="flex justify-between text-xs text-gray-500"
    >
      <span></span>
      <span>{{ characterCount }}/{{ maxLength }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'

type InputType = 'text' | 'email' | 'password' | 'number' | 'textarea' | 'select' | 'tel' | 'url'

interface Option {
  value: string | number
  label: string
}

interface Props {
  modelValue?: string | number
  type?: InputType
  label?: string
  placeholder?: string
  error?: string
  hint?: string
  disabled?: boolean
  loading?: boolean
  required?: boolean
  options?: Option[] | string[]
  rows?: number
  maxLength?: number
  autocomplete?: string
  showValidationIcon?: boolean
  validateOnBlur?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  rows: 3,
  showValidationIcon: true,
  validateOnBlur: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  'blur': [event: Event]
  'focus': [event: Event]
  'enter': []
}>()

// Generate unique ID for accessibility
const inputId = ref(`input-${Math.random().toString(36).substr(2, 9)}`)

// Local state
const isFocused = ref(false)
const hasBeenBlurred = ref(false)

// Computed properties
const hasError = computed(() => Boolean(props.error))
const hasHint = computed(() => Boolean(props.hint))
const isValid = computed(() => 
  !hasError.value && 
  props.modelValue && 
  String(props.modelValue).length > 0 && 
  hasBeenBlurred.value
)

const characterCount = computed(() => 
  props.modelValue ? String(props.modelValue).length : 0
)

const baseInputClasses = 'block w-full rounded-md shadow-sm transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed'

const inputClasses = computed(() => [
  baseInputClasses,
  'px-3 py-2 text-sm',
  {
    // Border and background
    'border-gray-300 bg-white focus:border-blue-500 focus:ring-blue-500': !hasError.value,
    'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500': hasError.value,
    
    // Loading state
    'pr-10': props.loading || (props.showValidationIcon && (hasError.value || isValid.value)),
    
    // Disabled state
    'bg-gray-50 text-gray-500': props.disabled
  }
])

const textareaClasses = computed(() => [
  baseInputClasses,
  'px-3 py-2 text-sm resize-vertical',
  {
    'border-gray-300 bg-white focus:border-blue-500 focus:ring-blue-500': !hasError.value,
    'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500': hasError.value,
    'bg-gray-50 text-gray-500': props.disabled
  }
])

const selectClasses = computed(() => [
  baseInputClasses,
  'px-3 py-2 text-sm pr-10',
  {
    'border-gray-300 bg-white focus:border-blue-500 focus:ring-blue-500': !hasError.value,
    'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500': hasError.value,
    'bg-gray-50 text-gray-500': props.disabled
  }
])

// Event handlers
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement | HTMLTextAreaElement
  let value: string | number = target.value
  
  if (props.type === 'number' && target instanceof HTMLInputElement) {
    value = target.valueAsNumber || 0
  }
  
  emit('update:modelValue', value)
}

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
}

const handleFocus = (event: Event) => {
  isFocused.value = true
  emit('focus', event)
}

const handleBlur = (event: Event) => {
  isFocused.value = false
  hasBeenBlurred.value = true
  emit('blur', event)
}

// Option helpers for select
const getOptionValue = (option: Option | string): string | number => {
  return typeof option === 'string' ? option : option.value
}

const getOptionLabel = (option: Option | string): string => {
  return typeof option === 'string' ? option : option.label
}
</script> 