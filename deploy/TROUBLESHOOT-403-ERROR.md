# 🚨 Fix 403 Forbidden Error - Hostinger Deployment

## ❌ **Error yang Anda Alami:**

```
403 Forbidden
Access to this resource on the server is denied!
```

## 🎯 **Kemungkinan Penyebab:**

### **1. File Permissions Issue**

- Laravel storage folder tidak memiliki write permissions
- Bootstrap cache folder tidak accessible
- .htaccess file tidak terbaca

### **2. Missing Index File**

- index.html tidak di root directory
- Laravel public/index.php tidak accessible

### **3. Web Server Configuration**

- .htaccess rules tidak compatible
- Directory listing disabled

## 🔧 **Solusi Step-by-Step:**

### **Step 1: Check Hostinger File Manager**

1. **Login** ke Hostinger hPanel
2. **Go to** File Manager → public_html (manufac.id)
3. **Verify** struktur file:

```
public_html/
├── index.html          ← Harus ada (Vue.js frontend)
├── assets/             ← CSS, JS files
├── api/                ← Laravel backend
│   ├── public/
│   │   └── index.php   ← Laravel entry point
│   ├── storage/        ← Need 755 permissions
│   ├── bootstrap/
│   │   └── cache/      ← Need 755 permissions
│   └── .htaccess       ← Laravel routing
└── .htaccess           ← Frontend routing
```

### **Step 2: Fix File Permissions**

**Via Hostinger File Manager:**

1. **Right-click** pada folder `api/storage` → Permissions
2. **Set to**: 755 (rwxr-xr-x)
3. **Apply** to all subdirectories

**Via Hostinger Terminal (if available):**

```bash
cd public_html
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache
chmod 644 api/.htaccess
chmod 644 .htaccess
```

### **Step 3: Check Missing Files**

**If index.html missing:**

1. **Check** if build process completed
2. **Verify** frontend build di `frontend-web/dist/`
3. **Re-run** deployment jika perlu

**If Laravel files missing:**

1. **Check** api/ folder structure
2. **Verify** composer install completed
3. **Check** .env file exists di api/.env

### **Step 4: Fix .htaccess Issues**

**Create/Fix Root .htaccess:**

```apache
# Frontend .htaccess untuk Vue.js routing
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle API requests
    RewriteRule ^api/(.*)$ api/public/$1 [L]

    # Handle Vue.js routing
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Disable directory browsing
Options -Indexes
```

**Create/Fix API .htaccess:**

```apache
# Laravel .htaccess
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### **Step 5: Database Configuration**

**Check api/.env file:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://manufac.id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u976886556_absensi
DB_USERNAME=u976886556_omanjaya
DB_PASSWORD=@Oman180010216
```

**Run Database Migrations:**

```bash
cd public_html/api
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

## 🚀 **Quick Fix Script**

**Create** file `fix-permissions.sh` di root:

```bash
#!/bin/bash
echo "Fixing 403 Forbidden error..."

# Set correct permissions
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache
chmod 644 api/.htaccess
chmod 644 .htaccess

# Clear Laravel cache
cd api
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Recreate cache
php artisan config:cache
php artisan route:cache

echo "Permissions fixed! Check https://manufac.id"
```

## 🔍 **Diagnostic Steps**

### **1. Check What's Loading:**

```bash
# Test homepage
curl -I https://manufac.id

# Test API
curl -I https://manufac.id/api/

# Test specific API endpoint
curl https://manufac.id/api/health
```

### **2. Check Hostinger Error Logs:**

1. **hPanel** → Website → Error Logs
2. **Look for** recent 403 errors
3. **Check** detailed error messages

### **3. Verify Build Process:**

1. **hPanel** → Git → Deployments
2. **Check** last deployment logs
3. **Verify** build.sh completed successfully

## 🎯 **Most Common Solutions:**

### **Solution 1: Missing index.html**

```bash
# If frontend not built properly
cd frontend-web
npm run build:production
cp -r dist/* ../
```

### **Solution 2: Laravel Permissions**

```bash
# Fix Laravel permissions
chmod -R 755 api/storage api/bootstrap/cache
chown -R www-data:www-data api/storage api/bootstrap/cache
```

### **Solution 3: .htaccess Issues**

- **Update** .htaccess files with correct syntax
- **Test** without .htaccess first
- **Enable** mod_rewrite di web server

### **Solution 4: Re-deploy Clean**

```bash
# Trigger clean deployment
git commit --allow-empty -m "Fix: Force re-deployment for 403 error"
git push origin main
```

## 📞 **Alternative Solutions:**

### **Option 1: Manual Upload**

Jika auto-deploy bermasalah:

1. **Use** manual upload dari `deploy-temp.zip`
2. **Extract** ke public_html/
3. **Set** permissions manually

### **Option 2: Hostinger Support**

Jika masih error:

1. **Contact** Hostinger support
2. **Mention** 403 error after Git deployment
3. **Ask** mereka check web server config

## ✅ **Expected Working State:**

Setelah fix, Anda should see:

- ✅ **https://manufac.id/** → Vue.js frontend loads
- ✅ **https://manufac.id/api/** → Laravel API responds
- ✅ **No 403 errors** in browser or logs

---

## 🚀 **Quick Action Plan:**

1. **Check** File Manager untuk missing files
2. **Fix** permissions: 755 untuk storage/cache
3. **Update** .htaccess files
4. **Test** https://manufac.id/
5. **Contact** Hostinger jika masih error

**Most likely fix: File permissions + missing .htaccess config!** 🔧
