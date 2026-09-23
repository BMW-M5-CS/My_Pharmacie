
<?php
/**
 * Cron : rappelle par email au client d'aller retirer une réservation
 * confirmée avant l'expiration du délai de 5h (cf. migration 007).
 *
 * 2 rappels prévus par réservation, à 2h restantes puis 1h restante avant
 * expire_at — même principe que les 3 rappels du pharmacien
 * (database/cron/relancer_pharmaciens.php), mais calculé sur le temps
 * RESTANT avant expiration plutôt que sur le temps écoulé depuis la
 * création, puisque expire_at est déjà connu dès la confirmation (contexte
 * différent du pharmacien, qui n'a pas de date de fin fixée à l'avance).
 *
 * Idempotent même en cas d'exécution irrégulière (cron en retard, redémarré
 * après une panne) : on calcule le nombre de rappels QUI DEVRAIENT avoir été
 * envoyés à cet instant, et on n'envoie que le complément manquant — jamais
 * plus d'un rappel par exécution pour une même réservation, même si les deux
 * seuils ont été dépassés d'un coup pendant que le cron était arrêté (pas de
 * rattrapage en rafale qui spammerait le client).
 *
 * Un seul rappel par réservation confirmée (regroupée par code_reservation,
 * qui relie toutes les lignes/produits d'une même demande), jamais un par
 * ligne de produit — le compteur nb_rappels_retrait_envoyes est partagé par
 * toutes les lignes d'un même code_reservation (migration 009).
 *
 * Best effort, comme tous les emails du système : si le client n'a aucun
 * canal email exploitable, le rappel est simplement sauté — le statut reste
 * de toute façon visible dans "Mes réservations".
 *
 * Fréquence recommandée : toutes les 10 à 15 minutes.
 *
 * Exécution manuelle : php database/cron/rappel_retrait.php
 */

require_once __DIR__ . '/../../int_Public/Dos-php/config.php';
require_once __DIR__ . '/../../int_Public/Dos-php/mailer_config.php';
require_once __DIR__ . '/../../int_Public/Dos-php/canaux_recuperation.php';

const VERROU_ADVISORY_ID = 918273649; // identifiant arbitraire mais stable, propre à ce cron

$verrou_obtenu = $pdo->query('SELECT pg_try_advisory_lock(' . VERROU_ADVISORY_ID . ')')->fetchColumn();

if (!$verrou_obtenu) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Une autre exécution est déjà en cours — arrêt sans erreur.\n");
    exit(0);
}

try {

    // ===================================================================
    // 1. Repérer les réservations confirmées pas encore au maximum de
    //    rappels, avec le temps restant avant expiration. Regroupées par
    //    code_reservation : une seule ligne suffit par groupe pour
    //    connaître client/pharmacie/date/compteur, puisque toutes les
    //    lignes d'un même code partagent le même expire_at et le même
    //    compteur (mis à jour ensemble, cf. bloc 2 plus bas).
    // ===================================================================

    $sql_a_rappeler = "SELECT
                            r.code_reservation,
                            r.id_user,
                            r.expire_at,
                            MIN(r.nb_rappels_retrait_envoyes) AS nb_rappels_retrait_envoyes,
                            EXTRACT(EPOCH FROM (r.expire_at - NOW())) / 3600 AS heures_restantes,
                            p.nom_pharmacie,
                            p.adresse
                        FROM reservations r
                        JOIN stocks s     ON s.id_stock = r.id_stock
                        JOIN pharmacies p ON p.id_pharmacie = s.id_pharmacie
                        WHERE r.statut = 'confirmee'
                          AND r.nb_rappels_retrait_envoyes < 2
                          AND r.expire_at IS NOT NULL
                          AND r.expire_at > NOW()
                        GROUP BY r.code_reservation, r.id_user, r.expire_at, p.nom_pharmacie, p.adresse";

    $a_verifier = $pdo->query($sql_a_rappeler)->fetchAll(PDO::FETCH_ASSOC);

    $sql_update_compteur = $pdo->prepare(
        "UPDATE reservations SET nb_rappels_retrait_envoyes = ? WHERE code_reservation = ?"
    );

    $sql_user = $pdo->prepare(
        "SELECT prenom, phone_email, email_recuperation, telephone_recuperation FROM users WHERE id_user = ?"
    );

    $nb_rappels_envoyes = 0;

    foreach ($a_verifier as $reservation) {

        $heures = (float) $reservation['heures_restantes'];

        // Seuil de rappel atteint à cet instant, d'après le temps restant.
        if ($heures <= 1) {
            $niveau_du = 2;
        } elseif ($heures <= 2) {
            $niveau_du = 1;
        } else {
            $niveau_du = 0;
        }

        $niveau_actuel = (int) $reservation['nb_rappels_retrait_envoyes'];

        if ($niveau_du <= $niveau_actuel) {
            continue; // rien à envoyer pour cette réservation à cet instant
        }

        // On rattrape directement au niveau dû (jamais plusieurs emails d'un
        // coup, même si les deux seuils ont été manqués pendant une panne).
        // Marqué AVANT l'envoi : mieux vaut rater un rappel dans un cas
        // extrême que d'en envoyer deux au même client.
        $sql_update_compteur->execute([$niveau_du, $reservation['code_reservation']]);

        $sql_user->execute([$reservation['id_user']]);
        $user = $sql_user->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            continue;
        }

        $destinataire = resoudreEmailContact($user);

        if ($destinataire === null) {
            continue;
        }

        $minutes_restantes = max(0, (int) round($heures * 60));

        $sujet = "Rappel — votre réservation vous attend à la pharmacie";
        $corps = "
            <p>Bonjour " . htmlspecialchars($user['prenom']) . ",</p>
            <p>Votre réservation (code <strong>" . htmlspecialchars($reservation['code_reservation']) . "</strong>)
               à la pharmacie <strong>" . htmlspecialchars($reservation['nom_pharmacie']) . "</strong>
               (" . htmlspecialchars($reservation['adresse']) . ") expire dans environ "
               . $minutes_restantes . " minutes.</p>
            <p>Passé ce délai, votre réservation sera automatiquement annulée et vous devrez la renouveler.</p>
        ";

        if (envoyerEmail($destinataire, $sujet, $corps)) {
            $nb_rappels_envoyes++;
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] $nb_rappels_envoyes rappel(s) de retrait envoyé(s).\n";

} catch (Throwable $e) {

    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Échec : " . $e->getMessage() . "\n");
    exit(1);

} finally {

    $pdo->query('SELECT pg_advisory_unlock(' . VERROU_ADVISORY_ID . ')');
}