
<?php

require_once 'config.php';
require_once 'fonctions_notation.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: conex.php');
    exit();
}

$csrf_recu    = $_POST['csrf_token'] ?? '';
$id_pharmacie = $_POST['id_pharmacie'] ?? null;
$note         = $_POST['note'] ?? null;
$commentaire  = trim($_POST['commentaire'] ?? '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_recu)) {
    die('Session invalide, veuillez recharger la page.');
}

if (!$id_pharmacie || !is_numeric($id_pharmacie) || !is_numeric($note) || $note < 1 || $note > 5) {
    die('Données invalides.');
}

$id_user = $_SESSION['user_id'];

if (!verifierEligibiliteAvis($pdo, $id_user, (int) $id_pharmacie)) {
    die('Vous devez avoir une réservation confirmée dans cette pharmacie pour la noter.');
}

$sql = "INSERT INTO avis (id_user, id_pharmacie, note, commentaire, date_avis)
        VALUES (?, ?, ?, ?, NOW())
        ON CONFLICT (id_user, id_pharmacie)
        DO UPDATE SET note = EXCLUDED.note, commentaire = EXCLUDED.commentaire, date_avis = NOW()";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_user, $id_pharmacie, (int) $note, $commentaire ?: null]);

header('Location: ../Dos-page/avis-pharmacie.php?id_pharmacie=' . urlencode($id_pharmacie) . '&succes=1');
exit();