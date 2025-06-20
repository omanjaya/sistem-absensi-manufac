<template>
  <div class="p-6 space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Project Management</h1>
        <p class="text-gray-600">Track projects and assign tasks to team members</p>
      </div>
      <div class="flex items-center space-x-3">
        <Form
          type="select"
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <Button variant="primary" @click="showCreateModal = true">
          Create Project
        </Button>
      </div>
    </div>

    <!-- Project Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        label="Total Projects"
        :value="stats.totalProjects"
        :icon="FolderIcon"
        icon-bg="blue"
        :change="stats.totalChange"
        change-unit=""
        description="all time"
      />
      
      <MetricCard
        label="Active Projects"
        :value="stats.activeProjects"
        :icon="PlayIcon"
        icon-bg="green"
        :change="stats.activeChange"
        change-unit=""
        description="currently running"
      />
      
      <MetricCard
        label="Completed"
        :value="stats.completedProjects"
        :icon="CheckCircleIcon"
        icon-bg="purple"
        :change="stats.completedChange"
        change-unit=""
        description="this quarter"
      />
      
      <MetricCard
        label="Team Members"
        :value="stats.teamMembers"
        :icon="UserGroupIcon"
        icon-bg="yellow"
        :change="stats.membersChange"
        change-unit=""
        description="assigned"
      />
    </div>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card
        v-for="project in filteredProjects"
        :key="project.id"
        :title="project.name"
        :subtitle="project.description"
        hover
      >
        <template #headerActions>
          <Badge 
            :variant="getStatusVariant(project.status)" 
            :label="project.status"
          />
        </template>

        <div class="space-y-4">
          <!-- Progress -->
          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600">Progress</span>
              <span class="font-medium">{{ project.progress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="h-2 rounded-full transition-all duration-300"
                :class="getProgressColor(project.progress)"
                :style="`width: ${project.progress}%`"
              ></div>
            </div>
          </div>

          <!-- Team & Deadline -->
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center space-x-1">
              <UserGroupIcon class="w-4 h-4 text-gray-400" />
              <span class="text-gray-600">{{ project.team_members.length }} members</span>
            </div>
            <div class="flex items-center space-x-1">
              <CalendarIcon class="w-4 h-4 text-gray-400" />
              <span class="text-gray-600">{{ formatDate(project.deadline) }}</span>
            </div>
          </div>

          <!-- Team Avatars -->
          <div class="flex -space-x-2">
            <div
              v-for="(member, index) in project.team_members.slice(0, 5)"
              :key="member.id"
              class="relative w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-medium border-2 border-white"
              :style="`background-color: ${getAvatarColor(index)}`"
            >
              {{ getInitials(member.name) }}
            </div>
            <div
              v-if="project.team_members.length > 5"
              class="relative w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white text-xs font-medium border-2 border-white"
            >
              +{{ project.team_members.length - 5 }}
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex space-x-2">
            <Button variant="secondary" size="sm" @click="editProject(project)" full-width>
              Edit
            </Button>
            <Button variant="primary" size="sm" @click="viewProject(project)" full-width>
              View Details
            </Button>
          </div>
        </template>
      </Card>

      <!-- Create New Project Card -->
      <Card 
        hover 
        custom-class="border-dashed border-2 border-gray-300 hover:border-blue-400 cursor-pointer transition-colors"
        @click="showCreateModal = true"
      >
        <div class="flex flex-col items-center justify-center py-12 text-center">
          <PlusIcon class="w-12 h-12 text-gray-400 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">Create New Project</h3>
          <p class="text-gray-500">Start a new project and assign team members</p>
        </div>
      </Card>
    </div>

    <!-- Project Details Table -->
    <Table
      title="All Projects"
      subtitle="Complete list of projects with details"
      :data="projects"
      :columns="projectColumns"
      searchable
      search-placeholder="Search projects..."
      :filterable="true"
      :filters="projectFilters"
      :paginated="true"
      :page-size="8"
    >
      <template #headerActions>
        <Button variant="secondary" @click="exportProjects">
          Export CSV
        </Button>
      </template>

      <template #cell-status="{ value }">
        <Badge :variant="getStatusVariant(value) as any" :label="value" />
      </template>

      <template #cell-progress="{ value }">
        <div class="flex items-center space-x-2">
          <div class="w-20 bg-gray-200 rounded-full h-2">
            <div 
              class="h-2 rounded-full"
              :class="getProgressColor(value)"
              :style="`width: ${value}%`"
            ></div>
          </div>
          <span class="text-sm font-medium">{{ value }}%</span>
        </div>
      </template>

      <template #cell-team_size="{ item }">
        <span class="text-sm text-gray-900">{{ item.team_members.length }} members</span>
      </template>

      <template #cell-deadline="{ value }">
        <span class="text-sm" :class="isOverdue(value) ? 'text-red-600' : 'text-gray-900'">
          {{ formatDate(value) }}
        </span>
      </template>

      <template #cell-actions="{ item }">
        <div class="flex space-x-1">
          <Button variant="ghost" size="sm" @click="editProject(item)">
            Edit
          </Button>
          <Button variant="ghost" size="sm" @click="deleteProject(item.id)">
            Delete
          </Button>
        </div>
      </template>
    </Table>

    <!-- Create/Edit Project Modal -->
    <Modal v-model:show="showCreateModal" :title="editingProject ? 'Edit Project' : 'Create New Project'" size="lg">
      <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Form
            v-model="projectForm.name"
            label="Project Name"
            placeholder="Enter project name"
            :required="true"
            :error="errors.name"
          />
          
          <Form
            v-model="projectForm.status"
            type="select"
            label="Status"
            :options="statusOptions"
            :required="true"
          />
        </div>

        <Form
          v-model="projectForm.description"
          type="textarea"
          label="Description"
          placeholder="Project description"
          :rows="3"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Form
            v-model="projectForm.start_date"
            type="date"
            label="Start Date"
            :required="true"
          />
          
          <Form
            v-model="projectForm.deadline"
            type="date"
            label="Deadline"
            :required="true"
          />
        </div>

        <Form
          v-model="projectForm.budget"
          type="number"
          label="Budget (IDR)"
          placeholder="0"
        />

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Team Members</label>
          <div class="space-y-2">
            <div v-for="member in availableMembers" :key="member.id" class="flex items-center">
              <input
                :id="`member-${member.id}`"
                type="checkbox"
                :value="member.id"
                v-model="projectForm.team_member_ids"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              >
              <label :for="`member-${member.id}`" class="ml-2 text-sm text-gray-900">
                {{ member.name }} - {{ member.position }}
              </label>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="closeModal">Cancel</Button>
          <Button variant="primary" @click="saveProject" :loading="saving">
            {{ editingProject ? 'Update' : 'Create' }} Project
          </Button>
        </div>
      </template>
    </Modal>

    <!-- Project Details Modal -->
    <Modal v-model:show="showDetailsModal" title="Project Details" size="xl">
      <div v-if="selectedProject" class="space-y-6">
        <!-- Project Header -->
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-xl font-semibold text-gray-900">{{ selectedProject.name }}</h3>
            <p class="text-gray-600 mt-1">{{ selectedProject.description }}</p>
          </div>
          <Badge :variant="getStatusVariant(selectedProject.status) as any" :label="selectedProject.status" />
        </div>

        <!-- Project Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Progress</p>
            <p class="text-2xl font-semibold text-gray-900">{{ selectedProject.progress }}%</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Team Size</p>
            <p class="text-2xl font-semibold text-gray-900">{{ selectedProject.team_members.length }}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-sm text-gray-500">Deadline</p>
            <p class="text-2xl font-semibold text-gray-900">{{ formatDate(selectedProject.deadline) }}</p>
          </div>
        </div>

        <!-- Team Members -->
        <div>
          <h4 class="text-lg font-medium text-gray-900 mb-4">Team Members</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
              v-for="member in selectedProject.team_members"
              :key="member.id"
              class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg"
            >
              <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                {{ getInitials(member.name) }}
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ member.name }}</p>
                <p class="text-xs text-gray-500">{{ member.position }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="showDetailsModal = false">Close</Button>
          <Button variant="primary" @click="editProject(selectedProject)">Edit Project</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  FolderIcon, 
  PlayIcon, 
  CheckCircleIcon, 
  UserGroupIcon,
  CalendarIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import { Card, MetricCard, Badge, Button, Form, Table, Modal } from '@/components/ui'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Reactive data
const statusFilter = ref('')
const showCreateModal = ref(false)
const showDetailsModal = ref(false)
const editingProject = ref(false)
const saving = ref(false)
const selectedProject = ref(null)

// Sample data
const stats = ref({
  totalProjects: 45,
  totalChange: 8,
  activeProjects: 12,
  activeChange: 3,
  completedProjects: 28,
  completedChange: 5,
  teamMembers: 156,
  membersChange: 12
})

const projects = ref([
  {
    id: 1,
    name: 'Employee Portal Redesign',
    description: 'Modernize the employee self-service portal with new UI/UX',
    status: 'active',
    progress: 75,
    start_date: '2024-01-01',
    deadline: '2024-03-15',
    budget: 50000000,
    team_members: [
      { id: 1, name: 'John Doe', position: 'Frontend Developer' },
      { id: 2, name: 'Jane Smith', position: 'UI/UX Designer' },
      { id: 3, name: 'Mike Johnson', position: 'Backend Developer' }
    ]
  },
  {
    id: 2,
    name: 'Payroll System Integration',
    description: 'Integrate third-party payroll system with HR database',
    status: 'planning',
    progress: 25,
    start_date: '2024-02-01',
    deadline: '2024-05-30',
    budget: 75000000,
    team_members: [
      { id: 4, name: 'Sarah Wilson', position: 'System Analyst' },
      { id: 5, name: 'Tom Davis', position: 'Integration Specialist' }
    ]
  },
  {
    id: 3,
    name: 'Mobile App Development',
    description: 'Native mobile app for attendance and leave management',
    status: 'completed',
    progress: 100,
    start_date: '2023-10-01',
    deadline: '2024-01-15',
    budget: 100000000,
    team_members: [
      { id: 6, name: 'Alex Brown', position: 'Mobile Developer' },
      { id: 7, name: 'Lisa Chen', position: 'QA Engineer' }
    ]
  }
])

const availableMembers = ref([
  { id: 1, name: 'John Doe', position: 'Frontend Developer' },
  { id: 2, name: 'Jane Smith', position: 'UI/UX Designer' },
  { id: 3, name: 'Mike Johnson', position: 'Backend Developer' },
  { id: 4, name: 'Sarah Wilson', position: 'System Analyst' },
  { id: 5, name: 'Tom Davis', position: 'Integration Specialist' },
  { id: 6, name: 'Alex Brown', position: 'Mobile Developer' },
  { id: 7, name: 'Lisa Chen', position: 'QA Engineer' }
])

const projectForm = ref({
  name: '',
  description: '',
  status: 'planning',
  start_date: '',
  deadline: '',
  budget: '',
  team_member_ids: []
})

const errors = ref({
  name: ''
})

// Options
const statusOptions = [
  { value: '', label: 'All Status' },
  { value: 'planning', label: 'Planning' },
  { value: 'active', label: 'Active' },
  { value: 'on_hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' }
]

// Table configuration
const projectColumns = [
  { key: 'name', label: 'Project Name', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'progress', label: 'Progress', sortable: true },
  { key: 'team_size', label: 'Team', sortable: false },
  { key: 'deadline', label: 'Deadline', sortable: true, type: 'date' as const },
  { key: 'actions', label: 'Actions', sortable: false }
]

const projectFilters = [
  {
    key: 'status',
    label: 'Status',
    options: statusOptions.slice(1) // Remove 'All Status' option
  }
]

// Computed
const filteredProjects = computed(() => {
  if (!statusFilter.value) return projects.value
  return projects.value.filter(project => project.status === statusFilter.value)
})

// Utility functions
function getStatusVariant(status: string) {
  const variants: Record<string, string> = {
    'planning': 'info',
    'active': 'success',
    'on_hold': 'warning',
    'completed': 'primary'
  }
  return variants[status] || 'gray'
}

function getProgressColor(progress: number) {
  if (progress >= 80) return 'bg-green-500'
  if (progress >= 60) return 'bg-blue-500'
  if (progress >= 40) return 'bg-yellow-500'
  return 'bg-red-500'
}

function getAvatarColor(index: number) {
  const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#F97316', '#06B6D4']
  return colors[index % colors.length]
}

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

function isOverdue(deadline: string) {
  return new Date(deadline) < new Date()
}

// Actions
function editProject(project: any) {
  editingProject.value = true
  selectedProject.value = project
  projectForm.value = {
    name: project.name,
    description: project.description,
    status: project.status,
    start_date: project.start_date,
    deadline: project.deadline,
    budget: project.budget,
    team_member_ids: project.team_members.map((m: any) => m.id)
  }
  showCreateModal.value = true
  showDetailsModal.value = false
}

function viewProject(project: any) {
  selectedProject.value = project
  showDetailsModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  editingProject.value = false
  selectedProject.value = null
  projectForm.value = {
    name: '',
    description: '',
    status: 'planning',
    start_date: '',
    deadline: '',
    budget: '',
    team_member_ids: []
  }
  errors.value = { name: '' }
}

async function saveProject() {
  // Validation
  if (!projectForm.value.name.trim()) {
    errors.value.name = 'Project name is required'
    return
  }

  saving.value = true
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    if (editingProject.value) {
      // Update existing project
      const index = projects.value.findIndex(p => p.id === selectedProject.value?.id)
      if (index > -1) {
        projects.value[index] = {
          ...projects.value[index],
          ...projectForm.value,
          team_members: availableMembers.value.filter(m => 
            projectForm.value.team_member_ids.includes(m.id)
          )
        }
      }
      toast.success('Project updated successfully!')
    } else {
      // Create new project
      const newProject = {
        id: Date.now(),
        ...projectForm.value,
        progress: 0,
        team_members: availableMembers.value.filter(m => 
          projectForm.value.team_member_ids.includes(m.id)
        )
      }
      projects.value.push(newProject)
      toast.success('Project created successfully!')
    }
    
    closeModal()
  } catch (error) {
    toast.error('Failed to save project')
  } finally {
    saving.value = false
  }
}

function deleteProject(projectId: number) {
  if (confirm('Are you sure you want to delete this project?')) {
    const index = projects.value.findIndex(p => p.id === projectId)
    if (index > -1) {
      projects.value.splice(index, 1)
      toast.success('Project deleted successfully!')
    }
  }
}

async function exportProjects() {
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('Projects exported to CSV!')
  } catch (error) {
    toast.error('Failed to export projects')
  }
}

onMounted(() => {
  // Load projects data
})
</script> 