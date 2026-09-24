# ============================================================================
# Image PHP+Apache pour MaPharmacie
# ----------------------------------------------------------------------------
# Version alignée sur celle utilisée en local par Wilfried sous WAMP (8.3.14),
# pour éviter tout écart de comportement entre le PC de dev et Docker.
# ============================================================================
FROM php:8.3-apache

# ---- Extensions PHP nécessaires au projet ----
# pdo_pgsql : utilisé partout via "new PDO($dsn, ...)" (cf. config.php)
# pgsql     : au cas où une fonction pg_* native serait utilisée ailleurs
# mbstring  : manipulation de chaînes multi-octets (emails, contenus HTML)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---- Composer (pour re-générer vendor/ si jamais absent du volume monté) ----
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- Script de démarrage : génère les fichiers de secrets à partir de
#      l'environnement (.env), puis lance Apache normalement ----
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
