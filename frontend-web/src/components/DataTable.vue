<template>
  <div class="data-table-container">
    <!-- Filters and Search -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <div class="flex flex-col md:flex-row items-center gap-4">
        <!-- Search -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @input="debounceSearch"
          >
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
        </div>

        <!-- Length Selection -->
        <select
          v-model="pageLength"
          @change="handleLengthChange"
          class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="10">10 entries</option>
          <option value="25">25 entries</option>
          <option value="50">50 entries</option>
          <option value="100">100 entries</option>
        </select>
      </div>

      <!-- Additional Filters Slot -->
      <div class="flex items-center gap-4">
        <slot name="filters"></slot>
        
        <!-- Action Buttons -->
        <slot name="actions">
          <button
            v-if="canCreate"
            @click="$emit('create')"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2"
          >
            <PlusIcon class="h-4 w-4" />
            Add New
          </button>
        </slot>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="relative">
      <div class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
        <div class="flex items-center gap-2">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
          <span class="text-gray-600">Loading...</span>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="handleSort(column.key)"
              >
                <div class="flex items-center gap-2">
                  {{ column.label }}
                  <div v-if="column.sortable !== false" class="flex flex-col">
                    <ChevronUpIcon 
                      :class="[
                        'h-3 w-3 -mb-1',
                        sortField === column.key && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-300'
                      ]"
                    />
                    <ChevronDownIcon 
                      :class="[
                        'h-3 w-3',
                        sortField === column.key && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-300'
                      ]"
                    />
                  </div>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="(row, index) in data"
              :key="row.id || index"
              class="hover:bg-gray-50"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
              >
                <slot :name="`column.${column.key}`" :row="row" :value="row[column.key]">
                  <span v-if="column.type === 'badge'" :class="getBadgeClass(row[column.key], column.badgeConfig)">
                    {{ formatCellValue(row[column.key], column) }}
                  </span>
                  <span v-else-if="column.type === 'currency'">
                    {{ formatCurrency(row[column.key]) }}
                  </span>
                  <span v-else-if="column.type === 'date'">
                    {{ formatDate(row[column.key]) }}
                  </span>
                  <span v-else-if="column.type === 'html'" v-html="row[column.key]"></span>
                  <span v-else>
                    {{ formatCellValue(row[column.key], column) }}
                  </span>
                </slot>
              </td>
            </tr>
            
            <!-- No Data Row -->
            <tr v-if="!loading && data.length === 0">
              <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center gap-2">
                  <DocumentIcon class="h-12 w-12 text-gray-300" />
                  <span>{{ noDataMessage }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
      <div class="text-sm text-gray-700">
        Showing {{ startEntry }} to {{ endEntry }} of {{ totalEntries }} entries
        <span v-if="filteredEntries !== totalEntries">
          (filtered from {{ totalEntries }} total entries)
        </span>
      </div>
      
      <div class="flex items-center gap-2">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        
        <div class="flex items-center gap-1">
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 text-sm font-medium border rounded-md',
                page === currentPage
                  ? 'text-blue-600 bg-blue-50 border-blue-500'
                  : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
          </template>
        </div>
        
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage >= totalPages"
          class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { 
  MagnifyingGlassIcon, 
  ChevronUpIcon, 
  ChevronDownIcon,
  PlusIcon,
  DocumentIcon 
} from '@heroicons/vue/24/outline'

interface Column {
  key: string
  label: string
  sortable?: boolean
  type?: 'text' | 'badge' | 'currency' | 'date' | 'html'
  badgeConfig?: Record<string, string>
  format?: (value: any) => string
}

interface Props {
  columns: Column[]
  apiUrl: string
  searchPlaceholder?: string
  noDataMessage?: string
  canCreate?: boolean
  filters?: Record<string, any>
  serverSide?: boolean
}

interface Emits {
  (e: 'create'): void
  (e: 'edit', row: any): void
  (e: 'delete', row: any): void
  (e: 'view', row: any): void
}

const props = withDefaults(defineProps<Props>(), {
  searchPlaceholder: 'Search...',
  noDataMessage: 'No data available',
  canCreate: true,
  serverSide: true
})

const emit = defineEmits<Emits>()

// Reactive data
const data = ref<any[]>([])
const loading = ref(false)
const searchQuery = ref('')
const pageLength = ref(10)
const currentPage = ref(1)
const totalEntries = ref(0)
const filteredEntries = ref(0)
const sortField = ref('')
const sortOrder = ref<'asc' | 'desc'>('asc')

// Search debouncing
let searchTimeout: NodeJS.Timeout

// Computed properties
const startEntry = computed(() => {
  return (currentPage.value - 1) * pageLength.value + 1
})

const endEntry = computed(() => {
  const end = currentPage.value * pageLength.value
  return end > filteredEntries.value ? filteredEntries.value : end
})

const totalPages = computed(() => {
  return Math.ceil(filteredEntries.value / pageLength.value)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2

  let pages: (number | string)[] = []
  
  if (total <= 7) {
    pages = Array.from({ length: total }, (_, i) => i + 1)
  } else {
    if (current <= 4) {
      pages = [1, 2, 3, 4, 5, '...', total]
    } else if (current >= total - 3) {
      pages = [1, '...', total - 4, total - 3, total - 2, total - 1, total]
    } else {
      pages = [1, '...', current - 1, current, current + 1, '...', total]
    }
  }
  
  return pages
})

// Methods
const fetchData = async () => {
  loading.value = true
  
  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: pageLength.value.toString(),
      search: searchQuery.value,
      sort_field: sortField.value,
      sort_order: sortOrder.value
    })

    // Add filters
    if (props.filters) {
      Object.entries(props.filters).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
          params.append(key, String(value))
        }
      })
    }

    const response = await fetch(`${props.apiUrl}?${params}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      credentials: 'include'
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json()
    
    if (props.serverSide) {
      data.value = result.data || []
      totalEntries.value = result.recordsTotal || 0
      filteredEntries.value = result.recordsFiltered || result.recordsTotal || 0
    } else {
      data.value = result.data || []
      totalEntries.value = data.value.length
      filteredEntries.value = data.value.length
    }
  } catch (error) {
    console.error('Failed to fetch data:', error)
    data.value = []
  } finally {
    loading.value = false
  }
}

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchData()
  }, 500)
}

const handleLengthChange = () => {
  currentPage.value = 1
  fetchData()
}

const handleSort = (field: string) => {
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortOrder.value = 'asc'
  }
  
  currentPage.value = 1
  fetchData()
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchData()
  }
}

const formatCellValue = (value: any, column: Column) => {
  if (value == null) return '-'
  
  if (column.format) {
    return column.format(value)
  }
  
  return String(value)
}

const formatCurrency = (value: number) => {
  if (value == null) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR'
  }).format(value)
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('id-ID')
}

const getBadgeClass = (value: any, config?: Record<string, string>) => {
  if (!config) return 'px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800'
  
  const baseClasses = 'px-2 py-1 text-xs rounded-full'
  const colorClass = config[value] || 'bg-gray-100 text-gray-800'
  
  return `${baseClasses} ${colorClass}`
}

// Watchers
watch(() => props.filters, () => {
  currentPage.value = 1
  fetchData()
}, { deep: true })

// Lifecycle
onMounted(() => {
  fetchData()
})

onUnmounted(() => {
  clearTimeout(searchTimeout)
})

// Expose methods for parent components
defineExpose({
  refresh: fetchData,
  goToPage
})
</script>

<style scoped>
.data-table-container {
  @apply w-full;
}
</style> 