@echo off
echo ========================================
echo    Git Deployment to Hostinger
echo    Domain: manufac.id
echo ========================================

:: Check current git status
echo Checking Git status...
git status --porcelain > temp_status.txt
set /p git_changes=<temp_status.txt
del temp_status.txt

if not "%git_changes%"=="" (
    echo.
    echo WARNING: You have uncommitted changes!
    echo Please commit your changes first:
    echo.
    git status
    echo.
    echo Commands to commit:
    echo git add .
    echo git commit -m "Your commit message"
    echo.
    pause
    exit /b 1
)

:: Get current branch
for /f "tokens=*" %%i in ('git rev-parse --abbrev-ref HEAD') do set current_branch=%%i
echo Current branch: %current_branch%

:: Confirm deployment
echo.
echo Ready to deploy branch '%current_branch%' to Hostinger
set /p confirm="Continue? (y/N): "
if /i not "%confirm%"=="y" (
    echo Deployment cancelled.
    exit /b 0
)

:: Build frontend
echo.
echo ========================================
echo    Building Frontend
echo ========================================
cd frontend-web
if errorlevel 1 (
    echo ERROR: frontend-web directory not found
    pause
    exit /b 1
)

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
cd backend-api
if errorlevel 1 (
    echo ERROR: backend-api directory not found
    pause
    exit /b 1
)

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
if exist "deploy-temp" rmdir /s /q "deploy-temp"
mkdir deploy-temp

:: Copy frontend build
echo Copying frontend files...
if exist "frontend-web\dist" (
    xcopy /E /I /H /Y "frontend-web\dist\*" "deploy-temp\"
) else (
    echo ERROR: Frontend build not found in frontend-web\dist
    pause
    exit /b 1
)

:: Copy backend files
echo Copying backend files...
mkdir "deploy-temp\api"
xcopy /E /I /H /Y "backend-api\app" "deploy-temp\api\app"
xcopy /E /I /H /Y "backend-api\bootstrap" "deploy-temp\api\bootstrap"
xcopy /E /I /H /Y "backend-api\config" "deploy-temp\api\config"
xcopy /E /I /H /Y "backend-api\database" "deploy-temp\api\database"
xcopy /E /I /H /Y "backend-api\public" "deploy-temp\api\public"
xcopy /E /I /H /Y "backend-api\routes" "deploy-temp\api\routes"
xcopy /E /I /H /Y "backend-api\storage" "deploy-temp\api\storage"
xcopy /E /I /H /Y "backend-api\vendor" "deploy-temp\api\vendor"

:: Copy individual files
copy "backend-api\artisan" "deploy-temp\api\"
copy "backend-api\composer.json" "deploy-temp\api\"
copy "backend-api\composer.lock" "deploy-temp\api\"

:: Copy production environment
if exist "dist-production\backend\.env" (
    copy "dist-production\backend\.env" "deploy-temp\api\.env"
    echo Production .env file copied
) else (
    echo WARNING: Production .env not found, using example
    if exist "backend-api\.env.example" (
        copy "backend-api\.env.example" "deploy-temp\api\.env"
    )
)

:: Copy deployment files
mkdir "deploy-temp\deployment-files"
xcopy /E /I /H /Y "deploy\*.md" "deploy-temp\deployment-files\"
if exist "deploy\.htaccess-frontend" copy "deploy\.htaccess-frontend" "deploy-temp\.htaccess"
if exist "deploy\.htaccess-backend" copy "deploy\.htaccess-backend" "deploy-temp\api\.htaccess"

:: Create deployment info
echo Creating deployment info...
(
    echo Deployment Information
    echo =====================
    echo.
    echo Branch: %current_branch%
    echo Commit: 
    git rev-parse HEAD
    echo Date: %date% %time%
    echo.
    echo Files Structure:
    echo - Root: Frontend Vue.js files
    echo - /api/: Laravel backend files
    echo - /deployment-files/: Documentation and configs
    echo.
    echo Next Steps:
    echo 1. Upload all files to manufac.id public_html
    echo 2. Configure database in api/.env
    echo 3. Run: php artisan migrate --force
    echo 4. Set permissions: chmod 755 -R storage bootstrap/cache
) > "deploy-temp\DEPLOYMENT-INFO.txt"

:: Push to repository (optional)
echo.
echo ========================================
echo    Git Operations
echo ========================================
set /p push_git="Push current changes to GitHub? (y/N): "
if /i "%push_git%"=="y" (
    echo Pushing to GitHub...
    git push origin %current_branch%
    if errorlevel 1 (
        echo WARNING: Git push failed, but deployment package is ready
    ) else (
        echo Git push successful!
    )
)

:: Create ZIP file (optional)
echo.
set /p create_zip="Create ZIP file for upload? (y/N): "
if /i "%create_zip%"=="y" (
    if exist "deploy-temp.zip" del "deploy-temp.zip"
    powershell -command "Compress-Archive -Path 'deploy-temp\*' -DestinationPath 'deploy-temp.zip'"
    if exist "deploy-temp.zip" (
        echo ZIP file created: deploy-temp.zip
    ) else (
        echo WARNING: ZIP creation failed
    )
)

echo.
echo ========================================
echo    Deployment Package Ready!
echo ========================================
echo.
echo Files prepared in: deploy-temp\
echo.
echo Manual Upload Steps:
echo 1. Login to Hostinger File Manager
echo 2. Navigate to public_html for manufac.id
echo 3. Upload all files from deploy-temp\
echo 4. Configure database settings in api\.env
echo 5. Run database migrations
echo.
echo Or setup Auto-Deploy:
echo 1. Go to Hostinger hPanel > Git
echo 2. Connect repository: https://github.com/omanjaya/absensi
echo 3. Set branch: %current_branch%
echo 4. Configure build commands from deploy/git-deployment-guide.md
echo.

pause 