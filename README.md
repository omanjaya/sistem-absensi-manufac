# 🚀 Sistem Absensi Berbasis Pengenalan Wajah

Sistem absensi karyawan modern dengan teknologi face recognition, GPS validation, dan manajemen penggajian terintegrasi.

## 🏗️ Arsitektur

```
sistemabsensi/
├── 📂 backend-api/        # Laravel 11 REST API Backend
├── 📂 frontend-web/       # Vue 3 + TypeScript Frontend
├── 📂 face-server/        # Flask AI Face Recognition
├── 📂 deploy/             # Docker Deployment Files
├── 📂 docs/              # Documentation
├── 📂 scripts/           # Setup Scripts
└── 📄 README.md          # Project Overview
```

## ⚡ Quick Start

### Option 1: Automated Setup

```bash
# Windows
scripts\setup.bat

# Linux/Mac
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

### Option 2: Manual Setup

```bash
# 1. Backend API (Terminal 1)
cd backend-api
composer install
php artisan migrate:fresh --seed
php artisan serve --port=8000

# 2. AI Server (Terminal 2)
cd face-server
pip install -r requirements.txt
python app.py

# 3. Frontend (Terminal 3)
cd frontend-web
npm install
npm run dev
```

### Option 3: Docker

```bash
cd deploy
docker-compose up -d
```

## 🌐 Access Points

- **Frontend**: http://localhost:3000
- **API Backend**: http://localhost:8000
- **AI Server**: http://localhost:5000

## 🔑 Default Login

| Role     | Email                | Password |
| -------- | -------------------- | -------- |
| Admin    | admin@example.com    | password |
| Employee | employee@example.com | password |

## 🎯 Key Features

✅ **Face Recognition** - Real-time attendance with AI  
✅ **GPS Validation** - Location-based check-in/out  
✅ **Multi-Role System** - Admin & Employee dashboards  
✅ **Payroll Management** - Automated salary calculation  
✅ **Leave Management** - Request & approval workflow  
✅ **Analytics Dashboard** - Real-time statistics  
✅ **Excel Export** - Comprehensive reporting

## 🛠️ Tech Stack

- **Frontend**: Vue 3, TypeScript, Tailwind CSS
- **Backend**: Laravel 11, PostgreSQL, Redis
- **AI**: Flask, face_recognition, OpenCV
- **Deploy**: Docker, Nginx

## 📚 Documentation

- [`docs/QUICK-START.md`](docs/QUICK-START.md) - Detailed setup guide
- [`docs/RUN-SYSTEM.md`](docs/RUN-SYSTEM.md) - Running instructions
- [`docs/DEMO-RESULTS.md`](docs/DEMO-RESULTS.md) - Implementation results
- [`docs/STRUCTURE.md`](docs/STRUCTURE.md) - Project structure guide
- [`backend-api/README.md`](backend-api/README.md) - API documentation
- [`frontend-web/README.md`](frontend-web/README.md) - Frontend guide
- [`face-server/README.md`](face-server/README.md) - AI server docs

## 🔧 System Requirements

- **PHP**: 8.2+
- **Node.js**: 18+
- **Python**: 3.8+
- **PostgreSQL**: 13+
- **Composer**: 2.5+
- **Docker**: 24+ (optional)

## 🎮 Demo Features

1. **Employee Registration** - Register face for recognition
2. **Clock In/Out** - Face-based attendance tracking
3. **GPS Validation** - 100m radius office location check
4. **Dashboard Analytics** - Real-time performance metrics
5. **Leave Requests** - Submit and approve time-off
6. **Salary Reports** - Generate monthly payroll

## 🚀 Production Deployment

For production environments:

```bash
# Using Docker (Recommended)
cd deploy
docker-compose -f docker-compose.prod.yml up -d

# Manual Production Setup
# See docs/QUICK-START.md for detailed instructions
```

## 🤝 Support

- **Issues**: Create GitHub issue
- **Documentation**: Check `/docs` folder
- **Setup Help**: Follow `/docs/QUICK-START.md`

---

**Ready to transform your attendance management! 🎯**
"# Test Auto Deploy - $(date)" 
