<template>
  <div class="p-6 space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="text-gray-600">Monitor your company's performance and insights</p>
      </div>
      <div class="flex items-center space-x-3">
        <Form
          type="select"
          v-model="selectedDateRange"
          :options="dateRangeOptions"
          placeholder="Select period"
        />
        <Button variant="primary" @click="exportData">
          Export Report
        </Button>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        label="Attendance Rate"
        :value="metrics.attendanceRate + '%'"
        :icon="UsersIcon"
        icon-bg="green"
        :change="metrics.attendanceChange"
        description="vs last month"
      />
      
      <MetricCard
        label="Active Employees"
        :value="metrics.activeEmployees"
        :icon="UserGroupIcon"
        icon-bg="blue"
        :change="metrics.employeeChange"
        description="currently working"
      />
      
      <MetricCard
        label="Productivity Score"
        :value="metrics.productivityScore + '/10'"
        :icon="ChartBarIcon"
        icon-bg="purple"
        :change="metrics.productivityChange"
        description="overall rating"
      />
      
      <MetricCard
        label="Late Arrivals"
        :value="metrics.lateArrivals"
        :icon="ClockIcon"
        icon-bg="yellow"
        :change="metrics.lateChange"
        change-unit=""
        description="this month"
      />
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card title="Attendance Trends" subtitle="Daily attendance over time">
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
          <p class="text-gray-500">Chart.js integration ready</p>
        </div>
      </Card>
      
      <Card title="Department Performance" subtitle="Attendance by department">
        <div class="space-y-4">
          <div v-for="dept in departmentData" :key="dept.name" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full" :class="dept.color"></div>
              <span class="text-sm font-medium">{{ dept.name }}</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-24 bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full" :class="dept.color" :style="`width: ${dept.percentage}%`"></div>
              </div>
              <span class="text-sm text-gray-600">{{ dept.percentage }}%</span>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- Top Performers & Issues -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Performers -->
      <Card title="Top Performers" subtitle="Highest attendance this month">
        <div class="space-y-3">
          <div v-for="(performer, index) in topPerformers" :key="performer.id" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium text-blue-600">#{{ index + 1 }}</span>
              </div>
              <div>
                <p class="text-sm font-medium">{{ performer.name }}</p>
                <p class="text-xs text-gray-500">{{ performer.department }}</p>
              </div>
            </div>
            <Badge variant="success" :label="performer.score + '%'" />
          </div>
        </div>
      </Card>

      <!-- Attendance Issues -->
      <Card title="Attendance Issues" subtitle="Recent concerns to address">
        <div class="space-y-3">
          <div v-for="issue in attendanceIssues" :key="issue.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 rounded-full" :class="getSeverityColor(issue.severity)"></div>
              <div>
                <p class="text-sm font-medium">{{ issue.employee }}</p>
                <p class="text-xs text-gray-500">{{ issue.description }}</p>
              </div>
            </div>
            <Badge :variant="getSeverityVariant(issue.severity) as any" :label="issue.severity" />
          </div>
        </div>
      </Card>
    </div>

    <!-- Recent Activity Table -->
    <Table
      title="Recent Activity"
      subtitle="Latest attendance and system events"
      :data="recentActivity"
      :columns="activityColumns"
      searchable
      search-placeholder="Search activities..."
      :filterable="true"
      :filters="activityFilters"
      :paginated="true"
      :page-size="5"
    >
      <template #cell-type="{ value }">
                 <Badge 
           :variant="getActivityVariant(value) as any" 
           :label="value" 
         />
      </template>
      <template #cell-timestamp="{ value }">
        <span class="text-sm text-gray-500">{{ formatDateTime(value) }}</span>
      </template>
    </Table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ChartBarIcon, ClockIcon, UserGroupIcon, UsersIcon } from '@heroicons/vue/24/outline'
import { Card, MetricCard, Badge, Button, Form, Table } from '@/components/ui'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Reactive data
const selectedDateRange = ref('last_30_days')
const loading = ref(false)

// Sample data - replace with actual API calls
const metrics = ref({
  attendanceRate: 94.5,
  attendanceChange: 2.1,
  activeEmployees: 156,
  employeeChange: 3,
  productivityScore: 8.7,
  productivityChange: 0.3,
  lateArrivals: 12,
  lateChange: -5
})

const departmentData = ref([
  { name: 'Engineering', percentage: 96, color: 'bg-blue-500' },
  { name: 'Sales', percentage: 92, color: 'bg-green-500' },
  { name: 'Marketing', percentage: 89, color: 'bg-purple-500' },
  { name: 'HR', percentage: 98, color: 'bg-yellow-500' },
  { name: 'Finance', percentage: 94, color: 'bg-red-500' }
])

const topPerformers = ref([
  { id: 1, name: 'John Doe', department: 'Engineering', score: 100 },
  { id: 2, name: 'Jane Smith', department: 'Sales', score: 98 },
  { id: 3, name: 'Mike Johnson', department: 'Marketing', score: 97 }
])

const attendanceIssues = ref([
  { id: 1, employee: 'Alex Brown', description: 'Frequent late arrivals', severity: 'High' },
  { id: 2, employee: 'Sarah Wilson', description: 'Missed check-out', severity: 'Medium' },
  { id: 3, employee: 'Tom Davis', description: 'Unusual pattern', severity: 'Low' }
])

const recentActivity = ref([
  { id: 1, user: 'John Doe', type: 'Check In', timestamp: '2024-01-15 09:00:00', location: 'Main Office' },
  { id: 2, user: 'Jane Smith', type: 'Check Out', timestamp: '2024-01-15 18:00:00', location: 'Main Office' },
  { id: 3, user: 'Admin', type: 'Report Generated', timestamp: '2024-01-15 10:30:00', location: 'System' }
])

 // Table configuration
 const activityColumns = [
   { key: 'user', label: 'User', sortable: true },
   { key: 'type', label: 'Type', sortable: true },
   { key: 'timestamp', label: 'Time', sortable: true, type: 'date' as const },
   { key: 'location', label: 'Location', sortable: true }
 ]

const activityFilters = [
  {
    key: 'type',
    label: 'Type',
    options: [
      { value: 'Check In', label: 'Check In' },
      { value: 'Check Out', label: 'Check Out' },
      { value: 'Report Generated', label: 'Report Generated' }
    ]
  }
]

// Options
const dateRangeOptions = [
  { value: 'last_7_days', label: 'Last 7 Days' },
  { value: 'last_30_days', label: 'Last 30 Days' },
  { value: 'last_90_days', label: 'Last 90 Days' },
  { value: 'this_year', label: 'This Year' }
]

 // Utility functions
 function getSeverityColor(severity: string) {
   const colors: Record<string, string> = {
     'High': 'bg-red-500',
     'Medium': 'bg-yellow-500',
     'Low': 'bg-green-500'
   }
   return colors[severity] || 'bg-gray-500'
 }
 
 function getSeverityVariant(severity: string) {
   const variants: Record<string, string> = {
     'High': 'danger',
     'Medium': 'warning',
     'Low': 'success'
   }
   return variants[severity] || 'gray'
 }
 
 function getActivityVariant(type: string) {
   const variants: Record<string, string> = {
     'Check In': 'success',
     'Check Out': 'info',
     'Report Generated': 'primary'
   }
   return variants[type] || 'gray'
 }

function formatDateTime(value: string) {
  return new Date(value).toLocaleString()
}

async function exportData() {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Report exported successfully!')
  } catch (error) {
    toast.error('Failed to export report')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Load analytics data
})
</script> 