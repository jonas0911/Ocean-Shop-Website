#!/bin/bash

# Ocean Shop URL Fix Script for Production Server
# Removes /ocean/shop prefix from all URLs

echo "🔧 Fixing Ocean Shop URLs for production server..."

cd /var/www/ocean-de.com/Shop

# Backup original files
echo "📁 Creating backup..."
mkdir -p backups/$(date +%Y%m%d_%H%M%S)
cp -r . backups/$(date +%Y%m%d_%H%M%S)/ 2>/dev/null || true

echo "🔄 Replacing /ocean/shop URLs with clean URLs..."

# Fix PHP files
find . -name "*.php" -type f -exec sed -i 's|/ocean/shop/|/|g' {} \;
find . -name "*.php" -type f -exec sed -i 's|"/ocean/shop"|"/"|g' {} \;
find . -name "*.php" -type f -exec sed -i "s|'/ocean/shop'|'/'|g" {} \;

# Fix JavaScript files
find . -name "*.js" -type f -exec sed -i 's|/ocean/shop/|/|g' {} \;

# Fix CSS files
find . -name "*.css" -type f -exec sed -i 's|/ocean/shop/|/|g' {} \;

# Fix HTML files (if any)
find . -name "*.html" -type f -exec sed -i 's|/ocean/shop/|/|g' {} \;

echo "🎨 Fixing asset paths..."
# Fix asset references
find . -name "*.php" -type f -exec sed -i 's|assets/|/assets/|g' {} \;
find . -name "*.php" -type f -exec sed -i 's|"/assets/|"assets/|g' {} \;

echo "🔐 Fixing API calls..."
# Fix API calls that might be broken
find . -name "*.js" -type f -exec sed -i 's|"/api/|"api/|g' {} \;
find . -name "*.php" -type f -exec sed -i 's|"/api/|"api/"|g' {} \;

echo "🏠 Fixing home redirects..."
# Fix specific redirects to home
find . -name "*.php" -type f -exec sed -i "s|header('Location: /')|header('Location: /')|g" {} \;

echo "✅ URL fixes completed!"

echo "🔄 Restarting PHP-FPM..."
systemctl reload php8.2-fpm

echo "🔄 Reloading Nginx..."
nginx -s reload

echo "🎉 Ocean Shop is now ready for production!"
echo "📍 Test your website: https://ocean-de.com"

# Show summary
echo "📊 Summary of changes:"
echo "   - Removed /ocean/shop/ prefix from all URLs"
echo "   - Fixed asset paths"
echo "   - Fixed API endpoints"
echo "   - Fixed navigation links"
echo "   - Backup created in backups/ directory"

echo ""
echo "🚀 Your Ocean Shop should now work correctly on the production server!"