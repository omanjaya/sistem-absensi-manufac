#!/bin/bash

echo "🔧 Fixing 403 Forbidden Error for Sistem Absensi Manufac.id"
echo "============================================================"

# Check if we're in the right directory
if [ ! -f "index.html" ] && [ ! -d "api" ]; then
    echo "❌ Error: Please run this script from public_html directory"
    echo "Expected structure: index.html and api/ folder"
    exit 1
fi

echo "📁 Checking file structure..."
ls -la

echo ""
echo "🔧 Setting correct permissions..."

# Set storage and cache permissions
if [ -d "api/storage" ]; then
    chmod -R 755 api/storage
    echo "✅ Set api/storage permissions to 755"
else
    echo "⚠️  api/storage not found"
fi

if [ -d "api/bootstrap/cache" ]; then
    chmod -R 755 api/bootstrap/cache  
    echo "✅ Set api/bootstrap/cache permissions to 755"
else
    echo "⚠️  api/bootstrap/cache not found"
fi

# Set .htaccess permissions
if [ -f ".htaccess" ]; then
    chmod 644 .htaccess
    echo "✅ Set root .htaccess permissions to 644"
else
    echo "⚠️  Root .htaccess not found"
fi

if [ -f "api/.htaccess" ]; then
    chmod 644 api/.htaccess
    echo "✅ Set api/.htaccess permissions to 644"
else
    echo "⚠️  API .htaccess not found"
fi

echo ""
echo "🧹 Clearing Laravel cache..."

cd api

# Clear all Laravel caches
php artisan config:clear 2>/dev/null && echo "✅ Config cache cleared" || echo "⚠️  Config clear failed"
php artisan route:clear 2>/dev/null && echo "✅ Route cache cleared" || echo "⚠️  Route clear failed"
php artisan cache:clear 2>/dev/null && echo "✅ Application cache cleared" || echo "⚠️  Cache clear failed"

# Rebuild caches
php artisan config:cache 2>/dev/null && echo "✅ Config cache rebuilt" || echo "⚠️  Config cache failed"
php artisan route:cache 2>/dev/null && echo "✅ Route cache rebuilt" || echo "⚠️  Route cache failed"

cd ..

echo ""
echo "🔍 Checking file ownership..."
ls -la api/storage/ 2>/dev/null || echo "Cannot check api/storage ownership"

echo ""
echo "✅ Fix completed!"
echo ""
echo "🌐 Testing URLs:"
echo "Frontend: https://manufac.id/"
echo "API: https://manufac.id/api/"
echo ""
echo "If still getting 403 error:"
echo "1. Check Hostinger Error Logs in hPanel"
echo "2. Verify index.html exists in root"
echo "3. Check if mod_rewrite is enabled"
echo "4. Contact Hostinger support if needed"
echo ""
echo "📋 Current structure:"
find . -maxdepth 2 -type f -name "*.html" -o -name "*.php" -o -name ".htaccess" | sort 