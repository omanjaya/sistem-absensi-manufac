@echo off
echo ============================================
echo    Sistema Absensi - Development Setup
echo ============================================
echo.

REM Check if Laravel is running
echo Checking Laravel backend...
curl -s -o nul http://localhost:8000/api/test-notifications
if %errorlevel% neq 0 (
    echo ❌ Laravel backend tidak berjalan di port 8000
    echo.
    echo Menjalankan Laravel backend...
    start "Laravel Backend" cmd /k "cd backend-api && php artisan serve --port=8000"
    echo ⏳ Menunggu Laravel backend siap...
    timeout /t 5 /nobreak > nul
) else (
    echo ✅ Laravel backend sudah berjalan di port 8000
)

REM Check if Frontend is running
echo.
echo Checking Vue frontend...
curl -s -o nul http://localhost:3000
if %errorlevel% neq 0 (
    echo ❌ Vue frontend tidak berjalan di port 3000
    echo.
    echo Menjalankan Vue frontend...
    start "Vue Frontend" cmd /k "cd frontend-web && npm run dev"
    echo ⏳ Menunggu Vue frontend siap...
    timeout /t 10 /nobreak > nul
) else (
    echo ✅ Vue frontend sudah berjalan di port 3000
)

REM Optional: Check Flask AI service
echo.
echo Checking Flask AI service (optional)...
curl -s -o nul http://localhost:5000
if %errorlevel% neq 0 (
    echo ⚠️ Flask AI service tidak berjalan di port 5000 (opsional)
    echo   Untuk face recognition, jalankan: cd face-server && python app.py
) else (
    echo ✅ Flask AI service berjalan di port 5000
)

echo.
echo ============================================
echo    Setup selesai! Buka browser di:
echo    http://localhost:3000
echo.
echo    API akan otomatis terkoneksi ke:
echo    http://localhost:8000/api
echo ============================================
echo.
echo Tekan sembarang key untuk membuka browser...
pause > nul

REM Open browser
start http://localhost:3000

echo.
echo Aplikasi siap digunakan!
echo Untuk mematikan, tutup terminal Laravel dan Vue yang terbuka.
pause 