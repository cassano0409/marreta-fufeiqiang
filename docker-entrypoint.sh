#!/bin/bash

###########################################
# Marreta Docker Entrypoint
# 
# This script initializes the Marreta container:
# - Configures environment variables
# - Adjusts directory permissions
# - Starts and checks services (PHP-FPM and Nginx)
#
# Este script inicializa o container do Marreta:
# - Configura variáveis de ambiente
# - Ajusta permissões dos diretórios
# - Inicia e verifica serviços (PHP-FPM e Nginx)
###########################################

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Success log function
log_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

# Error log function
log_error() {
    echo -e "${RED}[✗] $1${NC}"
    exit 1
}

# Info log function
log_info() {
    echo -e "${YELLOW}[i] $1${NC}"
}

echo -e "\n${YELLOW}=== Starting Marreta ===${NC}\n"

# === Environment Variables Configuration ===
log_info "Configuring environment variables..."

if [ -n "${SITE_NAME}" ]; then
    echo "SITE_NAME=\"${SITE_NAME}\"" >> /app/.env
fi

if [ -n "${SITE_DESCRIPTION}" ]; then
    echo "SITE_DESCRIPTION=\"${SITE_DESCRIPTION}\"" >> /app/.env
fi

if [ -n "${SITE_URL}" ]; then
    echo "SITE_URL=${SITE_URL}" >> /app/.env
fi

if [ -n "${LANGUAGE}" ]; then
    echo "LANGUAGE=${LANGUAGE}" >> /app/.env
fi

if [ -n "${DNS_SERVERS}" ]; then
    echo "DNS_SERVERS=${DNS_SERVERS}" >> /app/.env
fi

# S3 Settings
if [ -n "${S3_CACHE_ENABLED}" ]; then
    echo "S3_CACHE_ENABLED=${S3_CACHE_ENABLED}" >> /app/.env
fi

if [ -n "${S3_ACCESS_KEY}" ]; then
    echo "S3_ACCESS_KEY=${S3_ACCESS_KEY}" >> /app/.env
fi

if [ -n "${S3_SECRET_KEY}" ]; then
    echo "S3_SECRET_KEY=${S3_SECRET_KEY}" >> /app/.env
fi

if [ -n "${S3_BUCKET}" ]; then
    echo "S3_BUCKET=${S3_BUCKET}" >> /app/.env
fi

if [ -n "${S3_REGION}" ]; then
    echo "S3_REGION=${S3_REGION}" >> /app/.env
fi

if [ -n "${S3_FOLDER}" ]; then
    echo "S3_FOLDER=${S3_FOLDER}" >> /app/.env
fi

if [ -n "${S3_ACL}" ]; then
    echo "S3_ACL=${S3_ACL}" >> /app/.env
fi

if [ -n "${S3_ENDPOINT}" ]; then
    echo "S3_ENDPOINT=${S3_ENDPOINT}" >> /app/.env
fi

# Selenium Settings
if [ -n "${SELENIUM_HOST}" ]; then
    echo "SELENIUM_HOST=${SELENIUM_HOST}" >> /app/.env
fi

# Hawk.so Settings
if [ -n "${HAWK_TOKEN}" ]; then
    echo "HAWK_TOKEN=${HAWK_TOKEN}" >> /app/.env
fi

log_success "Environment variables configured"

# === Permissions Adjustment ===
log_info "Adjusting directory permissions..."

chown -R www-data:www-data /app/cache /app/logs
chmod -R 775 /app/cache /app/logs

log_success "Permissions adjusted"

# === Service Check Functions ===
check_nginx() {
    if ! pgrep nginx > /dev/null; then
        log_error "Failed to start Nginx"
    else
        log_success "Nginx started successfully"
    fi
}

check_php_fpm() {
    if ! pgrep php-fpm > /dev/null; then
        log_error "Failed to start PHP-FPM"
    else
        log_success "PHP-FPM started successfully"
    fi
}

# === Services Initialization ===
echo -e "\n${YELLOW}=== Starting services ===${NC}\n"

# PHP-FPM Directory
if [ ! -d /var/run/php ]; then
    log_info "Creating PHP-FPM directory..."
    mkdir -p /var/run/php
    chown -R www-data:www-data /var/run/php
    log_success "PHP-FPM directory created"
fi

# Starting PHP-FPM
log_info "Starting PHP-FPM..."
php-fpm &
sleep 3
check_php_fpm

# Checking Nginx configuration
log_info "Checking Nginx configuration..."
nginx -t
if [ $? -ne 0 ]; then
    log_error "Invalid Nginx configuration"
else
    log_success "Valid Nginx configuration"
fi

# Starting Nginx
log_info "Starting Nginx..."
nginx -g "daemon off;" &
sleep 3
check_nginx

echo -e "\n${GREEN}=== Marreta initialized ===${NC}\n"

wait -n

exit $?
