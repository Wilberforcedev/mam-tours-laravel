web: php artisan migrate --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work --sleep=3 --tries=3 --timeout=90
clock: php artisan schedule:work
