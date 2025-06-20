# 📘 MASTER PROMPT UNTUK CURSOR.AI
## 🔧 Sistem Absensi Wajah Berbasis Laravel + Vue + Flask AI (Multi-Repo)

---

## 🗂️ STRUKTUR MULTI-REPO YANG DIREKOMENDASIKAN

```
sistem-absensi-root/
├── backend-api/              ← Laravel REST API
├── frontend-web/             ← Vue 3 + Tailwind SPA
├── face-server/              ← Python Flask + face_recognition
├── docs/                     ← Dokumentasi teknis (ERD, roadmap)
└── deploy/                   ← Docker Compose, nginx.conf, env template
```

---

## 🗂️ [1] SETUP BACKEND LARAVEL API (`backend-api/`)

### 🎯 TUJUAN:
Bangun Laravel 11 REST API dengan login multi-role, CRUD pegawai, absensi, izin, dan penggajian.

### ✅ PROMPT:
```
Buatkan Laravel REST API di folder `backend-api/` dengan spesifikasi berikut:

1. Autentikasi login menggunakan Sanctum
2. Role dan permission menggunakan Spatie Laravel Permission
3. Struktur route modular via `routes/api.php`
4. Endpoint utama:
   - POST /api/login
   - GET/POST /api/pegawai
   - GET/POST /api/izin
   - GET/POST /api/jadwal
   - POST /api/absensi (menerima foto base64 dan lokasi GPS)
   - POST /api/gaji/export (mengembalikan Excel)
5. Middleware untuk membatasi akses berdasarkan role
6. Struktur folder: app/Http/Controllers/API, app/Models, app/Services
7. Gunakan PostgreSQL dan siapkan file `.env.example`
```

---

## 🖼️ [2] SETUP AI SERVER (`face-server/`)

### 🎯 TUJUAN:
Bangun Flask API yang bisa mengenali wajah dan melakukan training wajah baru.

### ✅ PROMPT:
```
Buatkan Flask API di folder `face-server/` menggunakan Python 3.11 dan library `face_recognition`.

1. Endpoint:
   - POST /recognize → menerima foto base64, balas nama user atau "unknown"
   - POST /register-face → terima foto base64 dan ID user, simpan encoding vector
   - GET /health → cek status server
2. Simpan face vector ke file `.npy` atau `.json` per user di folder `/templates`
3. Gunakan CORS agar bisa diakses dari Laravel
4. Tambahkan logging di setiap request
5. Jangan gunakan database, cukup local file untuk vector wajah
```

---

## 🎨 [3] SETUP FRONTEND VUE 3 (`frontend-web/`)

### 🎯 TUJUAN:
Buat antarmuka absensi wajah, login multi-role, dan dashboard pegawai.

### ✅ PROMPT:
```
Buatkan Vue 3 project di folder `frontend-web/` menggunakan Vite dan TailwindCSS.

1. Halaman yang harus dibuat:
   - Login.vue → form login, kirim ke Laravel `/api/login`
   - Dashboard.vue → ringkasan info pegawai
   - Absensi.vue → akses kamera, ambil lokasi GPS, kirim ke Laravel
   - Pegawai.vue → tabel daftar pegawai (server-side pagination via AJAX)
2. Gunakan composables untuk auth token handling
3. Gunakan Pinia untuk state management (user info)
4. Layout responsive, tombol besar, notifikasi toast
5. Siapkan juga layout untuk admin dan user biasa (role-based UI)
```

---

## ⚙️ [4] SETUP DOCKER & DEPLOYMENT (`deploy/`)

### 🎯 TUJUAN:
Buat konfigurasi docker-compose agar seluruh sistem jalan dalam satu perintah.

### ✅ PROMPT:
```
Buatkan konfigurasi `docker-compose.yml` di folder `deploy/` untuk menjalankan:

1. Laravel backend (port 8000)
2. Vue frontend (port 3000)
3. Flask face server (port 5000)
4. PostgreSQL database
5. Redis queue (opsional)

Tambahkan juga file:
- Dockerfile untuk Laravel, Vue, dan Flask
- nginx.conf untuk reverse proxy ke semua service
- .env.example untuk semua service
- Setup volume bind mount agar perubahan kode langsung aktif (tanpa rebuild)
```

---

## 🧠 [5] INTEGRASI AI + ABSENSI

### 🎯 TUJUAN:
Hubungkan sistem absensi dari frontend → Laravel → Flask AI.

### ✅ PROMPT:
```
1. Di frontend (Absensi.vue), ambil foto dari kamera dalam format base64 dan GPS (navigator.geolocation)
2. Kirim ke Laravel via endpoint: POST /api/absensi
3. Di Laravel, teruskan data wajah ke Flask API `/recognize`
4. Flask mengembalikan hasil (nama user atau unknown)
5. Laravel simpan data ke tabel `attendances` jika user dikenali dan dalam radius kantor
6. Radius dicek via Haversine formula
7. Jika gagal (foto blur, wajah tidak ditemukan), log ke tabel `attendance_logs`
```

---

## 🧾 [6] FITUR TAMBAHAN

### 📄 Training Wajah
```
Buat form upload wajah di Vue `/register-wajah`
Kirim foto ke endpoint Laravel → teruskan ke Flask `/register-face`
Simpan vector wajah dengan ID user terkait
```

### 📈 Laporan Gaji
```
Buat fitur filter laporan:
- Per pegawai
- Per bulan
- Tombol export XLSX
```

---

## 🧪 [7] TESTING & MONITORING

### ✅ PROMPT:
```
- Buat Laravel Feature test untuk login, absensi, dan export gaji
- Buat script Python test: kirim foto ke `/recognize` dan validasi hasil
- Tambahkan route `/health` di semua service
```

---

## 📚 [8] DOKUMENTASI

### ✅ PROMPT:
```
Buat file-file berikut di folder `docs/`:
- api-endpoints.md → daftar semua endpoint Laravel dan Flask
- system-architecture.png → diagram arsitektur sistem
- face-recognition-flow.md → alur frontend → AI server
- database-design.md → penjelasan tabel dan relasi
- cursor-agent-notes.md → penjelasan task khusus untuk AI Agent
```

---

## 📦 [9] OPSIONAL (PRODUKSI)

### ✅ PROMPT:
```
- Setup HTTPS reverse proxy via Nginx
- Tambahkan backup PostgreSQL otomatis (daily)
- Tambahkan otentikasi API Key di Flask AI server
```

