@echo off
echo ========================================
echo    Setup Git Repository - Sistem Absensi
echo    Repository: github.com/omanjaya/absensi
echo ========================================

:: Check if git is installed
git --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Git is not installed or not in PATH
    echo Please install Git first: https://git-scm.com/download/win
    pause
    exit /b 1
)

:: Initialize repository if not exists
if not exist .git (
    echo Initializing Git repository...
    git init
    echo.
)

:: Create .gitignore if not exists
if not exist .gitignore (
    echo Creating .gitignore...
    (
        echo # Dependencies
        echo node_modules/
        echo vendor/
        echo.
        echo # Environment files
        echo .env
        echo .env.local
        echo .env.production
        echo.
        echo # Build outputs
        echo dist/
        echo dist-production/
        echo.
        echo # Logs
        echo *.log
        echo storage/logs/*
        echo.
        echo # Cache
        echo bootstrap/cache/*
        echo storage/framework/cache/*
        echo storage/framework/sessions/*
        echo storage/framework/views/*
        echo.
        echo # IDE
        echo .vscode/
        echo .idea/
        echo.
        echo # OS
        echo .DS_Store
        echo Thumbs.db
        echo.
        echo # Temporary files
        echo *.tmp
        echo *.temp
        echo.
        echo # Laravel specific
        echo /public/hot
        echo /public/storage
        echo /storage/*.key
        echo /vendor
        echo.
        echo # Vue/Node specific
        echo /frontend-web/node_modules
        echo /frontend-web/dist
    ) > .gitignore
    echo .gitignore created!
    echo.
)

:: Create README.md if not exists
if not exist README.md (
    echo Creating README.md...
    (
        echo # Sistem Absensi Manufac.id
        echo.
        echo Modern attendance management system dengan fitur:
        echo - Face recognition attendance
        echo - Excel import/export
        echo - Real-time dashboard
        echo - Employee management
        echo - Salary calculation
        echo.
        echo ## Tech Stack
        echo - **Frontend**: Vue.js 3 + TypeScript + Tailwind CSS
        echo - **Backend**: Laravel 10 + PostgreSQL/MySQL
        echo - **Face Recognition**: Python Flask + OpenCV
        echo.
        echo ## Production URL
        echo https://manufac.id
        echo.
        echo ## Local Development
        echo ```bash
        echo # Backend
        echo cd backend-api
        echo composer install
        echo php artisan serve
        echo.
        echo # Frontend
        echo cd frontend-web
        echo npm install
        echo npm run dev
        echo.
        echo # Face Server
        echo cd face-server
        echo pip install -r requirements.txt
        echo python app_simple.py
        echo ```
        echo.
        echo ## Deployment
        echo See `deploy/git-deployment-guide.md` for complete deployment guide.
    ) > README.md
    echo README.md created!
    echo.
)

:: Add all files
echo Adding files to Git...
git add .

:: Check if there are changes to commit
git diff --cached --quiet
if errorlevel 1 (
    echo Committing changes...
    git commit -m "Initial commit - Sistem Absensi Manufac Production Ready"
    echo.
) else (
    echo No changes to commit.
    echo.
)

:: Set main branch
echo Setting main branch...
git branch -M main

:: Check if remote origin exists
git remote get-url origin >nul 2>&1
if errorlevel 1 (
    echo Adding remote origin...
    git remote add origin https://github.com/omanjaya/absensi.git
    echo.
) else (
    echo Remote origin already exists
    echo.
)

:: Push to repository
echo Pushing to GitHub...
echo NOTE: You may need to enter your GitHub credentials
echo.
git push -u origin main

if errorlevel 1 (
    echo.
    echo ========================================
    echo    PUSH FAILED - Manual Steps Required
    echo ========================================
    echo.
    echo 1. Create repository at: https://github.com/omanjaya/absensi
    echo 2. Make sure you're logged in to GitHub
    echo 3. Run: git push -u origin main
    echo.
    echo Alternative - use GitHub CLI:
    echo gh repo create omanjaya/absensi --public
    echo git push -u origin main
    echo.
) else (
    echo.
    echo ========================================
    echo    SUCCESS! Repository Setup Complete
    echo ========================================
    echo.
    echo Repository URL: https://github.com/omanjaya/absensi
    echo.
    echo Next Steps:
    echo 1. Setup Hostinger Git Deployment
    echo 2. Configure Auto-Deploy Webhook
    echo 3. Test deployment pipeline
    echo.
)

pause 