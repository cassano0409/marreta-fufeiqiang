# 🛠️ Marreta

[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/manualdousuario/marreta/blob/master/README.en.md)

[![Forks](https://img.shields.io/github/forks/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/network/members)
[![Stars](https://img.shields.io/github/stars/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/stargazers)
[![Issues](https://img.shields.io/github/issues/manualdousuario/marreta)](https://github.com/manualdousuario/marreta/issues)

Marreta is a tool that breaks access barriers and elements that hinder reading!

![Before and after Marreta](https://github.com/manualdousuario/marreta/blob/main/screen.png?raw=true)

Public instance at [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!

## ✨ What's Cool?

- Automatically cleans and corrects URLs
- Removes annoying tracking parameters
- Forces HTTPS to keep everything secure
- Changes user agent to avoid blocking
- Leaves HTML clean and optimized
- Fixes relative URLs on its own
- Allows you to add your own styles and scripts
- Removes unwanted elements
- Caching, caching!
- Blocks domains you don't want
- Allows configuring headers and cookies your way
- PHP-FPM and OPcache
- Proxy Support

## 🐳 Installing with Docker

Install [Docker and Docker Compose](https://docs.docker.com/engine/install/)

`curl -o ./docker-compose.yml https://raw.githubusercontent.com/manualdousuario/marreta/main/docker-compose.yml`

Now modify with your preferences:

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
      - LANGUAGE=
```

- `SITE_NAME`: Name of your Marreta
- `SITE_DESCRIPTION`: Explain what it's for
- `SITE_URL`: Where it will run, full address with `https://`. If you change the port in docker-compose (e.g., 8080:80), you must also include the port in SITE_URL (e.g., https://yoursite:8080)
- `SELENIUM_HOST`: Server:PORT of Selenium host (e.g., selenium-hub:4444)
- `LANGUAGE`: pt-br (Brazilian Portuguese), en (English), es (Spanish), de-de (German), ru-ru (Russian)
 
Now just run `docker compose up -d`

### More configurations:
- Selenium: https://github.com/manualdousuario/marreta/wiki/%F0%9F%92%BB-Selenium-Hub-(Chrome-and-Firefox)
- S3 Cache: https://github.com/manualdousuario/marreta/wiki/%F0%9F%97%83%EF%B8%8F-Cache-S3
- Maintenance: https://github.com/manualdousuario/marreta/wiki/%F0%9F%9B%A0%EF%B8%8F-Maintenance

## 🚀 Integrations

- 🤖 **Telegram**: [Official Bot](https://t.me/leissoai_bot)
- 🦊 **Firefox**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://addons.mozilla.org/en-US/firefox/addon/marreta/) | [Source Code](https://github.com/manualdousuario/marreta-extensao)
- 🌀 **Chrome**: Extension by [Clarissa Mendes](https://claromes.com/pages/whoami) - [Download](https://chromewebstore.google.com/detail/marreta/ipelapagohjgjcgpncpbmaaacemafppe) | [Source Code](https://github.com/manualdousuario/marreta-extensao)
- 🦋 **Bluesky**: Bot by [Joselito](https://bsky.app/profile/joseli.to) - [Profile](https://bsky.app/profile/marreta.pcdomanual.com) | [Source Code](https://github.com/manualdousuario/marreta-bot)
- 🍎 **Apple**: Integration with [Shortcuts](https://www.icloud.com/shortcuts/3594074b69ee4707af52ed78922d624f)

---

Made with ❤️! If you have questions or suggestions, open an issue and we'll help! 😉

Special thanks to the projects [Burlesco](https://github.com/burlesco/burlesco) and [Hover](https://github.com/nang-dev/hover-paywalls-browser-extension/) which served as the basis for many rules!

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=manualdousuario/marreta&type=Date)](https://star-history.com/#manualdousuario/marreta&Date)