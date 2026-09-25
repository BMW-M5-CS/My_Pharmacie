#!/bin/sh
set -e

# ============================================================================
# Ce script tourne à CHAQUE démarrage du conteneur, avant Apache.
#
# IMPORTANT : ce script ne génère plus db_secrets.php ni mailer_secrets.php.
# Il le faisait avant, mais comme le dossier MaPharmacie/ est partagé avec
# WAMP (même fichiers sur le disque, montés dans le conteneur), ça écrasait
# les identifiants locaux de Wilfried à chaque démarrage de Docker, cassant
# WAMP au passage (incident du 24/09/2026). Désormais, config.php et
# mailer_config.php lisent directement les variables d'environnement quand
# elles sont présentes (voir .env / docker-compose.yml), sans jamais toucher
# à ces deux fichiers.
# ============================================================================

# ---- Composer : ne réinstalle que si vendor/ est manquant (le volume monté
#      depuis l'hôte contient normalement déjà vendor/, versionné dans Git) ----
if [ ! -f "/var/www/html/MaPharmacie/vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/ absent, exécution de composer install..."
    composer install --working-dir=/var/www/html/MaPharmacie --no-interaction --no-progress
fi

echo "[entrypoint] Prêt. Démarrage d'Apache."
exec "$@"