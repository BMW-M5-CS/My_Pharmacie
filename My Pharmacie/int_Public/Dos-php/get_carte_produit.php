<?php

require_once 'config.php';

header('Content-Type: application/json');

$recherche = trim($_GET['recherche'] ?? '');

if ($recherche === '') {
    echo json_encode([]);
    exit();
}


// Une pharmacie peut apparaître plusieurs fois ici si plusieurs de ses produits
// correspondent à la recherche (ex: "Paracétamol" et "Paracétamol Effervescent") ;
// le regroupement par pharmacie se fait côté client. On ne renvoie jamais la
// quantité en stock, seulement le fait qu'il y en ait (> 0).

$sql = "SELECT p.id_pharmacie, p.nom_pharmacie, p.adresse, p.ville, p.commune, p.quartier,
               p.latitude, p.longitude, p.statut_garde,
               p.heure_ouverture, p.heure_fermeture, p.telephone_pharmacie,
               pr.id_produit, pr.nom_medicament, pr.forme_pharmaceutique, pr.prix_unitaire_fcfa, s.id_stock
        FROM pharmacies p
        JOIN stocks s   ON s.id_pharmacie = p.id_pharmacie
        JOIN produits pr ON pr.id_produit = s.id_produit
        WHERE pr.nom_medicament ILIKE ? AND s.quantite_disponible > 0
        ORDER BY p.nom_pharmacie ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['%' . $recherche . '%']);
$resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($resultats, JSON_UNESCAPED_UNICODE);
