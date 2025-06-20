# Windows Git Deployment - Panduan Khusus

## 🪟 **Setup Git Repository (Windows)**

### **Quick Start:**

```cmd
# Jalankan script setup otomatis
scripts\setup-git-repository.bat
```

### **Manual Setup (jika diperlukan):**

```cmd
# 1. Initialize repository
git init
git add .
git commit -m "Initial commit - Sistem Absensi Manufac"
git branch -M main
git remote add origin https://github.com/omanjaya/absensi.git
git push -u origin main
```

## 🚀 **Git Deployment Workflow (Windows)**

### **Method 1: Automated Script**

```cmd
# Build dan deploy dengan script otomatis
scripts\deploy-git-hostinger.bat
```

Script akan:

- ✅ Check Git status (pastikan tidak ada uncommitted changes)
- ✅ Build frontend dengan `npm run build:production`
- ✅ Prepare backend dengan `composer install --no-dev`
- ✅ Copy files dengan Windows commands (`xcopy`)
- ✅ Create deployment package di `deploy-temp\`
- ✅ Optional: Push ke GitHub dan create ZIP file

### **Method 2: Manual Commands**

```cmd
:: 1. Build Frontend
cd frontend-web
npm install
npm run build:production
cd ..

:: 2. Prepare Backend
cd backend-api
composer install --no-dev --optimize-autoloader
cd ..

:: 3. Create deployment folder
mkdir deploy-temp
xcopy /E /I /H /Y frontend-web\dist\* deploy-temp\
mkdir deploy-temp\api
xcopy /E /I /H /Y backend-api\* deploy-temp\api\

:: 4. Copy production environment
copy dist-production\backend\.env deploy-temp\api\.env
```

## 🔧 **Windows-Specific Commands**

### **File Copy Commands:**

```cmd
:: Frontend files (dari dist ke root)
xcopy /E /I /H /Y frontend-web\dist\* deploy-temp\

:: Backend files (ke folder api)
xcopy /E /I /H /Y backend-api\* deploy-temp\api\

:: Copy dengan exclude patterns
xcopy /E /I /H /Y /EXCLUDE:exclude.txt source\ dest\
```

### **Exclude File (exclude.txt):**

```
node_modules\
.git\
.env
*.log
storage\logs\
```

## 📁 **Struktur Deployment Windows**

```
deploy-temp\                    # Ready untuk upload
├── index.html                  # Vue.js frontend
├── assets\                     # CSS, JS, images
├── api\                        # Laravel backend
│   ├── app\
│   ├── config\
│   ├── database\
│   ├── public\
│   ├── .env                    # Production config
│   └── ...
├── deployment-files\           # Guides & configs
└── DEPLOYMENT-INFO.txt         # Deployment details
```

## 🌐 **Hostinger Git Integration**

### **Setup Auto-Deploy:**

1. **Login ke hPanel Hostinger**
2. **Website → Manage → Git**
3. **Create New Repository:**

   ```
   Repository URL: https://github.com/omanjaya/absensi
   Branch: main
   Install Path: public_html (kosong = root)
   ```

4. **Configure Build Commands:**

   ```bash
   # Build commands yang akan dijalankan di server
   cd frontend-web
   npm install
   npm run build:production
   cd ../backend-api
   composer install --no-dev --optimize-autoloader
   cd ..
   cp -r frontend-web/dist/* ./
   cp -r backend-api/* api/
   ```

5. **Setup Webhook** untuk auto-deploy saat push ke GitHub

## 🔄 **Development Workflow**

### **Daily Development:**

```cmd
:: 1. Pull latest changes
git pull origin main

:: 2. Make changes
:: Edit files...

:: 3. Test locally
scripts\dev.bat

:: 4. Commit changes
git add .
git commit -m "Feature: Add Excel import validation"

:: 5. Push to trigger auto-deploy
git push origin main
```

### **Deployment Workflow:**

```cmd
:: 1. Prepare deployment
scripts\deploy-git-hostinger.bat

:: 2a. Manual upload
:: Upload deploy-temp\ ke Hostinger File Manager

:: 2b. Or auto-deploy
:: Push akan trigger automatic deployment di Hostinger
```

## 🐛 **Troubleshooting Windows Issues**

### **Command Not Found:**

```cmd
:: 'cp' is not recognized
:: Solution: Use xcopy instead
xcopy /E /I /H /Y source\ dest\

:: 'npm' is not recognized
:: Solution: Install Node.js dan restart CMD
```

### **Permission Issues:**

```cmd
:: Run CMD as Administrator untuk file operations
:: Atau gunakan PowerShell:
powershell -command "Copy-Item -Recurse source dest"
```

### **Git Push Issues:**

```cmd
:: Setup Git credentials
git config --global user.name "Your Name"
git config --global user.email "your@email.com"

:: Use GitHub CLI (alternatif)
gh auth login
gh repo create omanjaya/absensi --public
```

### **Build Failures:**

```cmd
:: Clear caches
cd frontend-web
npm cache clean --force
rm -rf node_modules
npm install

cd ..\backend-api
composer clear-cache
rm -rf vendor
composer install
```

## 📊 **File Size Optimization**

### **Frontend Build:**

```cmd
:: Production build menghasilkan ~100KB optimized
npm run build:production

:: Check bundle size
npm run build:analyze
```

### **Backend Package:**

```cmd
:: Exclude dev dependencies (lebih kecil)
composer install --no-dev --optimize-autoloader

:: Remove unnecessary files
del /s /q storage\logs\*
del /s /q bootstrap\cache\*
```

## 🎯 **Best Practices Windows**

### **1. Use Batch Scripts:**

- ✅ `setup-git-repository.bat` untuk initial setup
- ✅ `deploy-git-hostinger.bat` untuk deployment
- ✅ `dev.bat` untuk development

### **2. Environment Management:**

```cmd
:: Development
copy .env.example .env

:: Production
copy dist-production\backend\.env deploy-temp\api\.env
```

### **3. Regular Backup:**

```cmd
:: Backup sebelum deployment
git tag v1.0.0
git push origin v1.0.0
```

## 🚀 **Quick Commands Reference**

```cmd
:: Setup repository pertama kali
scripts\setup-git-repository.bat

:: Daily development
scripts\dev.bat

:: Deploy to production
scripts\deploy-git-hostinger.bat

:: Check status
git status
git log --oneline -10

:: Emergency rollback
git revert HEAD
git push origin main
```

---

**💡 Tip:** Gunakan Windows batch scripts untuk automation yang konsisten dan mudah diingat!
