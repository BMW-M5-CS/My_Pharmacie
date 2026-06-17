<?php

require_once 'config.php';

$id_produit = $_GET['id_produit'] ?? null;

if (!$id_produit || !$is_numeric($id_produit)){
    http_response_code(400);
    echo json_encode(['erreur' => 'ID invalide']);
    exit();
}

?>
