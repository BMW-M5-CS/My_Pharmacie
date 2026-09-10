<?php
/**
 * Cron : recalcule statistiques_produits_populaires à partir de reservations.
 *
 * Remplace le calcul en direct qui tournait auparavant à chaque affichage de
 * chaque fiche pharmacie (get_pharmacie.php) : un GROUP BY sur toute la table
 * `reservations` à chaque requête ne tient pas à l'échelle. Ce script fait ce
 * calcul une seule fois, périodiquement, et get_pharmacie.php lit désormais
 * simplement le résultat déjà prêt.
 *
 * Idempotent : peut être relancé sans risque même si une exécution
 * précédente a été interrompue en cours de route (transaction + verrou).
 * Un verrou advisory PostgreSQL empêche deux exécutions de se chevaucher si
 * le cron redémarre avant la fin de la précédente (ex. calcul anormalement
 * long un jour de forte charge).
 *
 * Fréquence recommandée : toutes les 15 à 60 minutes selon la fraîcheur
 * souhaitée pour le badge "recommandée" et le taux de disponibilité affichés
 * sur les fiches pharmacie.
 *
 * Exécution manuelle : php database/cron/recalculer_produits_populaires.php
 * Planification :
 *   - Production (Linux)  : crontab -e -> */30 * * * * php /chemin/vers/ce/fichier.php
 *   - Développement (WAMP): Planificateur de tâches Windows, ou exécution manuelle
 *     pendant les tests — ce script n'a aucun effet de bord si on oublie de le
 *     planifier, il rend simplement les statistiques figées jusqu'au prochain lancement.
 */

require_once __DIR__ . '/../../int_Public/Dos-php/config.php';

const VERROU_ADVISORY_ID = 918273645; // identifiant arbitraire mais stable, propre à ce cron

$verrou_obtenu = $pdo->query('SELECT pg_try_advisory_lock(' . VERROU_ADVISORY_ID . ')')->fetchColumn();

if (!$verrou_obtenu) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Une autre exécution est déjà en cours — arrêt sans erreur.\n");
    exit(0);
}

try {

    $pdo->beginTransaction();

    $sql = "SELECT s.id_produit, COUNT(*) AS nb
            FROM reservations r
            JOIN stocks s ON s.id_stock = r.id_stock
            GROUP BY s.id_produit";

    $resultats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // TRUNCATE plutôt que DELETE : plus rapide sur une table qu'on vide
    // entièrement à chaque recalcul, et reste annulable dans la transaction
    // en cas d'erreur juste après.
    $pdo->exec('TRUNCATE TABLE statistiques_produits_populaires');

    $stmt_insert = $pdo->prepare(
        'INSERT INTO statistiques_produits_populaires (id_produit, nb_reservations, calcule_le)
         VALUES (?, ?, NOW())'
    );

    foreach ($resultats as $ligne) {
        $stmt_insert->execute([$ligne['id_produit'], $ligne['nb']]);
    }

    $pdo->commit();

    echo "[" . date('Y-m-d H:i:s') . "] statistiques_produits_populaires recalculée (" . count($resultats) . " produits).\n";

} catch (Throwable $e) {

    $pdo->rollBack();
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] Échec du recalcul : " . $e->getMessage() . "\n");
    exit(1);

} finally {

    $pdo->query('SELECT pg_advisory_unlock(' . VERROU_ADVISORY_ID . ')');
}
