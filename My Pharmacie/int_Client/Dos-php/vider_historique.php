<?php
require_once '../../int_Public/Dos-php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../int_Public/Dos-page/conex.php');
    exit();
}

$csrf_recu = $_POST['csrf_token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_recu)) {
    die('Session invalide, veuillez recharger la page.');
}

$sql = "DELETE FROM historique_recherche WHERE id_user = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);

header('Location: ../Dos-page/historique.php?vide=1');
exit();