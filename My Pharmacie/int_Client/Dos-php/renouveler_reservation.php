<?php
require_once '../../int_Public/Dos-php/config.php';

header('Content-Type: application/json');

// ===== Sécurité : utilisateur connecté =====
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['succes' => false, 'cas' => 'refuse', 'message' => 'Vous devez être connecté.']);
    exit();
}

$id_user = $_SESSION['user_id'];

$donnees          = json_decode(file_get_contents('php://input'), true);
$code_ancien      = trim($donnees['code_reservation'] ?? '');
$produits_choisis = $donnees['produits_choisis'] ?? null;

if (empty($code_ancien)) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'cas' => 'refuse', 'message' => 'Code de réservation manquant.']);
    exit();
}

try {

    // ===== Bloc 1 — Récupérer la réservation originale =====
    $sql_ancien = "SELECT
                        r.id_stock,
                        r.quantite_reservee,
                        r.statut,
                        r.expire_at,
                        p.nom_medicament,
                        p.forme_pharmaceutique,
                        s.id_pharmacie
                   FROM reservations r
                   JOIN stocks s   ON s.id_stock   = r.id_stock
                   JOIN produits p ON p.id_produit = s.id_produit
                   WHERE r.code_reservation = ?
                     AND r.id_user = ?";
    $stmt_ancien = $pdo->prepare($sql_ancien);
    $stmt_ancien->execute([$code_ancien, $id_user]);
    $lignes_anciennes = $stmt_ancien->fetchAll(PDO::FETCH_ASSOC);

    if (empty($lignes_anciennes)) {
        echo json_encode(['succes' => false, 'cas' => 'refuse', 'message' => 'Réservation introuvable.']);
        exit();
    }

    $premiere_ligne = $lignes_anciennes[0];

    // ===== Bloc 2 — Vérifications de sécurité =====

    if ($premiere_ligne['statut'] !== 'expiree') {
        echo json_encode(['succes' => false, 'cas' => 'refuse', 'message' => 'Cette réservation ne peut pas être renouvelée.']);
        exit();
    }

    $expire_at   = new DateTime($premiere_ligne['expire_at']);
    $maintenant  = new DateTime();
    $diff_heures = ($maintenant->getTimestamp() - $expire_at->getTimestamp()) / 3600;

    if ($diff_heures > 10) {
        echo json_encode([
            'succes'  => false,
            'cas'     => 'refuse',
            'message' => 'Le délai de renouvellement de 10 heures est dépassé. Veuillez refaire une recherche.'
        ]);
        exit();
    }

    $id_pharmacie = $premiere_ligne['id_pharmacie'];

    // ===== Bloc 3 — Vérification du stock pour chaque produit =====

    $sql_stock = "SELECT
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r2.quantite_reservee)
                        FROM reservations r2
                        WHERE r2.id_stock = s.id_stock
                          AND r2.statut = 'en_attente'
                          AND r2.expire_at > NOW()
                    ), 0) AS max_reservable
                  FROM stocks s
                  WHERE s.id_stock = ?";
    $stmt_stock = $pdo->prepare($sql_stock);

    $produits_ok        = [];
    $produits_problemes = [];

    foreach ($lignes_anciennes as $ligne) {
        $stmt_stock->execute([$ligne['id_stock']]);
        $stock      = $stmt_stock->fetch(PDO::FETCH_ASSOC);
        $stmt_stock->closeCursor();
        $disponible = max(0, (int)($stock['max_reservable'] ?? 0));
        $demande    = (int)$ligne['quantite_reservee'];

        if ($disponible >= $demande) {
            $produits_ok[] = [
                'id_stock' => $ligne['id_stock'],
                'nom'      => $ligne['nom_medicament'],
                'forme'    => $ligne['forme_pharmaceutique'],
                'quantite' => $demande
            ];
        } else {
            $produits_problemes[] = [
                'id_stock'   => $ligne['id_stock'],
                'nom'        => $ligne['nom_medicament'],
                'forme'      => $ligne['forme_pharmaceutique'],
                'demande'    => $demande,
                'disponible' => $disponible
            ];
        }
    }

    // ===== Bloc 4 — Décision =====

    if (!empty($produits_problemes) && $produits_choisis === null) {
        echo json_encode([
            'succes'             => true,
            'cas'                => 'partiel',
            'id_pharmacie'       => $id_pharmacie,
            'produits_ok'        => $produits_ok,
            'produits_problemes' => $produits_problemes
        ]);
        exit();
    }

    if ($produits_choisis !== null) {
        $liste_finale = $produits_choisis;
    } else {
        $liste_finale = $produits_ok;
    }

    if (empty($liste_finale)) {
        echo json_encode(['succes' => false, 'cas' => 'refuse', 'message' => 'Aucun produit disponible pour le renouvellement.']);
        exit();
    }

    // ===== Bloc 5 — Insertion de la nouvelle réservation =====
    $pdo->beginTransaction();

    $nouveau_code  = 'RES-' . strtoupper(bin2hex(random_bytes(3)));
    $expire_at_sql = "NOW() + INTERVAL '5 hours'";

    $sql_insert  = "INSERT INTO reservations
                        (id_user, id_stock, quantite_reservee, code_reservation, date_reservation, expire_at, statut)
                    VALUES (?, ?, ?, ?, NOW(), $expire_at_sql, 'en_attente')";
    $stmt_insert = $pdo->prepare($sql_insert);

    foreach ($liste_finale as $produit) {
        $stmt_insert->execute([
            $id_user,
            $produit['id_stock'],
            (int)$produit['quantite'],
            $nouveau_code
        ]);
    }

    $pdo->commit();

    $sql_expire  = "SELECT TO_CHAR(MAX(expire_at), 'DD/MM/YYYY à HH24:MI') AS expire_fmt
                    FROM reservations WHERE code_reservation = ?";
    $stmt_expire = $pdo->prepare($sql_expire);
    $stmt_expire->execute([$nouveau_code]);
    $expire_fmt  = $stmt_expire->fetch(PDO::FETCH_ASSOC)['expire_fmt'];

    echo json_encode([
        'succes'           => true,
        'cas'              => 'ok',
        'code_reservation' => $nouveau_code,
        'expire_at'        => $expire_fmt
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['succes' => false, 'cas' => 'erreur', 'message' => 'Erreur serveur. Veuillez réessayer.']);
}