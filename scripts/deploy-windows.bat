@echo off
title Sistem Absensi - Production Deploy untuk manufac.id
echo ========================================
echo    Sistem Absensi - Windows Deploy
echo    Target: manufac.id (Hostinger)
echo ========================================
echo.

:: Set colors
set "GREEN=[92m"
set "RED=[91m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

:: Check prerequisites
echo %YELLOW%Checking prerequisites...%NC%
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: npm not found. Install Node.js from https://nodejs.org%NC%
    pause
    exit /b 1
)

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: PHP not found. Install PHP or use XAMPP%NC%
    pause
    exit /b 1
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: Composer not found. Install from https://getcomposer.org%NC%
    pause
    exit /b 1
)

echo %GREEN%All prerequisites found!%NC%
echo.

:: Create production directory
echo %BLUE%Creating production build directory...%NC%
if not exist "dist-production" mkdir dist-production

:: Clean previous builds
if exist "dist-production\frontend" rmdir /s /q dist-production\frontend
if exist "dist-production\backend" rmdir /s /q dist-production\backend
if exist "dist-production\deployment-files" rmdir /s /q dist-production\deployment-files

echo.
echo %YELLOW%========================================%NC%
echo %YELLOW%Step 1: Building Vue.js Frontend%NC%
echo %YELLOW%========================================%NC%

cd frontend-web

:: Copy production environment
copy .env.production .env >nul
echo ✅ Production environment copied

:: Install dependencies
echo Installing frontend dependencies...
call npm install --silent

:: Build for production
echo Building frontend for production...
call npm run build:production

if %errorlevel% neq 0 (
    echo %RED%Frontend build failed!%NC%
    pause
    exit /b 1
)

echo %GREEN%✅ Frontend build completed!%NC%

cd ..

:: Copy frontend build
mkdir dist-production\frontend
xcopy /e /i /y /q frontend-web\dist dist-production\frontend
echo ✅ Frontend files copied to dist-production

echo.
echo %YELLOW%========================================%NC%
echo %YELLOW%Step 2: Preparing Laravel Backend%NC%
echo %YELLOW%========================================%NC%

cd backend-api

:: Copy production environment with real credentials
copy .env.production .env >nul
echo ✅ Production environment copied

:: Install production dependencies
echo Installing backend dependencies (production only)...
call composer install --no-dev --optimize-autoloader --no-interaction --quiet

if %errorlevel% neq 0 (
    echo %RED%Backend dependencies installation failed!%NC%
    pause
    exit /b 1
)

echo %GREEN%✅ Backend dependencies installed!%NC%

cd ..

:: Copy backend files
echo Copying backend files...
mkdir dist-production\backend

:: Copy essential directories
xcopy /e /i /y /q backend-api\app dist-production\backend\app
xcopy /e /i /y /q backend-api\bootstrap dist-production\backend\bootstrap
xcopy /e /i /y /q backend-api\config dist-production\backend\config
xcopy /e /i /y /q backend-api\database dist-production\backend\database
xcopy /e /i /y /q backend-api\public dist-production\backend\public
xcopy /e /i /y /q backend-api\routes dist-production\backend\routes
xcopy /e /i /y /q backend-api\storage dist-production\backend\storage
xcopy /e /i /y /q backend-api\vendor dist-production\backend\vendor

:: Copy essential files
copy backend-api\artisan dist-production\backend\ >nul
copy backend-api\composer.json dist-production\backend\ >nul
copy backend-api\composer.lock dist-production\backend\ >nul
copy backend-api\.env dist-production\backend\ >nul

echo %GREEN%✅ Backend files copied to dist-production%NC%

echo.
echo %YELLOW%========================================%NC%
echo %YELLOW%Step 3: Creating Deployment Package%NC%
echo %YELLOW%========================================%NC%

:: Create deployment files
mkdir dist-production\deployment-files

:: Copy configuration files
copy deploy\.htaccess-frontend dist-production\deployment-files\htaccess-frontend.txt >nul
copy deploy\.htaccess-backend dist-production\deployment-files\htaccess-backend.txt >nul
copy deploy\DEPLOYMENT-GUIDE.md dist-production\deployment-files\ >nul
copy deploy\migration-guide.md dist-production\deployment-files\ >nul

:: Create info file
echo # Manufac.id Deployment Package > dist-production\deployment-files\README.txt
echo. >> dist-production\deployment-files\README.txt
echo Database Configuration: >> dist-production\deployment-files\README.txt
echo - Host: localhost >> dist-production\deployment-files\README.txt
echo - Database: u976886556_absensi >> dist-production\deployment-files\README.txt
echo - Username: u976886556_omanjaya >> dist-production\deployment-files\README.txt
echo - Password: @Oman180010216 >> dist-production\deployment-files\README.txt
echo. >> dist-production\deployment-files\README.txt
echo Upload Instructions: >> dist-production\deployment-files\README.txt
echo 1. Upload frontend folder contents to public_html/ >> dist-production\deployment-files\README.txt
echo 2. Upload backend folder contents to public_html/api/ >> dist-production\deployment-files\README.txt
echo 3. Copy .htaccess files >> dist-production\deployment-files\README.txt
echo 4. Run: php artisan migrate --force >> dist-production\deployment-files\README.txt

:: Create zip files for easy upload
echo Creating zip files...
powershell -command "try { Compress-Archive -Path 'dist-production\frontend\*' -DestinationPath 'dist-production\frontend-manufac.zip' -Force; Write-Host 'Frontend zip created' } catch { Write-Host 'PowerShell zip failed, use manual zip' }"
powershell -command "try { Compress-Archive -Path 'dist-production\backend\*' -DestinationPath 'dist-production\backend-manufac.zip' -Force; Write-Host 'Backend zip created' } catch { Write-Host 'PowerShell zip failed, use manual zip' }"

echo.
echo %GREEN%========================================%NC%
echo %GREEN%   DEPLOYMENT PACKAGE READY! 🚀%NC%
echo %GREEN%========================================%NC%
echo.
echo %BLUE%Files created in dist-production/:%NC%
echo   📁 frontend/                (Upload to public_html/)
echo   📁 backend/                 (Upload to public_html/api/)
echo   📁 deployment-files/        (Configuration & guides)
echo   📦 frontend-manufac.zip     (Ready for upload)
echo   📦 backend-manufac.zip      (Ready for upload)
echo.
echo %YELLOW%Database Configuration (Hostinger):%NC%
echo   🗄️  Database: u976886556_absensi
echo   👤 Username: u976886556_omanjaya
echo   🔑 Password: @Oman180010216
echo   🌐 Host: localhost
echo.
echo %YELLOW%Next Steps:%NC%
echo %BLUE%1.%NC% Login to Hostinger hPanel
echo %BLUE%2.%NC% Create MySQL database (if not exists)
echo %BLUE%3.%NC% Upload frontend-manufac.zip to public_html/
echo %BLUE%4.%NC% Upload backend-manufac.zip to public_html/api/
echo %BLUE%5.%NC% Extract files on server
echo %BLUE%6.%NC% Copy .htaccess files
echo %BLUE%7.%NC% Run: php artisan migrate --force
echo %BLUE%8.%NC% Test: https://manufac.id/
echo.
echo %GREEN%📖 See DEPLOYMENT-GUIDE.md for detailed instructions%NC%
echo.
echo %YELLOW%Default Login (after deployment):%NC%
echo   👨‍💼 Admin: admin@absensi.com / password
echo   👨‍💻 Employee: john@absensi.com / password
echo.

:: Open dist-production folder
start "" dist-production

pause 