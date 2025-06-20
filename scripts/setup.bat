@echo off
echo ========================================
echo    SISTEM ABSENSI WAJAH - SETUP
echo ========================================
echo.

:: Set console encoding to UTF-8
chcp 65001 >nul

:: Check prerequisites
echo [INFO] Checking prerequisites...

where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found. Please install PHP 8.2+
    pause
    exit /b 1
)

where composer >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer not found. Please install Composer
    pause
    exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js not found. Please install Node.js 18+
    pause
    exit /b 1
)

where python >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python not found. Please install Python 3.8+
    pause
    exit /b 1
)

echo [SUCCESS] All prerequisites found!
echo.

:: Setup Laravel Backend
echo [1/4] Setting up Laravel Backend...
cd backend-api

if not exist .env (
    copy .env.example .env
    echo [INFO] Environment file created
)

echo [INFO] Installing Composer dependencies...
composer install --no-interaction --optimize-autoloader

echo [INFO] Generating application key...
php artisan key:generate --force

echo [INFO] Please ensure PostgreSQL is running with:
echo Database: absensi_db
echo Username: postgres
echo Password: omanjaya2000
echo.
pause

echo [INFO] Running database migrations...
php artisan migrate:fresh --seed

echo [INFO] Creating storage symlink...
php artisan storage:link

cd ..

:: Setup Face Server
echo.
echo [2/4] Setting up Flask AI Server...
cd face-server

echo [INFO] Installing Python dependencies...
pip install -r requirements.txt

cd ..

:: Setup Frontend
echo.
echo [3/4] Setting up Vue Frontend...
cd frontend-web

echo [INFO] Installing NPM dependencies...
npm install

cd ..

:: Start Services
echo.
echo [4/4] Starting services...
echo.

echo [INFO] Starting Laravel API on http://localhost:8000
start "Laravel API" cmd /k "cd /d %cd%\backend-api && php artisan serve --port=8000"

echo [INFO] Starting Flask AI Server on http://localhost:5000
start "Flask AI" cmd /k "cd /d %cd%\face-server && python app.py"

echo [INFO] Starting Vue Frontend on http://localhost:3000
start "Vue Frontend" cmd /k "cd /d %cd%\frontend-web && npm run dev"

echo.
echo ========================================
echo    SETUP COMPLETED SUCCESSFULLY!
echo ========================================
echo.
echo Services are starting in separate windows:
echo - Laravel API: http://localhost:8000
echo - Flask AI: http://localhost:5000
echo - Vue Frontend: http://localhost:3000
echo.
echo Default Login Credentials:
echo Admin: admin@example.com / password
echo Employee: employee@example.com / password
echo.
echo Check individual terminal windows for service status.
echo Press any key to exit...
pause >nul 