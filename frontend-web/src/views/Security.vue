<template>
  <div class="p-6 space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Security Dashboard</h1>
        <p class="text-gray-600">Monitor system security and audit logs</p>
      </div>
      <div class="flex items-center space-x-3">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 rounded-full bg-green-500"></div>
          <span class="text-sm font-medium text-gray-700">System Secure</span>
        </div>
        <Form
          type="select"
          v-model="selectedTimeframe"
          :options="timeframeOptions"
          placeholder="Select timeframe"
        />
        <Button variant="primary" @click="exportSecurityReport">
          Export Report
        </Button>
      </div>
    </div>

    <!-- Security Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        label="Failed Logins"
        :value="metrics.failedLogins"
        :icon="ShieldExclamationIcon"
        icon-bg="red"
        :change="metrics.failedLoginsChange"
        change-unit=""
        description="last 24 hours"
      />
      
      <MetricCard
        label="Active Sessions"
        :value="metrics.activeSessions"
        :icon="UserGroupIcon"
        icon-bg="blue"
        :change="metrics.activeSessionsChange"
        change-unit=""
        description="currently online"
      />
      
      <MetricCard
        label="Security Score"
        :value="metrics.securityScore + '/100'"
        :icon="ShieldCheckIcon"
        icon-bg="green"
        :change="metrics.securityScoreChange"
        description="overall rating"
      />
      
      <MetricCard
        label="Blocked IPs"
        :value="metrics.blockedIPs"
        :icon="NoSymbolIcon"
        icon-bg="yellow"
        :change="metrics.blockedIPsChange"
        change-unit=""
        description="auto-blocked"
      />
    </div>

    <!-- Security Alerts & System Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Security Alerts -->
      <Card title="Security Alerts" subtitle="Recent security incidents">
        <template #headerActions>
          <Badge variant="danger" :label="`${securityAlerts.length} active`" />
        </template>

        <div class="space-y-3">
          <div v-for="alert in securityAlerts" :key="alert.id" class="flex items-center justify-between p-3 rounded-lg border">
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 rounded-full" :class="getSeverityColor(alert.severity)"></div>
              <div>
                <p class="text-sm font-medium">{{ alert.title }}</p>
                <p class="text-xs text-gray-500">{{ alert.description }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(alert.timestamp) }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <Badge :variant="getSeverityVariant(alert.severity) as any" :label="alert.severity" />
              <Button variant="ghost" size="sm" @click="resolveAlert(alert.id)">
                Resolve
              </Button>
            </div>
          </div>
        </div>
      </Card>

      <!-- System Status -->
      <Card title="System Status" subtitle="Security system health">
        <div class="space-y-4">
          <div v-for="status in systemStatus" :key="status.component" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full" :class="status.status === 'healthy' ? 'bg-green-500' : status.status === 'warning' ? 'bg-yellow-500' : 'bg-red-500'"></div>
              <div>
                <p class="text-sm font-medium">{{ status.component }}</p>
                <p class="text-xs text-gray-500">{{ status.description }}</p>
              </div>
            </div>
            <Badge 
              :variant="status.status === 'healthy' ? 'success' : status.status === 'warning' ? 'warning' : 'danger'" 
              :label="status.status" 
            />
          </div>
        </div>
      </Card>
    </div>

    <!-- Security Controls -->
    <Card title="Security Controls" subtitle="Manage system security settings">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Two-Factor Authentication -->
        <div class="p-4 border rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Two-Factor Authentication</h4>
              <p class="text-xs text-gray-500">Require 2FA for all admin accounts</p>
            </div>
            <Badge variant="success" label="Enabled" />
          </div>
          <Button variant="secondary" size="sm" @click="toggle2FA">
            Configure 2FA
          </Button>
        </div>

        <!-- Password Policy -->
        <div class="p-4 border rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Password Policy</h4>
              <p class="text-xs text-gray-500">Enforce strong password requirements</p>
            </div>
            <Badge variant="success" label="Active" />
          </div>
          <Button variant="secondary" size="sm" @click="editPasswordPolicy">
            Edit Policy
          </Button>
        </div>

        <!-- Session Management -->
        <div class="p-4 border rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Session Management</h4>
              <p class="text-xs text-gray-500">Auto-logout after 30 minutes</p>
            </div>
            <Badge variant="info" label="30 min" />
          </div>
          <Button variant="secondary" size="sm" @click="configureSession">
            Configure
          </Button>
        </div>

        <!-- Audit Logging -->
        <div class="p-4 border rounded-lg">
          <div class="flex items-center justify-between mb-3">
            <div>
              <h4 class="text-sm font-medium text-gray-900">Audit Logging</h4>
              <p class="text-xs text-gray-500">Track all system activities</p>
            </div>
            <Badge variant="success" label="Active" />
          </div>
          <Button variant="secondary" size="sm" @click="viewAuditLogs">
            View Logs
          </Button>
        </div>
      </div>
    </Card>

    <!-- Audit Logs Table -->
    <Table
      title="Audit Logs"
      subtitle="Complete system activity history"
      :data="auditLogs"
      :columns="auditColumns"
      searchable
      search-placeholder="Search logs..."
      :filterable="true"
      :filters="auditFilters"
      :paginated="true"
      :page-size="10"
      :loading="logsLoading"
    >
      <template #headerActions>
        <Button variant="secondary" @click="refreshLogs">
          Refresh
        </Button>
      </template>

      <template #cell-action="{ value }">
        <Badge :variant="getActionVariant(value) as any" :label="value" />
      </template>

      <template #cell-risk_level="{ value }">
        <Badge :variant="getRiskVariant(value) as any" :label="value" />
      </template>

      <template #cell-timestamp="{ value }">
        <span class="text-sm text-gray-500">{{ formatDateTime(value) }}</span>
      </template>
    </Table>

    <!-- Security Settings Modal -->
    <Modal v-model:show="showSettingsModal" title="Security Settings" size="lg">
      <div class="space-y-6">
        <div>
          <h4 class="text-md font-medium mb-4">Password Policy</h4>
          <div class="space-y-4">
            <Form
              v-model="passwordPolicy.minLength"
              type="number"
              label="Minimum Length"
              :required="true"
            />
            <Form
              v-model="passwordPolicy.requireSpecial"
              type="select"
              label="Require Special Characters"
              :options="booleanOptions"
            />
            <Form
              v-model="passwordPolicy.expiration"
              type="number"
              label="Password Expiration (days)"
            />
          </div>
        </div>

        <div>
          <h4 class="text-md font-medium mb-4">Session Settings</h4>
          <div class="space-y-4">
            <Form
              v-model="sessionSettings.timeout"
              type="number"
              label="Session Timeout (minutes)"
              :required="true"
            />
            <Form
              v-model="sessionSettings.maxConcurrent"
              type="number"
              label="Max Concurrent Sessions"
            />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="showSettingsModal = false">Cancel</Button>
          <Button variant="primary" @click="saveSecuritySettings">Save Settings</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  ShieldCheckIcon, 
  ShieldExclamationIcon, 
  UserGroupIcon, 
  NoSymbolIcon 
} from '@heroicons/vue/24/outline'
import { Card, MetricCard, Badge, Button, Form, Table, Modal } from '@/components/ui'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Reactive data
const selectedTimeframe = ref('last_24_hours')
const logsLoading = ref(false)
const showSettingsModal = ref(false)

// Sample data
const metrics = ref({
  failedLogins: 23,
  failedLoginsChange: -12,
  activeSessions: 87,
  activeSessionsChange: 5,
  securityScore: 94,
  securityScoreChange: 2,
  blockedIPs: 15,
  blockedIPsChange: 3
})

const securityAlerts = ref([
  {
    id: 1,
    title: 'Multiple Failed Login Attempts',
    description: 'User admin@company.com - 5 failed attempts',
    severity: 'High',
    timestamp: '2024-01-15 14:30:00'
  },
  {
    id: 2,
    title: 'Suspicious IP Activity',
    description: 'IP 192.168.1.100 - Unusual access pattern',
    severity: 'Medium',
    timestamp: '2024-01-15 13:45:00'
  },
  {
    id: 3,
    title: 'Session Timeout Warning',
    description: 'Multiple users experiencing session timeouts',
    severity: 'Low',
    timestamp: '2024-01-15 12:15:00'
  }
])

const systemStatus = ref([
  { component: 'Authentication Service', status: 'healthy', description: 'All systems operational' },
  { component: 'Database Security', status: 'healthy', description: 'Encrypted connections active' },
  { component: 'Firewall', status: 'warning', description: 'High traffic detected' },
  { component: 'Audit System', status: 'healthy', description: 'Logging all activities' }
])

const auditLogs = ref([
  {
    id: 1,
    user: 'admin@company.com',
    action: 'Login',
    resource: 'Dashboard',
    ip_address: '192.168.1.100',
    risk_level: 'Low',
    timestamp: '2024-01-15 14:30:00'
  },
  {
    id: 2,
    user: 'john.doe@company.com',
    action: 'Data Export',
    resource: 'Employee Records',
    ip_address: '192.168.1.105',
    risk_level: 'Medium',
    timestamp: '2024-01-15 14:25:00'
  },
  {
    id: 3,
    user: 'system',
    action: 'Security Scan',
    resource: 'All Systems',
    ip_address: 'localhost',
    risk_level: 'Low',
    timestamp: '2024-01-15 14:00:00'
  }
])

const passwordPolicy = ref({
  minLength: 8,
  requireSpecial: 'true',
  expiration: 90
})

const sessionSettings = ref({
  timeout: 30,
  maxConcurrent: 3
})

// Options
const timeframeOptions = [
  { value: 'last_24_hours', label: 'Last 24 Hours' },
  { value: 'last_7_days', label: 'Last 7 Days' },
  { value: 'last_30_days', label: 'Last 30 Days' }
]

const booleanOptions = [
  { value: 'true', label: 'Yes' },
  { value: 'false', label: 'No' }
]

// Table configuration
const auditColumns = [
  { key: 'user', label: 'User', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'resource', label: 'Resource', sortable: true },
  { key: 'ip_address', label: 'IP Address', sortable: true },
  { key: 'risk_level', label: 'Risk Level', sortable: true },
  { key: 'timestamp', label: 'Timestamp', sortable: true, type: 'date' as const }
]

const auditFilters = [
  {
    key: 'action',
    label: 'Action',
    options: [
      { value: 'Login', label: 'Login' },
      { value: 'Data Export', label: 'Data Export' },
      { value: 'Security Scan', label: 'Security Scan' }
    ]
  },
  {
    key: 'risk_level',
    label: 'Risk Level',
    options: [
      { value: 'Low', label: 'Low' },
      { value: 'Medium', label: 'Medium' },
      { value: 'High', label: 'High' }
    ]
  }
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

function getActionVariant(action: string) {
  const variants: Record<string, string> = {
    'Login': 'info',
    'Data Export': 'warning',
    'Security Scan': 'primary'
  }
  return variants[action] || 'gray'
}

function getRiskVariant(risk: string) {
  const variants: Record<string, string> = {
    'Low': 'success',
    'Medium': 'warning',
    'High': 'danger'
  }
  return variants[risk] || 'gray'
}

function formatDateTime(value: string) {
  return new Date(value).toLocaleString()
}

// Actions
async function exportSecurityReport() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Security report exported successfully!')
  } catch (error) {
    toast.error('Failed to export security report')
  }
}

function resolveAlert(alertId: number) {
  const index = securityAlerts.value.findIndex(alert => alert.id === alertId)
  if (index > -1) {
    securityAlerts.value.splice(index, 1)
    toast.success('Alert resolved successfully')
  }
}

function toggle2FA() {
  toast.info('Redirecting to 2FA configuration...')
}

function editPasswordPolicy() {
  showSettingsModal.value = true
}

function configureSession() {
  showSettingsModal.value = true
}

function viewAuditLogs() {
  toast.info('Scrolling to audit logs section...')
}

async function refreshLogs() {
  logsLoading.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Audit logs refreshed')
  } catch (error) {
    toast.error('Failed to refresh logs')
  } finally {
    logsLoading.value = false
  }
}

async function saveSecuritySettings() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    showSettingsModal.value = false
    toast.success('Security settings saved successfully!')
  } catch (error) {
    toast.error('Failed to save security settings')
  }
}

onMounted(() => {
  // Load security data
})
</script> 