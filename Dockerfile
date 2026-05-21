FROM alpine:3.20

# Install dependencies
RUN apk add --no-cache \
    php83 \
    php83-fpm \
    php83-pdo \
    php83-pdo_mysql \
    php83-pdo_sqlite \
    php83-curl \
    php83-openssl \
    php83-mbstring \
    php83-tokenizer \
    php83-xml \
    php83-fileinfo \
    php83-ctype \
    php83-session \
    php83-dom \
    php83-simplexml \
    composer \
    nginx \
    supervisor \
    curl \
    bash \
    dcron

# Create app directory
WORKDIR /app

# Copy application files
COPY . /app/

RUN chmod -R 777 /app

# Install PHP dependencies with PHPMailer
RUN composer install --no-interaction --prefer-dist

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /app/storage/logs \
    && chmod -R 777 /app/storage

# Setup PHP-FPM
RUN mkdir -p /run/php-fpm83 && \
    chown -R nobody:nobody /app

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Copy PHP-FPM configuration
COPY docker/www.conf /etc/php83/php-fpm.d/www.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    sed -i 's/\r$//' /entrypoint.sh

# Setup cron for daily reports
RUN mkdir -p /etc/cron.d

# Expose port
EXPOSE 80

# Run entrypoint
CMD ["/entrypoint.sh"]
