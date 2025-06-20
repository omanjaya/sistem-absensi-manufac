<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Time Tracking</h1>
            <p class="mt-1 text-sm text-gray-500">
              Track your work hours and manage time entries
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <Form
              type="select"
              v-model="selectedProject"
              :options="projectOptions"
              placeholder="Select project"
            />
            <Button variant="primary" @click="showCreateEntryModal = true">
              Manual Entry
            </Button>
          </div>
        </div>
      </div>
    </div>

    <div class="p-6 space-y-6">
      <!-- Active Timer -->
      <Card title="Active Timer" subtitle="Start tracking time for your current task">
        <div class="text-center space-y-6">
          <!-- Timer Display -->
          <div class="text-6xl font-mono font-bold text-gray-900">
            {{ formatTime(currentTime) }}
          </div>

          <!-- Current Task Info -->
          <div v-if="isTracking" class="space-y-2">
            <p class="text-lg font-medium text-gray-900">{{ currentTask.project_name }}</p>
            <p class="text-gray-600">{{ currentTask.description }}</p>
            <Badge variant="success" label="Recording" />
          </div>

          <!-- Timer Controls -->
          <div class="flex items-center justify-center space-x-4">
            <Button
              v-if="!isTracking"
              variant="primary"
              size="lg"
              @click="startTimer"
              :disabled="!selectedProject"
            >
              <PlayIcon class="w-5 h-5 mr-2" />
              Start Timer
            </Button>

            <template v-else>
              <Button
                variant="warning"
                size="lg"
                @click="pauseTimer"
                v-if="!isPaused"
              >
                <PauseIcon class="w-5 h-5 mr-2" />
                Pause
              </Button>

              <Button
                variant="primary"
                size="lg"
                @click="resumeTimer"
                v-else
              >
                <PlayIcon class="w-5 h-5 mr-2" />
                Resume
              </Button>

              <Button
                variant="danger"
                size="lg"
                @click="stopTimer"
              >
                <StopIcon class="w-5 h-5 mr-2" />
                Stop
              </Button>
            </template>
          </div>

          <!-- Quick Task Description -->
          <div v-if="isTracking" class="max-w-md mx-auto">
            <Form
              v-model="currentTask.description"
              placeholder="What are you working on?"
              @input="updateTaskDescription"
            />
          </div>
        </div>
      </Card>

      <!-- Today's Summary -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <MetricCard
          label="Today's Total"
          :value="formatTime(todayStats.totalTime)"
          :icon="ClockIcon"
          icon-bg="blue"
          description="total hours worked"
          show-change="false"
        />
        
        <MetricCard
          label="Active Projects"
          :value="todayStats.activeProjects"
          :icon="FolderIcon"
          icon-bg="green"
          description="projects worked on"
          show-change="false"
        />
        
        <MetricCard
          label="Completed Tasks"
          :value="todayStats.completedTasks"
          :icon="CheckCircleIcon"
          icon-bg="purple"
          description="tasks finished"
          show-change="false"
        />
        
        <MetricCard
          label="Productivity"
          :value="todayStats.productivity + '%'"
          :icon="ChartBarIcon"
          icon-bg="yellow"
          description="vs yesterday"
          show-change="false"
        />
      </div>

      <!-- Weekly Overview -->
      <Card title="Weekly Overview" subtitle="Time tracking summary for this week">
        <div class="space-y-4">
          <div v-for="day in weeklyData" :key="day.date" class="flex items-center justify-between p-3 rounded-lg border">
            <div class="flex items-center space-x-3">
              <div class="w-3 h-3 rounded-full" :class="day.isToday ? 'bg-blue-500' : 'bg-gray-300'"></div>
              <div>
                <p class="text-sm font-medium">{{ day.dayName }}</p>
                <p class="text-xs text-gray-500">{{ day.date }}</p>
              </div>
            </div>
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <p class="text-sm font-medium">{{ formatTime(day.totalTime) }}</p>
                <p class="text-xs text-gray-500">{{ day.projectCount }} projects</p>
              </div>
              <div class="w-24 bg-gray-200 rounded-full h-2">
                <div 
                  class="h-2 rounded-full bg-blue-500"
                  :style="`width: ${(day.totalTime / 28800) * 100}%`"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Recent Time Entries -->
      <Table
        title="Recent Time Entries"
        subtitle="Your time tracking history"
        :data="timeEntries"
        :columns="timeColumns"
        searchable
        search-placeholder="Search entries..."
        :filterable="true"
        :filters="timeFilters"
        :paginated="true"
        :page-size="10"
      >
        <template #headerActions>
          <div class="flex space-x-2">
            <Button variant="secondary" @click="exportTimeEntries">
              Export CSV
            </Button>
            <Button variant="primary" @click="showCreateEntryModal = true">
              Add Manual Entry
            </Button>
          </div>
        </template>

        <template #cell-project="{ item }">
          <div class="flex items-center space-x-2">
            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
            <span class="text-sm font-medium">{{ item.project_name }}</span>
          </div>
        </template>

        <template #cell-duration="{ value }">
          <span class="text-sm font-medium">{{ formatTime(value) }}</span>
        </template>

        <template #cell-date="{ value }">
          <span class="text-sm text-gray-900">{{ formatDate(value) }}</span>
        </template>

        <template #cell-actions="{ item }">
          <div class="flex space-x-1">
            <Button variant="ghost" size="sm" @click="editTimeEntry(item)">
              Edit
            </Button>
            <Button variant="ghost" size="sm" @click="deleteTimeEntry(item.id)">
              Delete
            </Button>
          </div>
        </template>
      </Table>

      <!-- Manual Time Entry Modal -->
      <Modal v-model:show="showCreateEntryModal" :title="editingEntry ? 'Edit Time Entry' : 'Add Manual Time Entry'" size="lg">
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Form
              v-model="entryForm.project_id"
              type="select"
              label="Project"
              :options="projectOptions"
              :required="true"
              :error="entryErrors.project_id"
            />
            
            <Form
              v-model="entryForm.date"
              type="date"
              label="Date"
              :required="true"
            />
          </div>

          <Form
            v-model="entryForm.description"
            type="textarea"
            label="Task Description"
            placeholder="What did you work on?"
            :rows="3"
            :required="true"
          />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Form
              v-model="entryForm.start_time"
              type="time"
              label="Start Time"
              :required="true"
            />
            
            <Form
              v-model="entryForm.end_time"
              type="time"
              label="End Time"
              :required="true"
            />
          </div>

          <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
              Duration: <span class="font-medium">{{ calculateDuration() }}</span>
            </p>
          </div>
        </div>

        <template #footer>
          <div class="flex space-x-2">
            <Button variant="secondary" @click="closeEntryModal">Cancel</Button>
            <Button variant="primary" @click="saveTimeEntry" :loading="saving">
              {{ editingEntry ? 'Update' : 'Save' }} Entry
            </Button>
          </div>
        </template>
      </Modal>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { 
  PlayIcon, 
  PauseIcon, 
  StopIcon, 
  ClockIcon, 
  FolderIcon, 
  CheckCircleIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline'
import { Card, MetricCard, Badge, Button, Form, Table, Modal } from '@/components/ui'

const toast = useToast()

// State
const selectedProject = ref('')
const showCreateEntryModal = ref(false)
const editingEntry = ref(false)
const saving = ref(false)
const isTracking = ref(false)
const isPaused = ref(false)
const isRunning = ref(false)
const currentTime = ref(0)
const startTime = ref<Date | null>(null)
const pausedTime = ref(0)
const timerInterval = ref<number | null>(null)

const activeEntry = reactive({
  project_id: '',
  description: '',
  start_time: null as Date | null,
  end_time: null as Date | null
})

// Sample data
const projects = ref([
  { id: 1, name: 'Website Redesign' },
  { id: 2, name: 'Mobile App Development' },
  { id: 3, name: 'Database Migration' }
])

const timeEntries = ref([
  {
    id: 1,
    project_name: 'Employee Portal',
    description: 'Frontend development - login page',
    duration: 7200, // 2 hours
    date: '2024-01-19',
    start_time: '09:00',
    end_time: '11:00'
  },
  {
    id: 2,
    project_name: 'Mobile App',
    description: 'Bug fixes and testing',
    duration: 5400, // 1.5 hours
    date: '2024-01-19',
    start_time: '13:00',
    end_time: '14:30'
  },
  {
    id: 3,
    project_name: 'Database Migration',
    description: 'Data migration scripts',
    duration: 10800, // 3 hours
    date: '2024-01-18',
    start_time: '14:00',
    end_time: '17:00'
  }
])

const todayStats = ref({
  totalTime: 25200, // 7 hours in seconds
  activeProjects: 3,
  completedTasks: 8,
  productivity: 85
})

const currentTask = ref({
  project_name: '',
  description: '',
  project_id: ''
})

const weeklyData = ref([
  { date: '2024-01-15', dayName: 'Monday', totalTime: 28800, projectCount: 4, isToday: false },
  { date: '2024-01-16', dayName: 'Tuesday', totalTime: 25200, projectCount: 3, isToday: false },
  { date: '2024-01-17', dayName: 'Wednesday', totalTime: 30600, projectCount: 5, isToday: false },
  { date: '2024-01-18', dayName: 'Thursday', totalTime: 27000, projectCount: 3, isToday: false },
  { date: '2024-01-19', dayName: 'Friday', totalTime: 25200, projectCount: 3, isToday: true },
  { date: '2024-01-20', dayName: 'Saturday', totalTime: 0, projectCount: 0, isToday: false },
  { date: '2024-01-21', dayName: 'Sunday', totalTime: 0, projectCount: 0, isToday: false }
])

const entryForm = ref({
  project_id: '',
  description: '',
  date: new Date().toISOString().split('T')[0],
  start_time: '',
  end_time: ''
})

const entryErrors = ref({
  project_id: ''
})

// Options
const projectOptions = computed(() => [
  { value: '', label: 'Select Project' },
  ...projects.value.map(p => ({ value: p.id.toString(), label: p.name }))
])

// Table configuration
const timeColumns = [
  { key: 'project', label: 'Project', sortable: true },
  { key: 'description', label: 'Description', sortable: false },
  { key: 'duration', label: 'Duration', sortable: true },
  { key: 'date', label: 'Date', sortable: true },
  { key: 'actions', label: 'Actions', sortable: false }
]

const timeFilters = [
  {
    key: 'project_name',
    label: 'Project',
    options: projects.value.map(p => ({ value: p.name, label: p.name }))
  }
]

// Methods
function formatTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}

function calculateDuration() {
  if (!entryForm.value.start_time || !entryForm.value.end_time) return '0:00'
  
  const start = new Date(`2024-01-01 ${entryForm.value.start_time}`)
  const end = new Date(`2024-01-01 ${entryForm.value.end_time}`)
  const diff = end.getTime() - start.getTime()
  
  if (diff <= 0) return '0:00'
  
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  
  return `${hours}:${minutes.toString().padStart(2, '0')}`
}

function getProjectName(projectId: number): string {
  const project = projects.value.find(p => p.id === projectId)
  return project?.name || 'Unknown Project'
}

function startTimer() {
  if (!activeEntry.project_id) {
    toast.error('Please select a project first')
    return
  }
  
  isRunning.value = true
  startTime.value = new Date()
  activeEntry.start_time = startTime.value
  
  timerInterval.value = setInterval(() => {
    currentTime.value++
  }, 1000)
  
  toast.success('Timer started!')
}

function pauseTimer() {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  isRunning.value = false
  toast.info('Timer paused')
}

function stopTimer() {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  
  if (currentTime.value > 0) {
    // Save the time entry
    const entry = {
      id: timeEntries.value.length + 1,
      project_id: parseInt(activeEntry.project_id),
      description: activeEntry.description,
      duration: currentTime.value,
      date: new Date().toISOString().split('T')[0],
      start_time: activeEntry.start_time?.toTimeString().slice(0, 5) || '',
      end_time: new Date().toTimeString().slice(0, 5)
    }
    
    timeEntries.value.unshift(entry)
    toast.success(`Time entry saved: ${formatTime(currentTime.value)}`)
  }
  
  // Reset timer
  isRunning.value = false
  currentTime.value = 0
  activeEntry.project_id = ''
  activeEntry.description = ''
  activeEntry.start_time = null
  activeEntry.end_time = null
}

function editEntry(entry: any) {
  toast.info('Edit functionality would be implemented here')
}

function deleteEntry(entryId: number) {
  const index = timeEntries.value.findIndex(entry => entry.id === entryId)
  if (index !== -1) {
    timeEntries.value.splice(index, 1)
    toast.success('Time entry deleted')
  }
}

// Cleanup timer on unmount
onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
})
</script> 