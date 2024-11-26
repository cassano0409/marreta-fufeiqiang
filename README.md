# 🛠️ Marreta

[![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](https://github.com/manualdousuario/marreta/blob/master/README.md)

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
    image: ghcr.io/manualdousuario/marreta/marreta:latest
    ports:
      - "80:80"
    environment:
      - SITE_NAME=
      - SITE_DESCRIPTION=
      - SITE_URL=
      - DNS_SERVERS=
```

- `SITE_NAME`: Nome do seu Marreta
- `SITE_DESCRIPTION`: Conta pra que serve
- `SITE_URL`: Onde vai rodar, endereço completo com `https://`
- `DNS_SERVERS`: Quais servidores DNS usar `94.140.14.14, 94.140.15.15`

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
DNS_SERVERS=94.140.14.14, 94.140.15.15
DEBUG=true
```

4. Roda tudo:
```bash
docker-compose up -d
```

Pronto! Vai estar rodando em `http://localhost` 🎉

A opção de `DEBUG` quando `true` não irá gerar cache!

## ⚙️ Personalizando

No `Rules.php` você pode configurar regras diferentes pra cada site e regras globais

Em `config.php` você tem a lista os sites que não quer permitir ou não permitem extrair dados e configurações de User Agents

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

Instancia publica em [marreta.pcdomanual.com](https://marreta.pcdomanual.com)!
