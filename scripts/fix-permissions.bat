@echo off
echo ========================================
echo    Fix Permissions - Sistem Absensi
echo    For manual troubleshooting only
echo ========================================

echo.
echo This script helps fix common permission issues.
echo Only run this if you have permission problems after deployment.
echo.

set /p confirm="Continue with permission fix? (y/N): "
if /i not "%confirm%"=="y" (
    echo Operation cancelled.
    pause
    exit /b 0
)

echo.
echo Setting up deployment structure...

:: Create deployment folder structure
if not exist "deploy-temp" mkdir deploy-temp

:: Build frontend if needed
if exist "frontend-web\dist" (
    echo Copying frontend build...
    xcopy /E /I /H /Y "frontend-web\dist\*" "deploy-temp\"
) else (
    echo Building frontend first...
    cd frontend-web
    call npm install
    call npm run build:production
    cd ..
    xcopy /E /I /H /Y "frontend-web\dist\*" "deploy-temp\"
)

:: Setup backend
echo Setting up backend...
mkdir "deploy-temp\api" 2>nul
xcopy /E /I /H /Y "backend-api\*" "deploy-temp\api\"

:: Copy production environment
if exist ".env.production" (
    copy ".env.production" "deploy-temp\api\.env"
) else (
    copy "backend-api\.env.example" "deploy-temp\api\.env"
)

:: Copy htaccess files
copy ".htaccess" "deploy-temp\" 2>nul

echo.
echo Creating .htaccess for API...
(
    echo # Laravel API .htaccess
    echo ^<IfModule mod_rewrite.c^>
    echo     RewriteEngine On
    echo     RewriteCond %%{REQUEST_URI} !^/api/public/
    echo     RewriteRule ^^(.*)$ public/$1 [L]
    echo     RewriteCond %%{REQUEST_FILENAME} !-d
    echo     RewriteCond %%{REQUEST_FILENAME} !-f
    echo     RewriteRule ^^(.*)$ public/index.php [L]
    echo ^</IfModule^>
) > "deploy-temp\api\.htaccess"

echo.
echo ========================================
echo    Files ready in deploy-temp\
echo ========================================
echo.
echo Manual upload steps:
echo 1. Upload all files from deploy-temp\ to public_html
echo 2. Set permissions via File Manager:
echo    - api\storage: 755
echo    - api\bootstrap\cache: 755
echo 3. Configure database in api\.env
echo.

pause 