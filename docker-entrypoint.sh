#!/bin/bash

###########################################
# Docker Entrypoint
###########################################

# Output colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# Environment Variables Configuration
log_info "Configuring environment variables..."

# Env file (.env)
ENV_FILE="/app/.env"

# Clean Env file
> "$ENV_FILE"

while IFS='=' read -r key value; do
    # If value contains spaces and is not already quoted, add quotes
    if [[ "$value" =~ \  ]] && ! [[ "$value" =~ ^\".*\"$ ]]; then
        value="\"$value\""
    fi

    echo "$key=$value" >> "$ENV_FILE"
done < <(env)

log_success "Environment variables configured"

# Permissions Adjustment
log_info "Adjusting directory permissions..."

chown -R www-data:www-data /app/cache /app/logs
chmod -R 775 /app/cache /app/logs

log_success "Permissions adjusted"

# Service Check Functions
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

# Services Initialization
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

# Wait for any process to exit
wait -n

# Exit with status of process that exited first
exit $?
