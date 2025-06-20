<template>
  <div class="p-6 space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Workflow Management</h1>
        <p class="text-gray-600">Manage approval workflows and system automation</p>
      </div>
      <div class="flex items-center space-x-3">
        <Form
          type="select"
          v-model="selectedWorkflow"
          :options="workflowOptions"
          placeholder="Select workflow"
        />
        <Button variant="primary" @click="showCreateModal = true">
          Create Workflow
        </Button>
      </div>
    </div>

    <!-- Workflow Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        label="Pending Approvals"
        :value="stats.pendingApprovals"
        :icon="ClockIcon"
        icon-bg="yellow"
        :change="stats.pendingChange"
        change-unit=""
        description="awaiting action"
      />
      
      <MetricCard
        label="Completed Today"
        :value="stats.completedToday"
        :icon="CheckCircleIcon"
        icon-bg="green"
        :change="stats.completedChange"
        change-unit=""
        description="processed workflows"
      />
      
      <MetricCard
        label="Active Workflows"
        :value="stats.activeWorkflows"
        :icon="Cog6ToothIcon"
        icon-bg="blue"
        :change="stats.activeChange"
        change-unit=""
        description="currently running"
      />
      
      <MetricCard
        label="Efficiency Rate"
        :value="stats.efficiencyRate + '%'"
        :icon="ChartBarIcon"
        icon-bg="purple"
        :change="stats.efficiencyChange"
        description="workflow completion"
      />
    </div>

    <!-- Quick Actions & Approval Queue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Quick Actions -->
      <Card title="Quick Actions" subtitle="Common workflow operations">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" @click="bulkApproveLeaves">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <CheckCircleIcon class="w-6 h-6 text-green-600" />
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">Approve Leaves</h4>
                <p class="text-xs text-gray-500">{{ pendingLeaves.length }} pending</p>
              </div>
            </div>
          </div>

          <div class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" @click="generateSalaries">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <CurrencyDollarIcon class="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">Generate Salaries</h4>
                <p class="text-xs text-gray-500">For current period</p>
              </div>
            </div>
          </div>

          <div class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" @click="sendNotifications">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <BellIcon class="w-6 h-6 text-purple-600" />
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">Send Notifications</h4>
                <p class="text-xs text-gray-500">Bulk messaging</p>
              </div>
            </div>
          </div>

          <div class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer" @click="exportReports">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <DocumentArrowDownIcon class="w-6 h-6 text-yellow-600" />
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900">Export Reports</h4>
                <p class="text-xs text-gray-500">Monthly summaries</p>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Approval Queue -->
      <Card title="Approval Queue" subtitle="Items requiring your attention">
        <template #headerActions>
          <Badge variant="warning" :label="`${approvalQueue.length} pending`" />
        </template>

        <div class="space-y-3">
          <div v-for="item in approvalQueue.slice(0, 5)" :key="item.id" class="flex items-center justify-between p-3 rounded-lg border">
            <div class="flex items-center space-x-3">
              <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
              <div>
                <p class="text-sm font-medium">{{ item.title }}</p>
                <p class="text-xs text-gray-500">{{ item.description }}</p>
                <p class="text-xs text-gray-400">{{ formatDateTime(item.created_at) }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <Badge :variant="getPriorityVariant(item.priority)" :label="item.priority" />
              <Button variant="ghost" size="sm" @click="approveItem(item.id)">
                Approve
              </Button>
            </div>
          </div>
        </div>

        <template #footer>
          <Button variant="secondary" @click="showApprovalModal = true" full-width>
            View All Pending
          </Button>
        </template>
      </Card>
    </div>

    <!-- System Status -->
    <Card title="System Status" subtitle="Workflow automation health">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <CheckCircleIcon class="w-8 h-8 text-green-600" />
          </div>
          <h4 class="text-lg font-semibold text-gray-900">{{ systemStatus.queueSize }}</h4>
          <p class="text-sm text-gray-500">Queue Size</p>
        </div>

        <div class="text-center p-4">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <Cog6ToothIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h4 class="text-lg font-semibold text-gray-900">{{ systemStatus.runningJobs }}</h4>
          <p class="text-sm text-gray-500">Running Jobs</p>
        </div>

        <div class="text-center p-4">
          <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <ClockIcon class="w-8 h-8 text-purple-600" />
          </div>
          <h4 class="text-lg font-semibold text-gray-900">{{ systemStatus.avgProcessTime }}</h4>
          <p class="text-sm text-gray-500">Avg Process Time</p>
        </div>
      </div>
    </Card>

    <!-- Workflow History -->
    <Table
      title="Workflow History"
      subtitle="Recent workflow executions and their status"
      :data="workflowHistory"
      :columns="historyColumns"
      searchable
      search-placeholder="Search workflows..."
      :filterable="true"
      :filters="historyFilters"
      :paginated="true"
      :page-size="10"
    >
      <template #headerActions>
        <Button variant="secondary" @click="refreshHistory">
          Refresh
        </Button>
      </template>

      <template #cell-status="{ value }">
        <Badge :variant="getStatusVariant(value)" :label="value" />
      </template>

      <template #cell-type="{ value }">
        <Badge variant="info" :label="value" />
      </template>

      <template #cell-duration="{ value }">
        <span class="text-sm text-gray-900">{{ value }}s</span>
      </template>

      <template #cell-created_at="{ value }">
        <span class="text-sm text-gray-500">{{ formatDateTime(value) }}</span>
      </template>

      <template #cell-actions="{ item }">
        <div class="flex space-x-1">
          <Button variant="ghost" size="sm" @click="viewWorkflowDetails(item)">
            View
          </Button>
          <Button variant="ghost" size="sm" @click="retryWorkflow(item.id)" v-if="item.status === 'failed'">
            Retry
          </Button>
        </div>
      </template>
    </Table>

    <!-- Bulk Approval Modal -->
    <Modal v-model:show="showApprovalModal" title="Bulk Approval" size="xl">
      <div class="space-y-6">
        <!-- Filter Controls -->
        <div class="flex items-center space-x-4">
          <Form
            type="select"
            v-model="approvalFilters.type"
            :options="approvalTypeOptions"
            placeholder="Filter by type"
          />
          <Form
            type="select"
            v-model="approvalFilters.priority"
            :options="priorityOptions"
            placeholder="Filter by priority"
          />
        </div>

        <!-- Approval List -->
        <div class="space-y-3 max-h-96 overflow-y-auto">
          <div v-for="item in filteredApprovals" :key="item.id" class="flex items-center space-x-3 p-3 border rounded-lg">
            <input
              type="checkbox"
              :id="`approval-${item.id}`"
              v-model="selectedApprovals"
              :value="item.id"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <label :for="`approval-${item.id}`" class="text-sm font-medium text-gray-900 cursor-pointer">
                  {{ item.title }}
                </label>
                <Badge :variant="getPriorityVariant(item.priority)" :label="item.priority" />
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ item.description }}</p>
              <p class="text-xs text-gray-400">{{ formatDateTime(item.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div class="text-sm text-gray-600">
            {{ selectedApprovals.length }} items selected
          </div>
          <div class="flex space-x-2">
            <Button variant="danger" @click="bulkReject" :disabled="selectedApprovals.length === 0">
              Reject Selected
            </Button>
            <Button variant="primary" @click="bulkApprove" :disabled="selectedApprovals.length === 0">
              Approve Selected
            </Button>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="showApprovalModal = false">Close</Button>
        </div>
      </template>
    </Modal>

    <!-- Create Workflow Modal -->
    <Modal v-model:show="showCreateModal" title="Create New Workflow" size="lg">
      <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Form
            v-model="workflowForm.name"
            label="Workflow Name"
            placeholder="Enter workflow name"
            :required="true"
            :error="workflowErrors.name"
          />
          
          <Form
            v-model="workflowForm.type"
            type="select"
            label="Workflow Type"
            :options="workflowTypeOptions"
            :required="true"
          />
        </div>

        <Form
          v-model="workflowForm.description"
          type="textarea"
          label="Description"
          placeholder="Describe the workflow purpose"
          :rows="3"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Form
            v-model="workflowForm.trigger"
            type="select"
            label="Trigger"
            :options="triggerOptions"
            :required="true"
          />
          
          <Form
            v-model="workflowForm.priority"
            type="select"
            label="Priority"
            :options="priorityOptions"
            :required="true"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Workflow Steps</label>
          <div class="space-y-2">
            <div v-for="(step, index) in workflowForm.steps" :key="index" class="flex items-center space-x-2">
              <Form
                v-model="step.action"
                placeholder="Step action"
                class="flex-1"
              />
              <Button variant="danger" size="sm" @click="removeStep(index)">
                Remove
              </Button>
            </div>
            <Button variant="secondary" @click="addStep">
              Add Step
            </Button>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="closeCreateModal">Cancel</Button>
          <Button variant="primary" @click="saveWorkflow" :loading="saving">
            Create Workflow
          </Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ClockIcon, 
  CheckCircleIcon, 
  Cog6ToothIcon, 
  ChartBarIcon,
  CurrencyDollarIcon,
  BellIcon,
  DocumentArrowDownIcon
} from '@heroicons/vue/24/outline'
import { Card, MetricCard, Badge, Button, Form, Table, Modal } from '@/components/ui'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Reactive data
const selectedWorkflow = ref('')
const showApprovalModal = ref(false)
const showCreateModal = ref(false)
const saving = ref(false)
const selectedApprovals = ref([])

// Sample data
const stats = ref({
  pendingApprovals: 23,
  pendingChange: 5,
  completedToday: 47,
  completedChange: 12,
  activeWorkflows: 8,
  activeChange: 2,
  efficiencyRate: 94,
  efficiencyChange: 3
})

const systemStatus = ref({
  queueSize: 156,
  runningJobs: 7,
  avgProcessTime: '2.3s'
})

const pendingLeaves = ref([
  { id: 1, employee: 'John Doe', type: 'Annual Leave', days: 3 },
  { id: 2, employee: 'Jane Smith', type: 'Sick Leave', days: 1 },
  { id: 3, employee: 'Mike Johnson', type: 'Personal Leave', days: 2 }
])

const approvalQueue = ref([
  {
    id: 1,
    title: 'Leave Request - John Doe',
    description: 'Annual leave for 3 days starting Jan 20',
    priority: 'Medium',
    type: 'Leave',
    created_at: '2024-01-19 09:30:00'
  },
  {
    id: 2,
    title: 'Salary Adjustment - Jane Smith',
    description: 'Promotion salary increase request',
    priority: 'High',
    type: 'Salary',
    created_at: '2024-01-19 08:15:00'
  },
  {
    id: 3,
    title: 'Overtime Request - Mike Johnson',
    description: 'Weekend overtime approval needed',
    priority: 'Low',
    type: 'Overtime',
    created_at: '2024-01-18 16:45:00'
  }
])

const workflowHistory = ref([
  {
    id: 1,
    name: 'Monthly Salary Generation',
    type: 'Salary',
    status: 'completed',
    duration: 45,
    created_at: '2024-01-19 02:00:00'
  },
  {
    id: 2,
    name: 'Leave Approval Notification',
    type: 'Notification',
    status: 'running',
    duration: 0,
    created_at: '2024-01-19 10:30:00'
  },
  {
    id: 3,
    name: 'Attendance Report Export',
    type: 'Report',
    status: 'failed',
    duration: 12,
    created_at: '2024-01-19 09:15:00'
  }
])

const approvalFilters = ref({
  type: '',
  priority: ''
})

const workflowForm = ref({
  name: '',
  type: '',
  description: '',
  trigger: '',
  priority: 'Medium',
  steps: [{ action: '' }]
})

const workflowErrors = ref({
  name: ''
})

// Options
const workflowOptions = [
  { value: '', label: 'All Workflows' },
  { value: 'salary', label: 'Salary Workflows' },
  { value: 'leave', label: 'Leave Workflows' },
  { value: 'notification', label: 'Notification Workflows' }
]

const approvalTypeOptions = [
  { value: '', label: 'All Types' },
  { value: 'Leave', label: 'Leave Requests' },
  { value: 'Salary', label: 'Salary Changes' },
  { value: 'Overtime', label: 'Overtime Requests' }
]

const priorityOptions = [
  { value: '', label: 'All Priorities' },
  { value: 'High', label: 'High Priority' },
  { value: 'Medium', label: 'Medium Priority' },
  { value: 'Low', label: 'Low Priority' }
]

const workflowTypeOptions = [
  { value: 'approval', label: 'Approval Workflow' },
  { value: 'automation', label: 'Automation Workflow' },
  { value: 'notification', label: 'Notification Workflow' },
  { value: 'report', label: 'Report Generation' }
]

const triggerOptions = [
  { value: 'manual', label: 'Manual Trigger' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'event', label: 'Event-based' },
  { value: 'api', label: 'API Trigger' }
]

// Table configuration
const historyColumns = [
  { key: 'name', label: 'Workflow Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'duration', label: 'Duration', sortable: true },
  { key: 'created_at', label: 'Started At', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false }
]

const historyFilters = [
  {
    key: 'type',
    label: 'Type',
    options: [
      { value: 'Salary', label: 'Salary' },
      { value: 'Notification', label: 'Notification' },
      { value: 'Report', label: 'Report' }
    ]
  },
  {
    key: 'status',
    label: 'Status',
    options: [
      { value: 'completed', label: 'Completed' },
      { value: 'running', label: 'Running' },
      { value: 'failed', label: 'Failed' }
    ]
  }
]

// Computed
const filteredApprovals = computed(() => {
  let filtered = approvalQueue.value

  if (approvalFilters.value.type) {
    filtered = filtered.filter(item => item.type === approvalFilters.value.type)
  }

  if (approvalFilters.value.priority) {
    filtered = filtered.filter(item => item.priority === approvalFilters.value.priority)
  }

  return filtered
})

// Utility functions
function getPriorityVariant(priority: string) {
  const variants: Record<string, string> = {
    'High': 'danger',
    'Medium': 'warning',
    'Low': 'success'
  }
  return variants[priority] || 'gray'
}

function getStatusVariant(status: string) {
  const variants: Record<string, string> = {
    'completed': 'success',
    'running': 'info',
    'failed': 'danger',
    'pending': 'warning'
  }
  return variants[status] || 'gray'
}

function formatDateTime(value: string) {
  return new Date(value).toLocaleString()
}

// Actions
async function bulkApproveLeaves() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success(`${pendingLeaves.value.length} leave requests approved`)
    pendingLeaves.value = []
  } catch (error) {
    toast.error('Failed to approve leave requests')
  }
}

async function generateSalaries() {
  try {
    await new Promise(resolve => setTimeout(resolve, 2000))
    toast.success('Salary generation completed successfully')
  } catch (error) {
    toast.error('Failed to generate salaries')
  }
}

async function sendNotifications() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    toast.success('Bulk notifications sent successfully')
  } catch (error) {
    toast.error('Failed to send notifications')
  }
}

async function exportReports() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Reports exported successfully')
  } catch (error) {
    toast.error('Failed to export reports')
  }
}

function approveItem(itemId: number) {
  const index = approvalQueue.value.findIndex(item => item.id === itemId)
  if (index > -1) {
    approvalQueue.value.splice(index, 1)
    toast.success('Item approved successfully')
  }
}

async function bulkApprove() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Remove approved items from queue
    approvalQueue.value = approvalQueue.value.filter(item => 
      !selectedApprovals.value.includes(item.id)
    )
    
    toast.success(`${selectedApprovals.value.length} items approved`)
    selectedApprovals.value = []
  } catch (error) {
    toast.error('Failed to approve items')
  }
}

async function bulkReject() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Remove rejected items from queue
    approvalQueue.value = approvalQueue.value.filter(item => 
      !selectedApprovals.value.includes(item.id)
    )
    
    toast.success(`${selectedApprovals.value.length} items rejected`)
    selectedApprovals.value = []
  } catch (error) {
    toast.error('Failed to reject items')
  }
}

function viewWorkflowDetails(workflow: any) {
  toast.info(`Viewing details for: ${workflow.name}`)
}

async function retryWorkflow(workflowId: number) {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const index = workflowHistory.value.findIndex(w => w.id === workflowId)
    if (index > -1) {
      workflowHistory.value[index].status = 'running'
    }
    
    toast.success('Workflow retry initiated')
  } catch (error) {
    toast.error('Failed to retry workflow')
  }
}

async function refreshHistory() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Workflow history refreshed')
  } catch (error) {
    toast.error('Failed to refresh history')
  }
}

// Workflow creation
function addStep() {
  workflowForm.value.steps.push({ action: '' })
}

function removeStep(index: number) {
  workflowForm.value.steps.splice(index, 1)
}

function closeCreateModal() {
  showCreateModal.value = false
  workflowForm.value = {
    name: '',
    type: '',
    description: '',
    trigger: '',
    priority: 'Medium',
    steps: [{ action: '' }]
  }
  workflowErrors.value = { name: '' }
}

async function saveWorkflow() {
  // Validation
  if (!workflowForm.value.name.trim()) {
    workflowErrors.value.name = 'Workflow name is required'
    return
  }

  saving.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Add to workflow history
    const newWorkflow = {
      id: Date.now(),
      name: workflowForm.value.name,
      type: workflowForm.value.type,
      status: 'created',
      duration: 0,
      created_at: new Date().toISOString()
    }
    workflowHistory.value.unshift(newWorkflow)
    
    toast.success('Workflow created successfully!')
    closeCreateModal()
  } catch (error) {
    toast.error('Failed to create workflow')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  // Load workflow data
})
</script> 