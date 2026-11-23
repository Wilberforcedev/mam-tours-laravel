#!/usr/bin/env sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric TCP port" >&2
        exit 1
        ;;
esac

# Render mounts the persistent disk after the image is created.
mkdir -p /var/www/html/storage/app/public /var/www/html/storage/app/private /var/www/html/storage/app/backups
chown -R www-data:www-data /var/www/html/storage/app

# Run migrations after the persistent disk is mounted so file-moving migrations
# can read and protect existing KYC uploads before the app accepts traffic.
php artisan migrate --force

sed -i "s/10000/$port/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
