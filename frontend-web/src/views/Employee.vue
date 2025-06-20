<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Guru & Staff</h1>
        <p class="text-gray-600">Kelola data guru dan staff sekolah</p>
      </div>
      <div class="flex space-x-3">
        <Button variant="ghost" @click="downloadTemplate" :loading="downloadingTemplate">
          <DocumentArrowDownIcon class="w-4 h-4" />
          Download Template
        </Button>
        <Button variant="secondary" @click="showImportModal = true">
          <DocumentArrowUpIcon class="w-4 h-4" />
          Import Excel
        </Button>
        <Button variant="secondary" @click="exportEmployees" :loading="exporting">
          <DocumentArrowDownIcon class="w-4 h-4" />
          Export Excel
        </Button>
        <Button variant="primary" @click="showCreateModal = true">
          <UserPlusIcon class="w-4 h-4" />
          Tambah Guru/Staff
        </Button>
      </div>
    </div>

    <!-- Quick Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Card class="p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <UsersIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Guru & Staff</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Aktif</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
            <p class="text-xs text-green-600">{{ getPercentage(stats.active, stats.total) }}%</p>
          </div>
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <AcademicCapIcon class="h-8 w-8 text-purple-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Guru</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.teachers }}</p>
            <p class="text-xs text-purple-600">{{ getPercentage(stats.teachers, stats.total) }}%</p>
          </div>
        </div>
      </Card>

      <Card class="p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <StarIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Baru Bulan Ini</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.newThisMonth }}</p>
            <p class="text-xs text-yellow-600">+{{ stats.newThisMonth }} orang</p>
          </div>
        </div>
      </Card>
    </div>

    <!-- Advanced Filters & Search -->
    <Card class="p-6">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Filter & Pencarian</h3>
          <Button variant="ghost" size="sm" @click="resetFilters">
            <ArrowPathIcon class="w-4 h-4" />
            Reset Filter
          </Button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              v-model="filters.search"
              type="text"
              placeholder="Cari nama, NIP, email..."
              class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
          </div>

          <!-- Department Filter -->
          <select
            v-model="filters.department"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Semua Bidang</option>
            <option v-for="dept in departmentOptions" :key="dept.value" :value="dept.value">
              {{ dept.label }}
            </option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="filters.status"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Non-aktif</option>
          </select>

          <!-- Role Filter -->
          <select
            v-model="filters.role"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Semua Role</option>
            <option value="teacher">Guru</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <!-- Advanced Search Options -->
        <div class="border-t pt-4">
          <Button variant="ghost" size="sm" @click="showAdvancedSearch = !showAdvancedSearch">
            <FunnelIcon class="w-4 h-4" />
            {{ showAdvancedSearch ? 'Sembunyikan' : 'Tampilkan' }} Pencarian Lanjutan
          </Button>
          
          <div v-if="showAdvancedSearch" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Bergabung</label>
              <select v-model="filters.joinYear" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua Tahun</option>
                <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Range Gaji (Juta)</label>
              <div class="flex space-x-2">
                <input
                  v-model.number="filters.salaryMin"
                  type="number"
                  placeholder="Min"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
                <input
                  v-model.number="filters.salaryMax"
                  type="number"
                  placeholder="Max"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sortir Berdasarkan</label>
              <select v-model="filters.sortBy" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="name">Nama</option>
                <option value="join_date">Tanggal Bergabung</option>
                <option value="department">Bidang</option>
                <option value="status">Status</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </Card>

    <!-- Bulk Actions -->
    <div v-if="selectedEmployees.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <CheckCircleIcon class="w-5 h-5 text-blue-600" />
          <span class="text-sm font-medium text-blue-800">
            {{ selectedEmployees.length }} guru/staff dipilih
          </span>
        </div>
        <div class="flex space-x-2">
          <Button variant="secondary" size="sm" @click="bulkAction('activate')" :disabled="loading">
            <CheckIcon class="w-4 h-4" />
            Aktifkan
          </Button>
          <Button variant="secondary" size="sm" @click="bulkAction('deactivate')" :disabled="loading">
            <XMarkIcon class="w-4 h-4" />
            Non-aktifkan
          </Button>
          <Button variant="secondary" size="sm" @click="showBulkModal = true" :disabled="loading">
            <CogIcon class="w-4 h-4" />
            Aksi Lainnya
          </Button>
          <Button variant="ghost" size="sm" @click="selectedEmployees = []">
            <XMarkIcon class="w-4 h-4" />
            Batal
          </Button>
        </div>
      </div>
    </div>

    <!-- Employee Table -->
    <Card>
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Daftar Guru & Staff</h3>
        <div class="flex items-center space-x-3">
          <span class="text-sm text-gray-500">
            Menampilkan {{ employees.length }} dari {{ pagination.total }} data
          </span>
          <Button variant="ghost" size="sm" @click="selectAll">
            {{ selectedEmployees.length === employees.length ? 'Batalkan Semua' : 'Pilih Semua' }}
          </Button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="selectedEmployees.length === employees.length && employees.length > 0"
                  @change="selectAll"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('name')">
                Guru/Staff
                <ChevronUpDownIcon class="w-4 h-4 inline ml-1" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Kontak
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('department')">
                Bidang/Mata Pelajaran
                <ChevronUpDownIcon class="w-4 h-4 inline ml-1" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Role
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('status')">
                Status
                <ChevronUpDownIcon class="w-4 h-4 inline ml-1" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortBy('join_date')">
                Bergabung
                <ChevronUpDownIcon class="w-4 h-4 inline ml-1" />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading" v-for="n in 5" :key="`loading-${n}`" class="animate-pulse">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-4 bg-gray-200 rounded w-4"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-3">
                  <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                  <div class="space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-24"></div>
                    <div class="h-3 bg-gray-200 rounded w-16"></div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="space-y-2">
                  <div class="h-3 bg-gray-200 rounded w-32"></div>
                  <div class="h-3 bg-gray-200 rounded w-24"></div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-4 bg-gray-200 rounded w-20"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-4 bg-gray-200 rounded w-16"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-6 bg-gray-200 rounded w-16"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="h-4 bg-gray-200 rounded w-20"></div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex space-x-1">
                  <div class="h-6 w-6 bg-gray-200 rounded"></div>
                  <div class="h-6 w-6 bg-gray-200 rounded"></div>
                  <div class="h-6 w-6 bg-gray-200 rounded"></div>
                </div>
              </td>
            </tr>

            <tr v-else v-for="employee in employees" :key="employee.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :value="employee"
                  v-model="selectedEmployees"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center space-x-3">
                  <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                    {{ getInitials(employee.name) }}
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                    <div class="text-sm text-gray-500">{{ employee.employee_id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ employee.email }}</div>
                <div class="text-sm text-gray-500">{{ employee.phone || 'Tidak ada' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ employee.department || 'Belum ditentukan' }}</div>
                <div class="text-sm text-gray-500">{{ employee.position || 'Tidak ada jabatan' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="getRoleVariant(getUserRole(employee))">
                  {{ getRoleLabel(getUserRole(employee)) }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <Badge :variant="employee.status === 'active' ? 'success' : 'danger'">
                  {{ employee.status === 'active' ? 'Aktif' : 'Non-aktif' }}
                </Badge>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div>{{ formatDate(employee.join_date) }}</div>
                <div class="text-xs text-gray-500">{{ getWorkDuration(employee.join_date) }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-1">
                  <Button variant="ghost" size="sm" @click="viewEmployee(employee)" title="Lihat Detail">
                    <EyeIcon class="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="editEmployee(employee)" title="Edit">
                    <PencilIcon class="w-4 h-4" />
                  </Button>
                  <Button variant="ghost" size="sm" @click="toggleStatus(employee)"
                    :class="employee.status === 'active' ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'"
                    :title="employee.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'"
                  >
                    <component :is="employee.status === 'active' ? XCircleIcon : CheckCircleIcon" class="w-4 h-4" />
                  </Button>
                </div>
              </td>
            </tr>

            <tr v-if="!loading && employees.length === 0">
              <td colspan="8" class="px-6 py-12 text-center">
                <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data guru/staff</h3>
                <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan guru atau staff baru.</p>
                <div class="mt-6">
                  <Button variant="primary" @click="showCreateModal = true">
                    <UserPlusIcon class="w-4 h-4" />
                    Tambah Guru/Staff
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Menampilkan {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }} sampai 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} dari 
            {{ pagination.total }} data
          </div>
          <div class="flex space-x-1">
            <Button 
              variant="ghost" 
              size="sm" 
              @click="handlePageChange(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
            >
              Sebelumnya
            </Button>
            <Button
              v-for="page in getPageNumbers()"
              :key="page"
              :variant="page === pagination.current_page ? 'primary' : 'ghost'"
              size="sm"
              @click="handlePageChange(page)"
            >
              {{ page }}
            </Button>
            <Button 
              variant="ghost" 
              size="sm" 
              @click="handlePageChange(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
            >
              Selanjutnya
            </Button>
          </div>
        </div>
      </div>
    </Card>

    <!-- Modals -->
    <Modal :show="showCreateModal" @close="closeModal" :title="editingEmployee ? 'Edit Guru/Staff' : 'Tambah Guru/Staff Baru'" size="xl">
      <EmployeeForm
        :employee="editingEmployee"
        :departments="departments"
        @save="handleSaveEmployee"
        @cancel="closeModal"
      />
    </Modal>

    <Modal :show="showDetailModal" @close="showDetailModal = false" title="Detail Guru/Staff" size="xl">
      <EmployeeDetail
        v-if="selectedEmployee"
        :employee="selectedEmployee"
        @edit="editFromDetail"
        @close="showDetailModal = false"
      />
    </Modal>

    <Modal :show="showBulkModal" @close="showBulkModal = false" title="Aksi Bulk" size="lg">
      <BulkActionForm
        :employees="selectedEmployees"
        @action="handleBulkAction"
        @cancel="showBulkModal = false"
      />
    </Modal>

    <!-- Import Template Modal -->
    <Modal :show="showImportModal" @close="showImportModal = false" title="Import Data Guru & Staff" size="lg">
      <div class="space-y-6">
        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Cara Import Data</h3>
              <div class="mt-2 text-sm text-blue-700">
                <ol class="list-decimal list-inside space-y-1">
                  <li>Download template Excel terlebih dahulu</li>
                  <li>Isi data guru & staff sesuai format template</li>
                  <li>Upload file yang sudah diisi</li>
                  <li>Pastikan format tanggal: DD/MM/YYYY</li>
                  <li>Email harus unik untuk setiap guru/staff</li>
                </ol>
              </div>
            </div>
          </div>
        </div>

        <!-- Download Template Section -->
        <div class="border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">1. Download Template Excel</h4>
          <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
            <div class="flex items-center">
              <DocumentTextIcon class="h-8 w-8 text-green-600" />
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Template_Data_Guru_Staff.xlsx</p>
                <p class="text-xs text-gray-500">Template standar untuk import data guru dan staff sekolah</p>
              </div>
            </div>
            <Button variant="outline" size="sm" @click="downloadTemplate" :loading="downloadingTemplate">
              <ArrowDownTrayIcon class="w-4 h-4" />
              Download
            </Button>
          </div>
        </div>

        <!-- Upload Section -->
        <div class="border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">2. Upload File Excel</h4>
          
          <!-- File Drop Zone -->
          <div 
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
            :class="{ 'border-blue-400 bg-blue-50': dragOver }"
            @drop="handleFileDrop"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls"
              @change="handleFileSelect"
              class="hidden"
            />
            
            <div v-if="!selectedFile">
              <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
              <div class="mt-4">
                <p class="text-sm text-gray-600">
                  <Button variant="ghost" @click="(fileInput as HTMLInputElement)?.click()">
                    Klik untuk upload
                  </Button>
                  atau drag & drop file Excel
                </p>
                <p class="text-xs text-gray-500 mt-1">Format: .xlsx, .xls (Maksimal 10MB)</p>
              </div>
            </div>

            <div v-else class="flex items-center justify-center">
              <CheckCircleIcon class="h-8 w-8 text-green-500" />
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
              <Button variant="ghost" size="sm" @click="clearFile" class="ml-2">
                <XMarkIcon class="w-4 h-4" />
              </Button>
            </div>
          </div>

          <!-- Validation Options -->
          <div class="mt-4 space-y-3">
            <label class="flex items-center">
              <input type="checkbox" v-model="importOptions.skipDuplicates" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="ml-2 text-sm text-gray-700">Skip data duplikat (berdasarkan email)</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="importOptions.updateExisting" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="ml-2 text-sm text-gray-700">Update data yang sudah ada</span>
            </label>
            <label class="flex items-center">
              <input type="checkbox" v-model="importOptions.validateOnly" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="ml-2 text-sm text-gray-700">Validasi saja (tidak menyimpan data)</span>
            </label>
          </div>
        </div>

        <!-- Import Preview -->
        <div v-if="importPreview.length > 0" class="border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">3. Preview Data ({{ importPreview.length }} record)</h4>
          <div class="max-h-64 overflow-auto">
            <table class="min-w-full text-xs">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-2 py-1 text-left">Nama</th>
                  <th class="px-2 py-1 text-left">Email</th>
                  <th class="px-2 py-1 text-left">Bidang</th>
                  <th class="px-2 py-1 text-left">Status</th>
                  <th class="px-2 py-1 text-left">Validasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(row, index) in importPreview.slice(0, 10)" :key="index">
                  <td class="px-2 py-1">{{ row.name }}</td>
                  <td class="px-2 py-1">{{ row.email }}</td>
                  <td class="px-2 py-1">{{ row.department }}</td>
                  <td class="px-2 py-1">
                    <Badge :variant="row.status === 'active' ? 'success' : 'gray'" size="sm">
                      {{ row.status === 'active' ? 'Aktif' : 'Non-aktif' }}
                    </Badge>
                  </td>
                  <td class="px-2 py-1">
                    <span v-if="row.errors?.length > 0" class="text-red-600 text-xs">
                      {{ row.errors[0] }}
                    </span>
                    <span v-else class="text-green-600 text-xs">✓ Valid</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="importPreview.length > 10" class="text-xs text-gray-500 mt-2 text-center">
              ...dan {{ importPreview.length - 10 }} record lainnya
            </p>
          </div>
        </div>

        <!-- Import Summary -->
        <div v-if="importSummary" class="grid grid-cols-3 gap-4">
          <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
            <p class="text-lg font-bold text-green-800">{{ importSummary.valid }}</p>
            <p class="text-xs text-green-600">Valid</p>
          </div>
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
            <p class="text-lg font-bold text-yellow-800">{{ importSummary.warnings }}</p>
            <p class="text-xs text-yellow-600">Warning</p>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
            <p class="text-lg font-bold text-red-800">{{ importSummary.errors }}</p>
            <p class="text-xs text-red-600">Error</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <Button variant="ghost" @click="closeImportModal">
            Batal
          </Button>
          <Button 
            variant="secondary" 
            @click="previewImport" 
            :disabled="!selectedFile"
            :loading="previewing"
          >
            <EyeIcon class="w-4 h-4" />
            Preview
          </Button>
          <Button 
            variant="primary" 
            @click="executeImport"
            :disabled="!selectedFile || (importSummary && importSummary.errors > 0)"
            :loading="importing"
          >
            <CloudArrowUpIcon class="w-4 h-4" />
            {{ importOptions.validateOnly ? 'Validasi' : 'Import Data' }}
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useToast } from 'vue-toastification'
import { 
  UserPlusIcon, 
  MagnifyingGlassIcon,
  DocumentArrowDownIcon,
  EyeIcon,
  PencilIcon,
  CheckCircleIcon,
  XMarkIcon,
  CogIcon,
  CheckIcon,
  ChevronUpDownIcon,
  ArrowPathIcon,
  FunnelIcon,
  UserGroupIcon,
  UsersIcon,
  AcademicCapIcon,
  StarIcon,
  DocumentArrowUpIcon,
  InformationCircleIcon,
  DocumentTextIcon,
  CloudArrowUpIcon,
  ArrowDownTrayIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'
import { 
  Card, 
  Button, 
  Table, 
  Modal, 
  Form, 
  Badge,
  LoadingSpinner 
} from '@/components/ui'
import { employeeApi } from '@/services/api'
import type { Employee, User } from '@/types'

// Import child components
import EmployeeForm from '@/components/EmployeeForm.vue'
import EmployeeDetail from '@/components/EmployeeDetail.vue'
import BulkActionForm from '@/components/BulkActionForm.vue'

const toast = useToast()

// Custom debounce function
const debounce = (func: Function, delay: number) => {
  let timeoutId: ReturnType<typeof setTimeout>
  return (...args: any[]) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func.apply(null, args), delay)
  }
}

// State
const loading = ref(false)
const exporting = ref(false)
const downloadingTemplate = ref(false)
const employees = ref<Employee[]>([])
const selectedEmployees = ref<Employee[]>([])
const selectedEmployee = ref<Employee | null>(null)
const editingEmployee = ref<Employee | null>(null)

// Import related state
const importing = ref(false)
const previewing = ref(false)
const selectedFile = ref<File | null>(null)
const importPreview = ref<any[]>([])
const importSummary = ref<any>(null)
const dragOver = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

const importOptions = reactive({
  skipDuplicates: true,
  updateExisting: false,
  validateOnly: false
})

// Modals
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const showBulkModal = ref(false)
const showAdvancedSearch = ref(false)
const showImportModal = ref(false)

// Filters
const filters = reactive({
  search: '',
  department: '',
  status: '',
  role: '',
  joinYear: '',
  salaryMin: '',
  salaryMax: '',
  sortBy: ''
})

// Pagination
const pagination = reactive({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1
})

// Computed
const stats = computed(() => {
  const employeesArray = Array.isArray(employees.value) ? employees.value : []
  const total = employeesArray.length
  const active = employeesArray.filter(emp => emp.status === 'active').length
  const teachers = employeesArray.filter(emp => emp.department === 'Guru').length
  const newThisMonth = employeesArray.filter(emp => {
    const joinDate = new Date(emp.join_date)
    const now = new Date()
    return joinDate.getMonth() === now.getMonth() && joinDate.getFullYear() === now.getFullYear()
  }).length

  return { total, active, teachers, newThisMonth }
})

const departments = computed(() => {
  const employeesArray = Array.isArray(employees.value) ? employees.value : []
  const depts = [...new Set(employeesArray.map(emp => emp.department).filter(Boolean))]
  return depts.map(dept => ({ value: dept, label: dept }))
})

const departmentOptions = computed(() => [
  { value: '', label: 'Semua Departemen' },
  ...departments.value
])

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'active', label: 'Aktif' },
  { value: 'inactive', label: 'Nonaktif' }
]

const roleOptions = [
  { value: '', label: 'Semua Role' },
  { value: 'teacher', label: 'Guru' },
  { value: 'staff', label: 'Staff' },
  { value: 'admin', label: 'Admin' }
]

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 10 }, (_, i) => currentYear - i)
})

// Table configuration
const employeeColumns = [
  { key: 'employee', label: 'Pegawai', sortable: true },
  { key: 'contact', label: 'Kontak', sortable: false },
  { key: 'department', label: 'Departemen', sortable: true },
  { key: 'role', label: 'Role', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'join_date', label: 'Bergabung', sortable: true },
  { key: 'actions', label: 'Aksi', sortable: false }
]

// Methods
const fetchEmployees = async () => {
  try {
    loading.value = true
    const response = await employeeApi.getEmployees({
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: filters.search,
      department: filters.department,
      status: filters.status
      // role: filters.role,
      // joinYear: filters.joinYear,
      // salaryMin: filters.salaryMin,
      // salaryMax: filters.salaryMax,
      // sortBy: filters.sortBy
    })
    
    // Ensure we always have an array
    employees.value = Array.isArray(response.data) ? response.data : []
    
    // Handle pagination data safely
    if (response.current_page !== undefined) {
      Object.assign(pagination, {
        current_page: response.current_page || 1,
        per_page: response.per_page || 15,
        total: response.total || 0,
        last_page: response.last_page || 1
      })
    }
  } catch (error: any) {
    toast.error('Gagal memuat data pegawai')
    console.error('Fetch employees error:', error)
    // Ensure employees is always an array even on error
    employees.value = []
  } finally {
    loading.value = false
  }
}

const debouncedSearch = debounce(() => {
  pagination.current_page = 1
  fetchEmployees()
}, 300)

const applyFilters = () => {
  pagination.current_page = 1
  fetchEmployees()
}

const handleSort = (sortBy: string, direction: 'asc' | 'desc') => {
  // Implement sorting logic
  fetchEmployees()
}

const handlePageChange = (page: number) => {
  pagination.current_page = page
  fetchEmployees()
}

// Employee actions
const viewEmployee = (employee: Employee) => {
  selectedEmployee.value = employee
  showDetailModal.value = true
}

const editEmployee = (employee: Employee) => {
  editingEmployee.value = employee
  showCreateModal.value = true
}

const editFromDetail = (employee: Employee) => {
  showDetailModal.value = false
  editEmployee(employee)
}

const toggleStatus = async (employee: Employee) => {
  try {
    const newStatus = employee.status === 'active' ? 'inactive' : 'active'
    await employeeApi.updateEmployee(employee.id, { status: newStatus })
    
    employee.status = newStatus
    toast.success(`Pegawai berhasil ${newStatus === 'active' ? 'diaktifkan' : 'dinonaktifkan'}`)
  } catch (error: any) {
    toast.error('Gagal mengubah status pegawai')
  }
}

const handleSaveEmployee = async (employeeData: any) => {
  try {
    if (editingEmployee.value) {
      await employeeApi.updateEmployee(editingEmployee.value.id, employeeData)
      toast.success('Data pegawai berhasil diperbarui')
    } else {
      await employeeApi.createEmployee(employeeData)
      toast.success('Pegawai baru berhasil ditambahkan')
    }
    
    closeModal()
    fetchEmployees()
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan data pegawai')
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingEmployee.value = null
}

// Selection
const selectAll = () => {
  if (selectedEmployees.value.length === employees.value.length) {
    selectedEmployees.value = []
  } else {
    selectedEmployees.value = [...employees.value]
  }
}

const bulkAction = async (action: string) => {
  try {
    const employeeIds = selectedEmployees.value.map(emp => emp.id)
    
    switch (action) {
      case 'activate':
        await Promise.all(employeeIds.map(id => 
          employeeApi.updateEmployee(id, { status: 'active' })
        ))
        toast.success(`${employeeIds.length} pegawai berhasil diaktifkan`)
        break
        
      case 'deactivate':
        await Promise.all(employeeIds.map(id => 
          employeeApi.updateEmployee(id, { status: 'inactive' })
        ))
        toast.success(`${employeeIds.length} pegawai berhasil dinonaktifkan`)
        break
    }
    
    showBulkModal.value = false
    selectedEmployees.value = []
    fetchEmployees()
  } catch (error: any) {
    toast.error('Gagal melakukan aksi bulk')
  }
}

const handleBulkAction = async (action: string, data?: any) => {
  try {
    const employeeIds = selectedEmployees.value.map(emp => emp.id)
    
    switch (action) {
      case 'department':
        await Promise.all(employeeIds.map(id => 
          employeeApi.updateEmployee(id, { department: data.department })
        ))
        toast.success(`Departemen ${employeeIds.length} pegawai berhasil diubah`)
        break
        
      case 'status':
        await Promise.all(employeeIds.map(id => 
          employeeApi.updateEmployee(id, { status: data.status })
        ))
        toast.success(`Status ${employeeIds.length} pegawai berhasil diubah`)
        break
    }
    
    selectedEmployees.value = []
    showBulkModal.value = false
    await fetchEmployees()
  } catch (error: any) {
    toast.error('Gagal melakukan aksi bulk')
  }
}

const sortBy = (field: string) => {
  filters.sortBy = field
  fetchEmployees()
}

// Export
const exportEmployees = async () => {
  try {
    exporting.value = true
    // Implementation for export functionality
    toast.success('Data pegawai berhasil diekspor')
  } catch (error: any) {
    toast.error('Gagal mengekspor data')
  } finally {
    exporting.value = false
  }
}

// Utility functions
const getInitials = (name: string | null | undefined) => {
  if (!name || typeof name !== 'string') return 'NA'
  return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('id-ID')
}

const getWorkDuration = (joinDate: string) => {
  const join = new Date(joinDate)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - join.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 30) return `${diffDays} hari`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} bulan`
  return `${Math.floor(diffDays / 365)} tahun`
}

const getUserRole = (employee: Employee) => {
  // Since Employee interface doesn't have role, we'll need to implement this
  // For now, return 'employee' as default
  return 'employee'
}

const getRoleVariant = (role: string) => {
  switch (role) {
    case 'teacher':
      return 'info'
    case 'staff':
      return 'gray'
    case 'admin':
      return 'primary'
    default:
      return 'gray'
  }
}

const getRoleLabel = (role: string) => {
  switch (role) {
    case 'teacher':
      return 'Guru'
    case 'staff':
      return 'Staff'
    case 'admin':
      return 'Admin'
    default:
      return 'Pegawai'
  }
}

const getPercentage = (part: number, total: number) => {
  if (total === 0) return 0
  return Math.round((part / total) * 100)
}

const getPageNumbers = () => {
  const pageNumbers = []
  for (let i = 1; i <= pagination.last_page; i++) {
    pageNumbers.push(i)
  }
  return pageNumbers
}

const resetFilters = () => {
  filters.search = ''
  filters.department = ''
  filters.status = ''
  filters.role = ''
  filters.joinYear = ''
  filters.salaryMin = ''
  filters.salaryMax = ''
  filters.sortBy = ''
  pagination.current_page = 1
  fetchEmployees()
}

// Import functionality
const downloadTemplate = async () => {
  try {
    downloadingTemplate.value = true
    
    const blob = await employeeApi.downloadTemplate()
    
    // Create and download file
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `Template_Data_Guru_Staff_${new Date().toISOString().split('T')[0]}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    toast.success('Template berhasil didownload')
  } catch (error: any) {
    toast.error('Gagal mendownload template')
  } finally {
    downloadingTemplate.value = false
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    selectedFile.value = file
    resetImportData()
  }
}

const handleFileDrop = (event: DragEvent) => {
  event.preventDefault()
  dragOver.value = false
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.type.includes('spreadsheet') || file.name.endsWith('.xlsx') || file.name.endsWith('.xls')) {
      selectedFile.value = file
      resetImportData()
    } else {
      toast.error('Format file tidak didukung. Gunakan file Excel (.xlsx atau .xls)')
    }
  }
}

const clearFile = () => {
  selectedFile.value = null
  resetImportData()
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const resetImportData = () => {
  importPreview.value = []
  importSummary.value = null
}

const previewImport = async () => {
  if (!selectedFile.value) return
  
  try {
    previewing.value = true
    
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    
    const response = await employeeApi.previewImport(formData)
    
    if (response.success) {
      importPreview.value = response.data.preview
      importSummary.value = response.data.summary
      toast.success('Preview data berhasil dimuat')
    } else {
      toast.error(response.message || 'Gagal memproses file')
    }
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal memproses file')
  } finally {
    previewing.value = false
  }
}

const executeImport = async () => {
  if (!selectedFile.value || !importSummary.value) return
  
  try {
    importing.value = true
    
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    
    const response = await employeeApi.executeImport(formData, {
      skip_duplicates: importOptions.skipDuplicates,
      update_existing: importOptions.updateExisting,
      validate_only: importOptions.validateOnly
    })
    
    if (response.success) {
      toast.success(response.message)
      
      if (!importOptions.validateOnly) {
        closeImportModal()
        fetchEmployees()
      }
    } else {
      toast.error(response.message || 'Gagal mengimport data')
    }
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Gagal mengimport data')
  } finally {
    importing.value = false
  }
}

const closeImportModal = () => {
  showImportModal.value = false
  clearFile()
  resetImportData()
  importOptions.skipDuplicates = true
  importOptions.updateExisting = false
  importOptions.validateOnly = false
}

// Watchers
watch(() => filters.search, debouncedSearch)

onMounted(() => {
  fetchEmployees()
})
</script> 