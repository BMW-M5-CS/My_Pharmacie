<?php

require_once '../../int_Public/Dos-php/config.php';

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


try {

    $pdo->beginTransaction();

    // Code de réservation unique, lisible, lié à la pharmacie et à un timestamp
    $code_reservation = 'RES-' . strtoupper(bin2hex(random_bytes(3)));
    $expire_at_sql     = "NOW() + INTERVAL '5 hours'";

    $sql_verif = "SELECT 
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r.quantite_reservee)
                        FROM reservations r
                        WHERE r.id_stock = s.id_stock
                          AND r.statut = 'en_attente'
                          AND r.expire_at > NOW()
                    ), 0) AS max_reservable
                  FROM stocks s
                  WHERE s.id_stock = ? AND s.id_pharmacie = ?
                  FOR UPDATE";

    $stmt_verif = $pdo->prepare($sql_verif);

    $sql_insert = "INSERT INTO reservations 
                    (id_user, id_stock, quantite_reservee, code_reservation, date_reservation, expire_at, statut)
                    VALUES (?, ?, ?, ?, NOW(), $expire_at_sql, 'en_attente')";

    $stmt_insert = $pdo->prepare($sql_insert);


    foreach ($produits as $item) {

        $id_stock = $item['id_stock'] ?? null;
        $quantite = (int) ($item['quantite'] ?? 0);

        if (!$id_stock || $quantite <= 0) {
            throw new Exception('Produit invalide dans le panier.');
        }

        // Revalidation stricte côté serveur — jamais confiance au panier envoyé par le client
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

        $stmt_insert->execute([$id_user, $id_stock, $quantite, $code_reservation]);
    }

    $pdo->commit();


    // ===== Récupère l'heure d'expiration réelle pour l'affichage =====

    $sql_expire = "SELECT TO_CHAR(MAX(expire_at), 'HH24:MI') AS heure_expire 
                    FROM reservations WHERE code_reservation = ?";

    $stmt_expire = $pdo->prepare($sql_expire);
    $stmt_expire->execute([$code_reservation]);
    $heure_expire = $stmt_expire->fetch(PDO::FETCH_ASSOC)['heure_expire'];

    echo json_encode([
        'succes'           => true,
        'code_reservation' => $code_reservation,
        'expire_at'        => $heure_expire
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['succes' => false, 'message' => 'Erreur lors de la réservation. Veuillez réessayer .']);
}