# Panduan Migrasi Database: PostgreSQL → MySQL

## 🔄 **Perubahan Database untuk Deployment**

### **Situasi:**

- **Local Development**: PostgreSQL
- **Hostinger Shared**: MySQL/MariaDB only
- **Perlu migrasi** untuk deployment

## 📋 **Langkah Migrasi:**

### **1. Export Data dari PostgreSQL (Local)**

```bash
# Export struktur dan data
pg_dump -h 127.0.0.1 -U postgres -d absensi_db --no-owner --no-privileges > backup_postgres.sql

# Atau export data saja (CSV)
psql -h 127.0.0.1 -U postgres -d absensi_db -c "\copy users TO 'users.csv' CSV HEADER;"
psql -h 127.0.0.1 -U postgres -d absensi_db -c "\copy attendances TO 'attendances.csv' CSV HEADER;"
# ... untuk tabel lainnya
```

### **2. Setup MySQL di Hostinger**

1. **Login ke hPanel Hostinger**
2. **Buat Database MySQL:**
   ```
   Database Name: u[userid]_absensi
   Username: u[userid]_admin
   Password: [generate strong password]
   Host: localhost
   Port: 3306
   ```

### **3. Update Konfigurasi Laravel**

```bash
# Update .env production
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_absensi
DB_USERNAME=u123456789_admin
DB_PASSWORD=YourActualPassword
```

### **4. Test Migrasi Local (Optional)**

```bash
# Install MySQL local untuk testing
# Update .env untuk MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_mysql_test
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate:fresh --seed
```

### **5. Deploy ke Hostinger**

```bash
# Upload files ke Hostinger
# Run migrations di server
cd public_html/api
php artisan migrate --force
php artisan db:seed --force
```

## 🔧 **Perbedaan PostgreSQL vs MySQL:**

### **Data Types yang Berubah:**

| PostgreSQL  | MySQL        | Status        |
| ----------- | ------------ | ------------- |
| `jsonb`     | `json`       | ✅ Compatible |
| `timestamp` | `timestamp`  | ✅ Compatible |
| `text`      | `longtext`   | ✅ Compatible |
| `boolean`   | `tinyint(1)` | ✅ Compatible |

### **Laravel Migrations Sudah Compatible:**

- ✅ Schema Builder Laravel handle differences
- ✅ Migrations work for both databases
- ✅ No code changes needed

## 📊 **Data Migration Options:**

### **Option 1: Fresh Start (Recommended)**

```bash
# Start with fresh database
php artisan migrate --force
php artisan db:seed --force

# Import manual data jika ada
# Via Excel import feature yang sudah ada
```

### **Option 2: Data Transfer**

```bash
# Export dari PostgreSQL
pg_dump -h 127.0.0.1 -U postgres absensi_db --data-only --inserts > data.sql

# Convert & import to MySQL
# (Manual editing required untuk SQL syntax differences)
```

### **Option 3: Excel Import**

```bash
# Gunakan fitur Excel import yang sudah ada
# Export data dari PostgreSQL ke Excel
# Import via web interface
```

## ⚠️ **Hal Penting:**

### **1. Backup Data Local:**

```bash
# Selalu backup data PostgreSQL local
pg_dump -h 127.0.0.1 -U postgres absensi_db > backup_$(date +%Y%m%d).sql
```

### **2. Test di Local MySQL:**

```bash
# Test migrasi di local dulu
# Pastikan semua fitur berjalan
# Baru deploy ke production
```

### **3. Database Credentials:**

```bash
# Simpan credentials Hostinger dengan aman
# Format: u[userid]_nama
# Contoh: u123456789_absensi
```

## 🎯 **Rekomendasi:**

### **Untuk Deployment Pertama:**

1. ✅ **Fresh start** dengan MySQL di Hostinger
2. ✅ Run migrations & seeders
3. ✅ Test semua fitur
4. ✅ Import data manual via Excel jika diperlukan

### **Untuk Development Lanjutan:**

1. 🔄 Keep PostgreSQL untuk local development
2. 🔄 Test di MySQL sebelum deploy
3. 🔄 Maintain compatibility untuk both databases

## 🚀 **Next Steps:**

1. **Setup database MySQL di Hostinger**
2. **Update .env.production** dengan credentials sebenarnya
3. **Deploy & run migrations**
4. **Test semua functionality**
5. **Import data jika diperlukan**

---

**Note**: Laravel Schema Builder secara otomatis handle perbedaan antara PostgreSQL dan MySQL, jadi migrations Anda akan berjalan tanpa masalah! 🎉
