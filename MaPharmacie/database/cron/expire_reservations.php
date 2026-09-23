<?php
/**
 * Cron : deux responsabilités d'expiration automatique liées aux
 * réservations (voir migrations 005 et 007) :
 *
 *   1. Une DEMANDE ('demandee') que le pharmacien n'a pas traitée dans les
 *      12h (expire_demande_at dépassé) passe à 'expiree'. Le client pourra
 *      ensuite la renouveler une seule fois (cf.
 *      int_Client/Dos-php/renouveler_reservation.php).
 *
 *   2. Une réservation CONFIRMÉE ('confirmee') dont le délai de retrait de
 *      5h est dépassé (expire_at) passe elle aussi à 'expiree'. Le stock
 *      redevient automatiquement disponible : toutes les requêtes de calcul
 *      de stock filtrent déjà sur "statut = 'confirmee' AND expire_at > NOW()"
 *      (cf. traiter_decision_reservation.php, renouveler_reservation.php),
 *      donc aucune libération explicite n'est nécessaire ici.
 *
 * Remplace l'ancien mécanisme probabiliste présent dans
 * int_Public/Dos-php/config.php (1 exécution sur 20 pages vues), qui ciblait
 * un statut 'en_attente' devenu obsolète depuis la migration 005 — ce
 * fichier-ci doit devenir la seule source d'expiration automatique. Voir le
 * signalement correspondant transmis à Wilfried : ce vieux bloc de
 * config.php reste actif tant qu'il n'est pas retiré explicitement.
 *
 * Idempotent par construction : une ligne qui ne correspond plus au WHERE
 * (déjà expirée, ou changée entre-temps par une décision pharmacien ou une
 * action client) n'est simplement pas retouchée, quel que soit le nombre
 * d'exécutions successives ou leur chevauchement.
 *
 * Fréquence recommandée : toutes les 5 à 15 minutes.
 *
 * Exécution manuelle : php database/cron/expirer_reservations.php
 */

require_once __DIR__ . '/../../int_Public/Dos-php/config.php';

const VERROU_ADVISORY_ID = 918273648; // identifiant arbitraire mais stable, propre à ce cron

$verrou_obtenu = $pdo->query('SELECT pg_try_advisory_lock(' . VERROU_ADVISORY_ID . ')')->fetchColumn();

if (!$verrou_obtenu) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Une autre exécution est déjà en cours — arrêt sans erreur.\n");
    exit(0);
}

try {

    $pdo->beginTransaction();

    // ===================================================================
    // 1. Demandes jamais traitées par le pharmacien sous 12h.
    // ===================================================================

    $stmt_demandes = $pdo->prepare(
        "UPDATE reservations
         SET statut = 'expiree'
         WHERE statut = 'demandee'
           AND expire_demande_at IS NOT NULL
           AND expire_demande_at < NOW()"
    );
    $stmt_demandes->execute();
    $nb_demandes_expirees = $stmt_demandes->rowCount();

    // ===================================================================
    // 2. Réservations confirmées non retirées dans les 5h.
    // ===================================================================

    $stmt_confirmees = $pdo->prepare(
        "UPDATE reservations
         SET statut = 'expiree'
         WHERE statut = 'confirmee'
           AND expire_at IS NOT NULL
           AND expire_at < NOW()"
    );
    $stmt_confirmees->execute();
    $nb_confirmees_expirees = $stmt_confirmees->rowCount();

    $pdo->commit();

    echo "[" . date('Y-m-d H:i:s') . "] $nb_demandes_expirees demande(s) expirée(s) (faute de réponse du pharmacien), "
       . "$nb_confirmees_expirees réservation(s) confirmée(s) expirée(s) (faute de retrait).\n";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Échec : " . $e->getMessage() . "\n");
    exit(1);

} finally {

    $pdo->query('SELECT pg_advisory_unlock(' . VERROU_ADVISORY_ID . ')');
}