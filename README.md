# Sistem Absensi Manufac.id

Modern attendance management system dengan fitur face recognition, Excel import/export, dan real-time dashboard.

## 🌐 Production URLs

- **Website**: https://manufac.id
- **API**: https://manufac.id/api/
- **Repository**: https://github.com/omanjaya/sistem-absensi-manufac

## 🚀 Features

- ✅ **Employee Management** - CRUD dan Excel import/export
- ✅ **Face Recognition** - Attendance via camera
- ✅ **Real-time Dashboard** - Analytics dan reporting
- ✅ **Salary Calculation** - Automated payroll system
- ✅ **Mobile Responsive** - Works on all devices
- ✅ **Role-based Access** - Admin, Manager, Employee roles

## 🛠 Tech Stack

### Frontend

- **Vue.js 3** - Progressive web framework
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Fast build tool

### Backend

- **Laravel 11** - PHP web framework
- **MySQL** - Database (Hostinger compatible)
- **Laravel Sanctum** - API authentication
- **Maatwebsite Excel** - Excel import/export

### Face Recognition

- **Python Flask** - Face recognition service
- **OpenCV** - Computer vision library

## 📦 Project Structure

```
sistem-absensi-manufac/
├── frontend-web/           # Vue.js frontend
├── backend-api/           # Laravel backend
├── face-server/           # Python face recognition
├── scripts/               # Development & deployment scripts
├── docs/                  # Documentation
├── deploy/               # Production configuration
├── .htaccess             # Frontend routing
├── build.sh              # Production build script
└── README.md             # This file
```

## 🏃‍♂️ Quick Start

### Local Development

```bash
# 1. Clone repository
git clone https://github.com/omanjaya/sistem-absensi-manufac.git
cd sistem-absensi-manufac

# 2. Start all services
scripts\dev.bat

# 3. Access applications
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000
# Face Server: http://localhost:5000
```

### Production Deployment

**Option 1: Auto-Deploy (Recommended)**

1. Setup Hostinger Git integration
2. Configure webhook: `https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf`
3. Push to main branch → auto-deploy

**Option 2: Manual Deploy**

```bash
# Build deployment package
scripts\deploy-git-simple.bat

# Upload deploy-temp/ to Hostinger public_html
```

## 🔧 Development Scripts

| Script                             | Description                     |
| ---------------------------------- | ------------------------------- |
| `scripts\dev.bat`                  | Start local development servers |
| `scripts\setup-git-repository.bat` | Initialize Git repository       |
| `scripts\deploy-git-simple.bat`    | Build production package        |
| `scripts\build-production.bat`     | Build for production            |
| `scripts\fix-permissions.bat`      | Fix deployment issues           |

## 🌍 Deployment Configuration

### Database (MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u976886556_absensi
DB_USERNAME=u976886556_omanjaya
DB_PASSWORD=@Oman180010216
```

### Environment

```env
APP_URL=https://manufac.id
APP_ENV=production
APP_DEBUG=false
```

## 🔀 Git Workflow

### Development

```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes
git add .
git commit -m "Add new feature"
git push origin feature/new-feature

# Create Pull Request
# Merge to main → Auto-deploy
```

### Auto-Deploy

1. **Push to main** → GitHub webhook triggers
2. **Hostinger pulls** latest code
3. **Build script runs** → `build.sh`
4. **Website updates** → https://manufac.id

## 🧪 Testing

### Frontend

```bash
cd frontend-web
npm run test
```

### Backend

```bash
cd backend-api
php artisan test
```

## 🔐 Security Features

- ✅ **CSRF Protection** - Laravel built-in
- ✅ **XSS Protection** - Vue.js sanitization
- ✅ **SQL Injection Prevention** - Eloquent ORM
- ✅ **Rate Limiting** - API throttling
- ✅ **HTTPS Enforced** - Production only
- ✅ **File Upload Validation** - Excel import security

## 📱 Default Accounts

```
Admin:
Email: admin@absensi.com
Password: password

Manager:
Email: manager@absensi.com
Password: password

Employee:
Email: john@absensi.com
Password: password
```

## 🚨 Troubleshooting

### 403 Forbidden Error

```bash
# Check file permissions
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache

# Verify .htaccess files exist
ls -la .htaccess api/.htaccess

# Run fix script
scripts\fix-permissions.bat
```

### Build Failures

```bash
# Clear caches
cd frontend-web && npm cache clean --force
cd backend-api && composer clear-cache

# Rebuild
scripts\build-production.bat
```

### Database Issues

```bash
# Run migrations
cd backend-api
php artisan migrate --force
php artisan db:seed
```

## 📞 Support

- **Repository Issues**: https://github.com/omanjaya/sistem-absensi-manufac/issues
- **Documentation**: See `docs/` directory
- **Hostinger Support**: hPanel → Support

## 🏆 Production Status

- ✅ **Repository**: Set up and synchronized
- ✅ **Auto-Deploy**: Configured and working
- ✅ **Database**: MySQL ready for production
- ✅ **SSL**: HTTPS enforced
- ✅ **Monitoring**: Error tracking enabled
- ✅ **Backup**: Git-based version control

---

**Sistema Absensi Manufac.id - Ready for Production! 🚀**

Built with ❤️ for modern workforce management.
