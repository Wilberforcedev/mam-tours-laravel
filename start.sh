#!/usr/bin/env sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric TCP port" >&2
        exit 1
        ;;
esac

sed -i "s/10000/$port/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
