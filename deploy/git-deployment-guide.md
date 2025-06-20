# Git Deployment ke Hostinger - Panduan Lengkap

## 🚀 **Hostinger Git Deployment Features**

### **Keuntungan Git Deployment:**

- ✅ **Auto-deploy** dari GitHub/GitLab
- ✅ **Version control** yang proper
- ✅ **Rollback** mudah jika ada masalah
- ✅ **Collaboration** tim yang lebih baik
- ✅ **CI/CD** workflow otomatis
- ✅ **Backup** otomatis di repository

### **Hostinger Git Support:**

- ✅ **GitHub** integration
- ✅ **GitLab** integration
- ✅ **Auto-deployment** on push
- ✅ **Branch-based** deployment
- ✅ **SSH access** untuk advanced setup

## 📋 **Setup Git Deployment**

### **Method 1: Hostinger Auto-Deploy (Recommended)**

#### **Step 1: Prepare Repository**

```bash
# 1. Create GitHub repository
# 2. Push your code
git init
git add .
git commit -m "Initial commit - Sistem Absensi Manufac"
git branch -M main
git remote add origin https://github.com/omanjaya/absensi.git
git push -u origin main
```

#### **Step 2: Setup di Hostinger**

1. **Login ke hPanel Hostinger**
2. **Website → Manage → Git**
3. **Connect Repository:**
   ```
   Repository URL: https://github.com/username/sistem-absensi-manufac
   Branch: main
   Target Directory: public_html
   ```

#### **Step 3: Configure Build Commands**

```bash
# Hostinger server commands (Linux)
cd frontend-web && npm install && npm run build:production
cd ../backend-api && composer install --no-dev --optimize-autoloader
cp -r ../frontend-web/dist/* ../
cp -r ./* ../api/
```

**Untuk Windows Development (Local Testing):**

```cmd
:: Use Windows-compatible scripts
scripts\setup-git-repository.bat
scripts\deploy-git-hostinger.bat
```

### **Method 2: Manual Git Setup via SSH**

#### **Step 1: Setup SSH Access**

```bash
# Generate SSH key di Hostinger
ssh-keygen -t rsa -b 4096 -C "your-email@domain.com"

# Add to GitHub/GitLab SSH keys
cat ~/.ssh/id_rsa.pub
```

#### **Step 2: Clone Repository**

```bash
# SSH ke Hostinger
ssh u976886556@manufac.id

# Clone repository
cd domains/manufac.id/public_html
git clone git@github.com:username/sistem-absensi-manufac.git .

# Setup environment
cp .env.production .env
```

#### **Step 3: Setup Auto-Deploy Hook**

```bash
# Create deploy script
cat > deploy.sh << 'EOF'
#!/bin/bash
cd /path/to/your/site

# Pull latest changes
git pull origin main

# Frontend build
cd frontend-web
npm install
npm run build:production
cp -r dist/* ../

# Backend setup
cd ../backend-api
composer install --no-dev --optimize-autoloader
cp -r * ../api/

# Run migrations
cd ../api
php artisan migrate --force

# Clear cache
php artisan config:cache
php artisan route:cache

echo "Deployment completed!"
EOF

chmod +x deploy.sh
```

## 🔧 **Repository Structure untuk Git Deployment**

### **Recommended Structure:**

```
sistem-absensi-manufac/
├── frontend-web/           # Vue.js frontend
├── backend-api/           # Laravel backend
├── face-server/           # Python face service
├── deploy/               # Deployment configs
├── .github/              # GitHub Actions
├── .gitignore
├── README.md
├── .env.example
└── deploy.json           # Hostinger config
```

### **Create .gitignore:**

```gitignore
# Dependencies
node_modules/
vendor/

# Environment files
.env
.env.local
.env.production

# Build outputs
dist/
dist-production/

# Logs
*.log
storage/logs/*

# Cache
bootstrap/cache/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*

# IDE
.vscode/
.idea/

# OS
.DS_Store
Thumbs.db

# Temporary files
*.tmp
*.temp
```

### **Create deploy.json (Hostinger Config):**

```json
{
  "name": "Sistem Absensi Manufac",
  "repository": "https://github.com/username/sistem-absensi-manufac",
  "branch": "main",
  "target": "public_html",
  "build": {
    "frontend": {
      "directory": "frontend-web",
      "commands": ["npm install", "npm run build:production"],
      "output": "dist",
      "target": "../"
    },
    "backend": {
      "directory": "backend-api",
      "commands": ["composer install --no-dev --optimize-autoloader"],
      "target": "../api"
    }
  },
  "env": {
    "APP_ENV": "production",
    "APP_URL": "https://manufac.id",
    "DB_CONNECTION": "mysql"
  }
}
```

## 🤖 **GitHub Actions CI/CD (Advanced)**

### **Create .github/workflows/deploy.yml:**

```yaml
name: Deploy to Hostinger

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: "18"
          cache: "npm"
          cache-dependency-path: frontend-web/package-lock.json

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.2"
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
          coverage: xdebug

      - name: Install Frontend Dependencies
        run: |
          cd frontend-web
          npm ci

      - name: Install Backend Dependencies
        run: |
          cd backend-api
          composer install --prefer-dist --no-progress

      - name: Run Frontend Tests
        run: |
          cd frontend-web
          npm run test:unit

      - name: Run Backend Tests
        run: |
          cd backend-api
          php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Deploy to Hostinger
        uses: easingthemes/ssh-deploy@main
        env:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
          REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
          REMOTE_USER: ${{ secrets.REMOTE_USER }}
          SOURCE: "."
          TARGET: "/domains/manufac.id/public_html"
          EXCLUDE: "/node_modules/, /.git/, /.github/, /vendor/"
          SCRIPT_AFTER: |
            cd /domains/manufac.id/public_html
            ./deploy.sh
```

## 🔒 **Environment Variables & Secrets**

### **GitHub Secrets (Settings → Secrets):**

```
SSH_PRIVATE_KEY: [Your Hostinger SSH private key]
REMOTE_HOST: manufac.id
REMOTE_USER: u976886556
DB_PASSWORD: @Oman180010216
APP_KEY: base64:shvmMdDvW0NMDOR30dbGQ5E/6a/AxHsHVSl3CYrmCdw=
```

### **Environment Template (.env.production.template):**

```env
APP_NAME="Sistem Absensi Manufac"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://manufac.id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u976886556_absensi
DB_USERNAME=u976886556_omanjaya
DB_PASSWORD=${DB_PASSWORD}

# ... other config
```

## 🚀 **Deployment Workflow**

### **Development → Production:**

```bash
# 1. Development
git checkout -b feature/new-feature
# ... make changes
git commit -m "Add new feature"
git push origin feature/new-feature

# 2. Create Pull Request
# Review & approve

# 3. Merge to main
git checkout main
git pull origin main
git merge feature/new-feature
git push origin main

# 4. Auto-deployment triggered
# Hostinger automatically deploys
```

### **Rollback Strategy:**

```bash
# Quick rollback via Git
git revert HEAD
git push origin main

# Or rollback to specific commit
git reset --hard [commit-hash]
git push --force origin main
```

## 📊 **Monitoring & Logs**

### **Setup Monitoring:**

```bash
# Create monitoring script
cat > monitor.sh << 'EOF'
#!/bin/bash
# Check application health
curl -f https://manufac.id/api/health || echo "API DOWN"
curl -f https://manufac.id/ || echo "FRONTEND DOWN"

# Check database
cd /domains/manufac.id/public_html/api
php artisan tinker --execute="DB::connection()->getPdo();" || echo "DB DOWN"
EOF
```

### **Log Monitoring:**

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
tail -f /var/log/apache2/error.log
```

## 🎯 **Pros & Cons Git Deployment**

### **✅ Advantages:**

- **Version Control**: Proper change tracking
- **Collaboration**: Multiple developers
- **Automation**: Auto-deploy on push
- **Rollback**: Easy revert if issues
- **Testing**: Automated testing before deploy
- **Staging**: Multiple environments (dev/staging/prod)

### **⚠️ Considerations:**

- **Learning Curve**: Git knowledge required
- **Setup Time**: Initial configuration
- **SSH Access**: May need premium Hostinger plan
- **Build Time**: Longer deployment process

## 🔄 **Migration from Manual to Git**

### **Step 1: Create Repository**

```bash
# Backup current files
cd /path/to/current/project
git init
git add .
git commit -m "Initial import - existing codebase"
```

### **Step 2: Setup Hostinger Git**

```bash
# Connect repository in hPanel
# Configure auto-deploy
# Test deployment
```

### **Step 3: Verify & Go Live**

```bash
# Test all functionality
# Update DNS if needed
# Monitor first deployment
```

## 🎉 **Recommendation**

**Untuk Sistem Absensi Manufac.id:**

### **Phase 1: Manual Upload** (Quick Start)

- Upload via File Manager
- Get system running ASAP
- Test all features

### **Phase 2: Git Migration** (Long-term)

- Setup GitHub repository
- Configure Hostinger Git
- Implement CI/CD
- Team collaboration

**Start manual, migrate to Git when stable!** 🚀

---

**Note**: Git deployment sangat recommended untuk long-term maintenance dan team collaboration, tapi manual upload lebih cepat untuk go-live pertama.
