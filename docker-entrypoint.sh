#!/bin/bash

###########################################
# Marreta Docker Entrypoint
# 
# Este script inicializa o container do Marreta:
# - Configura variáveis de ambiente
# - Ajusta permissões dos diretórios
# - Inicia e verifica serviços (PHP-FPM e Nginx)
###########################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para logs de sucesso
log_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

# Função para logs de erro
log_error() {
    echo -e "${RED}[✗] $1${NC}"
    exit 1
}

# Função para logs de informação
log_info() {
    echo -e "${YELLOW}[i] $1${NC}"
}

echo -e "\n${YELLOW}=== Iniciando Container Marreta ===${NC}\n"

# === Configuração de Variáveis de Ambiente ===
log_info "Configurando variáveis de ambiente..."

if [ -n "${SITE_NAME}" ]; then
    echo "SITE_NAME=${SITE_NAME}" >> /app/.env
fi

if [ -n "${SITE_DESCRIPTION}" ]; then
    echo "SITE_DESCRIPTION=${SITE_DESCRIPTION}" >> /app/.env
fi

if [ -n "${SITE_URL}" ]; then
    echo "SITE_URL=${SITE_URL}" >> /app/.env
fi

if [ -n "${DNS_SERVERS}" ]; then
    echo "DNS_SERVERS=${DNS_SERVERS}" >> /app/.env
fi

# Configurações S3
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

# Configurações do Selenium
if [ -n "${SELENIUM_HOST}" ]; then
    echo "SELENIUM_HOST=${SELENIUM_HOST}" >> /app/.env
fi

log_success "Variáveis de ambiente configuradas"

# === Ajuste de Permissões ===
log_info "Ajustando permissões dos diretórios..."

chown -R www-data:www-data /app/cache /app/logs
chmod -R 775 /app/cache /app/logs

log_success "Permissões ajustadas"

# === Funções de Verificação de Serviços ===
check_nginx() {
    if ! pgrep nginx > /dev/null; then
        log_error "Falha ao iniciar Nginx"
    else
        log_success "Nginx iniciado com sucesso"
    fi
}

check_php_fpm() {
    if ! pgrep php-fpm > /dev/null; then
        log_error "Falha ao iniciar PHP-FPM"
    else
        log_success "PHP-FPM iniciado com sucesso"
    fi
}

# === Inicialização dos Serviços ===
echo -e "\n${YELLOW}=== Iniciando serviços ===${NC}\n"

# Diretório PHP-FPM
if [ ! -d /var/run/php ]; then
    log_info "Criando diretório PHP-FPM..."
    mkdir -p /var/run/php
    chown -R www-data:www-data /var/run/php
    log_success "Diretório PHP-FPM criado"
fi

# Iniciando PHP-FPM
log_info "Iniciando PHP-FPM..."
php-fpm &
sleep 3
check_php_fpm

# Verificando configuração Nginx
log_info "Verificando configuração do Nginx..."
nginx -t
if [ $? -ne 0 ]; then
    log_error "Configuração do Nginx inválida"
else
    log_success "Configuração do Nginx válida"
fi

# Iniciando Nginx
log_info "Iniciando Nginx..."
nginx -g "daemon off;" &
sleep 3
check_nginx

echo -e "\n${GREEN}=== Container Marreta inicializado ===${NC}\n"

wait -n

exit $?
