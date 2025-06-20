# Panduan Deployment Sistem Absensi ke Hostinger (Windows CMD)

## 🖥️ **Persyaratan Windows & Tools**

### **1. Tools yang Dibutuhkan:**

- ✅ **Windows Command Prompt (CMD)**
- ✅ **Web Browser** untuk akses hPanel Hostinger
- ✅ **WinRAR/7-Zip** untuk zip files
- ✅ **Text Editor** (Notepad++ recommended)

### **2. Paket Hosting Hostinger:**

- **Business Web Hosting** atau **Cloud Hosting** (minimum)
- PHP 8.2 atau lebih tinggi
- MySQL database support
- SSL Certificate (Let's Encrypt tersedia gratis)

## 🚀 **Langkah-langkah Deployment**

### **Langkah 1: Persiapan Database di Hostinger**

1. **Login ke hPanel Hostinger**

   - Buka: https://hpanel.hostinger.com
   - Login dengan akun Anda

2. **Buat Database MySQL**

   ```
   Database Name: u976886556_absensi
   Username: u976886556_omanjaya
   Password: @Oman180010216
   Host: localhost
   Port: 3306
   ```

3. **Aktifkan SSL Certificate**
   - Pilih domain manufac.id
   - Websites → Manage → SSL/TLS
   - Aktifkan "Force HTTPS"

### **Langkah 2: Build Production Files (Windows CMD)**

1. **Buka Command Prompt as Administrator**

   ```cmd
   # Navigate ke project directory
   cd "D:\project nich\sistemabsensi"
   ```

2. **Build Frontend Vue.js**

   ```cmd
   cd frontend-web

   # Copy production environment
   copy .env.production .env

   # Install dependencies
   npm install

   # Build for production
   npm run build:production

   cd ..
   ```

3. **Prepare Backend Laravel**

   ```cmd
   cd backend-api

   # Copy production environment with real credentials
   copy .env.production .env

   # Install production dependencies
   composer install --no-dev --optimize-autoloader

   cd ..
   ```

4. **Create Production Package**

   ```cmd
   # Create distribution directory
   if not exist "dist-production" mkdir dist-production

   # Copy frontend build
   xcopy /e /i /y frontend-web\dist dist-production\frontend

   # Copy backend files (essential only)
   mkdir dist-production\backend
   xcopy /e /i /y backend-api\app dist-production\backend\app
   xcopy /e /i /y backend-api\bootstrap dist-production\backend\bootstrap
   xcopy /e /i /y backend-api\config dist-production\backend\config
   xcopy /e /i /y backend-api\database dist-production\backend\database
   xcopy /e /i /y backend-api\public dist-production\backend\public
   xcopy /e /i /y backend-api\routes dist-production\backend\routes
   xcopy /e /i /y backend-api\storage dist-production\backend\storage
   xcopy /e /i /y backend-api\vendor dist-production\backend\vendor

   # Copy essential files
   copy backend-api\artisan dist-production\backend\
   copy backend-api\composer.json dist-production\backend\
   copy backend-api\composer.lock dist-production\backend\
   copy backend-api\.env dist-production\backend\
   ```

### **Langkah 3: Upload ke Hostinger (File Manager)**

#### **A. Upload Frontend**

1. **Login ke hPanel → File Manager**
2. **Navigate ke domains/manufac.id/public_html/**
3. **Upload semua file dari `dist-production\frontend\`**
   - Drag & drop atau klik Upload
   - Pastikan `index.html` ada di root public_html

#### **B. Upload Backend**

1. **Buat folder `api` di dalam public_html/**
2. **Upload semua file dari `dist-production\backend\`**
   - Upload ke folder `public_html/api/`
   - Pastikan struktur: `public_html/api/app/`, `public_html/api/config/`, dll

#### **C. Setup .htaccess Files**

1. **Frontend .htaccess** (`public_html/.htaccess`):

   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On

       # API Routes
       RewriteRule ^api/(.*)$ /api/index.php [L,QSA]

       # Vue.js SPA
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule ^(.*)$ /index.html [L,QSA]
   </IfModule>

   # Security Headers
   <IfModule mod_headers.c>
       Header always set X-Frame-Options "SAMEORIGIN"
       Header always set X-Content-Type-Options "nosniff"
       Header always set X-XSS-Protection "1; mode=block"
   </IfModule>
   ```

2. **Backend .htaccess** (`public_html/api/.htaccess`):

   ```apache
   <IfModule mod_rewrite.c>
       RewriteEngine On

       # Handle Authorization Header
       RewriteCond %{HTTP:Authorization} .
       RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

       # Send Requests To Front Controller
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteRule ^ index.php [L]
   </IfModule>

   # CORS Headers
   <IfModule mod_headers.c>
       Header always set Access-Control-Allow-Origin "https://manufac.id"
       Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
       Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
       Header always set Access-Control-Allow-Credentials "true"
   </IfModule>
   ```

### **Langkah 4: Setup Database**

#### **A. Update .env Production (Via File Manager)**

1. **Edit file `public_html/api/.env`**
2. **Pastikan konfigurasi database benar:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=u976886556_absensi
   DB_USERNAME=u976886556_omanjaya
   DB_PASSWORD=@Oman180010216
   ```

#### **B. Generate APP_KEY**

1. **Via Terminal di hPanel (jika tersedia)**:

   ```bash
   cd public_html/api
   php artisan key:generate
   ```

2. **Via Local CMD (alternative)**:
   ```cmd
   # Di local project
   cd backend-api
   php artisan key:generate
   # Copy key yang di-generate ke .env production
   ```

#### **C. Run Database Migrations**

1. **Via hPanel Terminal**:

   ```bash
   cd public_html/api
   php artisan migrate --force
   php artisan db:seed --force
   ```

2. **Via File Manager (alternative)**:
   - Upload file SQL migration manual
   - Import via phpMyAdmin di hPanel

### **Langkah 5: Set File Permissions (Via File Manager)**

1. **Set permission untuk storage:**

   ```
   public_html/api/storage/ → 755
   public_html/api/bootstrap/cache/ → 755
   ```

2. **Klik kanan folder → Permissions → 755**

### **Langkah 6: Testing**

#### **A. Test URLs:**

- **Frontend**: https://manufac.id/
- **API Health**: https://manufac.id/api/
- **Login Page**: https://manufac.id/login

#### **B. Test Login:**

```
Admin Login:
Email: admin@absensi.com
Password: password

Employee Login:
Email: john@absensi.com
Password: password
```

## 🔧 **Troubleshooting Windows CMD**

### **1. Error "npm not found"**

```cmd
# Install Node.js dari https://nodejs.org
# Restart CMD setelah install
where npm
```

### **2. Error "composer not found"**

```cmd
# Install Composer dari https://getcomposer.org
# Restart CMD setelah install
where composer
```

### **3. Error "xcopy access denied"**

```cmd
# Run CMD as Administrator
# Atau gunakan robocopy:
robocopy frontend-web\dist dist-production\frontend /E
```

### **4. File Upload Failed**

- Zip folder dulu sebelum upload
- Upload via chunks (max 100MB per file)
- Gunakan FTP client seperti FileZilla

## 📦 **Alternative: Zip Method**

Jika upload langsung susah, gunakan metode zip:

```cmd
# Create zip files
powershell Compress-Archive -Path "dist-production\frontend\*" -DestinationPath "frontend.zip"
powershell Compress-Archive -Path "dist-production\backend\*" -DestinationPath "backend.zip"
```

Upload zip files, lalu extract di File Manager.

## 🎯 **Final Checklist**

- ✅ Database MySQL dibuat di Hostinger
- ✅ Frontend files uploaded ke public_html/
- ✅ Backend files uploaded ke public_html/api/
- ✅ .htaccess files configured
- ✅ .env updated dengan database credentials
- ✅ APP_KEY generated
- ✅ Migrations run successfully
- ✅ File permissions set (755)
- ✅ SSL Certificate active
- ✅ All URLs tested

## 🚀 **Go Live!**

Setelah semua checklist selesai:

1. **Test https://manufac.id/**
2. **Login dengan admin account**
3. **Test semua fitur (attendance, employees, etc)**
4. **Setup real employee data**

**Sistem Absensi Manufac.id siap production! 🎉**

---

**Support**: Jika ada masalah, cek error logs di hPanel → Error Logs atau contact Hostinger support 24/7.
