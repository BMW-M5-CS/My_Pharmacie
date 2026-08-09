<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    // Visiteur non connecté : rien à enregistrer, mais pas une erreur pour autant
    echo json_encode(['succes' => false, 'raison' => 'non_connecte']);
    exit();
}

$donnees   = json_decode(file_get_contents('php://input'), true);
$id_produit = $donnees['id_produit'] ?? null;

if (!$id_produit || !is_numeric($id_produit)) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'raison' => 'id_invalide']);
    exit();
}

$sql = "INSERT INTO historique_recherche (id_user, id_produit, date_consultee)
        VALUES (?, ?, NOW())
        ON CONFLICT (id_user, id_produit)
        DO UPDATE SET date_consultee = NOW()";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], (int) $id_produit]);

echo json_encode(['succes' => true]);