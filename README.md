# 🛠️ Marreta

[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)

[![Forks](https://img.shields.io/github/forks/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/network/members)
[![Stars](https://img.shields.io/github/stars/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/stargazers)
[![Issues](https://img.shields.io/github/issues/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/issues)

Marreta é uma ferramenta para analisar URLs e acessar conteúdo na web sem dor de cabeça.

## ✨ O que tem de legal?

- Limpa e arruma URLs automaticamente
- Remove parâmetros chatos de rastreamento
- Força HTTPS pra manter tudo seguro
- Troca de user agent pra evitar bloqueios
- DNS esperto
- Deixa o HTML limpinho e otimizado
- Conserta URLs relativas sozinho
- Permite colocar seus próprios estilos
- Remove elementos indesejados
- Cache, cache!
- Bloqueia domínios que você não quer
- Permite configurar headers e cookies do seu jeito
- Tudo com SSL/TLS
- PHP-FPM
- OPcache ligado

## 🐳 Docker

### Antes de começar

Só precisa ter instalado:
- Docker e docker compose

### Produção

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

Se necessario

`nano docker-compose.yml`

```
services:
  marreta:
    container_name: marreta
    image: ghcr.io/manualdousuario/marreta:latest
    ports:
      - "80:80"
    environment:
      - SITE_NAME=
      - SITE_DESCRIPTION=
      - SITE_URL=
      - DNS_SERVERS=
      - SELENIUM_HOST=
```

- `SITE_NAME`: Nome do seu Marreta
- `SITE_DESCRIPTION`: Conta pra que serve
- `SITE_URL`: Onde vai rodar, endereço completo com `https://`
- `DNS_SERVERS`: Quais servidores DNS usar `1.1.1.1, 8.8.8.8`
- `SELENIUM_HOST`: Servidor:PORTA do host do Selenium (ex: selenium-hub:4444)
- 
Agora pode rodar `docker compose up -d`

#### Desenvolvimento

1. Primeiro, clona o projeto:
```bash
git clone https://github.com/manualdousuario/marreta/
cd marreta
```

2. Cria o arquivo de configuração:
```bash
cp app/.env.sample app/.env
```

3. Configura do seu jeito no `app/.env`:
```env
SITE_NAME="Marreta"
SITE_DESCRIPTION="Chapéu de paywall é marreta!"
SITE_URL=http://localhost
DNS_SERVERS=1.1.1.1, 8.8.8.8
DEBUG=true
SELENIUM_HOST=selenium-hub:4444
LANGUAGE=pt-br
```

4. Roda tudo:
```bash
docker-compose up -d
```

Pronto! Vai estar rodando em `http://localhost` 🎉

A opção de `DEBUG` quando `true` não irá gerar cache!

## ⚙️ Personalizando

As configurações estão organizadas em `data/`:

- `domain_rules.php`: Regras específicas para cada site
- `global_rules.php`: Regras que se aplicam a todos os sites
- `blocked_domains.php`: Lista de sites bloqueados
- `user_agents.php`: Configurações de User Agents

### Traduções

- `/languages/`: Cada lingua está em seu ISO id (`pt-br, en ou es`) e pode ser definida no environment `LANGUAGE`

### Cache S3

Suporte de armazenamento do cache em S3. Configure as seguintes variáveis no seu `.env`:

```env
S3_CACHE_ENABLED=true

S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_REGION=us-east-1
S3_FOLDER_=cache/
S3_ACL=private
S3_ENDPOINT=
```

Configurações possiveis:

```
## R2
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_ENDPOINT=https://{TOKEN}.r2.cloudflarestorage.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private

## DigitalOcean
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=nome_do_bucket
S3_ENDPOINT=https://{REGIAO}.digitaloceanspaces.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private
```

### Integração com Selenium

Integração com Selenium para processar sites que requerem javascript ou têm algumas barreiras de proteção mais avançadas. Para usar esta funcionalidade, você precisa configurar um ambiente Selenium com Firefox. Adicione a seguinte configuração ao seu `docker-compose.yml`:

```yaml
services:
  selenium-firefox:
    container_name: selenium-firefox
    image: selenium/node-firefox:4.27.0-20241204
    shm_size: 2gb
    environment:
      - SE_EVENT_BUS_HOST=selenium-hub
      - SE_EVENT_BUS_PUBLISH_PORT=4442
      - SE_EVENT_BUS_SUBSCRIBE_PORT=4443
      - SE_ENABLE_TRACING=false
      - SE_NODE_MAX_SESSIONS=10
      - SE_NODE_OVERRIDE_MAX_SESSIONS=true
    entrypoint: bash -c 'SE_OPTS="--host $$HOSTNAME" /opt/bin/entry_point.sh'
    depends_on:
      - selenium-hub

  selenium-hub:
    image: selenium/hub:4.27.0-20241204
    container_name: selenium-hub
    environment:
      - SE_ENABLE_TRACING=false
      - GRID_MAX_SESSION=10
      - GRID_BROWSER_TIMEOUT=10
      - GRID_TIMEOUT=10
    ports:
      - 4442:4442
      - 4443:4443
      - 4444:4444
```

Configurações importantes:
- `shm_size`: Define o tamanho da memória compartilhada para o Firefox (2GB recomendado)
- `SE_NODE_MAX_SESSIONS`: Número máximo de sessões simultâneas por nó
- `GRID_MAX_SESSION`: Número máximo de sessões simultâneas no hub
- `GRID_BROWSER_TIMEOUT` e `GRID_TIMEOUT`: Timeouts em segundos

Após configurar o Selenium, certifique-se de definir a variável `SELENIUM_HOST` no seu ambiente para apontar para o hub do Selenium (geralmente `selenium-hub:4444`).

### Monitoramento de erros

O Marreta utiliza o [Hawk.so](https://hawk.so), uma plataforma de código aberto para monitoramento de erros. Para configurar o monitoramento, adicione as seguintes variáveis ao seu `.env` ou docker:

```env
HAWK_TOKEN=seu_token
```

Você pode hospedar sua própria instância do Hawk.so ou usar o serviço hospedado em [hawk.so](https://hawk.so). O código fonte está disponível em [github.com/codex-team/hawk](https://github.com/codex-team/hawk).

## 🛠️ Manutenção

### Logs

Ver o que tá acontecendo:
```bash
docker-compose logs app
```

### Limpando o cache

Quando precisar limpar:
```bash
docker-compose exec app rm -rf /app/cache/*
```

---

Feito com ❤️! Se tiver dúvidas ou sugestões, abre uma issue que a gente ajuda! 😉

Agradecimento ao projeto [https://github.com/burlesco/burlesco](Burlesco) que serviu de base para varias regras!

Instancia publica em [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!
