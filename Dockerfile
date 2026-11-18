# =========================================================
# Image de production RyoPets
# PHP 8.4 + FrankenPHP (serveur web intégré, recommandé pour Symfony)
# =========================================================
FROM dunglas/frankenphp:1-php8.4

# Extensions PHP : PostgreSQL (base sur Render), SQLite, intl, opcache (performances), zip (Composer)
RUN install-php-extensions pdo_pgsql pdo_sqlite intl opcache zip apcu

# Variables d'environnement de production.
# DATABASE_URL et APP_SECRET sont fournis par l'hébergeur au démarrage ;
# la valeur ci-dessous ne sert qu'à construire l'image (aucune connexion n'est faite).
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DATABASE_URL="postgresql://build:build@127.0.0.1:5432/build?serverVersion=17&charset=utf8" \
    TRUSTED_PROXIES="REMOTE_ADDR"

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/php.ini $PHP_INI_DIR/conf.d/zz-ryopets.ini

# 1) Dépendances d'abord (mises en cache par Docker tant que composer.lock ne change pas)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# 2) Le code de l'application
COPY . .

# 3) Autoload optimisé, cache Symfony, fichiers JS (importmap) et compilation des assets
RUN composer dump-autoload --classmap-authoritative --no-dev \
    && composer run-script --no-dev post-install-cmd \
    && php bin/console asset-map:compile \
    && sed -i 's/\r$//' deploy/demarrage.sh \
    && chmod +x deploy/demarrage.sh

EXPOSE 8080

CMD ["/app/deploy/demarrage.sh"]
