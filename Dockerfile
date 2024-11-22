FROM php:8.0-fpm

RUN apt-get update && apt-get install -y nginx nano procps unzip git htop
	
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY default.conf /etc/nginx/sites-available/default

RUN mkdir -p /app
COPY app/ /app/

WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

# Copy and set permissions for entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /app/cache /app/logs

# Set base permissions for /app
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
