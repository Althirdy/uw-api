#!/bin/bash
set -e  # Stop the script if any command fails

echo "🚀 Deployment started..."

# 1. Get latest code
git pull origin staging

# 2. Run all commands in one go inside the container
docker compose -f docker-compose.uat.yml exec -T uat-app bash -c "
    composer install --no-dev --no-interaction --optimize-autoloader && \
    npm install && \
    npm run build && \
    php artisan migrate --force && \
    php artisan optimize && \
    chown -R www-data:www-data storage bootstrap/cache
"

echo "✅ Deployment finished successfully!"