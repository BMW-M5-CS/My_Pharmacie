<?php

require_once 'config.php';

$sql  = "SELECT id_pharmacie, nom_pharmacie, adresse, ville, commune, quartier,
                latitude, longitude, statut_garde,
                heure_ouverture, heure_fermeture, telephone_pharmacie
         FROM pharmacies
         ORDER BY nom_pharmacie ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($pharmacies, JSON_UNESCAPED_UNICODE);
?>