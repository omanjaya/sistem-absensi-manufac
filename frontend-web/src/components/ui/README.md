# UI Components Library

Koleksi komponen UI yang dapat digunakan kembali untuk aplikasi Vue.js 3 dengan TypeScript dan Tailwind CSS.

## 📦 Available Components

### 1. Card

Komponen dasar untuk menampilkan konten dalam container yang rapi.

```vue
<template>
  <Card title="Card Title" subtitle="Optional subtitle">
    <p>Card content goes here</p>

    <template #headerActions>
      <Button variant="primary">Action</Button>
    </template>

    <template #footer>
      <p>Footer content</p>
    </template>
  </Card>
</template>
```

**Props:**

- `title?: string` - Judul card
- `subtitle?: string` - Subjudul card
- `padding?: 'sm' | 'md' | 'lg' | 'xl' | 'none'` - Ukuran padding (default: 'md')
- `hover?: boolean` - Efek hover shadow (default: false)
- `contentClass?: string` - CSS class untuk content area
- `customClass?: string` - CSS class tambahan

### 2. Button

Komponen tombol dengan berbagai varian dan ukuran.

```vue
<template>
  <Button variant="primary" size="md" :loading="isLoading" @click="handleClick">
    Click Me
  </Button>

  <!-- With icon -->
  <Button variant="secondary" :icon="PlusIcon" label="Add Item" />
</template>
```

**Props:**

- `variant?: 'primary' | 'secondary' | 'danger' | 'success' | 'warning' | 'ghost'`
- `size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'`
- `type?: 'button' | 'submit' | 'reset'`
- `disabled?: boolean`
- `loading?: boolean` - Menampilkan spinner loading
- `icon?: any` - Heroicon component
- `label?: string` - Text label
- `fullWidth?: boolean` - Lebar penuh

### 3. Modal

Komponen modal dengan animasi dan backdrop.

```vue
<template>
  <Modal
    v-model:show="showModal"
    title="Modal Title"
    size="lg"
    :closable="true"
  >
    <p>Modal content</p>

    <template #footer>
      <div class="flex space-x-2">
        <Button variant="secondary" @click="showModal = false">Cancel</Button>
        <Button variant="primary">Save</Button>
      </div>
    </template>
  </Modal>
</template>
```

**Props:**

- `show: boolean` - Kontrol visibility modal
- `title?: string` - Judul modal
- `size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'`
- `closable?: boolean` - Tampilkan tombol close (default: true)
- `closeOnBackdrop?: boolean` - Close saat klik backdrop (default: true)

### 4. Table

Komponen tabel dengan fitur sorting, filtering, search, dan pagination.

```vue
<template>
  <Table
    title="Data Table"
    :data="tableData"
    :columns="columns"
    searchable
    :filterable="true"
    :filters="filters"
    :paginated="true"
    :page-size="10"
  >
    <!-- Custom cell template -->
    <template #cell-status="{ value }">
      <Badge :variant="getStatusVariant(value)" :label="value" />
    </template>
  </Table>
</template>

<script setup>
const columns = [
  { key: "name", label: "Name", sortable: true },
  { key: "email", label: "Email", sortable: true },
  { key: "created_at", label: "Created", type: "date" },
  { key: "salary", label: "Salary", type: "currency" },
];

const filters = [
  {
    key: "status",
    label: "Status",
    options: [
      { value: "active", label: "Active" },
      { value: "inactive", label: "Inactive" },
    ],
  },
];
</script>
```

**Props:**

- `title?: string` - Judul tabel
- `data: any[]` - Array data untuk tabel
- `columns: Column[]` - Konfigurasi kolom
- `loading?: boolean` - State loading
- `searchable?: boolean` - Enable search
- `filterable?: boolean` - Enable filtering
- `sortable?: boolean` - Enable sorting
- `paginated?: boolean` - Enable pagination

### 5. MetricCard

Komponen khusus untuk menampilkan metric/statistik dengan icon dan perubahan.

```vue
<template>
  <MetricCard
    label="Total Users"
    :value="totalUsers"
    :icon="UsersIcon"
    icon-bg="blue"
    :change="5.2"
    description="vs last month"
  />
</template>
```

**Props:**

- `label: string` - Label metric
- `value: string | number` - Nilai metric
- `icon?: any` - Heroicon component
- `iconBg?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'indigo' | 'gray'`
- `change?: number` - Persentase perubahan
- `description?: string` - Deskripsi tambahan

### 6. Badge

Komponen untuk menampilkan status atau label kecil.

```vue
<template>
  <Badge variant="success" label="Active" />
  <Badge variant="warning" outline :icon="ClockIcon">Pending</Badge>
</template>
```

**Props:**

- `variant?: 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'primary'`
- `size?: 'sm' | 'md' | 'lg'`
- `icon?: any` - Heroicon component
- `label?: string` - Text label
- `outline?: boolean` - Style outline

### 7. Form

Komponen input form yang mendukung berbagai type input.

```vue
<template>
  <Form
    v-model="formData.name"
    type="text"
    label="Full Name"
    placeholder="Enter your name"
    :required="true"
    :error="errors.name"
    help="Please enter your full name"
  />

  <Form
    v-model="formData.country"
    type="select"
    label="Country"
    :options="countryOptions"
    placeholder="Select country"
  />
</template>
```

**Props:**

- `type?: 'text' | 'email' | 'password' | 'number' | 'textarea' | 'select'`
- `label?: string` - Label input
- `placeholder?: string` - Placeholder text
- `modelValue?: string | number` - Value (v-model)
- `required?: boolean` - Field required
- `disabled?: boolean` - Disable input
- `error?: string` - Error message
- `help?: string` - Help text
- `options?: Option[]` - Options untuk select

## 🚀 Usage Example

```vue
<template>
  <div class="p-6 space-y-6">
    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <MetricCard
        v-for="metric in metrics"
        :key="metric.key"
        :label="metric.label"
        :value="metric.value"
        :icon="metric.icon"
        :icon-bg="metric.color"
        :change="metric.change"
      />
    </div>

    <!-- Data Table -->
    <Table
      title="Users"
      :data="users"
      :columns="userColumns"
      searchable
      :paginated="true"
    >
      <template #headerActions>
        <Button variant="primary" @click="addUser">Add User</Button>
      </template>

      <template #cell-status="{ value }">
        <Badge
          :variant="value === 'active' ? 'success' : 'danger'"
          :label="value"
        />
      </template>
    </Table>

    <!-- Add User Modal -->
    <Modal v-model:show="showAddModal" title="Add New User">
      <div class="space-y-4">
        <Form
          v-model="newUser.name"
          label="Name"
          placeholder="Enter name"
          :required="true"
        />
        <Form
          v-model="newUser.email"
          type="email"
          label="Email"
          placeholder="Enter email"
          :required="true"
        />
      </div>

      <template #footer>
        <div class="flex space-x-2">
          <Button variant="secondary" @click="showAddModal = false"
            >Cancel</Button
          >
          <Button variant="primary" @click="saveUser">Save</Button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { UsersIcon, UserPlusIcon } from "@heroicons/vue/24/outline";
import {
  Card,
  MetricCard,
  Badge,
  Button,
  Form,
  Table,
  Modal,
} from "@/components/ui";

// Component logic here...
</script>
```

## 🎨 Styling & Theming

Semua komponen menggunakan Tailwind CSS dengan design system yang konsisten:

- **Colors**: blue (primary), green (success), red (danger), yellow (warning), gray (neutral)
- **Spacing**: Konsisten menggunakan skala Tailwind (4, 6, 8, 12, 16, 24 spacing units)
- **Typography**: Font sizes dan weights yang seragam
- **Shadows**: Subtle shadows untuk depth
- **Animations**: Smooth transitions untuk interaksi

## 📚 Benefits

1. **Konsistensi**: Design yang seragam di seluruh aplikasi
2. **Reusability**: Komponen dapat digunakan berulang kali
3. **Maintainability**: Perubahan design dapat dilakukan di satu tempat
4. **Type Safety**: Full TypeScript support dengan proper typing
5. **Performance**: Optimized dengan Vue 3 Composition API
6. **Accessibility**: Built-in ARIA attributes dan keyboard navigation

## 🔧 Development

Untuk menambah komponen baru:

1. Buat file komponen di `/src/components/ui/`
2. Export di `/src/components/ui/index.ts`
3. Tambah dokumentasi di README ini
4. Test komponen dengan berbagai props dan scenarios

---

**Made with ❤️ for systematic and efficient frontend development**
