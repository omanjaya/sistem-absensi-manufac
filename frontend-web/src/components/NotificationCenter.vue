<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="togglePanel"
      class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg transition-colors"
      :class="{ 'text-blue-600': unreadCount > 0 }"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      
      <!-- Unread Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
      
      <!-- Urgent Alert Pulse -->
      <span
        v-if="urgentNotifications.length > 0"
        class="absolute -top-1 -right-1 inline-flex w-3 h-3"
      >
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
      </span>
    </button>

    <!-- Notification Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 top-12 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50 max-h-96 overflow-hidden"
      >
        <!-- Panel Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <div class="flex items-center space-x-2">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <span
              v-if="unreadCount > 0"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ unreadCount }} new
            </span>
          </div>
          
          <div class="flex items-center space-x-2">
            <!-- Settings Button -->
            <button
              @click="showSettings = !showSettings"
              class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
              title="Notification Settings"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
            
            <!-- Mark All Read Button -->
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
              title="Mark all as read"
            >
              Mark all read
            </button>
            
            <!-- Close Button -->
            <button
              @click="isOpen = false"
              class="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex border-b border-gray-200">
          <button
            v-for="filter in filters"
            :key="filter.key"
            @click="activeFilter = filter.key"
            class="flex-1 px-4 py-2 text-sm font-medium transition-colors"
            :class="activeFilter === filter.key 
              ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' 
              : 'text-gray-500 hover:text-gray-700'"
          >
            {{ filter.label }}
            <span
              v-if="filter.count > 0"
              class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs"
              :class="activeFilter === filter.key 
                ? 'bg-blue-100 text-blue-600' 
                : 'bg-gray-100 text-gray-600'"
            >
              {{ filter.count }}
            </span>
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-80 overflow-y-auto">
          <div v-if="isLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500">Loading notifications...</p>
          </div>

          <div v-else-if="filteredNotifications.length === 0" class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
            <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
          </div>

          <div v-else>
            <TransitionGroup
              name="notification"
              tag="div"
              enter-active-class="transition ease-out duration-300"
              enter-from-class="transform opacity-0 translate-x-full"
              enter-to-class="transform opacity-100 translate-x-0"
              leave-active-class="transition ease-in duration-200"
              leave-from-class="transform opacity-100 translate-x-0"
              leave-to-class="transform opacity-0 -translate-x-full"
            >
              <div
                v-for="notification in filteredNotifications"
                :key="notification.id"
                class="border-b border-gray-100 last:border-b-0"
              >
                <div
                  class="p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                  :class="{ 'bg-blue-50 border-l-4 border-blue-500': notification.status !== 'read' }"
                  @click="handleNotificationClick(notification)"
                >
                  <div class="flex items-start space-x-3">
                    <!-- Notification Icon -->
                    <div 
                      class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-lg"
                      :class="getPriorityClass(notification.priority)"
                    >
                      {{ getNotificationIcon(notification.type) }}
                    </div>

                    <!-- Notification Content -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 truncate">
                          {{ notification.title }}
                        </p>
                        <div class="flex items-center space-x-2">
                          <span class="text-xs text-gray-500">
                            {{ formatRelativeTime(notification.created_at) }}
                          </span>
                          
                          <!-- Priority Indicator -->
                          <span
                            v-if="notification.priority === 'urgent'"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                          >
                            Urgent
                          </span>
                          <span
                            v-else-if="notification.priority === 'high'"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
                          >
                            High
                          </span>
                        </div>
                      </div>
                      
                      <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                        {{ notification.message }}
                      </p>
                      
                      <!-- Action Buttons -->
                      <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center space-x-2">
                          <span 
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="getTypeClass(notification.type)"
                          >
                            {{ formatNotificationType(notification.type) }}
                          </span>
                        </div>
                        
                        <div class="flex items-center space-x-1">
                          <button
                            v-if="notification.status !== 'read'"
                            @click.stop="markAsRead(notification.id)"
                            class="p-1 text-gray-400 hover:text-blue-600 rounded transition-colors"
                            title="Mark as read"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          
                          <button
                            @click.stop="deleteNotification(notification.id)"
                            class="p-1 text-gray-400 hover:text-red-600 rounded transition-colors"
                            title="Delete notification"
                          >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </TransitionGroup>
          </div>
        </div>

        <!-- Panel Footer -->
        <div class="p-3 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <button
              @click="loadMore"
              v-if="filteredNotifications.length > 0 && !isLoading"
              class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
            >
              Load more
            </button>
            
            <button
              @click="clearAllNotifications"
              v-if="notifications.length > 0"
              class="text-sm text-red-600 hover:text-red-800 font-medium transition-colors"
            >
              Clear all
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Settings Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showSettings && isOpen"
        class="absolute right-0 top-12 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
      >
        <div class="p-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">Notification Settings</h4>
          
          <div class="space-y-4">
            <!-- Browser Notifications -->
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700">Browser Notifications</label>
                <p class="text-xs text-gray-500">Show desktop notifications</p>
              </div>
              <button
                @click="toggleBrowserNotifications"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="browserNotificationsEnabled ? 'bg-blue-600' : 'bg-gray-200'"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                  :class="browserNotificationsEnabled ? 'translate-x-6' : 'translate-x-1'"
                />
              </button>
            </div>
            
            <!-- Sound Notifications -->
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700">Sound Alerts</label>
                <p class="text-xs text-gray-500">Play sound for new notifications</p>
              </div>
              <button
                @click="soundEnabled = !soundEnabled"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="soundEnabled ? 'bg-blue-600' : 'bg-gray-200'"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                  :class="soundEnabled ? 'translate-x-6' : 'translate-x-1'"
                />
              </button>
            </div>
            
            <!-- Auto Mark as Read -->
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700">Auto Mark as Read</label>
                <p class="text-xs text-gray-500">Mark as read when clicked</p>
              </div>
              <button
                @click="autoMarkAsRead = !autoMarkAsRead"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="autoMarkAsRead ? 'bg-blue-600' : 'bg-gray-200'"
              >
                <span
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                  :class="autoMarkAsRead ? 'translate-x-6' : 'translate-x-1'"
                />
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div
      v-if="isOpen"
      @click="isOpen = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useNotifications } from '@/composables/useNotifications'

// Composables
const {
  notifications,
  unreadCount,
  isLoading,
  unreadNotifications,
  urgentNotifications,
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  clearAllNotifications,
  getNotificationIcon,
  formatRelativeTime
} = useNotifications()

// Component state
const isOpen = ref(false)
const showSettings = ref(false)
const activeFilter = ref('all')
const browserNotificationsEnabled = ref(false)
const soundEnabled = ref(true)
const autoMarkAsRead = ref(true)
const currentPage = ref(1)

// Filter options
const filters = computed(() => [
  { key: 'all', label: 'All', count: notifications.value.length },
  { key: 'unread', label: 'Unread', count: unreadNotifications.value.length },
  { key: 'urgent', label: 'Urgent', count: urgentNotifications.value.length }
])

// Filtered notifications
const filteredNotifications = computed(() => {
  switch (activeFilter.value) {
    case 'unread':
      return unreadNotifications.value
    case 'urgent':
      return urgentNotifications.value
    default:
      return notifications.value
  }
})

// Methods
function togglePanel() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    showSettings.value = false
  }
}

async function toggleBrowserNotifications() {
  if (!browserNotificationsEnabled.value) {
    const permission = await Notification.requestPermission()
    browserNotificationsEnabled.value = permission === 'granted'
  } else {
    browserNotificationsEnabled.value = false
  }
}

function handleNotificationClick(notification: any) {
  if (autoMarkAsRead.value && notification.status !== 'read') {
    markAsRead(notification.id)
  }
  
  // Handle navigation based on notification type
  handleNotificationNavigation(notification)
}

function handleNotificationNavigation(notification: any) {
  // Navigate to relevant page based on notification type
  // This would integrate with Vue Router
  console.log('Navigate to:', notification.type, notification.data)
}

function loadMore() {
  currentPage.value++
  fetchNotifications(currentPage.value)
}

function getPriorityClass(priority: string): string {
  const classes = {
    low: 'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600'
  }
  return classes[priority as keyof typeof classes] || classes.medium
}

function getTypeClass(type: string): string {
  const classes = {
    attendance_reminder: 'bg-blue-100 text-blue-800',
    leave_approved: 'bg-green-100 text-green-800',
    leave_rejected: 'bg-red-100 text-red-800',
    overtime_alert: 'bg-yellow-100 text-yellow-800',
    salary_generated: 'bg-green-100 text-green-800',
    schedule_changed: 'bg-purple-100 text-purple-800',
    holiday_announced: 'bg-indigo-100 text-indigo-800',
    system_maintenance: 'bg-orange-100 text-orange-800',
    security_alert: 'bg-red-100 text-red-800',
    birthday_reminder: 'bg-pink-100 text-pink-800',
    performance_review: 'bg-blue-100 text-blue-800',
    training_reminder: 'bg-purple-100 text-purple-800'
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-800'
}

function formatNotificationType(type: string): string {
  return type.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

// Watch for urgent notifications and play sound
watch(urgentNotifications, (newUrgent, oldUrgent) => {
  if (soundEnabled.value && newUrgent.length > (oldUrgent?.length || 0)) {
    // Play notification sound
    const audio = new Audio('/notification-sound.mp3')
    audio.play().catch(() => {
      // Sound play failed, which is fine
    })
  }
}, { deep: true })

// Load settings from localStorage
onMounted(() => {
  const settings = localStorage.getItem('notificationSettings')
  if (settings) {
    const parsed = JSON.parse(settings)
    browserNotificationsEnabled.value = parsed.browserNotificationsEnabled || false
    soundEnabled.value = parsed.soundEnabled !== false
    autoMarkAsRead.value = parsed.autoMarkAsRead !== false
  }
})

// Save settings to localStorage
watch([browserNotificationsEnabled, soundEnabled, autoMarkAsRead], () => {
  localStorage.setItem('notificationSettings', JSON.stringify({
    browserNotificationsEnabled: browserNotificationsEnabled.value,
    soundEnabled: soundEnabled.value,
    autoMarkAsRead: autoMarkAsRead.value
  }))
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.notification-move {
  transition: transform 0.3s ease;
}
</style> 