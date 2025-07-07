#!/bin/bash

echo "=== TaskManager Pro Setup Script ==="
echo "Installing required dependencies..."

# Get current directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Working directory: $SCRIPT_DIR"

# Check if composer is installed
if command -v composer &> /dev/null; then
    echo "✓ Composer is already installed"
else
    echo "Installing Composer..."
    
    # Download and install Composer
    curl -sS https://getcomposer.org/installer | php
    
    # Make it executable and move to local directory
    chmod +x composer.phar
    echo "✓ Composer installed locally as composer.phar"
fi

# Create composer.json if it doesn't exist
if [ ! -f "composer.json" ]; then
    echo "Creating composer.json..."
    cat > composer.json << 'EOL'
{
    "name": "taskmanager/pro",
    "description": "TaskManager Pro - Production Task Management System",
    "type": "project",
    "require": {
        "mongodb/mongodb": "^1.15",
        "php": ">=8.0"
    },
    "autoload": {
        "psr-4": {
            "TaskManager\\": "src/"
        }
    },
    "config": {
        "optimize-autoloader": true
    }
}
EOL
    echo "✓ composer.json created"
fi

# Install dependencies
echo "Installing PHP dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
else
    php composer.phar install --no-dev --optimize-autoloader
fi

if [ $? -eq 0 ]; then
    echo "✓ Dependencies installed successfully"
else
    echo "✗ Failed to install dependencies"
    exit 1
fi

# Check if vendor/autoload.php exists
if [ -f "vendor/autoload.php" ]; then
    echo "✓ Autoloader found at vendor/autoload.php"
else
    echo "✗ Autoloader not found"
    exit 1
fi

# Create uploads directory if it doesn't exist
if [ ! -d "uploads_local" ]; then
    mkdir -p uploads_local
    chmod 755 uploads_local
    echo "✓ Upload directory created"
fi

# Copy sample files to uploads directory
if [ -d "../uploads" ]; then
    cp -r ../uploads/* uploads_local/ 2>/dev/null || true
    echo "✓ Sample files copied to uploads directory"
fi

# Test PHP extensions
echo ""
echo "Checking PHP extensions..."
php -m | grep -E "(mongodb|redis)" || echo "⚠ MongoDB or Redis extensions may not be loaded"

# Run test script
echo ""
echo "Running API test..."
php test_api.php

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "1. Make sure MongoDB is running: mongod --dbpath ./database_data --fork"
echo "2. Make sure Redis is running: redis-server cache/redis.conf &"
echo "3. Load database data: mongosh < database/schema.js && mongosh < database/seed.js"
echo "4. Load Redis data: redis-cli < cache/production_data.redis"
echo "5. Start web server: php -S 0.0.0.0:8080"
echo ""
echo "Then access the application at: http://localhost:8080" 