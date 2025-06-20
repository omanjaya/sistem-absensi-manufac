import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { api } from '@/services/api'
import { getLaravelApiUrl } from '@/utils/api-config'

export interface Notification {
  id: number
  title: string
  message: string
  type: 'attendance_reminder' | 'leave_approved' | 'leave_rejected' | 'overtime_alert' | 
        'salary_generated' | 'schedule_changed' | 'holiday_announced' | 'system_maintenance' |
        'security_alert' | 'birthday_reminder' | 'performance_review' | 'training_reminder'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  status: 'pending' | 'sent' | 'read' | 'failed'
  created_at: string
  scheduled_at?: string
  expires_at?: string
  data?: any
}

export function useNotifications() {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Real-time connection
  let eventSource: EventSource | null = null
  
  // Computed properties
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => n.status !== 'read')
  )
  
  const urgentNotifications = computed(() =>
    notifications.value.filter(n => n.priority === 'urgent' && n.status !== 'read')
  )
  
  const groupedNotifications = computed(() => {
    const groups: Record<string, Notification[]> = {}
    notifications.value.forEach(notification => {
      const date = new Date(notification.created_at).toDateString()
      if (!groups[date]) {
        groups[date] = []
      }
      groups[date].push(notification)
    })
    return groups
  })

  // Fetch notifications
  async function fetchNotifications(page = 1, limit = 20) {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await api.get('/notifications', {
        params: { page, limit }
      })
      
      if (response.data.success) {
        if (page === 1) {
          notifications.value = response.data.data.data || response.data.data.notifications || []
        } else {
          const newNotifications = response.data.data.data || response.data.data.notifications || []
          notifications.value.push(...newNotifications)
        }
        unreadCount.value = response.data.unread_count || response.data.data.unread_count || 0
      }
    } catch (err: any) {
      console.error('Fetch notifications error:', err)
      error.value = err.response?.data?.message || 'Failed to fetch notifications'
      
      // If authentication error, don't try to setup real-time notifications
      if (err.response?.status === 401) {
        console.warn('User not authenticated, skipping notification setup')
        return
      }
    } finally {
      isLoading.value = false
    }
  }

  // Mark notification as read
  async function markAsRead(notificationId: number) {
    try {
      const response = await api.patch(`/notifications/${notificationId}/read`)
      
      if (response.data.success) {
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
          notification.status = 'read'
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (err: any) {
      console.error('Failed to mark notification as read:', err)
    }
  }

  // Mark all notifications as read
  async function markAllAsRead() {
    try {
      const response = await api.patch('/notifications/mark-all-read')
      
      if (response.data.success) {
        notifications.value.forEach(notification => {
          if (notification.status !== 'read') {
            notification.status = 'read'
          }
        })
        unreadCount.value = 0
      }
    } catch (err: any) {
      console.error('Failed to mark all notifications as read:', err)
    }
  }

  // Delete notification
  async function deleteNotification(notificationId: number) {
    try {
      const response = await api.delete(`/notifications/${notificationId}`)
      
      if (response.data.success) {
        const index = notifications.value.findIndex(n => n.id === notificationId)
        if (index !== -1) {
          const notification = notifications.value[index]
          if (notification.status !== 'read') {
            unreadCount.value = Math.max(0, unreadCount.value - 1)
          }
          notifications.value.splice(index, 1)
        }
      }
    } catch (err: any) {
      console.error('Failed to delete notification:', err)
    }
  }

  // Clear all notifications
  async function clearAllNotifications() {
    try {
      const response = await api.delete('/notifications/clear-all')
      
      if (response.data.success) {
        notifications.value = []
        unreadCount.value = 0
      }
    } catch (err: any) {
      console.error('Failed to clear notifications:', err)
    }
  }

  // Get notification icon based on type
  function getNotificationIcon(type: string): string {
    const icons: Record<string, string> = {
      attendance_reminder: '⏰',
      leave_approved: '✅',
      leave_rejected: '❌',
      overtime_alert: '⚠️',
      salary_generated: '💰',
      schedule_changed: '📅',
      holiday_announced: '🎉',
      system_maintenance: '🔧',
      security_alert: '🔒',
      birthday_reminder: '🎂',
      performance_review: '📊',
      training_reminder: '📚'
    }
    return icons[type] || '📢'
  }

  // Get notification color based on priority
  function getNotificationColor(priority: string): string {
    const colors: Record<string, string> = {
      low: 'text-gray-600',
      medium: 'text-blue-600',
      high: 'text-orange-600',
      urgent: 'text-red-600'
    }
    return colors[priority] || 'text-gray-600'
  }

  // Show browser notification if permission granted
  function showBrowserNotification(notification: Notification) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: `notification-${notification.id}`
      })
    }
  }

  // Request notification permission
  async function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      const permission = await Notification.requestPermission()
      return permission === 'granted'
    }
    return false
  }

  // Setup real-time notifications
  function setupRealTimeNotifications() {
    if (typeof EventSource === 'undefined') return

    // Menggunakan utility centralized untuk mendapatkan API URL
    const backendUrl = getLaravelApiUrl()
    const streamUrl = `${backendUrl}/notifications/stream`
    
    console.log('🔔 Setting up notification stream:', streamUrl)
    
    try {
      eventSource = new EventSource(streamUrl)
      
      eventSource.onopen = () => {
        console.log('✅ Notification stream connected to:', streamUrl)
      }
      
      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data)
          
          // Handle different event types
          if (data.type === 'connection') {
            console.log('🔔 Connected to notification stream:', data.message)
            return
          }
          
          if (data.type === 'heartbeat') {
            // Heartbeat to keep connection alive
            return
          }
          
          // Regular notification
          const notification = data as Notification
          notifications.value.unshift(notification)
          unreadCount.value++
          
          // Show browser notification for urgent notifications
          if (notification.priority === 'urgent') {
            showBrowserNotification(notification)
          }
        } catch (err) {
          console.error('Failed to parse notification:', err)
        }
      }
      
      eventSource.onerror = (event) => {
        console.error('❌ EventSource failed for:', streamUrl, event)
        
        // Close current connection
        if (eventSource) {
          eventSource.close()
          eventSource = null
        }
        
        // Don't try to reconnect automatically to avoid spam
        console.warn('⚠️ Notification stream disconnected. Manual reconnection required.')
      }
    } catch (error) {
      console.error('❌ Failed to setup notification stream:', streamUrl, error)
    }
  }

  // Cleanup real-time connection
  function cleanupRealTimeNotifications() {
    if (eventSource) {
      eventSource.close()
      eventSource = null
    }
  }

  // Format relative time
  function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)
    
    if (diffInSeconds < 60) return 'Just now'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
    
    return date.toLocaleDateString()
  }

  // Initialize
  onMounted(() => {
    fetchNotifications()
    // Only setup real-time notifications if not in development or if explicitly enabled
    if (import.meta.env.VITE_ENABLE_NOTIFICATIONS !== 'false') {
      setupRealTimeNotifications()
    }
    requestNotificationPermission()
  })

  // Cleanup
  onUnmounted(() => {
    cleanupRealTimeNotifications()
  })

  return {
    // State
    notifications,
    unreadCount,
    isLoading,
    error,
    
    // Computed
    unreadNotifications,
    urgentNotifications,
    groupedNotifications,
    
    // Methods
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearAllNotifications,
    getNotificationIcon,
    getNotificationColor,
    showBrowserNotification,
    requestNotificationPermission,
    formatRelativeTime,
    
    // Real-time
    setupRealTimeNotifications,
    cleanupRealTimeNotifications
  }
} 