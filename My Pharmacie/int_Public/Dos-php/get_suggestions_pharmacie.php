<?php

require_once 'config.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT nom_pharmacie FROM pharmacies WHERE nom_pharmacie ILIKE ? 
        ORDER BY nom_pharmacie ASC LIMIT 8";
        
$stmt = $pdo->prepare($sql);
$stmt->execute(['%' . $q . '%']);
$resultats = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($resultats, JSON_UNESCAPED_UNICODE);