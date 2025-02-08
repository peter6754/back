git reset --hard
git clean -fd
git pull origin main --force
chown -R www-data:www-data ./
php artisan view:clear && php artisan cache:clear && php artisan route:clear && php artisan config:clear && php artisan route:cache && php artisan config:cache
npm install 
npm run build
