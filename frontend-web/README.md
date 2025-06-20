# 🎯 Sistem Absensi Wajah - Frontend

Frontend aplikasi sistem absensi dengan pengenalan wajah menggunakan Vue 3, Vite, TypeScript, dan Tailwind CSS.

## 🚀 Fitur Utama

- ✅ **Face Recognition Attendance** - Absensi menggunakan pengenalan wajah
- 📍 **GPS Location Validation** - Validasi lokasi dalam radius kantor
- 📱 **Responsive Design** - Optimized untuk desktop dan mobile
- 🔐 **Role-based Access** - Admin dan Employee dengan akses berbeda
- 📊 **Dashboard Analytics** - Statistik absensi dan laporan
- 🎨 **Modern UI/UX** - Clean dan user-friendly interface

## 🛠️ Tech Stack

- **Vue 3** - Progressive JavaScript framework
- **TypeScript** - Type-safe JavaScript
- **Vite** - Fast build tool dan dev server
- **Tailwind CSS** - Utility-first CSS framework
- **Headless UI** - Unstyled, accessible UI components
- **Heroicons** - Beautiful hand-crafted SVG icons
- **Pinia** - State management
- **Vue Router** - Client-side routing
- **Axios** - HTTP client
- **Vue Toastification** - Toast notifications

## 📋 Prasyarat

- Node.js 18+ dan npm/yarn
- Browser modern dengan dukungan:
  - WebRTC (untuk kamera)
  - Geolocation API (untuk GPS)
  - LocalStorage/Cookies

## 🔧 Instalasi

### 1. Clone dan Install Dependencies

\`\`\`bash
cd frontend-web
npm install
\`\`\`

### 2. Setup Environment

\`\`\`bash
cp .env.example .env
\`\`\`

Edit file `.env` sesuai konfigurasi:

\`\`\`env

# API URLs

VITE_LARAVEL_API_URL=http://localhost:8000/api
VITE_FLASK_AI_URL=http://localhost:5000

# App Configuration

VITE_APP_NAME="Sistem Absensi Wajah"

# Office Location (Jakarta)

VITE_OFFICE_LATITUDE=-6.2088
VITE_OFFICE_LONGITUDE=106.8456
VITE_OFFICE_RADIUS=100

# Camera Settings

VITE_CAMERA_WIDTH=640
VITE_CAMERA_HEIGHT=480
VITE_PHOTO_QUALITY=0.85
VITE_MAX_FILE_SIZE=2097152
\`\`\`

### 3. Development Server

\`\`\`bash
npm run dev
\`\`\`

Aplikasi akan berjalan di `http://localhost:3000`

## 🎮 Demo Login

| Role     | Email                | Password |
| -------- | -------------------- | -------- |
| Admin    | admin@example.com    | password |
| Employee | employee@example.com | password |

## 📱 Fitur per Role

### 👥 Employee

- ✅ Dashboard dengan status absensi
- ✅ Absensi masuk/keluar dengan face recognition
- ✅ Registrasi wajah
- ✅ Pengajuan izin/cuti
- ✅ View profil dan edit data personal

### 👑 Admin

- ✅ Semua fitur Employee +
- ✅ Manajemen pegawai (CRUD)
- ✅ Laporan absensi seluruh pegawai
- ✅ Approve/reject pengajuan izin
- ✅ Generate laporan gaji
- ✅ Export data ke Excel

## 🏗️ Struktur Folder

\`\`\`
src/
├── components/ # Reusable Vue components
├── composables/ # Vue composables (useCamera, useGeolocation)
├── layouts/ # Layout components (AppLayout, AuthLayout)
├── router/ # Vue Router configuration
├── services/ # API service layer
├── stores/ # Pinia state management
├── types/ # TypeScript type definitions
├── utils/ # Utility functions
├── views/ # Page components
└── styles/ # Global CSS and Tailwind config
\`\`\`

## 🔌 API Integration

Frontend berkomunikasi dengan 2 backend services:

### Laravel API (Port 8000)

- Authentication & authorization
- CRUD operations
- Business logic
- Database operations

### Flask AI API (Port 5000)

- Face recognition
- Face registration
- Computer vision processing

## 📷 Camera & GPS

### Camera Requirements

- HTTPS connection (required untuk production)
- User permission untuk camera access
- Resolusi minimum 640x480
- Format JPEG dengan quality 85%

### GPS Requirements

- User permission untuk location access
- Accuracy minimum untuk validasi
- Fallback handling jika GPS unavailable

## 🚀 Build & Deploy

### Development Build

\`\`\`bash
npm run build
\`\`\`

### Preview Production Build

\`\`\`bash
npm run preview
\`\`\`

### Deploy ke Server

1. Build aplikasi: `npm run build`
2. Upload folder `dist/` ke web server
3. Setup reverse proxy (Nginx/Apache)
4. Ensure HTTPS untuk camera access

## ⚙️ Environment Variables

| Variable                | Description                 | Default                     |
| ----------------------- | --------------------------- | --------------------------- |
| `VITE_LARAVEL_API_URL`  | Laravel backend URL         | `http://localhost:8000/api` |
| `VITE_FLASK_AI_URL`     | Flask AI service URL        | `http://localhost:5000`     |
| `VITE_APP_NAME`         | Application name            | `"Sistem Absensi Wajah"`    |
| `VITE_OFFICE_LATITUDE`  | Office latitude coordinate  | `-6.2088`                   |
| `VITE_OFFICE_LONGITUDE` | Office longitude coordinate | `106.8456`                  |
| `VITE_OFFICE_RADIUS`    | Attendance radius in meters | `100`                       |
| `VITE_CAMERA_WIDTH`     | Camera resolution width     | `640`                       |
| `VITE_CAMERA_HEIGHT`    | Camera resolution height    | `480`                       |
| `VITE_PHOTO_QUALITY`    | JPEG quality (0.0-1.0)      | `0.85`                      |
| `VITE_MAX_FILE_SIZE`    | Max photo size in bytes     | `2097152` (2MB)             |

## 🐛 Troubleshooting

### Camera Issues

- **Permission denied**: User harus mengizinkan camera access
- **Camera in use**: Tutup aplikasi lain yang menggunakan camera
- **No camera found**: Pastikan device memiliki camera

### GPS Issues

- **Location denied**: User harus mengizinkan location access
- **Inaccurate location**: Tunggu beberapa detik untuk GPS lock
- **Outside radius**: Pastikan berada dalam radius kantor

### API Connection

- **CORS errors**: Pastikan backend mengizinkan frontend domain
- **Network errors**: Check backend services running di port yang benar
- **Auth errors**: Check token validity dan session

## 📝 Development Guide

### Code Style

- **TypeScript** untuk type safety
- **Composition API** untuk reactive logic
- **ESLint** untuk code quality
- **Prettier** untuk code formatting

### Best Practices

- Use composables untuk reusable logic
- Implement proper error handling
- Add loading states untuk better UX
- Use TypeScript interfaces untuk API responses
- Follow Vue 3 composition API patterns

## 🔗 Related Services

- **Backend API**: Laravel REST API di port 8000
- **AI Service**: Flask face recognition di port 5000
- **Database**: PostgreSQL untuk data storage

## 📞 Support

Untuk pertanyaan teknis atau bug report, silakan buat issue di repository ini.

---

⚡ **Happy Coding!** Aplikasi siap untuk development dan production deployment.
