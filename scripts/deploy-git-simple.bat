@echo off
echo ========================================
echo    Simple Git Deployment to Hostinger
echo    Domain: manufac.id
echo ========================================

:: Get current branch
for /f "tokens=*" %%i in ('git rev-parse --abbrev-ref HEAD') do set current_branch=%%i
echo Current branch: %current_branch%

:: Confirm deployment
echo.
echo Ready to create deployment package for Hostinger
set /p confirm="Continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Deployment cancelled.
    pause
    exit /b 0
)

:: Build frontend
echo.
echo ========================================
echo    Building Frontend
echo ========================================
if not exist "frontend-web" (
    echo ERROR: frontend-web directory not found
    pause
    exit /b 1
)

cd frontend-web
echo Installing frontend dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: npm install failed
    pause
    exit /b 1
)

echo Building for production...
call npm run build:production
if errorlevel 1 (
    echo ERROR: Frontend build failed
    pause
    exit /b 1
)

cd ..

:: Build backend
echo.
echo ========================================
echo    Preparing Backend
echo ========================================
if not exist "backend-api" (
    echo ERROR: backend-api directory not found
    pause
    exit /b 1
)

cd backend-api
echo Installing backend dependencies...
call composer install --no-dev --optimize-autoloader
if errorlevel 1 (
    echo ERROR: Composer install failed
    pause
    exit /b 1
)

cd ..

:: Create deployment package
echo.
echo ========================================
echo    Creating Deployment Package
echo ========================================

:: Remove old deployment
if exist "deploy-temp" (
    echo Removing old deployment folder...
    rmdir /s /q "deploy-temp"
)
mkdir deploy-temp

:: Copy frontend build
echo Copying frontend files...
if exist "frontend-web\dist" (
    xcopy /E /I /H /Y "frontend-web\dist\*" "deploy-temp\"
    if errorlevel 1 (
        echo WARNING: Some frontend files may not have copied
    ) else (
        echo Frontend files copied successfully
    )
) else (
    echo ERROR: Frontend build not found in frontend-web\dist
    echo Please run: cd frontend-web && npm run build:production
    pause
    exit /b 1
)

:: Copy backend files
echo Copying backend files...
mkdir "deploy-temp\api" 2>nul

:: Copy backend directories
xcopy /E /I /H /Y "backend-api\app" "deploy-temp\api\app\" >nul
xcopy /E /I /H /Y "backend-api\bootstrap" "deploy-temp\api\bootstrap\" >nul
xcopy /E /I /H /Y "backend-api\config" "deploy-temp\api\config\" >nul
xcopy /E /I /H /Y "backend-api\database" "deploy-temp\api\database\" >nul
xcopy /E /I /H /Y "backend-api\public" "deploy-temp\api\public\" >nul
xcopy /E /I /H /Y "backend-api\routes" "deploy-temp\api\routes\" >nul
xcopy /E /I /H /Y "backend-api\storage" "deploy-temp\api\storage\" >nul
xcopy /E /I /H /Y "backend-api\vendor" "deploy-temp\api\vendor\" >nul

:: Copy individual files
copy "backend-api\artisan" "deploy-temp\api\" >nul
copy "backend-api\composer.json" "deploy-temp\api\" >nul
copy "backend-api\composer.lock" "deploy-temp\api\" >nul

echo Backend files copied successfully

:: Copy production environment
if exist "dist-production\backend\.env" (
    copy "dist-production\backend\.env" "deploy-temp\api\.env" >nul
    echo Production .env file copied
) else (
    echo WARNING: Production .env not found at dist-production\backend\.env
    if exist "backend-api\.env.example" (
        copy "backend-api\.env.example" "deploy-temp\api\.env" >nul
        echo Using .env.example as fallback (needs manual configuration)
    )
)

:: Copy deployment files
mkdir "deploy-temp\deployment-files" 2>nul
xcopy /E /I /H /Y "deploy\*.md" "deploy-temp\deployment-files\" >nul 2>nul

:: Copy .htaccess files if they exist
if exist "deploy\.htaccess-frontend" (
    copy "deploy\.htaccess-frontend" "deploy-temp\.htaccess" >nul
    echo Frontend .htaccess copied
)
if exist "deploy\.htaccess-backend" (
    copy "deploy\.htaccess-backend" "deploy-temp\api\.htaccess" >nul
    echo Backend .htaccess copied
)

:: Create deployment info
echo Creating deployment info...
(
    echo Deployment Information
    echo =====================
    echo.
    echo Project: Sistem Absensi Manufac.id
    echo Branch: %current_branch%
    echo Commit: %current_commit%
    echo Date: %date% %time%
    echo.
    echo Files Structure:
    echo - Root: Frontend Vue.js files ^(index.html, assets/^)
    echo - /api/: Laravel backend files
    echo - /deployment-files/: Documentation and configs
    echo.
    echo Manual Upload Steps:
    echo 1. Login to Hostinger File Manager
    echo 2. Navigate to manufac.id public_html folder
    echo 3. Upload ALL files from deploy-temp\ folder
    echo 4. Configure database in api/.env file
    echo 5. Run database migrations via terminal or cron
    echo.
    echo Database Configuration:
    echo DB_CONNECTION=mysql
    echo DB_HOST=localhost
    echo DB_DATABASE=u976886556_absensi
    echo DB_USERNAME=u976886556_omanjaya
    echo DB_PASSWORD=@Oman180010216
    echo.
    echo Test URLs after upload:
    echo Frontend: https://manufac.id/
    echo Backend API: https://manufac.id/api/
    echo.
) > "deploy-temp\DEPLOYMENT-INFO.txt"

:: Get commit hash
for /f "tokens=*" %%i in ('git rev-parse HEAD') do set current_commit=%%i

:: Update deployment info with commit
(
    echo Commit Hash: %current_commit%
) >> "deploy-temp\DEPLOYMENT-INFO.txt"

:: Create ZIP file
echo.
set /p create_zip="Create ZIP file for easy upload? (y/N): "
if /i "%create_zip%"=="y" (
    if exist "deploy-temp.zip" del "deploy-temp.zip"
    echo Creating ZIP file...
    powershell -command "Compress-Archive -Path 'deploy-temp\*' -DestinationPath 'deploy-temp.zip'"
    if exist "deploy-temp.zip" (
        for /f "tokens=*" %%i in ('powershell -command "(Get-Item 'deploy-temp.zip').Length / 1MB") do set zip_size=%%i
        echo ZIP file created: deploy-temp.zip (%.1f MB^)
    ) else (
        echo WARNING: ZIP creation failed
    )
)

:: Show results
echo.
echo ========================================
echo    Deployment Package Ready!
echo ========================================
echo.
echo Files prepared in folder: deploy-temp\
echo.
if exist "deploy-temp.zip" (
    echo ZIP file: deploy-temp.zip
    echo.
)

echo Upload options:
echo 1. Manual: Upload deploy-temp\ via Hostinger File Manager
echo 2. Auto: Setup Git integration in Hostinger hPanel
echo.
echo Repository: https://github.com/omanjaya/absensi
echo Branch: %current_branch%
echo.

:: Optional: Push to Git
echo.
set /p push_git="Push current state to GitHub? (y/N): "
if /i "%push_git%"=="y" (
    echo Pushing to GitHub...
    git add .
    git commit -m "Deployment package prepared for %current_branch%"
    git push origin %current_branch%
    if errorlevel 1 (
        echo WARNING: Git push failed
    ) else (
        echo Git push successful!
    )
)

echo.
echo Deployment preparation complete!
echo See DEPLOYMENT-INFO.txt for detailed instructions.
echo.
pause 