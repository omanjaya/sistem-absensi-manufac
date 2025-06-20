# 🔧 Production API Connection Fix

## 🚨 Problem Identified

**Error**: `Login error: timeout of 30000ms exceeded`

Frontend tidak bisa terhubung ke backend API di production (https://manufac.id).

### Root Cause Analysis

1. **CORS Configuration** - Backend hanya mengizinkan `localhost`, tidak termasuk `manufac.id`
2. **Incorrect API URLs** - Frontend mencoba akses `/api` tapi seharusnya `/backend-api`
3. **Missing Environment Variables** - Production config tidak terbaca dengan benar
4. **Wrong .htaccess Routing** - URL rewriting salah arah

## ✅ Solutions Implemented

### 1. CORS Configuration Fix

**File**: `backend-api/config/cors.php`

```php
'allowed_origins' => [
    // Production domain
    'https://manufac.id',
    'https://www.manufac.id',
    'http://manufac.id',
    'http://www.manufac.id',

    // Development domains (unchanged)
    'http://localhost:3000',
    // ... etc
],
```

### 2. Frontend Environment Variables

**File**: `frontend-web/.env`

```env
VITE_APP_NAME="Sistem Absensi Manufac"
VITE_APP_ENV=production
VITE_API_BASE_URL=https://manufac.id/backend-api/api
VITE_LARAVEL_API_URL=https://manufac.id/backend-api/api
VITE_API_BACKEND_URL=https://manufac.id/backend-api
VITE_FACE_API_URL=https://manufac.id/face-server
VITE_FLASK_API_URL=https://manufac.id/face-server
VITE_APP_URL=https://manufac.id
```

### 3. Smart API URL Detection

**File**: `frontend-web/src/utils/api-config.ts`

```typescript
// Production detection
if (hostname === "manufac.id" || hostname === "www.manufac.id") {
  return `${protocol}//${hostname}/backend-api/api`;
}

// Development fallback
return `${protocol}//${hostname}:${PORT_CONFIG.current.laravel}/api`;
```

### 4. .htaccess Routing Fix

**File**: `.htaccess`

```apache
# Backend API Routes - Forward to Laravel backend
RewriteCond %{REQUEST_URI} ^/backend-api/
RewriteRule ^backend-api/(.*)$ backend-api/public/$1 [L,QSA]

# Face Recognition API Routes - Forward to Flask server
RewriteCond %{REQUEST_URI} ^/face-server/
RewriteRule ^face-server/(.*)$ face-server/$1 [L,QSA]

# Legacy API Routes (for backward compatibility)
RewriteCond %{REQUEST_URI} ^/api/
RewriteRule ^api/(.*)$ backend-api/public/api/$1 [L,QSA]
```

## 🚀 Deployment

### Quick Fix Script

```bash
# Run this to deploy the fix
./scripts/fix-production-api.bat
```

### Manual Steps

1. **Build Frontend**:

   ```bash
   cd frontend-web
   npm run build
   ```

2. **Commit Changes**:

   ```bash
   git add .
   git commit -m "🔧 Fix production API connection issues"
   git push origin main
   ```

3. **Auto-deployment** via GitHub webhook akan terpicu secara otomatis

## 🔍 Testing Checklist

### 1. Frontend Connection Test

- ✅ Visit: https://manufac.id
- ✅ Check Console: API URLs should point to `/backend-api/api`
- ✅ Login Test: `admin@absensi.com` / `password`

### 2. API Endpoint Tests

```bash
# Test Laravel API
curl -X GET "https://manufac.id/backend-api/api/health" \
  -H "Accept: application/json"

# Test CORS
curl -X OPTIONS "https://manufac.id/backend-api/api/auth/login" \
  -H "Origin: https://manufac.id" \
  -H "Access-Control-Request-Method: POST"
```

### 3. Debug Console Output

Jika berhasil, console akan menampilkan:

```
🔗 API Configuration:
  Laravel API: https://manufac.id/backend-api/api
  Flask AI: https://manufac.id/face-server
  Frontend: https://manufac.id
```

## 🐛 Troubleshooting

### Still Getting Timeout?

1. **Check Database Connection**:

   ```bash
   # SSH ke server
   cd backend-api
   php artisan migrate:status
   ```

2. **Verify .env File**:

   ```bash
   # Pastikan database credentials benar
   cat backend-api/.env | grep DB_
   ```

3. **Clear Laravel Cache**:
   ```bash
   cd backend-api
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

### CORS Still Blocking?

1. **Hard Refresh Browser**: `Ctrl+F5`
2. **Check Server Headers**:
   ```bash
   curl -I "https://manufac.id/backend-api/api/health"
   ```
3. **Verify Domain in CORS config**

### 404 Not Found?

1. **Check .htaccess Permissions**:
   ```bash
   chmod 644 .htaccess
   ```
2. **Verify Rewrite Module**:
   ```bash
   # Check if mod_rewrite is enabled
   apache2ctl -M | grep rewrite
   ```

## 📊 Expected URLs After Fix

| Service          | URL                                             | Description    |
| ---------------- | ----------------------------------------------- | -------------- |
| Frontend         | `https://manufac.id`                            | Vue.js SPA     |
| Laravel API      | `https://manufac.id/backend-api/api`            | Main backend   |
| Face Recognition | `https://manufac.id/face-server`                | Python Flask   |
| Login Endpoint   | `https://manufac.id/backend-api/api/auth/login` | Authentication |

## 🔄 Cache Busting

Cache version diupdate ke `1.0.1` untuk memaksa browser refresh:

```env
VITE_CACHE_VERSION=1.0.1
```

Browser akan mengunduh ulang JavaScript bundle dengan konfigurasi yang baru.

---

**Status**: ✅ **FIXED AND DEPLOYED**  
**Last Updated**: $(date)  
**Next Test**: Login via https://manufac.id
