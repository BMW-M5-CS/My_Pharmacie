<?php

require_once '../../int_Public/Dos-php/config.php';
require_once '../../int_Public/Dos-php/mailer_config.php';
require_once '../../Include_general/config_fonctionnalites.php';

header('Content-Type: application/json');


// ===== Vérification de la session utilisateur =====

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);
    echo json_encode(['succes' => false, 'message' => 'Vous devez être connecté.']);
    exit();
}

$id_user = $_SESSION['user_id'];


// ===== Lecture des données envoyées =====

$donnees = json_decode(file_get_contents('php://input'), true);


// ===== Vérification du jeton CSRF =====

if (!isset($donnees['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $donnees['csrf_token'])) {

    http_response_code(403);
    echo json_encode(['succes' => false, 'message' => 'Requête invalide, veuillez rafraîchir la page.']);
    exit();
}

$id_pharmacie = $donnees['id_pharmacie'] ?? null;
$produits     = $donnees['produits'] ?? [];

if (!$id_pharmacie || !is_numeric($id_pharmacie) || empty($produits)) {

    http_response_code(400);
    echo json_encode(['succes' => false, 'message' => 'Données de réservation invalides.']);
    exit();
}

// Une pharmacie désactivée ne doit plus jamais recevoir de nouvelle demande,
// même si quelqu'un contourne l'interface publique (qui ne l'affiche déjà
// plus) pour appeler cette route directement.
$stmt_statut_pharmacie = $pdo->prepare("SELECT statut FROM pharmacies WHERE id_pharmacie = ?");
$stmt_statut_pharmacie->execute([$id_pharmacie]);
$statut_pharmacie = $stmt_statut_pharmacie->fetchColumn();

if ($statut_pharmacie !== 'active') {

    http_response_code(404);
    echo json_encode(['succes' => false, 'message' => 'Cette pharmacie n\'est plus disponible.']);
    exit();
}


try {

    $pdo->beginTransaction();

    // Un identifiant technique lie les produits d'une même demande entre eux
    // (utile pour les afficher groupés dans "Mes réservations"), mais ce
    // n'est PAS encore le code que le client donnera en pharmacie : ce code
    // n'existe qu'une fois la demande acceptée par le pharmacien (voir
    // int_Admin, écran de décision).
    $groupe_demande     = bin2hex(random_bytes(8));
    $expire_demande_sql = "NOW() + INTERVAL '12 hours'";

    // Vérification indicative du stock, PAS un blocage : le stock déclaratif
    // n'est réellement réservé qu'au moment où le pharmacien accepte (revérifié
    // à cet instant précis). Ici, on évite juste de laisser un client demander
    // une quantité déjà manifestement impossible à honorer.
    $sql_verif = "SELECT 
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r.quantite_reservee)
                        FROM reservations r
                        WHERE r.id_stock = s.id_stock
                          AND r.statut = 'confirmee'
                          AND r.expire_at > NOW()
                    ), 0) AS max_reservable
                  FROM stocks s
                  WHERE s.id_stock = ? AND s.id_pharmacie = ?";

    $stmt_verif = $pdo->prepare($sql_verif);

    $sql_insert = "INSERT INTO reservations 
                    (id_user, id_stock, quantite_reservee, groupe_demande, date_reservation, expire_demande_at, statut)
                    VALUES (?, ?, ?, ?, NOW(), $expire_demande_sql, 'demandee')";

    $stmt_insert = $pdo->prepare($sql_insert);


    foreach ($produits as $item) {

        $id_stock = $item['id_stock'] ?? null;
        $quantite = (int) ($item['quantite'] ?? 0);

        if (!$id_stock || $quantite <= 0) {
            throw new Exception('Produit invalide dans le panier.');
        }

        $stmt_verif->execute([$id_stock, $id_pharmacie]);
        $resultat = $stmt_verif->fetch(PDO::FETCH_ASSOC);
        $stmt_verif->closeCursor();

        if (!$resultat || (int) $resultat['max_reservable'] < $quantite) {

            $pdo->rollBack();
            echo json_encode([
                'succes'  => false,
                'message' => 'Un ou plusieurs produits ne sont plus disponibles en quantité suffisante.'
            ]);
            exit();
        }

        $stmt_insert->execute([$id_user, $id_stock, $quantite, $groupe_demande]);
    }

    $pdo->commit();

    // ===== Notification immédiate au(x) pharmacien(s) de cette pharmacie =====
    // "Best effort" comme tous les emails du système : si l'envoi échoue, la
    // demande reste quand même bien créée — le pharmacien la verra de toute
    // façon dans sa liste de demandes en attente, et sera relancé plus tard
    // si jamais il ne répond pas (voir database/cron/relancer_pharmaciens.php).
    $sql_admins = "SELECT a.email, a.prenom, p.nom_pharmacie
                   FROM administrateurs a
                   JOIN pharmacies p ON p.id_pharmacie = a.id_pharmacie
                   WHERE a.id_pharmacie = ? AND a.statut = 'actif'";
    $stmt_admins = $pdo->prepare($sql_admins);
    $stmt_admins->execute([$id_pharmacie]);
    $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

    foreach ($admins as $admin) {

        $sujet = "Nouvelle demande de réservation — " . $admin['nom_pharmacie'];
        $corps = "
            <p>Bonjour " . htmlspecialchars($admin['prenom']) . ",</p>
            <p>Une nouvelle demande de réservation vient d'arriver pour " . htmlspecialchars($admin['nom_pharmacie']) . ".</p>
            <p>Vous avez 12h pour l'accepter ou la rejeter.</p>
            <p><a href=\"" . SITE_URL . "/int_Admin/Dos-page/demandes_reservation.php\">Voir la demande dans mon espace pharmacien</a></p>
        ";

        envoyerEmail($admin['email'], $sujet, $corps);
    }

    // Le client n'a ni code, ni statut "confirmé" tout de suite : sa demande
    // part vers le pharmacien, qui a 12h pour l'accepter ou la rejeter.
    echo json_encode([
        'succes'  => true,
        'message' => 'Votre demande de réservation a été envoyée à la pharmacie. '
                   . 'Vous recevrez un email dès qu\'elle sera acceptée, avec votre code de retrait. '
                   . 'Vous pouvez aussi suivre son statut dans "Mes réservations".'
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la réservation. Veuillez réessayer .']);
}