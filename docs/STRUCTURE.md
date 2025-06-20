# 📁 Struktur Project - Sistem Absensi Wajah

## 🏗️ Organisasi Direktori

```
sistemabsensi/
├── 📂 backend-api/           # Laravel 11 REST API Backend
│   ├── app/
│   │   ├── Http/Controllers/API/   # API Controllers (7 files)
│   │   ├── Models/                 # Eloquent Models (4 files)
│   │   ├── Services/               # Business Logic Services
│   │   ├── Exports/                # Excel Export Classes
│   │   └── Http/Middleware/        # Custom Middleware
│   ├── database/
│   │   ├── migrations/             # Database Schema (4 migrations)
│   │   └── seeders/                # Default Data Seeding
│   ├── config/                     # Laravel Configuration
│   ├── routes/                     # API Routes Definition
│   ├── storage/                    # File Storage & Logs
│   ├── public/                     # Web Entry Point
│   ├── artisan                     # Laravel CLI Tool
│   ├── composer.json               # PHP Dependencies
│   └── .env.example                # Environment Template
│
├── 📂 frontend-web/          # Vue 3 + TypeScript Frontend
│   ├── src/
│   │   ├── components/             # Reusable Vue Components
│   │   ├── composables/            # Vue Composition Functions
│   │   ├── layouts/                # Page Layout Templates
│   │   ├── views/                  # Page Components
│   │   ├── router/                 # Vue Router Configuration
│   │   ├── services/               # API Integration Layer
│   │   ├── stores/                 # Pinia State Management
│   │   ├── types/                  # TypeScript Type Definitions
│   │   └── utils/                  # Utility Functions
│   ├── public/                     # Static Assets
│   ├── package.json                # NPM Dependencies
│   ├── vite.config.ts              # Vite Build Configuration
│   ├── tailwind.config.js          # Tailwind CSS Config
│   └── tsconfig.json               # TypeScript Configuration
│
├── 📂 face-server/           # Flask AI Face Recognition
│   ├── app.py                      # Main Flask Application
│   ├── requirements.txt            # Python Dependencies
│   ├── face_data/                  # Face Encodings Storage
│   ├── .env.example                # Environment Template
│   └── README.md                   # AI Server Documentation
│
├── 📂 deploy/                # Docker Deployment Configuration
│   ├── docker-compose.yml          # Multi-service Orchestration
│   ├── Dockerfile.laravel          # Laravel Container Build
│   ├── Dockerfile.flask            # Flask Container Build
│   ├── Dockerfile.vue              # Vue Container Build
│   ├── nginx/                      # Nginx Configuration
│   └── README.md                   # Deployment Guide
│
├── 📂 docs/                  # Project Documentation
│   ├── QUICK-START.md              # Setup Instructions
│   ├── RUN-SYSTEM.md               # Running Guide
│   ├── DEMO-RESULTS.md             # Implementation Results
│   ├── STRUCTURE.md                # This file
│   └── API-DOCS.md                 # API Documentation
│
├── 📂 scripts/               # Automation Scripts
│   ├── setup.bat                   # Windows Setup Script
│   ├── setup.sh                    # Linux/Mac Setup Script
│   └── deploy.sh                   # Production Deployment
│
├── 📄 README.md              # Main Project Overview
├── 📄 .gitignore             # Git Ignore Rules
└── 📄 cursor_prompts.md      # Development Specifications
```

## 🔧 Komponen Utama

### 🎯 Backend API (Laravel 11)

**File Penting:**

- `artisan` - Laravel command line interface
- `composer.json` - PHP package management
- `.env.example` - Environment configuration template
- `routes/api.php` - API endpoint definitions (132 routes)

**Controllers (app/Http/Controllers/API/):**

- `AuthController.php` - Authentication & user management
- `AttendanceController.php` - Attendance tracking & face recognition
- `UserController.php` - Employee CRUD operations
- `LeaveController.php` - Leave request management
- `SalaryController.php` - Payroll processing
- `DashboardController.php` - Analytics & reporting
- `FaceRecognitionController.php` - Face AI integration

**Models (app/Models/):**

- `User.php` - Employee/admin user model
- `Attendance.php` - Attendance record model
- `Leave.php` - Leave request model
- `Salary.php` - Salary calculation model

**Database (database/):**

- `migrations/` - Database schema definitions
- `seeders/` - Default data population

### 🎨 Frontend (Vue 3)

**Core Files:**

- `package.json` - NPM dependencies
- `vite.config.ts` - Build tool configuration
- `tailwind.config.js` - CSS framework setup
- `tsconfig.json` - TypeScript compiler settings

**Source Structure (src/):**

- `App.vue` - Root application component
- `main.ts` - Application entry point
- `router/index.ts` - Route definitions
- `stores/auth.ts` - Authentication state management

**Key Views:**

- `Login.vue` - Authentication interface
- `Dashboard.vue` - Main dashboard
- `Attendance.vue` - Face recognition attendance

**Composables:**

- `useCamera.ts` - Camera integration
- `useGeolocation.ts` - GPS validation

### 🤖 AI Server (Flask)

**Core Components:**

- `app.py` - Main Flask application (455 lines)
- `requirements.txt` - Python package dependencies
- `face_data/` - Face encoding storage directory

**API Endpoints:**

- `/register-face` - Register new face encoding
- `/recognize` - Face recognition processing
- `/status/{user_id}` - Check registration status
- `/health` - Service health check

### 🐳 Deployment (Docker)

**Configuration Files:**

- `docker-compose.yml` - Multi-container orchestration
- `Dockerfile.laravel` - PHP-FPM container
- `Dockerfile.flask` - Python AI container
- `Dockerfile.vue` - Node.js frontend container

**Services:**

- PostgreSQL 15 database
- Redis cache server
- Nginx reverse proxy
- Laravel API backend
- Flask AI server
- Vue frontend

## 📊 Statistik Project

| Component     | Files   | Lines of Code | Language           |
| ------------- | ------- | ------------- | ------------------ |
| Backend API   | 25+     | 3,500+        | PHP/Laravel        |
| Frontend      | 20+     | 2,000+        | Vue/TypeScript     |
| AI Server     | 4       | 500+          | Python/Flask       |
| Docker        | 8       | 400+          | YAML/Dockerfile    |
| Documentation | 10+     | 1,500+        | Markdown           |
| **Total**     | **65+** | **7,900+**    | **Multi-language** |

## 🔄 Data Flow

```
┌─────────────┐    HTTP/JSON    ┌─────────────┐    HTTP/JSON    ┌─────────────┐
│   Vue.js    │◄──────────────►│   Laravel   │◄──────────────►│   Flask AI  │
│  Frontend   │                 │  Backend    │                 │   Server    │
└─────────────┘                 └─────────────┘                 └─────────────┘
       │                               │                               │
       │                               │                               │
       ▼                               ▼                               ▼
┌─────────────┐                 ┌─────────────┐                 ┌─────────────┐
│   Browser   │                 │ PostgreSQL  │                 │  Face Data  │
│   Storage   │                 │  Database   │                 │   Storage   │
└─────────────┘                 └─────────────┘                 └─────────────┘
```

## 🚀 Quick Navigation

- **Start Development**: [`docs/QUICK-START.md`](QUICK-START.md)
- **Run System**: [`docs/RUN-SYSTEM.md`](RUN-SYSTEM.md)
- **View Results**: [`docs/DEMO-RESULTS.md`](DEMO-RESULTS.md)
- **API Reference**: [`backend-api/README.md`](../backend-api/README.md)
- **Deployment**: [`deploy/README.md`](../deploy/README.md)

---

_Last Updated: 2024-12-19_
