# 🐳 Docker Deployment - Sistem Absensi Wajah

Deployment menggunakan Docker Compose untuk menjalankan semua service secara terintegrasi.

## 🏗️ Arsitektur Sistem

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Vue Frontend  │    │  Laravel API    │    │  Flask AI       │
│   Port: 3000    │────│   Port: 8000    │────│   Port: 5000    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Nginx Proxy    │    │   PostgreSQL    │    │     Redis       │
│  Port: 80/443   │    │   Port: 5432    │    │   Port: 6379    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📦 Services

| Service         | Container Name      | Port   | Description               |
| --------------- | ------------------- | ------ | ------------------------- |
| **frontend**    | absensi_frontend    | 3000   | Vue.js SPA dengan Vite    |
| **backend**     | absensi_backend     | 8000   | Laravel 11 REST API       |
| **face-server** | absensi_face_server | 5000   | Flask AI face recognition |
| **database**    | absensi_db          | 5432   | PostgreSQL 15 database    |
| **redis**       | absensi_redis       | 6379   | Redis cache & session     |
| **nginx**       | absensi_nginx       | 80/443 | Nginx reverse proxy       |

## ⚙️ Prerequisites

### System Requirements

- **Docker**: 24.0+
- **Docker Compose**: 2.21+
- **RAM**: 4GB minimum, 8GB recommended
- **CPU**: 2 cores minimum, 4 cores recommended
- **Storage**: 10GB free space

### Check Installation

```bash
docker --version
docker-compose --version
```

## 🚀 Quick Start

### 1. Clone Repository Structure

```bash
git clone <repository-url>
cd sistem-absensi-root

# Verify structure
tree -L 2
# ├── frontend-web/
# ├── backend-api/
# ├── face-server/
# └── deploy/
```

### 2. Environment Setup

```bash
cd deploy

# Copy and edit environment files
cp .env.example .env
cp docker-compose.override.yml.example docker-compose.override.yml

# Edit configurations as needed
nano .env
```

### 3. Build & Start Services

```bash
# Build all images
docker-compose build

# Start services in background
docker-compose up -d

# Monitor logs
docker-compose logs -f
```

### 4. Initialize Database

```bash
# Wait for database to be ready
docker-compose exec backend php artisan migrate --seed

# Verify services
curl http://localhost:8000/api/health
curl http://localhost:5000/health
curl http://localhost:3000
```

### 5. Access Application

- **Frontend**: http://localhost:3000
- **API**: http://localhost:8000/api
- **Face AI**: http://localhost:5000
- **Admin Panel**: http://localhost (nginx proxy)

## 🔧 Configuration

### Environment Variables

**Database Configuration:**

```env
POSTGRES_DB=absensi_db
POSTGRES_USER=postgres
POSTGRES_PASSWORD=secure_password_here
```

**Laravel Configuration:**

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=database
DB_PASSWORD=secure_password_here
FACE_SERVICE_URL=http://face-server:5000
```

**Face Recognition Configuration:**

```env
RECOGNITION_TOLERANCE=0.6
FACE_QUALITY_THRESHOLD=0.8
LOG_LEVEL=INFO
```

### Volume Mounts

| Volume            | Purpose         | Backup Required |
| ----------------- | --------------- | --------------- |
| `postgres_data`   | Database files  | ✅ Yes          |
| `face_data`       | Face encodings  | ✅ Yes          |
| `backend_storage` | Laravel uploads | ✅ Yes          |
| `redis_data`      | Cache data      | ❌ No           |

## 📊 Monitoring & Health Checks

### Service Status

```bash
# Check all services
docker-compose ps

# Check specific service logs
docker-compose logs frontend
docker-compose logs backend
docker-compose logs face-server

# Real-time monitoring
docker stats
```

### Health Endpoints

```bash
# Laravel API health
curl http://localhost:8000/api/health

# Flask AI health
curl http://localhost:5000/health

# Frontend health
curl http://localhost:3000

# Database connection test
docker-compose exec backend php artisan tinker
>>> DB::connection()->getPdo();
```

### Performance Monitoring

```bash
# Resource usage
docker-compose exec backend php artisan route:cache
docker-compose exec face-server curl localhost:5000/stats

# Database performance
docker-compose exec database psql -U postgres -d absensi_db -c "SELECT count(*) FROM users;"
```

## 🔄 Development Workflow

### Hot Reload Development

```bash
# Use development override
cp docker-compose.dev.yml docker-compose.override.yml

# Start with dev configuration
docker-compose up -d

# Frontend hot reload
docker-compose exec frontend npm run dev

# Laravel auto-reload
docker-compose exec backend php artisan serve --host=0.0.0.0
```

### Code Updates

```bash
# Rebuild specific service
docker-compose build backend
docker-compose up -d backend

# Restart all services
docker-compose restart

# Force recreate containers
docker-compose up -d --force-recreate
```

### Database Operations

```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Seed data
docker-compose exec backend php artisan db:seed

# Fresh migration
docker-compose exec backend php artisan migrate:fresh --seed

# Backup database
docker-compose exec database pg_dump -U postgres absensi_db > backup.sql

# Restore database
docker-compose exec -T database psql -U postgres absensi_db < backup.sql
```

## 🔐 Production Deployment

### Security Hardening

```bash
# Generate secure passwords
openssl rand -base64 32

# Setup SSL certificates
mkdir -p ssl/
# Copy SSL certificates to ssl/ directory

# Update nginx configuration for HTTPS
nano nginx/sites-available/absensi.conf
```

### Environment Variables for Production

```env
# Laravel
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your_generated_key_here

# Database
DB_PASSWORD=very_secure_password_here

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

### Backup Strategy

```bash
# Automated backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
docker-compose exec -T database pg_dump -U postgres absensi_db > backups/db_$DATE.sql

# Face data backup
docker cp absensi_face_server:/app/face_data backups/face_data_$DATE/

# Storage backup
docker cp absensi_backend:/var/www/html/storage backups/storage_$DATE/
```

### Load Balancing (Optional)

```yaml
# docker-compose.prod.yml
services:
  backend:
    deploy:
      replicas: 3

  face-server:
    deploy:
      replicas: 2

  nginx:
    depends_on:
      - backend
    environment:
      - NGINX_BACKEND_SERVERS=backend:8000,backend:8000,backend:8000
```

## 🐛 Troubleshooting

### Common Issues

**1. Services Not Starting**

```bash
# Check logs
docker-compose logs

# Verify ports are available
netstat -tulpn | grep :3000
netstat -tulpn | grep :8000
netstat -tulpn | grep :5000

# Clean up and restart
docker-compose down -v
docker-compose up -d
```

**2. Database Connection Issues**

```bash
# Check database container
docker-compose exec database psql -U postgres -d absensi_db -c "SELECT 1;"

# Reset database
docker-compose down
docker volume rm deploy_postgres_data
docker-compose up -d database
```

**3. Face Recognition Issues**

```bash
# Check face server logs
docker-compose logs face-server

# Test face server directly
curl -X POST http://localhost:5000/recognize \
  -H "Content-Type: application/json" \
  -d '{"photo": "test_base64_data"}'

# Rebuild with fresh dependencies
docker-compose build --no-cache face-server
```

**4. High Memory Usage**

```bash
# Monitor resource usage
docker stats

# Limit container resources
# Add to docker-compose.yml:
deploy:
  resources:
    limits:
      memory: 512M
      cpus: '0.5'
```

**5. Nginx Proxy Issues**

```bash
# Test nginx configuration
docker-compose exec nginx nginx -t

# Reload nginx
docker-compose exec nginx nginx -s reload

# Check upstream servers
docker-compose exec nginx cat /etc/nginx/conf.d/default.conf
```

### Performance Optimization

**1. Database Optimization**

```bash
# Optimize PostgreSQL
docker-compose exec database psql -U postgres -d absensi_db -c "VACUUM ANALYZE;"

# Monitor database performance
docker-compose exec database psql -U postgres -d absensi_db -c "
SELECT schemaname,tablename,attname,n_distinct,correlation
FROM pg_stats WHERE schemaname='public';"
```

**2. Laravel Optimization**

```bash
# Cache optimization
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache

# Optimize autoloader
docker-compose exec backend composer dump-autoload --optimize
```

**3. Face Recognition Optimization**

```bash
# Monitor face server performance
curl http://localhost:5000/stats

# Adjust workers based on CPU
# Update docker-compose.yml face-server command:
# gunicorn --workers 4 --bind 0.0.0.0:5000 app:app
```

## 📈 Scaling

### Horizontal Scaling

```bash
# Scale specific services
docker-compose up -d --scale backend=3
docker-compose up -d --scale face-server=2

# With nginx load balancing
# Update nginx upstream configuration
```

### Vertical Scaling

```yaml
# Update resource limits
services:
  backend:
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: "1.0"
```

## 📞 Support

### Logs Location

- **Application logs**: `docker-compose logs [service]`
- **Laravel logs**: `backend_storage` volume `/logs/`
- **Face server logs**: Container `/app/face_server.log`
- **Nginx logs**: Container `/var/log/nginx/`

### Debugging Commands

```bash
# Enter container shell
docker-compose exec backend bash
docker-compose exec face-server bash

# Database shell
docker-compose exec database psql -U postgres -d absensi_db

# Redis shell
docker-compose exec redis redis-cli
```

### Performance Metrics

```bash
# System metrics
docker-compose exec backend php artisan queue:monitor
curl http://localhost:5000/stats
docker stats --no-stream
```

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

---

**🚀 Happy Deploying! Container-ready production system.**

# Deployment Configuration

Production deployment files for Sistem Absensi Manufac.id

## 🚀 Quick Deploy

### Auto Deploy (Recommended)

```bash
# Push to trigger auto-deploy
git add .
git commit -m "Deploy changes"
git push origin main
```

### Manual Deploy

```bash
# Build production package
../scripts/deploy-git-simple.bat

# Upload deploy-temp/ contents to Hostinger public_html
```

## 📁 Files Overview

- `DEPLOYMENT-GUIDE.md` - Complete deployment instructions
- `exclude.txt` - Files to exclude from production build
- `.htaccess-*` - Apache configuration files
- `docker-*.yml` - Docker deployment configurations
- `Dockerfile.*` - Container build instructions

## 🌐 Production URLs

- **Website**: https://manufac.id
- **API**: https://manufac.id/api/
- **Webhook**: `https://webhooks.hostinger.com/deploy/7f59fddf8be7857f24d3de0010477ddf`

## ⚡ Auto-Deploy Status

- ✅ GitHub repository configured
- ✅ Hostinger webhook active
- ✅ Build script working
- ✅ Production environment ready

## 🔧 Manual Fixes

If deployment issues occur:

1. **Run fix script**: `../scripts/fix-permissions.bat`
2. **Check logs**: Hostinger hPanel → Git
3. **Verify files**: Ensure .htaccess exists in root and api/

---

Ready for production deployment! 🚀
