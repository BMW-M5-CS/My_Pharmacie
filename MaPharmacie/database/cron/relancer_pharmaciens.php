<?php
/**
 * Cron : relance par email les pharmaciens qui n'ont pas encore répondu à
 * une demande de réservation.
 *
 * 3 relances prévues par demande, à H+10h, H+11h et H+11h45 (H = création de
 * la demande, délai total de 12h avant expiration — cf. cron
 * expirer_reservations.php et migration 005).
 *
 * Idempotent même en cas d'exécution irrégulière (cron en retard, redémarré
 * après une panne) : on calcule le nombre de relances QUI DEVRAIENT avoir
 * été envoyées à cet instant, et on n'envoie que le complément manquant —
 * jamais plus d'une relance par exécution pour une même demande, même si
 * plusieurs seuils ont été dépassés d'un coup pendant que le cron était
 * arrêté (pas de rattrapage en rafale qui spammerait le pharmacien).
 *
 * Fréquence recommandée : toutes les 10 à 15 minutes.
 *
 * Exécution manuelle : php database/cron/relancer_pharmaciens.php
 */

require_once __DIR__ . '/../../int_Public/Dos-php/config.php';
require_once __DIR__ . '/../../int_Public/Dos-php/mailer_config.php';
require_once __DIR__ . '/../../Include_general/config_fonctionnalites.php';

const VERROU_ADVISORY_ID = 918273647; // identifiant arbitraire mais stable, propre à ce cron

$verrou_obtenu = $pdo->query('SELECT pg_try_advisory_lock(' . VERROU_ADVISORY_ID . ')')->fetchColumn();

if (!$verrou_obtenu) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Une autre exécution est déjà en cours — arrêt sans erreur.\n");
    exit(0);
}

try {

    // ===================================================================
    // 1. Repérer les demandes en attente, avec leur nombre d'heures écoulées
    //    et le nombre de relances déjà envoyées (identique pour toutes les
    //    lignes d'une même demande — on prend juste la première).
    // ===================================================================

    $sql_demandes = "SELECT
                        r.groupe_demande,
                        MIN(r.nb_relances_envoyees) AS nb_relances_envoyees,
                        MIN(r.date_reservation)     AS date_reservation,
                        EXTRACT(EPOCH FROM (NOW() - MIN(r.date_reservation))) / 3600 AS heures_ecoulees,
                        s.id_pharmacie
                      FROM reservations r
                      JOIN stocks s ON s.id_stock = r.id_stock
                      WHERE r.statut = 'demandee'
                        AND r.nb_relances_envoyees < 3
                      GROUP BY r.groupe_demande, s.id_pharmacie";

    $demandes = $pdo->query($sql_demandes)->fetchAll(PDO::FETCH_ASSOC);

    $sql_update_compteur = $pdo->prepare(
        "UPDATE reservations SET nb_relances_envoyees = ? WHERE groupe_demande = ?"
    );

    $sql_admins_pharmacie = $pdo->prepare(
        "SELECT email, prenom FROM administrateurs WHERE id_pharmacie = ? AND statut = 'actif'"
    );

    $nb_relances_envoyees_total = 0;

    foreach ($demandes as $demande) {

        $heures = (float) $demande['heures_ecoulees'];

        // Seuil de relance atteint à cet instant, d'après le temps écoulé.
        if ($heures >= 11.75) {
            $niveau_du = 3;
        } elseif ($heures >= 11) {
            $niveau_du = 2;
        } elseif ($heures >= 10) {
            $niveau_du = 1;
        } else {
            $niveau_du = 0;
        }

        $niveau_actuel = (int) $demande['nb_relances_envoyees'];

        if ($niveau_du <= $niveau_actuel) {
            continue; // rien à envoyer pour cette demande à cet instant
        }

        // On rattrape directement au niveau dû (jamais plusieurs emails d'un
        // coup, même si plusieurs seuils ont été manqués pendant une panne).
        $sql_update_compteur->execute([$niveau_du, $demande['groupe_demande']]);

        // ----- Envoi de l'email à tous les comptes actifs de cette pharmacie -----

        $sql_admins_pharmacie->execute([$demande['id_pharmacie']]);
        $admins = $sql_admins_pharmacie->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admins as $admin) {

            $sujet = "Une demande de réservation attend votre réponse";
            $corps = "
                <p>Bonjour " . htmlspecialchars($admin['prenom']) . ",</p>
                <p>Une demande de réservation est en attente depuis plus de " . (int) $heures . "h.</p>
                <p>Merci de vous connecter à votre espace pharmacien pour l'accepter ou la rejeter avant son expiration.</p>
                <p><a href=\"" . SITE_URL . "/int_Admin/Dos-page/demandes_reservation.php\">Voir la demande dans mon espace pharmacien</a></p>
            ";

            envoyerEmail($admin['email'], $sujet, $corps);
        }

        $nb_relances_envoyees_total++;
    }

    echo "[" . date('Y-m-d H:i:s') . "] $nb_relances_envoyees_total relance(s) envoyée(s).\n";

} catch (Throwable $e) {

    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Échec : " . $e->getMessage() . "\n");
    exit(1);

} finally {

    $pdo->query('SELECT pg_advisory_unlock(' . VERROU_ADVISORY_ID . ')');
}
