// Export all UI components for easy importing
export { default as Button } from './Button.vue'
export { default as Card } from './Card.vue'
export { default as Badge } from './Badge.vue'
export { default as Form } from './Form.vue'
export { default as Modal } from './Modal.vue'
export { default as Table } from './Table.vue'
export { default as MetricCard } from './MetricCard.vue'
export { default as LoadingSpinner } from './LoadingSpinner.vue'
export { default as Toast } from './Toast.vue'

// Export types for TypeScript support
export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'ghost' | 'outline'
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
export type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'primary'
export type ToastType = 'success' | 'error' | 'warning' | 'info'
export type LoadingSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

// Re-export types if needed
export type { default as ButtonProps } from './Button.vue'
export type { default as ModalProps } from './Modal.vue'
export type { default as TableProps } from './Table.vue'
export type { default as MetricCardProps } from './MetricCard.vue'
export type { default as BadgeProps } from './Badge.vue'
export type { default as FormProps } from './Form.vue' 