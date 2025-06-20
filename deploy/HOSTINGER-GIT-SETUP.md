# 🔧 Fix Hostinger Git Deployment - composer.json Issue

## ❌ **Problem Yang Anda Alami:**

```
Deployment start
Repository https://github.com/omanjaya/sistem-absensi-manufac.git
Checking project directory is empty
Project directory is git repository
On branch main
Your branch is up to date with 'origin/main'.

nothing to commit, working tree clean
Looking for composer.lock file
composer.lock file was not found ❌
Looking for composer.json file
composer.json file was not found ❌
Deployment end
```

## 🎯 **Root Cause:**

Hostinger Git deployment mencari `composer.json` dan `composer.lock` di **root directory**, tapi project structure Anda memiliki file tersebut di dalam folder `backend-api/`.

## ✅ **Solusi yang Sudah Saya Buat:**

### **1. Root Files Created:**

- ✅ `composer.json` - Root composer file untuk Hostinger
- ✅ `composer.lock` - Copied dari backend-api/
- ✅ `package.json` - Root package.json dengan build scripts
- ✅ `.env.example` - Environment template
- ✅ `build.sh` - Build script untuk deployment
- ✅ `deploy.json` - Hostinger deployment config

### **2. Structure Sekarang:**

```
sistem-absensi-manufac/
├── composer.json        ✅ # Root composer (Hostinger deteksi ini)
├── composer.lock        ✅ # Root composer lock
├── package.json         ✅ # Root package.json
├── .env.example         ✅ # Environment template
├── build.sh             ✅ # Build script
├── deploy.json          ✅ # Deployment config
├── frontend-web/        # Vue.js frontend
├── backend-api/         # Laravel backend
└── ...
```

## 🚀 **Steps to Fix:**

### **Step 1: Commit & Push Changes**

```cmd
git add .
git commit -m "Fix: Add root composer.json and build scripts for Hostinger Git deployment"
git push origin main
```

### **Step 2: Update Hostinger Git Settings**

1. **Login** ke Hostinger hPanel
2. **Website → Manage → Git**
3. **Update Repository Settings:**

   ```
   Repository URL: https://github.com/omanjaya/sistem-absensi-manufac.git
   Branch: main
   Install Path: public_html (leave empty)
   ```

4. **Configure Build Commands:**
   ```bash
   # Hostinger akan run commands ini:
   chmod +x build.sh
   ./build.sh
   ```

### **Step 3: Test Deployment**

- **Trigger Deploy** dari Hostinger
- **Check Logs** untuk memastikan build berhasil
- **Verify** structure di File Manager

## 📋 **Build Process (build.sh):**

Ketika Hostinger run deployment, script `build.sh` akan:

1. **Build Frontend:**

   ```bash
   cd frontend-web
   npm install
   npm run build:production
   ```

2. **Install Backend:**

   ```bash
   cd backend-api
   composer install --no-dev --optimize-autoloader
   ```

3. **Copy Files:**

   ```bash
   cp -r frontend-web/dist/* ./     # Frontend ke root
   mkdir -p api
   cp -r backend-api/* api/         # Backend ke /api/
   ```

4. **Set Permissions:**
   ```bash
   chmod -R 755 api/storage
   chmod -R 755 api/bootstrap/cache
   ```

## 🎯 **Expected Result:**

Setelah deployment berhasil, struktur di Hostinger akan:

```
public_html/                 # manufac.id root
├── index.html              # Vue.js frontend
├── assets/                 # CSS, JS, images
├── api/                    # Laravel backend
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── .env               # Production config
│   └── ...
└── ...
```

## 🔍 **Troubleshooting:**

### **Jika masih error "composer.json not found":**

1. **Check** apakah file sudah di-commit dan push
2. **Verify** repository URL di Hostinger settings
3. **Clear** Hostinger deployment cache
4. **Retry** deployment

### **Jika build script gagal:**

1. **Check** Node.js version di Hostinger (minimal 18.x)
2. **Verify** PHP version (minimal 8.2)
3. **Check** build logs untuk error details

### **Jika permission issues:**

```bash
# Run di Hostinger terminal setelah deploy:
cd public_html
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache
```

## 📝 **Manual Alternative (Jika Git Deploy Masih Bermasalah):**

Jika Git deployment tetap bermasalah, Anda bisa:

1. **Use** manual upload dengan `deploy-temp.zip` yang sudah ready
2. **Upload** via File Manager ke public_html
3. **Configure** database manually
4. **Setup** Git deployment setelah sistem stable

## 🎉 **Next Steps:**

1. **Commit & Push** changes yang sudah saya buat
2. **Update** Hostinger Git settings
3. **Test** deployment
4. **Monitor** build logs
5. **Verify** https://manufac.id working

---

**Files yang sudah saya siapkan untuk fix issue ini:**

- ✅ `composer.json` - Root composer dengan Laravel dependencies
- ✅ `composer.lock` - Copied dari backend-api
- ✅ `build.sh` - Automated build script
- ✅ `.env.example` - Production environment template
- ✅ `deploy.json` - Hostinger configuration

**Sekarang Hostinger akan bisa detect composer files dan run automated deployment!** 🚀
