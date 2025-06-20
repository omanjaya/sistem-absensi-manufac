@echo off
echo ========================================
echo    Sistem Absensi - Production Deploy
echo ========================================
echo.

:: Set colors
set "GREEN=[92m"
set "RED=[91m"
set "YELLOW=[93m"
set "NC=[0m"

:: Check if required tools are installed
echo %YELLOW%Checking prerequisites...%NC%
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: npm not found. Please install Node.js%NC%
    pause
    exit /b 1
)

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: PHP not found. Please install PHP%NC%
    pause
    exit /b 1
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo %RED%Error: Composer not found. Please install Composer%NC%
    pause
    exit /b 1
)

echo %GREEN%All prerequisites found!%NC%
echo.

:: Create production build directory
if not exist "dist-production" mkdir dist-production
cd dist-production

:: Clean previous builds
if exist "frontend" rmdir /s /q frontend
if exist "backend" rmdir /s /q backend
if exist "deployment-files" rmdir /s /q deployment-files

echo %YELLOW%Creating production build...%NC%
echo.

:: Build Frontend
echo %YELLOW%Building Vue.js Frontend...%NC%
mkdir frontend
cd ..\frontend-web

:: Copy production environment
copy .env.production .env

:: Install dependencies and build
echo Installing frontend dependencies...
call npm ci --silent

echo Building frontend for production...
call npm run build:production

:: Copy build files
echo Copying frontend build files...
xcopy /e /i /h /y dist ..\dist-production\frontend

cd ..

:: Prepare Backend
echo %YELLOW%Preparing Laravel Backend...%NC%
mkdir dist-production\backend
cd backend-api

:: Copy production environment
copy .env.production .env

:: Install production dependencies
echo Installing backend dependencies...
call composer install --no-dev --optimize-autoloader --no-interaction

:: Copy backend files
echo Copying backend files...
xcopy /e /i /h /y . ..\dist-production\backend /exclude:deploy\exclude.txt

cd ..

:: Create deployment files
echo %YELLOW%Creating deployment files...%NC%
mkdir dist-production\deployment-files

:: Copy deployment configurations
copy deploy\*.htaccess dist-production\deployment-files\
copy deploy\DEPLOYMENT-GUIDE.md dist-production\deployment-files\
copy .env.production dist-production\deployment-files\env-template

:: Create zip files for easy upload
echo %YELLOW%Creating zip archives...%NC%
cd dist-production

:: Use PowerShell to create zip files (available on Windows 10+)
powershell -command "Compress-Archive -Path 'frontend\*' -DestinationPath 'frontend-production.zip'"
powershell -command "Compress-Archive -Path 'backend\*' -DestinationPath 'backend-production.zip'"
powershell -command "Compress-Archive -Path 'deployment-files\*' -DestinationPath 'deployment-files.zip'"

cd ..

:: Display success message
echo.
echo %GREEN%========================================%NC%
echo %GREEN%   Production Build Complete!%NC%
echo %GREEN%========================================%NC%
echo.
echo Files created in dist-production/:
echo   - frontend-production.zip   (Upload to public_html/)
echo   - backend-production.zip    (Upload to public_html/api/)
echo   - deployment-files.zip      (Configuration files)
echo.
echo %YELLOW%Next steps:%NC%
echo 1. Update database credentials in .env.production
echo 2. Upload frontend-production.zip to Hostinger public_html/
echo 3. Upload backend-production.zip to Hostinger public_html/api/
echo 4. Extract files on server
echo 5. Run: php artisan migrate --force
echo 6. Run: php artisan db:seed --force
echo.
echo %YELLOW%See DEPLOYMENT-GUIDE.md for detailed instructions%NC%
echo.

pause 