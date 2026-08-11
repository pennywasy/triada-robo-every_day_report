#!/bin/bash
set -e

echo "Starting Triada Robo Every Day Report Service..."

# Create necessary directories
mkdir -p /var/log/php-fpm /var/log/supervisor /var/log/nginx
mkdir -p /app/storage/logs /app/logs
chown -R nobody:nobody /app/storage /app/logs


# Create php-fpm log file
touch /var/log/php-fpm/error.log
chmod 666 /var/log/php-fpm/error.log

# Setup cron job for daily reports at 07:00 Moscow Time (05:00 UTC)
echo "Setting up cron job for daily reports..."

# Create crontab entry for running the report command daily at 07:00 MSK
# Using UTC time: 04:00 UTC = 07:00 MSK (with UTC+3 offset)
# For UTC+2 (summer), adjust to 05:00
# CRON_SCHEDULE can be overridden via environment variable
_cron_schedule="${CRON_SCHEDULE:-0 4 * * *}"
CRON_JOB="${_cron_schedule} cd /app && php console send:daily-reports >> /app/storage/logs/cron.log 2>&1"

# Create crontab file
mkdir -p /var/spool/cron/crontabs
echo "$CRON_JOB" > /var/spool/cron/crontabs/nobody
chmod 600 /var/spool/cron/crontabs/nobody

# Optional: Add manual trigger command accessibility
echo "Adding manual command access..."
chmod +x /app/console

# Wait for services to be ready
echo "Configuration complete, starting services..."

# Start supervisor which manages PHP-FPM, Nginx, and Cron
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
