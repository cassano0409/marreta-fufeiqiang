# Stage 0: Base
FROM php:8.3-fpm AS base

# Install dependencies and extensions
RUN apt-get update && apt-get install -y \
    nginx \
    nano \
    procps \
    psmisc \
    zip \
    git \
    htop \
    cron \
    libzip-dev \
    libsqlite3-dev \
    && docker-php-ext-install zip opcache pdo_sqlite \
    && docker-php-ext-enable opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Stage 1: Build stage
FROM base AS builder

# Copy OPCache configuration
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy app folder
COPY app/ /app/

# Install composer packages
WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

# Stage 2: Final
FROM base

# Copy necessary files from the builder stage
COPY --from=builder /usr/local/etc/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
COPY --from=builder /app /app

# Copy webservice configuration
COPY default.conf /etc/nginx/sites-available/default

# Copy and configure initialization script permissions
COPY docker-entrypoint.sh /usr/local/bin/
COPY bin/cleanup /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/cleanup

# Create cache, database, and logs folders
RUN mkdir -p /app/cache /app/cache/database /app/logs

# Configure base permissions for /app directory
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

# Configure Cron
RUN touch /app/logs/cron.log
RUN echo '0 * * * * root php "/app/bin/cleanup" >> /app/logs/cleanup.log 2>&1' >> /etc/crontab
RUN echo '0 * * * * root php "/app/bin/proxy" >> /app/logs/proxy.log 2>&1' >> /etc/crontab

# Run proxy list check
RUN php /app/bin/proxy

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]