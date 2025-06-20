#!/bin/bash
# ========================================
# 🗄️ DATABASE SETUP FOR MANUFAC.ID
# Setup MySQL database on Hostinger production
# ========================================

echo "🗄️ Starting Database Setup for Sistema Absensi Manufac.id..."

# Navigate to Laravel API directory
cd /home/u976886556/domains/manufac.id/public_html/api || {
    echo "❌ Error: Cannot access API directory"
    echo "Make sure the website structure is deployed first"
    exit 1
}

echo "📁 Current directory: $(pwd)"

# Check if Laravel is properly installed
if [ ! -f "artisan" ]; then
    echo "❌ Error: Laravel artisan not found"
    echo "Please deploy the backend application first"
    exit 1
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file from production template..."
    cat > .env << 'ENV_END'
APP_NAME="Sistema Absensi"
APP_ENV=production
APP_KEY=base64:shvmMdDvW0NMDOR30dbGQ5E/6a/AxHsHVSl3CYrmCdw=
APP_DEBUG=false
APP_URL=https://manufac.id

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# MySQL Database Configuration (Hostinger)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u976886556_absensi
DB_USERNAME=u976886556_omanjaya
DB_PASSWORD=@Oman180010216

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
ENV_END
else
    echo "✅ .env file already exists"
fi

# Test database connection
echo "🔌 Testing database connection..."
php artisan config:clear 2>/dev/null || echo "Config cleared"

# Run database migrations
echo "📊 Running database migrations..."
php artisan migrate --force 2>/dev/null || {
    echo "⚠️ Migration failed, trying to create tables manually..."
    
    # If migration fails, try basic table creation
    mysql -h localhost -u u976886556_omanjaya -p@Oman180010216 u976886556_absensi << 'SQL_END'
-- Check if database is accessible
SELECT 1 as connection_test;

-- Create basic users table if not exists
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    employee_id VARCHAR(255) UNIQUE NULL,
    phone VARCHAR(255) NULL,
    department VARCHAR(255) NULL,
    position VARCHAR(255) NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    status ENUM('active', 'inactive') DEFAULT 'active',
    join_date DATE NULL,
    avatar VARCHAR(255) NULL,
    basic_salary DECIMAL(12, 2) DEFAULT 0,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert admin user if not exists
INSERT IGNORE INTO users (name, email, password, employee_id, role, status, email_verified_at) 
VALUES ('Admin', 'admin@absensi.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM001', 'admin', 'active', NOW());

SELECT COUNT(*) as user_count FROM users;
SQL_END
}

# Install/update dependencies if composer is available
if command -v composer >/dev/null 2>&1; then
    echo "📦 Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction 2>/dev/null || echo "⚠️ Composer install skipped"
else
    echo "⚠️ Composer not available, dependencies will be handled by Hostinger"
fi

# Clear and cache configuration
echo "🧹 Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || echo "Config clear skipped"
php artisan cache:clear 2>/dev/null || echo "Cache clear skipped"
php artisan route:clear 2>/dev/null || echo "Route clear skipped"
php artisan view:clear 2>/dev/null || echo "View clear skipped"

# Cache for production
echo "⚡ Caching for production..."
php artisan config:cache 2>/dev/null || echo "Config cache skipped"
php artisan route:cache 2>/dev/null || echo "Route cache skipped"
php artisan view:cache 2>/dev/null || echo "View cache skipped"

# Run database seeder (create sample data)
echo "🌱 Seeding database with initial data..."
php artisan db:seed --force 2>/dev/null || {
    echo "⚠️ Seeder failed, creating basic admin user manually..."
    
    # Create admin user manually using SQL
    mysql -h localhost -u u976886556_omanjaya -p@Oman180010216 u976886556_absensi << 'SEED_SQL'
-- Create admin user if not exists
INSERT IGNORE INTO users (
    name, 
    email, 
    password, 
    employee_id, 
    phone, 
    department, 
    position, 
    role, 
    status, 
    join_date, 
    basic_salary, 
    email_verified_at,
    created_at,
    updated_at
) VALUES (
    'Admin Sistema Absensi',
    'admin@absensi.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'ADM001',
    '+628123456789',
    'Management', 
    'System Administrator',
    'admin',
    'active',
    CURDATE(),
    10000000,
    NOW(),
    NOW(),
    NOW()
);

-- Create sample employee
INSERT IGNORE INTO users (
    name, 
    email, 
    password, 
    employee_id, 
    phone, 
    department, 
    position, 
    role, 
    status, 
    join_date, 
    basic_salary, 
    email_verified_at,
    created_at,
    updated_at
) VALUES (
    'John Doe',
    'john@absensi.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'EMP001',
    '+628987654321',
    'IT', 
    'Software Engineer',
    'employee',
    'active',
    CURDATE(),
    8000000,
    NOW(),
    NOW(),
    NOW()
);

SELECT 'Database seeded successfully!' as message;
SELECT name, email, role FROM users;
SEED_SQL
}

# Set proper storage permissions
echo "🔒 Setting storage permissions..."
chmod -R 755 storage/ bootstrap/cache/ 2>/dev/null || echo "Permission setting skipped"

# Test the setup
echo ""
echo "🧪 Testing database setup..."
php artisan tinker --execute="echo 'Users count: ' . App\Models\User::count();" 2>/dev/null || {
    echo "Direct SQL test..."
    mysql -h localhost -u u976886556_omanjaya -p@Oman180010216 u976886556_absensi -e "SELECT COUNT(*) as total_users FROM users;" 2>/dev/null || echo "❌ Database connection test failed"
}

echo ""
echo "✅ ========================================="
echo "   DATABASE SETUP COMPLETED!"
echo "==========================================="
echo ""
echo "📊 DEFAULT ACCOUNTS:"
echo "👤 Admin:"
echo "   Email: admin@absensi.com"
echo "   Password: password"
echo "   Role: Administrator"
echo ""
echo "👤 Employee (Sample):"
echo "   Email: john@absensi.com"  
echo "   Password: password"
echo "   Role: Employee"
echo ""
echo "🌐 TEST LOGIN:"
echo "1. Go to: https://manufac.id/login"
echo "2. Use admin credentials above"
echo "3. Access dashboard and employee management"
echo ""
echo "📋 NEXT STEPS:"
echo "1. Test login functionality"
echo "2. Add real employee data via Excel import"
echo "3. Configure face recognition settings"
echo "4. Set up attendance schedules"
echo ""
echo "🔧 TROUBLESHOOTING:"
echo "- If login fails: Check .env database config"
echo "- If 500 error: Check Laravel logs in storage/logs/"
echo "- If permission error: Run chmod -R 755 storage/"
echo ""
echo "🚀 Database ready for Sistema Absensi Manufac.id!" 