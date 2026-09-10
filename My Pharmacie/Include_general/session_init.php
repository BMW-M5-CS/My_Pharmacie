<?php
/**
 * Point d'entrée UNIQUE pour l'initialisation de session sur tout le site.
 *
 * Toute page doit inclure ce fichier EN PREMIER (avant toute sortie HTML) au
 * lieu de dupliquer session_start() + le tirage du jeton CSRF. Centraliser ici
 * permet de durcir les paramètres du cookie de session une seule fois pour
 * tout le projet, plutôt que de risquer un oubli page par page.
 */

if (session_status() === PHP_SESSION_NONE) {

    // Détection HTTPS : couvre le cas d'un reverse proxy (répartiteur de charge,
    // CDN) qui termine le TLS en amont du serveur PHP.
    $est_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,           // cookie de session (expire à la fermeture du navigateur)
        'path'     => '/',
        'domain'   => '',
        'secure'   => $est_https,  // true en production HTTPS ; false en local WAMP (HTTP) pour ne pas bloquer le développement
        'httponly' => true,        // inaccessible en JavaScript — protège contre le vol de session par XSS
        'samesite' => 'Lax',       // protège contre le CSRF inter-site tout en gardant les liens normaux fonctionnels
    ]);

    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
