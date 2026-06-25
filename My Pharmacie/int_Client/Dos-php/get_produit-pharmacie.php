<?php
require_once '../../int_Public/Dos-php/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Non connecté']);
    exit();
}

$id_pharmacie = $_GET['id_pharmacie'] ?? null;

if (!$id_pharmacie || !is_numeric($id_pharmacie)) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID Invalide']);
    exit();
}

// Pour chaque produit en stock dans cette pharmacie, on calcule :
// max_reservable = quantite_disponible - somme des réservations en_attente non expirées sur ce stock
$sql_produits = "SELECT 
                    p.id_produit,
                    s.id_stock,
                    p.nom_medicament,
                    p.forme_pharmaceutique,
                    p.prix_unitaire_fcfa,
                    s.quantite_disponible - COALESCE((
                        SELECT SUM(r.quantite_reservee)
                        FROM reservations r
                        WHERE r.id_stock = s.id_stock
                          AND r.statut = 'en_attente'
                          AND r.expire_at > NOW()
                    ), 0) AS max_reservable
                FROM produits p
                JOIN stocks s ON s.id_produit = p.id_produit
                WHERE s.id_pharmacie = ? AND s.quantite_disponible > 0
                ORDER BY p.nom_medicament ASC";

$stmt = $pdo->prepare($sql_produits);
$stmt->execute([$id_pharmacie]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sécurité : jamais de valeur négative envoyée au client
foreach ($produits as &$produit) {
    $produit['max_reservable'] = max(0, (int)$produit['max_reservable']);
}

header('Content-Type: application/json');
echo json_encode(['produits' => $produits], JSON_UNESCAPED_UNICODE);