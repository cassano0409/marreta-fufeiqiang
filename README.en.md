# 🛠️ Marreta

[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)
[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

[![Forks](https://img.shields.io/github/forks/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/network/members)
[![Stars](https://img.shields.io/github/stars/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/stargazers)
[![Issues](https://img.shields.io/github/issues/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/issues)

Marreta is a tool that breaks access barriers and elements that hinder reading!

![Before and after Marreta](https://github.com/manualdousuario/marreta/blob/main/screen.en.png?raw=true)

Public instance at [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!

## ✨ What's cool about it?

- Cleans and corrects URLs automatically
- Removes annoying tracking parameters
- Forces HTTPS to keep everything secure
- Changes user agent to avoid blockages
- Leaves the HTML clean and optimized
- Fixes relative URLs on its own
- Allows you to put your own styles and scripts
- Removes unwanted elements
- Cache, cache!
- Blocks domains you don't want
- Allows you to configure headers and cookies your way
- PHP-FPM and OPcache

## 🐳 Installing with Docker

Install Docker and Docker Compose

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

Now modify it with your settings:

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
```

- `SITE_NAME`: Your Marreta's name
- `SITE_DESCRIPTION`: What it's for
- `SITE_URL`: Where it will run, complete address with `https://`. If you change the port in docker-compose (e.g. 8080:80), you must also include the port in SITE_URL (e.g. https://yoursite:8080)
- `DNS_SERVERS`: Which DNS servers to use `1.1.1.1, 8.8.8.8`
- `SELENIUM_HOST`: Selenium host server:PORT (e.g. selenium-hub:4444)

Now you can run `docker compose up -d`

### S3 Cache

Support for cache storage in S3. Configure the following variables in your `.env`:

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

Integration with Selenium allows processing sites that require JavaScript or have some more advanced protection barriers. To use this feature, you need to set up a Selenium environment with Firefox. Add the following configuration to your `docker-compose.yml`:

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
- `GRID_MAX_SESSION`: Maximum number of concurrent sessions on the hub
- `GRID_BROWSER_TIMEOUT` and `GRID_TIMEOUT`: Timeouts in seconds

After configuring Selenium, make sure to set the `SELENIUM_HOST` variable in your environment to point to the Selenium hub (usually `selenium-hub:4444`).

## Development

1. First, clone the project:
```bash 
git clone https://github.com/manualdousuario/marreta/
cd marreta/app
```

2. Install the project dependencies:
```bash
composer install
npm install
```

3. Create the configuration file: 
```bash
cp .env.sample .env
```

4. Configure the environment variables in `.env`

5. Use the `default.conf` as a base for NGINX or point your webservice to `app/`

Gulp is used to compile Sass to CSS, minify JavaScript, use: `gulp`

### ⚙️ Customizing

The settings are organized in `data/`:

- `domain_rules.php`: Specific rules for each site
- `global_rules.php`: Rules that apply to all sites
- `blocked_domains.php`: List of blocked sites

### Translations

- `/languages/`: Each language is in its ISO id (`pt-br, en, es or de-de`) and can be defined in the environment `LANGUAGE`

## 🛠️ Maintenance

### Logging System

Logs are stored in `app/logs/*.log` with automatic rotation every 7 days.

Log settings available in `.env` or docker:

```env
LOG_LEVEL=WARNING
```

Available log levels:
- DEBUG: Detailed information for debugging
- INFO: General information about operations
- WARNING: Warnings that deserve attention (default)
- ERROR: Errors that do not interrupt operation
- CRITICAL: Critical errors that need immediate attention

View the application logs:
```bash
docker-compose logs app
# or directly from the log file
cat app/logs/*.log
```

### Clearing the cache

When you need to clear:
```bash
docker-compose exec app rm -rf /app/cache/*
```

## 🚀 Integrations

- 🤖 **Telegram**: [Official Bot](https://t.me/leissoai_bot)
- 🦊 **Firefox**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://addons.mozilla.org/pt-BR/firefox/addon/marreta/) | [Source Code](https://github.com/manualdousuario/marreta-extensao)
- 🌀 **Chrome**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://chromewebstore.google.com/detail/marreta/ipelapagohjgjcgpncpbmaaacemafppe) | [Source Code](https://github.com/manualdousuario/marreta-extensao)
- 🦋 **Bluesky**: Bot by [Joselito](https://bsky.app/profile/joseli.to) - [Profile](https://bsky.app/profile/marreta.pcdomanual.com) | [Source Code](https://github.com/manualdousuario/marreta-bot)
- 🍎 **Apple**: Integration with [Shortcuts](https://www.icloud.com/shortcuts/3594074b69ee4707af52ed78922d624f)

---

Made with ❤️! If you have any questions or suggestions, open an issue and we'll help! 😉

Thanks to the [https://github.com/burlesco/burlesco](Burlesco) and [https://github.com/nang-dev/hover-paywalls-browser-extension/](Hover) projects that served as the basis for several rules!

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=manualdousuario/marreta&type=Date)](https://star-history.com/#manualdousuario/marreta&Date)
