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

# Copy environment file
if [ -f ".env.production" ]; then
    cp .env.production api/.env
elif [ -f "backend-api/.env.example" ]; then
    cp backend-api/.env.example api/.env
fi

# Set permissions
chmod -R 755 api/storage
chmod -R 755 api/bootstrap/cache

echo "Build completed successfully!"
echo "Frontend files copied to root directory"
echo "Backend API available at /api/"
echo "Don't forget to run database migrations!" 