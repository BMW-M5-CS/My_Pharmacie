<?php

require_once 'config.php';

header('Content-type: application/json');

$q = trim($_GET['q'] ?? '');

if(mb_strlen($q) < 2){
    echo json_encode([]);
    exit();
}

$sql = "SELECT nom_medicament FROM produits WHERE nom_medicament ILIKE ?
        ORDER BY nom_medicament ASC LIMIT 8";

$stmt = $pdo->prepare($sql);
$stmt->execute(['%' . $q . '%']);
$resultats = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($resultats, JSON_UNESCAPED_UNICODE);
