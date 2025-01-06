FROM php:8.3-fpm

# Install PHP dependencies and extensions
# Instala dependências e extensões do PHP
RUN apt-get update && apt-get install -y \
    nginx \
    nano \
    procps \
    zip \
    git \
    htop \
    libzip-dev \
    libhiredis-dev \
    && docker-php-ext-install zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis opcache

# Copy OPCache configuration
# Copia a configuração do OPCache
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
# Instala o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy webservice configuration
# Copia a configuração do webservice
COPY default.conf /etc/nginx/sites-available/default

RUN mkdir -p /app
COPY app/ /app/

WORKDIR /app
RUN composer install --no-interaction --optimize-autoloader

# Copy and configure initialization script permissions
# Copia e configura permissões do script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN mkdir -p /app/cache /app/logs

# Configure base permissions for /app directory
# Configura permissões base para o diretório /app
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
