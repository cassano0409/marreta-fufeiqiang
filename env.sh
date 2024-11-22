#!/bin/bash

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

echo "Variáveis de ambiente salvas com sucesso."
