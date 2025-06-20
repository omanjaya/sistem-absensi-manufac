@echo off
setlocal enabledelayedexpansion

echo ================================================
echo 🔧 FIXING PRODUCTION API CONNECTION ISSUES
echo ================================================
echo.

echo ⚙️  Step 1: Building frontend with new configuration...
cd /d "%~dp0..\frontend-web"
call npm run build
if !errorlevel! neq 0 (
    echo ❌ Frontend build failed!
    pause
    exit /b 1
)

echo.
echo ✅ Step 2: Moving to project root...
cd /d "%~dp0.."

echo.
echo 📝 Step 3: Staging changes...
git add .
git add -A

echo.
echo 💾 Step 4: Committing API fixes...
git commit -m "🔧 Fix production API connection issues

- Update CORS config to allow manufac.id domain
- Fix frontend API URLs to point to /backend-api
- Update .htaccess routing for backend-api and face-server
- Add environment variable detection for production
- Increment cache version to force refresh

This should resolve the 30000ms timeout error in production."

if !errorlevel! neq 0 (
    echo ⚠️  No changes to commit or commit failed
)

echo.
echo 🚀 Step 5: Pushing to production...
git push origin main

if !errorlevel! neq 0 (
    echo ❌ Git push failed!
    pause
    exit /b 1
)

echo.
echo ================================================
echo ✅ PRODUCTION API FIX DEPLOYED SUCCESSFULLY!
echo ================================================
echo.
echo 🌐 Changes will be live at: https://manufac.id
echo 📡 GitHub webhook will auto-deploy in ~1-2 minutes
echo.
echo 🔍 Testing checklist:
echo   ✓ Visit https://manufac.id
echo   ✓ Try to login with: admin@absensi.com / password
echo   ✓ Check browser console for errors
echo   ✓ Verify API calls go to /backend-api/api
echo.

echo 📊 Debug info will show:
echo   - Laravel API: https://manufac.id/backend-api/api
echo   - Flask AI: https://manufac.id/face-server
echo   - Frontend: https://manufac.id
echo.

pause 