<?php

// ============================================================================
// Résolution centralisée des canaux de contact d'un client
// ----------------------------------------------------------------------------
// Un seul endroit qui décide "par quel email/téléphone peut-on joindre cet
// utilisateur ?" — utilisé par le flux mot de passe oublié ET par la
// confirmation de réservation (email envoyé quand un pharmacien accepte une
// demande), pour ne jamais dupliquer cette règle à deux endroits.
//
// Priorité pour chaque canal :
//   1. Le champ explicitement dédié (email_recuperation / telephone_recuperation)
//      → l'utilisateur l'a renseigné volontairement pour ce rôle précis.
//   2. À défaut, phone_email (l'identifiant de connexion) s'il correspond au
//      format du canal recherché → beaucoup de clients se sont inscrits avec
//      leur email comme identifiant sans remplir le contact de récupération
//      séparément ; autant s'en servir plutôt que de les laisser sans recours.
//
// $user doit être un tableau associatif contenant au moins :
// phone_email, email_recuperation, telephone_recuperation
// (le résultat direct d'un SELECT * FROM users WHERE ... convient).
// ============================================================================

/**
 * Retourne l'adresse email à utiliser pour joindre cet utilisateur, ou null
 * si aucune n'est disponible.
 */
function resoudreEmailContact(array $user): ?string {

    if (!empty($user['email_recuperation'])) {
        return $user['email_recuperation'];
    }

    if (!empty($user['phone_email']) && filter_var($user['phone_email'], FILTER_VALIDATE_EMAIL)) {
        return $user['phone_email'];
    }

    return null;
}

/**
 * Retourne le numéro de téléphone à utiliser pour joindre cet utilisateur,
 * ou null si aucun n'est disponible. Ne tient PAS compte de SMS_ACTIF —
 * c'est à l'appelant de vérifier le drapeau avant de proposer ce canal,
 * cette fonction se contente de dire ce qui existe en base.
 */
function resoudreTelephoneContact(array $user): ?string {

    if (!empty($user['telephone_recuperation'])) {
        return $user['telephone_recuperation'];
    }

    if (!empty($user['phone_email']) && preg_match('/^[0-9+\s]{8,20}$/', $user['phone_email'])) {
        return $user['phone_email'];
    }

    return null;
}

/**
 * Retourne la liste des canaux réellement utilisables pour cet utilisateur
 * EN CE MOMENT (respecte SMS_ACTIF). Exemple de retour :
 * ['email' => 'client@example.com'] — ou ['email' => '...', 'sms' => '...']
 * une fois le SMS actif — ou [] si aucun canal n'est disponible.
 */
function canauxDisponibles(array $user): array {

    $canaux = [];

    $email = resoudreEmailContact($user);
    if ($email !== null) {
        $canaux['email'] = $email;
    }

    if (defined('SMS_ACTIF') && SMS_ACTIF) {
        $telephone = resoudreTelephoneContact($user);
        if ($telephone !== null) {
            $canaux['sms'] = $telephone;
        }
    }

    return $canaux;
}
