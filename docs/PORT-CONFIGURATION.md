# 🔗 Konfigurasi Port Dinamis - Sistema Absensi

## Overview

Sistema Absensi dilengkapi dengan **sistem deteksi port dinamis** yang secara otomatis menyesuaikan koneksi API berdasarkan port yang sedang digunakan. Tidak lagi terkunci pada port tertentu!

## 🎯 Fitur Utama

### ✅ Auto-Detection Port

- **Frontend** dan **Backend** akan otomatis saling terhubung
- **Tidak perlu hardcode** URL atau port
- **Fleksibel** untuk berbagai environment development

### ✅ Port Mapping Otomatis

| Frontend Port | Backend Port | Keterangan                             |
| ------------- | ------------ | -------------------------------------- |
| 3000          | 8000         | **Default Vite** ↔ **Default Laravel** |
| 5173          | 8001         | Alternative Vite ↔ Alternative Laravel |
| 4173          | 8000         | Vite Preview ↔ Laravel Default         |
| 8080          | 8000         | Vue CLI ↔ Laravel Default              |

### ✅ Fallback & Prioritas

1. **Prioritas 1**: Environment Variable (`.env`)
2. **Prioritas 2**: Auto-detection berdasarkan port frontend
3. **Prioritas 3**: Default port 8000

## 🚀 Cara Penggunaan

### Method 1: Auto Script (Recommended)

```bash
# Jalankan script otomatis
scripts/dev.bat

# Script akan:
# 1. Check backend di port 8000
# 2. Check frontend di port 3000
# 3. Auto-start jika belum berjalan
# 4. Buka browser otomatis
```

### Method 2: Manual

```bash
# Terminal 1: Backend
cd backend-api
php artisan serve --port=8000

# Terminal 2: Frontend
cd frontend-web
npm run dev
# Otomatis akan buka di port 3000
```

### Method 3: Custom Port

```bash
# Backend custom port
cd backend-api
php artisan serve --port=8001

# Frontend akan otomatis detect dan connect ke 8001
cd frontend-web
npm run dev -- --port=5173
```

## ⚙️ Konfigurasi Environment

### `.env` file (Opsional)

```env
# Jika ingin override auto-detection
VITE_LARAVEL_API_URL=http://localhost:8000/api
VITE_FLASK_AI_URL=http://localhost:5000

# Atau biarkan kosong untuk auto-detection
# VITE_LARAVEL_API_URL=
# VITE_FLASK_AI_URL=
```

## 🛠️ Technical Implementation

### Frontend: `src/utils/api-config.ts`

```typescript
// Auto-detection logic
const getApiUrl = () => {
  // 1. Check environment variable
  if (import.meta.env.VITE_LARAVEL_API_URL) {
    return import.meta.env.VITE_LARAVEL_API_URL;
  }

  // 2. Auto-detect based on frontend port
  const currentPort = window.location.port;
  const backendPort = PORT_MAPPING[currentPort] || "8000";
  return `http://localhost:${backendPort}/api`;
};
```

### Services Integration

```typescript
// api.ts menggunakan dynamic URL
const laravelApi = axios.create({
  baseURL: getLaravelApiUrl(), // Dynamic!
});

// notifications.ts juga dynamic
const streamUrl = `${getLaravelApiUrl()}/notifications/stream`;
```

## 🔍 Debug & Monitoring

### Console Debug Info

Saat development, aplikasi akan menampilkan info di console:

```
🔗 API Configuration:
  Laravel API: http://localhost:8000/api
  Flask AI: http://localhost:5000
  Frontend: http://localhost:3000
  Port Mapping: {3000: 8000, 5173: 8001, ...}
  Current Port: 3000
```

### Health Check Functions

```typescript
// Check backend connection
await checkBackendConnection(); // true/false

// Check Flask AI connection
await checkFlaskConnection(); // true/false

// Get full configuration
getApiConfiguration(); // Complete debug info
```

## 🎨 Network Access

### Local Development

```
Frontend: http://localhost:3000
Backend:  http://localhost:8000/api
Flask AI: http://localhost:5000
```

### Network Access (Multi-device testing)

```
Frontend: http://192.168.x.x:3000
Backend:  http://192.168.x.x:8000/api
Flask AI: http://192.168.x.x:5000
```

## ⚡ Keuntungan

### 🔄 Fleksibilitas

- Bisa run di port manapun
- Auto-adapt tanpa konfigurasi manual
- Support multiple development scenarios

### 🛡️ Reliability

- Fallback mechanism
- Error handling yang baik
- Informative error messages

### 🚀 Developer Experience

- Zero configuration untuk case umum
- Easy debugging dengan console logs
- Script automation untuk quick start

## 🆘 Troubleshooting

### Backend tidak terkoneksi

```bash
# Check apakah Laravel running
curl http://localhost:8000/api/test-notifications

# Atau check di browser
http://localhost:8000/api/test-notifications
```

### Frontend tidak detect backend

1. Check console untuk debug info
2. Verify port mapping di `api-config.ts`
3. Override dengan environment variable jika perlu

### CORS Issues

- Pastikan Laravel backend allow CORS
- Check `config/cors.php`
- Restart backend setelah config change

## 💡 Tips

1. **Gunakan script `dev.bat`** untuk pengalaman terbaik
2. **Check console** untuk debug info saat development
3. **Override dengan .env** jika butuh custom configuration
4. **Test network access** dengan IP address untuk multi-device testing

---

**🎉 Sekarang aplikasi bisa berjalan di port apapun secara otomatis!**
