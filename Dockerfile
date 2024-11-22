FROM php:8.0-fpm

RUN apt-get update && apt-get install -y nginx nano procps unzip git htop
	
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY default.conf /etc/nginx/sites-available/default

RUN mkdir -p /app
COPY app/ /app/

WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

COPY env.sh /usr/local/bin/env.sh
RUN chmod +x /usr/local/bin/env.sh

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

RUN mkdir -p /app/cache /app/logs
RUN chown -R www-data:www-data /app && chmod -R 755 /app

VOLUME ["/app/cache", "/app/logs"]

EXPOSE 80

CMD ["/bin/bash", "-c", "/usr/local/bin/env.sh && /usr/local/bin/start.sh"]
