<template>
  <div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Izin' : 'Ajukan Izin' }}
        </h1>
      </div>
      
      <form @submit.prevent="saveLeave" class="p-6">
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Izin *</label>
              <select
                v-model="form.type"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Pilih Jenis Izin</option>
                <option value="sick">Sakit</option>
                <option value="personal">Pribadi</option>
                <option value="annual">Tahunan</option>
                <option value="emergency">Darurat</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai *</label>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir *</label>
              <input
                v-model="form.end_date"
                type="date"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Durasi</label>
              <input
                :value="calculateDuration"
                type="text"
                readonly
                class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm"
                placeholder="Akan dihitung otomatis"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan *</label>
            <textarea
              v-model="form.reason"
              required
              rows="4"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Jelaskan alasan pengajuan izin..."
            ></textarea>
          </div>

          <div v-if="form.type === 'sick'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Surat Keterangan Dokter</label>
            <input
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="handleFileUpload"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
            <p class="mt-1 text-sm text-gray-500">Format: PDF, JPG, PNG (Max 2MB)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan</label>
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Catatan tambahan (opsional)..."
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex justify-end space-x-3">
          <router-link
            to="/leaves"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Batal
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ loading ? 'Menyimpan...' : (isEdit ? 'Update' : 'Ajukan') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { leaveApi } from '@/services/api'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const loading = ref(false)
const leaveId = computed(() => route.params.id as string)
const isEdit = computed(() => !!leaveId.value)

const form = reactive({
  type: '',
  start_date: '',
  end_date: '',
  reason: '',
  notes: '',
  document: null as File | null
})

const calculateDuration = computed(() => {
  if (!form.start_date || !form.end_date) return '0 hari'
  
  const start = new Date(form.start_date)
  const end = new Date(form.end_date)
  const diffTime = Math.abs(end.getTime() - start.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  
  return `${diffDays} hari`
})

const fetchLeave = async () => {
  if (!isEdit.value) return
  
  try {
    loading.value = true
    const leave = await leaveApi.getLeave(parseInt(leaveId.value))
    
    Object.assign(form, {
      type: leave.type,
      start_date: leave.start_date,
      end_date: leave.end_date,
      reason: leave.reason,
      notes: leave.notes || ''
    })
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memuat data izin')
    router.push('/leaves')
  } finally {
    loading.value = false
  }
}

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    // Check file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      toast.error('Ukuran file terlalu besar. Maksimal 2MB.')
      target.value = ''
      return
    }
    
    form.document = file
  }
}

const saveLeave = async () => {
  // Validate dates
  if (new Date(form.end_date) < new Date(form.start_date)) {
    toast.error('Tanggal akhir tidak boleh sebelum tanggal mulai')
    return
  }

  try {
    loading.value = true
    
    const leaveData = new FormData()
    leaveData.append('type', form.type)
    leaveData.append('start_date', form.start_date)
    leaveData.append('end_date', form.end_date)
    leaveData.append('reason', form.reason)
    leaveData.append('notes', form.notes)
    
    if (form.document) {
      leaveData.append('document', form.document)
    }

    if (isEdit.value) {
      await leaveApi.updateLeave(parseInt(leaveId.value), leaveData)
      toast.success('Pengajuan izin berhasil diperbarui')
    } else {
      await leaveApi.createLeave(leaveData)
      toast.success('Pengajuan izin berhasil diajukan')
    }
    
    router.push('/leaves')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan pengajuan izin')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (isEdit.value) {
    fetchLeave()
  }
})
</script> 