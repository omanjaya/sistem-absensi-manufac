<template>
  <Card :title="title" :subtitle="subtitle" padding="none">
    <template #headerActions>
      <slot name="headerActions" />
    </template>

    <!-- Filters and Search -->
    <div v-if="searchable || filterable" class="p-4 border-b border-gray-200">
      <div class="flex items-center justify-between space-x-4">
        <!-- Search -->
        <div v-if="searchable" class="flex-1 max-w-sm">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
          >
        </div>

        <!-- Filters -->
        <div v-if="filterable && filters.length > 0" class="flex items-center space-x-2">
          <select
            v-for="filter in filters"
            :key="filter.key"
            v-model="filterValues[filter.key]"
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">{{ filter.placeholder || `All ${filter.label}` }}</option>
            <option
              v-for="option in filter.options"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
              @click="sortBy(column.key)"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <div v-if="sortable && column.sortable !== false" class="flex flex-col">
                  <svg
                    class="w-3 h-3"
                    :class="sortKey === column.key && sortOrder === 'asc' ? 'text-blue-600' : 'text-gray-400'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" />
                  </svg>
                  <svg
                    class="w-3 h-3 -mt-1"
                    :class="sortKey === column.key && sortOrder === 'desc' ? 'text-blue-600' : 'text-gray-400'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                  </svg>
                </div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading" v-for="n in 5" :key="n">
            <td v-for="column in columns" :key="column.key" class="px-6 py-4 whitespace-nowrap">
              <div class="animate-pulse h-4 bg-gray-200 rounded"></div>
            </td>
          </tr>
          <tr v-else-if="filteredData.length === 0">
            <td :colspan="columns.length" class="px-6 py-4 text-center text-gray-500">
              {{ emptyMessage }}
            </td>
          </tr>
          <tr v-else v-for="(item, index) in paginatedData" :key="item.id || index" class="hover:bg-gray-50">
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-6 py-4 whitespace-nowrap"
              :class="column.class"
            >
              <slot
                :name="`cell-${column.key}`"
                :item="item"
                :value="getColumnValue(item, column.key)"
                :index="index"
              >
                <span v-if="column.type === 'date'">
                  {{ formatDate(getColumnValue(item, column.key)) }}
                </span>
                <span v-else-if="column.type === 'currency'">
                  {{ formatCurrency(getColumnValue(item, column.key)) }}
                </span>
                <span v-else>
                  {{ getColumnValue(item, column.key) }}
                </span>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="paginated && filteredData.length > pageSize" class="px-6 py-3 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ ((currentPage - 1) * pageSize) + 1 }} to {{ Math.min(currentPage * pageSize, filteredData.length) }} of {{ filteredData.length }} results
        </div>
        <div class="flex items-center space-x-2">
          <Button
            variant="secondary"
            size="sm"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Previous
          </Button>
          <Button
            variant="secondary"
            size="sm"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import Card from './Card.vue'
import Button from './Button.vue'

interface Column {
  key: string
  label: string
  sortable?: boolean
  type?: 'text' | 'date' | 'currency'
  class?: string
}

interface Filter {
  key: string
  label: string
  placeholder?: string
  options: { value: string; label: string }[]
}

interface Props {
  title?: string
  subtitle?: string
  data: any[]
  columns: Column[]
  loading?: boolean
  searchable?: boolean
  searchPlaceholder?: string
  filterable?: boolean
  filters?: Filter[]
  sortable?: boolean
  paginated?: boolean
  pageSize?: number
  emptyMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  searchable: false,
  searchPlaceholder: 'Search...',
  filterable: false,
  filters: () => [],
  sortable: true,
  paginated: false,
  pageSize: 10,
  emptyMessage: 'No data available'
})

const searchQuery = ref('')
const sortKey = ref('')
const sortOrder = ref<'asc' | 'desc'>('asc')
const currentPage = ref(1)
const filterValues = reactive<Record<string, string>>({})

// Initialize filter values
props.filters.forEach(filter => {
  filterValues[filter.key] = ''
})

const filteredData = computed(() => {
  let result = [...props.data]

  // Apply search
  if (searchQuery.value && props.searchable) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      props.columns.some(column =>
        String(getColumnValue(item, column.key)).toLowerCase().includes(query)
      )
    )
  }

  // Apply filters
  if (props.filterable) {
    props.filters.forEach(filter => {
      const value = filterValues[filter.key]
      if (value) {
        result = result.filter(item => getColumnValue(item, filter.key) === value)
      }
    })
  }

  // Apply sorting
  if (sortKey.value && props.sortable) {
    result.sort((a, b) => {
      const aVal = getColumnValue(a, sortKey.value)
      const bVal = getColumnValue(b, sortKey.value)
      
      if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
      if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
      return 0
    })
  }

  return result
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / props.pageSize))

const paginatedData = computed(() => {
  if (!props.paginated) return filteredData.value
  
  const start = (currentPage.value - 1) * props.pageSize
  const end = start + props.pageSize
  return filteredData.value.slice(start, end)
})

function getColumnValue(item: any, key: string) {
  return key.split('.').reduce((obj, k) => obj?.[k], item)
}

function sortBy(key: string) {
  if (!props.sortable) return
  
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

function formatDate(value: any) {
  if (!value) return ''
  return new Date(value).toLocaleDateString()
}

function formatCurrency(value: any) {
  if (!value) return ''
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR'
  }).format(value)
}
</script> 