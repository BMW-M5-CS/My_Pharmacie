<?php

// ============================================================================
// Drapeaux de fonctionnalités — MaPharmacie
// ----------------------------------------------------------------------------
// Pas un fichier de secrets (rien de sensible ici) : versionné normalement,
// contrairement à config.php / mailer_secrets.php / db_secrets.php.
//
// SMS_ACTIF : tant que false, aucun canal SMS n'est proposé nulle part
// (récupération de mot de passe, confirmation de réservation...) — même si
// un utilisateur a un telephone_recuperation renseigné. Le jour où une API
// SMS est branchée, il suffit de passer ce drapeau à true : les écrans et la
// logique de résolution de canal (canaux_recuperation.php) sont déjà prêts
// à l'utiliser, aucune autre modification n'est nécessaire.
// ============================================================================

if (!defined('SMS_ACTIF')) {
    define('SMS_ACTIF', false);
}
