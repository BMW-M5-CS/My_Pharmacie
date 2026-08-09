<?php

require_once 'config.php';

$id_produit = $_GET['id_produit'] ?? null;

if (!$id_produit || !is_numeric($id_produit)){
    http_response_code(400);
    echo json_encode(['erreur' => 'ID invalide']);
    exit();
}

$sql_produit = "SELECT
                id_produit,
                nom_medicament,
                nom_generique,
                forme_pharmaceutique,
                dosage,
                conditionnement,
                description,
                prix_unitaire_fcfa
            FROM produits WHERE id_produit = ?";

$stmt = $pdo->prepare($sql_produit);
$stmt ->execute([$id_produit]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$produit){
    http_response_code(404);
    echo json_encode(['erreur' => 'Produit introuvable']);
    exit();
}

$sql_pharmacies = "SELECT
                     p.id_pharmacie,
                     p.nom_pharmacie, 
                     s.id_stock,
                     p.adresse,
                     p.ville,
                     p.commune,
                     p.quartier,
                     p.telephone_pharmacie,
                     p.heure_ouverture,
                     p.heure_fermeture,
                     p.latitude,
                     p.longitude
                FROM pharmacies p JOIN stocks s ON s.id_pharmacie = p.id_pharmacie 
                WHERE s.id_produit = ? AND s.quantite_disponible > 0
                ORDER BY p.nom_pharmacie ASC";

$stmt2 = $pdo->prepare($sql_pharmacies);
$stmt2->execute([$id_produit]);
$id_pharmacies = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$produit['pharmacies'] = $id_pharmacies;

header('Content-Type: application/json');
echo json_encode($produit, JSON_UNESCAPED_UNICODE);

?>
