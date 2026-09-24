#!/bin/sh
set -e

# ============================================================================
# Ce script tourne à CHAQUE démarrage du conteneur, avant Apache.
#
# Rappel important : db_secrets.php et mailer_secrets.php ne sont JAMAIS
# versionnés dans Git (voir .gitignore) — une image ou un dépôt fraîchement
# cloné ne les contient donc pas. Ce script les recrée à chaque fois à partir
# des variables d'environnement définies dans .env, pour que le conteneur
# fonctionne dès "docker compose up", sans manipulation manuelle.
# ============================================================================

DEST_APP="/var/www/html/MaPharmacie/int_Public/Dos-php"

echo "[entrypoint] Génération de db_secrets.php à partir de l'environnement..."
cat > "${DEST_APP}/db_secrets.php" <<PHP
<?php

// Fichier généré automatiquement au démarrage du conteneur — voir docker/entrypoint.sh.
// Ne pas modifier à la main, les changements seraient perdus au prochain redémarrage :
// modifie plutôt les variables correspondantes dans le fichier .env à la racine.

\$db_host = '${DB_HOST:-db}';
\$db_port = '${DB_PORT:-5432}';
\$db_name = '${POSTGRES_DB:-my_pharmacie}';
\$db_user = '${POSTGRES_USER:-postgres}';
\$db_pass = '${POSTGRES_PASSWORD}';
PHP

echo "[entrypoint] Génération de mailer_secrets.php à partir de l'environnement..."
cat > "${DEST_APP}/mailer_secrets.php" <<PHP
<?php

// Fichier généré automatiquement au démarrage du conteneur — voir docker/entrypoint.sh.
// En développement Docker, ces valeurs n'ont pas besoin d'être réelles : Mailpit
// capture tous les emails sans authentification, voir mailer_config.php.

\$smtp_user = '${SMTP_USER:-test@mapharmacie.local}';
\$smtp_pass = '${SMTP_PASS:-non_utilise_par_mailpit}';
PHP

# ---- Composer : ne réinstalle que si vendor/ est manquant (le volume monté
#      depuis l'hôte contient normalement déjà vendor/, versionné dans Git) ----
if [ ! -f "/var/www/html/MaPharmacie/vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/ absent, exécution de composer install..."
    composer install --working-dir=/var/www/html/MaPharmacie --no-interaction --no-progress
fi

# ---- Droits d'écriture pour Apache (utile si des logs ou fichiers générés
#      sont écrits par le code plus tard) ----
chown -R www-data:www-data /var/www/html/MaPharmacie 2>/dev/null || true

echo "[entrypoint] Prêt. Démarrage d'Apache."
exec "$@"
