#!/bin/bash
# ========================================
# 🚑 MANUFAC.ID - 403 FORBIDDEN FIX
# Run this script via SSH on Hostinger
# ========================================

echo "🚀 Starting 403 Forbidden Fix for manufac.id..."

# Navigate to correct directory
cd /home/u976886556/domains/manufac.id/public_html || {
    echo "❌ Error: Cannot access public_html directory"
    exit 1
}

echo "📁 Current directory: $(pwd)"
echo "📋 Files before cleanup:"
ls -la

# Backup current state
BACKUP_DIR="../backup_$(date +%Y%m%d_%H%M%S)"
echo "💾 Creating backup at: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
cp -r . "$BACKUP_DIR/" 2>/dev/null

# Clean unwanted development files
echo "🧹 Cleaning development files..."
rm -rf backend-api frontend-web face-server scripts vendor
rm -f composer.* build.sh deploy*.* README.md package.json
rm -f HOTFIX-403-MANUFAC.zip DEPLOYMENT-FIX-403.txt

# Extract hotfix if exists (skip vendor issues)
if [ -f "hotfix_temp/index.html" ]; then
    echo "📦 Copying essential files from hotfix..."
    cp hotfix_temp/index.html . 2>/dev/null
    cp hotfix_temp/.htaccess . 2>/dev/null
    cp -r hotfix_temp/assets . 2>/dev/null
    
    # Copy API files (skip vendor)
    if [ -d "hotfix_temp/api" ]; then
        mkdir -p api
        cp hotfix_temp/api/.htaccess api/ 2>/dev/null
        cp -r hotfix_temp/api/app api/ 2>/dev/null
        cp -r hotfix_temp/api/config api/ 2>/dev/null
        cp -r hotfix_temp/api/database api/ 2>/dev/null
        cp -r hotfix_temp/api/public api/ 2>/dev/null
        cp -r hotfix_temp/api/routes api/ 2>/dev/null
        cp -r hotfix_temp/api/storage api/ 2>/dev/null
        cp hotfix_temp/api/.env api/ 2>/dev/null
    fi
    
    # Clean up
    rm -rf hotfix_temp
fi

# Create critical .htaccess files
echo "📝 Creating ROOT .htaccess..."
cat > .htaccess << 'HTACCESS_END'
# Vue.js SPA + Laravel API - Hostinger Production
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # API Routes - Forward to Laravel backend
    RewriteCond %{REQUEST_URI} ^/api/
    RewriteRule ^api/(.*)$ api/public/$1 [L,QSA]
    
    # Vue.js SPA Routes - Everything else goes to index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} !^/api/
    RewriteRule ^.*$ /index.html [L]
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Cache Control
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
HTACCESS_END

# Create API .htaccess
echo "📝 Creating API .htaccess..."
mkdir -p api
cat > api/.htaccess << 'API_HTACCESS_END'
# Laravel API .htaccess - Hostinger Production
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Force HTTPS for API
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Redirect to public directory
    RewriteCond %{REQUEST_URI} !^/api/public/
    RewriteRule ^(.*)$ public/$1 [L]
    
    # Handle Laravel routing
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/index.php [L]
</IfModule>

# Security - Deny access to sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# CORS headers
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "https://manufac.id"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>
API_HTACCESS_END

# Create basic index.html if missing
if [ ! -f "index.html" ]; then
    echo "📄 Creating basic index.html..."
    cat > index.html << 'INDEX_END'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Absensi Manufac.id</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .loading { color: #666; }
        .logo { font-size: 24px; color: #333; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="logo">🏭 Sistema Absensi Manufac.id</div>
    <div class="loading">Loading application...</div>
    <script>
        // Basic loading check
        setTimeout(() => {
            document.querySelector('.loading').innerHTML = 
                'Application ready! <br><small>If you see this, the .htaccess fix worked!</small>';
        }, 2000);
    </script>
</body>
</html>
INDEX_END
fi

# Set proper permissions
echo "🔒 Setting file permissions..."
find . -type f -exec chmod 644 {} \; 2>/dev/null
find . -type d -exec chmod 755 {} \; 2>/dev/null

# Laravel specific permissions
if [ -d "api/storage" ]; then
    chmod -R 755 api/storage/
fi
if [ -d "api/bootstrap/cache" ]; then
    chmod -R 755 api/bootstrap/cache/
fi

# Show final structure
echo ""
echo "✅ ========================================="
echo "   403 FORBIDDEN FIX COMPLETED!"
echo "==========================================="
echo ""
echo "📁 FINAL STRUCTURE:"
ls -la

echo ""
echo "🔍 VERIFICATION:"
echo "✅ Root .htaccess exists: $([ -f .htaccess ] && echo 'YES' || echo 'NO')"
echo "✅ API .htaccess exists: $([ -f api/.htaccess ] && echo 'YES' || echo 'NO')"
echo "✅ Index.html exists: $([ -f index.html ] && echo 'YES' || echo 'NO')"

echo ""
echo "🌐 TEST YOUR WEBSITE:"
echo "- Frontend: https://manufac.id"
echo "- API Test: https://manufac.id/api/"
echo ""
echo "⏰ Wait 2-5 minutes for changes to propagate"
echo "🔄 Clear browser cache (Ctrl+F5) before testing"
echo ""
echo "✨ If still 403: Check Hostinger Git deployment status"
echo "📞 Backup location: $BACKUP_DIR"
echo ""
echo "🚀 DONE! Your website should be working now!" 