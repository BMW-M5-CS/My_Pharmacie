<?php

require_once '../../Include_general/session_init.php';
require_once '../../int_Public/Dos-php/config.php';
require_once '../Dos-php/verifier_session_admin.php';
require_once '../../int_Public/Dos-php/canaux_recuperation.php';
require_once '../../int_Public/Dos-php/mailer_config.php';

header('Content-Type: application/json');


// ===== Lecture des données envoyées =====

$donnees = json_decode(file_get_contents('php://input'), true);


// ===== Vérification du jeton CSRF =====

if (!isset($donnees['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $donnees['csrf_token'])) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Requête invalide, veuillez rafraîchir la page.']);
    exit();
}

$groupe_demande = trim($donnees['groupe_demande'] ?? '');
$action         = trim($donnees['action']         ?? ''); // 'accepter' ou 'rejeter'
$motif_rejet    = trim($donnees['motif']          ?? '');

if (empty($groupe_demande) || !in_array($action, ['accepter', 'rejeter'], true)) {

    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Requête incomplète.']);
    exit();
}

$est_administration_plateforme = ($_SESSION['admin_role_nom'] === 'administration_plateforme');


try {

    $pdo->beginTransaction();

    // ===================================================================
    // 1. Récupérer les lignes de la demande.
    //    - Un pharmacien normal : scopé strictement à SA pharmacie, jamais
    //      confiance au groupe_demande seul (il pourrait appartenir à une
    //      autre pharmacie si quelqu'un le devine ou le manipule).
    //    - L'administration plateforme : peut traiter n'importe quelle
    //      pharmacie, donc pas de filtre id_pharmacie ici pour elle.
    // ===================================================================

    if ($est_administration_plateforme) {

        $sql_lignes = "SELECT r.id_stock, r.quantite_reservee, r.id_user, s.id_pharmacie
                       FROM reservations r
                       JOIN stocks s ON s.id_stock = r.id_stock
                       WHERE r.groupe_demande = ?
                         AND r.statut = 'demandee'
                       FOR UPDATE OF r";
        $stmt_lignes = $pdo->prepare($sql_lignes);
        $stmt_lignes->execute([$groupe_demande]);

    } else {

        $sql_lignes = "SELECT r.id_stock, r.quantite_reservee, r.id_user, s.id_pharmacie
                       FROM reservations r
                       JOIN stocks s ON s.id_stock = r.id_stock
                       WHERE r.groupe_demande = ?
                         AND s.id_pharmacie = ?
                         AND r.statut = 'demandee'
                       FOR UPDATE OF r";
        $stmt_lignes = $pdo->prepare($sql_lignes);
        $stmt_lignes->execute([$groupe_demande, $_SESSION['admin_pharmacie_id']]);
    }

    $lignes = $stmt_lignes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lignes)) {

        $pdo->rollBack();
        echo json_encode(['succes' => false, 'message' => 'Cette demande n\'existe plus ou a déjà été traitée.']);
        exit();
    }

    $id_user = $lignes[0]['id_user'];


    if ($action === 'rejeter') {

        // ===================================================================
        // Rejet : aucun stock n'était bloqué, donc rien à libérer — juste
        // marquer les lignes comme rejetées.
        // ===================================================================

        $sql_update = "UPDATE reservations
                        SET statut = 'rejetee', motif_rejet = ?, date_decision_pharmacien = NOW()
                        WHERE groupe_demande = ? AND statut = 'demandee'";
        $pdo->prepare($sql_update)->execute([$motif_rejet ?: null, $groupe_demande]);

        $pdo->commit();

        // ----- Email au client (best effort, jamais bloquant pour la décision elle-même) -----
        envoyerNotificationClient($pdo, $id_user, 'rejetee', null, $motif_rejet);

        echo json_encode(['succes' => true, 'message' => 'Demande rejetée.']);
        exit();
    }


    // ===================================================================
    // Acceptation : revalidation FERME du stock, verrouillée (FOR UPDATE),
    // maintenant que c'est le moment où le stock est réellement engagé.
    // ===================================================================

    $sql_stock = "SELECT
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r2.quantite_reservee)
                        FROM reservations r2
                        WHERE r2.id_stock = s.id_stock
                          AND r2.statut = 'confirmee'
                          AND r2.expire_at > NOW()
                    ), 0) AS max_reservable
                  FROM stocks s
                  WHERE s.id_stock = ?
                  FOR UPDATE";

    $stmt_stock = $pdo->prepare($sql_stock);

    foreach ($lignes as $ligne) {

        $stmt_stock->execute([$ligne['id_stock']]);
        $resultat = $stmt_stock->fetch(PDO::FETCH_ASSOC);
        $stmt_stock->closeCursor();

        if (!$resultat || (int) $resultat['max_reservable'] < (int) $ligne['quantite_reservee']) {

            $pdo->rollBack();
            echo json_encode([
                'succes'  => false,
                'message' => 'Stock insuffisant pour au moins un produit — la demande n\'a pas pu être acceptée. '
                           . 'Vous pouvez la rejeter à la place.'
            ]);
            exit();
        }
    }

    // Stock confirmé disponible pour tous les produits : on peut générer le
    // code et confirmer.
    $code_reservation = 'RES-' . strtoupper(bin2hex(random_bytes(3)));

    $sql_update = "UPDATE reservations
                    SET statut = 'confirmee',
                        code_reservation = ?,
                        expire_at = NOW() + INTERVAL '5 hours',
                        date_decision_pharmacien = NOW()
                    WHERE groupe_demande = ? AND statut = 'demandee'";
    $pdo->prepare($sql_update)->execute([$code_reservation, $groupe_demande]);

    $pdo->commit();

    envoyerNotificationClient($pdo, $id_user, 'confirmee', $code_reservation, null);

    echo json_encode(['succes' => true, 'message' => 'Demande acceptée.']);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur traitement décision réservation : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
}


// ============================================================================
// Envoie un email au client pour l'informer de la décision. Volontairement
// "best effort" : si le client n'a aucun canal email exploitable, l'email est
// simplement sauté — le statut reste de toute façon visible dans
// "Mes réservations" dès qu'il se connecte (jamais de dépendance totale au
// canal email, cf. décision prise pendant la conception).
// ============================================================================

function envoyerNotificationClient(PDO $pdo, int $id_user, string $decision, ?string $code, ?string $motif): void {

    $stmt = $pdo->prepare("SELECT prenom, phone_email, email_recuperation, telephone_recuperation FROM users WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return;
    }

    $destinataire = resoudreEmailContact($user);

    if ($destinataire === null) {
        return;
    }

    if ($decision === 'confirmee') {

        $sujet = "Votre réservation est confirmée — MaPharmacie";
        $corps = "
            <p>Bonjour " . htmlspecialchars($user['prenom']) . ",</p>
            <p>Votre réservation a été acceptée par la pharmacie.</p>
            <p>Voici votre code de retrait : <strong>" . htmlspecialchars($code) . "</strong></p>
            <p>Présentez ce code à la pharmacie pour récupérer vos produits.</p>
        ";

    } else {

        $sujet = "Votre demande de réservation a été rejetée — MaPharmacie";
        $corps = "
            <p>Bonjour " . htmlspecialchars($user['prenom']) . ",</p>
            <p>Votre demande de réservation a été rejetée par la pharmacie.</p>"
            . ($motif ? "<p>Motif : " . htmlspecialchars($motif) . "</p>" : "")
            . "<p>Vous pouvez consulter le détail dans votre espace \"Mes réservations\".</p>
        ";
    }

    envoyerEmail($destinataire, $sujet, $corps);
}
