@echo off
echo ========================================
echo    Sistem Absensi - Production Build
echo ========================================
echo.

:: Create production directory
if not exist "dist-production" mkdir dist-production

:: Build Frontend
echo Building Vue.js Frontend...
cd frontend-web
if exist ".env" del .env
copy .env.production .env
call npm install
call npm run build
echo Frontend build completed!

:: Copy frontend files
if exist "..\dist-production\frontend" rmdir /s /q ..\dist-production\frontend
mkdir ..\dist-production\frontend
xcopy /e /i /y dist ..\dist-production\frontend

cd ..

:: Prepare Backend
echo.
echo Preparing Laravel Backend...
cd backend-api
if exist ".env" del .env
copy .env.production .env

:: Install dependencies
call composer install --no-dev --optimize-autoloader

:: Copy backend files
if exist "..\dist-production\backend" rmdir /s /q ..\dist-production\backend
mkdir ..\dist-production\backend

:: Copy essential files only
xcopy /e /i /y app ..\dist-production\backend\app
xcopy /e /i /y bootstrap ..\dist-production\backend\bootstrap
xcopy /e /i /y config ..\dist-production\backend\config
xcopy /e /i /y database ..\dist-production\backend\database
xcopy /e /i /y public ..\dist-production\backend\public
xcopy /e /i /y resources ..\dist-production\backend\resources
xcopy /e /i /y routes ..\dist-production\backend\routes
xcopy /e /i /y storage ..\dist-production\backend\storage
xcopy /e /i /y vendor ..\dist-production\backend\vendor

copy artisan ..\dist-production\backend\
copy composer.json ..\dist-production\backend\
copy composer.lock ..\dist-production\backend\
copy .env ..\dist-production\backend\

cd ..

:: Copy deployment files
echo.
echo Copying deployment files...
mkdir dist-production\deployment-files
copy deploy\.htaccess-frontend dist-production\deployment-files\htaccess-frontend.txt
copy deploy\.htaccess-backend dist-production\deployment-files\htaccess-backend.txt
copy deploy\DEPLOYMENT-GUIDE.md dist-production\deployment-files\

echo.
echo ========================================
echo   Production Build Complete!
echo ========================================
echo.
echo Files ready in dist-production/:
echo   - frontend/     (Upload to public_html/)
echo   - backend/      (Upload to public_html/api/)
echo   - deployment-files/ (Configuration files)
echo.
echo Next: Follow DEPLOYMENT-GUIDE.md for Hostinger setup
echo.
pause 