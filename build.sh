#!/bin/bash

echo "Starting deployment build for Sistem Absensi Manufac.id..."

# Set error handling
set -e

# Build Frontend
echo "Building frontend..."
cd frontend-web
npm install
npm run build:production
cd ..

# Install Backend Dependencies
echo "Installing backend dependencies..."
cd backend-api
composer install --no-dev --optimize-autoloader --no-interaction
cd ..

# Copy frontend build to root
echo "Copying frontend files..."
cp -r frontend-web/dist/* ./

# Create api directory and copy backend files
echo "Setting up backend API..."
mkdir -p api
cp -r backend-api/* api/

# Copy .htaccess files
echo "Setting up .htaccess files..."
if [ -f ".htaccess" ]; then
    echo "Root .htaccess found and copied"
else
    echo "Creating root .htaccess..."
fi

if [ -f "api/.htaccess" ]; then
    echo "API .htaccess found and copied"
else
    echo "Creating API .htaccess..."
fi

# Copy environment file
if [ -f ".env.production" ]; then
    cp .env.production api/.env
elif [ -f "backend-api/.env.example" ]; then
    cp backend-api/.env.example api/.env
fi

# Set permissions
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache

# Set .htaccess permissions
chmod 644 .htaccess 2>/dev/null || echo "Root .htaccess not found"
chmod 644 api/.htaccess 2>/dev/null || echo "API .htaccess not found"

# Clear and rebuild Laravel cache
echo "Setting up Laravel cache..."
cd api
php artisan config:clear 2>/dev/null || echo "Config clear skipped"
php artisan route:clear 2>/dev/null || echo "Route clear skipped" 
php artisan cache:clear 2>/dev/null || echo "Cache clear skipped"
php artisan config:cache 2>/dev/null || echo "Config cache skipped"
php artisan route:cache 2>/dev/null || echo "Route cache skipped"
cd ..

echo "Build completed successfully!"
echo "Frontend files copied to root directory"
echo "Backend API available at /api/"
echo "Don't forget to run database migrations!" 