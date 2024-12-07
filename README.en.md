# 🛠️ Marreta

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

Marreta is a tool for analyzing URLs and accessing web content without hassle.

## ✨ Features

- Automatically cleans and fixes URLs
- Removes annoying tracking parameters
- Forces HTTPS to keep everything secure
- Changes user agent to avoid blocks
- Smart DNS
- Keeps HTML clean and optimized
- Fixes relative URLs automatically
- Allows custom styles
- Removes unwanted elements
- Cache, cache!
- Blocks domains you don't want
- Allows custom headers and cookies configuration
- Everything with SSL/TLS
- PHP-FPM
- OPcache enabled

## 🐳 Docker

### Prerequisites

You only need:
- Docker and docker compose

### Production

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

If needed

`nano docker-compose.yml`

```
services:
  marreta:
    container_name: marreta
    image: ghcr.io/manualdousuario/marreta/marreta:latest
    ports:
      - "80:80"
    environment:
      - SITE_NAME=
      - SITE_DESCRIPTION=
      - SITE_URL=
      - DNS_SERVERS=
      - SELENIUM_HOST=
```

- `SITE_NAME`: Your Marreta's name
- `SITE_DESCRIPTION`: Tell what it's for
- `SITE_URL`: Where it will run, full address with `https://`
- `DNS_SERVERS`: Which DNS servers to use `1.1.1.1, 8.8.8.8`
- `SELENIUM_HOST`: Selenium host server:PORT (e.g., selenium-hub:4444)

Now you can run `docker compose up -d`

#### Development

1. First, clone the project:
```bash
git clone https://github.com/manualdousuario/marreta/
cd marreta
```

2. Create the configuration file:
```bash
cp app/.env.sample app/.env
```

3. Configure it your way in `app/.env`:
```env
SITE_NAME="Marreta"
SITE_DESCRIPTION="Paywall hammer!"
SITE_URL=http://localhost
DNS_SERVERS=1.1.1.1, 8.8.8.8
DEBUG=true
SELENIUM_HOST=selenium-hub:4444
```

4. Run everything:
```bash
docker-compose up -d
```

Done! It will be running at `http://localhost` 🎉

The `DEBUG` option when `true` will not generate cache!

## ⚙️ Customization

The configurations are organized in `data/`:

- `domain_rules.php`: Site-specific rules
- `global_rules.php`: Rules that apply to all sites
- `blocked_domains.php`: List of blocked sites
- `user_agents.php`: User Agents configurations
- `messages.php`: System messages

### S3 Cache

Cache storage support in S3. Configure the following variables in your `.env`:

```env
S3_CACHE_ENABLED=true

S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_REGION=us-east-1
S3_FOLDER_=cache/
S3_ACL=private
S3_ENDPOINT=
```

Possible configurations:

```
## R2
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_ENDPOINT=https://{TOKEN}.r2.cloudflarestorage.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private

## DigitalOcean
S3_ACCESS_KEY=access_key
S3_SECRET_KEY=secret_key
S3_BUCKET=bucket_name
S3_ENDPOINT=https://{REGION}.digitaloceanspaces.com
S3_REGION=auto
S3_FOLDER_=cache/
S3_ACL=private
```

### Selenium Integration

Selenium integration for processing websites that require javascript or have more advanced protection barriers. To use this functionality, you need to set up a Selenium environment with Firefox. Add the following configuration to your `docker-compose.yml`:

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

Important settings:
- `shm_size`: Sets the shared memory size for Firefox (2GB recommended)
- `SE_NODE_MAX_SESSIONS`: Maximum number of concurrent sessions per node
- `GRID_MAX_SESSION`: Maximum number of concurrent sessions in the hub
- `GRID_BROWSER_TIMEOUT` and `GRID_TIMEOUT`: Timeouts in seconds

After setting up Selenium, make sure to set the `SELENIUM_HOST` variable in your environment to point to the Selenium hub (typically `selenium-hub:4444`).

## 🛠️ Maintenance

### Logs

See what's happening:
```bash
docker-compose logs app
```

### Clearing the cache

When you need to clear:
```bash
docker-compose exec app rm -rf /app/cache/*
```

---

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉

Thanks to the project [https://github.com/burlesco/burlesco](Burlesco) which served as the basis for several rules!

Public instance at [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!
