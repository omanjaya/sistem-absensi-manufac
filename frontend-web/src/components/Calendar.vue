<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Calendar Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button
            @click="previousMonth"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
          >
            <ChevronLeftIcon class="h-5 w-5" />
          </button>
          
          <h2 class="text-lg font-semibold text-gray-900">
            {{ monthNames[currentMonth] }} {{ currentYear }}
          </h2>
          
          <button
            @click="nextMonth"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md transition-colors"
          >
            <ChevronRightIcon class="h-5 w-5" />
          </button>
        </div>
        
        <div class="flex items-center space-x-3">
          <select
            v-model="selectedView"
            class="rounded-md border border-gray-300 bg-white py-1.5 px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="month">Bulan</option>
            <option value="week">Minggu</option>
            <option value="day">Hari</option>
          </select>
          
          <button
            @click="goToToday"
            class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            Hari Ini
          </button>
        </div>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="p-6">
      <!-- Day Headers -->
      <div class="grid grid-cols-7 gap-px mb-2">
        <div
          v-for="day in dayHeaders"
          :key="day"
          class="py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wide"
        >
          {{ day }}
        </div>
      </div>

      <!-- Calendar Days -->
      <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-lg overflow-hidden">
        <div
          v-for="(day, index) in calendarDays"
          :key="`${day.date}-${index}`"
          :class="[
            'min-h-[100px] bg-white p-2 cursor-pointer hover:bg-gray-50 transition-colors',
            {
              'bg-gray-50 text-gray-400': !day.isCurrentMonth,
              'bg-indigo-50 border-2 border-indigo-200': day.isToday,
              'bg-blue-50': day.hasEvents
            }
          ]"
          @click="selectDate(day)"
        >
          <!-- Day Number -->
          <div class="flex justify-between items-start mb-1">
            <span
              :class="[
                'text-sm font-medium',
                {
                  'text-gray-400': !day.isCurrentMonth,
                  'text-indigo-600 font-bold': day.isToday,
                  'text-gray-900': day.isCurrentMonth && !day.isToday
                }
              ]"
            >
              {{ day.day }}
            </span>
            
            <!-- Event Indicators -->
            <div v-if="day.hasEvents" class="flex space-x-1">
              <div
                v-for="event in day.events.slice(0, 3)"
                :key="event.id"
                :class="[
                  'w-2 h-2 rounded-full',
                  {
                    'bg-red-400': event.type === 'holiday',
                    'bg-blue-400': event.type === 'schedule',
                    'bg-green-400': event.type === 'attendance',
                    'bg-yellow-400': event.type === 'leave'
                  }
                ]"
              ></div>
              <span v-if="day.events.length > 3" class="text-xs text-gray-500">
                +{{ day.events.length - 3 }}
              </span>
            </div>
          </div>

          <!-- Events List -->
          <div class="space-y-1">
            <div
              v-for="event in day.events.slice(0, 2)"
              :key="event.id"
              :class="[
                'text-xs px-2 py-1 rounded text-white truncate',
                {
                  'bg-red-500': event.type === 'holiday',
                  'bg-blue-500': event.type === 'schedule',
                  'bg-green-500': event.type === 'attendance',
                  'bg-yellow-500': event.type === 'leave'
                }
              ]"
              :title="event.title"
            >
              {{ event.title }}
            </div>
            
            <div v-if="day.events.length > 2" class="text-xs text-gray-500 text-center">
              +{{ day.events.length - 2 }} lainnya
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Legend -->
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <div class="flex flex-wrap items-center gap-4 text-sm">
        <div class="flex items-center">
          <div class="w-3 h-3 bg-red-400 rounded-full mr-2"></div>
          <span class="text-gray-600">Libur</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-blue-400 rounded-full mr-2"></div>
          <span class="text-gray-600">Jadwal</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-green-400 rounded-full mr-2"></div>
          <span class="text-gray-600">Kehadiran</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-yellow-400 rounded-full mr-2"></div>
          <span class="text-gray-600">Cuti</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Detail Modal -->
  <TransitionRoot as="template" :show="showEventModal">
    <Dialog as="div" class="relative z-10" @close="showEventModal = false">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 mb-4">
                      {{ formatSelectedDate }}
                    </DialogTitle>
                    
                    <div v-if="selectedDateEvents.length > 0" class="space-y-3">
                      <div
                        v-for="event in selectedDateEvents"
                        :key="event.id"
                        class="border border-gray-200 rounded-lg p-3"
                      >
                        <div class="flex items-center justify-between mb-2">
                          <h4 class="font-medium text-gray-900">{{ event.title }}</h4>
                          <span
                            :class="[
                              'px-2 py-1 text-xs font-medium rounded-full',
                              {
                                'bg-red-100 text-red-800': event.type === 'holiday',
                                'bg-blue-100 text-blue-800': event.type === 'schedule',
                                'bg-green-100 text-green-800': event.type === 'attendance',
                                'bg-yellow-100 text-yellow-800': event.type === 'leave'
                              }
                            ]"
                          >
                            {{ getEventTypeLabel(event.type) }}
                          </span>
                        </div>
                        
                        <p v-if="event.description" class="text-sm text-gray-600">
                          {{ event.description }}
                        </p>
                        
                        <div v-if="event.time" class="text-sm text-gray-500 mt-1">
                          <ClockIcon class="h-4 w-4 inline mr-1" />
                          {{ event.time }}
                        </div>
                      </div>
                    </div>
                    
                    <div v-else class="text-center py-8">
                      <CalendarIcon class="h-12 w-12 mx-auto text-gray-400 mb-2" />
                      <p class="text-gray-500">Tidak ada acara pada tanggal ini</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button
                  type="button"
                  class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                  @click="showEventModal = false"
                >
                  Tutup
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ChevronLeftIcon, ChevronRightIcon, CalendarIcon, ClockIcon } from '@heroicons/vue/24/outline'

interface CalendarEvent {
  id: string
  title: string
  description?: string
  type: 'holiday' | 'schedule' | 'attendance' | 'leave'
  date: string
  time?: string
}

interface CalendarDay {
  date: string
  day: number
  isCurrentMonth: boolean
  isToday: boolean
  hasEvents: boolean
  events: CalendarEvent[]
}

interface Props {
  events?: CalendarEvent[]
}

const props = withDefaults(defineProps<Props>(), {
  events: () => []
})

// Calendar state
const currentDate = ref(new Date())
const selectedView = ref('month')
const showEventModal = ref(false)
const selectedDate = ref<CalendarDay | null>(null)

// Calendar computed properties
const currentMonth = computed(() => currentDate.value.getMonth())
const currentYear = computed(() => currentDate.value.getFullYear())

const monthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
]

const dayHeaders = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']

const calendarDays = computed(() => {
  const days: CalendarDay[] = []
  const firstDay = new Date(currentYear.value, currentMonth.value, 1)
  const lastDay = new Date(currentYear.value, currentMonth.value + 1, 0)
  const today = new Date()

  // Get first day of the week (Sunday = 0, Monday = 1, etc.)
  const startDate = new Date(firstDay)
  startDate.setDate(startDate.getDate() - firstDay.getDay())

  // Generate 42 days (6 weeks)
  for (let i = 0; i < 42; i++) {
    const date = new Date(startDate)
    date.setDate(startDate.getDate() + i)
    
    const dateString = date.toISOString().split('T')[0]
    const dayEvents = props.events.filter(event => event.date === dateString)
    
    days.push({
      date: dateString,
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === currentMonth.value,
      isToday: date.toDateString() === today.toDateString(),
      hasEvents: dayEvents.length > 0,
      events: dayEvents
    })
  }

  return days
})

const formatSelectedDate = computed(() => {
  if (!selectedDate.value) return ''
  
  const date = new Date(selectedDate.value.date)
  const options: Intl.DateTimeFormatOptions = {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }
  
  return date.toLocaleDateString('id-ID', options)
})

const selectedDateEvents = computed(() => {
  return selectedDate.value?.events || []
})

// Calendar methods
const previousMonth = () => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() - 1)
  currentDate.value = newDate
}

const nextMonth = () => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() + 1)
  currentDate.value = newDate
}

const goToToday = () => {
  currentDate.value = new Date()
}

const selectDate = (day: CalendarDay) => {
  selectedDate.value = day
  if (day.hasEvents) {
    showEventModal.value = true
  }
}

const getEventTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    holiday: 'Libur',
    schedule: 'Jadwal',
    attendance: 'Kehadiran',
    leave: 'Cuti'
  }
  return labels[type] || type
}

// Demo events for testing
const demoEvents: CalendarEvent[] = [
  {
    id: '1',
    title: 'Hari Raya Idul Fitri',
    type: 'holiday',
    date: '2024-04-10',
    description: 'Libur nasional Hari Raya Idul Fitri'
  },
  {
    id: '2',
    title: 'Jadwal Matematika',
    type: 'schedule',
    date: '2024-04-15',
    time: '08:00 - 09:30',
    description: 'Kelas 12 IPA 1'
  },
  {
    id: '3',
    title: 'Kehadiran Guru',
    type: 'attendance',
    date: '2024-04-15',
    time: '07:30',
    description: 'Check-in pagi'
  }
]

onMounted(() => {
  // Add demo events for testing if no events provided
  if (props.events.length === 0) {
    // You can emit events to parent or use a store to load real data
    console.log('Loading demo events for calendar')
  }
})
</script> 