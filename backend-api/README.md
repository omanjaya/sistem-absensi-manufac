# 🔥 Sistem Absensi Wajah - Laravel Backend API

Backend REST API untuk sistem absensi berbasis pengenalan wajah menggunakan Laravel 11, PostgreSQL, dan integrasi Flask AI.

## 🚀 Fitur Utama

- ✅ **Autentikasi Multi-Role** - Admin & Employee dengan Laravel Sanctum
- 🎯 **Face Recognition** - Integrasi dengan Flask AI Server
- 📍 **GPS Validation** - Validasi lokasi absensi dengan Haversine formula
- 📊 **Dashboard Analytics** - Statistik kehadiran dan performa
- 📄 **Export Excel** - Laporan absensi dan penggajian
- 🔐 **Role-based Access** - Middleware authorization
- 📱 **RESTful API** - Struktur API yang konsisten

## 🗂️ Struktur Database

### Tables:

- `users` - Data karyawan dan admin
- `attendances` - Record absensi dengan foto dan GPS
- `leaves` - Permohonan izin/cuti
- `salaries` - Data penggajian
- `sessions` - Laravel session management

## ⚙️ Installation

### Prerequisites

- PHP 8.2+
- Composer 2.5+
- PostgreSQL 13+
- Redis (optional)

### 1. Install Dependencies

```bash
cd backend-api
composer install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Configuration

Edit `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=absensi_db
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Face Recognition Service
FACE_SERVICE_URL=http://localhost:5000
FACE_SERVICE_TIMEOUT=30

# Office Location (Jakarta)
OFFICE_LATITUDE=-6.2088
OFFICE_LONGITUDE=106.8456
OFFICE_RADIUS=100

# CORS for Vue Frontend
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
```

### 4. Database Migration & Seeding

```bash
php artisan migrate
php artisan db:seed
```

### 5. Storage Setup

```bash
php artisan storage:link
```

### 6. Start Development Server

```bash
php artisan serve --port=8000
```

## 🔑 Default Login Credentials

| Role     | Email                | Password |
| -------- | -------------------- | -------- |
| Admin    | admin@example.com    | password |
| Employee | employee@example.com | password |

## 📚 API Endpoints

### Authentication

```
POST   /api/auth/login          # Login dengan email/password
POST   /api/auth/register       # Register user baru (admin only)
GET    /api/auth/user           # Get authenticated user
PUT    /api/auth/profile        # Update profile
PUT    /api/auth/password       # Change password
POST   /api/auth/logout         # Logout
```

### Attendance Management

```
GET    /api/attendances         # List attendance records
POST   /api/attendances         # Clock in/out dengan face recognition
GET    /api/attendances/today   # Today's attendance
GET    /api/attendances/export  # Export attendance data
GET    /api/attendances/{id}    # Show specific attendance
PUT    /api/attendances/{id}    # Update attendance (admin only)
DELETE /api/attendances/{id}    # Delete attendance (admin only)
```

### Face Recognition

```
POST   /api/face/register       # Register new face encoding
POST   /api/face/recognize      # Recognize face for attendance
GET    /api/face/status/{user}  # Check face registration status
```

### Leave Management

```
GET    /api/leaves              # List leave requests
POST   /api/leaves              # Submit leave request
GET    /api/leaves/{id}         # Show leave details
PUT    /api/leaves/{id}         # Update leave request
DELETE /api/leaves/{id}         # Delete leave request
POST   /api/leaves/{id}/approve # Approve leave (admin only)
POST   /api/leaves/{id}/reject  # Reject leave (admin only)
```

### Employee Management (Admin Only)

```
GET    /api/users               # List all employees
POST   /api/users               # Create new employee
GET    /api/users/{id}          # Show employee details
PUT    /api/users/{id}          # Update employee
DELETE /api/users/{id}          # Delete employee
```

### Payroll Management (Admin Only)

```
GET    /api/salaries            # List salary records
POST   /api/salaries/generate   # Generate monthly salaries
GET    /api/salaries/export     # Export salary data
POST   /api/salaries/{id}/finalize # Finalize salary
POST   /api/salaries/{id}/mark-paid # Mark as paid
```

### Dashboard & Reports

```
GET    /api/dashboard/stats              # Dashboard statistics
GET    /api/dashboard/recent-attendances # Recent attendance
GET    /api/reports/attendance-summary   # Attendance summary
GET    /api/reports/monthly-stats        # Monthly statistics
GET    /api/reports/employee-performance # Employee performance
```

### Health Check

```
GET    /api/health              # Service health status
```

## 🧪 Testing

### Run Unit Tests

```bash
php artisan test
```

### API Testing dengan Postman

Import collection: `docs/Absensi-API.postman_collection.json`

## 🐳 Docker Support

### Build & Run

```bash
cd deploy/
docker-compose up -d
```

### Services:

- Laravel API: `http://localhost:8000`
- PostgreSQL: `localhost:5432`
- Redis: `localhost:6379`

## 🔧 Configuration

### Face Recognition Service

Pastikan Flask AI Server running di `localhost:5000`:

```bash
# Test connection
curl http://localhost:5000/health
```

### Office Location Setup

Update koordinat kantor di `.env`:

```env
OFFICE_LATITUDE=-6.2088    # Jakarta coordinate
OFFICE_LONGITUDE=106.8456  # Jakarta coordinate
OFFICE_RADIUS=100          # 100 meters radius
```

### Work Schedule Configuration

```env
WORK_START_TIME=08:00
WORK_END_TIME=17:00
LATE_THRESHOLD_MINUTES=15
EARLY_CHECKOUT_THRESHOLD_MINUTES=30
```

## 📊 Monitoring & Logs

### Application Logs

```bash
tail -f storage/logs/laravel.log
```

### Face Recognition Logs

```bash
grep "Face recognition" storage/logs/laravel.log
```

### Performance Monitoring

```bash
php artisan route:list --verbose
php artisan queue:work --verbose
```

## 🔒 Security Features

- ✅ CSRF Protection
- ✅ SQL Injection Prevention
- ✅ XSS Protection
- ✅ Rate Limiting
- ✅ Input Validation
- ✅ File Upload Security
- ✅ JWT Token Management

## 📈 Performance Optimization

### Database Indexing

- User ID + Date indexes on attendances
- Status indexes for fast filtering
- Composite indexes for reporting

### Caching Strategy

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Jobs

```bash
php artisan queue:work --queue=default,face-recognition
```

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Error**

```bash
# Check PostgreSQL service
sudo systemctl status postgresql

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

**2. Face Recognition Service Unavailable**

```bash
# Check Flask server
curl http://localhost:5000/health

# Check Laravel logs
grep "Face recognition" storage/logs/laravel.log
```

**3. Storage Permission Issues**

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

**4. CORS Issues**

```bash
# Clear config cache
php artisan config:clear

# Check sanctum configuration
php artisan config:show sanctum
```

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up SSL certificates
- [ ] Configure Redis for caching
- [ ] Set up backup strategy
- [ ] Configure monitoring alerts
- [ ] Optimize autoloader: `composer install --optimize-autoloader --no-dev`

### Environment Variables for Production

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## 📞 Support

- **Documentation**: [Wiki](docs/)
- **API Reference**: [Postman Collection](docs/api.postman_collection.json)
- **Issue Tracking**: [GitHub Issues](issues/)

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Dibuat dengan ❤️ menggunakan Laravel 11 & PostgreSQL**
