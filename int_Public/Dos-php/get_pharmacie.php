<?php

require_once 'config.php';

$id_pharmacie = $_GET['id_pharmacie'] ?? null;

if(!$id_pharmacie || !is_numeric($id_pharmacie)){
    http_response_code(400);
    echo json_encode(['erreur' => 'ID Invalide']);
    exit();
}

$sql_pharmacie = "SELECT 
                    id_pharmacie, 
                    nom_pharmacie, 
                    adresse, 
                    ville, 
                    commune, 
                    quartier, 
                    telephone_pharmacie, 
                    heure_ouverture, 
                    heure_fermeture,
                    statut_garde, 
                    latitude, 
                    longitude
                  FROM pharmacies WHERE id_pharmacie = ?";

$stmt = $pdo->prepare($sql_pharmacie);
$stmt ->execute([$id_pharmacie]);
$pharmacie = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$pharmacie){
    http_response_code(404);
    echo json_encode(['erreur' => 'Pharmacie introuvable']);
    exit();
}

$sql_produits = "SELECT 
                    p.id_produit, 
                    s.id_stock,
                    p.nom_medicament, 
                    p.forme_pharmaceutique,
                    p.prix_unitaire_fcfa
                FROM produits p 
                JOIN stocks s ON s.id_produit = p.id_produit 
                WHERE s.id_pharmacie = ? AND s.quantite_disponible > 0 
                ORDER BY p.nom_medicament ASC";

$stmt2 = $pdo->prepare($sql_produits);
$stmt2->execute([$id_pharmacie]);
$produits = $stmt2->fetchAll(PDO::FETCH_ASSOC);


$pharmacie['produits'] = $produits;

header('Content-Type: application/json');
echo json_encode($pharmacie, JSON_UNESCAPED_UNICODE);

?>